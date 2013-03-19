<?php


/**
 * This payment processor manages a combination of onsite and
 * offsite payment processors
 *
 * The basic technique here is to only load one of the payment processors
 * whenever possible, and just let it handle all the real work. The job of
 * this class is just to track which to instantiate when.
 *
 * @package GBS
 * @subpackage Payment Processing_Processor
 */
class Group_Buying_Hybrid_Payment_Processor extends Group_Buying_Payment_Processors {
	const PAYMENT_METHOD = 'TBD';
	const ENABLED_PROCESSORS_OPTION = 'gb_hybrid_processors';

	/** @var Group_Buying_Hybrid_Payment_Processor */
	private static $instance;
	/** @var Group_Buying_Payment_Processors */
	private $current_payment_processor = NULL;

	public static function get_instance() {
		if ( !( isset( self::$instance ) && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		if ( self::$instance->current_payment_processor ) {
			return self::$instance->current_payment_processor;
		}

		// both exist, so we'll have to manage them a bit (ever so transparently, of course)
		return self::$instance;
	}

	protected function __construct() {
		// don't want to call the parent constructor, we would just have to undo it
		// parent::__construct();
		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );
		add_action( 'gb_processing_cart', array( $this, 'load_enabled_processors' ), 10, 0 );
		add_action( self::CRON_HOOK, array( $this, 'load_enabled_processors' ), -100, 0 );
		add_filter( 'gb_payment_fields', array( $this, 'payment_fields' ), 10, 3 );
		add_filter( 'gb_checkout_payment_controls', array( $this, 'payment_controls' ), 10, 2 );
		add_filter( 'gb_checkout_panes', array( $this, 'payment_method_cache_pane' ), 10, 2 );


		// always load all enabled processors on admin pages
		if ( is_admin() ) {
			$this->load_enabled_processors();
		}

		add_action( 'gb_load_cart', array( $this, 'load_payment_processor_for_checkout' ), 0, 2 );

		// let the sub-processors handle this
		// add_action('purchase_completed', array($this, 'capture_purchase'), 10, 1);
		// let the sub-processors handle this
		// add_action(self::CRON_HOOK, array($this, 'capture_pending_payments'));
	}

	/**
	 * No reason this should ever be called, but included for completeness
	 *
	 * @return string
	 */
	public function get_payment_method() {
		return self::PAYMENT_METHOD;
	}

	public function payment_fields( $fields, $payment_processor_class, $checkout ) {
		$enabled = $this->enabled_processors();
		if ( count($enabled) > 1 ) {
			$fields['payment_method'] = array(
				'type' => 'radios',
				'weight' => 0,
				'label' => self::__( 'Payment Method' ),
				'required' => TRUE,
				'options' => array(),
				'default' => '',
			);
			$registered = self::get_registered_processors();
			foreach ( $enabled as $class ) {
				if ( self::is_cc_processor($class) ) {
					$fields['payment_method']['default'] = 'credit';
					if ( is_callable( array( $class, 'accepted_cards' ) ) ) {
						$accepted_cards = call_user_func( array($class, 'accepted_cards') );
					} else {
						$accepted_cards = 0;
					}
					$fields['payment_method']['options']['credit'] = self::load_view_to_string( 'checkout/credit-card-option', array( 'accepted_cards' => $accepted_cards, 'class' => $class, 'registered' => $registered ) );
				} else {
					if ( is_callable( array( $class, 'checkout_icon' ) ) ) {
						$label = call_user_func( array($class, 'checkout_icon') );
					} elseif ( is_callable( array( $class, 'public_name' ) ) ) {
						$label = call_user_func( array($class, 'public_name') );
					} else {
						$label = $registered[$class];
					}
					$label = apply_filters('gbs_payment_processor_public_name', $label);

					$fields['payment_method']['options'][$class] = $label;
					if ( empty($fields['payment_method']['default']) ) {
						$fields['payment_method']['default'] = $class;
					}
				}
			}
		}
		return $fields;
	}

	public function payment_controls( $controls, Group_Buying_Checkouts $checkout ) {
		if ( FALSE && isset( $controls['review'] ) ) {
			$controls['review'] = str_replace( self::__( 'Review' ), self::__( 'Continue' ), $controls['review'] );
		}
		return $controls;
	}

	/**
	 * Load any enabled processors
	 *
	 * @return void
	 */
	public function load_enabled_processors() {
		$current = $this->enabled_processors();
		foreach ( $current as $class ) {
			$this->load_processor($class);
		}
	}

	public function payment_method_cache_pane( $panes, Group_Buying_Checkouts $checkout ) {
		if ( $this->current_payment_processor ) {
			$data = array(
				'type' => 'hidden',
				'value' => esc_attr( get_class($this->current_payment_processor) ),
			);
			if ( self::is_cc_processor($data['value']) ) {
				$data['value'] = 'credit';
			}
			$panes['payment_method_cache'] = array(
				'weight' => 0,
				'body' => gb_get_form_field( 'payment_method_cache', $data, 'credit' )
			);
		}
		return $panes;
	}

	/**
	 * @param string $class
	 * @return Group_Buying_Payment_Processors|NULL
	 */
	private function load_processor( $class ) {
		if ( class_exists($class) ) {
			$processor = call_user_func(array($class, 'get_instance'));
			return $processor;
		}
		return NULL;
	}

	public function enabled_processors() {
		$enabled = get_option(self::ENABLED_PROCESSORS_OPTION, array());
		if ( !is_array($enabled) ) { $enabled = array(); }
		return array_filter( $enabled );
	}

	/**
	 * We're on the checkout page, so we need to load one of the payment processors (but not both)
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @param Group_Buying_Cart $cart
	 * @return void
	 */
	public function load_payment_processor_for_checkout( Group_Buying_Checkouts $checkout, Group_Buying_Cart $cart ) {
		// if the decision's already been made
		if ( !empty($this->current_payment_processor) ) {
			return;
		}

		// if only one is enabled, the decision is easy
		$enabled = $this->enabled_processors();
		if ( count($enabled) == 1 ) {
			$this->current_payment_processor = $this->load_processor($enabled[0]);
			return;
		}

		// multiple are enabled, we have to make a decision

		// if the user chose to use one on the checkout page
		if ( isset( $_POST['gb_credit_payment_method'] ) || isset($_POST['gb_credit_payment_method_cache']) ) {
			$method = isset( $_POST['gb_credit_payment_method'] )?$_POST['gb_credit_payment_method']:$_POST['gb_credit_payment_method_cache'];
			if ( $method == 'credit' ) {
				foreach ( $enabled as $class ) {
					if ( self::is_cc_processor($class) ) {
						$this->current_payment_processor = $this->load_processor($class);
						return;
					}
				}
			} elseif ( in_array($method, $enabled) ) {
				$this->current_payment_processor = $this->load_processor($method);
				return;
			}
		}

		// check for tokens sent back by offsite processors
		foreach ( $enabled as $class ) {
			if ( !self::is_cc_processor($class) ) { 
				if ( call_user_func(array($class, 'returned_from_offsite')) ) {
					$this->current_payment_processor = $this->load_processor($class);
					return;
				}
			}
		}

		// default to the enabled onsite processor (if there is one)
		foreach ( $enabled as $class ) {
			if ( self::is_cc_processor($class) ) {
				$this->current_payment_processor = $this->load_processor($class);
				return;
			}
		}

		// default to the selected default offsite processor (if there is one)
		$this->current_payment_processor = $this->load_processor($enabled[0]);
	}

	public static function register() {
		self::add_payment_processor( __CLASS__, self::__( 'Multiple' ) );
	}

	/**
	 * Add options to choose which payment processors to enable
	 *
	 * @return void
	 */
	public function register_settings() {
		$page = Group_Buying_Payment_Processors::get_settings_page();
		$section = 'gb_hybrid_settings';
		add_settings_section( $section, self::__( 'Payment Processors' ), array( $this, 'display_settings_section' ), $page );
		register_setting( $page, self::ENABLED_PROCESSORS_OPTION );
		add_settings_field( self::ENABLED_PROCESSORS_OPTION, self::__( 'Payment Methods' ), array( $this, 'display_payment_methods_field' ), $page, $section );
	}

	public function display_payment_methods_field() {
		$offsite = self::get_registered_processors('offsite');
		$credit = self::get_registered_processors('credit');
		$enabled = $this->enabled_processors();

		if ( $offsite ) {
			printf( '<h4>%s</h4>', self::__('Offsite Processors') );
			foreach ( $offsite as $class => $label ) {
				printf('<p><label><input type="checkbox" name="%s[]" value="%s" %s /> %s</label></p>', self::ENABLED_PROCESSORS_OPTION, esc_attr($class), checked(TRUE, in_array($class, $enabled), FALSE), esc_html($label));
			}
		}
		if ( $credit ) {
			printf( '<h4>%s</h4>', self::__('Credit Card Processors') );
			printf( '<p><select name="%s[]">', self::ENABLED_PROCESSORS_OPTION );
			printf( '<option value="">%s</option>', self::__('-- None --') );
			foreach ( $credit as $class => $label ) {
				printf('<option value="%s" %s /> %s</option>', esc_attr($class), selected(TRUE, in_array($class, $enabled), FALSE), esc_html($label));
			}
			echo '</select>';
		}
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
	 *
	 *
	 * @param Group_Buying_Payment $payment
	 * @return void
	 */
	public function verify_recurring_payment( Group_Buying_Payment $payment ) {
		$method = $payment->get_payment_method();
		$this->load_enabled_processors();
		$enabled = $this->enabled_processors();
		foreach ( $enabled as $class ) {
			$processor = $this->load_processor($class);
			if ( $processor && $processor->get_payment_method() == $method ) {
				$processor->verify_recurring_payment($payment);
				break;
			}
		}
		// else, it's not handled here
	}
}
Group_Buying_Hybrid_Payment_Processor::register();
