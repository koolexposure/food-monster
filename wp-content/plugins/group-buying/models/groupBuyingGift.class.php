<?php

/**
 * GBS Gift Model
 *
 * @package GBS
 * @subpackage Gift
 */
class Group_Buying_Gift extends Group_Buying_Post_Type {
	const POST_TYPE = 'gb_gift';
	const REWRITE_SLUG = 'gifts';
	//const COUPON_TYPE_TAXONOMY = 'gb_gift_type';
	const NO_EXPIRATION_DATE = -1;

	private static $instances = array();


	private static $meta_keys = array(
		'coupon_code' => '_coupon_code', // string
		'expiration_date' => '_expiration_date', // int
		'purchase' => '_purchase', // int
		'recipient' => '_recipient', // string Email address of the gift recipient
		'message' => '_message', // string
		'value' => '_value', // string
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.

	public static function init() {
		$post_type_args = array(
			'has_archive' => FALSE,
			'show_in_menu' => FALSE,
			'rewrite' => FALSE,
			'public' => FALSE,
			'publicly_queryable' => FALSE
		);
		self::register_post_type( self::POST_TYPE, 'Gift', 'Gifts', $post_type_args );

	}

	protected function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Group_Buying_Gift
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

	public static function new_gift( $purchase_id, $recipient, $message ) {
		$post = array(
			'post_title' => sprintf( self::__( 'Gift Purchase #%d' ), $purchase_id ),
			'post_status' => 'pending',
			'post_type' => self::POST_TYPE,
		);
		$id = wp_insert_post( $post );
		if ( !is_wp_error( $id ) ) {
			$gift = self::get_instance( $id );
			$gift->set_purchase_id( $purchase_id );
			$gift->set_coupon_code( self::random_coupon_code() );
			$gift->set_expiration_date( self::NO_EXPIRATION_DATE );
			$gift->set_recipient( $recipient );
			$gift->set_message( $message );

			$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
			$purchase->set_user( Group_Buying_Purchase::NO_USER ); // TODO create a dummy account for all gifts
		}
		return $id;
	}

	public static function get_gift_for_purchase( $purchase_id ) {
		$ids = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['purchase']=>$purchase_id ) );
		if ( is_array( $ids ) && $ids ) {
			return (int)$ids[0];
		}
		return 0;
	}

	public static function validate_gift( $email, $code ) {
		$ids = self::find_by_meta( self::POST_TYPE, array(
				self::$meta_keys['coupon_code'] => $code,
				self::$meta_keys['recipient'] => $email,
			) );
		if ( is_array( $ids ) && $ids ) {
			return $ids[0];
		}
		return 0;
	}

	public static function random_coupon_code() {
		$code = strtoupper( wp_generate_password( 8, FALSE, FALSE ) );
		return $code;
	}

	public function activate() {
		$this->post->post_status = 'publish';
		$this->save_post();
		do_action( 'gb_gift_notification', array( 'gift' => $this ) );
		do_action( 'gift_activated', $this );
	}

	/**
	 *
	 *
	 * @return string The coupon code
	 */
	public function get_coupon_code() {
		$code = $this->get_post_meta( self::$meta_keys['coupon_code'] );
		if ( is_null( $code ) ) {
			$code = '';
		}
		return $code;
	}

	/**
	 * Set a new value for the coupon code
	 *
	 * @param string  $code
	 * @return string The coupon code
	 */
	public function set_coupon_code( $code ) {
		$this->save_post_meta( array(
				self::$meta_keys['coupon_code'] => $code,
			) );
		return $code;
	}

	/**
	 *
	 *
	 * @return int The timestamp of this gift's expiration date
	 */
	public function get_expiration_date() {
		$expiration_date = $this->get_post_meta( self::$meta_keys['expiration_date'] );
		if ( is_null( $expiration_date ) ) {
			$expiration_date = self::NO_EXPIRATION_DATE;
		}
		return $expiration_date;
	}

	/**
	 *
	 *
	 * @return bool Whether the gift is expired
	 */
	public function is_expired() {
		if ( $this->get_expiration_date() == self::NO_EXPIRATION_DATE ) {
			return FALSE;
		}
		if ( $this->get_expiration_date() < current_time( 'timestamp' ) ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Set a new expiration date for this gift
	 *
	 * @param int     $date The new value
	 * @return int the Timestamp of this gift's expiration date
	 */
	public function set_expiration_date( $date ) {
		$this->save_post_meta( array(
				self::$meta_keys['expiration_date'] => $date
			) );
		return $date;
	}

	/**
	 *
	 *
	 * @return int The ID of the Purchase associated with this gift
	 */
	public function get_purchase_id() {
		$purchase_id = $this->get_post_meta( self::$meta_keys['purchase'] );
		return $purchase_id;
	}

	/**
	 * Associate this gift with a new purchase
	 *
	 * @param int     $id The new value
	 * @return int The ID of the Purchase associated with this gift
	 */
	public function set_purchase_id( $id ) {
		$this->save_post_meta( array(
				self::$meta_keys['purchase'] => $id
			) );
		return $id;
	}

	/**
	 *
	 *
	 * @return Group_Buying_Purchase The Purchase associated with this gift
	 */
	public function get_purchase() {
		$id = $this->get_purchase_id();
		return Group_Buying_Purchase::get_instance( $id );
	}

	/**
	 *
	 *
	 * @return int|float The dollar amount of the value attributed to this gift
	 */
	public function get_value() {
		$value = $this->get_post_meta( self::$meta_keys['value'] );
		return $value;
	}

	/**
	 * Set a new value
	 *
	 * @param int|float The new value
	 * @return int|float The dollar amount of the value attributed to this gift
	 */
	public function set_value( $value ) {
		$this->save_post_meta( array(
				self::$meta_keys['value'] => $value
			) );
		return $value;
	}

	/**
	 *
	 *
	 * @return string The email address of the gift's recipient
	 */
	public function get_recipient() {
		$value = $this->get_post_meta( self::$meta_keys['recipient'] );
		return $value;
	}

	/**
	 * Set a new recipient
	 *
	 * @param string  The new recipient
	 * @return string The new recipient
	 */
	public function set_recipient( $recipient ) {
		$this->save_post_meta( array(
				self::$meta_keys['recipient'] => $recipient
			) );
		return $recipient;
	}

	/**
	 *
	 *
	 * @return string The message for the gift
	 */
	public function get_message() {
		$value = $this->get_post_meta( self::$meta_keys['message'] );
		return $value;
	}

	/**
	 * Set a new message
	 *
	 * @param string  The new message
	 * @return string The new message
	 */
	public function set_message( $message ) {
		$this->save_post_meta( array(
				self::$meta_keys['message'] => $message
			) );
		return $message;
	}
}
