<?php
/**
 * GBS Syndication Service Source.
 *
 * @package GBS
 * @subpackage Syndication
 */
class GB_Aggregator_Source extends GB_Aggregator_Plugin {
	const META_KEY_SYNDICATE = '_gb_aggregator_syndicate'; // the checkbox for whether to syndicate the deal
	const META_KEY_PACKAGE = '_gb_aggregator_syndication_package'; // the json package to submit to the server
	const META_KEY_URI = '_gb_aggregator_syndication_uri'; // the URI for the deal created at the server
	const META_KEY_LOCATION = '_gb_aggregator_location';
	const META_KEY_CATEGORY = '_gb_aggregator_category';
	const OPTION_SYNDICATE_POSTS = 'gb_aggregator_syndicate_by_default';
	const OPTION_DEFAULT_LOCATION = 'gb_aggregator_default_location';
	const OPTION_DEFAULT_CATEGORY = 'gb_aggregator_default_category';
	const OPTION_AFFILIATE_APP = 'gb_aggregator_syndicate_affiliate_platform';
	const OPTION_AFFILIATE_APP_QUERY_STRING = 'gb_aggregator_syndicate_affiliate_platform_qs';
	const OPTION_AFFILIATE_SIGNUP = 'gb_aggregator_syndicate_affiliate_signup';


	private static $instance;
	/**
	 * Create the instance of the class
	 *
	 * @static
	 * @return void
	 */
	public static function init() {
		self::$instance = self::get_instance();
	}

	/**
	 * Singleton
	 */

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return GB_Aggregator_Source
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
		add_action( 'post_submitbox_misc_actions', array( $this, 'display_syndicate_field' ) );
		add_action( 'add_meta_boxes_'.Group_Buying_Deal::POST_TYPE, array( $this, 'register_syndication_options_meta_box' ), 10, 0 );
		add_action( 'save_post', array( $this, 'save_syndicate_field' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_syndication_options_meta_box' ), 10, 2 );
		add_action( 'save_post', array( $this, 'maybe_syndicate_deal' ), 1000, 2 ); // call late, so anything else can be saved, first

		add_action( 'admin_init', array( $this, 'register_settings_section' ), 12, 0 );
		add_action( 'before_delete_post', array( $this, 'maybe_unsyndicate_deal' ), 10, 1 );

		add_action( 'gb_router_generate_routes', array( $this, 'setup_routes' ), 10, 1 );
	}

	/**
	 * Map paths to callbacks
	 *
	 * @param GB_Router|WP_Router $router
	 * @return void
	 */
	public function setup_routes( $router ) {
		$routes = array(
			'gb_aggregation_affiliate_info' => array(
				'path' => 'affiliate_info',
				'title' => 'Affiliate Information',
				'page_callback' => array( get_class(), 'affiliate_info' ),
				'template' => FALSE,
			), /* */
		);
		foreach ( $routes as $id => $route ) {
			$router->add_route( $id, $route );
		}
	}

	public function affiliate_info() {
		header( 'Content-Type: application/json; charset=utf8' );
		$response = array(
			'affiliate_platform' => get_option( self::OPTION_AFFILIATE_APP ),
			'affiliate_query_string' => get_option( self::OPTION_AFFILIATE_APP_QUERY_STRING ),
			'affiliate_singup_url' => get_option( self::OPTION_AFFILIATE_SIGNUP )
		);
		echo json_encode( $response );
		exit();
	}

	/**
	 * Register a settings section on the syndication settings page
	 *
	 * @return void
	 */
	public function register_settings_section() {
		add_settings_section( 'source', self::__( 'Default Options for Deals You Create' ), array( $this, 'display_settings_section' ), self::$settings_page );

		register_setting( self::$settings_page, self::OPTION_SYNDICATE_POSTS );
		register_setting( self::$settings_page, self::OPTION_DEFAULT_CATEGORY );
		register_setting( self::$settings_page, self::OPTION_DEFAULT_LOCATION );
		register_setting( self::$settings_page, self::OPTION_AFFILIATE_APP );
		register_setting( self::$settings_page, self::OPTION_AFFILIATE_APP_QUERY_STRING );
		register_setting( self::$settings_page, self::OPTION_AFFILIATE_SIGNUP );
		add_settings_field( self::OPTION_SYNDICATE_POSTS, self::__( 'Syndicate' ), array( $this, 'display_syndicate_setting' ), self::$settings_page, 'source' );
		add_settings_field( self::OPTION_DEFAULT_CATEGORY, self::__( 'Default Category' ), array( $this, 'display_category_setting' ), self::$settings_page, 'source' );
		add_settings_field( self::OPTION_DEFAULT_LOCATION, self::__( 'Default Location' ), array( $this, 'display_location_setting' ), self::$settings_page, 'source' );
		add_settings_field( self::OPTION_AFFILIATE_APP, self::__( 'Affiliate Platform' ), array( $this, 'display_affiliate_setting' ), self::$settings_page, 'source' );
		add_settings_field( self::OPTION_AFFILIATE_SIGNUP, self::__( 'Affiliate Sign-up URL' ), array( $this, 'display_affiliate_signup_setting' ), self::$settings_page, 'source' );
		add_settings_field( self::OPTION_AFFILIATE_APP_QUERY_STRING, self::__( 'Affiliate Query String' ), array( $this, 'display_affiliate_qs_setting' ), self::$settings_page, 'source' );


	}

	public function display_syndicate_setting() {
		$current = get_option( self::OPTION_SYNDICATE_POSTS, 'yes' );
		if ( $current != 'no' ) {
			$current = 'yes';
		}
		printf( '<label><input type="radio" value="yes" name="%s" %s /> %s</label> ', self::OPTION_SYNDICATE_POSTS, checked( TRUE, $current=='yes', FALSE ), self::__( 'Yes' ) );
		printf( '<label><input type="radio" value="no" name="%s" %s /> %s</label> ', self::OPTION_SYNDICATE_POSTS, checked( TRUE, $current=='no', FALSE ), self::__( 'No' ) );
	}

	public function display_category_setting() {
		$taxa = self::get_taxa();
		$categories = $taxa->categories;
		if ( $categories ) {
			$this->display_category_select( $categories, array( 'name' => self::OPTION_DEFAULT_CATEGORY, 'label' => FALSE ) );
		} else {
			self::_e( 'Failed to load categories from the server. Please try reloading the page.' );
		}
	}

	public function display_location_setting() {
		$taxa = self::get_taxa();
		$locations = $taxa->locations;
		if ( $locations ) {
			$this->display_location_select( $locations, array( 'name' => self::OPTION_DEFAULT_LOCATION, 'label' => FALSE ) );
		} else {
			self::_e( 'Failed to load locations from the server. Please try reloading the page.' );
		}
	}

	public function display_affiliate_setting() {
		$option = ( defined( 'WP_AFFILIATE_PLATFORM_VERSION' ) ) ? get_option( self::OPTION_AFFILIATE_APP, 'WordPress Affiliate Platform' ) : get_option( self::OPTION_AFFILIATE_APP ) ;
		echo '<input type="text" name="'.self::OPTION_AFFILIATE_APP.'" value="'.$option.'" />';
		echo '<br/>';
		printf( self::__( 'Let potential affiliates know what affiliate platform you use (e.g. <a href="%s" target="_blank">WordPress Affiliate Platform</a>).' ), 'http://groupbuyingsite.com/goto/WPAffiliatePlatform' );
	}

	public function display_affiliate_signup_setting() {
		echo '<input type="text" name="'.self::OPTION_AFFILIATE_SIGNUP.'" value="'.get_option( self::OPTION_AFFILIATE_SIGNUP ).'" />';
		echo '<br/>'.gb__( 'This is the URL prospective affiliates will be redirected to sign-up for your affiliate program.' );
	}

	public function display_affiliate_qs_setting() {
		$option = ( defined( 'WP_AFFILIATE_PLATFORM_VERSION' ) ) ? get_option( self::OPTION_AFFILIATE_APP_QUERY_STRING, 'app_id' ) : get_option( self::OPTION_AFFILIATE_APP_QUERY_STRING ) ;
		echo '<input type="text" name="'.self::OPTION_AFFILIATE_APP_QUERY_STRING.'" value="'.$option.'" />';
		echo '<br/>'.gb__( 'This query string will be added to your deal URLs in order to help your affiliates easily enter their affiliate tracking code (e.g. WP Affiliate Platform uses ap_id)' );
	}

	/**
	 * Prints the "syndicate" field in the publishing meta box
	 * Called by 'post_submitbox_misc_actions' filter
	 *
	 * @return void
	 */
	public function display_syndicate_field() {
		global $post;
		if ( class_exists( 'GB_Aggregator_Destination' ) && GB_Aggregator_Destination::is_syndicated( $post->ID ) ) {
			return; // don't provide the option if it came from elsewhere
		}
		if ( Group_Buying_Deal::POST_TYPE == $post->post_type ) {
			$box = '<div class="misc-pub-section misc-pub-section-last" style="border-top: none;">';
			$box .= '<input type="hidden" value="1" name="gb_aggregator_syndicate_checkbox_flag" />';
			$checked =  get_post_meta( $post->ID, self::META_KEY_SYNDICATE, TRUE );
			if ( !$checked ) {
				$checked = get_option( self::OPTION_SYNDICATE_POSTS, 'yes' );
			}
			$box .= '<label for="'.self::META_KEY_SYNDICATE.'"><input type="checkbox" name="'.self::META_KEY_SYNDICATE.'" value="yes" id="'.self::META_KEY_SYNDICATE.'"'.checked( 'yes', $checked, FALSE ).' /> '.self::__( 'Syndicate This Deal' ).'</label>';
			$box .= '</div>';
			echo $box;
		}
	}


	/**
	 * Saves the checkbox for syndicating the deal
	 *
	 * @param int     $post_id
	 * @param object  $post
	 * @return
	 */
	public function save_syndicate_field( $post_id, $post ) {
		// only continue if it's a deal post
		if ( $post->post_type != Group_Buying_Deal::POST_TYPE ) {
			return;
		}
		// don't do anything on autosave, auto-draft, bulk edit, or quick edit
		if ( wp_is_post_autosave( $post_id ) || $post->post_status == 'auto-draft' || defined( 'DOING_AJAX' ) || isset( $_GET['bulk_edit'] ) ) {
			return;
		}
		// we don't need a full-blown nonce, just a flag to indicate that this was actually a post being saved
		if ( !isset( $_POST['gb_aggregator_syndicate_checkbox_flag'] ) ) {
			return;
		}

		// if this is syndicated from another site, we shouldn't even be here
		if ( class_exists( 'GB_Aggregator_Destination' ) && GB_Aggregator_Destination::is_syndicated( $post_id ) ) {
			delete_post_meta( $post_id, self::META_KEY_SYNDICATE );
			return;
		}

		$syndicate = ( isset( $_POST[self::META_KEY_SYNDICATE] ) && $_POST[self::META_KEY_SYNDICATE] == 'yes' )?'yes':'no';
		update_post_meta( $post_id, self::META_KEY_SYNDICATE, $syndicate );
	}

	public function register_syndication_options_meta_box() {
		global $post;
		if ( class_exists( 'GB_Aggregator_Destination' ) && GB_Aggregator_Destination::is_syndicated( $post->ID ) ) {
			return; // don't provide the meta box if it came from elsewhere
		}
		add_meta_box( 'gb_aggregator_syndication_options', self::__( 'Syndication Options' ), array( $this, 'display_syndication_options_meta_box' ), Group_Buying_Deal::POST_TYPE, 'side' );
	}

	public function display_syndication_options_meta_box( $post ) {
		$taxa = self::get_taxa();
		$locations = $taxa->locations;
		if ( $locations ) {
			$selected = get_post_meta( $post->ID, self::META_KEY_LOCATION, TRUE );
			$args = array();
			if ( is_numeric( $selected ) ) {
				$args['selected'] = $selected;
			}
			$this->display_location_select( $locations, $args );
		}
		$categories = $taxa->categories;
		if ( $categories ) {
			$selected = get_post_meta( $post->ID, self::META_KEY_CATEGORY, TRUE );
			$args = array();
			if ( is_numeric( $selected ) ) {
				$args['selected'] = $selected;
			}
			$this->display_category_select( $categories, $args );
		}

	}

	private function display_location_select( $locations, $args = array() ) {
		$defaults = array(
			'selected' => get_option( self::OPTION_DEFAULT_LOCATION, 0 ),
			'label' => self::__( 'Location' ),
			'name' => self::META_KEY_LOCATION,
		);
		$args = wp_parse_args( $args, $defaults );
		echo '<p>';
		if ( $args['label'] ) {
			echo '<label>'.$args['label'];
		}
		echo ' <select name="'.$args['name'].'">';
		echo '<option class="level-0" value="0">'.self::__( 'Anywhere' ).'</option>';
		$walker = new Walker_CategoryDropdown();
		echo $walker->walk( $locations, 0, array( 'selected' => $args['selected'] ) );
		echo '</select>';
		if ( $args['label'] ) {
			echo '</label>';
		}
		echo '</p>';
	}

	private function display_category_select( $categories, $args = array() ) {
		$defaults = array(
			'selected' => get_option( self::OPTION_DEFAULT_CATEGORY, 0 ),
			'label' => self::__( 'Category' ),
			'name' => self::META_KEY_CATEGORY,
		);
		$args = wp_parse_args( $args, $defaults );
		echo '<p>';
		if ( $args['label'] ) {
			echo '<label>'.$args['label'];
		}
		echo ' <select name="'.$args['name'].'">';
		echo '<option class="level-0" value="0">'.self::__( 'Uncategorized' ).'</option>';
		$walker = new Walker_CategoryDropdown();
		echo $walker->walk( $categories, 0, array( 'selected' => $args['selected'] ) );
		echo '</select>';
		if ( $args['label'] ) {
			echo '</label>';
		}
		echo '</p>';
	}

	public function save_syndication_options_meta_box( $post_id, $post ) {
		if ( $post->post_type == Group_Buying_Deal::POST_TYPE && isset( $_POST[self::META_KEY_LOCATION] ) && isset( $_POST[self::META_KEY_CATEGORY] ) ) {
			update_post_meta( $post_id, self::META_KEY_LOCATION, $_POST[self::META_KEY_LOCATION] );
			update_post_meta( $post_id, self::META_KEY_CATEGORY, $_POST[self::META_KEY_CATEGORY] );
		}
	}

	/**
	 * Determines if we need to syndicate the deal, update a previously
	 * syndicated deal, or do nothing; and does so
	 *
	 * @param int     $post_id
	 * @param object  $post
	 * @return void
	 */
	public function maybe_syndicate_deal( $post_id, $post ) {
		// only continue if it's a deal post
		if ( $post->post_type != Group_Buying_Deal::POST_TYPE ) {
			return;
		}
		// don't do anything on autosave, auto-draft, bulk edit, or quick edit
		if ( wp_is_post_autosave( $post_id ) || $post->post_status == 'auto-draft' || defined( 'DOING_AJAX' ) || isset( $_GET['bulk_edit'] ) ) {
			return;
		}

		// we actually do want to trigger for scheduled posts, etc.
		$approved_for_syndication = ( get_post_meta( $post_id, self::META_KEY_SYNDICATE, TRUE )=='yes' )?TRUE:FALSE;
		$syndicated_post_uri = get_post_meta( $post_id, self::META_KEY_URI, TRUE );

		if ( $syndicated_post_uri && !( $approved_for_syndication && $post->post_status == 'publish' ) ) {
			// it was once syndicated, but now it should not be
			$this->delete_syndicated_post( $syndicated_post_uri );
			delete_post_meta( $post_id, self::META_KEY_URI );
			delete_post_meta( $post_id, self::META_KEY_PACKAGE );

		} elseif ( $approved_for_syndication && $post->post_status == 'publish' ) {
			// this should be syndicated
			$old_syndication_package = (array)get_post_meta( $post_id, self::META_KEY_PACKAGE, TRUE );
			$new_syndication_package = $this->create_syndication_package( $post );
			if ( !$syndicated_post_uri ) {
				// a new post to send to the server
				$uri = $this->create_syndicated_post( $new_syndication_package );
			} elseif ( $old_syndication_package != $new_syndication_package ) {
				// it's been updated, let the server know
				$uri = $this->update_syndicated_post( $syndicated_post_uri, $new_syndication_package );
			} else {
				// nothing has changed, so nothing to do here
				return;
			}

			// keep track of the URI
			if ( $uri ) {
				update_post_meta( $post_id, self::META_KEY_URI, $uri );
			}
			// cache the package for later
			update_post_meta( $post_id, self::META_KEY_PACKAGE, $new_syndication_package );
		}
	}

	/**
	 * Before deleting a post locally, delete it from the syndication server
	 * if it has been syndicated
	 *
	 * @param int     $post_id
	 * @return void
	 */
	public function maybe_unsyndicate_deal( $post_id ) {
		$syndicated_post_uri = get_post_meta( $post_id, self::META_KEY_URI, TRUE );
		if ( $syndicated_post_uri ) {
			$this->delete_syndicated_post( $syndicated_post_uri );
		}
	}

	/**
	 * Create an array of info to syndicate
	 *
	 * @param object  $post
	 * @return array
	 */
	private function create_syndication_package( $post ) {
		$url = ( get_option( self::OPTION_AFFILIATE_APP_QUERY_STRING ) != '' ) ? add_query_arg( array( get_option( self::OPTION_AFFILIATE_APP_QUERY_STRING )=>'YOUR_AFFILIATE_ID' ), get_permalink( $post->ID ) ) : get_permalink( $post->ID );
		$package = array(
			'source' => get_bloginfo( 'name' ),
			'source_url' => home_url(),
			'ID' => $post->ID,
			'uri' => $url,
			'title' => get_the_title( $post->ID ),
			'description' => $post->post_content,
			'created' => get_post_time( 'U', TRUE, $post->ID ),
			'modified' => get_post_modified_time( 'U', TRUE, $post->ID ),
			'expires' => gb_get_expiration_date( $post->ID ),
			'price' => gb_get_price( $post->ID ),
			'max_price' => gb_get_price( $post->ID ), // might be updated in a moment
			'min_price' => gb_get_price( $post->ID ), // might be updated in a moment
			'value' => gb_get_deal_worth( $post->ID ),
			'savings' => gb_get_deal_savings( $post->ID ),
			'highlights' => gb_get_highlights( $post->ID ),
			'fine_print' => gb_get_fine_print( $post->ID ),
			'rss_excerpt' => gb_get_rss_excerpt( $post->ID ),
			'thumbnail' => $this->get_thumbnail( $post->ID ),
			'location' => (int)get_post_meta( $post->ID, self::META_KEY_LOCATION, TRUE ),
			'category' => (int)get_post_meta( $post->ID, self::META_KEY_CATEGORY, TRUE ),
			'tags' => $this->get_tags( $post->ID ),
		);
		if ( class_exists( 'Group_Buying_Attributes' ) ) {
			// attributes may have difference prices
			$attributes = Group_Buying_Attribute::get_attributes( $post->ID, 'object' );
			foreach ( $attributes as $attribute ) {
				$price = (float)$attribute->get_price();
				if ( $price == Group_Buying_Attribute::DEFAULT_PRICE ) {
					continue;
				} elseif ( $price < (float)$package['min_price'] ) {
					$package['min_price'] = $price;
				} elseif ( (float)$package['max_price'] < $price ) {
					$package['max_price'] = $price;
				}
			}
		}
		$package = apply_filters( 'gb_aggregator_syndication_package', $package, $post );
		return $package;
	}

	/**
	 * Get the URL to the thumbnail for the given post
	 *
	 * @param int     $post_id
	 * @return string
	 */
	private function get_thumbnail( $post_id ) {
		if ( function_exists( 'get_post_thumbnail_id' ) && $thumbnail_id = get_post_thumbnail_id( $post_id ) ) {
			$image = wp_get_attachment_image_src( $thumbnail_id, 'full' );
			if ( $image ) {
				return $image[0];
			}
		}
		return '';
	}

	/**
	 * Get the tags associated with the post, as an array
	 * with slugs for keys and tag names for values
	 *
	 * @param int     $post_id
	 * @return array
	 */
	private function get_tags( $post_id ) {
		$tags = array();
		$terms = get_the_terms( $post_id, Group_Buying_Deal::TAG_TAXONOMY );
		if ( !$terms ) {
			return array();
		}
		foreach ( $terms as $tag ) {
			$tags[$tag->slug] = $tag->name;
		}
		return $tags;
	}

	/**
	 * Submit a new post to the syndication server
	 *
	 * @param array   $package
	 * @return string The URI of the post thus created
	 */
	private function create_syndicated_post( $package ) {
		$client = new GB_Aggregator_Client();
		$client->post_deal( $package );
		if ( $client->code() == '409' ) {
			$location = $client->location();
			$this->update_syndicated_post( $location, $package );
			return $location;
		}
		return $client->location();
	}


	/**
	 * Update an existing post on the syndication server
	 *
	 * @param string  $uri
	 * @param array   $package
	 * @return string The URI of the updated post (which may have changed?)
	 */
	private function update_syndicated_post( $uri, $package ) {
		$client = new GB_Aggregator_Client();
		$client->put_deal( $uri, $package );
		return $client->location();
	}

	/**
	 * Delete an existing post on the syndication server
	 *
	 * @param string  $uri
	 * @return bool If the deletion was successful
	 */
	private function delete_syndicated_post( $uri ) {
		$client = new GB_Aggregator_Client();
		return $client->delete_deal( $uri );
	}
}
