<?php
/**
 * This class provides a model for a subscription processor. To implement a
 * different list service, create a new class that extends
 * Group_Buying_List_Services. The new class should implement
 * the following methods (at a minimum):
 *  - get_instance()
 *  - process_subscription()
 *  - process_registration_subscription()
 *  - register()
 *  - get_subscription_method()
 *
 * You may also want to register some settings for the Payment Options page
 */

class Group_Buying_Custom extends Group_Buying_List_Services {
	protected static $instance;


	protected static function get_instance() {
		if ( !( isset( self::$instance ) && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_subscription_method() {
		return self::SUBSCRIPTION_SERVICE;
	}

	protected function __construct() {
		parent::__construct();
		add_action( 'gb_subscription_form', array( get_class(), 'gb_subscription_form' ), 10, 4 );
	}

	public static function register() {
		self::add_list_service( __CLASS__, self::__( 'Custom Form' ) );
	}

	public static function gb_subscription_form( $view, $show_locations, $select_location_text, $button_text ) {
		$view = get_option( Group_Buying_List_Services::SUBSCRIPTION_FORM_CUSTOM );
		return $view;
	}

	public function process_subscription() {
		if ( isset( $_POST['deal_location'] ) ) {
			parent::success( $_POST['deal_location'], null );
		}

	}

	public function process_registration_subscription( $userId = null, $user_login = null, $user_email = null, $password = null, $post = null ) {
		return;
	}
}
Group_Buying_Custom::register();
