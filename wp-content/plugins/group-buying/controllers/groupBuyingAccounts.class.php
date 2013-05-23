<?php

/**
 * Accounts Controller: Registration, Login, Edit, Password Recovery, etc.
 *
 * @package GBS
 * @subpackage Account
 */
class Group_Buying_Accounts extends Group_Buying_Controller {
	const ACCOUNT_PATH_OPTION = 'gb_account_path';
	const ACCOUNT_QUERY_VAR = 'gb_view_account';
	const CREDIT_TYPE = 'balance';
	private static $account_path = 'account';
	public static $record_type = 'credit_history';
	private static $balance_payment_processor;
	private static $instance;

	/**
	 * Init
	 *
	 * @return void
	 */
	public static function init() {
		self::$account_path = get_option( self::ACCOUNT_PATH_OPTION, self::$account_path );
		self::register_path_callback( self::$account_path, array( get_class(), 'on_account_page' ), self::ACCOUNT_QUERY_VAR, 'account' );

		
		add_action( 'admin_head', array( get_class(), 'admin_style' ) );
		add_action( 'add_meta_boxes', array( get_class(), 'add_meta_boxes' ) );
		add_action( 'save_post', array( get_class(), 'save_meta_boxes' ), 10, 2 );
		add_filter( 'gb_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 10, 1 );
		add_action( 'checkout_completed', array( get_class(), 'save_contact_info_from_checkout' ), 10, 1 );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 50, 0 );

		add_filter( 'gb_account_credit_types', array( get_class(), 'register_credit_type' ), 10, 1 );
		// This shouldn't ever be instantiated through the normal process. We want to add it on.
		self::$balance_payment_processor = Group_Buying_Account_Balance_Payments::get_instance();

		// Admin
		self::$settings_page = self::register_settings_page( 'account_records', self::__( 'Accounts' ), self::__( 'Accounts' ), 9, FALSE, 'records', array( get_class(), 'display_table' ) );
		// User Admin columns
		add_filter ( 'manage_users_columns', array( get_class(), 'user_register_columns' ) );
		add_filter ( 'manage_users_custom_column', array( get_class(), 'user_column_display' ), 10, 3 );

		// Init other classes, this allows for the paths to be changed
		add_action( 'init', array( get_class(), 'wp_init' ) );

		// AJAX Actions
		add_action( 'wp_ajax_nopriv_gbs_ajax_get_account',  array( get_class(), 'ajax_get_account' ), 10, 0 );
		add_action( 'wp_ajax_gbs_ajax_get_account',  array( get_class(), 'ajax_get_account' ), 10, 0 );
	}

	/**
	 * Init all other Account classes
	 *
	 * @return void
	 */
	public static function wp_init() {
		// Initialize the classes for each of the form pages
		Group_Buying_Accounts_Login::init();
		Group_Buying_Accounts_Registration::init();
		Group_Buying_Accounts_Edit_Profile::init();
		Group_Buying_Accounts_Retrieve_Password::init();
		Group_Buying_Accounts_Checkout::init();
	}

	public static function register_credit_type( $credit_types = array() ) {
		$credit_types[self::CREDIT_TYPE] = self::__( 'Account Balance' );
		return $credit_types;
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_url_paths';
		add_settings_section( $section, self::__( 'Custom URL Paths' ), array( get_class(), 'display_account_paths_section' ), $page );

		// Settings
		register_setting( $page, self::ACCOUNT_PATH_OPTION );
		add_settings_field( self::ACCOUNT_PATH_OPTION, self::__( 'Account Path' ), array( get_class(), 'display_account_path' ), $page, $section );
	}

	public static function display_account_paths_section() {
		Group_Buying_Controller::flush_rewrite_rules();
		echo self::__( '<h4>Customize the Account paths</h4>' );
	}

	public static function display_account_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="'.self::ACCOUNT_PATH_OPTION.'" id="'.self::ACCOUNT_PATH_OPTION.'" value="' . esc_attr( self::$account_path ) . '"  size="40" /><br />';
	}

	public static function admin_style() {
		global $post;
		if ( $post && $post->post_type == Group_Buying_Account::POST_TYPE ) {
		?>
			<style type="text/css">
				#minor-publishing-actions, #misc-publishing-actions, #delete-action { display:none; }
			</style>
		<?php
		}	
	}

	public static function add_meta_boxes() {
		add_meta_box( 'gb_account_contact_info', self::__( 'Contact Info' ), array( get_class(), 'show_meta_box' ), Group_Buying_Account::POST_TYPE, 'advanced', 'high' );
		add_meta_box( 'gb_account_purchases', self::__( 'Purchases' ), array( get_class(), 'show_meta_box' ), Group_Buying_Account::POST_TYPE, 'advanced', 'high' );
		add_meta_box( 'gb_account_credits', self::__( 'Credits' ), array( get_class(), 'show_meta_box' ), Group_Buying_Account::POST_TYPE, 'advanced', 'high' );
	}

	public static function show_meta_box( $post, $metabox ) {
		$account = Group_Buying_Account::get_instance_by_id( $post->ID );
		switch ( $metabox['id'] ) {
		case 'gb_account_credits':
			self::show_meta_box_gb_account_credits( $account, $post, $metabox );
			break;
		case 'gb_account_contact_info':
			self::show_meta_box_gb_account_contact_info( $account, $post, $metabox );
			break;
		case 'gb_account_purchases':
			self::show_meta_box_gb_account_purchases( $account, $post, $metabox );
			break;
		default:
			self::unknown_meta_box( $metabox['id'] );
			break;
		}
	}

	public static function save_meta_boxes( $post_id, $post ) {
		// only continue if it's an account post
		if ( $post->post_type != Group_Buying_Account::POST_TYPE ) {
			return;
		}
		// don't do anything on autosave, auto-draft, bulk edit, or quick edit
		if ( wp_is_post_autosave( $post_id ) || $post->post_status == 'auto-draft' || defined( 'DOING_AJAX' ) || isset( $_GET['bulk_edit'] ) ) {
			return;
		}
		// save all the meta boxes
		$account = Group_Buying_Account::get_instance_by_id( $post_id );
		if ( !is_a( $account, 'Group_Buying_Account' ) ) {
			return; // The account doesn't exist
		}
		self::save_meta_box_gb_account_contact_info( $account, $post_id, $post );
		self::save_meta_box_gb_account_credits( $account, $post_id, $post );
	}

	private static function show_meta_box_gb_account_contact_info( Group_Buying_Account $account, $post, $metabox ) {
		$address = $account->get_address();
		self::load_view( 'meta_boxes/account-contact-info', array(
				'first_name' => $account->get_name( 'first' ),
				'last_name' => $account->get_name( 'last' ),
				'street' => isset( $address['street'] )?$address['street']:'',
				'city' => isset( $address['city'] )?$address['city']:'',
				'zone' => isset( $address['zone'] )?$address['zone']:'',
				'postal_code' => isset( $address['postal_code'] )?$address['postal_code']:'',
				'country' => isset( $address['country'] )?$address['country']:'',
			), FALSE );
	}

	private static function save_meta_box_gb_account_contact_info( Group_Buying_Account $account, $post_id, $post ) {
		$first_name = isset( $_POST['account_first_name'] ) ? $_POST['account_first_name'] : '';
		$account->set_name( 'first', $first_name );
		$last_name = isset( $_POST['account_last_name'] ) ? $_POST['account_last_name'] : '';
		$account->set_name( 'last', $last_name );
		$address = array(
			'street' => isset( $_POST['account_street'] ) ? $_POST['account_street'] : '',
			'city' => isset( $_POST['account_city'] ) ? $_POST['account_city'] : '',
			'zone' => isset( $_POST['account_zone'] ) ? $_POST['account_zone'] : '',
			'postal_code' => isset( $_POST['account_postal_code'] ) ? $_POST['account_postal_code'] : '',
			'country' => isset( $_POST['account_country'] ) ? $_POST['account_country'] : '',
		);
		$account->set_address( $address );
	}

	private static function show_meta_box_gb_account_credits( Group_Buying_Account $account, $post, $metabox ) {
		$types = self::account_credit_types();
		$credit_fields = array();
		foreach ( $types as $key => $label ) {
			$credit_fields[$key] = array(
				'balance' => $account->get_credit_balance( $key ),
				'label' => $label,
			);
		}
		$credit_types = apply_filters( 'gb_account_meta_box_credit_types', $credit_fields, $account );
		self::load_view( 'meta_boxes/account-credits', array(
				'account' => $account,
				'credit_types' => $credit_fields
			), FALSE );
	}

	private static function account_credit_types() {
		return apply_filters( 'gb_account_credit_types', array() );
	}

	private static function save_meta_box_gb_account_credits( Group_Buying_Account $account, $post_id, $post ) {
		if ( isset( $_POST['account_credit_balance'] ) && is_array( $_POST['account_credit_balance'] ) ) {
			$types = array_keys( self::account_credit_types() );
			foreach ( $_POST['account_credit_balance'] as $key => $value ) {
				if ( in_array( $key, $types ) && is_numeric( $value ) ) {
					$balance = $account->get_credit_balance( $key );
					switch ( $_POST['account_credit_action'][$key] ) {
					case 'add':
						$total = $balance+$value;
						break;
					case 'deduct':
						$total = $balance-$value;
						break;
					case 'change':
						$total = $value;
						break;
					}
					$account->set_credit_balance( $total, $key );
					$data = $_POST;
					$data['adjustment_value'] = $value;
					$data['current_total'] = $total;
					$data['prior_total'] = $balance;
					do_action( 'gb_new_record', $_POST['account_credit_notes'][$key], Group_Buying_Accounts::$record_type . '_' . $key, gb__( 'Credit Adjustment' ), get_current_user_id(), $account->get_ID(), $data );
					do_action( 'gb_save_meta_box_gb_account_credits', $account, $post_id, $_POST );
				}
			}
		}
	}

	private static function show_meta_box_gb_account_purchases( Group_Buying_Account $account, $post, $metabox ) {
		do_action( 'gb_account_purchases_meta_box_top', $account, $post );
		self::load_view( 'meta_boxes/account-purchases', array( 'account'=>$account ), TRUE );
		do_action( 'gb_account_purchases_meta_box_bottom', $account, $post );
	}

	/**
	 * If a user's contact info isn't saved, try to get if from their billing
	 * information when checkout is finished.
	 *
	 * @static
	 * @param Group_Buying_Checkouts $checkout
	 * @return void
	 */
	public static function save_contact_info_from_checkout( Group_Buying_Checkouts $checkout ) {
		$account = Group_Buying_Account::get_instance();
		$address = $account->get_address();
		$new_address = $address;
		$first_name = $account->get_name( 'first' );
		$last_name = $account->get_name( 'last' );
		if ( !$first_name && isset( $checkout->cache['billing']['first_name'] ) && $checkout->cache['billing']['first_name'] ) {
			$account->set_name( 'first', $checkout->cache['billing']['first_name'] );
		}
		if ( !$last_name && isset( $checkout->cache['billing']['last_name'] ) && $checkout->cache['billing']['last_name'] ) {
			$account->set_name( 'last', $checkout->cache['billing']['last_name'] );
		}
		foreach ( array( 'street', 'city', 'zone', 'postal_code', 'country' ) as $key ) {
			if ( ( !isset( $address[$key] ) || !$address[$key] ) && isset( $checkout->cache['billing'][$key] ) && $checkout->cache['billing'][$key] ) {
				$new_address[$key] = $checkout->cache['billing'][$key];
			}
		}
		if ( $address != $new_address ) {
			$account->set_address( $new_address );
		}
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url( self::$account_path ) );
		} else {
			return add_query_arg( self::ACCOUNT_QUERY_VAR, 1, home_url() );
		}
	}

	public static function on_account_page() {
		self::login_required();
		self::get_instance(); // make sure the class is instantiated
	}

	public static function user_register_columns( $columns ) {
		// create a new array with account just after username, then everything else
		$new_columns = array();
		if ( isset($columns['username']) ) {
			$new_columns['username'] = $columns['username'];
			unset($columns['username']);
		}
		$new_columns['account'] = self::__( 'Account' );
		$new_columns = array_merge($new_columns, $columns);
		return $new_columns;
	}

	public static function user_column_display( $empty='', $column_name, $id ) {
		$account = Group_Buying_Account::get_instance( $id );

		if ( !$account )
			return; // return for that temp post

		switch ( $column_name ) {
		case 'account':
			$account_id = $account->get_ID();
			$user_id = $account->get_user_id_for_account( $account_id );
			$user = get_userdata( $user_id );
			$get_name = $account->get_name();
			$name = ( strlen( $get_name ) <= 1  ) ? '' : $get_name;

			//Build row actions
			$actions = array(
				'edit'    => sprintf( '<a href="post.php?post=%s&action=edit">'.self::__( 'Manage' ).'</a>', $account_id ),
				'payments'    => sprintf( '<a href="admin.php?page=group-buying/payment_records&account_id=%s">'.self::__( 'Payments' ).'</a>', $account_id ),
				'purchases'    => sprintf( '<a href="admin.php?page=group-buying/purchase_records&account_id=%s">'.self::__( 'Orders').'</a>', $account_id ),
				'vouchers'    => sprintf( '<a href="admin.php?page=group-buying/voucher_records&account_id=%s">'.self::__( 'Vouchers').'</a>', $account_id ),
				'gifts'    => sprintf( '<a href="admin.php?page=group-buying/gift_records&account_id=%s">'.self::__( 'Gifts').'</a>', $account_id )
			);

			//Return the title contents
			return sprintf( self::__( '%1$s <span style="color:silver">(account&nbsp;id:%2$s)</span> <span style="color:silver">(user&nbsp;id:%3$s)</span>%4$s' ),
				$name,
				$account_id,
				$user_id,
				WP_List_Table::row_actions( $actions )
			);
			break;

		default:
			// code...
			break;
		}
	}

	public static function ajax_get_account() {
		$id = $_POST['id'];
		if ( !$id ) {
			return;
		}
		$user = get_userdata( $id );
		if ( is_a( $user, 'WP_User' ) ) { // Check if id is a WP User
			$account = Group_Buying_Account::get_instance( $id );
		} else {
			$account = Group_Buying_Account::get_instance_by_id( $id );
		}
		if ( is_a( $account, 'Group_Buying_Account' ) ) {

			header( 'Content-Type: application/json' );
			$response = array(
				'account_id' => $account->get_ID(),
				'user_id' => $account->get_user_id(),
				'name' => gb_get_name( $account->get_user_id() ),
				'rewards' => $account->get_credit_balance( Group_Buying_Affiliates::CREDIT_TYPE ),
				'credits' => $account->get_credit_balance( Group_Buying_Accounts::CREDIT_TYPE ),
				'merchant_id' => gb_account_merchant_id( $account->get_user_id() ),
				'address' => gb_format_address( $account->get_address() )
			);
			echo json_encode( $response );	
		}
		exit();
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
		self::do_not_cache(); // never cache the account pages
		add_action( 'pre_get_posts', array( $this, 'edit_query' ), 10, 1 );
		add_action( 'the_post', array( $this, 'view_profile' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'get_title' ), 10, 2 );
		add_filter( 'gb_account_view_panes', array( $this, 'get_panes' ), 10, 2 );
	}

	/**
	 * Edit the query on the profile edit page to select the user's account.
	 *
	 * @param WP_Query $query
	 * @return void
	 */
	public function edit_query( WP_Query $query ) {
		// we only care if this is the query to show the account
		if ( isset( $query->query_vars[self::ACCOUNT_QUERY_VAR] ) && $query->query_vars[self::ACCOUNT_QUERY_VAR] ) {
			$query->query_vars['post_type'] = Group_Buying_Account::POST_TYPE;
			$query->query_vars['p'] = Group_Buying_Account::get_account_id_for_user();
		}
	}

	/**
	 * Update the global $pages array with the HTML for the page.
	 *
	 * @param object  $post
	 * @return void
	 */
	public function view_profile( $post ) {
		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			remove_filter( 'the_content', 'wpautop' );
			$account = Group_Buying_Account::get_instance();
			$panes = apply_filters( 'gb_account_view_panes', array(), $account );
			uasort( $panes, array( get_class(), 'sort_by_weight' ) );
			$view = self::load_view_to_string( 'account/view', array(
					'panes' => $panes,
				) );
			global $pages;
			$pages = array( $view );
		}
	}

	/**
	 * Filter the array of panes for the account view page
	 *
	 * @param array   $panes
	 * @param Group_Buying_Account $account
	 * @return array
	 */
	public function get_panes( array $panes, Group_Buying_Account $account ) {
		$panes['contact'] = array(
			'weight' => 0,
			'body' => self::load_view_to_string( 'account/contact-info', array(
					'first_name' => $account->get_name( 'first' ),
					'last_name' => $account->get_name( 'last' ),
					'name' => $account->get_name(),
					'address' => $account->get_address(),
				) ),
		);
		return $panes;
	}

	/**
	 * Filter 'the_title' to display the title of the page rather than the user name
	 *
	 * @static
	 * @param string  $title
	 * @param int     $post_id
	 * @return string
	 */
	public function get_title(  $title, $post_id  ) {
		$post = &get_post( $post_id );
		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			return self::__( "Your Account" );
		}
		return $title;
	}

	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'edit_accounts',
			'title' => self::__( 'Edit Accounts' ),
			'href' => gb_admin_url( 'account_records' ),
			'weight' => 5,
		);
		return $items;
	}

	public static function display_table() {
		//Create an instance of our package class...
		$wp_list_table = new Group_Buying_Accounts_Table();
		//Fetch, prepare, sort, and filter our data...
		$wp_list_table->prepare_items();
?>
		<script type="text/javascript" charset="utf-8">
			jQuery(document).ready(function($){
				jQuery(".gb_suspend").on('click', function(event) {
					event.preventDefault();
						if( confirm( '<?php gb_e("This will modify a users access to your site. Are you sure?") ?>' ) ) {
							var $suspend_link = $( this ),
							account_id = $suspend_link.attr( 'ref' );
							$.post( ajaxurl, { action: 'gbs_destroyer', type: 'account', id: account_id, destroyer_nonce: '<?php echo wp_create_nonce( Group_Buying_Destroy::NONCE ) ?>' },
								function( data ) {
										$suspend_link.parent().html( '<?php gb_e('Modified') ?>' );
									}
								);
						} else {
							// nothing to do.
						}
				});
			});
		</script>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2 class="nav-tab-wrapper">
				<?php self::display_admin_tabs(); ?>
			</h2>

			 <?php $wp_list_table->views() ?>
			<form id="payments-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $wp_list_table->search_box( gb__( 'Account ID' ), 'account_id' ); ?>
				<?php $wp_list_table->display() ?>
			</form>
		</div>
		<?php
	}
}

class Group_Buying_Accounts_Edit_Profile extends Group_Buying_Controller {
	const EDIT_PROFILE_PATH_OPTION = 'gb_account_edit_profile_path';
	const EDIT_PROFILE_QUERY_VAR = 'gb_account_edit';
	const FORM_ACTION = 'gb_account_edit';
	private static $edit_profile_path = 'account/edit';
	private static $instance;

	public static function init() {
		self::$edit_profile_path = get_option( self::EDIT_PROFILE_PATH_OPTION, self::$edit_profile_path );
		self::register_path_callback( self::$edit_profile_path, array( get_class(), 'on_edit_profile_page' ), self::EDIT_PROFILE_QUERY_VAR, 'account/edit' );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 1 );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_url_paths';

		// Settings
		register_setting( $page, self::EDIT_PROFILE_PATH_OPTION );
		add_settings_field( self::EDIT_PROFILE_PATH_OPTION, self::__( 'Account Edit Path' ), array( get_class(), 'display_account_edit_path' ), $page, $section );
	}

	public static function display_account_edit_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::EDIT_PROFILE_PATH_OPTION . '" id="' . self::EDIT_PROFILE_PATH_OPTION . '" value="' . esc_attr( self::$edit_profile_path ) . '" size="40"/><br />';
	}

	public static function on_edit_profile_page() {
		self::login_required();
		self::get_instance(); // make sure the class is instantiated
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$edit_profile_path );
		} else {
			return add_query_arg( self::EDIT_PROFILE_QUERY_VAR, 1, home_url() );
		}
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
		self::do_not_cache(); // never cache the account pages
		add_filter( 'gb_validate_account_edit_form', array( $this, 'validate_account_fields' ), 0, 2 );
		if ( isset( $_POST['gb_account_action'] ) && $_POST['gb_account_action'] == self::FORM_ACTION ) {
			$this->process_form_submission();
		}
		add_action( 'pre_get_posts', array( $this, 'edit_query' ), 10, 1 );
		add_action( 'the_post', array( $this, 'view_profile_form' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'get_title' ), 10, 2 );
		add_filter( 'gb_account_edit_panes', array( $this, 'get_panes' ), 0, 2 );
	}

	/**
	 * Edit the query on the profile edit page to select the user's account.
	 *
	 * @param WP_Query $query
	 * @return void
	 */
	public function edit_query( WP_Query $query ) {
		// we only care if this is the query to show a the edit profile form
		if ( isset( $query->query_vars[self::EDIT_PROFILE_QUERY_VAR] ) && $query->query_vars[self::EDIT_PROFILE_QUERY_VAR] ) {
			$query->query_vars['post_type'] = Group_Buying_Account::POST_TYPE;
			$query->query_vars['p'] = Group_Buying_Account::get_account_id_for_user();
		}
	}



	private function process_form_submission() {
		$errors = array();
		$account = Group_Buying_Account::get_instance();
		$user = $account->get_user();

		$errors = apply_filters( 'gb_validate_account_edit_form', $errors, $account );

		$user_email = apply_filters( 'user_registration_email', $_POST['gb_account_email'] );
		if ( !$errors && ( $user->user_email != $user_email || $_POST['gb_account_password'] ) ) { // we have wordpress account info to update
			$_POST['email'] = $user_email;
			if ( $_POST['gb_account_password'] ) {
				$_POST['pass1'] = $_POST['gb_account_password'];
				$_POST['pass2'] = $_POST['gb_account_password_confirm'];
			}
			require_once ABSPATH . 'wp-admin/includes/admin.php'; // so we can have the edit_user function
			$password_errors = edit_user( $account->get_user_id() );
			if ( is_wp_error( $password_errors ) ) {
				$errors = $password_errors->get_error_messages();
			}
		}

		if ( $errors ) {
			foreach ( $errors as $error ) {
				self::set_message( $error, self::MESSAGE_STATUS_ERROR );
			}
			return FALSE;
		}

		$first_name = isset( $_POST['gb_contact_first_name'] ) ? $_POST['gb_contact_first_name'] : '';
		$account->set_name( 'first', $first_name );
		$last_name = isset( $_POST['gb_contact_last_name'] ) ? $_POST['gb_contact_last_name'] : '';
		$account->set_name( 'last', $last_name );
		$address = array(
			'street' => isset( $_POST['gb_contact_street'] ) ? $_POST['gb_contact_street'] : '',
			'city' => isset( $_POST['gb_contact_city'] ) ? $_POST['gb_contact_city'] : '',
			'zone' => isset( $_POST['gb_contact_zone'] ) ? $_POST['gb_contact_zone'] : '',
			'postal_code' => isset( $_POST['gb_contact_postal_code'] ) ? $_POST['gb_contact_postal_code'] : '',
			'country' => isset( $_POST['gb_contact_country'] ) ? $_POST['gb_contact_country'] : '',
		);
		$account->set_address( $address );

		do_action( 'gb_process_account_edit_form', $account );

		self::set_message( self::__( 'Account updated' ) );
		wp_redirect( Group_Buying_Accounts::get_url(), 303 );
		exit;
	}

	public function validate_account_fields( $errors, $account ) {
		$user = $account->get_user();
		$user_email = apply_filters( 'user_registration_email', $_POST['gb_account_email'] );
		// Check the e-mail address
		if ( $user_email == '' ) {
			$errors[] = 'Please type your e-mail address.';
		} elseif ( ! is_email( $user_email ) ) {
			$errors[] = 'The email address isn&#8217;t correct.';
		} elseif ( $user_email != $user->user_email && email_exists( $user_email ) ) {
			$errors[] = 'This email is already registered, please choose another one.';
		}

		if ( $_POST['gb_account_password'] && !$_POST['gb_account_password_confirm'] ) {
			$errors[] = 'Please confirm your password.';
		} elseif ( $_POST['gb_account_password'] != $_POST['gb_account_password_confirm'] ) {
			$errors[] = 'The passwords you entered to not match.';
		}
		return $errors;
	}

	/**
	 * Add the default pane to the account edit form
	 *
	 * @param array   $panes
	 * @param Group_Buying_Account $account
	 * @return array
	 */
	public function get_panes( array $panes, Group_Buying_Account $account ) {
		$panes['account'] = array(
			'weight' => 0,
			'body' => self::load_view_to_string( 'account/edit-account-info', array( 'fields' => $this->account_info_fields( $account ) ) ),
		);
		$panes['contact'] = array(
			'weight' => 1,
			'body' => self::load_view_to_string( 'account/edit-contact-info', array( 'fields' => $this->contact_info_fields( $account ) ) ),
		);
		$panes['controls'] = array(
			'weight' => 1000,
			'body' => self::load_view_to_string( 'account/edit-controls', array() ),
		);
		return $panes;
	}

	private function account_info_fields( $account = NULL ) {
		if ( !$account ) {
			$account = Group_Buying_Account::get_instance();
		}
		$user = $account->get_user();
		$fields = array(
			'email' => array(
				'weight' => 0,
				'label' => self::__( 'Email Address' ),
				'type' => 'text',
				'required' => TRUE,
				'default' => $user->user_email,
			),
			'password' => array(
				'weight' => 10,
				'label' => self::__( 'Password' ),
				'type' => 'password',
				'required' => FALSE,
				'default' => '',
			),
			'password_confirm' => array(
				'weight' => 10.01,
				'label' => self::__( 'Confirm Password' ),
				'type' => 'password',
				'required' => FALSE,
				'default' => '',
			),
		);
		$fields = apply_filters( 'gb_account_edit_account_fields', $fields );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}

	private function contact_info_fields( $account = NULL ) {
		$fields = $this->get_standard_address_fields( $account );
		$fields = apply_filters( 'gb_account_edit_contact_fields', $fields );
		foreach ( $fields as $key => $value ) { // Remove all the required fields since we don't validate any of it anyway.
			if ( $value['required'] ) {
				unset( $fields[$key]['required'] );
			}
		}
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}

	/**
	 * Update the global $pages array with the HTML for the page.
	 *
	 * @param object  $post
	 * @return void
	 */
	public function view_profile_form( $post ) {
		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			remove_filter( 'the_content', 'wpautop' );
			$account = Group_Buying_Account::get_instance();
			$panes = apply_filters( 'gb_account_edit_panes', array(), $account );
			uasort( $panes, array( get_class(), 'sort_by_weight' ) );
			$view = self::load_view_to_string( 'account/edit', array(
					'panes' => $panes,
				) );
			global $pages;
			$pages = array( $view );
		}
	}

	/**
	 * Filter 'the_title' to display the title of the page rather than the user name
	 *
	 * @static
	 * @param string  $title
	 * @param int     $post_id
	 * @return string
	 */
	public function get_title(  $title, $post_id  ) {
		$post = &get_post( $post_id );
		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			$user = wp_get_current_user();
			if ( $user->display_name ) {
				$name = $user->display_name;
			} else {
				$name = $user->user_login;
			}
			return sprintf( self::__( 'Editing %s&rsquo;s Profile' ), $name );
		}
		return $title;
	}
}

class Group_Buying_Accounts_Registration extends Group_Buying_Controller {
	const REGISTER_PATH_OPTION = 'gb_account_register_path';
	const MINIMAL_REGISTRATION_OPTION = 'gb_minimal_registration';
	const REGISTER_QUERY_VAR = 'gb_account_register';
	const FORM_ACTION = 'gb_account_register';
	private static $register_path = 'account/register';
	private static $minimal_registration;
	private static $instance;
	private static $on_registration_page = FALSE;

	public static function init() {
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 1 );
		self::$register_path = get_option( self::REGISTER_PATH_OPTION, self::$register_path );
		self::$minimal_registration = get_option( self::MINIMAL_REGISTRATION_OPTION, 'FALSE' );
		self::register_path_callback( self::$register_path, array( get_class(), 'on_registration_page' ), self::REGISTER_QUERY_VAR, 'account/register' );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_url_paths';

		// Settings
		register_setting( $page, self::REGISTER_PATH_OPTION );
		// Fields
		add_settings_field( self::REGISTER_PATH_OPTION, self::__( 'Account Registration Path' ), array( get_class(), 'display_account_register_path' ), $page, $section );

		$section = 'gb_registration_settings';
		add_settings_section( $section, self::__( 'Registration Settings' ), array( get_class(), 'display_settings_section' ), $page );
		// Settings
		register_setting( $page, self::MINIMAL_REGISTRATION_OPTION );
		// Fields
		add_settings_field( self::MINIMAL_REGISTRATION_OPTION, self::__( 'Registration Fields' ), array( get_class(), 'display_registration_mini_option' ), $page, $section );
	}

	public static function display_registration_mini_option() {
		echo '<label><input type="radio" name="'.self::MINIMAL_REGISTRATION_OPTION.'" value="TRUE" '.checked( 'TRUE', self::$minimal_registration, FALSE ).'/> '.self::__( 'Minimal Registration with Username, E-Mail and Password.' ).'</label><br />';
		echo '<label><input type="radio" name="'.self::MINIMAL_REGISTRATION_OPTION.'" value="FALSE" '.checked( 'FALSE', self::$minimal_registration, FALSE ).'/> '.self::__( 'Full Registration with all contact fields' ).'</label><br />';
	}

	public static function display_account_register_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::REGISTER_PATH_OPTION . '" id="' . self::REGISTER_PATH_OPTION . '" value="' . esc_attr( self::$register_path ) . '" size="40"/><br />';
	}

	public static function on_registration_page() {
		self::$on_registration_page = TRUE;
		// Registered users shouldn't be here. Send them elsewhere
		if ( get_current_user_id() ) {
			wp_redirect( Group_Buying_Accounts::get_url(), 303 );
			exit();
		}
		if ( !get_option( 'users_can_register' ) ) {
			self::set_message( self::__( 'Registration Disabled.' ), self::MESSAGE_STATUS_ERROR );
			wp_redirect( add_query_arg( array( 'registration' => 'disabled' ), home_url() ) );
			exit();
		}
		self::get_instance(); // make sure the class is instantiated
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$register_path );
		} else {
			return add_query_arg( self::REGISTER_QUERY_VAR, 1, home_url() );
		}
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
		self::do_not_cache(); // never cache the account pages
		if ( isset( $_POST['gb_account_action'] ) && $_POST['gb_account_action'] == self::FORM_ACTION ) {
			$this->process_form_submission();
		}
		add_action( 'pre_get_posts', array( $this, 'edit_query' ), 10, 1 );
		add_action( 'the_post', array( $this, 'view_registration_form' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'get_title' ), 10, 2 );
		add_filter( 'gb_account_registration_panes', array( $this, 'get_panes' ), 0, 1 );
	}

	private function process_form_submission() {
		$errors = array();
		$email_address = isset( $_POST['gb_user_email'] )?$_POST['gb_user_email']:'';
		$username = isset( $_POST['gb_user_login'] )?$_POST['gb_user_login']:$email_address;
		$password = isset( $_POST['gb_user_password'] )?$_POST['gb_user_password']:'';
		$password2 = isset( $_POST['gb_user_password2'] )?$_POST['gb_user_password2']:'';
		$errors = array_merge( $errors, $this->validate_user_fields( $username, $email_address, $password, $password2 ) );
		if ( self::$minimal_registration == 'FALSE' ) {
			$errors = array_merge( $errors, $this->validate_contact_info_fields( $_POST ) );
		}
		$errors = apply_filters( 'gb_validate_account_registration', $errors, $username, $email_address, $_POST );
		if ( $errors ) {
			foreach ( $errors as $error ) {
				self::set_message( $error, self::MESSAGE_STATUS_ERROR );
			}
			return FALSE;
		} else {
			$sanitized_user_login = sanitize_user( $username );
			$user_email = apply_filters( 'user_registration_email', $email_address );
			$password = isset( $_POST['gb_user_password'] )?$_POST['gb_user_password']:'';
			$user_id = $this->create_user( $sanitized_user_login, $user_email, $password, $_POST );
			if ( $user_id ) {
				$user = wp_signon(
					array(
						'user_login' => $sanitized_user_login,
						'user_password' => $password,
						'remember' => false
					), false );
				do_action( 'gb_registration', $user, $sanitized_user_login, $user_email, $password, $_POST );

				if ( self::$on_registration_page ) {
					if ( isset( $_REQUEST['redirect_to'] ) && !empty( $_REQUEST['redirect_to'] ) ) {
						$redirect = str_replace( home_url(), '', $_REQUEST['redirect_to'] ); // in case the home_url is already added
						$url = home_url( $redirect );
					} else {
						$url = gb_get_last_viewed_redirect_url();
					}
					wp_redirect( apply_filters( 'gb_registration_redirect', $url ), 303 );
					exit();
				} else {
					wp_set_current_user( $user->ID );
				}
			}
		}
	}

	private function validate_user_fields( $username, $email_address, $password, $password2 ) {
		$errors = new WP_Error();
		if ( is_multisite() && GB_IS_AUTHORIZED_WPMU_SITE ) {
			$validation = wpmu_validate_user_signup( $username, $email_address );
			if ( $validation['errors']->get_error_code() ) {
				$errors = apply_filters( 'registration_errors_mu', $validation['errors'] );
			}
		} else { // Single-site install, so we don't have the wpmu functions
			// This is mostly just copied from register_new_user() in wp-login.php
			$sanitized_user_login = sanitize_user( $username );
			$user_email = apply_filters( 'user_registration_email', $email_address );

			if ( $password2 == '' )
				$password2 = $password;

			// check Password
			if ( $password == '' || $password2 == '' ) {
				$errors->add( 'empty_password', __( 'Please enter a password.' ) );
			} elseif ( $password != $password2 ) {
				$errors->add( 'password_mismatch', __( 'Passwords did not match.' ) );
			}
			// Check the username
			if ( $sanitized_user_login == '' ) {
				$errors->add( 'empty_username', __( 'Please enter a username.' ) );
			} elseif ( ! validate_username( $username ) ) {
				$errors->add( 'invalid_username', __( 'This username is invalid because it uses illegal characters. Please enter a valid username.' ) );
				$sanitized_user_login = '';
			} elseif ( username_exists( $sanitized_user_login ) ) {
				$errors->add( 'username_exists', __( 'This username is already registered, please choose another one.' ) );
			}

			// Check the e-mail address
			if ( $user_email == '' ) {
				$errors->add( 'empty_email', __( 'Please type your e-mail address.' ) );
			} elseif ( ! is_email( $user_email ) ) {
				$errors->add( 'invalid_email', __( 'The email address isn&#8217;t correct.' ) );
				$user_email = '';
			} elseif ( email_exists( $user_email ) ) {
				$errors->add( 'email_exists', __( 'This email is already registered, please choose another one.' ) );
			}

			do_action( 'register_post', $sanitized_user_login, $user_email, $password, $password2, $errors );
			$errors = apply_filters( 'registration_errors', $errors, $sanitized_user_login, $user_email, $password, $password2 );
		}
		if ( $errors->get_error_code() ) {
			return $errors->get_error_messages();
		} else {
			return array();
		}
	}

	private function validate_contact_info_fields( $submitted ) {
		$errors = array();
		$fields = $this->contact_info_fields();
		foreach ( $fields as $key => $data ) {
			if ( isset( $data['required'] ) && $data['required'] && !( isset( $submitted['gb_contact_'.$key] ) && $submitted['gb_contact_'.$key] != '' ) ) {
				$errors[] = sprintf( self::__( '"%s" field is required.' ), $data['label'] );
			}
		}
		return $errors;
	}

	public function create_user( $username, $email_address, $password = '', $submitted = array() ) {
		$password = ( $password != '' ) ? $password: wp_generate_password( 12, false );
		$username = ( !empty( $username ) ) ? $username : $email_address;
		$user_id = wp_create_user( $username, $password, $email_address );
		if ( !$user_id || is_wp_error( $user_id ) ) {
			self::set_message( self::__( 'Couldn&#8217;t register you... please contact the site administrator!' ) );
			return FALSE;
		}
		// Set contact info for the new account
		$account = Group_Buying_Account::get_instance( $user_id );

		if ( is_a( $account, 'Group_Buying_Account' ) ) {
			$first_name = isset( $submitted['gb_contact_first_name'] ) ? $submitted['gb_contact_first_name'] : '';
			$account->set_name( 'first', $first_name );
			$last_name = isset( $submitted['gb_contact_last_name'] ) ? $submitted['gb_contact_last_name'] : '';
			$account->set_name( 'last', $last_name );
			$address = array(
				'street' => isset( $submitted['gb_contact_street'] ) ? $submitted['gb_contact_street'] : '',
				'city' => isset( $submitted['gb_contact_city'] ) ? $submitted['gb_contact_city'] : '',
				'zone' => isset( $submitted['gb_contact_zone'] ) ? $submitted['gb_contact_zone'] : '',
				'postal_code' => isset( $submitted['gb_contact_postal_code'] ) ? $submitted['gb_contact_postal_code'] : '',
				'country' => isset( $submitted['gb_contact_country'] ) ? $submitted['gb_contact_country'] : '',
			);
			$account->set_address( $address );
		}

		wp_new_user_notification( $user_id );
		do_action( 'gb_account_created', $user_id, $_POST, $account );
		return $user_id;
	}

	/**
	 * Edit the query on the registration page to select the user's account.
	 *
	 * @param WP_Query $query
	 * @return void
	 */
	public function edit_query( WP_Query $query ) {
		// we only care if this is the query to show a the edit profile form
		if ( isset( $query->query_vars[self::REGISTER_QUERY_VAR] ) && $query->query_vars[self::REGISTER_QUERY_VAR] ) {
			$query->query_vars['post_type'] = Group_Buying_Account::POST_TYPE;
			$query->query_vars['p'] = Group_Buying_Account::get_account_id_for_user();
		}
	}

	/**
	 * Update the global $pages array with the HTML for the page.
	 *
	 * @param object  $post
	 * @return void
	 */
	public function view_registration_form( $post ) {
		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			remove_filter( 'the_content', 'wpautop' );
			$panes = apply_filters( 'gb_account_registration_panes', array() );
			uasort( $panes, array( get_class(), 'sort_by_weight' ) );
			$args = array();
			if ( isset( $_GET['redirect_to'] ) ) {
				$redirect = str_replace( home_url(), '', $_GET['redirect_to'] );
				$args['redirect'] = $redirect;
			}
			$view = self::load_view_to_string( 'account/register', array( 'panes'=>$panes, 'args' => $args ) );
			global $pages;
			$pages = array( $view );
		}
	}

	public static function get_registration_form() {
		$registration = Group_Buying_Accounts_Registration::get_instance(); // make sure the class is instantiated
		$panes = apply_filters( 'gb_account_registration_panes', array() );
		uasort( $panes, array( get_class(), 'sort_by_weight' ) );
		$args = array();
		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect = str_replace( home_url(), '', $_GET['redirect_to'] );
			$args['redirect'] = $redirect;
		}
		return self::load_view_to_string( 'account/register', array( 'panes'=>$panes, 'args' => $args ) );
	}

	/**
	 * Get the panes for the registration page
	 *
	 * @param array   $panes
	 * @return array
	 */
	public function get_panes( array $panes ) {
		$panes['user'] = array(
			'weight' => 0,
			'body' => $this->user_pane(),
		);
		if ( self::$minimal_registration == 'FALSE' ) {
			$panes['contact_info'] = array(
				'weight' => 10,
				'body' => $this->contact_info_pane(),
			);
		}
		$panes['controls'] = array(
			'weight' => 100,
			'body' => $this->load_view_to_string( 'account/register-controls', array() ),
		);
		return $panes;
	}

	private function user_pane() {
		return $this->load_view_to_string( 'account/register-user', array( 'fields' => $this->user_info_fields() ) );
	}

	private function user_info_fields() {
		$fields = array();
		$fields['login'] = array(
			'weight' => 0,
			'label' => self::__( 'Username' ),
			'type' => 'text',
			'required' => TRUE,
		);
		$fields['email'] = array(
			'weight' => 5,
			'label' => self::__( 'Email Address' ),
			'type' => 'text',
			'required' => TRUE,
		);
		$fields['password'] = array(
			'weight' => 10,
			'label' => self::__( 'Password' ),
			'type' => 'password',
			'required' => TRUE,
		);
		$fields['password2'] = array(
			'weight' => 15,
			'label' => self::__( 'Confirm Password' ),
			'type' => 'password',
			'required' => TRUE,
		);
		$fields = apply_filters( 'gb_account_register_user_fields', $fields );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}

	private function contact_info_pane() {
		return $this->load_view_to_string( 'account/register-contact-info', array( 'fields' => $this->contact_info_fields() ) );
	}

	private function contact_info_fields() {
		$fields = $this->get_standard_address_fields();
		$fields = apply_filters( 'gb_account_register_contact_info_fields', $fields );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}

	/**
	 * Filter 'the_title' to display the title of the page rather than the user name
	 *
	 * @static
	 * @param string  $title
	 * @param int     $post_id
	 * @return string
	 */
	public function get_title(  $title, $post_id  ) {
		$post = &get_post( $post_id );
		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			return self::__( "Register" );
		}
		return $title;
	}
}

class Group_Buying_Accounts_Login extends Group_Buying_Controller {
	const LOGIN_PATH_OPTION = 'gb_account_login_path';
	const LOGIN_QUERY_VAR = 'gb_account_login';
	const FORM_ACTION = 'gb_account_login';
	private static $login_path = 'account/login';
	private static $instance;
	private static $on_login_page = FALSE;

	public static function init() {
		self::$login_path = get_option( self::LOGIN_PATH_OPTION, self::$login_path );
		self::register_path_callback( self::$login_path, array( get_class(), 'on_login_page' ), self::LOGIN_QUERY_VAR, 'account/login' );

		add_action( 'wp_loaded', array( get_class(), 'redirect_away_from_login' ) );

		add_action( 'wp_login_failed', array( get_class(), 'login_failed' ), 10, 1 );

		// Replace WP Login URIs
		add_filter( 'login_url', array( get_class(), 'login_url' ), 10, 2 );
		add_filter( 'logout_url' , array( get_class(), 'log_out_url' ), 100, 2 );
		add_action( 'admin_init' , array( get_class(), 'register_settings_fields' ), 10, 1 );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_url_paths';

		// Settings
		register_setting( $page, self::LOGIN_PATH_OPTION );
		add_settings_field( self::LOGIN_PATH_OPTION, self::__( 'Account Login Path' ), array( get_class(), 'display_account_registration_path' ), $page, $section );
	}

	public static function display_account_registration_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::LOGIN_PATH_OPTION . '" id="' . self::LOGIN_PATH_OPTION . '" value="' . esc_attr( self::$login_path ) . '" size="40"/><br />';
	}

	/**
	 * Redirects away from the login page.
	 *
	 */
	public function redirect_away_from_login() {
		global $pagenow;

		// check if it's part of a flash upload. TODO has to be a better method
		if ( isset( $_POST ) && !empty( $_POST['_wpnonce'] ) )
			return;

		// always redirect away from wp-login.php but check if the user is an admin before redirecting them.
		if ( 'wp-login.php' == $pagenow || 'wp-activate.php' == $pagenow || 'wp-signup.php' == $pagenow || ( !current_user_can( 'edit_posts' ) && is_admin() && !defined( 'DOING_AJAX' ) ) ) {
			// If they're logged in, direct to the account page
			if ( is_user_logged_in() ) {
				wp_redirect( apply_filters( 'gb_redirect_away_from_login', Group_Buying_Accounts::get_url() ) );
				exit();
			} else { // everyone else needs to login
				if ( !defined( 'DOING_AJAX' ) ) {
					$redirect = ( isset( $_GET['action'] ) ) ? add_query_arg( array( 'action' => $_GET['action'] ), self::get_url() ) : self::get_url() ;
					wp_redirect( apply_filters( 'gb_redirect_away_from_login', $redirect ) );
					exit();
				}
			}
		}
	}

	public static function on_login_page() {
		self::$on_login_page = TRUE;
		// Registered users shouldn't be here. Send them elsewhere
		if ( get_current_user_id() && !self::log_out_attempt() ) {
			wp_redirect( Group_Buying_Accounts::get_url(), 303 );
			exit();
		}
		if ( !empty( $_POST['gb_login'] ) && wp_verify_nonce( $_POST['gb_login'], 'gb_login_action' ) ) {
			// signin
			$user = wp_signon();
			if ( !is_wp_error( $user ) ) {
				$user_id = $user->ID;
				do_action( 'gb_user_logged_in', $user, $_REQUEST );
				if ( isset( $_POST['redirect_to'] ) && !empty( $_POST['redirect_to'] ) ) {
					$redirect_str = str_replace( home_url(), '', $_POST['redirect_to'] ); // in case the home_url is already added
					$redirect = home_url( $redirect_str );
					wp_redirect( apply_filters( 'gb_login_success_redirect', $redirect, $user_id ) );
				} else {
					wp_redirect( apply_filters( 'gb_login_success_redirect', gb_get_last_viewed_redirect_url(), $user_id ) );
				}
				exit();
			}
		} elseif ( self::log_out_attempt() ) {
			// logout
			wp_logout();

			if ( isset( $_GET['redirect_to'] ) ) {
				$redirect_to = add_query_arg( array( 'loggedout' => 'true', 'message' => 'loggedout' ), home_url( $_GET['redirect_to'] ) );
			} else {
				$redirect_to = add_query_arg( array( 'loggedout' => 'true', 'message' => 'loggedout' ), self::get_url() );
			}

			wp_redirect( $redirect_to );
			exit();

		}

		self::get_instance(); // make sure the class is instantiated
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url( trailingslashit( self::$login_path ) ) );
		} else {
			return add_query_arg( self::LOGIN_QUERY_VAR, 1, home_url() );
		}
	}

	public static function login_url( $url, $redirect ) {
		$url = self::get_url();
		$redirect = apply_filters( 'gb_login_url_redirect', $redirect );
		if ( $redirect ) {
			$redirect = str_replace( home_url(), '', $redirect );
			$url = add_query_arg( 'redirect_to', $redirect, $url );
		} else {
			$redirect = str_replace( home_url(), '', Group_Buying_Accounts::get_url() );
			$url = add_query_arg( 'redirect_to', $redirect, $url );
		}
		return $url;
	}

	public static function log_out_url(  $url = null, $redirect = null ) {
		$url = self::get_url();
		if ( $redirect ) {
			$redirect = str_replace( home_url(), '', $redirect );
			$url = add_query_arg( array( 'redirect_to' => $redirect, 'action' => 'logout', 'message' => 'loggedout' ), $url );
		} else {
			$url = add_query_arg( array( 'action' => 'logout', 'message' => 'loggedout' ), $url );
		}
		return $url;
	}

	public static function log_out_attempt() {
		return ( isset( $_GET['action'] ) && 'logout' == $_GET['action'] ) ? TRUE : FALSE;
	}

	public static function login_failed( $username ) {
		// recap a lot of wp-login.php
		if ( !empty( $_GET['loggedout'] ) )
			return;

		// If cookies are disabled we can't log in even with a valid user+pass
		if ( isset( $_POST['testcookie'] ) && empty( $_COOKIE[TEST_COOKIE] ) )
			$message = self::__( 'Cookies are Disabled' );

		if ( isset( $_GET['registration'] ) && 'disabled' == $_GET['registration'] )
			$message = self::__( 'Registration Disabled' );
		elseif ( isset( $_GET['checkemail'] ) && 'registered' == $_GET['checkemail'] )
			$message = self::__( 'Registered' );
		elseif ( isset( $_REQUEST['interim-login'] ) )
			$message = self::__( 'Error: Expired' );
		else
			$message = self::__( 'Username and/or Password Incorrect.' );

		$url = self::get_url();
		$url = add_query_arg( 'message', $message, self::get_url() );
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$url = add_query_arg( 'redirect_to', $_REQUEST['redirect_to'], $url );
		}
		self::set_message( $message, self::MESSAGE_STATUS_ERROR );
		wp_redirect( $url );
		exit();
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
		self::do_not_cache(); // never cache the account pages
		$this->check_messages();
		add_action( 'pre_get_posts', array( $this, 'edit_query' ), 10, 1 );
		add_action( 'the_post', array( $this, 'view_login_form' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'get_title' ), 10, 2 );
	}

	private function check_messages() {
		$messages = array(
			'test_cookie' => self::__( "Cookies are blocked or not supported by your browser. You must <a href='http://www.google.com/cookies.html'>enable cookies</a> to use this site." ),
			'loggedout' => self::__( 'You are now logged out.' ),
			'registerdisabled' => self::__( 'User registration is currently not allowed.' ),
			'confirm' => self::__( 'Check your e-mail for the password reset link.' ),
			'newpass' => self::__( 'Check your e-mail for your new password.' ),
			'registered' => self::__( 'Registration complete. Please log in' ),
			'expired' => self::__( 'Your session has expired. Please log in again.' ),
		);
		if ( isset( $_GET['message'] ) && isset( $messages[$_GET['message']] ) ) {
			self::set_message( $messages[$_GET['message']], self::MESSAGE_STATUS_ERROR );
		}
	}

	/**
	 * Edit the query on the profile edit page to select the user's account.
	 *
	 * @param WP_Query $query
	 * @return void
	 */
	public function edit_query( WP_Query $query ) {
		// we only care if this is the query to show a the edit profile form
		if ( isset( $query->query_vars[self::LOGIN_QUERY_VAR] ) && $query->query_vars[self::LOGIN_QUERY_VAR] ) {
			$query->query_vars['post_type'] = Group_Buying_Account::POST_TYPE;
			$query->query_vars['p'] = Group_Buying_Account::get_account_id_for_user();
		}
	}

	/**
	 * Update the global $pages array with the HTML for the page.
	 *
	 * @param object  $post
	 * @return void
	 */
	public function view_login_form( $post ) {

		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			remove_filter( 'the_content', 'wpautop' );
			$args = array();
			if ( isset( $_GET['redirect_to'] ) ) {
				$redirect = str_replace( home_url(), '', $_GET['redirect_to'] );
				$args['redirect'] = $redirect;
			}
			if ( isset( $_GET['action'] ) && $_GET['action'] == 'retrievepassword' ) {
				$view = self::load_view_to_string( 'account/retrievepassword', array( 'args' => $args ) );
			} else { //default
				if ( self::$on_login_page ) {
					$args['submit'] = '<input type="submit" name="submit" value="'.self::__( 'Sign In Now' ).'" class="form-submit" />';
				} else {
					$args['submit'] = '';
				}
				$view = self::load_view_to_string( 'account/login', array( 'args' => $args ) );
			}
			global $pages;
			$pages = array( $view );
		}
	}

	/**
	 * Filter 'the_title' to display the title of the page rather than the user name
	 *
	 * @static
	 * @param string  $title
	 * @param int     $post_id
	 * @return string
	 */
	public function get_title(  $title, $post_id  ) {
		$post = &get_post( $post_id );
		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			return self::__( "Login" );
		}
		return $title;
	}
}

class Group_Buying_Accounts_Retrieve_Password extends Group_Buying_Controller {
	const RP_PATH_OPTION = 'gb_account_rp_path';
	const RP_QUERY_VAR = 'gb_account_rp';
	private static $rp_path = 'account/retrievepassword';
	private static $instance;

	public static function init() {
		self::$rp_path = get_option( self::RP_PATH_OPTION, self::$rp_path );
		self::register_path_callback( self::$rp_path, array( get_class(), 'on_rp_page' ), self::RP_QUERY_VAR, 'account/retrievepassword' );
		// Replace WP Login URIs
		add_filter( 'lostpassword_url', array( get_class(), 'get_url' ), 10, 2 );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 1 );
		add_action( 'parse_request', array( get_class(), 'check_messages' ) );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_url_paths';

		// Settings
		register_setting( $page, self::RP_PATH_OPTION );
		add_settings_field( self::RP_PATH_OPTION, self::__( 'Account Retrieve Password Path' ), array( get_class(), 'display_account_login_path' ), $page, $section );
	}

	public static function display_account_login_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::RP_PATH_OPTION . '" id="' . self::RP_PATH_OPTION . '" value="' . esc_attr( self::$rp_path ) . '" size="40"/><br />';
	}


	public static function on_rp_page() {

		// Registered users shouldn't be here. Send them elsewhere
		if ( get_current_user_id() && !self::log_out_attempt() ) {
			wp_redirect( Group_Buying_Accounts::get_url(), 303 );
			exit();
		}

		if ( isset( $_GET['key'] ) ) {
			if ( self::reset_password( $_GET['key'] ) ) {
				self::set_message( __( 'Password Reset Successful.' ), self::MESSAGE_STATUS_INFO );
				wp_redirect( add_query_arg( array( 'checkemail' => 'newpass', 'message' => 'newpass' ), Group_Buying_Accounts_Edit_Profile::get_url() ) );
				exit();
			}

			wp_redirect( add_query_arg( array( 'error' => 'invalidkey', 'action' => 'lostpassword', 'message' => 'invalidkey' ), self::get_url() ) );
			exit();
		}
		elseif ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$message = self::retrieve_password();
			if ( $message == 'newpass' ) {
				wp_redirect( add_query_arg( array( 'message' => $message ), Group_Buying_Accounts_Login::get_url() ) );
			} else {
				wp_redirect( add_query_arg( array( 'message' => $message ), self::get_url() ) );
			}
			exit();
		}

		self::get_instance(); // make sure the class is instantiated
	}

	public static function log_out_attempt() {
		return ( isset( $_GET['action'] ) && 'logout' == $_GET['action'] ) ? TRUE : FALSE;
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$rp_path );
		} else {
			return add_query_arg( self::RP_QUERY_VAR, 1, home_url() );
		}
	}

	/**
	 * Handles sending password retrieval email to user.
	 */
	private static function retrieve_password() {
		global $wpdb;

		if ( empty( $_POST['user_login'] ) ) {
			return 'blank';
		}

		if ( strpos( $_POST['user_login'], '@' ) ) {
			$user_data = get_user_by( 'email', trim( $_POST['user_login'] ) );
			if ( empty( $user_data ) )
				return 'incorrect';
		}

		if ( !$user_data || empty( $user_data ) ) {
			$login = trim( $_POST['user_login'] );
			$user_data = get_userdatabylogin( $login );
		}

		if ( !$user_data ) {
			return 'incorrect';
		}

		// redefining user_login ensures we return the right case in the email
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		do_action( 'retrieve_password', $user_login );

		$allow = apply_filters( 'allow_password_reset', TRUE, $user_data->ID );

		if ( !$allow ) {
			return 'notallowed';
		}

		$key = $wpdb->get_var( $wpdb->prepare( "SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login ) );

		if ( empty( $key ) ) {
			// Generate something random for a key...
			$key = wp_generate_password( 20, false );
			do_action( 'gb_retrieve_password_key', $user_login, $key );
			// Now insert the new md5 key into the db
			$wpdb->update( $wpdb->users, array( 'user_activation_key' => $key ), array( 'user_login' => $user_login ) );
		}

		$data = array(
			'key' => $key,
			'user' => $user_data
		);

		do_action( 'gb_retrieve_password_notification', $data );

		return 'newpass';
	}

	/**
	 * Handles resetting the user's password.
	 */
	private static function reset_password( $key ) {
		global $wpdb;

		$key = preg_replace( '/[^a-z0-9]/i', '', $key );

		if ( empty( $key ) || is_array( $key ) )
			return false;

		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE user_activation_key = %s", $key ) );

		if ( empty( $user ) )
			return false;

		// Generate random password w/o those special_chars that we all hate
		$new_pass = wp_generate_password( 8, false );

		do_action( 'password_reset', $user, $new_pass );

		wp_set_password( $new_pass, $user->ID );
		$user = wp_signon(
			array(
				'user_login' => $user->user_login,
				'user_password' => $new_pass,
				'remember' => false
			), false );

		$data = array(
			'user' => $user,
			'new_pass' => $new_pass
		);

		do_action( 'gb_password_reset_notification', $data );

		wp_password_change_notification( $user );

		return true;
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
		self::do_not_cache(); // never cache the account pages
		$this->check_messages();
		add_action( 'pre_get_posts', array( $this, 'edit_query' ), 10, 1 );
		add_action( 'the_post', array( $this, 'view_rp_form' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'get_title' ), 10, 2 );
	}

	public function check_messages() {
		$messages = array(
			'test_cookie' => self::__( "Cookies are blocked or not supported by your browser. You must <a href='http://www.google.com/cookies.html'>enable cookies</a> to use this site." ),
			'confirm' => self::__( 'Check your e-mail for the password reset link.' ),
			'newpass' => self::__( 'Check your e-mail for your new password.' ),
			'incorrect' => self::__( 'Your username or email is incorrect' ),
			'notallowed' => self::__( 'Password reset is not allowed.' ),
			'blank' => self::__( 'Your username or email is incorrect' ),
			'invalidkey' => self::__( 'Invalid Password Reset Key.' ),
		);
		if ( isset( $_GET['message'] ) && isset( $messages[$_GET['message']] ) ) {
			self::set_message( $messages[$_GET['message']], self::MESSAGE_STATUS_ERROR );
		}
	}

	/**
	 * Edit the query on the reset password page
	 *
	 * @param WP_Query $query
	 * @return void
	 */
	public function edit_query( WP_Query $query ) {
		// we only care if this is the query to show a the edit profile form
		if ( isset( $query->query_vars[self::RP_QUERY_VAR] ) && $query->query_vars[self::RP_QUERY_VAR] ) {
			$query->query_vars['post_type'] = Group_Buying_Account::POST_TYPE;
			$query->query_vars['p'] = Group_Buying_Account::get_account_id_for_user();
		}
	}

	/**
	 * Update the global $pages array with the HTML for the page.
	 *
	 * @param object  $post
	 * @return void
	 */
	public function view_rp_form( $post ) {
		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			remove_filter( 'the_content', 'wpautop' );
			$args = array();
			if ( isset( $_GET['redirect_to'] ) ) {
				$redirect = str_replace( home_url(), '', $_GET['redirect_to'] );
				$args['redirect'] = $redirect;
			}
			$view = self::load_view_to_string( 'account/retrievepassword', array( 'args' => $args ) );
			global $pages;
			$pages = array( $view );
		}
	}

	/**
	 * Filter 'the_title' to display the title of the page rather than the user name
	 *
	 * @static
	 * @param string  $title
	 * @param int     $post_id
	 * @return string
	 */
	public function get_title(  $title, $post_id  ) {
		$post = &get_post( $post_id );
		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			return self::__( "Retrieve Password" );
		}
		return $title;
	}
}

class Group_Buying_Accounts_Checkout extends Group_Buying_Controller {
	private static $instance;
	const GUEST_PURCHASE_USER_FLAG = 'guest_purchase_user_flag';

	public static function init() {
		self::get_instance();
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
		add_filter( 'gb_checkout_panes_'.Group_Buying_Checkouts::PAYMENT_PAGE, array( $this, 'display_checkout_registration_form' ), 10, 2 );
		add_action( 'gb_checkout_action_'.Group_Buying_Checkouts::PAYMENT_PAGE, array( $this, 'process_checkout_registration_form' ), 0, 1 );
		add_filter( 'gb_valid_process_payment_page', array( $this, 'validate_payment_page' ), 10, 2 );
		add_action( 'gb_user_logged_in', array( $this, 'claim_cart' ) );
		add_filter( 'gb_checkout_account_registration_panes', array( $this, 'filter_registration_panes' ) );
		add_filter( 'gb_account_register_contact_info_fields', array( $this, 'filter_contact_fields_on_checkout_registration' ) );

		add_filter( 'gb_account_register_user_fields', array( $this, 'registration_fields' ), 10, 1 );
		add_action( 'checkout_completed', array( $this, 'checkout_complete' ), 10, 3 );
		// add_action( self::CRON_HOOK, array( get_class(), 'delete_temp_users' ), 10, 0 ); TODO
	}

	public function display_checkout_registration_form( $panes, $checkout ) {

		// Registered users shouldn't see the
		if ( get_current_user_id() ) {
			return $panes;
		}
		if ( !get_option( 'users_can_register' ) ) {
			$error_new_pane['error'] = array(
				'weight' => 1,
				'body' => gb__( 'Contact an Administrator, registrations are disabled.' ),
			);
			return $error_new_pane;
		}
		if ( !get_current_user_id() ) {
			$args = array();
			if ( get_option( 'users_can_register' ) ) {
				$args['registration_form'] = $this->get_registration_form();
			}
			$args['login_form'] = $this->get_login_form();
			$panes['account'] = array(
				'weight' => 1,
				'body' => self::load_view_to_string( 'checkout/login-or-register', $args ),
			);
		}
		return $panes;
	}

	public function registration_fields( $fields = array() ) {
		if ( gb_on_checkout_page() ) {
			$fields['guest_purchase'] = array(
				'weight' => -10,
				'label' => self::__( 'Guest Purchase' ),
				'type' => 'checkbox',
				'required' => FALSE,
				'value' => 1,
			);
			unset($fields['password2']);
		}
		return $fields;
	}

	private function get_registration_form() {
		$registration = Group_Buying_Accounts_Registration::get_instance(); // make sure the class is instantiated
		$panes = apply_filters( 'gb_account_registration_panes', array() );
		$panes = apply_filters( 'gb_checkout_account_registration_panes', $panes );
		uasort( $panes, array( get_class(), 'sort_by_weight' ) );
		$args = apply_filters( 'gb_checkout_account_registration_args', array() );
		$view = self::load_view_to_string( 'checkout/register', array( 'panes'=>$panes, 'args' => $args ) );
		return $view;
	}

	public function filter_registration_panes( $panes ) {
		//mc_subs - for design purposes but possibly wanted.
		//contact_info - Make it a minimal registration since the checkout process will save the billing info.

		// Create an array of unregistered panes so it can be more easily filtered.
		$unregistered_checkout_panes = apply_filters( 'unregistered_registration_checkout_panes', array( 'mc_subs', 'contact_info' ) );
		foreach ( $unregistered_checkout_panes as $pane_key ) {
			unset( $panes[$pane_key] );
		}
		return $panes;
	}

	public function filter_contact_fields_on_checkout_registration( $fields ) {
		if ( isset( $_POST['gb_login_or_register'] ) ) {
			return array();
		}
		return $fields;
	}

	private function get_login_form() {
		$args = apply_filters( 'gb_checkout_account_login_args', array() );
		$view = self::load_view_to_string( 'checkout/login', array( 'args' => $args ) );
		return $view;
	}

	/**
	 * Hook into the payment process page and check to see if the the user is trying to login or register.
	 * Check to see if the user has selected guest checkout as well.
	 * @param  Group_Buying_Checkouts $checkout 
	 * @return                            
	 */
	public function process_checkout_registration_form( Group_Buying_Checkouts $checkout ) {
		if ( !isset( $_POST['gb_login_or_register'] ) ) {
			return;
		}

		if ( $_POST['log'] != '' ) {
			$login = Group_Buying_Accounts_Login::get_instance(); // make sure the class is instantiated
			$user = wp_signon();
			wp_set_current_user( $user->ID );
			if ( !$user || !$user->ID ) {
				self::set_message( self::__( 'Login unsuccessful. Please try again.' ), self::MESSAGE_STATUS_ERROR );
			}
		} else {

			// Guest Checkout
			if ( isset( $_POST['gb_user_guest_purchase'] ) && $_POST['gb_user_guest_purchase'] ) {
				$cart = &$checkout->get_cart();
				$cart_id = $cart->get_id();

				// User
				$user_login = $cart_id;
				// Check if user exists
				if ( $user_id = username_exists( $user_login ) ) {
					// $user_id already set
				} else {
					$email = $cart_id . '-guestpurchase@' . str_replace( 'http://', '', site_url('', 'http') );
					$password = wp_generate_password();
					// Account info so that the user 
					$account_info = array();
					$account_info['gb_contact_first_name'] = self::__('Guest');
					$account_info['gb_contact_last_name'] = self::__('Purchase');
					// Create user
					$user_id = Group_Buying_Accounts_Registration::create_user( $user_login, $email, $password, $account_info );
					update_user_meta( $user_id, self::GUEST_PURCHASE_USER_FLAG, 1 );
				}

				$user = get_user_by( 'id', $user_id );
				if ( $user_id ) {
					$user = wp_signon(
						array(
							'user_login' => $user_login,
							'user_password' => $password,
							'remember' => false
						), false );
					wp_set_current_user( $user->ID );
				}
			}
			else { // Registration
				Group_Buying_Accounts_Registration::get_instance(); // instantiating should process the form
				$user = wp_get_current_user();
				if ( $user && $user->ID ) {
					self::set_message( self::__( 'Registration complete. Please continue with your purchase.' ) );
				}
			}
		}
		if ( $user && $user->ID ) {
			$cart = &$checkout->get_cart();
			$cart_id = $cart->get_id();
			Group_Buying_Cart::claim_anonymous_cart( $cart_id, $user->ID );
		}
		
		// Don't validate billing fields
		add_filter( 'gb_valid_process_payment_page_fields', '__return_false');
		// mark checkout incomplete
		add_filter( 'gb_valid_process_payment_page', '__return_false');
	}

	/**
	 * After checkout is complete check whether the purchase was made by a temp account and
	 * update that WP User with the Purchase ID, log the user out and change the guest purchase flag
	 * @param  Group_Buying_Checkouts $checkout 
	 * @param  Group_Buying_Payment   $payment  
	 * @param  Group_Buying_Purchase  $purchase 
	 * @return                            
	 */
	public static function checkout_complete( Group_Buying_Checkouts $checkout, Group_Buying_Payment $payment, Group_Buying_Purchase $purchase ) {
		$user_id = get_current_user_id();
		// $account_id = Group_Buying_Account::get_account_id_for_user( $user_id );
		if ( get_user_meta( $user_id, self::GUEST_PURCHASE_USER_FLAG, TRUE ) == 1 ) { // If the user is flagged as a guest user
			
			global $wpdb;
			$purchase_id = $purchase->get_id();
			$wpdb->query( $wpdb->prepare(  "UPDATE $wpdb->users SET user_login = '$purchase_id' WHERE ID = '$user_id'" ) ); // Not sure why the $wpdb->udpate method is undefined at this point.
			
			// Logout
	 		wp_logout();
	 		// Set the flag to the purchase ID so we can clean up the temp users that never purchase via a cron (TODO)
	 		update_user_meta( $user_id, self::GUEST_PURCHASE_USER_FLAG, $purchase_id );
		}
	}

	/**
	 * Delete temporary users without a purchase
	 * @return
	 */
	public static function delete_temp_users() {
		// get users with self::GUEST_PURCHASE_USER_FLAG set to 1
		// check registration date and delete if older than x days
	}

	public function validate_payment_page( $valid, $checkout ) {
		$user = wp_get_current_user();
		if ( !$user || !$user->ID ) {
			return FALSE;
		}
		return $valid;
	}

	/**
	 * Claim the anonymous cart after login.
	 *
	 * @param object  $user
	 * @return void
	 */
	public static function claim_cart( $user ) {
		$cart = Group_Buying_Cart::get_anonymous_cart_id();
		if ( $cart ) {
			Group_Buying_Cart::claim_anonymous_cart( $cart, $user->ID, TRUE );
		}
	}
}

class Group_Buying_Accounts_Upgrade {
	public static function upgrade_3_0() {

		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->users} LEFT JOIN {$wpdb->usermeta} ON {$wpdb->users}.ID = {$wpdb->usermeta}.user_id AND {$wpdb->usermeta}.meta_key = 'gb_account_upgraded' WHERE (  {$wpdb->usermeta}.meta_value IS NULL OR {$wpdb->usermeta}.meta_value LIKE '0')";
		$users = $wpdb->get_results( $sql );

		$user_count = count( $users ); $i = 0;
		foreach ( $users as $user ) {
			$i++;
			// Create Account
			$account = Group_Buying_Account::get_instance( $user->ID );
			// Display
			printf( '<p style="margin-left: 20px">%s of %s &mdash; User #%s</p>', $i, $user_count, $user->ID );
			flush();

			if ( !empty( $user->user_firstname ) ) {
				$account->set_name( 'first', $user->user_firstname );
			} else { // If there is no first name attempt to make one.
				$display_name = explode( ' ', $user->display_name );
				$account->set_name( 'first', $display_name[0] );
			}

			if ( !empty( $user->user_lastname ) ) {
				$account->set_name( 'last', $user->user_lastname );
			} else { // If there is no last name attempt to make one.
				$display_name = explode( ' ', $user->display_name );
				$account->set_name( 'last', $display_name[1] );
			}

			// Capture old credits
			$old_credits = get_user_meta( $user->ID, '_totalCredits', true );
			$account->add_credit( $old_credits, Group_Buying_Affiliates::CREDIT_TYPE );
			do_action( 'gb_upgrade_account', $account, $user );
			add_user_meta( $user->ID, 'gb_account_upgraded', '1', TRUE );
			unset( $account ); // for memory issues.
		}
	}
}




if ( !class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class Group_Buying_Accounts_Table extends WP_List_Table {
	protected static $post_type = Group_Buying_Account::POST_TYPE;

	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
				'singular' => 'account',     // singular name of the listed records
				'plural' => 'account', // plural name of the listed records
				'ajax' => false     // does this table support ajax?
			) );

	}

	function get_views() {

		$status_links = array();
		$num_posts = wp_count_posts( self::$post_type, 'readable' );
		$class = '';
		$allposts = '';

		$total_posts = array_sum( (array) $num_posts );

		// Subtract post types that are not included in the admin all list.
		foreach ( get_post_stati( array( 'show_in_admin_all_list' => false ) ) as $state )
			$total_posts -= $num_posts->$state;

		$class = empty( $_REQUEST['post_status'] ) ? ' class="current"' : '';
		$status_links['all'] = "<a href='admin.php?page=group-buying/account_records{$allposts}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' ) as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( empty( $num_posts->$status_name ) )
				continue;

			if ( isset( $_REQUEST['post_status'] ) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			// replace "Published" with "Complete".
			$label = str_replace( array( 'Published', 'Trash' ), array( 'Active', 'Suspended' ), translate_nooped_plural( $status->label_count, $num_posts->$status_name ) );
			$status_links[$status_name] = "<a href='admin.php?page=group-buying/account_records&post_status=$status_name'$class>" . sprintf( $label, number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return $status_links;
	}

	function extra_tablenav( $which ) {
?>
		<div class="alignleft actions">
<?php
		if ( 'top' == $which && !is_singular() ) {

			$this->months_dropdown( self::$post_type );

			submit_button( __( 'Filter' ), 'secondary', false, false, array( 'id' => 'post-query-submit' ) );
		}
?>
		</div>
<?php
	}


	/**
	 *
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array   $item        A singular item (one full row's worth of data)
	 * @param array   $column_name The name/slug of the column to be processed
	 * @return string Text or HTML to be placed inside the column <td>
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
		default:
			return apply_filters( 'gb_mngt_account_column_'.$column_name, $item ); // do action for those columns that are filtered in
		}
	}


	/**
	 *
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @param array   $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 */
	function column_title( $item ) {
		$account_id = $item->ID;
		$account = Group_Buying_Account::get_instance_by_id( $account_id );

		//Build row actions
		$actions = array(
			'payments'    => sprintf( '<a href="admin.php?page=group-buying/payment_records&account_id=%s">'.gb__('Payments').'</a>', $account_id ),
			'purchases'    => sprintf( '<a href="admin.php?page=group-buying/purchase_records&account_id=%s">'.gb__('Orders').'</a>', $account_id ),
			'vouchers'    => sprintf( '<a href="admin.php?page=group-buying/voucher_records&account_id=%s">'.gb__('Vouchers').'</a>', $account_id ),
			'gifts'    => sprintf( '<a href="admin.php?page=group-buying/gift_records&account_id=%s">'.gb__('Gifts').'</a>', $account_id )
		);

		//Return the title contents
		return sprintf( '%1$s <span style="color:silver">(account&nbsp;id:%2$s)</span>%3$s',
			$item->post_title,
			$item->ID,
			$this->row_actions( $actions )
		);
	}

	function column_username( $item ) {
		$account_id = $item->ID;
		$account = Group_Buying_Account::get_instance_by_id( $account_id );
		$user_id = $account->get_user_id_for_account( $account_id );
		$user = get_userdata( $user_id );
		$name = ( $account->is_suspended() ) ? '<span style="color:#BC0B0B">Suspended:</span> ' . $account->get_name() : $account->get_name() ;

		//Build row actions
		$suspend_text = ( $account->is_suspended() ) ? gb__('Revert Suspension'): gb__('Suspend');
		$actions = array(
			'edit'    => sprintf( '<a href="post.php?post=%s&action=edit">Manage</a>', $account_id ),
			'user'    => sprintf( '<a href="user-edit.php?user_id=%s">User</a>', $user_id ),
			'trash'    => '<span id="'.$account_id.'_suspend_result"></span><a href="javascript:void(0)" class="gb_suspend" id="'.$account_id.'_suspend" ref="'.$account_id.'">'.$suspend_text.'</a>'
		);

		//Return the title contents
		return sprintf( '%1$s %2$s <span style="color:silver">(user&nbsp;id:%3$s)</span>%4$s',
			get_avatar( $user->user_email, '35' ),
			$name,
			$user_id,
			$this->row_actions( $actions )
		);

	}

	function column_address( $item ) {
		$account_id = $item->ID;
		$account = Group_Buying_Account::get_instance_by_id( $account_id );
		echo gb_format_address( $account->get_address(), 'string', '<br />' );
	}

	function column_credits( $item ) {
		$account_id = $item->ID;
		$account = Group_Buying_Account::get_instance_by_id( $account_id );
		$credits = $account->get_credit_balance( Group_Buying_Affiliates::CREDIT_TYPE );
		if ( !$credits ) $credits = '0';
		echo $credits;
	}


	/**
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value
	 * is the column's title text.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 * */
	function get_columns() {
		$columns = array(
			'username' => gb__('Name'),
			'title'  => gb__('Account'),
			'address'  => gb__('Address'),
			'credits'  => gb__('Credits')
		);
		return apply_filters( 'gb_mngt_accounts_columns', $columns );
	}

	/**
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 * */
	function get_sortable_columns() {
		$sortable_columns = array(
			'title'  => array( 'title', true ),     // true means its already sorted
		);
		return apply_filters( 'gb_mngt_accounts_sortable_columns', $sortable_columns );
	}


	/**
	 * Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 * */
	function get_bulk_actions() {
		$actions = array();
		return apply_filters( 'gb_mngt_accounts_bulk_actions', $actions );
	}


	/**
	 * Prep data.
	 *
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 * */
	function prepare_items() {

		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 25;


		/**
		 * Define our column headers.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();


		/**
		 * REQUIRED. Build an array to be used by the class for column
		 * headers.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$filter = ( isset( $_REQUEST['post_status'] ) ) ? $_REQUEST['post_status'] : 'all';
		$args=array(
			'post_type' => Group_Buying_Account::POST_TYPE,
			'post_status' => $filter,
			'posts_per_page' => $per_page,
			'paged' => $this->get_pagenum()
		);
		// Search
		if ( isset( $_GET['s'] ) && $_GET['s'] != '' ) {
			$args = array_merge( $args, array( 's' => $_GET['s'] ) );
		}
		// Filter by date
		if ( isset( $_GET['m'] ) && $_GET['m'] != '' ) {
			$args = array_merge( $args, array( 'm' => $_GET['m'] ) );
		}
		$accounts = new WP_Query( $args );

		/**
		 * REQUIRED. *Sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = apply_filters( 'gb_mngt_accounts_items', $accounts->posts );

		/**
		 * REQUIRED. Register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
				'total_items' => $accounts->found_posts,                //WE have to calculate the total number of items
				'per_page'  => $per_page,                    //WE have to determine how many items to show on a page
				'total_pages' => $accounts->max_num_pages   //WE have to calculate the total number of pages
			) );
	}

}
