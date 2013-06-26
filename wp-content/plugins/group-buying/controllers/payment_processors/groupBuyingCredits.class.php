<?php

/**
 * Affiliate credit payment processor.
 * 
 * @package GBS
 * @subpackage Payment Processing_Processor
 */
class Group_Buying_Affiliate_Credit_Payments extends Group_Buying_Payment_Processors {
	const PAYMENT_METHOD = 'Account Credit (Affiliate)';
	const RESERVED_CREDIT_META = '_gbs_reserved_affiliate_credit';
	protected static $instance;

	public static function get_instance() {
		if ( !( isset( self::$instance ) && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	protected function __construct() {
		parent::__construct();
		remove_action( 'gb_new_purchase', array( $this, 'register_as_payment_method' ), 10, 1 );
		add_action( 'gb_new_purchase', array( $this, 'register_as_payment_method' ), 5, 2 );
		add_action( 'purchase_completed', array( $this, 'capture_purchase' ), 10, 1 );
		if ( GBS_DEV ) {
			add_action( 'init', array( $this, 'capture_pending_payments' ) );
		} else {
			add_action( self::CRON_HOOK, array( $this, 'capture_pending_payments' ) );
		}
		add_filter( 'gb_payment_fields', array( $this, 'payment_fields' ), 10, 3 );
		add_filter( 'gb_payment_review_fields', array( $this, 'payment_review_fields' ), 10, 3 );
		add_action( 'gb_checkout_action_'.Group_Buying_Checkouts::PAYMENT_PAGE, array( $this, 'process_payment_page' ), 10, 1 );
		add_filter( 'gb_offsite_purchase_payment_request_total', array( $this, 'filter_payment_request_total' ), 10, 2 );
		add_action( 'processing_payment', array( $this, 'process_payment' ), 10, 2 );
		add_action( 'deleted_purchase', array( $this, 'purchase_deleted' ), 10, 1 );

		add_filter( 'gb_item_to_capture_total', array( $this, 'filter_item_to_capture_total' ), 10, 3 );

	}

	public function get_payment_method() {
		return self::PAYMENT_METHOD;
	}

	public function get_credit_type() {
		return Group_Buying_Affiliates::CREDIT_TYPE;
	}

	/**
	 * When making an offsite payment request, filter the total requested to account
	 * for any affiliate credits being used
	 *
	 * @param unknown $total
	 * @param Group_Buying_Carts $cart
	 * @param Group_Buying_Checkouts $checkout
	 * @return mixed
	 */
	public function filter_payment_request_total( $total, Group_Buying_Checkouts $checkout ) {
		if ( isset( $checkout->cache['affiliate_credits'] ) && $checkout->cache['affiliate_credits'] ) {
			$credit_to_use = $checkout->cache['affiliate_credits'];
			$credit_to_use_value = $credit_to_use/self::get_credit_exchange_rate( $this->get_credit_type() );
			return max( $total - $credit_to_use_value, 0 ); // no, you can't use credit to get free money
		}
		return $total;
	}

	public function filter_item_to_capture_total( $total, $raw_total, $item = array() ) {
		if ( isset( $item['payment_method'][self::PAYMENT_METHOD] ) ) {
			return $raw_total;
		}
		return $total;

	}
	public function process_payment( Group_Buying_Checkouts $checkout, Group_Buying_Purchase $purchase ) {
		$account = Group_Buying_Account::get_instance();
		// $balance = $account->get_credit_balance($this->get_credit_type()); // TODO warn the person that the balance doesn't match their submission.
		$credit_to_use = isset($checkout->cache['affiliate_credits'])?$checkout->cache['affiliate_credits']:0;
		$total_value = $credit_to_use/self::get_credit_exchange_rate( $this->get_credit_type() );
		$account->reserve_credit( $credit_to_use, $this->get_credit_type() );
		$deal_info = array();
		foreach ( $purchase->get_products() as $item ) {
			if ( isset( $item['payment_method'][$this->get_payment_method()] ) ) {
				if ( !isset( $deal_info[$item['deal_id']] ) ) {
					$deal_info[$item['deal_id']] = array();
				}
				$deal_info[$item['deal_id']][] = $item;
			}
		}
		if ( !$deal_info ) {
			return FALSE; // no deals, so no payment
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
				'amount' => $total_value,
				'data' => array(
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

		// make note of how much credit we're reserving for this payment
		update_post_meta( $payment_id, self::RESERVED_CREDIT_META, $credit_to_use );

		return $payment;
	}

	public static function register() {
		// don't do anything. You shouldn't be able to set this as the sole payment processor
	}


	/**
	 * Register as the payment method for each unpaid-for item in the purchase
	 *
	 * @param Group_Buying_Purchase $purchase
	 * @return void
	 */
	public function register_as_payment_method( $purchase, $args ) {
		$checkout = $args['checkout'];
		if ( isset( $checkout->cache['free_deal'] ) && $checkout->cache['free_deal'] ) {
			$items = $purchase->get_products();
			if ( 0 == $purchase->get_total() ) { // Free deals
				foreach ( $items as $key => $item ) {
					$items[$key]['payment_method'][$this->get_payment_method()] = '0';
				}
				$purchase->set_products( $items );
			}

		}
		elseif ( isset( $checkout->cache['affiliate_credits'] ) && $checkout->cache['affiliate_credits'] ) {
			$credit_to_use = $checkout->cache['affiliate_credits'];
			$credits_to_use_value = $credit_to_use/self::get_credit_exchange_rate( $this->get_credit_type() );
			$items = $purchase->get_products();
			foreach ( $items as $key => $item ) {
				$remaining = $item['price'];
				foreach ( $item['payment_method'] as $processor => $amount ) {
					$remaining -= $amount;
				}
				if ( $remaining >= 0.01 ) { // leave a bit of room for floating point arithmetic
					// need to leave some room for tax an shipping, so we don't allocate too much credit
					$extras = $purchase->get_item_shipping( $item )+$purchase->get_item_tax( $item );
					if ( $remaining+$extras <= $credits_to_use_value ) {
						$items[$key]['payment_method'][$this->get_payment_method()] = $remaining;
						$credit_to_use -= $remaining+$extras;
					} else {
						$ratio = @( $credits_to_use_value/( $item['price']+$extras ) );
						$items[$key]['payment_method'][$this->get_payment_method()] = $ratio*$item['price'];
						$credit_to_use = 0;
					}
				}
				if ( $credit_to_use < 0.01 ) {
					break;
				}
			}
			$purchase->set_products( $items );
		}
	}


	public function payment_fields( $fields, $payment_processor_class, $checkout ) {
		$account = Group_Buying_Account::get_instance();
		$balance = $account->get_credit_balance( $this->get_credit_type() );
		$credit_value = $account->get_credit_balance( $this->get_credit_type() )/self::get_credit_exchange_rate( $this->get_credit_type() );
		$cart = Group_Buying_Cart::get_instance();
		$total = $cart->get_total();

		if ( 0 == $total ) {
			// get rid of credit card info, since there won't be any
			unset( $fields['cc_name'] );
			unset( $fields['cc_number'] );
			unset( $fields['cc_expiration'] );
			unset( $fields['cc_expiration_month'] );
			unset( $fields['cc_expiration_year'] );
			unset( $fields['cc_cvv'] );
			unset( $fields['payment_method'] );
			$fields['free_deal'] = array(
				'type' => 'hidden',
				'weight' => -5,
				'label' => self::__( 'Free' ),
				'attributes' => array(

				),
				'description' =>self::__( 'Awesome! This item is free. We need nothing more.' ),
				'size' => 10,
				'required' => FALSE,
				'value' => 'TRUE',
			);
			return $fields;
		}

		if ( $balance ) {
			$default = isset( $checkout->cache['affiliate_credits'] )?$checkout->cache['affiliate_credits']:0;
			$fields['affiliate_credits'] = array(
				'type' => 'text',
				'weight' => -5,
				'label' => self::__( 'Reward Points' ),
				'description' => sprintf( self::__( 'You have %s reward points a value of %s. How many points would you like to apply to this purchase?' ), gb_get_number_format( $balance, '.', ',' ), gb_get_formatted_money( $credit_value ) ),
				'size' => 10,
				'required' => FALSE,
				'default' => $default,
			);
		}
		if ( isset( $checkout->cache['affiliate_credits'] ) && $checkout->cache['affiliate_credits'] >= 0.01 ) {
			if ( $total <= $checkout->cache['affiliate_credits'] ) {
				foreach ( array_keys( $fields ) as $key ) {
					$fields[$key]['required'] = FALSE;
				}
			}
		}
		return $fields;
	}


	/**
	 * Display the credit card review pane
	 *
	 * @param array   $panes
	 * @param Group_Buying_Checkouts $checkout
	 * @return array
	 */
	public function payment_review_fields( $fields, $payment_processor_class, Group_Buying_Checkouts $checkout ) {
		$cart = Group_Buying_Cart::get_instance();
		$total = $cart->get_total();
		if ( 0 == $total ) {
			unset( $fields['cc_name'] );
			unset( $fields['cc_number'] );
			unset( $fields['cc_expiration'] );
			unset( $fields['cc_cvv'] );
			$fields['free_deal'] = array(
				'label' => self::__( 'Free' ),
				'value' => self::__( 'On the house.' ),
				'weight' => 10,
			);
			return $fields;
		}
		if ( isset( $checkout->cache['affiliate_credits'] ) && $checkout->cache['affiliate_credits'] > 0 ) {
			if ( $checkout->cache['affiliate_credits'] >= $total ) {
				// get rid of credit card info, since there won't be any
				unset( $fields['cc_name'] );
				unset( $fields['cc_number'] );
				unset( $fields['cc_expiration'] );
				unset( $fields['cc_cvv'] );
				unset( $fields['method'] );
				$fields['affiliate_credits'] = array(
					'label' => self::__( 'Payment Method' ),
					'value' => self::__( 'Rewards Points' ),
					'weight' => 1,
				);
			} else {
				$amount_paid = $checkout->cache['affiliate_credits']/self::get_credit_exchange_rate( $this->get_credit_type() );
				$fields['affiliate_credits'] = array(
					'label' => self::__( 'Reward Points' ),
					'value' => sprintf( self::__( '%s will be paid from your rewards balance' ), gb_get_formatted_money( $amount_paid ) ),
					'weight' => 10,
				);
			}
		}
		return $fields;
	}



	public function process_payment_page( Group_Buying_Checkouts $checkout ) {
		if ( isset( $_POST['gb_credit_free_deal'] ) && $_POST['gb_credit_free_deal'] ) {
			$cart = Group_Buying_Cart::get_instance();
			$total = $cart->get_total();
			if ( 0 == $total ) { // Free Deals
				$checkout->cache['free_deal'] = 'TRUE';
				return;
			}

		}
		if ( isset( $_POST['gb_credit_affiliate_credits'] ) && $_POST['gb_credit_affiliate_credits'] ) {
			$credit_to_use = $_POST['gb_credit_affiliate_credits'];
			$credits_to_use_value = $credit_to_use/self::get_credit_exchange_rate( $this->get_credit_type() );
			if ( !is_numeric( $credit_to_use ) ) {
				self::set_message( "Unknown value for Rewards field", self::MESSAGE_STATUS_ERROR );
				$checkout->mark_page_incomplete( Group_Buying_Checkouts::PAYMENT_PAGE );
				return;
			}
			$credit_to_use = (float)$credit_to_use;
			if ( $credit_to_use < 0.01 ) {
				return;
			}
			$account = Group_Buying_Account::get_instance();
			$balance = $account->get_credit_balance( $this->get_credit_type() );
			if ( $balance < $credits_to_use_value ) {
				self::set_message( "You don't have that many reward points.", self::MESSAGE_STATUS_ERROR );
				$checkout->mark_page_incomplete( Group_Buying_Checkouts::PAYMENT_PAGE );
				return;
			}
			$cart = Group_Buying_Cart::get_instance();
			$total = $cart->get_total();
			if ( $credits_to_use_value > $total ) {
				// If trying to use more credits than what's needed set to the max.
				$credit_to_use = $total*self::get_credit_exchange_rate( $this->get_credit_type() );
			}
			$checkout->cache['affiliate_credits'] = $credit_to_use;
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
			$this->release_payment( $payment ); // release reserved credits for failed deals
		}
	}


	public function capture_payment( Group_Buying_Payment $payment ) {
		// is this the right payment processor? does the payment still need processing?
		if ( $payment->get_payment_method() == $this->get_payment_method() && $payment->get_status() != Group_Buying_Payment::STATUS_COMPLETE ) {
			$account = $payment->get_account();
			if ( !$account ) {
				return FALSE;
			}

			$data = $payment->get_data();
			$items_to_capture = $this->items_to_capture( $payment );

			if ( $items_to_capture ) {
				$finishes_payment = ( count( $items_to_capture ) < count( $data['uncaptured_deals'] ) )?FALSE:TRUE;

				$payment_total = 0;
				foreach ( $items_to_capture as $deal_id => $amount ) {
					unset( $data['uncaptured_deals'][$deal_id] );
					$payment_total += $amount;
				}
				$credit_total = $payment_total*self::get_credit_exchange_rate( $this->get_credit_type() );
				$total = gb_get_number_format( $credit_total );
				$account->restore_credit( $total, $this->get_credit_type() );
				$account->deduct_credit( $total, $this->get_credit_type() );

				$reserved = get_post_meta( $payment->get_id(), self::RESERVED_CREDIT_META, TRUE );

				if ( $reserved ) {
					$reserved = max( $reserved - $total, 0 );
					update_post_meta( $payment->get_id(), self::RESERVED_CREDIT_META, $reserved );
				}
				$payment->set_data( $data );
				// trigger action to create vouchers, etc.
				do_action( 'payment_captured', $payment, array_keys( $items_to_capture ) );
				if ( $finishes_payment ) {
					$payment->set_status( Group_Buying_Payment::STATUS_COMPLETE );
					if ( $reserved > 0 ) {
						$account->restore_credit( $reserved, $this->get_credit_type() );
					}
					do_action( 'payment_complete', $payment );
				} else {
					$payment->set_status( Group_Buying_Payment::STATUS_PARTIAL );
				}
			}
		}
	}

	public function release_payment( Group_Buying_Payment $payment ) {
		if ( $payment->get_payment_method() == $this->get_payment_method() && $payment->get_status() != Group_Buying_Payment::STATUS_COMPLETE ) {

			$account = $payment->get_account();
			if ( !$account ) {
				return FALSE;
			}

			$data = $payment->get_data();
			$items_to_release = $this->items_to_capture( $payment, TRUE );

			if ( $items_to_release ) {

				$payment_total = 0;
				foreach ( $items_to_release as $deal_id => $amount ) {
					unset( $data['uncaptured_deals'][$deal_id] ); // remove record since the credit is being restored.
					$payment_total += $amount;
				}
				$credit_total = $payment_total*self::get_credit_exchange_rate( $this->get_credit_type() );
				$total = gb_get_number_format( $credit_total );
				$account->restore_credit( $total, $this->get_credit_type() );

				// Remove the reserved amount from the payment.
				$reserved = get_post_meta( $payment->get_id(), self::RESERVED_CREDIT_META, TRUE );
				if ( $reserved ) {
					$reserved = max( $reserved - $total, 0 );
					update_post_meta( $payment->get_id(), self::RESERVED_CREDIT_META, $reserved );
				}
				// Remove the uncaptured items payments
				$payment->set_data( $data );
				// trigger action for future notifications, etc..
				if ( self::DEBUG ) error_log( '----------Rewards Release----------' . print_r( $items_to_release, TRUE ) );
				do_action( 'payment_released', $payment, array_keys( $items_to_release ) );
			}
		}
	}

	/**
	 * When a purchase is deleted (e.g., if checkout failed),
	 * we need to cancel any affiliate payments associated with
	 * it so that credits aren't improperly deducted
	 *
	 * @param int     $purchase_id
	 * @return void
	 */
	public function purchase_deleted( $purchase_id ) {
		$payments = Group_Buying_Payment::get_payments_for_purchase( $purchase_id );
		foreach ( $payments as $payment_id ) {
			$payment = Group_Buying_Payment::get_instance( $payment_id );
			if ( $payment->get_payment_method() == $this->get_payment_method() ) {
				$account = Group_Buying_Account::get_instance();
				$credit_total = $payment->get_amount()*self::get_credit_exchange_rate( $this->get_credit_type() );
				$account->restore_credit( $credit_total, $this->get_credit_type() );
				$payment->set_status( Group_Buying_Payment::STATUS_VOID );
			}
		}
	}
}
