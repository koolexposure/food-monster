<?php
/**
 * Paypal Adaptive Payments offsite payment processor.
 * 
 * @package GBS
 * @subpackage Payment Processing_Processor
 */
class Group_Buying_Paypal_AP extends Group_Buying_Offsite_Processors {
	const API_ENDPOINT_SANDBOX = 'https://svcs.sandbox.paypal.com/AdaptivePayments';
	const API_ENDPOINT_LIVE = 'https://svcs.paypal.com/AdaptivePayments';
	const API_REDIRECT_SANDBOX = 'https://www.sandbox.paypal.com/webscr?';
	const API_REDIRECT_LIVE = 'https://www.paypal.com/webscr?';
	const MODE_TEST = 'sandbox';
	const MODE_LIVE = 'live';
	const API_USERNAME_OPTION = 'gb_paypal_ap_username';
	const API_SIGNATURE_OPTION = 'gb_paypal_ap_signature';
	const API_PASSWORD_OPTION = 'gb_paypal_ap_password';
	const APP_ID_OPTION = 'gb_paypal_ap_id';
	const API_MODE_OPTION = 'gb_paypal_ap_mode';
	const CANCEL_URL_OPTION = 'gb_paypal_cancel_url';
	const RETURN_URL_OPTION = 'gb_paypal_return_url';
	const CURRENCY_CODE_OPTION = 'gb_paypal_ap_currency';
	const PAYMENT_METHOD = 'PayPal AP';
	const USE_PROXY = FALSE;
	const PROXY_HOST = '';
	const PROXY_PORT = '';
	const DEBUG = FALSE;
	protected static $instance;
	private static $ap_key;
	protected static  $api_mode = self::MODE_TEST;
	private $cancel_url = '';
	private $return_url = '';
	private $app_id = '';
	private $currency_code = 'USD';

	private static $meta_keys = array(
		'primary' => '_adaptive_primary', // string
		'secondary' => '_adaptive_secondary', // string
		'primary_share' => '_adaptive_primary_share', // string
		'secondary_share' => '_adaptive_primary_share', // string
	);

	public static function get_instance() {
		if ( !( isset( self::$instance ) && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function get_api_url() {
		if ( $this->api_mode == self::MODE_LIVE ) {
			return self::API_ENDPOINT_LIVE;
		} else {
			return self::API_ENDPOINT_SANDBOX;
		}
	}

	private function get_redirect_url() {
		if ( $this->api_mode == self::MODE_LIVE ) {
			return self::API_REDIRECT_LIVE;
		} else {
			return self::API_REDIRECT_SANDBOX;
		}
	}

	public function get_payment_method() {
		return self::PAYMENT_METHOD;
	}

	protected function __construct() {
		parent::__construct();
		$this->api_username = get_option( self::API_USERNAME_OPTION );
		$this->api_password = get_option( self::API_PASSWORD_OPTION );
		$this->api_signature = get_option( self::API_SIGNATURE_OPTION );
		$this->app_id = get_option( self::APP_ID_OPTION, 'APP-80W284485P519543T' );
		$this->api_mode = get_option( self::API_MODE_OPTION, self::MODE_TEST );
		$this->currency_code = get_option( self::CURRENCY_CODE_OPTION, 'USD' );
		$this->cancel_url = get_option( self::CANCEL_URL_OPTION, home_url( '/account/' ) ); // TODO, get setting no hardcodes
		$this->return_url = get_option( self::RETURN_URL_OPTION, add_query_arg( array( 'gb_checkout_action'=>'confirmation' ), home_url( '/checkout/' ) ) ); // TODO, get setting no hardcodes

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );
		add_action( 'purchase_completed', array( $this, 'capture_purchase' ), 10, 1 );
		add_action( 'gb_cron', array( $this, 'capture_pending_payments' ) );

		add_filter( 'group_buying_template_checkout/review-controls.php', array( $this, 'review_controls' ), 10 );

		add_action( 'gb_payment_handler', array( $this, 'handle_ipn' ), 10, 1 );

		// remove the notification when a purchase is completed. Instead send the notification
		remove_action( 'purchase_completed', array( 'Group_Buying_Notifications', 'purchase_notification' ), 10 );

	}

	public static function register() {
		self::add_payment_processor( __CLASS__, self::__( 'PayPal Adaptive Payments (beta)' ) );
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

		$user = get_userdata( $purchase->get_user() );
		// Create the preauth nvp
		$nvpData = array();
		$nvpData['requestEnvelope.errorLanguage'] = 'en_US';
		$nvpData['requestEnvelope.detailLevel'] = 'ReturnAll';
		$nvpData['endingDate'] = date( 'c', time()+31535400 ); // TODO (lp) Option
		$nvpData['startingDate'] = date( 'c' );
		$nvpData['maxTotalAmountOfAllPayments'] = gb_get_number_format( $purchase->get_total( self::get_payment_method() ) );
		$nvpData['currencyCode'] = self::get_currency_code();
		$nvpData['cancelUrl'] = $this->cancel_url;
		$nvpData['returnUrl'] = $this->return_url;
		$nvpData['ipnNotificationUrl'] = Group_Buying_Offsite_Processor_Handler::get_url();
		$nvpData['clientDetails'] = $purchase->get_ID();
		//$nvpData['memo'] = rtrim ( $line_items, ", " ); // TODO (lp) Option

		// Send for approval.
		$response = self::remote_post( 'Preapproval', $nvpData );

		$ack = strtoupper( $response['responseEnvelope_ack'] );
		if ( $ack == 'SUCCESS' ) {
			// Set the auth_key for later reference
			$purchase->set_auth_key( urldecode( $response['preapprovalKey'] ) );

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
					'amount' => $ipn['max_total_amount_of_all_payments'],
					'data' => array(
						'api_response' => $ipn,
						'uncaptured_deals' => $deal_info
					),
					'deals' => $deal_info,
					'shipping_address' => $shipping_address,
				), Group_Buying_Payment::STATUS_PENDING );
			if ( !$payment_id ) {
				return FALSE;
			}

			// Mark purchase as unsettled
			$purchase->set_unsettled_status();
			// Send offsite after checkout is complete but before the confirmation page
			add_action( 'checkout_completed', array( $this, 'redirect' ), 10, 3 );

			// send data back to complete_checkout
			$payment = Group_Buying_Payment::get_instance( $payment_id );
			do_action( 'payment_pending', $payment );
			return $payment;

		} else {
			$this->set_error_messages( $response['error(0).message'] );
			return FALSE;
		}
	}

	public function redirect( Group_Buying_Checkouts $checkout, Group_Buying_Payment $payment, Group_Buying_Purchase $purchase ) {
		$ap_key = $purchase->get_auth_key( $response['preapprovalKey'] ); // Already url decoded
		$cmd = "cmd=_ap-preapproval&preapprovalkey=" . $ap_key;
		wp_redirect ( self::get_redirect_url() . $cmd );
		exit();
	}

	/**
	 * Handle a received IPN.
	 *
	 * @param array   $data_array Array containing the data received from PayPal.
	 * @return
	 * If VERIFIED: true
	 * If UNVERIFIED: false
	 */
	public static function handle_ipn( $ipn ) {

		do_action( 'payment_handle_ipn', $ipn );

		if ( self::validate_ipn( $ipn ) ) {
			$preapproval_key = $ipn['preapproval_key'];
			if ( empty( $preapproval_key ) )
				return FALSE;

			$purchase_id = Group_Buying_Purchase::get_purchase_by_key( $preapproval_key );
			$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
			// Mark as pending so the function can proceed
			$purchase->set_pending();
			// Mark payments as authorized.
			$payments = Group_Buying_Payment::get_payments_for_purchase( $purchase_id );
			foreach ( $payments as $payment_id ) {
				$payment = Group_Buying_Payment::get_instance( $payment_id );
				$data = $payment->get_data();
				$data['api_response'] = $ipn;
				$payment->set_data( $data );
				$payment->set_status( Group_Buying_Payment::STATUS_AUTHORIZED );
				do_action( 'payment_authorized', $payment );
				Group_Buying_Notifications::purchase_notification( $purchase );
			}
			// Mark as complete, run purchase_completed
			$purchase->complete();
			// todo send message instead of 404
		}
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

	public function capture_payment( Group_Buying_Payment $payment ) {

		// is this the right payment processor? does the payment still need processing?
		if ( $payment->get_payment_method() == self::get_payment_method() && $payment->get_status() != Group_Buying_Payment::STATUS_COMPLETE ) {
			$data = $payment->get_data();
			//wp_delete_post($payment->get_ID(),TRUE);
			// Do we have a transaction ID to use for the capture?
			if ( isset( $data['api_response']['preapproval_key'] ) && $data['api_response']['preapproval_key'] ) {

				// items we need to capture
				$items_to_capture = $this->items_to_capture( $payment );
				
				if ( $items_to_capture ) {

					// if not set create an array
					if ( !isset( $data['capture_response'] ) ) {
						$data['capture_response'] = array();
					}

					// Get Quantities
					$item_quantities = array();
					$purchase = Group_Buying_Purchase::get_instance( $payment->get_purchase() );
					foreach ( $purchase->get_products() as $item ) {
						$item_quantities[$item['deal_id']] += $item['quantity'];
					}

					$payment_captured = FALSE;
					foreach ( $items_to_capture as $deal_id => $amount ) {
						// capture the payment individually since each capture depends on deal meta
						$response = self::call_pay( $payment, $deal_id, $amount, $item_quantities[$deal_id] );
						if ( self::DEBUG ) {
							error_log( '----------PayPal AP Capture Cal Pay ----------' );
							error_log( "response: " . print_r( $response, true ) );
						}

						// check if response is returns a success response
						$ack = strtoupper( $response['responseEnvelope_ack'] );
						if ( $ack == 'SUCCESS' ) {
							// make sure the payment status is completed
							$paymentExecStatus = strtoupper( $response["paymentExecStatus"] );
							if ( $paymentExecStatus == 'COMPLETED' ) {
								$payment_captured = TRUE;
								unset( $data['uncaptured_deals'][$deal_id] );
							}
						}
						// set new response
						$data['capture_response'][] = $response;
					}
					if ( $payment_captured ) {
						$payment->set_data( $data );
						do_action( 'payment_captured', $payment, array_keys( $items_to_capture ) );
					}

					// Set the status
					if ( count( $data['uncaptured_deals'] ) < 1 ) {
						$payment->set_status( Group_Buying_Payment::STATUS_COMPLETE );
						do_action( 'payment_complete', $payment );
					} else {
						$payment->set_status( Group_Buying_Payment::STATUS_PARTIAL );
					}
				}
			}
		}
	}

	private function remote_post( $methodName = "Preapproval", $nvpData ) {
		$post_string = self::make_nvp( $nvpData );
		$response = wp_remote_post( $this->get_api_url().'/'.$methodName, array(
				'method' => 'POST',
				'headers' => array(
					'X-PAYPAL-REQUEST-DATA-FORMAT' => 'NV',
					'X-PAYPAL-RESPONSE-DATA-FORMAT' => 'NV',
					'X-PAYPAL-SECURITY-USERID' => $this->api_username,
					'X-PAYPAL-SECURITY-PASSWORD' => $this->api_password,
					'X-PAYPAL-SECURITY-SIGNATURE' => $this->api_signature,
					'X-PAYPAL-SERVICE-VERSION' => '1.3.0',
					'X-PAYPAL-APPLICATION-ID' => $this->app_id
				),
				'body' => $post_string,
				'timeout' => apply_filters( 'http_request_timeout', 15 ),
				'sslverify' => false
			) );

		if ( self::DEBUG ) {
			error_log( '----------PayPal AP Approval Response----------' );
			error_log( print_r( $response, TRUE ) );
		}

		if ( is_wp_error( $response ) ) {
			return FALSE;
		}

		$res = wp_parse_args( wp_remote_retrieve_body( $response ) );
		$req = wp_parse_args( $post_string );
		$_SESSION['nvpReqArray'] = $req;

		return $res;
	}

	private function call_pay( $payment, $deal_id, $amount, $qty = 1 ) {
		$payment_data = $payment->get_data();
		$secondary_share_per = Group_Buying_Paypal_AP::get_secondary_share( $deal_id ); 
    	$secondary_share = $secondary_share_per*$qty;
		$subtotal = $amount - $secondary_share;
		
		$receiverEmailArray = array(
			self::get_primary( $deal_id ),
			self::get_secondary( $deal_id ) );
		$receiverEmailArray = apply_filters( 'gb_paypal_ap_receiver_email_array', $receiverEmailArray, $payment, $deal_id, $amount ); 
		
		$receiverAmountArray = array(
			number_format( floatval( $amount ), 2 ),
        	number_format( floatval( $secondary_share ), 2 ) );
		$receiverAmountArray = apply_filters( 'gb_paypal_ap_receiver_amount_array', $receiverAmountArray, $payment, $deal_id, $amount );

		$receiverPrimaryArray = array(
			'true',
			'false' );
		$receiverInvoiceIdArray = '';

		$nvpstr = 'actionType=PAY&currencyCode=' . self::get_currency_code();
		$nvpstr .= '&returnUrl=' . urlencode( $this->return_url ) . '&cancelUrl=' . urlencode( $this->cancel_url );
		$nvpstr .= '&requestEnvelope.errorLanguage=en_US&requestEnvelope.detailLevel=ReturnAll';

		if ( 0 != count( $receiverAmountArray ) ) {
			reset( $receiverAmountArray );
			while ( list( $key, $value ) = each( $receiverAmountArray ) ) {
				if ( "" != $value ) {
					$nvpstr .= "&receiverList.receiver(" . $key . ").amount=" . urlencode( $value );
				}
			}
		}

		if ( 0 != count( $receiverEmailArray ) ) {
			reset( $receiverEmailArray );
			while ( list( $key, $value ) = each( $receiverEmailArray ) ) {
				if ( "" != $value ) {
					$nvpstr .= "&receiverList.receiver(" . $key . ").email=" . urlencode( $value );
				}
			}
		}

		if ( 0 != count( $receiverPrimaryArray ) ) {
			reset( $receiverPrimaryArray );
			while ( list( $key, $value ) = each( $receiverPrimaryArray ) ) {
				if ( "" != $value ) {
					$nvpstr = $nvpstr . "&receiverList.receiver(" . $key . ").primary=" . urlencode( $value );
				}
			}
		}
		//$nvpstr .= "&ipnNotificationUrl=" . Group_Buying_Offsite_Processor_Handler::get_url()
		//$nvpstr .= "&memo=" . urlencode($memo); // TODO (lp) make an option
		$nvpstr .= "&reverseAllParallelPaymentsOnError=FALSE";
		//$nvpstr .= "&senderEmail=" . urlencode($payment_data['api_response']['sender_email']);
		$nvpstr .= "&preapprovalKey=" . urlencode( $payment_data['api_response']['preapproval_key'] );
		$nvpstr .= "&trackingId=" . urlencode( $payment->get_ID() );
		
		$nvpstr = apply_filters( 'gb_paypal_ap_nvpst', $nvpstr, $payment, $deal_id, $amount );
		
		if ( self::DEBUG ) {
			error_log( "call: " . print_r(  wp_parse_args($nvpstr), true ) );
		}
		// Make the call
		$res = self::remote_post( 'Pay', $nvpstr );
		if ( is_wp_error( $res ) ) {
			return FALSE;
		}
		// Return response
		return $res;
	}


	/**
	 * Validate the message by checking with PayPal to make sure they really
	 * sent it
	 */
	private function validate_ipn( $ipn = null ) {
		if ( null == $ipn ) {
			$ipn = $_POST;
		}
		if ( self::$api_mode == self::MODE_TEST ) {
			$pp_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		} else {
			$pp_url = "https://www.paypal.com/cgi-bin/webscr";
		}

		// Set the command that is used to validate the message
		$ipn['cmd'] = "_notify-validate";
		$resp = wp_remote_post( $pp_url, array( 'method' => 'POST', 'body' => $ipn, 'sslverify' => false ) );
		// If the response was valid, check to see if the request was valid
		if ( !is_wp_error( $resp ) && $resp['response']['code'] >= 200 && $resp['response']['code'] < 300 && ( $resp['body'] == 'VERIFIED' ) ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Convert an associative array into an NVP string
	 *
	 * @param array   Associative array to create NVP string from
	 * @param string[optional] Used to separate arguments (defaults to &)
	 *
	 * @return string NVP string
	 */
	public function make_nvp( $reqArray, $sep = '&' ) {
		if ( !is_array( $reqArray ) ) {
			return $reqArray;
		}
		return http_build_query( $reqArray, '', $sep );
	}

	private function get_currency_code() {
		return apply_filters( 'gb_paypal_wpp_currency_code', $this->currency_code );
	}

	public function register_settings() {
		$page = Group_Buying_Payment_Processors::get_settings_page();
		$section = 'gb_paypalwpp_settings';
		add_settings_section( $section, self::__( 'PayPal Adaptive Payments' ), array( $this, 'display_settings_section' ), $page );
		register_setting( $page, self::API_MODE_OPTION );
		register_setting( $page, self::API_USERNAME_OPTION );
		register_setting( $page, self::API_PASSWORD_OPTION );
		register_setting( $page, self::API_SIGNATURE_OPTION );
		register_setting( $page, self::APP_ID_OPTION );
		register_setting( $page, self::CURRENCY_CODE_OPTION );
		register_setting( $page, self::RETURN_URL_OPTION );
		register_setting( $page, self::CANCEL_URL_OPTION );
		add_settings_field( self::API_MODE_OPTION, self::__( 'Mode' ), array( $this, 'display_api_mode_field' ), $page, $section );
		add_settings_field( self::API_USERNAME_OPTION, self::__( 'API Username' ), array( $this, 'display_api_username_field' ), $page, $section );
		add_settings_field( self::API_PASSWORD_OPTION, self::__( 'API Password' ), array( $this, 'display_api_password_field' ), $page, $section );
		add_settings_field( self::API_SIGNATURE_OPTION, self::__( 'API Signature' ), array( $this, 'display_api_signature_field' ), $page, $section );
		add_settings_field( self::APP_ID_OPTION, self::__( 'Application ID' ), array( $this, 'display_app_id_field' ), $page, $section );
		add_settings_field( self::CURRENCY_CODE_OPTION, self::__( 'Currency Code' ), array( $this, 'display_currency_code_field' ), $page, $section );
		add_settings_field( self::RETURN_URL_OPTION, self::__( 'Return URL' ), array( $this, 'display_return_field' ), $page, $section );
		add_settings_field( self::CANCEL_URL_OPTION, self::__( 'Cancel URL' ), array( $this, 'display_cancel_field' ), $page, $section );
	}

	public function display_api_username_field() {
		echo '<input type="text" name="'.self::API_USERNAME_OPTION.'" value="'.$this->api_username.'" size="80" />';
	}

	public function display_api_password_field() {
		echo '<input type="text" name="'.self::API_PASSWORD_OPTION.'" value="'.$this->api_password.'" size="80" />';
	}

	public function display_api_signature_field() {
		echo '<input type="text" name="'.self::API_SIGNATURE_OPTION.'" value="'.$this->api_signature.'" size="80" />';
	}

	public function display_app_id_field() {
		echo '<input type="text" name="'.self::APP_ID_OPTION.'" value="'.$this->app_id.'" size="80" />';
	}

	public function display_return_field() {
		echo '<input type="text" name="'.self::RETURN_URL_OPTION.'" value="'.$this->return_url.'" size="80" />';
	}

	public function display_cancel_field() {
		echo '<input type="text" name="'.self::CANCEL_URL_OPTION.'" value="'.$this->cancel_url.'" size="80" />';
	}

	public function display_api_mode_field() {
		echo '<label><input type="radio" name="'.self::API_MODE_OPTION.'" value="'.self::MODE_LIVE.'" '.checked( self::MODE_LIVE, $this->api_mode, FALSE ).'/> '.self::__( 'Live' ).'</label><br />';
		echo '<label><input type="radio" name="'.self::API_MODE_OPTION.'" value="'.self::MODE_TEST.'" '.checked( self::MODE_TEST, $this->api_mode, FALSE ).'/> '.self::__( 'Sandbox' ).'</label>';
	}

	public function display_currency_code_field() {
		echo '<input type="text" name="'.self::CURRENCY_CODE_OPTION.'" value="'.$this->currency_code.'" size="5" />';
	}

	public function review_controls() {
		echo '<div class="checkout-controls">
				<input type="hidden" name="" value="'.self::CHECKOUT_ACTION.'">
				<input class="form-submit submit checkout_next_step" type="submit" value="'.self::__( 'Paypal' ).'" name="gb_checkout_button" />
			</div>';
	}

	public static function add_meta_boxes() {
		add_meta_box( 'gb_adaptive_payments', self::__( 'Adaptive Payments' ), array( get_class(), 'show_meta_box' ), Group_Buying_Deal::POST_TYPE, 'advanced', 'high' );
	}

	public static function show_meta_box( $post, $metabox ) {
		$deal = Group_Buying_Deal::get_instance( $post->ID );
		self::show_adaptive_meta_box( $deal, $post, $metabox );
	}

	/**
	 * Display the deal adaptive payment meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post
	 * @param array   $metabox
	 * @return void
	 */
	public static function show_adaptive_meta_box( Group_Buying_Deal $deal, $post, $metabox ) {
		$primary = self::get_primary( $post->ID );
		$secondary = self::get_secondary( $post->ID );
		//$primary_share = self::get_primary_share($post->ID);
		$secondary_share = self::get_secondary_share( $post->ID );

		include dirname( __FILE__ ) .  '/meta-boxes/deal-adaptive-payments.php';
	}

	public static function save_meta_box( $post_id, $post ) {
		// only continue if it's a deal post
		if ( $post->post_type != Group_Buying_Deal::POST_TYPE ) {
			return;
		}
		// don't do anything on autosave, auto-draft, bulk edit, or quick edit
		if ( wp_is_post_autosave( $post_id ) || $post->post_status == 'auto-draft' || defined( 'DOING_AJAX' ) || isset( $_GET['bulk_edit'] ) ) {
			return;
		}
		// save all the meta boxes
		$deal = Group_Buying_Deal::get_instance( $post_id );
		self::save_adaptive_meta_box( $deal, $post_id, $post );
	}

	/**
	 * Save the deal adaptive payment meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post_id
	 * @param object  $post
	 * @return void
	 */
	private static function save_adaptive_meta_box( Group_Buying_Deal $deal, $post_id, $post ) {
		$primary = isset( $_POST['adaptive_primary'] ) ? $_POST['adaptive_primary'] : '';
		self::set_primary( $post_id, $primary, $deal );

		$secondary = isset( $_POST['adaptive_secondary'] ) ? $_POST['adaptive_secondary'] : '';
		self::set_secondary( $post_id, $secondary, $deal );

		//$primary_share = isset( $_POST['adaptive_primary_share'] ) ? $_POST['adaptive_primary_share'] : '';
		//self::set_primary_share( $post_id, $primary_share, $deal );

		$secondary_share = isset( $_POST['adaptive_secondary_share'] ) ? $_POST['adaptive_secondary_share'] : '';
		self::set_secondary_share( $post_id, $secondary_share, $deal );
	}

	public function set_primary( $post_id, $primary , Group_Buying_Deal $deal ) {
		update_post_meta( $post_id, self::$meta_keys['primary'], $primary );
		return $primary;
	}

	public function get_primary( $post_id, $primary = NULL ) {
		$primary = get_post_meta( $post_id, self::$meta_keys['primary'], true );
		return $primary;
	}

	public function set_secondary( $post_id, $secondary, Group_Buying_Deal $deal ) {
		update_post_meta( $post_id, self::$meta_keys['secondary'], $secondary );
		return $secondary;
	}

	public function get_secondary( $post_id, $secondary = NULL ) {
		$secondary = get_post_meta( $post_id, self::$meta_keys['secondary'], true );
		return $secondary;
	}

	public function set_primary_share( $post_id, $primary_share, Group_Buying_Deal $deal ) {
		update_post_meta( $post_id, self::$meta_keys['primary_share'], $primary_share );
		return $primary_share;
	}

	public function get_primary_share( $post_id, $primary_share = NULL ) {
		$primary_share = get_post_meta( $post_id, self::$meta_keys['primary_share'], true );
		return $primary_share;
	}

	public function set_secondary_share( $post_id, $secondary_share, Group_Buying_Deal $deal ) {
		if ( $deal->get_price() < $secondary_share ) {
			$secondary_share = $deal->get_price();
		}
		update_post_meta( $post_id, self::$meta_keys['secondary_share'], $secondary_share );
		return $secondary_share;
	}

	public function get_secondary_share( $post_id, $secondary_share = NULL ) {
		$secondary_share = get_post_meta( $post_id, self::$meta_keys['secondary_share'], true );
		return apply_filters( 'gb_paypal_ap_get_secondary_share', $secondary_share, $post_id );
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
			error_log( $message );
		}
	}
}
Group_Buying_Paypal_AP::register();