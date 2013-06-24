<?php

/**
 * GBS Merchant Model
 *
 * @package GBS
 * @subpackage Merchant
 */
class Group_Buying_Merchant extends Group_Buying_Post_Type {
	const POST_TYPE = 'gb_merchant';
	const REWRITE_SLUG = 'business';
	const MERCHANT_TYPE_TAXONOMY = 'gb_merchant_type';
	const MERCHANT_TAG_TAXONOMY = 'gb_merchant_tags';
	const MERCHANT_TYPE_TAX_SLUG = 'business-type';
	const MERCHANT_TAG_TAX_SLUG = 'business-tags';

	private static $instances = array();

	private static $meta_keys = array(
		'authorized_users' => '_authorized_users', // array
		'contact_title' => '_contact_title', // string
		'contact_name' => '_contact_name', // string
		'contact_street' => '_contact_street', // string
		'contact_city' => '_contact_city', // string
		'contact_state' => '_contact_state', // string
		'contact_postal_code' => '_contact_postal_code', // string
		'contact_country' => '_contact_country', // string
		'contact_phone' => '_contact_phone', // string
		'website' => '_website', // string
		'facebook' => '_facebook', // string
		'twitter' => '_twitter', // string
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.


	public static function init() {
		$post_type_args = array(
			'menu_position' => 4,
			'has_archive' => TRUE,
			'rewrite' => array(
				'slug' => self::REWRITE_SLUG,
				'with_front' => FALSE,
			),
			'supports' => array( 'title', 'editor', 'thumbnail', 'comments', 'custom-fields', 'revisions' ),
			'menu_icon' => GB_URL . '/resources/img/merchant.png'
		);
		self::register_post_type( self::POST_TYPE, 'Merchant', 'Merchants', $post_type_args );

		// register Business Type taxonomy
		$singular = 'Merchant Type';
		$plural = 'Merchant Types';
		$taxonomy_args = array(
			'rewrite' => array(
				'slug' => self::MERCHANT_TYPE_TAX_SLUG,
				'with_front' => FALSE,
				'hierarchical' => TRUE,
			),
		);
		self::register_taxonomy( self::MERCHANT_TYPE_TAXONOMY, array( self::POST_TYPE ), $singular, $plural, $taxonomy_args );

		// register Business Tag taxonomy
		$singular = 'Merchant Tag';
		$plural = 'Merchant Tags';
		$taxonomy_args = array(
			'hierarchical' => FALSE,
			'rewrite' => array(
				'slug' => self::MERCHANT_TAG_TAX_SLUG,
				'with_front' => FALSE,
				'hierarchical' => FALSE,
			),
		);
		self::register_taxonomy( self::MERCHANT_TAG_TAXONOMY, array( self::POST_TYPE ), $singular, $plural, $taxonomy_args );
	}


	protected function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Group_Buying_Merchant
	 */
	public static function get_instance( $id = 0 ) {
		if ( !$id ) {
			return NULL;
		}
		if ( !isset( self::$instances[$id] ) || !self::$instances[$id] instanceof self ) {
			self::$instances[$id] = new self( $id );
		}
		if ( self::$instances[$id]->post->post_type != self::POST_TYPE ) {
			return NULL;
		}
		return self::$instances[$id];
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return
	 */
	public static function get_merchant_object( $id = 0 ) {
		if ( !$id ) {
			return NULL;
		}
		$type = get_post_type( $id );

		if ( $type == self::POST_TYPE ) {
			$merchant = self::get_instance( $id );
		} elseif ( $type = Group_Buying_Deal::POST_TYPE ) {
			$deal = Group_Buying_Deal::get_instance( $id );
			$merchant = $deal->get_merchant();
		} else { return NULL; }

		return $merchant;
	}

	public static function get_merchant_id_for_user( $user_id = 0 ) {
		if ( !$user_id ) {
			$user_id = (int)get_current_user_id();
		}
		$merchant_ids = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['authorized_users'] => $user_id ) );
		if ( empty( $merchant_ids ) ) {
			$account_id = self::blank_merchant();
		} else {
			$account_id = $merchant_ids[0];
		}
		return $account_id;
	}

	public static function blank_merchant() {
		global $wpdb;
		$blank_merchant_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID from {$wpdb->posts} WHERE post_type = %s and post_title = %s", self::POST_TYPE, self::__( 'Blank Merchant' ) ) );
		if ( $blank_merchant_id ) {
			return $blank_merchant_id;
		}
		$post = array(
			'post_title' => self::__( 'Blank Merchant' ),
			'post_name' => self::__( 'blank-merchant' ),
			'post_status' => 'publish',
			'post_type' => self::POST_TYPE
		);
		$id = wp_insert_post( $post );
		return $id;
	}

	/**
	 *
	 *
	 * @static
	 * @return bool Whether the current query is for the merchant post type
	 */
	public static function is_merchant_query() {
		$post_type = get_query_var( 'post_type' );
		if ( $post_type == self::POST_TYPE ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 *
	 *
	 * @static
	 * @return bool Whether the current query is for the merchant post type
	 */
	public static function is_merchant_tax_query() {
		$taxonomy = get_query_var( 'taxonomy' );
		if ( $taxonomy == self::MERCHANT_TYPE_TAXONOMY || $taxonomy == self::MERCHANT_TAG_TAXONOMY ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Can the given user edit the Merchant's contact info, or draft deals for this merchant
	 *
	 * @param (int)   $user_id
	 * @return bool TRUE if the user is authorized to edit the Merchant record
	 */
	public function is_user_authorized( $user_id ) {
		$authorized_users = $this->get_authorized_users();
		if ( empty( $authorized_users ) ) return;
		return in_array( $user_id, $authorized_users );
	}

	/**
	 * Get a list of all users who are authorized to edit this Merchant
	 *
	 * @return array User IDs of all authorized users
	 */
	public function get_authorized_users() {
		$authorized_users = $this->get_post_meta( self::$meta_keys['authorized_users'], FALSE );
		if ( empty( $authorized_users ) ) {
			$authorized_users = array();
		}
		return $authorized_users;
	}

	/**
	 * Add a user to the list of authorized users
	 *
	 * @param int     $user_id
	 * @return void
	 */
	public function authorize_user( $user_id ) {
		$user = get_userdata( $user_id );
		if ( !is_a( $user, 'WP_User' ) ) { // Check if id is a WP User, if not try to find the id by cross referencing $id with an account.
			$account = Group_Buying_Account::get_instance_by_id( $user_id );
			$user_id = $account->get_user_id_for_account( $account->get_ID() );
		}
		if ( $user_id && !$this->is_user_authorized( $user_id ) ) {
			$this->add_post_meta( array(
					self::$meta_keys['authorized_users'] => $user_id
				) );
		}
	}

	/**
	 * Remove a user from the list of authorized users
	 *
	 * @param int     $user_id
	 * @return void
	 */
	public function unauthorize_user( $user_id ) {
		if ( $this->is_user_authorized( $user_id ) ) {
			$this->delete_post_meta( array(
					self::$meta_keys['authorized_users'] => $user_id
				) );
		}
	}

	public function get_deal_ids( ) {
		$deal_ids = Group_Buying_Deal::get_deals_by_merchant( $this->ID );
		return $deal_ids;
	}

	/**
	 * Get a list of deals associated with this merchant
	 *
	 * @param string|NULL $status A deal status code (one of GROUP_BUYING_DEAL::DEAL_STATUS_*)
	 *   or NULL to return all associated deals
	 * @return array A list of deals
	 */
	public function get_deals( $status = NULL ) {
		$deal_ids = self::get_deal_ids();
		$deals = array();
		foreach ( $deal_ids as $deal_id ) {
			$deal = Group_Buying_Deal::get_instance( $deal_id );
			if ( is_null( $status ) || $deal->get_status() == $status ) {
				$deals[] = $deal;
			}
		}
		return $deals;
	}

	public static function get_url() {
		if ( gb_using_permalinks() ) {
			$url = trailingslashit( home_url() ).trailingslashit( self::REWRITE_SLUG );
		} else {
			$url = add_query_arg( self::REWRITE_SLUG, 1, home_url() );
		}
		return $url;
	}

	public function get_contact_name() {
		$contact_name = $this->get_post_meta( self::$meta_keys['contact_name'] );
		return $contact_name;
	}

	public function set_contact_name( $contact_name ) {
		$this->save_post_meta( array(
				self::$meta_keys['contact_name'] => $contact_name
			) );
		return $contact_name;
	}

	public function get_contact_title() {
		$contact_title = $this->get_post_meta( self::$meta_keys['contact_title'] );
		return $contact_title;
	}

	public function set_contact_title( $contact_title ) {
		$this->save_post_meta( array(
				self::$meta_keys['contact_title'] => $contact_title
			) );
		return $contact_title;
	}

	public function get_contact_street() {
		$contact_street = $this->get_post_meta( self::$meta_keys['contact_street'] );
		return $contact_street;
	}

	public function set_contact_street( $contact_street ) {
		$this->save_post_meta( array(
				self::$meta_keys['contact_street'] => $contact_street
			) );
		return $contact_street;
	}

	public function get_contact_city() {
		$contact_city = $this->get_post_meta( self::$meta_keys['contact_city'] );
		return $contact_city;
	}

	public function set_contact_city( $contact_city ) {
		$this->save_post_meta( array(
				self::$meta_keys['contact_city'] => $contact_city
			) );
		return $contact_city;
	}

	public function get_contact_state() {
		$contact_state = $this->get_post_meta( self::$meta_keys['contact_state'] );
		return $contact_state;
	}

	public function set_contact_state( $contact_state ) {
		$this->save_post_meta( array(
				self::$meta_keys['contact_state'] => $contact_state
			) );
		return $contact_state;
	}

	public function get_contact_postal_code() {
		$contact_postal_code = $this->get_post_meta( self::$meta_keys['contact_postal_code'] );
		return $contact_postal_code;
	}

	public function set_contact_postal_code( $contact_postal_code ) {
		$this->save_post_meta( array(
				self::$meta_keys['contact_postal_code'] => $contact_postal_code
			) );
		return $contact_postal_code;
	}

	public function get_contact_country() {
		$contact_country = $this->get_post_meta( self::$meta_keys['contact_country'] );
		return $contact_country;
	}

	public function set_contact_country( $contact_country ) {
		$this->save_post_meta( array(
				self::$meta_keys['contact_country'] => $contact_country
			) );
		return $contact_country;
	}

	public function get_contact_phone() {
		$contact_phone = $this->get_post_meta( self::$meta_keys['contact_phone'] );
		return $contact_phone;
	}

	public function set_contact_phone( $contact_phone ) {
		$this->save_post_meta( array(
				self::$meta_keys['contact_phone'] => $contact_phone
			) );
		return $contact_phone;
	}

	public function get_website() {
		$website = $this->get_post_meta( self::$meta_keys['website'] );
		return $website;
	}

	public function set_website( $website ) {
		$this->save_post_meta( array(
				self::$meta_keys['website'] => $website
			) );
		return $website;
	}

	public function get_facebook() {
		$facebook = $this->get_post_meta( self::$meta_keys['facebook'] );
		return $facebook;
	}

	public function set_facebook( $facebook ) {
		$this->save_post_meta( array(
				self::$meta_keys['facebook'] => $facebook
			) );
		return $facebook;
	}

	public function get_twitter() {
		$twitter = $this->get_post_meta( self::$meta_keys['twitter'] );
		return $twitter;
	}

	public function set_twitter( $twitter ) {
		$this->save_post_meta( array(
				self::$meta_keys['twitter'] => $twitter
			) );
		return $twitter;
	}

	/**
	 * Add a file as a post attachment.
	 */
	public function set_attachement( $files, $key = '' ) {
		if ( !function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin' . '/includes/image.php';
			require_once ABSPATH . 'wp-admin' . '/includes/file.php';
			require_once ABSPATH . 'wp-admin' . '/includes/media.php';
		}

		foreach ( $files as $file => $array ) {
			if ( $files[$file]['error'] !== UPLOAD_ERR_OK ) {
				// Group_Buying_Controller::set_message('upload error : ' . $files[$file]['error']);
			}
			if ( $key !== '' ) {
				if ( $key == $file  ) {
					$attach_id = media_handle_upload( $file, $this->ID );
				}
			}
			else {
				$attach_id = media_handle_upload( $file, $this->ID );
			}
		}
		// Make it a thumbnail while we're at it.
		if ( !is_wp_error($attach_id) && $attach_id > 0 ) {
			update_post_meta( $this->ID, '_thumbnail_id', $attach_id );
		}
		return $attach_id;
	}


	/**
	 *
	 *
	 * @param int     $merchant_id The merchant to look for
	 * @return array List of IDs for deals created by this merchant
	 */
	public static function get_merchants_by_account( $user_id ) {
		$merchants = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['authorized_users'] => $user_id ) );
		return $merchants;
	}
}
