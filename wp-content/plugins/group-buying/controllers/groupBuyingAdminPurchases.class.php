<?php

/**
 * Purchases administration
 *
 * @package GBS
 * @subpackage Purchase
 */
class Group_Buying_Admin_Purchases extends Group_Buying_Controller {
	const ADD_DEAL_ID_FIELD = 'gb_added_deal_id';
	const ADD_DEAL_QUANTITY_FIELD = 'gb_added_deal_qty';
	public static function init() {
		add_action( 'gb_account_purchases_meta_box_top', array( get_class(), 'display_add_deal_form' ) );
		add_action( 'save_post', array( get_class(), 'save_add_deal_form' ), 10, 2 );
		add_action( self::CRON_HOOK, array( get_class(), 'capture_pending_payments' ) );
	}

	public static function display_add_deal_form() {
		if ( current_user_can( 'edit_users' ) ) {
			self::load_view( 'meta_boxes/account-purchases-add', array(), FALSE );
		}
	}

	public static function save_add_deal_form( $post_id, $post ) {
		// only continue if it's an account post
		if ( $post->post_type != Group_Buying_Account::POST_TYPE ) {
			return;
		}
		// don't do anything on autosave, auto-draft, bulk edit, or quick edit
		if ( wp_is_post_autosave( $post_id ) || $post->post_status == 'auto-draft' || defined( 'DOING_AJAX' ) || isset( $_GET['bulk_edit'] ) ) {
			return;
		}

		// nothing to do if a deal wasn't selected
		if ( !isset( $_POST[self::ADD_DEAL_ID_FIELD] ) || !$_POST[self::ADD_DEAL_ID_FIELD] ) {
			return;
		}

		// don't know how many to add if quantity wasn't set
		if ( !isset( $_POST[self::ADD_DEAL_QUANTITY_FIELD] ) || !$_POST[self::ADD_DEAL_QUANTITY_FIELD] ) {
			return;
		}

		$deal = Group_Buying_Deal::get_instance( $_POST[self::ADD_DEAL_ID_FIELD] );
		if ( !$deal ) {
			return; // the deal ID doesn't correspond to a deal
		}

		$account = Group_Buying_Account::get_instance_by_id( $post_id );
		if ( !is_a( $account, 'Group_Buying_Account' ) ) {
			return; // The account doesn't exist
		}

		// create a new purchase
		$purchase_id = Group_Buying_Purchase::new_purchase( array(
				'user' => $account->get_user_id(),
				'items' => array(
					array(
						'deal_id' => $deal->get_id(),
						'quantity' => $_POST[self::ADD_DEAL_QUANTITY_FIELD],
						'data' => apply_filters( 'gbs_admin_purchase_data', array(), $deal ),
						'price' => 0,
						'unit_price' => 0,
						'payment_method' => array( 'admin' => 0 ),
					),
				),
			) );
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		$purchase->complete();

		// create a payment for the purchase
		$deal_info = array();
		foreach ( $purchase->get_products() as $item ) {
			if ( !isset( $deal_info[$item['deal_id']] ) ) {
				$deal_info[$item['deal_id']] = array();
			}
			$deal_info[$item['deal_id']][] = $item;
		}
		$payment_id = Group_Buying_Payment::new_payment( array(
				'payment_method' => 'admin',
				'purchase' => $purchase->get_id(),
				'amount' => 0,
				'data' => array(
					'uncaptured_deals' => $deal_info,
				),
				'deals' => $deal_info,
			), Group_Buying_Payment::STATUS_AUTHORIZED );

		$payment = Group_Buying_Payment::get_instance( $payment_id );
		do_action( 'payment_authorized', $payment );
	}


	/**
	 * Try to capture all pending payments
	 *
	 * @return void
	 */
	public function capture_pending_payments() {
		$payments = Group_Buying_Payment::get_pending_payments( 'admin' );
		foreach ( $payments as $payment_id ) {
			$payment = Group_Buying_Payment::get_instance( $payment_id );
			$data = $payment->get_data();
			$deals = array_keys( $data['uncaptured_deals'] );

			// see if any deals in this purchase are ready to capture
			$items_to_capture = array();
			foreach ( $deals as $deal_id ) {
				$deal = Group_Buying_Deal::get_instance( $deal_id );
				if ( $deal->is_successful() ) {
					$items_to_capture[] = $deal_id;
				}
			}

			if ( !$items_to_capture ) {
				continue; // loop to the next payment
			}

			// mark the items as captured
			foreach ( $items_to_capture as $deal_id ) {
				unset( $data['uncaptured_deals'][$deal_id] );
			}
			$payment->set_data( $data );

			// trigger action to create vouchers, etc.
			do_action( 'payment_captured', $payment, $items_to_capture );

			// set the payment status
			if ( $data['uncaptured_deals'] ) { // still more to capture later
				$payment->set_status( Group_Buying_Payment::STATUS_PARTIAL );
			} else {
				$payment->set_status( Group_Buying_Payment::STATUS_COMPLETE );
				do_action( 'payment_complete', $payment );
			}
		}
	}
}
