<?php

/**
 * Record Controller
 *
 * @package GBS
 * @subpackage Base
 */
class Group_Buying_Records extends Group_Buying_Controller {

	private static $instance;

	public static function init() {
		add_action( 'gb_new_record', array( get_class(), 'new_record' ), 10, 6 );
		// add_action('gb_apply_credits', array(get_class(),'affiliate_record'),9,4);
	}

	public static function affiliate_record( $account, $payment_id, $credits, $type ) {
		$account_id = $account->get_ID();
		$balance = $account->get_credit_balance( $type );
		$data = array();
		$data['account_id'] = $account_id;
		$data['payment_id'] = $payment_id;
		$data['credits'] = $credits;
		$data['type'] = $type;
		$data['current_total_'.$type] = $credits;
		$data['prior_total_'.$type] = $balance;
		Group_Buying_Records::new_record( sprintf( self::__( '%s Credit Applied' ), ucfirst( $type ) ), Group_Buying_Accounts::$record_type, sprintf( self::__( '%s Credit Applied' ), ucfirst( $type ) ), 1, $account_id, $data );
	}

	public static function new_record( $message, $type = 'mixed', $title = '', $author = 1, $associate_id = -1, $data = array() ) {
		Group_Buying_Record::new_record( $message, $type, $title, $author, $associate_id, $data );
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	private function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}
	private function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}
	public static function get_instance() {
		if ( !( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
	}

	public function sort_callback( $a, $b ) {
		if ( $a == $b ) {
			return 0;
		}
		return ( $a < $b ) ? 1 : -1;
	}

}
