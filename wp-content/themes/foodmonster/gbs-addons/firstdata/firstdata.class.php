<?php

/**
 * Paypal offsite payment processor.
 *
 * @package GBS
 * @subpackage Payment Processing_Processor
 */
class Group_Buying_First_Data extends Group_Buying_Offsite_Processors {
	const API_ENDPOINT_SANDBOX = '../pgw/connect.php';
	const API_ENDPOINT_LIVE = '../pgw/connect.php';
	const API_REDIRECT_SANDBOX = '../pgw/connect.php';
	const API_REDIRECT_LIVE = '../pgw/connect.php';
	const MODE_TEST = 'sandbox';
	const MODE_LIVE = 'live';
	const API_USERNAME_OPTION = 'gb_paypal_username';
	const API_SIGNATURE_OPTION = 'gb_paypal_signature';
	const API_PASSWORD_OPTION = 'gb_paypal_password';
	const API_MODE_OPTION = 'gb_paypal_mode';
	const CANCEL_URL_OPTION = 'gb_paypal_cancel_url';
	const RETURN_URL_OPTION = 'gb_paypal_return_url';
	const CURRENCY_CODE_OPTION = 'gb_paypal_currency';
	const PAYMENT_METHOD = 'First Data';
	const TOKEN_KEY = 'gb_token_key'; // Combine with $blog_id to get the actual meta key
	const PAYER_ID = 'gb_payer_id'; // Combine with $blog_id to get the actual meta key
	
	const STORE_NAME = "1909379632";
	const SHARED_SECRET = "30393731383836393938303437333732313231363736303731383030333131303336313331323837313133303630313438383539383836373732393137383030";
	const TIME_ZONE ="EST";
	
	const LOGS = 'gb_offsite_logs';

	protected static $instance;
	private static $token;
	protected static $api_mode = self::MODE_TEST;
	private static $api_username;
	private static $api_password;
	private static $api_signature;
	private static $responseFailURL = '';
	private static $responseSuccessURL = '';
	private static $currency_code = 'USD';
	private static $version = '64';
	private static $mode = 'payonly';
	private static $storename = '1909379632';
	private static $sharedsecret = '30393731383836393938303437333732313231363736303731383030333131303336313331323837313133303630313438383539383836373732393137383030';
	private static $paymentMethod = '';
	private static $timezone = 'EST';
	private static $trxOrigin = 'ECI';
	private static $txntype = 'sale';
	private static $authenticateTransaction = false;
	private static $cancel_url = '';
	private static $return_url = '';
	

	public static function get_instance() {
		if ( !( isset( self::$instance ) && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function get_api_url() {
		if ( self::$api_mode == self::MODE_LIVE ) {
			return self::API_ENDPOINT_LIVE;
		} else {
			return self::API_ENDPOINT_SANDBOX;
		}
	}

	private function get_redirect_url() {
		if ( self::$api_mode == self::MODE_LIVE ) {
			return self::API_REDIRECT_LIVE;
		} else {
			return self::API_REDIRECT_SANDBOX;
		}
	}

	public function get_payment_method() {
		return self::PAYMENT_METHOD;
	}

	public static function returned_from_offsite() {
		return ( isset( $_GET['token'] ) && isset( $_GET['PayerID'] ) && isset( $_GET['approval_code'] ) && isset( $_GET['status'] )  );
	}

	protected function __construct() {
		parent::__construct();
		self::$api_username = get_option( self::API_USERNAME_OPTION );
		self::$api_password = get_option( self::API_PASSWORD_OPTION );
		self::$api_signature = get_option( self::API_SIGNATURE_OPTION );
		self::$api_mode = get_option( self::API_MODE_OPTION, self::MODE_TEST );
		self::$currency_code = get_option( self::CURRENCY_CODE_OPTION, 'USD' );
		self::$cancel_url = 'http://localhost:8888/foodmonster/cart/';
		self::$return_url = Group_Buying_Checkouts::get_url();

		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );
		add_action( 'purchase_completed', array( $this, 'capture_purchase' ), 10, 1 );
		add_action( self::CRON_HOOK, array( $this, 'capture_pending_payments' ) );

		add_filter( 'gb_checkout_payment_controls', array( $this, 'payment_controls' ), 20, 2 );

		add_action( 'gb_send_offsite_for_payment', array( $this, 'send_offsite' ), 10, 1 );
		add_action( 'gb_load_cart', array( $this, 'back_from_paypal' ), 10, 0 );
	}

	public static function register() {
		self::add_payment_processor( __CLASS__, self::__( 'First Data Payments Standard' ) );
	}

	public static function public_name() {
		return self::__( 'FirstData' );
	}

	public static function checkout_icon() {
		return '<img src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" title="Paypal Payments" id="paypal_icon"/>';
	}

	/**
	 * Instead of redirecting to the GBS checkout page,
	 * set up the Express Checkout transaction and redirect there
	 *
	 * @param Group_Buying_Carts $cart
	 * @return void
	 */
	public function send_offsite( Group_Buying_Checkouts $checkout ) {

		$cart = $checkout->get_cart();
		if ( $cart->get_total() < 0.01 ) { // for free deals.
			return;
		}


		if ( !isset( $_GET['approval_code'] ) && $_REQUEST['gb_checkout_action'] == Group_Buying_Checkouts::PAYMENT_PAGE ) {

			$post_data = $this->set_nvp_data( $checkout );
			if ( !$post_data ) {
				return; // paying for it some other way
			}
			
			

			if ( self::DEBUG ) {
				error_log( '----------Filtered post_data----------' );
				error_log( print_r( $post_data, TRUE ) );
			}

			$response = wp_remote_post( self::get_api_url(), array(
					'method' => 'POST',
					'body' => $post_data,
					'timeout' => apply_filters( 'http_request_timeout', 15 ),
					'sslverify' => false
				) );

			if ( self::DEBUG ) {
				error_log( '----------PayPal EC Approval Response----------' );
				error_log( print_r( $response, TRUE ) );
			}

			if ( is_wp_error( $response ) ) {
				return FALSE;
			}

			$response = wp_parse_args( wp_remote_retrieve_body( $response ) );

			if ( self::DEBUG ) {
				error_log( '----------PayPal EC Approval Response (Parsed)----------' );
				error_log( print_r( $response, TRUE ) );
			}

			$ack = strtoupper( $response['status'] );
			if ( $ack == 'Approved' ) {
				$_SESSION['approval_code'] = urldecode( $response['approval_code'] ); // needed?
				self::$token = urldecode( $response['approval_code'] ); // set var for redirect use
				self::redirect();
			} else {
				update_option( self::LOGS, $response );
				self::set_error_messages( $response['L_LONGMESSAGE0'] );
				wp_redirect( Group_Buying_Carts::get_url(), 303 );
				exit();
			}
		}
	}

	/**
	 * We're on the checkout page, just back from PayPal.
	 * Store the token and payer ID that PayPal gives us
	 *
	 * @return void
	 */
	public function back_from_paypal() {
		if ( self::returned_from_offsite() ) {
			self::set_token( urldecode( $_GET['approval_code'] ) );
			self::set_payerid( urldecode( $_GET['PayerID'] ) );
			// let the checkout know that this isn't a fresh start
			$_REQUEST['gb_checkout_action'] = 'back_from_paypal';
		} elseif ( !isset( $_REQUEST['gb_checkout_action'] ) ) {
			// this is a new checkout. clear the token so we don't give things away for free
			self::unset_token();
		}
	}

	/**
	 * Build the NVP data array for submitting the current checkout to PayPal as an Authorization request
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Purchase $purchase
	 * @return array
	 */
	private function set_nvp_data( Group_Buying_Checkouts $checkout ) {
		$cart = $checkout->get_cart();

		$user = get_userdata( get_current_user_id() );
		$filtered_total = $this->get_payment_request_total( $checkout );
		if ( $filtered_total < 0.01 ) {
			return array();
		}
		$nvpData = array();

		$nvpData['USER'] = self::$api_username;
		$nvpData['PWD'] = self::$api_password;
		$nvpData['SIGNATURE'] = self::$api_signature;
		$nvpData['VERSION'] = self::$version;

		$nvpData['responseFailURL'] = self::$cancel_url;
		$nvpData['responseSuccessURL'] = self::$return_url;

		$nvpData['mode'] = 'ECI';
		$nvpData['txntype'] = 'sale';
		$nvpData['authenticateTransaction'] = 'false';
		$nvpData['EMAIL'] = $user->user_email;
		$nvpData['LANDINGPAGE'] = 'Billing';
		$nvpData['SOLUTIONTYPE'] = 'Sole';

		$nvpData['PAYMENTREQUEST_0_AMT'] = gb_get_number_format( $filtered_total );
		$nvpData['PAYMENTREQUEST_0_CURRENCYCODE'] = self::get_currency_code();
		$nvpData['PAYMENTREQUEST_0_ITEMAMT'] = gb_get_number_format( $cart->get_subtotal() );
		$nvpData['PAYMENTREQUEST_0_SHIPPINGAMT'] = gb_get_number_format( $cart->get_shipping_total() );
		$nvpData['PAYMENTREQUEST_0_TAXAMT'] = gb_get_number_format( $cart->get_tax_total() );
		$nvpData['BUTTONSOURCE'] = self::PLUGIN_NAME;

		if ( isset( $checkout->cache['shipping'] ) ) {
			$nvpData['NOSHIPPING'] = 2;
			$nvpData['ADDROVERRIDE'] = 1;
			$nvpData['PAYMENTREQUEST_0_SHIPTONAME'] = $checkout->cache['shipping']['first_name'].' '.$checkout->cache['shipping']['last_name'];
			$nvpData['PAYMENTREQUEST_0_SHIPTOSTREET'] = $checkout->cache['shipping']['street'];
			$nvpData['PAYMENTREQUEST_0_SHIPTOCITY'] = $checkout->cache['shipping']['city'];
			$nvpData['PAYMENTREQUEST_0_SHIPTOSTATE'] = $checkout->cache['shipping']['zone'];
			$nvpData['PAYMENTREQUEST_0_SHIPTOZIP'] = $checkout->cache['shipping']['postal_code'];
			$nvpData['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = $checkout->cache['shipping']['country'];
		}

		$i = 0;
		$j = 0;
		if (
			$nvpData['PAYMENTREQUEST_0_ITEMAMT'] == gb_get_number_format( 0 ) ||
			( $filtered_total < $cart->get_total()
				&& ( $cart->get_subtotal() + $filtered_total - $cart->get_total() ) == 0
			)
		) {
			//handle orders that are free but have tax or shipping
			if ( $nvpData['PAYMENTREQUEST_0_SHIPPINGAMT'] != gb_get_number_format( 0 ) ) {
				$nvpData['PAYMENTREQUEST_0_ITEMAMT'] = $nvpData['PAYMENTREQUEST_0_SHIPPINGAMT'];
				$nvpData['PAYMENTREQUEST_0_SHIPPINGAMT'] = gb_get_number_format( 0 );
			} elseif ( $nvpData['PAYMENTREQUEST_0_TAXAMT'] != gb_get_number_format( 0 ) ) {
				$nvpData['PAYMENTREQUEST_0_ITEMAMT'] = $nvpData['PAYMENTREQUEST_0_TAXAMT'];
				$nvpData['PAYMENTREQUEST_0_TAXAMT'] = gb_get_number_format( 0 );
			}
		} else {
			// we can add individual item info if there's actually an item cost
			foreach ( $cart->get_items() as $key => $item ) {
				$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
				$nvpData['L_PAYMENTREQUEST_0_NAME'.$i] = html_entity_decode( strip_tags( $deal->get_title( $item['data'] ) ), ENT_QUOTES, 'UTF-8' );
				$nvpData['L_PAYMENTREQUEST_0_AMT'.$i] = gb_get_number_format( $deal->get_price( NULL, $item['data'] ) );
				$nvpData['L_PAYMENTREQUEST_0_NUMBER'.$i] = $item['deal_id'];
				$nvpData['L_PAYMENTREQUEST_0_QTY'.$i] = $item['quantity'];

				if ( !empty( $item['data']['recurring'] ) ) {
					$nvpData['L_BILLINGTYPE'.$j] = 'RecurringPayments';
					$nvpData['L_BILLINGAGREEMENTDESCRIPTION'.$j] = $deal->get_title( $item['data'] );
				}
				$i++;
			}
			if ( $filtered_total < $cart->get_total() ) {
				$nvpData['L_PAYMENTREQUEST_0_NAME'.$i] = self::__( 'Applied Credit' );
				$nvpData['L_PAYMENTREQUEST_0_AMT'.$i] = gb_get_number_format( $filtered_total - $cart->get_total() );
				$nvpData['L_PAYMENTREQUEST_0_QTY'.$i] = '1';
				$nvpData['PAYMENTREQUEST_0_ITEMAMT'] = gb_get_number_format( $cart->get_subtotal() + $filtered_total - $cart->get_total() );
				$i++;
			}
		}

		$nvpData = apply_filters( 'gb_paypal_ec_set_nvp_data', $nvpData );
		if ( self::DEBUG ) {
			error_log( '----------PayPal EC SetCheckout Data----------' );
			error_log( print_r( $nvpData, TRUE ) );
		}
		return apply_filters( 'gb_set_nvp_data', $nvpData, $checkout, $i );
	}

	public function redirect() {
		wp_redirect ( self::get_redirect_url() . self::$token );
		exit();
	}

	public static function set_token( $token ) {
		global $blog_id;
		update_user_meta( get_current_user_id(), $blog_id.'_'.self::TOKEN_KEY, $token );
	}

	public static function unset_token() {
		global $blog_id;
		delete_user_meta( get_current_user_id(), $blog_id.'_'.self::TOKEN_KEY );
	}

	public static function get_token() {
		global $blog_id;
		return get_user_meta( get_current_user_id(), $blog_id.'_'.self::TOKEN_KEY, TRUE );
	}

	public static function set_payerid( $get_payerid ) {
		global $blog_id;
		update_user_meta( get_current_user_id(), $blog_id.'_'.self::PAYER_ID, $get_payerid );
	}

	public static function get_payerid() {
		global $blog_id;
		return get_user_meta( get_current_user_id(), $blog_id.'_'.self::PAYER_ID, TRUE );
	}

	public function offsite_payment_complete() {
		if ( self::get_token() && self::get_payerid() ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Process a payment
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Purchase $purchase
	 * @return Group_Buying_Payment|bool FALSE if the payment failed, otherwise a Payment object
	 */
	public function process_payment( Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
		if ( $purchase->get_total( self::get_payment_method() ) < 0.01 ) {
			// Nothing to do here, another payment handler intercepted and took care of everything
			// See if we can get that payment and just return it
			$payments = Group_Buying_Payment::get_payments_for_purchase( $purchase->get_id() );
			foreach ( $payments as $payment_id ) {
				$payment = Group_Buying_Payment::get_instance( $payment_id );
				return $payment;
			}
		}

		$post_data = $this->process_nvp_data( $checkout, $purchase );

		if ( self::DEBUG ) {
			error_log( '----------PayPal EC Authorization Request ----------' );
			error_log( print_r( $post_data, TRUE ) );
		}

		$response = wp_remote_post( self::get_api_url(), array(
				'method' => 'POST',
				'body' => $post_data,
				'timeout' => apply_filters( 'http_request_timeout', 15 ),
				'sslverify' => false
			) );

		if ( self::DEBUG ) {
			error_log( '----------PayPal EC Authorization Response (Raw) ----------' );
			error_log( print_r( $response, TRUE ) );
		}

		if ( is_wp_error( $response ) ) {
			return FALSE;
		}
		if ( $response['response']['code'] != '200' ) {
			return FALSE;
		}

		$response = wp_parse_args( wp_remote_retrieve_body( $response ) );

		if ( self::DEBUG ) {
			error_log( '----------PayPal EC Authorization Response (Parsed) ----------' );
			error_log( print_r( $response, TRUE ) );
		}

		if ( strpos( $response['ACK'], 'Success' ) !== 0 ) {
			$this->set_error_messages( $response['L_LONGMESSAGE0'] );
			return FALSE;
		}
		if ( strpos( $response['ACK'], 'SuccessWithWarning' ) === 0 ) {
			$this->set_error_messages( $response['L_LONGMESSAGE0'] );
		}
		// create loop of deals for the payment post
		$deal_info = array();
		foreach ( $purchase->get_products() as $item ) {
			if ( isset( $item['payment_method'][self::get_payment_method()] ) ) {
				if ( !isset( $deal_info[$item['deal_id']] ) ) {
					$deal_info[$item['deal_id']] = array();
				}
				$deal_info[$item['deal_id']][] = $item;
			}
		}
		if ( isset( $checkout->cache['shipping'] ) ) {
			$shipping_address = array();
			$shipping_address['first_name'] = $checkout->cache['shipping']['first_name'];
			$shipping_address['last_name'] = $checkout->cache['shipping']['last_name'];
			$shipping_address['street'] = $checkout->cache['shipping']['street'];
			$shipping_address['city'] = $checkout->cache['shipping']['city'];
			$shipping_address['zone'] = $checkout->cache['shipping']['zone'];
			$shipping_address['postal_code'] = $checkout->cache['shipping']['postal_code'];
			$shipping_address['country'] = $checkout->cache['shipping']['country'];
		}
		// create new payment
		$payment_id = Group_Buying_Payment::new_payment( array(
				'payment_method' => self::get_payment_method(),
				'purchase' => $purchase->get_id(),
				'amount' => $response['PAYMENTINFO_0_AMT'],
				'data' => array(
					'api_response' => $response,
					'uncaptured_deals' => $deal_info
				),
				// 'transaction_id' => $response[], // TODO set the transaction ID
				'deals' => $deal_info,
				'shipping_address' => $shipping_address,
			), Group_Buying_Payment::STATUS_AUTHORIZED );
		if ( !$payment_id ) {
			return FALSE;
		}
		$payment = Group_Buying_Payment::get_instance( $payment_id );
		do_action( 'payment_authorized', $payment );

		$this->create_recurring_payment_profiles( $checkout, $purchase );
		self::unset_token();

		return $payment;
	}

	/**
	 * Build the NVP data array for submitting the current checkout to PayPal as an Authorization request
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Purchase $purchase
	 * @return array
	 */
	private function process_nvp_data( Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
		$cart = $checkout->get_cart();

		$user = get_userdata( get_current_user_id() );
		$filtered_total = $this->get_payment_request_total( $checkout );
		if ( $filtered_total < 0.01 ) {
			return array();
		}
		$nvpData = array();

		$nvpData['USER'] = self::$api_username;
		$nvpData['PWD'] = self::$api_password;
		$nvpData['SIGNATURE'] = self::$api_signature;
		$nvpData['VERSION'] = self::$version;

		$nvpData['TOKEN'] = self::get_token();
		$nvpData['PAYERID'] = self::get_payerid();

		$nvpData['METHOD'] = 'DoExpressCheckoutPayment';
		$nvpData['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Authorization';
		$nvpData['IPADDRESS'] = $_SERVER ['REMOTE_ADDR'];

		$nvpData['PAYMENTREQUEST_0_AMT'] = gb_get_number_format( $filtered_total );
		$nvpData['PAYMENTREQUEST_0_CURRENCYCODE'] = self::get_currency_code();
		$nvpData['PAYMENTREQUEST_0_ITEMAMT'] = gb_get_number_format( $cart->get_subtotal() );
		$nvpData['PAYMENTREQUEST_0_SHIPPINGAMT'] = gb_get_number_format( $cart->get_shipping_total() );
		$nvpData['PAYMENTREQUEST_0_TAXAMT'] = gb_get_number_format( $cart->get_tax_total() );
		$nvpData['PAYMENTREQUEST_0_INVNUM'] = $purchase->get_id();
		$nvpData['BUTTONSOURCE'] = self::PLUGIN_NAME;

		if ( isset( $checkout->cache['shipping'] ) ) {
			$nvpData['NOSHIPPING'] = 2;
			$nvpData['ADDROVERRIDE'] = 1;
			$nvpData['PAYMENTREQUEST_0_SHIPTONAME'] = $checkout->cache['shipping']['first_name'].' '.$checkout->cache['shipping']['last_name'];
			$nvpData['PAYMENTREQUEST_0_SHIPTOSTREET'] = $checkout->cache['shipping']['street'];
			$nvpData['PAYMENTREQUEST_0_SHIPTOCITY'] = $checkout->cache['shipping']['city'];
			$nvpData['PAYMENTREQUEST_0_SHIPTOSTATE'] = $checkout->cache['shipping']['zone'];
			$nvpData['PAYMENTREQUEST_0_SHIPTOZIP'] = $checkout->cache['shipping']['postal_code'];
			$nvpData['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = $checkout->cache['shipping']['country'];
		}

		$i = 0;
		if (
			$nvpData['PAYMENTREQUEST_0_ITEMAMT'] == gb_get_number_format( 0 ) ||
			( $filtered_total < $cart->get_total()
				&& ( $cart->get_subtotal() + $filtered_total - $cart->get_total() ) == 0
			)
		) {
			// handle free/credit purchases (paypal requires minimum 0.01 item amount)
			if ( $nvpData['PAYMENTREQUEST_0_SHIPPINGAMT'] != gb_get_number_format( 0 ) ) {
				$nvpData['PAYMENTREQUEST_0_ITEMAMT'] = $nvpData['PAYMENTREQUEST_0_SHIPPINGAMT'];
				$nvpData['PAYMENTREQUEST_0_SHIPPINGAMT'] = gb_get_number_format( 0 );
			} elseif ( $nvpData['PAYMENTREQUEST_0_TAXAMT'] != gb_get_number_format( 0 ) ) {
				$nvpData['PAYMENTREQUEST_0_ITEMAMT'] = $nvpData['PAYMENTREQUEST_0_TAXAMT'];
				$nvpData['PAYMENTREQUEST_0_TAXAMT'] = gb_get_number_format( 0 );
			}
		} else {
			foreach ( $cart->get_items() as $key => $item ) {
				$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
				$nvpData['L_PAYMENTREQUEST_0_NAME'.$i] = $deal->get_title( $item['data'] );
				$nvpData['L_PAYMENTREQUEST_0_AMT'.$i] = gb_get_number_format( $deal->get_price( NULL, $item['data'] ) );
				$nvpData['L_PAYMENTREQUEST_0_NUMBER'.$i] = $item['deal_id'];
				$nvpData['L_PAYMENTREQUEST_0_QTY'.$i] = $item['quantity'];
				$i++;
			}
			if ( $filtered_total < $cart->get_total() ) {
				$nvpData['L_PAYMENTREQUEST_0_NAME'.$i] = self::__( 'Applied Credit' );
				$nvpData['L_PAYMENTREQUEST_0_AMT'.$i] = gb_get_number_format( $filtered_total - $cart->get_total() );
				$nvpData['L_PAYMENTREQUEST_0_QTY'.$i] = '1';
				$nvpData['PAYMENTREQUEST_0_ITEMAMT'] = gb_get_number_format( $cart->get_subtotal() + $filtered_total - $cart->get_total() );
			}
		}

		$nvpData = apply_filters( 'gb_paypal_ec_nvp_data', $nvpData, $checkout, $i, $purchase );

		return $nvpData;
	}

	/**
	 * Capture a pre-authorized payment
	 *
	 * @param Group_Buying_Purchase $purchase
	 * @return void
	 */
	public function capture_purchase( Group_Buying_Purchase $purchase ) {
		$payments = Group_Buying_Payment::get_payments_for_purchase( $purchase->get_id() );
		foreach ( $payments as $payment_id ) {
			$payment = Group_Buying_Payment::get_instance( $payment_id );
			$this->capture_payment( $payment );
		}
	}

	/**
	 * Try to capture all pending payments
	 *
	 * @return void
	 */
	public function capture_pending_payments() {
		$payments = Group_Buying_Payment::get_pending_payments();
		foreach ( $payments as $payment_id ) {
			$payment = Group_Buying_Payment::get_instance( $payment_id );
			$this->capture_payment( $payment );
		}
	}

	public  function capture_payment( Group_Buying_Payment $payment ) {
		// is this the right payment processor? does the payment still need processing?
		if ( $payment->get_payment_method() == $this->get_payment_method() && $payment->get_status() != Group_Buying_Payment::STATUS_COMPLETE ) {
			$data = $payment->get_data();
			// Do we have a transaction ID to use for the capture?
			if ( isset( $data['api_response']['PAYMENTINFO_0_TRANSACTIONID'] ) && $data['api_response']['PAYMENTINFO_0_TRANSACTIONID'] ) {
				$transaction_id = $data['api_response']['PAYMENTINFO_0_TRANSACTIONID'];
				$items_to_capture = $this->items_to_capture( $payment );
				if ( $items_to_capture ) {
					$status = ( count( $items_to_capture ) < count( $data['uncaptured_deals'] ) )?'NotComplete':'Complete';
					$post_data = $this->capture_nvp_data( $transaction_id, $items_to_capture, $status );
					if ( self::DEBUG ) {
						error_log( '----------PayPal EC DoCapture Request----------' );
						error_log( print_r( $post_data, TRUE ) );
					}
					$response = wp_remote_post( $this->get_api_url(), array(
							'body' => $post_data,
							'timeout' => apply_filters( 'http_request_timeout', 15 ),
							'sslverify' => false
						) );
					if ( !is_wp_error( $response ) && $response['response']['code'] == '200' ) {
						$response = wp_parse_args( wp_remote_retrieve_body( $response ) );
						if ( self::DEBUG ) {
							error_log( '----------PayPal EC DoCapture Response----------' );
							error_log( print_r( $response, TRUE ) );
						}
						if ( strpos( $response['ACK'], 'Success' ) === 0 ) {
							foreach ( $items_to_capture as $deal_id => $amount ) {
								unset( $data['uncaptured_deals'][$deal_id] );
							}
							if ( !isset( $data['capture_response'] ) ) {
								$data['capture_response'] = array();
							}
							$data['capture_response'][] = $response;
							$payment->set_data( $data );
							do_action( 'payment_captured', $payment, array_keys( $items_to_capture ) );
							if ( $status == 'Complete' ) {
								$payment->set_status( Group_Buying_Payment::STATUS_COMPLETE );
								do_action( 'payment_complete', $payment );
							} else {
								$payment->set_status( Group_Buying_Payment::STATUS_PARTIAL );
							}
						} else {
							$this->set_error_messages( $response, FALSE );
						}
					}
				}
			}
		}
	}

	/**
	 * The the NVP data for submitting a DoCapture request
	 *
	 * @param string  $transaction_id
	 * @param array   $items
	 * @param string  $status
	 * @return array
	 */
	private function capture_nvp_data( $transaction_id, $items, $status = 'Complete' ) {
		$total = 0;
		foreach ( $items as $price ) {
			$total += $price;
		}
		$nvpData = array();

		$nvpData['USER'] = self::$api_username;
		$nvpData['PWD'] = self::$api_password;
		$nvpData['SIGNATURE'] = self::$api_signature;
		$nvpData['VERSION'] = self::$version;

		$nvpData['METHOD'] = 'DoCapture';
		$nvpData['AUTHORIZATIONID'] = $transaction_id;
		$nvpData['AMT'] = gb_get_number_format( $total );
		$nvpData['CURRENCYCODE'] = self::get_currency_code();
		$nvpData['COMPLETETYPE'] = $status;

		$nvpData = apply_filters( 'gb_paypal_ec_capture_nvp_data', $nvpData );

		//$nvpData = array_map('rawurlencode', $nvpData);
		return $nvpData;
	}

	private function get_currency_code() {
		return apply_filters( 'gb_paypal_ec_currency_code', self::$currency_code );
	}


	public function cart_controls( $controls ) {
		$controls['checkout'] = '<input type="submit" class="form-submit alignright checkout_next_step" value="Paypal" name="gb_cart_action-checkout" />';
		return $controls;
	}


	public function payment_controls( $controls, Group_Buying_Checkouts $checkout ) {
		if ( isset( $controls['review'] ) ) {
			$style = 'style="box-shadow: none;-moz-box-shadow: none;-webkit-box-shadow: none; display: block; width: 145px; height: 42px; background-color: transparent; background-image: url(https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif); background-position: 0 0; padding: 42px 0 0 0; border: none; cursor: pointer; text-indent: -9000px; margin-top: 12px;"';
			$controls['review'] = str_replace( 'value="'.self::__( 'Review' ).'"', $style . ' value="'.self::__( 'Paypal' ).'"', $controls['review'] );
		}
		return $controls;
	}


	/**
	 * Grabs error messages from a PayPal response and displays them to the user
	 *
	 * @param array   $response
	 * @param bool    $display
	 * @return void
	 */
	private function set_error_messages( $message, $display = TRUE ) {
		if ( $display ) {
			self::set_message( $message, self::MESSAGE_STATUS_ERROR );
		} else {
			error_log( "error message from paypal: " . print_r( $message, true ) );
		}
	}
}

$storename ="1909379632";
$sharedsecret="30393731383836393938303437333732313231363736303731383030333131303336313331323837313133303630313438383539383836373732393137383030";

$timezone="EST";

$b = time () - 14400; 
$datetime = date("Y:m:d-H:i:s",$b);

function getdatetime() {
  global $datetime;
  return $datetime;
}

function gettimezone() {
  global $timezone;
  return $timezone;
}

function getstore() {
  global $storename;
  return $storename;
}

function createhash($chargetotal) {
  global $storename, $sharedsecret;
  $str = $storename . getdatetime() . $chargetotal . $sharedsecret;
  for ($i=0;$i<strlen($str);$i++) {
    $hexstr .= dechex(ord($str[$i]));
  }
  return hash('sha256', $hexstr);
}


Group_Buying_First_Data::register();