<?php

/**
 * Paypal credit card payment processor.
 * 
 * @package GBS
 * @subpackage Payment Processing_Processor
 */
class Group_Buying_Paypal_WPP extends Group_Buying_Credit_Card_Processors {
	const API_ENDPOINT_SANDBOX = 'https://api-3t.sandbox.paypal.com/nvp';
	const API_ENDPOINT_LIVE = 'https://api-3t.paypal.com/nvp';
	const MODE_TEST = 'sandbox';
	const MODE_LIVE = 'live';
	const API_USERNAME_OPTION = 'gb_paypal_username';
	const API_SIGNATURE_OPTION = 'gb_paypal_signature';
	const API_PASSWORD_OPTION = 'gb_paypal_password';
	const API_MODE_OPTION = 'gb_paypal_mode';
	const CURRENCY_CODE_OPTION = 'gb_paypal_currency';
	const PAYMENT_METHOD = 'Credit (PayPal WPP)';
	protected static $instance;
	private $api_mode = self::MODE_TEST;
	private $api_username = '';
	private $api_password = '';
	private $api_signature = '';
	private $currency_code = 'USD';
	private $version = '64';

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

	public function get_payment_method() {
		return self::PAYMENT_METHOD;
	}

	protected function __construct() {
		parent::__construct();
		$this->api_username = get_option( self::API_USERNAME_OPTION, '' );
		$this->api_password = get_option( self::API_PASSWORD_OPTION, '' );
		$this->api_signature = get_option( self::API_SIGNATURE_OPTION, '' );
		$this->api_mode = get_option( self::API_MODE_OPTION, self::MODE_TEST );
		$this->currency_code = get_option( self::CURRENCY_CODE_OPTION, 'USD' );

		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );
		add_action( 'purchase_completed', array( $this, 'capture_purchase' ), 10, 1 );
		add_action( self::CRON_HOOK, array( $this, 'capture_pending_payments' ) );
	}

	public static function register() {
		self::add_payment_processor( __CLASS__, self::__( 'PayPal Payments Pro' ) );
	}

	public static function accepted_cards() {
		$accepted_cards = array(
				'visa', 
				'mastercard', 
				'amex', 
				// 'diners', 
				'discover', 
				// 'jcb', 
				// 'maestro'
			);
		return apply_filters( 'gb_accepted_credit_cards', $accepted_cards );
	}

	/**
	 * Process a payment
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Purchase $purchase
	 * @return Group_Buying_Payment|bool FALSE if the payment failed, otherwise a Payment object
	 */
	public function process_payment( Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
		if ( $purchase->get_total( $this->get_payment_method() ) < 0.01 ) {
			// Nothing to do here, another payment handler intercepted and took care of everything
			// See if we can get that payment and just return it
			$payments = Group_Buying_Payment::get_payments_for_purchase( $purchase->get_id() );
			foreach ( $payments as $payment_id ) {
				$payment = Group_Buying_Payment::get_instance( $payment_id );
				return $payment;
			}
		}

		$post_data = $this->nvp_data( $checkout, $purchase );
		if ( self::DEBUG ) {
			error_log( '----------PayPal WPP post_data----------' );
			error_log( print_r( $post_data, TRUE ) );
		}
		$response = wp_remote_post( $this->get_api_url(), array(
				'body' => $post_data,
				'timeout' => apply_filters( 'http_request_timeout', 15 ),
				'sslverify' => false
			) );
		if ( self::DEBUG ) {
			error_log( '----------PayPal WPP $response----------' );
			error_log( print_r( $response, TRUE ) );
		}
		if ( is_wp_error( $response ) ) {
			return FALSE;
		}
		if ( $response['response']['code'] != '200' ) {
			return FALSE;
		}
		$response = wp_parse_args( $response['body'] );
		if ( strpos( $response['ACK'], 'Success' ) !== 0 ) {
			$this->set_error_messages( $response );
			return FALSE;
		}
		if ( strpos( $response['ACK'], 'SuccessWithWarning' ) === 0 ) {
			$this->set_error_messages( $response );
		}
		$deal_info = array();
		foreach ( $purchase->get_products() as $item ) {
			if ( isset( $item['payment_method'][$this->get_payment_method()] ) ) {
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
		$payment_id = Group_Buying_Payment::new_payment( array(
				'payment_method' => $this->get_payment_method(),
				'purchase' => $purchase->get_id(),
				'amount' => $response['AMT'],
				'data' => array(
					'api_response' => $response,
					'uncaptured_deals' => $deal_info,
				),
				'deals' => $deal_info,
				'shipping_address' => $shipping_address,
			), Group_Buying_Payment::STATUS_AUTHORIZED );
		if ( !$payment_id ) {
			return FALSE;
		}
		$payment = Group_Buying_Payment::get_instance( $payment_id );
		do_action( 'payment_authorized', $payment );

		$this->create_recurring_payment_profiles( $checkout, $purchase );

		return $payment;
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

	public  function capture_payment( Group_Buying_Payment $payment ) {
		// is this the right payment processor? does the payment still need processing?
		if ( $payment->get_payment_method() == $this->get_payment_method() && $payment->get_status() != Group_Buying_Payment::STATUS_COMPLETE ) {
			$data = $payment->get_data();
			// Do we have a transaction ID to use for the capture?
			if ( isset( $data['api_response']['TRANSACTIONID'] ) && $data['api_response']['TRANSACTIONID'] ) {
				$transaction_id = $data['api_response']['TRANSACTIONID'];
				$items_to_capture = $this->items_to_capture( $payment );
				if ( $items_to_capture ) {
					$status = ( count( $items_to_capture ) < count( $data['uncaptured_deals'] ) )?'NotComplete':'Complete';
					$post_data = $this->capture_nvp_data( $transaction_id, $items_to_capture, $status );
					if ( self::DEBUG ) {
						error_log( '----------PayPal WPP DoCapture Request----------' );
						error_log( print_r( $post_data, TRUE ) );
					}
					$response = wp_remote_post( $this->get_api_url(), array(
							'body' => $post_data,
							'timeout' => apply_filters( 'http_request_timeout', 15 ),
							'sslverify' => false
						) );
					if ( !is_wp_error( $response ) && $response['response']['code'] == '200' ) {
						$response = wp_parse_args( $response['body'] );
						if ( self::DEBUG ) {
							error_log( '----------PayPal WPP DoCapture Response----------' );
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

		$nvpData['USER'] = $this->api_username;
		$nvpData['PWD'] = $this->api_password;
		$nvpData['SIGNATURE'] = $this->api_signature;
		$nvpData['VERSION'] = '56.0';

		$nvpData['METHOD'] = 'DoCapture';
		$nvpData['AUTHORIZATIONID'] = $transaction_id;
		$nvpData['AMT'] = gb_get_number_format( $total );
		$nvpData['CURRENCYCODE'] = $this->get_currency_code();
		$nvpData['COMPLETETYPE'] = $status;

		$nvpData = apply_filters( 'gb_paypal_wpp_capture_nvp_data', $nvpData );

		//$nvpData = array_map('rawurlencode', $nvpData);
		return $nvpData;
	}

	/**
	 * Grabs error messages from a PayPal response and displays them to the user
	 *
	 * @param array   $response
	 * @param bool    $display
	 * @return void
	 */
	private function set_error_messages( $response, $display = TRUE ) {
		foreach ( $response as $key => $value ) {
			if ( preg_match( '/^L_SHORTMESSAGE(\d+)$/', $key, $matches ) ) {
				$message_id = $matches[1];
				$message = $value;
				if ( isset( $response['L_LONGMESSAGE'.$message_id] ) ) {
					$message .= sprintf( ': %s', $response['L_LONGMESSAGE'.$message_id] );
				}
				if ( isset( $response['L_ERRORCODE'.$message_id] ) ) {
					$message .= sprintf( self::__( ' (Error Code: %s)' ), $response['L_ERRORCODE'.$message_id] );
				}
				if ( $display ) {
					self::set_message( $message, self::MESSAGE_STATUS_ERROR );
				} else {
					error_log( $message );
				}
			}
		}
	}

	/**
	 * Build the NVP data array for submitting the current checkout to PayPal as an Authorization request
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Purchase $purchase
	 * @return array
	 */
	private function nvp_data( Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
		$user = get_userdata( $purchase->get_user() );
		$nvpData = array();

		$nvpData['USER'] = $this->api_username;
		$nvpData['PWD'] = $this->api_password;
		$nvpData['SIGNATURE'] = $this->api_signature;
		$nvpData['VERSION'] = '56.0';


		$nvpData['METHOD'] = 'DoDirectPayment';
		$nvpData['PAYMENTACTION'] = 'Authorization';
		$nvpData['IPADDRESS'] = $_SERVER ['REMOTE_ADDR'];

		$nvpData['CREDITCARDTYPE'] = self::get_card_type( $this->cc_cache['cc_number'] );
		$nvpData['ACCT'] = $this->cc_cache['cc_number'];
		$nvpData['EXPDATE'] = self::expiration_date( $this->cc_cache['cc_expiration_month'], $this->cc_cache['cc_expiration_year'] );
		$nvpData['CVV2'] = $this->cc_cache['cc_cvv'];

		$nvpData['FIRSTNAME'] = $checkout->cache['billing']['first_name'];
		$nvpData['LASTNAME'] = $checkout->cache['billing']['last_name'];
		$nvpData['EMAIL'] = $user->user_email;

		$nvpData['STREET'] = $checkout->cache['billing']['street'];
		$nvpData['CITY'] = $checkout->cache['billing']['city'];
		$nvpData['STATE'] = $checkout->cache['billing']['zone'];
		$nvpData['COUNTRYCODE'] = self::country_code( $checkout->cache['billing']['country'] );
		$nvpData['ZIP'] = $checkout->cache['billing']['postal_code'];

		$nvpData['AMT'] = gb_get_number_format( $purchase->get_total( $this->get_payment_method() ) );
		$nvpData['CURRENCYCODE'] = $this->get_currency_code();
		$nvpData['ITEMAMT'] = gb_get_number_format( $purchase->get_subtotal( $this->get_payment_method() ) );
		$nvpData['SHIPPINGAMT'] = gb_get_number_format( $purchase->get_shipping_total( $this->get_payment_method() ) );
		$nvpData['TAXAMT'] = gb_get_number_format( $purchase->get_tax_total( $this->get_payment_method() ) );
		$nvpData['INVNUM'] = $purchase->get_id();
		$nvpData['BUTTONSOURCE'] = self::PLUGIN_NAME;

		$i = 0;
		if ( $nvpData['ITEMAMT'] == gb_get_number_format(0) ) {
			if ( $nvpData['SHIPPINGAMT'] != gb_get_number_format(0) ) {
				$nvpData['ITEMAMT'] = $nvpData['SHIPPINGAMT'];
				$nvpData['SHIPPINGAMT'] = gb_get_number_format(0);
			} elseif ( $nvpData['TAXAMT'] != gb_get_number_format(0) ) {
				$nvpData['ITEMAMT'] = $nvpData['TAXAMT'];
				$nvpData['TAXAMT'] = gb_get_number_format(0);
			}
		} else {
			foreach ( $purchase->get_products() as $item ) {
				if ( isset( $item['payment_method'][$this->get_payment_method()] ) ) {
					$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
					$nvpData['L_NAME'.$i] = html_entity_decode( strip_tags( $deal->get_title( $item['data'] ) ), ENT_QUOTES, 'UTF-8' );
					if ( count( $item['payment_method'] ) > 1 ) { // if we're only handling part of this item, prorate to make PayPal happy
						$nvpData['L_AMT'.$i] = gb_get_number_format( ( $item['payment_method'][$this->get_payment_method()]/$item['quantity'] ) );
					} else {
						$nvpData['L_AMT'.$i] = gb_get_number_format( $item['unit_price'] );
					}
					$nvpData['L_NUMBER'.$i] = $item['deal_id'];
					$nvpData['L_QTY'.$i] = $item['quantity'];
				}
				$i++;
			}
		}

		if ( isset( $checkout->cache['shipping'] ) ) {
			$nvpData['SHIPTONAME'] = $checkout->cache['shipping']['first_name'].' '.$checkout->cache['shipping']['last_name'];
			$nvpData['SHIPTOSTREET'] = $checkout->cache['shipping']['street'];
			$nvpData['SHIPTOCITY'] = $checkout->cache['shipping']['city'];
			$nvpData['SHIPTOSTATE'] = $checkout->cache['shipping']['zone'];
			$nvpData['SHIPTOZIP'] = $checkout->cache['shipping']['postal_code'];
			$nvpData['SHIPTOCOUNTRY'] = $checkout->cache['shipping']['country'];
		}

		$nvpData = apply_filters( 'gb_paypal_wpp_nvp_data', $nvpData, $checkout, $i, $purchase );

		//$nvpData = array_map('rawurlencode', $nvpData);
		return $nvpData;
	}

	private function get_currency_code() {
		return apply_filters( 'gb_paypal_wpp_currency_code', $this->currency_code );
	}

	/**
	 * Create recurring payment profiles for any recurring deals in the purchase
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Purchase $purchase
	 * @return void
	 */
	private function create_recurring_payment_profiles( Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
		foreach ( $purchase->get_products() as $item ) {
			if ( isset( $item['payment_method'][$this->get_payment_method()] ) && isset( $item['data']['recurring'] ) && $item['data']['recurring'] ) {
				// make a separate recurring payment for each item,
				// so they can be cancelled separately if necessary
				$this->create_recurring_payment_profile( $item, $checkout, $purchase );
			}
		}
	}

	/**
	 * Create the recurring payment profile.
	 *
	 * Start on the second payment, as the first payment is included in the initial purchase
	 *
	 * @param array   $item
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Purchase $purchase
	 * @return bool Whether we succeeded in creating a recurring payment profile
	 */
	private function create_recurring_payment_profile( $item, Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
		$nvpData = $this->create_recurring_payment_nvp_data( $item, $checkout, $purchase );
		if ( !$nvpData ) {
			return FALSE; // paying for it some other way
		}

		if ( self::DEBUG ) {
			error_log( '----------PayPal WPP Recurring Payment Request ----------' );
			error_log( print_r( $nvpData, TRUE ) );
		}

		$response = wp_remote_post( self::get_api_url(), array(
				'method' => 'POST',
				'body' => $nvpData,
				'timeout' => apply_filters( 'http_request_timeout', 15 ),
				'sslverify' => false
			) );

		if ( self::DEBUG ) {
			error_log( '----------PayPal WPP Recurring Payment Response (Raw)----------' );
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
			error_log( '----------PayPal WPP Recurring Payment Response (Parsed)----------' );
			error_log( print_r( $response, TRUE ) );
		}

		if ( empty( $response['PROFILEID'] ) ) {
			do_action( 'gb_paypal_recurring_payment_profile_failed' );
			return FALSE;
		}

		// create a payment to store the API response
		$payment_id = Group_Buying_Payment::new_payment( array(
				'payment_method' => $this->get_payment_method(),
				'purchase' => $purchase->get_id(),
				'amount' => $item['data']['recurring']['price'],
				'data' => array(
					'api_response' => $response,
				),
			), Group_Buying_Payment::STATUS_RECURRING );

		// let the world know
		do_action( 'gb_paypal_recurring_payment_profile_created', $payment_id );
		return TRUE;
	}

	private function create_recurring_payment_nvp_data( $item, Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
		$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
		$user = get_userdata( get_current_user_id() );
		$term = $item['data']['recurring']['term']; // day, week, month, or year
		$duration = (int)$item['data']['recurring']['duration'];
		$price = $item['data']['recurring']['price'];

		$terms = array(
			'day' => 'Day',
			'week' => 'Week',
			'month' => 'Month',
			'year' => 'Year',
		);
		if ( !isset( $terms[$term] ) ) {
			$term = 'day';
		}

		$starts = strtotime( date( 'Y-m-d' ).' +'.$duration.' '.$term );

		$nvp = array(
			'USER' => $this->api_username,
			'PWD' => $this->api_password,
			'SIGNATURE' => $this->api_signature,
			'VERSION' => $this->version,
			'METHOD' => 'CreateRecurringPaymentsProfile',
			'PROFILESTARTDATE' => date( 'Y-m-d', $starts ).'T00:00:00Z',
			'PROFILEREFERENCE' => $purchase->get_id(),
			'DESC' => $deal->get_title( $item['data'] ),
			'MAXFAILEDPAYMENTS' => 2,
			'AUTOBILLOUTAMT' => 'AddToNextBilling',
			'BILLINGPERIOD' => $terms[$term],
			'BILLINGFREQUENCY' => $duration,
			'TOTALBILLINGCYCLES' => 0,
			'AMT' => gb_get_number_format( $price ),
			'CURRENCYCODE' => self::get_currency_code(),
			'EMAIL' => $user->user_email,
			'L_PAYMENTREQUEST_0_ITEMCATEGORY0' => 'Digital',
			'L_PAYMENTREQUEST_0_NAME0' => $deal->get_title( $item['data'] ),
			'L_PAYMENTREQUEST_0_AMT0' => gb_get_number_format( $price ),
			'L_PAYMENTREQUEST_0_NUMBER0' => $deal->get_id(),
			'L_PAYMENTREQUEST_0_QTY0' => 1,
			'CREDITCARDTYPE' => self::get_card_type( $this->cc_cache['cc_number'] ),
			'ACCT' => $this->cc_cache['cc_number'],
			'EXPDATE' => self::expiration_date( $this->cc_cache['cc_expiration_month'], $this->cc_cache['cc_expiration_year'] ),
			'CVV2' => $this->cc_cache['cc_cvv'],
			'STREET' => $checkout->cache['billing']['street'],
			'CITY' => $checkout->cache['billing']['city'],
			'STATE' => $checkout->cache['billing']['zone'],
			'COUNTRYCODE' => self::country_code( $checkout->cache['billing']['country'] ),
			'ZIP' => $checkout->cache['billing']['postal_code'],
		);
		return $nvp;
	}

	/**
	 *
	 *
	 * @param Group_Buying_Payment $payment
	 * @return void
	 */
	public function verify_recurring_payment( Group_Buying_Payment $payment ) {
		// Check if the payment has a recurring profile ID (in $data['api_response'])
		$data = $payment->get_data();
		if ( empty( $data['api_response']['PROFILEID'] ) ) {
			return;
		}
		// Get the profile status
		//  - see https://www.x.com/developers/paypal/documentation-tools/api/getrecurringpaymentsprofiledetails-api-operation-nvp
		$status = $this->get_recurring_payment_status( $data['api_response']['PROFILEID'] );
		if ( $status != 'Active' ) {
			$payment->set_status( Group_Buying_Payment::STATUS_CANCELLED );
		}
	}

	private function get_recurring_payment_status( $profile_id ) {
		$nvp = array(
			'USER' => $this->api_username,
			'PWD' => $this->api_password,
			'SIGNATURE' => $this->api_signature,
			'VERSION' => $this->version,
			'METHOD' => 'GetRecurringPaymentsProfileDetails',
			'PROFILEID' => $profile_id,
		);

		if ( self::DEBUG ) {
			error_log( '----------PayPal WPP Recurring Payment Details Request ----------' );
			error_log( print_r( $nvp, TRUE ) );
		}

		$response = wp_remote_post( self::get_api_url(), array(
				'method' => 'POST',
				'body' => $nvp,
				'timeout' => apply_filters( 'http_request_timeout', 15 ),
				'sslverify' => false
			) );

		if ( self::DEBUG ) {
			error_log( '----------PayPal WPP Recurring Payment Details Response (Raw)----------' );
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
			error_log( '----------PayPal WPP Recurring Payment Details Response (Parsed)----------' );
			error_log( print_r( $response, TRUE ) );
		}

		if ( empty( $response['STATUS'] ) ) {
			return FALSE;
		}

		return $response['STATUS'];
	}

	/**
	 *
	 *
	 * @param Group_Buying_Payment $payment
	 * @return void
	 */
	public function cancel_recurring_payment( Group_Buying_Payment $payment ) {
		// Admin or User Inititated.
		// Check if the payment has a recurring profile ID (in $data['api_response'])
		$data = $payment->get_data();
		if ( empty( $data['api_response']['PROFILEID'] ) ) {
			return;
		}
		$profile_id = $data['api_response']['PROFILEID'];
		// Cancel the profile
		//  - see https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_ManageRecurringPaymentsProfileStatus

		$nvp = array(
			'USER' => $this->api_username,
			'PWD' => $this->api_password,
			'SIGNATURE' => $this->api_signature,
			'VERSION' => $this->version,
			'METHOD' => 'ManageRecurringPaymentsProfileStatus',
			'PROFILEID' => $profile_id,
			'ACTION' => 'Cancel',
			'NOTE' => apply_filters( 'gbs_paypal_recurring_payment_cancelled_note', '' ),
		);

		if ( self::DEBUG ) {
			error_log( '----------PayPal WPP Cancel Recurring Payment Request ----------' );
			error_log( print_r( $nvp, TRUE ) );
		}

		$response = wp_remote_post( self::get_api_url(), array(
				'method' => 'POST',
				'body' => $nvp,
				'timeout' => apply_filters( 'http_request_timeout', 15 ),
				'sslverify' => false
			) );

		if ( self::DEBUG ) {
			error_log( '----------PayPal WPP Cancel Recurring Payment Response (Raw)----------' );
			error_log( print_r( $response, TRUE ) );
		}
		// we don't really need to do anything with the response. It's either a success message
		// or the profile is already cancelled/suspended. Either way, we're good.
		parent::cancel_recurring_payment( $payment );
	}

	/**
	 * Format the month and year as an expiration date
	 *
	 * @static
	 * @param int     $month
	 * @param int     $year
	 * @return string
	 */
	private static function expiration_date( $month, $year ) {
		return sprintf( '%02d%04d', $month, $year );
	}

	private static function country_code( $country = null ) {
		if ( null != $country ) {
			return $country;
		}
		return 'US';
	}

	public function register_settings() {
		$page = Group_Buying_Payment_Processors::get_settings_page();
		$section = 'gb_paypal_settings';
		add_settings_section( $section, self::__( 'PayPal Payments Pro' ), array( $this, 'display_settings_section' ), $page );
		register_setting( $page, self::API_MODE_OPTION );
		register_setting( $page, self::API_USERNAME_OPTION );
		register_setting( $page, self::API_PASSWORD_OPTION );
		register_setting( $page, self::API_SIGNATURE_OPTION );
		register_setting( $page, self::CURRENCY_CODE_OPTION );
		add_settings_field( self::API_MODE_OPTION, self::__( 'Mode' ), array( $this, 'display_api_mode_field' ), $page, $section );
		add_settings_field( self::API_USERNAME_OPTION, self::__( 'API Username' ), array( $this, 'display_api_username_field' ), $page, $section );
		add_settings_field( self::API_PASSWORD_OPTION, self::__( 'API Password' ), array( $this, 'display_api_password_field' ), $page, $section );
		add_settings_field( self::API_SIGNATURE_OPTION, self::__( 'API Signature' ), array( $this, 'display_api_signature_field' ), $page, $section );
		add_settings_field( self::CURRENCY_CODE_OPTION, self::__( 'Currency Code' ), array( $this, 'display_currency_code_field' ), $page, $section );
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

	public function display_api_mode_field() {
		echo '<label><input type="radio" name="'.self::API_MODE_OPTION.'" value="'.self::MODE_LIVE.'" '.checked( self::MODE_LIVE, $this->api_mode, FALSE ).'/> '.self::__( 'Live' ).'</label><br />';
		echo '<label><input type="radio" name="'.self::API_MODE_OPTION.'" value="'.self::MODE_TEST.'" '.checked( self::MODE_TEST, $this->api_mode, FALSE ).'/> '.self::__( 'Sandbox' ).'</label>';
	}

	public function display_currency_code_field() {
		echo '<input type="text" name="'.self::CURRENCY_CODE_OPTION.'" value="'.$this->currency_code.'" size="5" />';
	}
}
Group_Buying_Paypal_WPP::register();