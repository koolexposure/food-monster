<?php

/**
 * NMI credit card payment processor.
 * 
 * @package GBS
 * @subpackage Payment Processing_Processor
 */
class Group_Buying_NMI extends Group_Buying_Credit_Card_Processors {
	const API_ENDPOINT = 'https://secure.networkmerchants.com/api/transact.php';
	const API_USERNAME_OPTION = 'gb_nmi_username';
	const API_PASSWORD_OPTION = 'gb_nmi_password';
	const PAYMENT_METHOD = 'Credit (NMI)';
	protected static $instance;
	private $api_username = '';
	private $api_password = '';

	public static function get_instance() {
		if ( !( isset( self::$instance ) && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function get_api_url() {
		return self::API_ENDPOINT;
	}

	public function get_payment_method() {
		return self::PAYMENT_METHOD;
	}

	protected function __construct() {
		parent::__construct();
		$this->api_username = get_option( self::API_USERNAME_OPTION, '' );
		$this->api_password = get_option( self::API_PASSWORD_OPTION, '' );

		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );
		add_action( 'purchase_completed', array( $this, 'complete_purchase' ), 10, 1 );

		// Limitations
		add_filter( 'group_buying_template_meta_boxes/deal-expiration.php', array( $this, 'display_exp_meta_box' ), 10 );
		add_filter( 'group_buying_template_meta_boxes/deal-price.php', array( $this, 'display_price_meta_box' ), 10 );
		add_filter( 'group_buying_template_meta_boxes/deal-limits.php', array( $this, 'display_limits_meta_box' ), 10 );
	}

	public static function register() {
		self::add_payment_processor( __CLASS__, self::__( 'NMI' ) );
	}

	public static function accepted_cards() {
		$accepted_cards = array(
				'visa', 
				'mastercard', 
				'amex', 
				'diners', 
				'discover', 
				'jcb', 
				'maestro'
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

		$post_data = $this->dp_data( $checkout, $purchase );
		if ( self::DEBUG ) error_log( '----------NMI Response----------' . print_r( $post_data, true ) );
		$post_string = "";

		foreach ( $post_data as $key => $value ) {
			$post_string .= "{$key}=".urlencode( $value )."&";
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
		$response = wp_parse_args( wp_remote_retrieve_body( $response ) );

		$response_code = $response['response']; // The response we want to validate on

		if ( self::DEBUG ) error_log( '----------NMI Response----------' . print_r( $response, TRUE ) );
		if ( $response_code != 1 ) {
			$this->set_error_messages( $response['responsetext'] );
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
				'amount' => $post_data['amount'],
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
	private function dp_data( Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
		
		$cart = $checkout->get_cart();
		$user = get_userdata( $purchase->get_user() );
		$local_billing = $this->get_checkout_local( $checkout, $purchase, TRUE );
		$subtotal = gb_get_number_format( $purchase->get_subtotal( $this->get_payment_method() ) );

		$DPdata = array();
		$DPdata['username'] = $this->api_username;
		$DPdata['password'] = $this->api_password;

		$DPdata['type'] = 'sale';
		$DPdata['payment'] = 'creditcard';

		$DPdata['ccnumber'] = $this->cc_cache['cc_number'];
		$DPdata['ccexp'] = $this->cc_cache['cc_expiration_month'] . '/' . $this->cc_cache['cc_expiration_year'];
		$DPdata['cvv'] = $this->cc_cache['cc_cvv'];

		$DPdata['firstname'] = $checkout->cache['billing']['first_name'];
		$DPdata['lastname'] = $checkout->cache['billing']['last_name'];
		$DPdata['address1'] = $checkout->cache['billing']['street'];
		$DPdata['city'] = $checkout->cache['billing']['city'];
		$DPdata['state'] = $checkout->cache['billing']['zone'];
		$DPdata['zip'] = $checkout->cache['billing']['postal_code'];
		$DPdata['phone'] = $checkout->cache['billing']['phone'];

		$DPdata['email'] = $user->user_email;
		$DPdata['x_cust_id'] = $user->ID;

		$DPdata['orderid'] = $purchase->get_id();

		$DPdata['amount'] = gb_get_number_format( $purchase->get_total( $this->get_payment_method() ) );
		$DPdata['shipping'] = gb_get_number_format( $purchase->get_shipping_total( $this->get_payment_method() ) );
		$DPdata['tax'] = gb_get_number_format( $purchase->get_tax_total( $this->get_payment_method() ) );

		$line_items = 'line_item=';
		if ( $AIMdata['amount'] == ( $cart->get_shipping_total() + $cart->get_tax_total() ) ) {
			$line_items .= $purchase->get_id().'<|>'.gb__('Cart Totals').'<|><|>1<|>'.$DPdata['amount'].'<|>'.$DPdata['tax'].'&';
			$DPdata['shipping'] = gb_get_number_format(0);
			$DPdata['tax'] = gb_get_number_format(0);
		} else {
			foreach ( $purchase->get_products() as $item ) {
				if ( isset( $item['payment_method'][$this->get_payment_method()] ) ) {
					$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
					$tax = $deal->get_tax( $local_billing );
					$tax = ( !empty( $tax ) && $tax > '0' ) ? 'Y' : 'N' ;
					$line_items .= $item['deal_id'].'<|>'.substr( $deal->get_slug(), 0, 31 ).'<|><|>'.$item['quantity'].'<|>'.gb_get_number_format( $item['unit_price'] ).'<|>'.$tax.'&';
				}
			}
		}
		$DPdata['orderdescription'] = rtrim( $line_items, "&" );

		if ( isset( $checkout->cache['shipping'] ) ) {
			$DPdata['shipping_firstname'] = $checkout->cache['shipping']['first_name'];
			$DPdata['shipping_lastname'] = $checkout->cache['shipping']['last_name'];
			$DPdata['shipping_address1'] = $checkout->cache['shipping']['street'];
			$DPdata['shipping_city'] = $checkout->cache['shipping']['city'];
			$DPdata['shipping_state'] = $checkout->cache['shipping']['zone'];
			$DPdata['x_ship_to_zip'] = $checkout->cache['shipping']['postal_code'];
			$DPdata['shipping_country'] = $checkout->cache['shipping']['country'];
		}

		$DPdata = apply_filters( 'gb_nmi_nvp_data', $DPdata );

		//$DPdata = array_map('rawurlencode', $DPdata);
		return $DPdata;
	}

	public function register_settings() {
		$page = Group_Buying_Payment_Processors::get_settings_page();
		$section = 'gb_authorizenet_settings';
		add_settings_section( $section, self::__( 'NMI' ), array( $this, 'display_settings_section' ), $page );
		register_setting( $page, self::API_USERNAME_OPTION );
		register_setting( $page, self::API_PASSWORD_OPTION );
		add_settings_field( self::API_USERNAME_OPTION, self::__( 'Username' ), array( $this, 'display_api_username_field' ), $page, $section );
		add_settings_field( self::API_PASSWORD_OPTION, self::__( 'Password' ), array( $this, 'display_api_password_field' ), $page, $section );
		//add_settings_field(null, self::__('Currency'), array($this, 'display_currency_code_field'), $page, $section);
	}

	public function display_api_username_field() {
		echo '<input type="text" name="'.self::API_USERNAME_OPTION.'" value="'.$this->api_username.'" size="80" />';
	}

	public function display_api_password_field() {
		echo '<input type="text" name="'.self::API_PASSWORD_OPTION.'" value="'.$this->api_password.'" size="80" />';
	}

	public function display_currency_code_field() {
		echo 'Specified in your NMI Merchant Interface.';
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
Group_Buying_NMI::register();
