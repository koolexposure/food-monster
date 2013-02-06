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

class Group_Buying_ConstantContact extends Group_Buying_List_Services {
	const LOGIN = 'gb_constantcontact_login';
	const PASSWORD = 'gb_constantcontact_password';
	protected static $instance;
	protected static $ccListOBJ;
	protected static $ccContactOBJ;
	private static $login = '';
	private static $password = '';
	private static $email = '';
	private static $location = '';
	private static $list_id = array();
	private static $latest_deal_redirect = '';


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
		self::$login = get_option( self::LOGIN, '' );
		self::$password = get_option( self::PASSWORD, '' );

		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );
	}

	public static function register() {
		self::add_list_service( __CLASS__, self::__( 'ConstantContact' ) );
	}

	public static function init_cc() {

		self::$email = $_POST['email_address'];
		self::$location = $_POST['deal_location'];
		require_once 'utilities/ctct.class.php';
		self::$ccListOBJ = new CC_List();
		self::$ccContactOBJ = new CC_Contact();

		// Figure out which list to subscribe the user to.
		$allLists = self::$ccListOBJ->getLists();
		foreach ( $allLists as $k => $item ) {
			$list_name = strtolower( $item['title'] );
			if ( ( self::$location == $list_name ) || ( self::$location == str_replace( ' ', '-', $list_name ) ) ) {
				self::$list_id[] = $item['id'];
				break;
			}
		}
		if ( empty( self::$list_id ) ) {
			self::$list_id[] =  'http://api.constantcontact.com/ws/customers/'.self::$login.'/lists/1';
		}
	}
	public function process_subscription() {
		self::init_cc();
		$postFields = array();
		$postFields["email_address"] = self::$email;
		$postFields["mail_type"] = 'HTML';
		$postFields["city_name"] = self::$location;
		$postFields["lists"] = self::$list_id;
		if ( GBS_DEV ) error_log( "postFields: " . print_r( $postFields, true ) );
		$contactXML = self::$ccContactOBJ->createContactXML( null, $postFields );
		if ( GBS_DEV ) error_log( "contactXML: " . print_r( $contactXML, true ) );
		if ( !self::$ccContactOBJ->addSubscriber( $contactXML ) ) {
			$message = self::$ccContactOBJ->lastError;
			Group_Buying_Controller::set_message( $message, 'error' );
		} else {
			$class = "success";
			parent::success( $postFields["city_name"], $postFields["email_address"] );
		}
	}

	public function process_registration_subscription( $user = null, $user_login = null, $user_email = null, $password = null, $post = null ) {
		if ( !$post[ parent::REGISTRATION_OPTIN ] )
			return;

		self::init_cc();
		$cookie = gb_get_preferred_location();
		if ( !empty( $cookie ) ) {
			$current_location = $cookie;
		} elseif ( isset( $_POST['deal_location'] ) ) {
			$current_location = $_POST['deal_location'];
		} elseif ( isset( $_POST['gb_contact_city'] ) ) {
			$current_location = $_POST['gb_contact_city'];
		} else {
			$current_location = 'unknown';
		}

		$postFields = array();
		$postFields["email_address"] = $user_email;
		$postFields["mail_type"] = 'HTML';
		$postFields["lists"] = self::$list_id;
		$contactXML = self::$ccContactOBJ->createContactXML( null, $postFields );
		if ( !self::$ccContactOBJ->addSubscriber( $contactXML ) ) {
			$error = true;
		} else {
			$error = false;
			$_POST = array();
		}
		return;
	}

	public function register_settings() {
		$page = Group_Buying_List_Services::get_settings_page();
		$section = 'gb_constantcontact_sub';
		add_settings_section( $section, self::__( 'ConstantContact Subscription' ), array( $this, 'display_settings_section' ), $page );
		register_setting( $page, self::LOGIN );
		register_setting( $page, self::PASSWORD );
		add_settings_field( self::LOGIN, self::__( 'Login' ), array( $this, 'display_login_field' ), $page, $section );
		add_settings_field( self::PASSWORD, self::__( 'Password' ), array( $this, 'display_password_field' ), $page, $section );
	}

	public static function display_login_field() {
		echo '<input type="text" name="'.self::LOGIN.'" value="'.self::$login.'" />';
	}

	public static function display_password_field() {
		echo '<input type="password" name="'.self::PASSWORD.'" value="'.self::$password.'" />';
	}
}
Group_Buying_ConstantContact::register();
