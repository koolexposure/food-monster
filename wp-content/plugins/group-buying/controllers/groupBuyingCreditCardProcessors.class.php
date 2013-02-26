<?php

/**
 * Credit Card Processors parent class, extends all payment processors.
 *
 * @package GBS
 * @subpackage Payment Processing
 */
abstract class Group_Buying_Credit_Card_Processors extends Group_Buying_Payment_Processors {

	protected $cc_cache = array();

	protected function __construct() {
		parent::__construct();
		add_filter( 'gb_checkout_panes', array( $this, 'credit_card_cache_pane' ), 10, 2 );
		add_action( 'gb_checkout_action', array( $this, 'process_credit_card_cache' ), 10, 2 );

		add_filter( 'gb_checkout_panes_'.Group_Buying_Checkouts::PAYMENT_PAGE, array( $this, 'payment_pane' ), 10, 2 );
		add_action( 'gb_checkout_action_'.Group_Buying_Checkouts::PAYMENT_PAGE, array( $this, 'process_payment_page' ), 20, 1 );

		add_filter( 'gb_checkout_panes_'.Group_Buying_Checkouts::REVIEW_PAGE, array( $this, 'review_pane' ), 10, 2 );
 
	}

	/**
	 * Since we can't store credit card info in the database,
	 * pass it from page to page in a hidden form field.
	 *
	 * @param array   $panes
	 * @param Group_Buying_Checkouts $checkout
	 * @return array
	 */
	public function credit_card_cache_pane( $panes, Group_Buying_Checkouts $checkout ) {
		if ( $this->cc_cache && $checkout->get_current_page() != Group_Buying_Checkouts::PAYMENT_PAGE ) {
			$data = array(
				'type' => 'hidden',
				'value' => esc_attr( serialize( $this->cc_cache ) ),
			);
			$panes['cc_cache'] = array(
				'weight' => 0,
				'body' => gb_get_form_field( 'cc_cache', $data, 'credit' )
			);
		}
		return $panes;
	}

	public function process_credit_card_cache( $action, Group_Buying_Checkouts $checkout ) {
		if ( isset( $_POST['gb_credit_cc_cache'] ) ) {
			$cache = unserialize( stripslashes( $_POST['gb_credit_cc_cache'] ) );
			if ( $this->validate_credit_card( $cache, $checkout ) ) {
				$this->cc_cache = $cache;

			}
		}
	}


	/**
	 * Display the credit card form
	 *
	 * @param array   $panes
	 * @param Group_Buying_Checkouts $checkout
	 * @return array
	 */
	public function payment_pane( $panes, Group_Buying_Checkouts $checkout ) {
		$panes['payment'] = array(
			'weight' => 100,
			'body' => self::load_view_to_string( 'checkout/credit_card', array( 'fields' => $this->payment_fields( $checkout ) ) ),
		);
		return $panes;
	}


	/**
	 * Validate the submitted credit card info
	 * Store the submitted credit card info in memory for processing the payment later
	 *
	 * @param Group_Buying_Checkouts $checkout
	 * @return void
	 */
	public function process_payment_page( Group_Buying_Checkouts $checkout ) {
		$fields = $this->payment_fields( $checkout );
		foreach ( array_keys( $fields ) as $key ) {
			if ( $key == 'cc_number' ) { // catch the cc_number so it can be sanatized
				if ( isset( $_POST['gb_credit_cc_number'] ) && strlen( $_POST['gb_credit_cc_number'] ) > 0 ) {
					$this->cc_cache['cc_number'] = preg_replace( '/\D+/', '', $_POST['gb_credit_cc_number'] );
				}
			}
			elseif ( isset( $_POST['gb_credit_'.$key] ) && strlen( $_POST['gb_credit_'.$key] ) > 0 ) {
				$this->cc_cache[$key] = $_POST['gb_credit_'.$key];
			}
		}
		$this->validate_credit_card( $this->cc_cache, $checkout );
	}

	/**
	 * Display the credit card review pane
	 *
	 * @param array   $panes
	 * @param Group_Buying_Checkouts $checkout
	 * @return array
	 */
	public function review_pane( $panes, Group_Buying_Checkouts $checkout ) {
		$cache = wp_parse_args($this->cc_cache, array(
			'cc_name' => '',
			'cc_number' => '',
			'cc_expiration_month' => '',
			'cc_expiration_year' => '',
			'cc_cvv' => ''
		));
		$fields = array(
			'cc_name' => array(
				'label' => self::__( 'Cardholder' ),
				'value' => $cache['cc_name'],
				'weight' => 1,
			),
			'cc_number' => array(
				'label' => self::__( 'Card Number' ),
				'value' => $cache['cc_number']?self::mask_card_number( $cache['cc_number'] ):'',
				'weight' => 2,
			),
			'cc_expiration' => array(
				'label' => self::__( 'Expiration Date' ),
				'value' => $cache['cc_expiration_month'].'/'.$cache['cc_expiration_year'],
				'weight' => 3,
			),
			'cc_cvv' => array(
				'label' => self::__( 'CVV' ),
				'value' => $cache['cc_cvv'],
				'weight' => 4,
			),
		);
		$fields = apply_filters( 'gb_payment_review_fields', $fields, get_class( $this ), $checkout );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		$panes['payment'] = array(
			'weight' => 100,
			'body' => self::load_view_to_string( 'checkout/credit-card-review', array( 'fields' => $fields ) ),
		);
		return $panes;
	}

	/**
	 * An array of standard credit card fields
	 *
	 * @static
	 * @return array
	 */
	public static function default_credit_fields() {
		return array(
			'cc_name' => array(
				'type' => 'text',
				'weight' => 1,
				'label' => self::__( 'Cardholder Name' ),
				'attributes' => array(
					'autocomplete' => 'off',
				),
				'required' => TRUE,
			),
			'cc_number' => array(
				'type' => 'text',
				'weight' => 1,
				'label' => self::__( 'Card Number' ),
				'attributes' => array(
					'autocomplete' => 'off',
				),
				'required' => TRUE,
			),
			'cc_expiration_month' => array(
				'type' => 'select',
				'weight' => 2,
				'options' => self::get_month_options(),
				'label' => self::__( 'Expiration Date' ),
				'attributes' => array(
					'autocomplete' => 'off',
				),
				'required' => TRUE,
			),
			'cc_expiration_year' => array(
				'type' => 'select',
				'weight' => 3,
				'options' => self::get_year_options(),
				'label' => self::__( 'Expiration Date' ),
				'attributes' => array(
					'autocomplete' => 'off',
				),
				'required' => TRUE,
			),
			'cc_cvv' => array(
				'type' => 'text',
				'size' => 5,
				'weight' => 10,
				'label' => self::__( 'Security Code' ),
				'attributes' => array(
					'autocomplete' => 'off',
				),
				'required' => TRUE,
			),
		);
	}


	/**
	 * Validate the credit card number
	 *
	 * Code borrowed from Ubercart.
	 *
	 * @see http://api.ubercart.org/api/function/_valid_card_number/2
	 *
	 * @static
	 * @param string  $number   The credit card number
	 * @param bool    $sanitize Clean up the string so it's only digits.
	 * @return bool
	 */
	public static function is_valid_credit_card( $number, $sanitize = false ) {
		if ( $sanitize ) {
			$number = preg_replace( '/\D+/', '', $number );
		}
		if ( !ctype_digit( $number ) ) {
			return FALSE; // not a number
		}

		$total = 0;
		for ( $i = 0; $i < strlen( $number ); $i++ ) {
			$digit = substr( $number, $i, 1 );
			if ( ( strlen( $number ) - $i - 1 ) % 2 ) {
				$digit *= 2;
				if ( $digit > 9 ) {
					$digit -= 9;
				}
			}
			$total += $digit;
		}

		if ( $total % 10 != 0 ) {
			return FALSE; // invalid checksum
		}

		return TRUE; // seems valid
	}

	/**
	 * Make sure the CVV is 3 or 4 digits long
	 *
	 * @static
	 * @param string  $cvv
	 * @return bool
	 */
	public static function is_valid_cvv( $cvv ) {
		if ( !is_numeric( $cvv ) || strlen( $cvv ) > 4 || strlen( $cvv ) < 3 ) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Determine if the given date is in the past
	 *
	 * Code borrowed from Ubercart.
	 *
	 * @see http://api.ubercart.org/api/function/_valid_card_expiration/2
	 *
	 * @static
	 * @param int|string $year
	 * @param int_string $month
	 * @return bool
	 */
	public static function is_expired( $year, $month ) {
		if ( $year < date( 'Y' ) ) {
			return TRUE;
		}
		elseif ( $year == date( 'Y' ) ) {
			if ( $month < date( 'n' ) ) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Mask a credit card number by replacing all but the first digit and last
	 * four digits with $filler
	 *
	 * @static
	 * @param string  $number
	 * @param string  $filler
	 * @return string
	 */
	public static function mask_card_number( $number, $filler = 'x' ) {
		$length = strlen( $number )-5;
		$masked = sprintf( "%s%'".$filler.$length."s%s", substr( $number, 0, 1 ), '', substr( $number, -4 ) );
		return $masked;
	}


	protected function payment_fields( $checkout = NULL ) {
		$fields = self::default_credit_fields();
		foreach ( array_keys( $fields ) as $key ) {
			if ( isset( $this->cc_cache[$key] ) ) {
				$fields[$key]['default'] = $this->cc_cache[$key];
			}
		}
		$fields = apply_filters( 'gb_credit_fields', $fields, __CLASS__, $checkout );
		$fields = apply_filters( 'gb_payment_fields', $fields, __CLASS__, $checkout );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}


	protected function validate_credit_card( $cc_data, Group_Buying_Checkouts $checkout ) {
		$valid = TRUE;
		$fields = $this->payment_fields( $checkout );
		foreach ( $fields as $key => $data ) {
			if ( $data['required'] && !( isset( $cc_data[$key] ) && strlen( $cc_data[$key] ) > 0 ) ) {
				self::set_message( sprintf( self::__( '"%s" field is required.' ), $fields[$key]['label'] ), self::MESSAGE_STATUS_ERROR );
				$valid = FALSE;
			}
		}
		if ( isset( $cc_data['cc_number'] ) ) {
			if ( !self::is_valid_credit_card( $cc_data['cc_number'] ) ) {
				self::set_message( self::__( 'Invalid credit card number' ), self::MESSAGE_STATUS_ERROR );
				$valid = FALSE;
			}
		}

		if ( isset( $cc_data['cc_cvv'] ) ) {
			if ( !self::is_valid_cvv( $cc_data['cc_cvv'] ) ) {
				self::set_message( self::__( 'Invalid credit card security code' ), self::MESSAGE_STATUS_ERROR );
				$valid = FALSE;
			}
		}

		if ( !empty($fields['cc_expiration_year']['required']) && isset( $cc_data['cc_expiration_year'] ) ) {
			if ( self::is_expired( $cc_data['cc_expiration_year'], $cc_data['cc_expiration_month'] ) ) {
				self::set_message( self::__( 'Credit card is expired.' ), self::MESSAGE_STATUS_ERROR );
				$valid = FALSE;
			}
		}

		if ( !$valid ) {
			$this->invalidate_checkout( $checkout );
		}
		return $valid;
	}

	/**
	 * Return the card type based on number
	 *
	 * @see http://en.wikipedia.org/wiki/Bank_card_number
	 *
	 * @static
	 * @param string  $number
	 * @return string
	 */
	public static function get_card_type( $cc_number ) {
		if ( preg_match( '/^(6334[5-9][0-9]|6767[0-9]{2})[0-9]{10}([0-9]{2,3}?)?$/', $cc_number ) ) {

			return 'Solo'; // is also a Maestro product

		} elseif ( preg_match( '/^(49369[8-9]|490303|6333[0-4][0-9]|6759[0-9]{2}|5[0678][0-9]{4}|6[0-9][02-9][02-9][0-9]{2})[0-9]{6,13}?$/', $cc_number ) ) {

			return 'Maestro';

		} elseif ( preg_match( '/^(49030[2-9]|49033[5-9]|4905[0-9]{2}|49110[1-2]|49117[4-9]|49918[0-2]|4936[0-9]{2}|564182|6333[0-4][0-9])[0-9]{10}([0-9]{2,3}?)?$/', $cc_number ) ) {

			return 'Maestro'; // SWITCH is now Maestro

		} elseif ( preg_match( '/^4[0-9]{12}([0-9]{3})?$/', $cc_number ) ) {

			return 'Visa';

		} elseif ( preg_match( '/^5[1-5][0-9]{14}$/', $cc_number ) ) {

			return 'MasterCard';

		} elseif ( preg_match( '/^3[47][0-9]{13}$/', $cc_number ) ) {

			return 'Amex';

		} elseif ( preg_match( '/^3(0[0-5]|[68][0-9])[0-9]{11}$/', $cc_number ) ) {

			return 'Diners';

		} elseif ( preg_match( '/^(6011[0-9]{12}|622[1-9][0-9]{12}|64[4-9][0-9]{13}|65[0-9]{14})$/', $cc_number ) ) {

			return 'Discover';

		} elseif ( preg_match( '/^(35(28|29|[3-8][0-9])[0-9]{12}|2131[0-9]{11}|1800[0-9]{11})$/', $cc_number ) ) {

			return 'JCB';

		} else {

			return 'Unknown';

		}
	}
}
