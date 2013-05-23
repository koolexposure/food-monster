<?php

/**
 * Offsite payments controller, extends all payment processors
 *
 * @package GBS
 * @subpackage Payment Processing
 */
abstract class Group_Buying_Offsite_Processors extends Group_Buying_Payment_Processors {
	const CHECKOUT_ACTION = 'gb_offsite_payments';

	/**
	 * Subclasses should override this to identify if they've returned from
	 * offsite processing
	 *
	 * @static
	 * @return bool
	 */
	public static function returned_from_offsite() {
		return FALSE;
	}


	protected function __construct() {
		parent::__construct();
		add_filter( 'gb_checkout_panes_'.Group_Buying_Checkouts::PAYMENT_PAGE, array( $this, 'payment_pane' ), 10, 2 );
		add_action( 'gb_checkout_action_'.Group_Buying_Checkouts::PAYMENT_PAGE, array( $this, 'processed_payment_page' ), 20, 1 );
		add_filter( 'gb_checkout_panes_'.Group_Buying_Checkouts::REVIEW_PAGE, array( $this, 'review_pane' ), 10, 2 );
		add_action( 'gb_checkout_action_'.Group_Buying_Checkouts::REVIEW_PAGE, array( $this, 'process_review_page' ), 20, 1 );
		add_filter( 'gb_checkout_payment_controls', array( $this, 'payment_controls' ), 10, 2 );
		//add_filter('gb_checkout_pages', array($this, 'remove_payment_page'));
		Group_Buying_Offsite_Processor_Handler::init();
	}

	/**
	 * The payment page is unnecessary (or, rather, it's offsite)
	 *
	 * @param array   $pages
	 * @return array
	 */
	public function remove_payment_page( $pages ) {
		if ( $this->offsite_payment_complete() ) {
			unset( $pages[Group_Buying_Checkouts::PAYMENT_PAGE] );
		}
		return $pages;
	}

	/**
	 * Determine whether the user has finished with the offsite portion
	 * of the payment. If so, we can skip the payment page
	 *
	 * @return bool
	 */
	public function offsite_payment_complete() {
		return TRUE;
	}

	public function payment_pane( $panes, Group_Buying_Checkouts $checkout ) {
		$fields = $this->payment_fields( $checkout );
		if ( !empty( $fields ) ) {
			$panes['payment'] = array(
				'weight' => 100,
				'body' => self::load_view_to_string( 'checkout/credit_card', array( 'fields' => $fields ) ),
			);
		}
		return $panes;
	}

	protected function payment_fields( $checkout = NULL ) {
		$fields = array();
		/*/
		$fields = array(
			'payment_method' => array(
				'type' => 'radios',
				'weight' => 1,
				'label' => self::__('Payment Method'),
				'required' => TRUE,
				'options' => array(
					$this->get_payment_method() => $this->get_payment_method(),
				),
				'default' => $this->get_payment_method(),
			),
		);
		/**/
		$fields = apply_filters( 'gb_offsite_payment_fields', $fields, __CLASS__, $checkout );
		$fields = apply_filters( 'gb_payment_fields', $fields, __CLASS__, $checkout );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}

	public function get_payment_request_total( Group_Buying_Checkouts $checkout, $purchase = NULL ) {
		if ( $purchase && is_a( $purchase, 'Group_Buying_Purchase' ) ) {
			$total = $purchase->get_total();
		} else {
			$cart = $checkout->get_cart();
			$total = $cart->get_total();
		}
		return apply_filters( 'gb_offsite_purchase_payment_request_total', $total, $checkout );
	}

	public function payment_controls( $controls, Group_Buying_Checkouts $checkout ) {
		if ( isset( $controls['review'] ) ) {
			$controls['review'] = str_replace( self::__( 'Review' ), self::__( 'Continue' ), $controls['review'] );
		}
		return $controls;
	}

	public function processed_payment_page( Group_Buying_Checkouts $checkout ) {
		if ( $checkout->is_page_complete( Group_Buying_Checkouts::PAYMENT_PAGE ) ) { // Make sure to send offsite when it's okay to do so.
			do_action( 'gb_send_offsite_for_payment', $checkout );
		}
	}

	public function process_review_page( Group_Buying_Checkouts $checkout ) {
		do_action( 'gb_send_offsite_for_payment_after_review', $checkout );
	}

	/**
	 * Display the review pane
	 *
	 * @param array   $panes
	 * @param Group_Buying_Checkouts $checkout
	 * @return array
	 */
	public function review_pane( $panes, Group_Buying_Checkouts $checkout ) {
		$fields = array(
			'method' => array(
				'label' => self::__( 'Payment Method' ),
				'value' => $this->get_payment_method(),
				'weight' => 1,
			)
		);
		$fields = apply_filters( 'gb_payment_review_fields', $fields, get_class( $this ), $checkout );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		$panes['payment'] = array(
			'weight' => 100,
			'body' => self::load_view_to_string( 'checkout/credit-card-review', array( 'fields' => $fields ) ),
		);
		return $panes;
	}
}

class Group_Buying_Offsite_Processor_Handler extends Group_Buying_Controller {

	const PAYMENT_HANDLER_OPTION = 'gb_payment_handler';
	const PAYMENT_HANDLER_QUERY_ARG = 'payment_handler';
	private static $payment_handler_path = 'payment_handler';
	private static $use_ssl = FALSE;
	private static $payment_handler = NULL;

	public static function init() {
		self::$use_ssl = get_option( Group_Buying_Checkouts::USE_SSL_OPTION, FALSE );
		self::$payment_handler_path = get_option( self::PAYMENT_HANDLER_OPTION, self::$payment_handler_path );
		self::register_path_callback( self::$payment_handler_path, array( get_class(), 'on_handler_page' ), self::PAYMENT_HANDLER_QUERY_ARG );
	}

	/**
	 *
	 *
	 * @static
	 * @return string The URL to the checkout page
	 */
	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url( self::$payment_handler_path, self::$use_ssl?'https':NULL ) );
		} else {
			return add_query_arg( self::PAYMENT_HANDLER_QUERY_ARG, 1, home_url( '', self::$use_ssl?'https':NULL ) );
		}
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	private function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}

	private function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}

	public static function get_instance() {
		if ( !( self::$payment_handler && is_a( self::$payment_handler, __CLASS__ ) ) ) {
			self::$payment_handler = new self();
		}
		return self::$payment_handler;
	}

	/**
	 * We're on the checkout page. Time to auto-instantiate!
	 *
	 * @static
	 * @return void
	 */
	public static function on_handler_page() {
		self::do_not_cache();
		self::get_instance(); // make sure the class is instantiated
	}

	protected function __construct() {
		$this->handle_action();
	}

	private function handle_action() {
		if ( isset( $_GET[self::PAYMENT_HANDLER_QUERY_ARG] ) ) {
			do_action( 'gb_payment_handler_'.strtolower( $_GET[self::PAYMENT_HANDLER_QUERY_ARG] ), $this );
		}
		do_action( 'gb_payment_handler', $_POST, $this );
	}
}
