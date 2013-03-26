<?php

/**
 * This payment processor manages a combination of PayPal Website Payments
 * Pro and PayPal Express Checkout.
 *
 * The basic technique here is to only load one of the payment processors
 * whenever possible, and just let it handle all the real work. The job of
 * this class is just to track which to instantiate when.
 * 
 * @package GBS
 * @subpackage Payment Processing_Processor
 */
class Group_Buying_Paypal_Hybrid extends Group_Buying_Payment_Processors {
	const PAYMENT_METHOD = 'PayPal';
	const ENABLED_PROCESSORS_OPTION = 'gb_paypal_hybrid_processors';
	private static $instance;
	private $use_ec = TRUE;
	private $use_wpp = TRUE; 
	private $ec; /** @var Group_Buying_Paypal_EC */
	private $wpp; /** @var Group_Buying_Paypal_WPP */
	private $current_payment_processor = '';

	public static function get_instance() {
		if ( !( isset( self::$instance ) && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		// If only one is enabled, just let it do the work. No sense getting in the way
		if ( self::$instance->ec && !self::$instance->wpp ) {
			return self::$instance->ec;
		}
		if ( self::$instance->wpp && !self::$instance->ec ) {
			return self::$instance->wpp;
		}

		// both exist, so we'll have to manage them a bit (ever so transparently, of course)
		return self::$instance;
	}

	/**
	 * No reason this should ever be called, but included for completeness
	 *
	 * @return string
	 */
	public function get_payment_method() {
		return self::PAYMENT_METHOD;
	}

	protected function __construct() {
		// don't want to call the parent constructor, we would just have to undo it
		// parent::__construct();
		$options = get_option( self::ENABLED_PROCESSORS_OPTION, 'both' );
		$this->use_ec = ( $options == 'ec' || $options == 'both' );
		$this->use_wpp = ( $options == 'wpp' || $options == 'both' );
		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );
		add_action( 'gb_processing_cart', array( $this, 'load_enabled_processors' ), 10, 0 );
		add_action( self::CRON_HOOK, array( $this, 'load_enabled_processors' ), -100, 0 );
		add_filter( 'gb_payment_fields', array( $this, 'payment_fields' ), 10, 3 );
		add_filter( 'gb_checkout_payment_controls', array( $this, 'payment_controls' ), 10, 2 );


		// always load both processors on admin pages
		if ( is_admin() ) {
			$this->get_ec();
			$this->get_wpp();

			// WPP options are a subset of the EC options, so only display the latter
			remove_action( 'admin_init', array( $this->wpp, 'register_settings' ), 10, 0 );
			add_action( 'admin_init', array( $this, 'edit_settings_section' ), 100, 0 );
		}

		add_action( 'gb_load_cart', array( $this, 'load_payment_processor_for_checkout' ), 0, 2 );

		// let the sub-processors handle this
		//add_action('purchase_completed', array($this, 'capture_purchase'), 10, 1);
		// let the sub-processors handle this
		//add_action(self::CRON_HOOK, array($this, 'capture_pending_payments'));
	}

	public function payment_fields( $fields, $payment_processor_class, $checkout ) {
		if ( $this->use_ec && $this->use_wpp ) {
			$fields['payment_method'] = array(
				'type' => 'radios',
				'weight' => -10,
				'label' => self::__( 'Payment Method' ),
				'required' => TRUE,
				'options' => array(
					'wpp' => self::__( 'Credit Card' ),
					'ec' => '<img src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" title="Paypal Payments" id="paypal_icon"/>',
				),
				'default' => 'wpp',
			);
		}
		return $fields;
	}

	public function payment_controls( $controls, Group_Buying_Checkouts $checkout ) {
		if ( $this->use_ec && $this->use_wpp && isset( $controls['review'] ) ) {
			$controls['review'] = str_replace( self::__( 'Review' ), self::__( 'Continue' ), $controls['review'] );
		}
		return $controls;
	}

	/**
	 * Change the name of the EC settings section
	 *
	 * @return void
	 */
	public function edit_settings_section() {
		global $wp_settings_sections;
		$page = Group_Buying_Payment_Processors::get_settings_page();
		$wp_settings_sections[$page]['gb_paypal_settings']['title'] = self::__( 'PayPal API Settings' );
	}

	/**
	 * Load any enabled processors
	 *
	 * @return void
	 */
	public function load_enabled_processors(  ) {
		if ( $this->use_ec ) {
			$this->get_ec();
		}
		if ( $this->use_wpp ) {
			$this->get_wpp();
		}
	}

	/**
	 * We're on the checkout page, so we need to load one of the payment processors (but not both)
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Cart $cart
	 * @return void
	 */
	public function load_payment_processor_for_checkout( Group_Buying_Checkouts $checkout, Group_Buying_Cart $cart ) {
		// if only one is enabled, the decision is easy
		if ( !$this->use_ec ) {
			$this->get_wpp();
			$this->current_payment_processor = 'wpp';
			return;
		}
		if ( !$this->use_wpp ) {
			$this->get_ec();
			$this->current_payment_processor = 'ec';
			return;
		}

		// both are enabled, we have to make a decision

		// if the decision's already been made
		if ( $this->current_payment_processor == 'ec' ) {
			$this->get_ec();
			$this->current_payment_processor = 'ec';
			return;
		} elseif ( $this->current_payment_processor == 'wpp' ) {
			$this->get_wpp();
			$this->current_payment_processor = 'wpp';
			return;
		}


		// if the user just chose to use EC on the checkout page
		if ( isset( $_POST['gb_credit_payment_method'] ) && $_POST['gb_credit_payment_method'] == 'ec' ) {
			$this->get_ec();
			$this->current_payment_processor = 'ec';
			return;
		}

		// if we decided on EC at an earlier step, it will be stored in $_REQUEST['paypal_payment_processor']
		if ( isset( $_REQUEST['paypal_payment_processor'] ) && $_REQUEST['paypal_payment_processor'] == 'ec' ) {
			$this->get_ec();
			$this->current_payment_processor = 'ec';
			return;
		}

		// EC just sent the user back with a token, must be using EC
		if ( isset( $_GET['token'] ) && isset( $_GET['PayerID'] ) ) {
			$this->get_ec();
			$this->current_payment_processor = 'ec';
			return;
		}

		// clearly not EC at this point, default to WPP
		$this->get_wpp();
		$this->current_payment_processor = 'wpp';
		return;
	}

	/**
	 * Load EC, and remove hooks that are inappropriate in a multi-processor environment
	 *
	 * @return Group_Buying_Paypal_EC
	 */
	private function get_ec() {
		if ( !$this->ec ) {
			$this->ec = Group_Buying_Paypal_EC::get_instance();
		}
		if ( $this->use_ec && $this->use_wpp ) {
			// override a few hooks
			//remove_filter( 'gb_cart_controls', array($this->ec, 'cart_controls'), 10, 1);
			//add_filter( 'gb_cart_controls', array($this, 'cart_controls'), 10, 1);

			//remove_action('gb_proceeding_to_checkout', array($this->ec,'send_offsite'), 10, 1);
			//add_action('gb_proceeding_to_checkout', array($this, 'pre_checkout'), 10, 1);

			add_filter( 'gb_checkout_panes', array( $this, 'ec_cache_checkout_pane' ), 10, 1 );
		}
		return $this->ec;
	}

	/**
	 * A hidden checkout pane to track which processor was called
	 *
	 * @param array   $panes
	 * @return array
	 */
	public function ec_cache_checkout_pane( $panes ) {
		$panes['paypal_ec_cache'] = array(
			'weight' => 10,
			'body' => '<input type="hidden" value="ec" name="paypal_payment_processor" />',
		);
		return $panes;
	}

	/**
	 * Load WPP
	 *
	 * @return Group_Buying_Paypal_WPP
	 */
	private function get_wpp() {
		if ( !$this->wpp ) {
			$this->wpp = Group_Buying_Paypal_WPP::get_instance();
		}
		return $this->wpp;
	}

	public static function register() {
		self::add_payment_processor( __CLASS__, self::__( 'PayPal Payments Pro' ) );
	}

	/**
	 * Add options to choose which payment processors to enable
	 *
	 * @return void
	 */
	public function register_settings() {
		$page = Group_Buying_Payment_Processors::get_settings_page();
		$section = 'gb_paypal_hybrid_settings';
		add_settings_section( $section, self::__( 'PayPal' ), array( $this, 'display_settings_section' ), $page );
		register_setting( $page, self::ENABLED_PROCESSORS_OPTION );
		add_settings_field( self::ENABLED_PROCESSORS_OPTION, self::__( 'Payment Methods' ), array( $this, 'display_payment_methods_field' ), $page, $section );
	}

	public function display_payment_methods_field() {
		echo '<p><label><input type="radio" name="'.self::ENABLED_PROCESSORS_OPTION.'" value="ec" '.checked( TRUE, $this->use_ec && !$this->use_wpp, FALSE ).' /> '.self::__( 'Payments Standard (off site)' ).'</label></p>';
		echo '<p><label><input type="radio" name="'.self::ENABLED_PROCESSORS_OPTION.'" value="wpp" '.checked( TRUE, $this->use_wpp && !$this->use_ec, FALSE ).' /> '.self::__( 'Payments Pro (on site)' ).'</label></p>';
		echo '<p><label><input type="radio" name="'.self::ENABLED_PROCESSORS_OPTION.'" value="both" '.checked( TRUE, $this->use_wpp && $this->use_ec, FALSE ).' /> '.self::__( 'Both' ).'</label></p>';

	}

	/**
	 * No reason this should ever be called...
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Purchase $purchase
	 * @return Group_Buying_Payment|bool FALSE if the payment failed, otherwise a Payment object
	 */
	public function process_payment( Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
		return FALSE;
	}

	/**
	 * Display controls for both methods on the cart page
	 *
	 * @param array   $controls
	 * @return array
	 */
	public function cart_controls( $controls ) {
		//$controls['checkout'] = '<input type="submit" class="form-submit checkout_next_step" value="'.self::__('Checkout with Credit Card').'" name="gb_cart_action-checkout" />';
		//$controls['express_checkout'] = $this->ec->get_paypal_button();
		return $controls;
	}

	/**
	 * If the user chose to pay with EC, send them away
	 *
	 * @param Group_Buying_Cart $cart
	 * @return void
	 */
	public function pre_checkout( Group_Buying_Cart $cart ) {
		if ( $_POST['gb_cart_action-checkout'] == 'Paypal' ) {
			$this->ec->send_offsite( $cart );
		}
	}

	/**
	 *
	 *
	 * @param Group_Buying_Payment $payment
	 * @return void
	 */
	public function verify_recurring_payment( Group_Buying_Payment $payment ) {
		$method = $payment->get_payment_method();
		$this->load_enabled_processors();
		if ( $this->use_ec && $this->ec->get_payment_method() == $method ) {
			$this->ec->verify_recurring_payment( $payment );
			return;
		} elseif ( $this->use_wpp && $this->wpp->get_payment_method() == $method ) {
			$this->wpp->verify_recurring_payment( $payment );
			return;
		}
		// else, it's not handled here
	}
}
Group_Buying_Paypal_Hybrid::register();
