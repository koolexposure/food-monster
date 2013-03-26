<?php

/**
 * GBS Payment Model
 * 
 * @package GBS
 * @subpackage Payment
 */
class Group_Buying_Payment extends Group_Buying_Post_Type {
	const POST_TYPE = 'gb_payment';
	const REWRITE_SLUG = 'payments';
	const STATUS_PENDING = 'pending'; // payment has been created for later authorization
	const STATUS_AUTHORIZED = 'authorized'; // payment has been authorized, but not yet captured
	const STATUS_COMPLETE = 'publish'; // payment has been authorized and fully captured
	const STATUS_PARTIAL = 'partial'; // payment has been authorized and partially captured
	const STATUS_VOID = 'void'; // payment has been voided
	const STATUS_REFUND = 'refunded'; // payment has been voided
	const STATUS_RECURRING = 'recurring'; // a recurring payment has been created and is ongoing
	const STATUS_CANCELLED = 'cancelled'; // a recurring payment has been cancelled
	private static $instances = array();

	protected static $meta_keys = array(
		'amount' => '_amount', // int|float
		'data' => '_payment_data', // array - Misc. data saved by the payment processor
		'deals' => '_payment_deals', // array - Info about which deals this pays for, and how much of each
		'payment_method' => '_payment_method', // string
		'purchase_id' => '_purchase_id', // int
		'shipping_address' => '_shipping_address', // array - Address
		'source' => '_source', // int|float Another tracking method, used for affiliate.
		'transaction_id' => '_trans_id', // int for the payment gateway's transaction id.
		'tracking' => '_tracking', // array - Misc info for later tracking.
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.

	public static function init() {
		$post_type_args = array(
			'public' => FALSE,
			'show_ui' => FALSE,
			'show_in_menu' => 'group-buying', // in case we want a ui
			'rewrite' => FALSE,
			'has_archive' => FALSE,
			'supports' => array( 'title' ),
		);
		self::register_post_type( self::POST_TYPE, 'Payment', 'Payments', $post_type_args );
		self::register_post_statuses();
	}

	private static function register_post_statuses() {
		$statuses = array(
			self::STATUS_AUTHORIZED => self::__('Authorized'),
			self::STATUS_CANCELLED => self::__('Cancelled'),
			self::STATUS_PARTIAL => self::__('Partially Captured'),
			self::STATUS_VOID => self::__('Void'),
			self::STATUS_REFUND => self::__('Refunded'),
			self::STATUS_RECURRING => self::__('Recurring'),
		);
		foreach ( $statuses as $status => $label ) {
			register_post_status( $status, array(
				'label' => $label,
				'exclude_from_search' => FALSE,
			));
		}
	}

	public function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Group_Buying_Payment
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

	public static function new_payment( $args, $status = self::STATUS_COMPLETE ) {
		$default = array(
			'post_title' => self::__( 'Payment' ),
			'post_status' => $status,
			'post_type' => self::POST_TYPE,
		);
		$id = wp_insert_post( $default );
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		$payment = self::get_instance( $id );
		$payment->set_title( sprintf( self::__( 'Payment #%d' ), $id ) );
		if ( isset( $args['purchase'] ) && is_numeric( $args['purchase'] ) ) {
			$payment->set_purchase( (int)$args['purchase'] );
		}
		if ( isset( $args['payment_method'] ) ) {
			$payment->set_payment_method( $args['payment_method'] );
		}
		if ( isset( $args['amount'] ) ) {
			$payment->set_amount( (int)$args['amount'] );
		}
		if ( isset( $args['data'] ) ) {
			$payment->set_data( $args['data'] );
		}
		if ( isset( $args['deals'] ) ) {
			$payment->set_deals( $args['deals'] );
		}
		if ( isset( $args['transaction_id'] ) ) {
			$payment->set_transaction_id( $args['transaction_id'] );
		}
		if ( isset( $args['shipping_address'] ) ) {
			$payment->set_shipping_address( $args['shipping_address'] );
		}
		do_action( 'gb_new_payment', $payment, $args );
		return $id;
	}

	/**
	 * Find all Payments associated with a specific purchase
	 *
	 * @static
	 * @param int     $purchase_id ID of the purchase to search by
	 * @return array List of IDs of Payments associated with the given purchase
	 */
	public static function get_payments_for_purchase( $purchase_id ) {
		$payment_ids = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['purchase_id'] => $purchase_id ) );
		return $payment_ids;
	}

	public static function get_pending_payments( $method = NULL ) {
		$args = array(
			'post_type' => self::POST_TYPE,
			'post_status' => array( self::STATUS_PARTIAL, self::STATUS_AUTHORIZED ),
			'posts_per_page' => -1,
			'fields' => 'ids',
			'gb_bypass_filter' => TRUE
		);
		if ( $method ) {
			$args['meta_query'] = array(
				array(
					'key' => self::$meta_keys['payment_method'],
					'value' => $method
				),
			);
		}
		$posts = get_posts($args);
		return $posts;
	}

	public function get_status() {
		return $this->post->post_status;
	}

	public function set_status( $status ) {
		$this->post->post_status = $status;
		$this->save_post();
	}

	public function set_purchase( $purchase_id ) {
		$this->save_post_meta( array(
				self::$meta_keys['purchase_id'] => $purchase_id,
			) );
	}

	public function get_purchase() {
		return $this->get_post_meta( self::$meta_keys['purchase_id'] );
	}

	public function set_payment_method( $method ) {
		$this->save_post_meta( array(
				self::$meta_keys['payment_method'] => $method,
			) );
	}

	public function get_payment_method() {
		return $this->get_post_meta( self::$meta_keys['payment_method'] );
	}

	public function set_amount( $amount ) {
		$this->save_post_meta( array(
				self::$meta_keys['amount'] => $amount,
			) );
	}

	public function get_amount() {
		return $this->get_post_meta( self::$meta_keys['amount'] );
	}

	public function set_deals( $deals ) {
		if ( !is_array( $deals ) ) {
			$deals = array( $deals );
		}
		$this->save_post_meta( array(
				self::$meta_keys['deals'] => $deals,
			) );
	}

	public function get_deals() {
		return $this->get_post_meta( self::$meta_keys['deals'] );
	}

	public function set_data( $data ) {
		if ( !is_array( $data ) ) {
			$data = array( $data );
		}
		$this->save_post_meta( array(
				self::$meta_keys['data'] => $data,
			) );
	}

	public function get_data() {
		return $this->get_post_meta( self::$meta_keys['data'] );
	}

	public function set_source( $source ) {
		$this->save_post_meta( array(
				self::$meta_keys['source'] => $source,
			) );
	}

	public function get_source() {
		return $this->get_post_meta( self::$meta_keys['source'] );
	}

	public function set_transaction_id( $trans_id ) {
		$this->save_post_meta( array(
				self::$meta_keys['transaction_id'] => $trans_id,
			) );
	}

	public function get_transaction_id() {
		return $this->get_post_meta( self::$meta_keys['transaction_id'] );
	}

	public function set_shipping_address( $shipping_address ) {
		$this->save_post_meta( array(
				self::$meta_keys['shipping_address'] => $shipping_address,
			) );
	}

	public function get_shipping_address() {
		return $this->get_post_meta( self::$meta_keys['shipping_address'] );
	}

	public function set_tracking( $tracking ) {
		if ( !is_array( $tracking ) ) {
			$tracking = array( $tracking );
		}
		$this->save_post_meta( array(
				self::$meta_keys['tracking'] => $tracking,
			) );
	}

	public function get_tracking() {
		return $this->get_post_meta( self::$meta_keys['tracking'] );
	}

	public function get_account() {
		$purchase_id = $this->get_purchase();
		if ( !$purchase_id ) {
			return NULL;
		}
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		if ( !is_a( $purchase, 'Group_Buying_Purchase' ) ) {
			return NULL;
		}
		$user_id = $purchase->get_original_user();
		if ( !$user_id ) {
			return NULL;
		}
		$account = Group_Buying_Account::get_instance( $user_id );
		return $account;
	}

	public function is_recurring() {
		if ( in_array( $this->get_status(), array( self::STATUS_RECURRING, self::STATUS_CANCELLED ) ) ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Check if a recurring payment is still active and in good standing
	 *
	 * @param bool    $refresh Whether to re-verify with the payment processor
	 * @return bool
	 */
	public function is_active( $refresh = FALSE ) {
		if ( !$this->is_recurring() ) {
			return FALSE; // non-recurring payments are never active
		}
		if ( $this->get_status() == self::STATUS_CANCELLED ) {
			return FALSE;
		}
		if ( $refresh ) {
			$payment_processor = Group_Buying_Payment_Processors::get_payment_processor();
			$payment_processor->verify_recurring_payment( $this );
		}
		if ( $this->get_status() == self::STATUS_RECURRING ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Cancel a recurring payment
	 *
	 * @return void
	 */
	public function cancel() {
		if ( $this->get_status() == self::STATUS_RECURRING ) {
			do_action( 'gb_cancelling_recurring_payment', $this );

			// cancel the actual payment
			$payment_processor = Group_Buying_Payment_Processors::get_payment_processor();
			$payment_processor->cancel_recurring_payment( $this );

			$this->set_status( self::STATUS_CANCELLED );

			// notify plugins that this has been cancelled
			$purchase_id = $this->get_purchase();
			do_action( 'gb_recurring_payment_cancelled', $this, $purchase_id );
		}
	}
}
