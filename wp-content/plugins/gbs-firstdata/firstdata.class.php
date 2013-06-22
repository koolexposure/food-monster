<?php

/**
 * Paypal offsite payment processor.
 *
 * @package GBS
 * @subpackage Payment Processing_Processor
 */
class Group_Buying_First_Data extends Group_Buying_Offsite_Processors {
	const API_ENDPOINT_SANDBOX = 'https://connect.firstdataglobalgateway.com/IPGConnect/gateway/processing';
	const API_ENDPOINT_LIVE = 'https://connect.firstdataglobalgateway.com/IPGConnect/gateway/processing';
	
	const MODE_TEST = 'sandbox';
	const MODE_LIVE = 'live';
	const API_MODE_OPTION = 'gb_borgun_mode';

	const API_MERCHANTID_OPTION = 'gb_borgun_merchantid';
	const API_SECRETCODE_OPTION = 'gb_borgun_secrecode';
	const API_PAYMENTGATEWAYID_OPTION = 'gb_borgun_paymentgatewayid';
	
	const API_PAGETYPE_OPTION = 'gb_borgun_pagetype';
	const API_SKIPRECEIPTPAGE_OPTION = 'gb_borgun_skipreceiptpage';
	const API_MERCHANTLOGO_OPTION = 'gb_borgun_merchantlogo';

	const CANCEL_URL_OPTION = 'gb_borgun_cancel_url';
	const RETURN_URL_OPTION = 'gb_borgun_return_url';
	const ERROR_URL_OPTION = 'gb_borgun_error_url';
	const SUCCESSSERVER_URL_OPTION = 'gb_borgun_error_url';

	const LANG_OPTION = 'gb_borgun_lang';
	const CURRENCY_CODE_OPTION = 'gb_borgun_currency';
	const PAYMENT_METHOD = 'Borgun';
	const TOKEN_KEY = 'gb_token_key'; // Combine with $blog_id to get the actual meta key
	const LOGS = 'gb_offsite_logs';

	protected static $instance;
	protected static $api_mode = self::MODE_TEST;

	private static $api_merchantid;
	private static $api_secretcode;
	private static $api_paymentgatwayid;

	private static $api_pagetype;
	private static $api_skipReceiptPage;
	private static $api_merchantlogo;

	private static $cancel_url;
	private static $return_url;
	private static $error_url;
	private static $returnurlsuccess_server_url;

	private static $currency_code;
	private static $page_language;
	



public static function getdatetime() {
  global $datetime;
  $timezone="EST";
$b = time () - 14400; 
$datetime = date("Y:m:d-H:i:s",$b);
  return $datetime;
}

public static function gettimezone() {
 global $timezone;
 $timezone = "EST";
  return $timezone;
}

public static function getstore() {
  global $storename;
  $storename ="1001318860";
  return $storename;
}

public static function createhash($chargetotal) {
  global $storename, $sharedsecret;

  $sharedsecret="38353831373131313334363739363038353334353730393134393037343636393432343636373938373033353035303634353532313331383936363138373036";
  $str = $storename . self::getdatetime() . $chargetotal . $sharedsecret;
  for ($i=0;$i<strlen($str);$i++) {
    $hexstr .= dechex(ord($str[$i]));
  }
  return hash('sha256', $hexstr);
}
	
	

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

	protected function __construct() {
		parent::__construct();

		self::$api_merchantid = get_option( self::API_MERCHANTID_OPTION, '9275444' );
		self::$api_secretcode = get_option( self::API_SECRETCODE_OPTION, '99887766' );
		self::$api_paymentgatwayid = get_option( self::API_PAYMENTGATEWAYID_OPTION, '16' );

		self::$api_pagetype = get_option( self::API_PAGETYPE_OPTION );
		self::$api_skipReceiptPage = get_option( self::API_SKIPRECEIPTPAGE_OPTION );
		self::$api_merchantlogo = get_option( self::API_MERCHANTLOGO_OPTION );

		self::$api_mode = get_option( self::API_MODE_OPTION, self::MODE_TEST );
		self::$page_language = get_option( self::LANG_OPTION, 'EN' );
		self::$currency_code = get_option( self::CURRENCY_CODE_OPTION, 'USD' );
		
		self::$return_url = Group_Buying_Checkouts::get_url();
		self::$returnurlsuccess_server_url = Group_Buying_Checkouts::get_url();
		self::$cancel_url = get_option( self::CANCEL_URL_OPTION, Group_Buying_Carts::get_url() );
		self::$error_url = get_option( self::ERROR_URL_OPTION, Group_Buying_Checkouts::get_url() );
		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );

		// Remove review page
		add_filter('gb_checkout_pages', array($this, 'remove_review_page'));

		// Send Offsite
		add_action( 'gb_send_offsite_for_payment', array( $this, 'send_offsite' ), 10, 1 );
		// back from burgon
		add_action( 'gb_load_cart', array( $this, 'back_from_borgun' ), 10, 0 );
		// Complete purchase
		add_action( 'purchase_completed', array( $this, 'complete_purchase' ), 10, 1 );

		// Checkout button
		add_filter( 'gb_checkout_payment_controls', array( $this, 'payment_controls' ), 20, 2 );

	}


	public static function register() {
		self::add_payment_processor( __CLASS__, self::__( 'Borgun Payments' ) );
	}

	public static function public_name() {
		return self::__( 'Borgun' );
	}

	public static function checkout_icon() {
		return '<img src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" title="Paypal Payments" id="paypal_icon"/>';
	}

	/**
	 * The review page is unnecessary (or, rather, it's offsite)
	 * @param array $pages
	 * @return array
	 */
	public function remove_review_page( $pages ) {
		unset($pages[Group_Buying_Checkouts::REVIEW_PAGE]);
		return $pages;
	}


	public function send_offsite( Group_Buying_Checkouts $checkout ) {

		$cart = $checkout->get_cart();

		if ( $cart->get_total( self::get_payment_method() ) < 0.01 ) {
			// Nothing to do here, another payment handler intercepted and took care of everything
			// See if we can get that payment and just return it
			$payments = Group_Buying_Payment::get_payments_for_purchase( $cart->get_id() );
			foreach ( $payments as $payment_id ) {
				$payment = Group_Buying_Payment::get_instance( $payment_id );
				return $payment;
			}
		}

		if ( $_REQUEST['gb_checkout_action'] == Group_Buying_Checkouts::PAYMENT_PAGE ) {
if ( self::DEBUG ) error_log( '----------redirect to form----------' );
			$post_data = $this->set_nvp_data( $checkout );
			if ( !$post_data ) {
				return; // paying for it some other way
			}
			$checkout->save_cache_on_redirect( NULL ); // Save cache since it's not being saved via wp_redirect
			self::redirect_borgun( $post_data );
		}
	}


	public static function redirect_borgun( $post_data ) {

		$_html = array();

		$_html[] = "<html>";
		$_html[] = "<head><title>Processing Payment...</title></head>";
		$_html[] = "<body onLoad=\"document.forms['borgun_form'].submit();\">";
		//$_html[] = "<body>";
		$_html[] = '<center><img src="http://vps-1083582-7290.manage.myhosting.com/foodmonster/wp-content/themes/foodmonster/img/logo.png"></center>';
		$_html[] =  "<center><h2>";
		$_html[] = self::__("Please wait, your order is being processed and you will be redirected to the our secure payment gateway.");
		$_html[] =  "</h2></center>";

		$_html[] = '<form name="borgun_form" action="'. self::get_api_url() .'" method="post">';

		foreach ( $post_data as $key => $value ) {
			$_html[] = '<input type="hidden" value="'. $value .'" name="'. $key .'" />';
		}

		$_html[] =  "<center>";
		$_html[] =  "";
		$_html[] =  "";
		$_html[] =  self::__("If you are not automatically redirected to ");
		$_html[] =  self::__("First Data  - PNC within 5 seconds...");
		$_html[] =  "";
		$_html[] =  '<input type="submit" value="'.self::__( 'Click Here' ).'"></center>';

		$_html[] = '</form>';
		$_html[] = '</body>';
		$return = implode( "\n", $_html );

		print $return;

		exit();
	}


	/**
	 * Process a payment
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Purchase $purchase
	 * @return Group_Buying_Payment|bool FALSE if the payment failed, otherwise a Payment object
	 */
	public function process_payment( Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
	if ( self::DEBUG ) error_log( '----------Borgun EC SetCheckout Data----------' );
		if ( $purchase->get_total( self::get_payment_method() ) < 0.01 ) {
			// Nothing to do here, another payment handler intercepted and took care of everything
			// See if we can get that payment and just return it
			$payments = Group_Buying_Payment::get_payments_for_purchase( $purchase->get_id() );
			foreach ( $payments as $payment_id ) {
				$payment = Group_Buying_Payment::get_instance( $payment_id );
				return $payment;
			}
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
		self::unset_token();
		return $payment;
	}

	/**
	 * Complete the purchase after the process_payment action, otherwise vouchers will not be activated.
	 *
	 * @param Group_Buying_Purchase $purchase
	 * @return void
	 */
	public function complete_purchase( Group_Buying_Purchase $purchase ) {
			
	if ( self::DEBUG ) error_log( '----------Borgun EC SetCheckout Data----------' );
		$items_captured = array(); // Creating simple array of items that are captured
		foreach ( $purchase->get_products() as $item ) {
			$items_captured[] = $item['deal_id'];
		}
		$payments = Group_Buying_Payment::get_payments_for_purchase($purchase->get_id());
		foreach ( $payments as $payment_id ) {
			$payment = Group_Buying_Payment::get_instance($payment_id);
			do_action('payment_captured', $payment, $items_captured);
			do_action('payment_complete', $payment);
			$payment->set_status(Group_Buying_Payment::STATUS_COMPLETE);
		}
	}


	public static function validate_purchase() {
			if ( self::DEBUG ) error_log( '----------validate_purchase----------' );
		if ( self::returned_from_offsite() ) {
			if ( isset( $_REQUEST['status'] ) && $_REQUEST['status'] == 'APPROVED' ) {
						if ( self::DEBUG ) error_log( '----------approved---------' );
				//$token = self::get_token();
				//$token_array = explode( '_', $token );
				//$purchase_amount = $token_array[1];
				//$check_hash = md5( $_REQUEST['orderid'].$purchase_amount.self::$api_secretcode );
				//if ( $check_hash === $_REQUEST['orderhash'] ) {
					return TRUE;
				//}
			}
		}
		return;
	}

	/**
	 * Necessary for all offsite processors
	 * @return  
	 */
	public static function returned_from_offsite() {
				if ( self::DEBUG ) error_log( '----------Borgun EC SetCheckout Data----------' );
				if ( self::DEBUG ) error_log(print_r($_REQUEST, TRUE)	);
		return ( isset($_REQUEST['oid']) );
		
	}

	/**
	 * We're on the checkout page, just back from Borgun.
	 *
	 * @return void
	 */
	public function back_from_borgun() {
				if ( self::DEBUG ) error_log( '----------back_from_borgun----------' );
		if ( self::validate_purchase() ) {
			
			if ( self::DEBUG ) error_log( '----------it validated---------' );
			
			$_REQUEST['gb_checkout_action'] = 'back_from_borgan';
			if ( self::DEBUG ) error_log( "SUCCESS: " . print_r( $_REQUEST, true ) );
			return;
		} elseif ( !isset( $_REQUEST['gb_checkout_action'] ) ) {
			// this is a new checkout. clear the token so we don't give things away for free
			
				if ( self::DEBUG ) error_log( '----------it failed validated---------' );
			self::unset_token();
		}
		// Report errors.
		if ( isset( $_REQUEST['errordetail'] ) ) {
			self::set_error_messages( $_REQUEST['errordetail'] );
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
		
		self::set_token( $cart->get_id() . '_' . gb_get_number_format( $this->get_payment_request_total( $checkout ) ) ); // Set a price so it can be referenced later.

		$nvpData = array();
		$nvpData['storename'] = self::getstore();
		$nvpData['hash'] = self::createhash(gb_get_number_format( $filtered_total ));
		$nvpData['responseFailURL'] = "http://vps-1083582-7290.manage.myhosting.com/foodmonster/checkout";
		$nvpData['responseSuccessURL'] = "http://vps-1083582-7290.manage.myhosting.com/foodmonster/checkout";

		$nvpData['mode'] = 'payonly';
		$nvpData['trxOrigin'] = 'ECI';
		$nvpData['txntype'] = 'sale';
		$nvpData['txndatetime'] = self::getdatetime();
		$nvpData['authenticateTransaction'] = 'false';
		$nvpData['timezone'] = self::gettimezone();
		$nvpData['EMAIL'] = $user->user_email;
		$nvpData['LANDINGPAGE'] = 'Billing';
		$nvpData['SOLUTIONTYPE'] = 'Sole';
		$nvpData['chargetotal'] = gb_get_number_format( $filtered_total );
		$nvpData['PAYMENTREQUEST_0_CURRENCYCODE'] = self::get_currency_code();
		$nvpData['subtotal'] = gb_get_number_format( $cart->get_subtotal() );
		$nvpData['PAYMENTREQUEST_0_SHIPPINGAMT'] = gb_get_number_format( $cart->get_shipping_total() );
		$nvpData['PAYMENTREQUEST_0_TAXAMT'] = gb_get_number_format( $cart->get_tax_total() );

		$i = 0;
		if (
			$nvpData['PAYMENTREQUEST_0_ITEMAMT'] == gb_get_number_format( 0 ) ||
			( $filtered_total < $cart->get_total()
				&& ( $cart->get_subtotal() + $filtered_total - $cart->get_total() ) == 0
			)
		) {
			// handle orders that are free but have tax or shipping
			// TODO
		} else {
			// we can add individual item info if there's actually an item cost
			foreach ( $cart->get_items() as $key => $item ) {
				$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
				$nvpData['Itemdescription_'.$i] = $deal->get_title( $item['data'] );
				$nvpData['Itemcount_'.$i] = $item['quantity'];
				$nvpData['Itemunitamount_'.$i] = gb_get_number_format( $deal->get_price( NULL, $item['data'] ) );
				$nvpData['Itemamount_'.$i] = gb_get_number_format( $deal->get_price( NULL, $item['data'] ) ) * $item['quantity'];
				$i++;
			}
			if ( $filtered_total < $cart->get_total() ) {
				$nvpData['Itemdescription_'.$i] = self::__( 'Applied Credit' );
				$nvpData['Itemunitamount_'.$i] = gb_get_number_format( $filtered_total - $cart->get_total() );
				$nvpData['Itemcount_'.$i] = '1';
				$nvpData['Itemamount_'] = gb_get_number_format( $cart->get_subtotal() + $filtered_total - $cart->get_total() );
				$i++;
			}
		}

		$nvpData = apply_filters( 'gb_borgun_ec_set_nvp_data', $nvpData );
		if ( self::DEBUG ) {
			if ( self::DEBUG ) error_log( '----------Borgun EC SetCheckout Data----------' );
			if ( self::DEBUG ) error_log( print_r( $nvpData, TRUE ) );
		}

		return apply_filters( 'gb_set_nvp_data', $nvpData, $checkout, $i );
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


	private function get_currency_code() {
		return apply_filters( 'gb_borgun_ec_currency_code', self::$currency_code );
	}

	public function register_settings() {
		$page = Group_Buying_Payment_Processors::get_settings_page();
		$section = 'gb_borgun_settings';
		add_settings_section( $section, self::__( 'Borgun Payments Standard' ), array( $this, 'display_settings_section' ), $page );
		register_setting( $page, self::API_MODE_OPTION );
		register_setting( $page, self::API_MERCHANTID_OPTION );
		register_setting( $page, self::API_SECRETCODE_OPTION );
		register_setting( $page, self::API_PAYMENTGATEWAYID_OPTION );
		register_setting( $page, self::API_PAGETYPE_OPTION );
		register_setting( $page, self::API_SKIPRECEIPTPAGE_OPTION );
		register_setting( $page, self::API_MERCHANTLOGO_OPTION );
		register_setting( $page, self::CANCEL_URL_OPTION );
		register_setting( $page, self::LANG_OPTION );
		register_setting( $page, self::CURRENCY_CODE_OPTION );

		add_settings_field( self::API_MODE_OPTION, self::__( 'Mode' ), array( get_class(), 'display_api_mode_field' ), $page, $section );
		
		add_settings_field( self::API_MERCHANTID_OPTION, self::__( 'Merchant ID' ), array( get_class(), 'display_api_merchantid_field' ), $page, $section );
		add_settings_field( self::API_SECRETCODE_OPTION, self::__( 'Secure Code' ), array( get_class(), 'display_api_secretcode_field' ), $page, $section );
		add_settings_field( self::API_PAYMENTGATEWAYID_OPTION, self::__( 'Payment Gateway ID' ), array( get_class(), 'display_api_paymentgatewayid_field' ), $page, $section );

		add_settings_field( self::API_PAGETYPE_OPTION, self::__( 'Page Type' ), array( get_class(), 'display_api_pagetype_field' ), $page, $section );
		add_settings_field( self::API_SKIPRECEIPTPAGE_OPTION, self::__( 'Skip Receipt Page' ), array( get_class(), 'display_api_skipReceiptPage_field' ), $page, $section );
		add_settings_field( self::API_MERCHANTLOGO_OPTION, self::__( 'Merchant logo' ), array( get_class(), 'display_api_merchantlogo_field' ), $page, $section );
		add_settings_field( self::CANCEL_URL_OPTION, self::__( 'Cancel URL' ), array( get_class(), 'display_cancel_field' ), $page, $section );
		add_settings_field( self::LANG_OPTION, self::__( 'Language (Supported langages are icelandic (IS), english (EN), german (DE), french (FR), russian (RU), spanish (ES) Italian (IT), portuguese (PT) and swedish (SE))' ), array( get_class(), 'display_lang_field' ), $page, $section );
		add_settings_field( self::CURRENCY_CODE_OPTION, self::__( 'Currency' ), array( get_class(), 'display_currency_code_field' ), $page, $section );

		// add_settings_section( 'gb_logs', self::__( 'Logs' ), array( $this, 'display_settings_logs' ), $page );
	}

	public function display_api_mode_field() {
		echo '<label><input type="radio" name="'.self::API_MODE_OPTION.'" value="'.self::MODE_LIVE.'" '.checked( self::MODE_LIVE, self::$api_mode, FALSE ).'/> '.self::__( 'Live' ).'</label><br />';
		echo '<label><input type="radio" name="'.self::API_MODE_OPTION.'" value="'.self::MODE_TEST.'" '.checked( self::MODE_TEST, self::$api_mode, FALSE ).'/> '.self::__( 'Sandbox' ).'</label>';
	}

	public function display_api_merchantid_field() {
		echo '<input type="text" name="'.self::API_MERCHANTID_OPTION.'" value="'.self::$api_merchantid.'" size="80" />';
	}

	public function display_api_secretcode_field() {
		echo '<input type="text" name="'.self::API_SECRETCODE_OPTION.'" value="'.self::$api_secretcode.'" size="80" />';
	}

	public function display_api_paymentgatewayid_field() {
		echo '<input type="text" name="'.self::API_PAYMENTGATEWAYID_OPTION.'" value="'.self::$api_paymentgatwayid.'" size="80" />';
	}

	public function display_api_pagetype_field() {
		echo '<input type="text" name="'.self::API_PAGETYPE_OPTION.'" value="'.self::$api_pagetype.'" size="10" />';
	}

	public function display_api_skipReceiptPage_field() {
		echo '<label><input type="radio" name="'.self::API_SKIPRECEIPTPAGE_OPTION.'" value="0" '.checked( '0', self::$api_skipReceiptPage, FALSE ).'/> '.self::__( 'No' ).'</label><br />';
		echo '<label><input type="radio" name="'.self::API_SKIPRECEIPTPAGE_OPTION.'" value="1" '.checked( '1', self::$api_skipReceiptPage, FALSE ).'/> '.self::__( 'Yes' ).'</label>';
	}

	public function display_api_merchantlogo_field() {
		echo '<input type="text" name="'.self::API_MERCHANTLOGO_OPTION.'" value="'.self::$api_merchantlogo.'" size="80" />';
	}

	public function display_cancel_field() {
		echo '<input type="text" name="'.self::CANCEL_URL_OPTION.'" value="'.self::$cancel_url.'" size="80" />';
	}

	public function display_lang_field() {
		echo '<input type="text" name="'.self::LANG_OPTION.'" value="'.self::$page_language.'" size="2" />';
	}

	public function display_currency_code_field() {
		echo '<input type="text" name="'.self::CURRENCY_CODE_OPTION.'" value="'.self::$currency_code.'" size="5" />';
	}

	public function display_settings_logs() {
		?>
		<script type="text/javascript">
	        jQuery(document).ready(function() {
                jQuery('#debug_wrap').hide();
                jQuery('#logs_link').click(function() {
                        jQuery('#debug_wrap').toggle();
                });
	        });
		</script>
		<?php
		echo '<a id="logs_link" class="button">'.self::__( 'Logs' ).'</a>';
		echo '<div id="debug_wrap"><pre>'.print_r( get_option( self::LOGS ), true ).'</pre></div>';
	}

	public function cart_controls( $controls ) {
		$controls['checkout'] = '<input type="submit" class="form-submit alignright checkout_next_step" value="Borgun" name="gb_cart_action-checkout" />';
		return $controls;
	}


	public function payment_controls( $controls, Group_Buying_Checkouts $checkout ) {
		if ( isset( $controls['review'] ) ) {
			$style = 'style="box-shadow: none;-moz-box-shadow: none;-webkit-box-shadow: none; display: block; width: 160px; height: 81px; background-color: transparent; background-image: url(http://vps-1083582-7290.manage.myhosting.com/foodmonster/wp-content/themes/foodmonster/img/cardlogo.png); background-repeat:no-repeat no-repeat; background-position: 0 0; padding: 42px 0 0 0; border: none; cursor: pointer; text-indent: -9000px; margin-top: 12px;"';
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
			error_log( $message );
		}
	}
}



Group_Buying_First_Data::register();