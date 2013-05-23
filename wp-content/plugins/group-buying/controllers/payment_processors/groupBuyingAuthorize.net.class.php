<?php
/**
 * This class provides a model for a payment processor. To implement a
 * different credit card payment gateway, create a new class that extends
 * Group_Buying_Credit_Card_Processors. The new class should implement
 * the following methods (at a minimum):
 *  - get_instance()
 *  - process_payment()
 *  - register()
 *  - get_payment_method()
 *
 * You may also want to register some settings for the Payment Options page
 * @package GBS
 * @subpackage Payment Processing_Processor
 */
class Group_Buying_Authorize_Net extends Group_Buying_Credit_Card_Processors {
	const API_ENDPOINT_SANDBOX = 'https://test.authorize.net/gateway/transact.dll';
	const API_ENDPOINT_LIVE = 'https://secure.authorize.net/gateway/transact.dll';
	const MODE_TEST = 'sandbox';
	const MODE_LIVE = 'live';
	const API_USERNAME_OPTION = 'gb_authorize_net_username';
	const API_PASSWORD_OPTION = 'gb_authorize_net_password';
	const API_MODE_OPTION = 'gb_authorize_net_mode';
	const PAYMENT_METHOD = 'Credit (Authorize.Net)';
	protected static $instance;
	private $api_mode = self::MODE_TEST;
	private $api_username = '';
	private $api_password = '';

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
		$this->api_mode = get_option( self::API_MODE_OPTION, self::MODE_TEST );

		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );
		add_action( 'purchase_completed', array( $this, 'complete_purchase' ), 10, 1 );

		// Limitations
		add_filter( 'group_buying_template_meta_boxes/deal-expiration.php', array( $this, 'display_exp_meta_box' ), 10 );
		add_filter( 'group_buying_template_meta_boxes/deal-price.php', array( $this, 'display_price_meta_box' ), 10 );
		add_filter( 'group_buying_template_meta_boxes/deal-limits.php', array( $this, 'display_limits_meta_box' ), 10 );
	}

	public static function register() {
		self::add_payment_processor( __CLASS__, self::__( 'Authorize.net' ) );
	}

	public static function accepted_cards() {
		$accepted_cards = array(
				'visa', 
				'mastercard', 
				'amex', 
				'diners', 
				// 'discover', 
				'jcb', 
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

		$post_data = $this->aim_data( $checkout, $purchase );
		if ( self::DEBUG ) error_log( '----------Authorize Net Response----------' . print_r( $post_data, true ) );
		$post_string = "";

		foreach ( $post_data as $key => $value ) {
			if ( $key == 'x_line_item' ) {
				$post_string .= "{$key}=".$value."&";
			} else {
				$post_string .= "{$key}=".urlencode( $value )."&";
			}
		}
		$post_string = rtrim( $post_string, "& " );
		if ( self::DEBUG ) error_log( "post_string: " . print_r( $post_string, true ) );
		$response = wp_remote_post( $this->get_api_url(), array(
				'method' => 'POST',
				'body' => $post_string,
				'timeout' => apply_filters( 'http_request_timeout', 15 ),
				'sslverify' => false
			) );
		if ( is_wp_error( $response ) ) {
			return FALSE;
		}

		$response = explode( $post_data['x_delim_char'], $response['body'] );
		$response_code = $response[0]; // The response we want to validate on
		if ( self::DEBUG ) error_log( '----------Authorize Net Response----------' . print_r( $response, TRUE ) );
		if ( $response_code != 1 ) {
			$this->set_error_messages( $response[3] );
			return FALSE;
		}

		$deal_info = array(); // creating purchased products array for payment below
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
				'amount' => $post_data['x_amount'],
				'data' => array(
					'api_response' => $response,
					'masked_cc_number' => $this->mask_card_number( $this->cc_cache['cc_number'] ), // save for possible credits later
				),
				'deals' => $deal_info,
				'shipping_address' => $shipping_address,
			), Group_Buying_Payment::STATUS_AUTHORIZED );
		if ( !$payment_id ) {
			return FALSE;
		}
		$payment = Group_Buying_Payment::get_instance( $payment_id );
		do_action( 'payment_authorized', $payment );
		$payment->set_data( $response );
		return $payment;
	}

	public function process_api_payment( Group_Buying_Purchase $purchase, $cc_data, $amount, $cart, $billing_address, $shipping_address, $data ) {

		$user = get_userdata( $purchase->get_user() );

		$aim_data = array();
		$aim_data['x_login'] = $this->api_username;
		$aim_data['x_tran_key'] = $this->api_password;

		$aim_data['x_version'] = '3.1';
		$aim_data['x_delim_data'] = 'TRUE';
		$aim_data['x_delim_char'] = '|';
		$aim_data['x_relay_response'] = 'FALSE';
		$aim_data['x_type'] = 'AUTH_CAPTURE';
		$aim_data['x_method'] = 'CC';

		$aim_data['x_card_num'] = $cc_data['cc_number'];
		$aim_data['x_exp_date'] = $cc_data['cc_expiration_month'] . $cc_data['cc_expiration_year'];
		$aim_data['x_card_code'] = $cc_data['cc_cvv'];

		$aim_data['x_amount'] = gb_get_number_format( $amount );

		$aim_data['x_first_name'] = $billing_address['first_name'];
		$aim_data['x_last_name'] = $billing_address['last_name'];
		$aim_data['x_address'] = $billing_address['street'];
		$aim_data['x_city'] = $billing_address['city'];
		$aim_data['x_state'] = $billing_address['zone'];
		$aim_data['x_zip'] = $billing_address['postal_code'];
		$aim_data['x_phone'] = $billing_address['phone'];

		$aim_data['x_email'] = $user->user_email;
		$aim_data['x_cust_id'] = $user->ID;

		$aim_data['x_invoice_num'] = $purchase->get_id();

		$line_items = '';
		foreach ( $purchase->get_products() as $item ) {
			$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
			$tax = $deal->get_tax( $local_billing );
			$tax = ( !empty( $tax ) && $tax > '0' ) ? 'Y' : 'N' ;
			$line_items .= $item['deal_id'].'<|>'.substr( $deal->get_slug(), 0, 31 ).'<|><|>'.$item['quantity'].'<|>'.gb_get_number_format( $item['unit_price'] ).'<|>'.$tax.'&x_line_item=';
		}
		$aim_data['x_line_item'] = rtrim( $line_items, "&x_line_item=" );

		if ( !empty( $shipping_address ) ) {
			$aim_data['x_ship_to_first_name'] = $shipping_address['first_name'];
			$aim_data['x_ship_to_last_name'] = $shipping_address['last_name'];
			$aim_data['x_ship_to_address'] = $shipping_address['street'];
			$aim_data['x_ship_to_city'] = $shipping_address['city'];
			$aim_data['x_ship_to_state'] = $shipping_address['zone'];
			$aim_data['x_ship_to_zip'] = $shipping_address['postal_code'];
			$aim_data['x_ship_to_country'] = $shipping_address['country'];
		}

		if ( $this->api_mode == self::MODE_TEST ) {
			$aim_data['x_test_request'] = 'TRUE';
		}

		$aim_data = apply_filters( 'gb_authorize_net_nvp_data', $aim_data );


		if ( self::DEBUG ) error_log( '----------Authorize Net Response----------' . print_r( $aim_data, true ) );
		
		// Format
		$post_string = "";
		foreach ( $aim_data as $key => $value ) {
			if ( $key == 'x_line_item' ) {
				$post_string .= "{$key}=".$value."&";
			} else {
				$post_string .= "{$key}=".urlencode( $value )."&";
			}
		}
		$post_string = rtrim( $post_string, "& " );
		if ( self::DEBUG ) error_log( "post_string: " . print_r( $post_string, true ) );

		// Post
		$response = wp_remote_post( $this->get_api_url(), array(
				'method' => 'POST',
				'body' => $post_string,
				'timeout' => apply_filters( 'http_request_timeout', 15 ),
				'sslverify' => false
			) );
		if ( is_wp_error( $response ) ) {
			return FALSE;
		}

		// Response
		$response = explode( $post_data['x_delim_char'], $response['body'] );
		$response_code = $response[0]; // The response we want to validate on
		if ( self::DEBUG ) error_log( '----------Authorize Net Response----------' . print_r( $response, TRUE ) );
		if ( $response_code != 1 ) {
			$this->set_error_messages( $response[3] );
			return FALSE;
		}

		// Build payment vars
		$deal_info = array(); // creating purchased products array for payment below
		foreach ( $purchase->get_products() as $item ) {
			if ( !isset( $deal_info[$item['deal_id']] ) ) {
				$deal_info[$item['deal_id']] = array();
			}
			$deal_info[$item['deal_id']][] = $item;
		}

		// create payments
		$payment_id = Group_Buying_Payment::new_payment( array(
				'payment_method' => $this->get_payment_method(),
				'purchase' => $purchase->get_id(),
				'amount' => $amount,
				'data' => array(
					'api_response' => $response,
					'masked_cc_number' => $this->mask_card_number( $cc_data['cc_number'] ), // save for possible credits later
				),
				'deals' => $deal_info,
				'shipping_address' => $shipping_address,
			), Group_Buying_Payment::STATUS_AUTHORIZED );
		if ( !$payment_id ) {
			return FALSE;
		}
		$payment = Group_Buying_Payment::get_instance( $payment_id );
		do_action( 'payment_authorized', $payment );
		$payment->set_data( $response );
		return $payment;
	}

	/**
	 * Complete the purchase after the process_payment action, otherwise vouchers will not be activated.
	 *
	 * @param Group_Buying_Purchase $purchase
	 * @return void
	 */
	public function complete_purchase( Group_Buying_Purchase $purchase ) {
		$items_captured = array(); // Creating simple array of items that are captured
		foreach ( $purchase->get_products() as $item ) {
			$items_captured[] = $item['deal_id'];
		}
		$payments = Group_Buying_Payment::get_payments_for_purchase( $purchase->get_id() );
		foreach ( $payments as $payment_id ) {
			$payment = Group_Buying_Payment::get_instance( $payment_id );
			do_action( 'payment_captured', $payment, $items_captured );
			do_action( 'payment_complete', $payment );
			$payment->set_status( Group_Buying_Payment::STATUS_COMPLETE );
		}
	}


	/**
	 * Grabs error messages from a Authorize response and displays them to the user
	 *
	 * @param array   $response
	 * @param bool    $display
	 * @return void
	 */
	private function set_error_messages( $response, $display = TRUE ) {
		if ( $display ) {
			self::set_message( $response, self::MESSAGE_STATUS_ERROR );
		} else {
			error_log( $response );
		}
	}

	/**
	 * Build the NVP data array for submitting the current checkout to Authorize as an Authorization request
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Purchase $purchase
	 * @return array
	 */
	private function aim_data( Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
		if ( self::DEBUG ) error_log( "checkout: " . print_r( $checkout->cache, true ) );
		
		$cart = $checkout->get_cart();
		$user = get_userdata( $purchase->get_user() );
		$local_billing = $this->get_checkout_local( $checkout, $purchase, TRUE );

		$AIMdata= array();
		$AIMdata['x_login'] = $this->api_username;
		$AIMdata['x_tran_key'] = $this->api_password;

		$AIMdata['x_version'] = '3.1';
		$AIMdata['x_delim_data'] = 'TRUE';
		$AIMdata['x_delim_char'] = '|';
		$AIMdata['x_relay_response'] = 'FALSE';
		$AIMdata['x_type'] = 'AUTH_CAPTURE';
		$AIMdata['x_method'] = 'CC';

		$AIMdata['x_card_num'] = $this->cc_cache['cc_number'];
		$AIMdata['x_exp_date'] = $this->cc_cache['cc_expiration_month'] . $this->cc_cache['cc_expiration_year'];
		$AIMdata['x_card_code'] = $this->cc_cache['cc_cvv'];

		$AIMdata['x_amount'] = gb_get_number_format( $purchase->get_total( $this->get_payment_method() ) );

		$AIMdata['x_first_name'] = $checkout->cache['billing']['first_name'];
		$AIMdata['x_last_name'] = $checkout->cache['billing']['last_name'];
		$AIMdata['x_address'] = $checkout->cache['billing']['street'];
		$AIMdata['x_city'] = $checkout->cache['billing']['city'];
		$AIMdata['x_state'] = $checkout->cache['billing']['zone'];
		$AIMdata['x_zip'] = $checkout->cache['billing']['postal_code'];
		$AIMdata['x_phone'] = $checkout->cache['billing']['phone'];

		$AIMdata['x_email'] = $user->user_email;
		$AIMdata['x_cust_id'] = $user->ID;

		$AIMdata['x_invoice_num'] = $purchase->get_id();

		$AIMdata['x_freight'] = gb_get_number_format( $purchase->get_shipping_total( $this->get_payment_method() ) );
		$AIMdata['x_tax'] = gb_get_number_format( $purchase->get_tax_total( $this->get_payment_method() ) );

		$line_items = '';
		if ( $AIMdata['x_amount'] == ( $cart->get_shipping_total() + $cart->get_tax_total() ) ) {
			$line_items .= $purchase->get_id().'<|>'.gb__('Cart Totals').'<|><|>1<|>'.$AIMdata['x_amount'].'<|>'.$AIMdata['x_tax'].'&x_line_item=';
			$AIMdata['x_freight'] = gb_get_number_format(0);
			$AIMdata['x_tax'] = gb_get_number_format(0);
		} else {
			foreach ( $purchase->get_products() as $item ) {
				if ( isset( $item['payment_method'][$this->get_payment_method()] ) ) {
					$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
					$tax = $deal->get_tax( $local_billing );
					$tax = ( !empty( $tax ) && $tax > '0' ) ? 'Y' : 'N' ;
					$line_items .= $item['deal_id'].'<|>'.substr( $deal->get_slug(), 0, 31 ).'<|><|>'.$item['quantity'].'<|>'.gb_get_number_format( $item['unit_price'] ).'<|>'.$tax.'&x_line_item=';
				}
			}
		}
		$AIMdata['x_line_item'] = rtrim( $line_items, "&x_line_item=" );

		if ( isset( $checkout->cache['shipping'] ) ) {
			$AIMdata['x_ship_to_first_name'] = $checkout->cache['shipping']['first_name'];
			$AIMdata['x_ship_to_last_name'] = $checkout->cache['shipping']['last_name'];
			$AIMdata['x_ship_to_address'] = $checkout->cache['shipping']['street'];
			$AIMdata['x_ship_to_city'] = $checkout->cache['shipping']['city'];
			$AIMdata['x_ship_to_state'] = $checkout->cache['shipping']['zone'];
			$AIMdata['x_ship_to_zip'] = $checkout->cache['shipping']['postal_code'];
			$AIMdata['x_ship_to_country'] = $checkout->cache['shipping']['country'];
		}

		if ( $this->api_mode == self::MODE_TEST ) {
			$AIMdata['x_test_request'] = 'TRUE';
		}

		$AIMdata= apply_filters( 'gb_authorize_net_nvp_data', $AIMdata);

		//$AIMdata= array_map('rawurlencode', $AIMdata);
		return $AIMdata;
	}

	public function register_settings() {
		$page = Group_Buying_Payment_Processors::get_settings_page();
		$section = 'gb_authorizenet_settings';
		add_settings_section( $section, self::__( 'Authorize.net' ), array( $this, 'display_settings_section' ), $page );
		register_setting( $page, self::API_MODE_OPTION );
		register_setting( $page, self::API_USERNAME_OPTION );
		register_setting( $page, self::API_PASSWORD_OPTION );
		add_settings_field( self::API_MODE_OPTION, self::__( 'Mode' ), array( $this, 'display_api_mode_field' ), $page, $section );
		add_settings_field( self::API_USERNAME_OPTION, self::__( 'API Login (Username)' ), array( $this, 'display_api_username_field' ), $page, $section );
		add_settings_field( self::API_PASSWORD_OPTION, self::__( 'Transaction Key (Password)' ), array( $this, 'display_api_password_field' ), $page, $section );
		//add_settings_field(null, self::__('Currency'), array($this, 'display_currency_code_field'), $page, $section);
	}

	public function display_api_username_field() {
		echo '<input type="text" name="'.self::API_USERNAME_OPTION.'" value="'.$this->api_username.'" size="80" />';
	}

	public function display_api_password_field() {
		echo '<input type="text" name="'.self::API_PASSWORD_OPTION.'" value="'.$this->api_password.'" size="80" />';
	}

	public function display_api_mode_field() {
		echo '<label><input type="radio" name="'.self::API_MODE_OPTION.'" value="'.self::MODE_LIVE.'" '.checked( self::MODE_LIVE, $this->api_mode, FALSE ).'/> '.self::__( 'Live' ).'</label><br />';
		echo '<label><input type="radio" name="'.self::API_MODE_OPTION.'" value="'.self::MODE_TEST.'" '.checked( self::MODE_TEST, $this->api_mode, FALSE ).'/> '.self::__( 'Sandbox' ).'</label>';
	}

	public function display_currency_code_field() {
		echo 'Specified in your Authorize.Net Merchant Interface.';
	}

	public function display_exp_meta_box() {
		return dirname( __FILE__ ) . '/meta-boxes/exp-only.php';
	}

	public function display_price_meta_box() {
		return dirname( __FILE__ ) . '/meta-boxes/no-dyn-price.php';
	}

	public function display_limits_meta_box() {
		return dirname( __FILE__ ) . '/meta-boxes/no-tipping.php';
	}
}
Group_Buying_Authorize_Net::register();
