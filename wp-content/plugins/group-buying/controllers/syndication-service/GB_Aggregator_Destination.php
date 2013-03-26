<?php

/**
 * GBS Syndication Service Destination.
 *
 * @package GBS
 * @subpackage Syndication
 */

class GB_Aggregator_Destination extends GB_Aggregator_Plugin {
	const DEAL_URI_META_KEY = '_syndicated_deal_uri';
	const DEAL_LINK_META_KEY = '_syndicated_deal_link';
	const DEAL_SOURCE_META_KEY = '_syndicated_deal_source';
	const DEAL_SOURCE_URL_META_KEY = '_syndicated_deal_source_url';
	const MAX_PRICE_META_KEY = '_syndicated_deal_max_price';
	const MIN_PRICE_META_KEY = '_syndicated_deal_min_price';
	const ORIGINAL_THUMBNAIL_META_KEY = '_syndicated_deal_original_thumbnail_url';
	const OPTION_SUBSCRIBED_CATEGORIES = 'gbs_aggregator_subscribed_categories';
	const OPTION_SUBSCRIBED_LOCATIONS = 'gbs_aggregator_subscribed_locations';
	const OPTION_CATEGORY_MAPPING = 'gbs_aggregator_category_mapping';
	const OPTION_LOCATION_MAPPING = 'gbs_aggregator_location_mapping';
	const OPTION_AGG_NOTES = 'gbs_aggregator_notes';

	private static $instance;

	/**
	 * Create the instance of the class
	 *
	 * @static
	 * @return void
	 */
	public static function init() {
		self::$instance = self::get_instance();

		// Admin columns
		add_filter( 'manage_edit-'.Group_Buying_Deal::POST_TYPE.'_columns', array( get_class(), 'register_columns' ), 50 );
		add_filter( 'manage_'.Group_Buying_Deal::POST_TYPE.'_posts_custom_column', array( get_class(), 'column_display' ), 50, 2 );
		//add_filter( 'manage_edit-'.Group_Buying_Deal::POST_TYPE.'_sortable_columns', array( get_class(), 'sortable_columns' ) );
		//add_filter( 'request', array( get_class(), 'column_orderby' ) );
	}

	public static function is_syndicated( $post_id ) {
		$uri = get_post_meta( $post_id, self::DEAL_URI_META_KEY, TRUE );
		if ( $uri ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Singleton
	 */

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return GB_Aggregator_Destination
	 */
	public static function get_instance() {
		if ( !is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	final public function __clone() {
		trigger_error( "No cloning allowed!", E_USER_ERROR );
	}

	final public function __sleep() {
		trigger_error( "No serialization allowed!", E_USER_ERROR );
	}

	protected function __construct() {
		$this->add_hooks();
	}

	protected function add_hooks() {
		if ( defined(GBS_AGG_DEV) && GBS_AGG_DEV ) {
			add_action( 'init', array( $this, 'check_for_updates' ), 1000, 0 );
		} else {
			add_action( self::CRON_HOOK, array( $this, 'check_for_updates' ), 10, 0 );
		}
		add_action( 'add_meta_boxes_'.Group_Buying_Deal::POST_TYPE, array( $this, 'register_syndication_options_meta_box' ), 10, 0 );
		add_action( 'save_post', array( $this, 'save_syndication_options_meta_box' ), 10, 2 );
		add_action( 'shutdown', array( $this, 'remove_duplicates' ), 10, 0 );
		add_filter( 'gb_get_add_to_cart_form', array( $this, 'filter_add_to_cart_button' ), 100, 1 );
		add_filter( 'gb_get_add_to_cart_url', array( $this, 'filter_add_to_cart_url' ), 100, 1 );
		add_filter( 'account_can_purchase', array( $this, 'prevent_purchase_of_external_deal' ), 100, 4 );
		add_action( 'admin_init', array( $this, 'register_settings_section' ), 11, 0 );
	}

	/**
	 * Register a settings section on the syndication settings page
	 *
	 * @return void
	 */
	public function register_settings_section() {
		add_settings_section( 'destination', self::__( 'Subscription Settings' ), array( $this, 'display_settings_section' ), self::$settings_page );

		register_setting( self::$settings_page, self::OPTION_SUBSCRIBED_CATEGORIES );
		register_setting( self::$settings_page, self::OPTION_SUBSCRIBED_LOCATIONS );
		register_setting( self::$settings_page, self::OPTION_CATEGORY_MAPPING );
		register_setting( self::$settings_page, self::OPTION_LOCATION_MAPPING );
		register_setting( self::$settings_page, self::OPTION_AGG_NOTES );
		add_settings_field( self::OPTION_AGG_NOTES, self::__( 'Aggregation Notes' ), array( $this, 'display_notes_setting' ), self::$settings_page, 'destination' );
		add_settings_field( self::OPTION_SUBSCRIBED_CATEGORIES, self::__( 'Categories' ), array( $this, 'display_category_subscriptions_setting' ), self::$settings_page, 'destination' );
		add_settings_field( self::OPTION_SUBSCRIBED_LOCATIONS, self::__( 'Locations' ), array( $this, 'display_location_subscriptions_setting' ), self::$settings_page, 'destination' );
	}

	public function display_settings_section() {
		require_once self::plugin_path( 'Walker_Category_Checklist_Subscription_Mapper.php' );
		?><style type="text/css">
		.aggregator-taxonomy-checkboxes {
			list-style-type: none;
		}
		.aggregator-taxonomy-checkboxes .children {
			margin-left: 1.5em;
		}
		</style>
		<p><?php self::_e( 'Deals with the selected categories (and their sub-categories) will be automatically imported from the aggregation server. If you no categories are selected, deals will be imported regardless of category.' ); ?></p>
	<?php
	}

	public function display_category_subscriptions_setting() {
		$taxa = self::get_taxa();
		$categories = $taxa->categories;
		$subscriptions = get_option( self::OPTION_SUBSCRIBED_CATEGORIES, array() );
		$mappings = get_option( self::OPTION_CATEGORY_MAPPING, array() );
		if ( $categories ) {
			$walker = new Walker_Category_Checklist_Subscription_Mapper();
			$args = array(
				'selected_cats' => $subscriptions,
				'subscription_option' => self::OPTION_SUBSCRIBED_CATEGORIES,
				'local_taxonomy' => Group_Buying_Deal::CAT_TAXONOMY,
				'mappings' => $mappings,
				'map_option' => self::OPTION_CATEGORY_MAPPING,
			);
			echo '<ul class="aggregator-taxonomy-checkboxes category-checkboxes">';
			echo $walker->walk( $categories, 0, $args );
			echo '</ul>';
		} else {
			foreach ( $subscriptions as $subscription ) {
				printf( '<input type="hidden" name="%s[]" value="%s" />', self::OPTION_SUBSCRIBED_CATEGORIES, $subscription );
			}
			foreach ( $mappings as $mapping ) {
				printf( '<input type="hidden" name="%s[]" value="%s" />', self::OPTION_LOCATION_MAPPING, $mapping );
			}
			self::_e( 'Failed to load categories from the server. Please try reloading the page.' );
		}
	}

	public function display_location_subscriptions_setting() {
		$taxa = self::get_taxa();
		$locations = $taxa->locations;
		$subscriptions = get_option( self::OPTION_SUBSCRIBED_LOCATIONS, array() );
		$mappings = get_option( self::OPTION_LOCATION_MAPPING, array() );
		if ( $locations ) {
			$walker = new Walker_Category_Checklist_Subscription_Mapper();
			$args = array(
				'selected_cats' => $subscriptions,
				'subscription_option' => self::OPTION_SUBSCRIBED_LOCATIONS,
				'local_taxonomy' => Group_Buying_Deal::LOCATION_TAXONOMY,
				'mappings' => $mappings,
				'map_option' => self::OPTION_LOCATION_MAPPING,
			);
			echo '<ul class="aggregator-taxonomy-checkboxes location-checkboxes">';
			echo $walker->walk( $locations, 0, $args );
			echo '</ul>';
		} else {
			foreach ( $subscriptions as $subscription ) {
				printf( '<input type="hidden" name="%s[]" value="%s" />', self::OPTION_SUBSCRIBED_CATEGORIES, $subscription );
			}
			foreach ( $mappings as $mapping ) {
				printf( '<input type="hidden" name="%s[]" value="%s" />', self::OPTION_LOCATION_MAPPING, $mapping );
			}
			self::_e( 'Failed to load locations from the server. Please try reloading the page.' );
		}
	}

	public function display_notes_setting( $link ) {
		echo '<textarea name="'.self::OPTION_AGG_NOTES.'" class="medium-text code" rows="10">'.get_option( self::OPTION_AGG_NOTES ).'</textarea><br/><small>';
		echo self::__( 'These notes are shown on the deals edit page and could be used to show affiliate IDs for each site your aggregating.' );
		echo '</small>';
	}


	public function register_syndication_options_meta_box() {
		global $post;
		if ( !self::is_syndicated( $post->ID ) ) {
			return; // don't provide the meta box if it it's not a syndicated site
		}
		add_meta_box( 'gb_aggregator_options', self::__( 'Aggregation Options' ), array( $this, 'display_syndication_options_meta_box' ), Group_Buying_Deal::POST_TYPE, 'side' );
	}

	public function display_syndication_options_meta_box( $post ) {
		if ( self::is_syndicated( $post->ID ) ) {
			$link = get_post_meta( $post->ID, self::DEAL_LINK_META_KEY, TRUE );
			$this->display_affiliate_url_setting( $link );
		}
	}

	private function display_affiliate_url_setting( $link ) {
		echo '<label for="'.self::DEAL_LINK_META_KEY.'">'.gb__( 'Purchase URL:' ).'</label><br/>';
		echo '<input type="text" class="large-text" name="'.self::DEAL_LINK_META_KEY.'" value="'.$link.'" />';
		echo nl2br( stripslashes( get_option( self::OPTION_AGG_NOTES ) ) );
	}



	public function save_syndication_options_meta_box( $post_id, $post ) {
		if ( $post->post_type == Group_Buying_Deal::POST_TYPE && isset( $_POST[self::DEAL_LINK_META_KEY] ) ) {
			update_post_meta( $post_id, self::DEAL_LINK_META_KEY, $_POST[self::DEAL_LINK_META_KEY] );
		}
	}

	/**
	 * Users shouldn't be able to add a syndicated deal to their carts
	 *
	 * @param int     $qty
	 * @param Group_Buying_Account $account
	 * @param int     $deal_id
	 * @return int|bool
	 */
	public function prevent_purchase_of_external_deal( $qty, $deal_id, $data, $account ) {
		if ( self::is_syndicated( $deal_id ) ) {
			return FALSE;
		}
		return $qty;
	}

	/**
	 * The add to cart button should be replaced with a link to the original item
	 *
	 * @param string  $button
	 * @return string
	 */
	public function filter_add_to_cart_button( $button ) {
		$id = get_the_ID();
		$link = get_post_meta( $id, self::DEAL_LINK_META_KEY, TRUE );
		if ( !$link ) {
			return $button;
		}
		$a = sprintf( '<a href="%s" class="gb-aggregator-link">%s</a>', esc_url( $link ), self::__( 'Purchase' ) );
		return apply_filters( 'gb_aggregator_external_link', $a, esc_url( $link ), $button, $id );
	}

	/**
	 * The add to cart button should be replaced with a link to the original item
	 *
	 * @param string  $button
	 * @return string
	 */
	public function filter_add_to_cart_url( $url ) {
		$id = get_the_ID();
		$link = get_post_meta( $id, self::DEAL_LINK_META_KEY, TRUE );
		if ( !$link ) {
			return $url;
		}
		return apply_filters( 'gb_aggregator_external_url', $link, $url, $id );
	}

	/**
	 * Poll the server to see if updates are available. If they are, grab them.
	 *
	 * @return void
	 */
	public function check_for_updates() {
		$client = new GB_Aggregator_Client();
		$args = array(
			'location' => get_option( self::OPTION_SUBSCRIBED_LOCATIONS, array() ),
			'category' => get_option( self::OPTION_SUBSCRIBED_CATEGORIES, array() ),
		);
		/*
		 * TODO: do we need to track the last modified date to reduce the number
		 * of results returned? Probably should, to boost performance.
		 */
		$feed = $client->get_feed( $args );
		foreach ( $feed as $deal ) {
			if ( !isset( $deal['uri'] ) || !$deal['uri'] ) {
				continue;
			}
			$post = $this->get_post_by_uri( $deal['uri'] );
			if ( !$post ) {
				// if it's not published, ignore it
				if ( $deal['status'] == 'publish' ) {
					// we don't have a local post, yet. Make one
					$this->create_post( $deal['uri'] );
				}
			} elseif ( $deal['status'] == 'trash' ) {
				// the deal has been removed; remove the local copy
				wp_delete_post( $post->ID, TRUE );
			} else {
				// check if the local post needs to be updated
				$modified = get_post_modified_time( 'U', TRUE, $post->ID );
				if ( $modified &&  $modified >= $deal['modified'] ) {
					continue; // nothing has changed, don't update
				}
				$this->update_post( $post->ID, $deal['uri'] );
			}
		}
	}

	/**
	 * If we've somehow managed to duplicate a post (most likely through duplicate
	 * crons running simultaneously), remove all but one instance of it
	 *
	 * @return void
	 */
	public function remove_duplicates() {
		global $wpdb;
		$uris = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key=%s GROUP BY meta_value HAVING COUNT(meta_value) > 1", self::DEAL_URI_META_KEY ) );
		foreach ( $uris as $uri ) {
			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s ORDER BY post_id ASC", self::DEAL_URI_META_KEY, $uri ) );
			unset( $post_ids[0] ); // keep the first
			foreach ( $post_ids as $id ) {
				wp_delete_post( $id, TRUE ); // no need to keep it around
			}
		}
	}

	/**
	 * Get the local post created from $uri
	 *
	 * @param string  $uri
	 * @return object|null
	 */
	private function get_post_by_uri( $uri ) {
		$posts = get_posts( array(
				'post_type' => Group_Buying_Deal::POST_TYPE,
				'post_status' => get_post_stati(), // can't use 'any', because that excludes 'trash'
				'meta_query' => array(
					array(
						'key' => self::DEAL_URI_META_KEY,
						'value' => $uri,
					),
				),
			) );
		if ( !$posts ) {
			return NULL;
		}
		return $posts[0];
	}

	private function server_user_id() {
		if ( $user_id = get_option( 'gb_aggregator_server_user_id', 0 ) ) {
			return $user_id;
		}

		$user_id = wp_insert_user( array(
				'user_pass' => wp_generate_password( 16, TRUE, TRUE ),
				'user_login' => 'gbs_aggregation_server',
				'user_nicename' => self::__( 'GBS Aggregation Server' ),
				'user_email' => 'syndication@groupbuyingsite.com',
				'user_url' => 'http://groupbuyingsite.com/',
				'nickname' => self::__( 'GBS Aggregation Server' ),
				'role' => 'subscriber',
			) );

		if ( !$user_id || is_wp_error( $user_id ) ) {
			return 0; // oh, well, we tried
		}
		update_option( 'gb_aggregator_server_user_id', $user_id );
		return $user_id;
	}

	/**
	 * Create a new deal locally based on the date from $uri
	 *
	 * @param string  $uri
	 * @return bool
	 */
	private function create_post( $uri ) {
		$client = new GB_Aggregator_Client();
		$deal = $client->get_deal( $uri );
		if ( self::DEBUG ) {
			error_log( 'Creating post for syndicated deal' );
			error_log( print_r( $deal, TRUE ) );
		}
		if ( !$deal ) {
			return FALSE;
		}

		$post = array(
			'post_type' => Group_Buying_Deal::POST_TYPE,
			'post_status' => 'pending',
			'post_author' => $this->server_user_id(),
			'post_title' => $deal['title'],
			'post_content' => $deal['description'],
			'post_date' => date( 'Y-m-d H:i:s', $deal['created'] + ( get_option( 'gmt_offset' ) * 3600 ) ),
			'post_date_gmt' => date( 'Y-m-d H:i:s', $deal['created'] ),
			'post_modified' => date( 'Y-m-d H:i:s', $deal['modified'] + ( get_option( 'gmt_offset' ) * 3600 ) ),
			'post_modified_gmt' => date( 'Y-m-d H:i:s', $deal['modified'] ),
			'guid' => $uri
		);
		$post_id = wp_insert_post( $post );
		if ( !$post_id || is_wp_error( $post_id ) ) {
			return FALSE;
		}
		update_post_meta( $post_id, self::DEAL_URI_META_KEY, $uri );
		$local_deal = Group_Buying_Deal::get_instance( $post_id );
		$this->update_local_deal_properties( $local_deal, $deal );

		return TRUE;
	}

	/**
	 * Given the URL for an image, download it and create
	 * derivative images, then assign as a featured image to $post_id
	 *
	 * @param int     $post_id
	 * @param string  $url
	 * @return bool Whether a thumbnail was successfully created and assigned
	 */
	private function create_thumbnail( $post_id, $url ) {
		if ( ! ( ( $uploads = wp_upload_dir( current_time( 'mysql' ) ) ) && false === $uploads['error'] ) ) {
			return FALSE; // upload dir is not accessible
		}
		$name_parts = pathinfo( $url );
		$filename = wp_unique_filename( $uploads['path'], $name_parts['basename'] );
		$unique_name_parts = pathinfo( $filename );
		$newfile = $uploads['path'] . "/$filename";

		// try to upload
		$response = wp_remote_get( $url );
		if ( !$response || is_wp_error( $response ) ) {
			return FALSE; // couldn't complete the request
		}
		$content = $response['body'];

		if ( empty( $content ) ) { // nothing was found
			return FALSE;
		}

		file_put_contents( $newfile, $content ); // save image

		if ( ! file_exists( $newfile ) ) { // upload was not successful
			return FALSE;
		}
		// Set correct file permissions
		$stat = stat( dirname( $newfile ) );
		$perms = $stat['mode'] & 0000666;
		@chmod( $newfile, $perms );
		// get file type
		$wp_filetype = wp_check_filetype( $newfile );
		extract( $wp_filetype );

		// No file type! No point to proceed further
		if ( ( !$type || !$ext ) && !current_user_can( 'unfiltered_upload' ) ) {
			return FALSE;
		}
		$image_title = $unique_name_parts['filename'];
		$image_description = '';

		/**
		 * Load WordPress Image Administration API
		 */
		require_once ABSPATH . 'wp-admin/includes/image.php';
		// use image exif/iptc data for title and caption defaults if possible
		if ( $image_meta = @wp_read_image_metadata( $newfile ) ) {
			if ( trim( $image_meta['title'] ) ) {
				$image_title = $image_meta['title'];
			}
			if ( trim( $image_meta['caption'] ) ) {
				$image_description = $image_meta['caption'];
			}
		}

		// Compute the URL
		$url = $uploads['url'] . "/$filename";

		// Construct the attachment array
		$attachment = array(
			'post_mime_type' => $type,
			'guid' => $url,
			'post_parent' => $post->ID,
			'post_title' => $image_title,
			'post_content' => $image_description,
			'post_excerpt' => '',
		);
		$thumb_id = wp_insert_attachment( $attachment, $newfile, $post->ID );
		if ( !is_wp_error( $thumb_id ) ) {
			wp_update_attachment_metadata( $thumb_id, wp_generate_attachment_metadata( $thumb_id, $newfile ) );
			update_post_meta( $post_id, '_thumbnail_id', $thumb_id );
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Update the local deal $post_id with data from $url
	 *
	 * @param int     $post_id
	 * @param string  $uri
	 * @return bool
	 */
	private function update_post( $post_id, $uri ) {
		$client = new GB_Aggregator_Client();
		$deal = $client->get_deal( $uri );
		if ( self::DEBUG ) {
			error_log( 'Updating post for syndicated deal' );
			error_log( 'Post ID: '.$post_id );
			error_log( print_r( $deal, TRUE ) );
		}
		if ( !$deal ) {
			return FALSE; // failed to load a deal
		}

		// The syndication server should have filtered this out, but check for good measure
		if ( $deal['source_url'] == home_url() ) {
			return FALSE;
		}

		$post = get_post( $post_id );
		$local_deal = Group_Buying_Deal::get_instance( $post_id );

		// reset the post status to pending if it's already published and something significant has changed
		if ( in_array( $post->post_status, array( 'publish', 'future' ) ) ) {
			if ( $post->post_title != $deal['title']
				|| $post->post_content != $deal['description']
				|| $local_deal->get_highlights() != $deal['highlights']
				|| $local_deal->get_fine_print() != $deal['fine_print']
				|| $local_deal->get_rss_excerpt() != $deal['rss_excerpt']
			) {
				$post->post_status = 'pending';
			}
		}

		$post->post_content = $deal['description'];
		$post->post_title = $deal['title'];
		$post->post_modified = date( 'Y-m-d H:i:s', $deal['modified'] + ( get_option( 'gmt_offset' ) * 3600 ) );
		$post->post_modified_gmt = date( 'Y-m-d H:i:s', $deal['modified'] );

		$post_id = wp_update_post( $post );
		if ( !$post_id || is_wp_error( $post_id ) ) {
			return FALSE; // there was an error updating the post. Do nothing more.
		}
		$this->update_local_deal_properties( $local_deal, $deal );
		return TRUE;
	}

	/**
	 *
	 *
	 * @param Group_Buying_Deal $local_deal
	 * @param array   $remote_deal
	 * @return void
	 */
	private function update_local_deal_properties( $local_deal, $remote_deal ) {
		$post_id = $local_deal->get_id();

		$local_deal->set_expiration_date( $remote_deal['expires'] );
		$local_deal->set_prices( array( $remote_deal['price'] ) );
		$local_deal->set_value( $remote_deal['value'] );
		$local_deal->set_amount_saved( $remote_deal['savings'] );
		$local_deal->set_highlights( $remote_deal['highlights'] );
		$local_deal->set_fine_print( $remote_deal['fine_print'] );
		$local_deal->set_rss_excerpt( $remote_deal['rss_excerpt'] );

		update_post_meta( $post_id, self::DEAL_LINK_META_KEY, $remote_deal['uri'] );
		update_post_meta( $post_id, self::DEAL_SOURCE_META_KEY, $remote_deal['source'] );
		update_post_meta( $post_id, self::DEAL_SOURCE_URL_META_KEY, $remote_deal['source_url'] );
		update_post_meta( $post_id, self::MAX_PRICE_META_KEY, $remote_deal['max_price'] );
		update_post_meta( $post_id, self::MIN_PRICE_META_KEY, $remote_deal['min_price'] );

		// if the thumbnail URL has changed, update_post_meta will return TRUE
		// in that case, we need to re-thumbnail the post
		if ( update_post_meta( $post_id, self::ORIGINAL_THUMBNAIL_META_KEY, $remote_deal['thumbnail'] ) ) {
			if ( $remote_deal['thumbnail'] && current_theme_supports( 'post-thumbnails', array( Group_Buying_Deal::POST_TYPE ) ) ) {
				$this->create_thumbnail( $post_id, esc_url_raw( $remote_deal['thumbnail'] ) );
			} else {
				delete_post_meta( $post_id, '_thumbnail_id' );
			}
		}

		foreach ( $remote_deal['tags'] as $slug => $label ) {
			if ( !term_exists( $label, Group_Buying_Deal::TAG_TAXONOMY ) ) {
				wp_insert_term( $label, Group_Buying_Deal::TAG_TAXONOMY );
			}
		}
		// append, don't overwrite, because we don't know if the local user added/edited terms
		wp_set_object_terms( $post_id, $remote_deal['tags'], Group_Buying_Deal::TAG_TAXONOMY, TRUE );

		if ( $remote_deal['location'] ) {
			$local_term_id = $this->get_mapped_term( $remote_deal['location'], 'location' );
			if ( $local_term_id > 0 ) {
				wp_set_object_terms( $post_id, array( $local_term_id ), Group_Buying_Deal::LOCATION_TAXONOMY, FALSE );
			}
		}
		if ( $remote_deal['category'] ) {
			$local_term_id = $this->get_mapped_term( $remote_deal['category'], 'category' );
			if ( $local_term_id > 0 ) {
				wp_set_object_terms( $post_id, array( $local_term_id ), Group_Buying_Deal::CAT_TAXONOMY, FALSE );
			}
		}
	}

	/**
	 * Given the ID of a term from the server, get the ID
	 * of the local term it should be mapped to
	 *
	 * @param int     $remote_term_id
	 * @param string  $taxonomy
	 * @return int
	 */
	private function get_mapped_term( $remote_term_id, $taxonomy ) {
		$taxa = self::get_taxa();
		switch ( $taxonomy ) {
		case 'location':
			$terms = $taxa->locations;
			$mappings = get_option( self::OPTION_LOCATION_MAPPING, array() );
			break;
		case 'category':
		default:
			$terms = $taxa->categories;
			$mappings = get_option( self::OPTION_CATEGORY_MAPPING, array() );
			break;
		}
		$terms = $this->key_by_term_id( $terms );
		$parent = $remote_term_id;
		do {
			$term = isset( $terms[$parent] )?$terms[$parent]:(object)array( 'term_id' => 0, 'parent' => 0 );
			$local_term_id = isset( $mappings[$parent] )?$mappings[$parent]:-1;
			$parent = $term->parent;
		} while ( $local_term_id <= 0 && $parent > 0 );
		return (int)$local_term_id;
	}

	/**
	 * Given an array of terms with incremented numeric keys,
	 * return an array of the same terms with their term
	 * IDs as the array keys
	 *
	 * @param array   $terms
	 * @return array
	 */
	private function key_by_term_id( $terms ) {
		$keyed = array();
		foreach ( $terms as $term ) {
			$keyed[$term->term_id] = $term;
		}
		return $keyed;
	}



	public static function register_columns( $columns ) {
		$columns['source'] = self::__( 'Source' );
		return $columns;
	}


	public static function column_display( $column_name, $id ) {
		global $post;

		$source = get_post_meta( $id, self::DEAL_SOURCE_META_KEY, TRUE );
		$source_url = get_post_meta( $id, self::DEAL_SOURCE_URL_META_KEY, TRUE );

		if ( $source == '' )
			return; // return for that temp post

		$client = new GB_Aggregator_Client();
		$info = $client->get_affiliate_info( $source_url );

		$affiliate_platform = ( isset( $info['affiliate_platform'] ) ) ? $info['affiliate_platform'] : 'Unknown' ;	

		switch ( $column_name ) {
		case 'source':
			echo '<a href="'.$source_url.'">'.$source.'</a>';
			echo '<br/>';
			printf( self::__( 'Affiliate Service: %s' ), $affiliate_platform);
			echo '<br/>';
			if ( isset( $info['affiliate_singup_url'] ) ) {
				echo '<a href="'.$info['affiliate_singup_url'].'">'.self::__('Signup/Apply').'</a>';
			} else {
				echo $affiliate_platform;
			}
			break;
		case 'records' :
			echo '';
		default:
			break;
		}
	}

	public function sortable_columns( $columns ) {
		$columns['source'] = 'source';
		return $columns;
	}
	public function column_orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && is_admin() ) {
			switch ( $vars['orderby'] ) {
			case 'source':
				$vars = array_merge( $vars, array(
						'orderby' => 'SQL' // TODO SQL
					) );
				break;
			default:
				// code...
				break;
			}
		}

		return $vars;
	}


}
