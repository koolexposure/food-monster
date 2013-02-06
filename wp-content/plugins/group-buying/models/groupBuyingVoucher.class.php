<?php

/**
 * GBS Voucher Model
 *
 * @package GBS
 * @subpackage Voucher
 */
class Group_Buying_Voucher extends Group_Buying_Post_Type {
	const POST_TYPE = 'gb_voucher';
	const REWRITE_SLUG = 'vouchers';
	const TEMP_ACCESS_KEY = 'temp_voucher_access';
	private static $instances = array();
	private $deal; // The deal associated with this voucher
	private $purchase; // The purchase associated with this voucher

	protected static $meta_keys = array(
		'claimed' => '_claimed', // int/time
		'deal_id' => '_voucher_deal_id', // int
		'product_data' => '_product_data', // array
		'purchase_id' => '_purchase_id', // int
		'redemption_data' => '_redemption_data', // array
		'security_code' => '_voucher_security_code', //string
		'serial_number' => '_voucher_serial_number', // string
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.

	public static function init() {
		$post_type_args = array(
			'public' => TRUE,
			'exclude_from_search' => TRUE,
			'rewrite' => TRUE,
			'has_archive' => TRUE,
			'show_ui' => FALSE,
			'show_in_menu' => 'group-buying',
			'rewrite' => array(
				'slug' => self::REWRITE_SLUG,
				'with_front' => TRUE,
			),
			'supports' => array( 'title' ),
		);
		self::register_post_type( self::POST_TYPE, 'Voucher', 'Vouchers', $post_type_args );
		add_action( 'pre_get_posts', array( get_class(), 'filter_voucher_query' ), 10, 1 );
		add_action( 'wp_unique_post_slug', array( get_class(), 'filter_post_slug' ), 10, 4 );

	}

	public static function is_voucher_query( WP_Query $query = NULL ) {
		if ( is_null( $query ) ) {
			global $wp_query;
			$query = $wp_query;
		}
		if ( !isset( $query->query_vars['post_type'] ) ) {
			return FALSE; // normal posts query
		}
		if ( $query->query_vars['post_type'] == self::POST_TYPE ) {
			return TRUE;
		}
		if ( is_array( $query->query_vars['post_type'] ) && in_array( self::POST_TYPE, $query->query_vars['post_type'] ) ) {
			return TRUE;
		}
		return FALSE;
	}


	/**
	 * Edit the query to remove other users vouchers
	 *
	 * @param WP_Query $query
	 * @return void
	 */
	public static function filter_voucher_query( WP_Query $query ) {
		

		if ( self::is_voucher_query( $query ) && // we only care if this is the query for vouchers
			!is_admin() ) {

			// Nonce used to view voucher, do not filter
			if ( self::temp_voucher_access( $query->query_vars[self::POST_TYPE] ) ) {
				return;
			}
			
			// Standard View
			if ( !isset( $query->query_vars['gb_bypass_filter'] ) && // don't filter if the bypass flag is set in the query
				$query->query_vars['post_status'] != 'pending' // don't filter pending queries
				) {
					// get all the user's purchases
					$purchases = Group_Buying_Purchase::get_purchases( array(
							'user' => get_current_user_id(),
						) );
					if ( empty( $purchases ) ) { // no purchases means no vouchers
						$query->query_vars['post__in'] = array( 0 );
						return;
					}
					if ( !isset( $query->query_vars['meta_query'] ) || !is_array( $query->query_vars['meta_query'] ) ) {
						$query->query_vars['meta_query'] = array();
					}
					$query->query_vars['meta_query'][] = array(
						'key' => self::$meta_keys['purchase_id'],
						'value' => $purchases,
						'compare' => 'IN',
						'type' => 'NUMERIC',
					);
			}
		}
		
	}

	public function temp_voucher_access( $voucher_id = 0 ) {
		if ( isset( $_GET['_wpnonce'] ) && get_post_type( $voucher_id ) === self::POST_TYPE ) {
			return wp_verify_nonce( $_GET['_wpnonce'], self::TEMP_ACCESS_KEY . $voucher_id );
		}
	}

	public function temp_voucher_access_attempt( $voucher_id = 0 ) {
		global $wp_query;
		if ( isset( $wp_query->query_vars[self::POST_TYPE] ) ) { // validate if possible
			return self::temp_voucher_access( $wp_query->query_vars[self::POST_TYPE] );
		}
		return isset( $_GET['_wpnonce'] );
	}

	public static function new_voucher( $purchase_id, $deal_id ) {
		$deal = Group_Buying_Deal::get_instance( $deal_id );
		$title = $deal->get_title();
		$post = array(
			'post_title' => sprintf( self::__( 'Voucher for %s' ), $title ),
			'post_type' => self::POST_TYPE,
			'post_status' => 'pending', // it won't be marked published until the purchase is paid for
		);
		$id = wp_insert_post( $post );
		if ( !is_wp_error( $id ) ) {
			$voucher = self::get_instance( $id );
			// $voucher->post->post_name = $id; // Filtered since it doesn't work for non-admins.
			// $voucher->save_post();
			$voucher->set_purchase( $purchase_id );
			$voucher->set_deal( $deal_id );
		}
		do_action( 'gb_new_voucher', $id, $purchase_id, $deal_id );
		return $id;
	}

	public static function filter_post_slug( $slug, $post_ID, $post_status, $post_type ) {
		if ( $post_type == self::POST_TYPE ) {
			return $post_ID;
		}
		return $slug;
	}

	protected function __construct( $id ) {
		parent::__construct( $id );
	}

	protected function refresh() {
		parent::refresh();
		$purchase_id = $this->get_post_meta( self::$meta_keys['purchase_id'] );
		if ( $purchase_id ) {
			$this->purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		}
		$deal_id = $this->get_post_meta( self::$meta_keys['deal_id'] );
		if ( $deal_id ) {
			$this->deal = Group_Buying_Deal::get_instance( $deal_id );
		}
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Group_Buying_Voucher
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
	 * Find all Vouchers associated with a specific purchase
	 *
	 * @static
	 * @param int     $purchase_id ID of the purchase to search by
	 * @return array List of IDs of Vouchers associated with the given purchase
	 */
	public static function get_vouchers_for_purchase( $purchase_id ) {
		$voucher_ids = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['purchase_id'] => $purchase_id ) );
		return $voucher_ids;
	}

	/**
	 * Find all Vouchers associated with a specific deal
	 *
	 * @static
	 * @param int     $purchase_id ID of the purchase to search by
	 * @return array List of IDs of Vouchers associated with the given purchase
	 */
	public static function get_vouchers_for_deal( $deal_id ) {
		$voucher_ids = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['deal_id'] => $deal_id ) );
		return $voucher_ids;
	}

	public function get_purchase() {
		$purchase_id = $this->get_post_meta( self::$meta_keys['purchase_id'] );
		if ( empty( $purchase_id ) )
			return; // prevent a new purchase being created if no purchase_id is present.
		return Group_Buying_Purchase::get_instance( $purchase_id );
	}

	public function get_purchase_id() {
		$purchase_id = $this->get_post_meta( self::$meta_keys['purchase_id'] );
		if ( empty( $purchase_id ) )
			return; // prevent a new purchase being created if no purchase_id is present.
		return $purchase_id;
	}

	public function set_purchase( $id ) {
		$this->save_post_meta( array( self::$meta_keys['purchase_id'] => $id ) );
		$this->refresh();
	}

	public function get_account() {
		$purchase = $this->get_purchase();
		return $purchase->get_account_id();
	}

	public function get_deal() {
		$deal_id = $this->get_post_meta( self::$meta_keys['deal_id'] );
		return Group_Buying_Deal::get_instance( $deal_id );
	}

	public function get_deal_id() {
		$deal_id = $this->get_post_meta( self::$meta_keys['deal_id'] );
		return $deal_id;
	}

	public function set_deal( $id ) {
		$this->save_post_meta( array( self::$meta_keys['deal_id'] => $id ) );
		$this->refresh();
	}

	/**
	 *
	 *
	 * @return string The voucher's expiration date
	 */
	public function get_expiration_date() {
		return $this->deal->get_voucher_expiration_date();
	}

	/**
	 *
	 *
	 * @return string The logo for this voucher
	 */
	public function get_logo() {
		return $this->deal->get_voucher_logo();
	}

	/**
	 *
	 *
	 * @return string Instructions on how to use this voucher
	 */
	public function get_usage_instructions() {
		return $this->deal->get_voucher_how_to_use();
	}


	/**
	 *
	 *
	 * @return string This voucher's serial number
	 */
	public function get_serial_number() {
		return $this->get_post_meta( self::$meta_keys['serial_number'] );
	}

	/**
	 * Assign a serial number (or generate one)
	 *
	 * @param string  $number
	 * @return void
	 */
	public function set_serial_number( $number = '' ) {
		if ( !$number ) {
			$serial = $this->deal->get_next_serial();
			if ( !$serial ) { // generate a random serial
				$random = wp_generate_password( 12, FALSE, FALSE );
				$serial = implode( '-', str_split( $random, 4 ) );
			}
			$this->deal->mark_serial_used( $serial );
			$prefix = $this->deal->get_voucher_id_prefix( TRUE );
			$number = $prefix.$serial;
		}
		$this->save_post_meta( array( 
			self::$meta_keys['serial_number'] => apply_filters( 'gb_set_voucher_serial_number', $number, $this ) 
			) );
	}

	/**
	 *
	 *
	 * @return array Locations where this voucher may be used
	 */
	public function get_locations() {
		return $this->deal->get_voucher_locations();
	}

	/**
	 *
	 *
	 * @return array Fine print
	 */
	public function get_fine_print() {
		return $this->deal->get_fine_print();
	}

	/**
	 *
	 *
	 * @return string Google maps iframe code for this voucher
	 */
	public function get_map() {
		return $this->deal->get_voucher_map();
	}

	public function get_product_data() {
		return $this->get_post_meta( self::$meta_keys['product_data'] );
	}

	public function set_product_data( $data = '' ) {
		$this->save_post_meta( array( self::$meta_keys['product_data'] => apply_filters( 'gb_vouchers_set_product_data', $data ) ) );
	}

	public function get_security_code() {
		return $this->get_post_meta( self::$meta_keys['security_code'] );
	}

	public function set_security_code( $code = '' ) {
		if ( !$code ) {
			$code = self::get_ID() . '-' . strtoupper( wp_generate_password( 5, FALSE, FALSE ) );
		}
		$this->save_post_meta( array( self::$meta_keys['security_code'] => apply_filters( 'gb_vouchers_set_security_code', $code ) ) );
	}

	public function get_redemption_data() {
		return $this->get_post_meta( self::$meta_keys['redemption_data'] );
	}

	public function set_redemption_data( $data ) {
		$this->save_post_meta( array( self::$meta_keys['redemption_data'] => $data ) );
	}

	public function get_claimed_date() {
		return $this->get_post_meta( self::$meta_keys['claimed'] );
	}

	public function set_claimed_date( $reset = false ) {
		if ( $reset ) { // If resetting the claim date.
			$this->save_post_meta( array( self::$meta_keys['claimed'] => NULL ) );
			return TRUE;
		}
		if ( self::get_claimed_date() == '' ) { // don't allow for it to be reset.
			$this->save_post_meta( array( self::$meta_keys['claimed'] => current_time( 'timestamp' ) ) );
			return current_time( 'timestamp' );
		}
		return;
	}

	/**
	 * Transition the voucher's status from publish to pending
	 *
	 * @return void
	 */
	public function deactivate() {
		$this->post->post_status = 'trash';
		$this->save_post();
		do_action( 'voucher_deactivated', $this );
	}

	/**
	 * Transition the voucher's status from pending to publish
	 *
	 * @return void
	 */
	public function activate() {
		$this->post->post_status = 'publish';
		$this->save_post();
		do_action( 'voucher_activated', $this );
	}

	public function is_active() {
		return $this->post->post_status == 'publish';
	}

	/**
	 * Get a list of pending vouchers for the given deal
	 *
	 * @static
	 * @param int     $deal_id
	 * @return array The IDs of the pending vouchers
	 */
	public static function get_pending_vouchers( $deal_id ) {
		$vouchers = new WP_Query( array(
				'post_type' => self::POST_TYPE,
				'post_status' => 'pending',
				'posts_per_page' => -1,
				'fields' => 'ids',
				'meta_query' => array(
					array(
						'key' => self::$meta_keys['deal_id'],
						'value' => $deal_id,
						'type' => 'NUMERIC',
					),
				),
			) );
		return $vouchers->posts;
	}

	/**
	 *
	 *
	 * @param int     $serial the serial to look against
	 * @return array List of IDs for vouchers with this serial
	 */
	public static function get_voucher_by_security_code( $code ) {
		$vouchers = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['security_code'] => $code ) );
		return $vouchers;
	}

	/**
	 *
	 *
	 * @param int     $serial the serial to look against
	 * @return array List of IDs for vouchers with this serial
	 */
	public static function get_voucher_by_serial( $serial ) {
		$vouchers = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['serial_number'] => $serial ) );
		return $vouchers;
	}
}
