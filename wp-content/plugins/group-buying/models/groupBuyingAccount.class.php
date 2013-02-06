<?php

/**
 * GBS Account Model
 *
 * @package GBS
 * @subpackage Account
 */
class Group_Buying_Account extends Group_Buying_Post_Type {
	const POST_TYPE = 'gb_account';
	const SUSPEND = 'suspended';
	const REWRITE_SLUG = 'account';
	const CREDIT_TYPE_DEFAULT = 'default';
	const NO_MAXIMUM = -1;

	const CACHE_PREFIX = 'gbs_account';
	const CACHE_GROUP = 'gbs_account';

	protected $user_id;
	private static $instances;

	private static $meta_keys = array(
		'user_id' => '_user_id', // int
		'first_name' => '_first_name', // string
		'last_name' => '_last_name', // string
		'address' => '_address', // array
		'ship_address' => '_ship_address', // array
		'data' => '_data', // array
	);

	public static function init() {
		$post_type_args = array(
			'has_archive' => FALSE,
			'public' => FALSE,
			'publicly_queryable' => FALSE,
			'exclude_from_search' => TRUE,
			'show_ui' => FALSE,
			'show_in_menu' => 'group-buying',
			'supports' => array( null ),
			'rewrite' => FALSE,
		);

		self::register_post_type( self::POST_TYPE, 'Account', 'Accounts', $post_type_args );

		add_action( 'pre_get_posts', array( get_class(), 'filter_dummy' ), 10, 1 );

		add_action( 'update_post_meta', array( get_class(), 'maybe_clear_id_cache' ), 10, 4 );
		add_action( 'added_post_meta', array( get_class(), 'maybe_clear_id_cache' ), 10, 4 );
		add_action( 'delete_post_meta', array( get_class(), 'maybe_clear_id_cache' ), 10, 4 );
	}

	protected function __construct( $user_id, $account_id = 0 ) {
		$this->user_id = $user_id;
		if ( !$account_id ) {
			$account_id = self::get_account_id_for_user( $user_id );
		}
		parent::__construct( $account_id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $user_id
	 * @return Group_Buying_Account The cart for the given user
	 */
	public static function get_instance( $user_id = 0, $account_id = 0  ) {
		if ( $user_id == -1 ) { // For those content types with temporary user_ids, e.g. Gifting.
			return FALSE;
		}
		if ( !$user_id ) {
			$user_id = get_current_user_id();
		}
		if ( !$account_id ) {
			if ( !$account_id = self::get_account_id_for_user( $user_id ) ) {
				return NULL;
			}
		}
		if ( !isset( self::$instances[$account_id] ) || !self::$instances[$account_id] instanceof self ) {
			self::$instances[$account_id] = new self( $user_id, $account_id );
		}
		if ( self::$instances[$account_id]->post->post_type != self::POST_TYPE ) {
			return NULL;
		}
		return self::$instances[$account_id];
	}

	public static function get_instance_by_id( $account_id = 0 ) {
		$user_id = self::get_user_id_for_account( $account_id );
		if ( !$user_id ) {
			return NULL;
		}
		return self::get_instance( $user_id, $account_id );
	}

	public static function get_account_id_for_user( $user_id = 0 ) {
		if ( $user_id == -1 ) { // For those content types with temporary user_ids, e.g. Gifting.
			return FALSE;
		}
		if ( !is_numeric( $user_id ) ) {
			$user_id = 0; // choosing to provide a sensible default; other option is to throw an exception
		}
		if ( !$user_id ) {
			$user_id = (int)get_current_user_id();
		}

		// see if we've cached the value
		if ( $account_id = self::get_id_cache( $user_id ) ) {
			return $account_id;
		}

		$account_ids = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['user_id'] => $user_id ) );

		if ( empty( $account_ids ) ) {
			$account_id = self::new_account( $user_id );
		} else {
			$account_id = $account_ids[0];
		}

		self::set_id_cache( $user_id, $account_id );

		return $account_id;
	}

	public static function get_user_id_for_account( $account_id ) {
		if ( isset( self::$instances[$account_id] ) ) {
			return self::$instances[$account_id]->user_id;
		} else {
			$user_id = get_post_meta( $account_id, self::$meta_keys['user_id'], TRUE );
			return $user_id;
		}
	}

	/**
	 * Create a new account
	 *
	 * @static
	 * @param int     $user_id
	 * @return int|WP_Error
	 */
	private static function new_account( $user_id ) {
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			if ( !is_a( $user, 'WP_User' ) ) { // prevent an account being created without a proper user_id associated.
				return NULL;
			}
			$title = $user->user_login;
			if ( $user->user_nicename ) {
				$title = $user->user_nicename;
			}
			$post = array(
				'post_title' => $title,
				'post_name' => $user->user_login,
				'post_status' => 'publish',
				'post_type' => self::POST_TYPE,
			);
		} else { // create a dummy account for anonymous users
			$post = array(
				'post_title' => self::__( 'Anonymous' ),
				'post_status' => 'publish',
				'post_type' => self::POST_TYPE,
			);
		}
		$id = wp_insert_post( $post );
		if ( !is_wp_error( $id ) ) {
			if ( is_a( $user_id, 'WP_User' ) ) { // Bug found in 3.6.6 where accounts are created with the WP_User object instead of int; TODO remove
				$user_id = $user_id->ID;
			}
			update_post_meta( $id, self::$meta_keys['user_id'], $user_id );
		}
		return $id;
	}



	/**
	 * When the _user_id post meta for an account is updated,
	 * flush the cache for both the old user ID and the new
	 *
	 * @wordpress-action update_post_meta (update_{$meta_type}_meta)
	 * @wordpress-action added_post_meta (added_{$meta_type}_meta)
	 * @wordpress-action delete_post_meta (delete_{$meta_type}_meta)
	 *
	 * @static
	 * @param int|array $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $_meta_value
	 */
	public static function maybe_clear_id_cache( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( $meta_key == self::$meta_keys['user_id'] && get_post_type($object_id) == self::POST_TYPE ) {
			if ( $meta_value ) { // this might be empty on a delete_post_meta
				self::clear_id_cache($meta_value);
			}
			$old_value = get_post_meta($object_id, $meta_key);
			if ( $old_value != $meta_value ) { // this is probably the same on added_post_meta
				self::clear_id_cache($old_value);
			}
		}
	}

	/**
	 * Get the account ID cache for the user
	 *
	 * @static
	 * @param int $user_id
	 * @return int
	 */
	private static function get_id_cache( $user_id ) {
		return (int)wp_cache_get(self::CACHE_PREFIX.'_id_'.$user_id, self::CACHE_GROUP);
	}

	/**
	 * Set the account ID cache for the user
	 *
	 * @static
	 * @param int $user_id
	 * @param int $account_id
	 */
	private static function set_id_cache( $user_id, $account_id ) {
		wp_cache_set(self::CACHE_PREFIX.'_id_'.$user_id, (int)$account_id, self::CACHE_GROUP);
	}

	/**
	 * Delete the account ID cache for the user
	 *
	 * @static
	 * @param int $user_id
	 */
	private static function clear_id_cache( $user_id ) {
		wp_cache_delete(self::CACHE_PREFIX.'_id_'.$user_id, self::CACHE_GROUP);
	}

	/**
	 * Edit the query on the account edit page to remove the dummy post
	 *
	 * @param WP_Query $query
	 * @return void
	 */
	public function filter_dummy( WP_Query $query ) {
		if ( is_admin() ) {
			global $wpdb;
			// we only care if this is the query to show a the edit profile form
			if ( isset( $query->query_vars['post_type'] ) && self::POST_TYPE == $query->query_vars['post_type'] ) {
				$blank_account_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID from {$wpdb->posts} WHERE post_type = %s and post_title = %s", self::POST_TYPE, self::__( 'Anonymous' ) ) );
				if ( isset( $query->query_vars['post__not_in'] ) && !empty( $query->query_vars['post__not_in'] ) ) {
					$query->query_vars['post__not_in'][] = $blank_account_id;
				} else {
					$query->query_vars['post__not_in'] = array( $blank_account_id );
				}
			}
		}
	}

	/**
	 * Get the user's balance for the given credit type
	 *
	 * @param string  $credit_type
	 * @return int|float
	 */
	public function get_credit_balance( $credit_type = self::CREDIT_TYPE_DEFAULT ) {
		$balance = $this->get_post_meta( "_credit_balance_".$credit_type );
		if ( is_null( $balance ) || $balance < 0.01 ) {
			$balance = 0;
		}
		return $balance;
	}

	/**
	 * Set the user's balance for the given credit type
	 *
	 * @param int|float $balance
	 * @param string  $credit_type
	 * @return void
	 */
	public function set_credit_balance( $balance, $credit_type = self::CREDIT_TYPE_DEFAULT ) {
		$this->save_post_meta( array(
				"_credit_balance_".$credit_type => $balance
			) );
	}

	/**
	 * Add to the user's balance for the given credit type
	 *
	 * @param int|float $qty         The amount to add
	 * @param string  $credit_type
	 * @return int|float The new balance
	 */
	public function add_credit( $qty, $credit_type = self::CREDIT_TYPE_DEFAULT ) {
		$balance = $this->get_credit_balance( $credit_type );
		$balance = $balance + $qty;
		$this->set_credit_balance( $balance, $credit_type );
		return $balance;
	}

	/**
	 * Deduct from the user's balance for the given credit type
	 *
	 * @param int|float $qty         The amount to deduct
	 * @param string  $credit_type
	 * @return int|float The new balance
	 */
	public function deduct_credit( $qty, $credit_type = self::CREDIT_TYPE_DEFAULT ) {
		$balance = $this->get_credit_balance( $credit_type );
		$total = $balance - $qty;
		if ( $total < 0.01 ) {
			$total = 0;
		}
		$this->set_credit_balance( $total, $credit_type );
	}

	/**
	 * Move credits to a holding area so they can't be spent
	 *
	 * @param int|float $qty
	 * @param string  $credit_type
	 * @return float|int
	 */
	public function reserve_credit( $qty, $credit_type = self::CREDIT_TYPE_DEFAULT ) {
		$this->deduct_credit( $qty, $credit_type );
		return $this->add_credit( $qty, $credit_type.'_reserved' );
	}

	/**
	 * Move credits out of the holding area
	 *
	 * @param int|float $qty
	 * @param string  $credit_type
	 * @return float|int
	 */
	public function restore_credit( $qty, $credit_type = self::CREDIT_TYPE_DEFAULT ) {
		$this->deduct_credit( $qty, $credit_type.'_reserved' );
		return $this->add_credit( $qty, $credit_type );
	}

	/**
	 *
	 *
	 * @param int     $deal_id
	 * @param array   $data
	 * @return int The quantity the user is allowed to purchase
	 */
	public function can_purchase( $deal_id, $data = array() ) {
		$qty = self::NO_MAXIMUM;
		$deal = Group_Buying_Deal::get_instance( $deal_id );
		if ( !is_a( $deal, 'Group_Buying_Deal' ) ) {
			return 0;
		}
		if ( $deal->is_closed() ) {
			return 0;
		}
		$max_purchases_per_user = $deal->get_max_purchases_per_user();
		if ( $max_purchases_per_user >= 0 ) {
			$total_purchased = 0;
			$purchases = $deal->get_purchases_by_account( $this->ID );
			if ( $purchases ) {
				foreach ( $purchases as $purchase_id ) {
					$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
					$number_purchased = $purchase->get_product_quantity( $deal_id );
					$total_purchased += $number_purchased;
				}
			}
			$qty = $max_purchases_per_user - $total_purchased;
		}
		$remaining = $deal->get_remaining_allowed_purchases();
		if ( $remaining >= 0 && ( $remaining < $qty || $qty == self::NO_MAXIMUM ) ) {
			$qty = $remaining;
		}
		$qty = apply_filters( 'account_can_purchase', $qty, $deal_id, $data, $this );
		return $qty;
	}

	public function get_name( $component = NULL ) {
		$first = $this->get_post_meta( self::$meta_keys['first_name'] );
		$last = $this->get_post_meta( self::$meta_keys['last_name'] );
		switch ( $component ) {
		case 'first':
			return $first;
		case 'last':
			return $last;
		default:
			return $first.' '.$last;
		}
	}

	public function set_name( $component = NULL, $value ) {
		switch ( $component ) {
		case 'first':
			$this->save_post_meta( array( self::$meta_keys['first_name'] => $value ) );
			break;
		case 'last':
			$this->save_post_meta( array( self::$meta_keys['last_name'] => $value ) );
			break;
		}
	}

	public function get_data() {
		return $this->get_post_meta( self::$meta_keys['data'] );
	}

	public function set_data( $address ) {
		return $this->save_post_meta( array( self::$meta_keys['data'] => $address ) );
	}

	public function get_address() {
		return $this->get_post_meta( self::$meta_keys['address'] );
	}

	public function set_address( $address ) {
		return $this->save_post_meta( array( self::$meta_keys['address'] => $address ) );
	}

	public function get_ship_address() {
		return $this->get_post_meta( self::$meta_keys['ship_address'] );
	}

	public function set_ship_address( $ship_address ) {
		return $this->save_post_meta( array( self::$meta_keys['ship_address'] => $ship_address ) );
	}

	public function get_user_id() {
		$user_id = $this->user_id;
		if ( is_a( $user_id, 'WP_User' ) ) {  // Bug found in 3.6.6 where accounts are created with the WP_User object instead of int; TODO remove
			$user_id = $user_id->ID;
			// correct the meta; TODO remove
			update_post_meta( $this->get_id(), self::$meta_keys['user_id'], $user_id );
		}
		return $user_id;
	}

	public function get_user() {
		$user = new WP_User( $this->user_id );
		return $user;
	}

	public function is_account( $account_id ) {
		if ( is_object( $account_id ) ) {
			if ( $account_id instanceof self ) {
				return TRUE;
			}
		}
		if ( self::POST_TYPE === get_post_type( $account_id ) ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Suspend an account
	 *
	 * @return void
	 */
	public function suspend() {
		$this->post->post_status = self::SUSPEND;
		$this->save_post();
		do_action( 'account_suspended', $this );
	}

	public function unsuspend() {
		$this->post->post_status = 'publish';
		$this->save_post();
		do_action( 'account_unsuspended', $this );
	}

	public function is_suspended() {
		return $this->post->post_status == self::SUSPEND;
	}

}
