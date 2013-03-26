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

class Group_Buying_MailChimp extends Group_Buying_List_Services {
	const API_KEY = 'gb_mailchimp_api_key';
	const LIST_ID = 'gb_mailchimp_list_id';
	const GROUP_ID = 'gb_mailchimp_group_id';
	const FIELD_ID = 'gb_mailchimp_field_id';
	const SIGNUP_DOUBLEOPT_OPTION = 'gb_mailchimp_doubleopt';
	const SIGNUP_SENDWELCOME_OPTION = 'gb_mailchimp_sendwelcome';
	const LOCATION_PREF_OPTION = 'gb_location_prefs';
	protected static $instance;
	protected static $api;
	private static $api_key = '';
	private static $list_id = '';
	private static $group_id = '';
	private static $field_id = '';
	protected static $signup_doubleopt;
	protected static $signup_sendwelcome;


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

		self::$api_key = get_option( 'gb_mailchimp_api_key', '' );
		self::$list_id = get_option( self::LIST_ID, '' );
		self::$group_id = get_option( self::GROUP_ID, '' );
		self::$field_id = get_option( self::FIELD_ID, '' );
		self::$signup_doubleopt = get_option( self::SIGNUP_DOUBLEOPT_OPTION, 'true' );
		self::$signup_sendwelcome = get_option( self::SIGNUP_SENDWELCOME_OPTION, 'true' );

		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );

		add_filter( 'gb_account_registration_panes', array( $this, 'get_registration_panes' ), 100 );
		if ( !version_compare( Group_Buying::GB_VERSION, '4.2', '>=' ) ) { // TODO remove deprecated method and functions
			add_filter( 'gb_account_edit_panes', array( $this, 'get_edit_panes' ), 0, 2 );
		} else {
			add_filter( 'gb_account_edit_account_notificaiton_fields', array( $this, 'account_notification_fields' ), 10, 2 );
		}
		add_action( 'gb_process_account_edit_form', array( $this, 'process_form' ) );
		add_filter( 'gb_account_view_panes', array( $this, 'get_panes' ), 0, 2 );

		// AJAX options
		if ( is_admin() ) {
			add_filter( 'admin_head', array( get_class(), 'head' ) );
		}
		add_action( 'wp_ajax_mc_ajax_callback', array( get_class(), 'return_mc_options' ) );

	}

	public static function head() {
		if ( !isset( $_GET['page'] ) || $_GET['page'] != 'group-buying/subscription' ) {
			return;
		}
?>
		<script type="text/javascript" charset="utf-8">
			jQuery(document).ready(function($){

				var list_ajax_gif = '<span id="<?php echo self::LIST_ID ?>">'+gb_ajax_gif+'</span>';
				var group_ajax_gif = '<span id="<?php echo self::GROUP_ID ?>">'+gb_ajax_gif+'</span>';

				// After an API key is entered
				jQuery("#<?php echo self::API_KEY ?>").live('keyup', function() {
					// Var
					var api_key = $(this).val();

					// hide and show the ajax loader
					$("#<?php echo self::LIST_ID ?>").replaceWith(list_ajax_gif);
					$("#<?php echo self::GROUP_ID ?>").hide();

					// Get new select list
					$.post( gb_ajax_url, { action: 'mc_ajax_callback', mail_chimp_get_lists: api_key },
						function( data ) {
							if ( data ) {
								$("#<?php echo self::LIST_ID ?>").replaceWith(data);
								$("#<?php echo self::GROUP_ID ?>").replaceWith(group_ajax_gif);
							};
						}
					);
				});

				// After the list is changed
				jQuery("select#<?php echo self::LIST_ID ?>").live('change', function() {
					// Var
					var list = $(this).val();

					// show the ajax loader
					$("#<?php echo self::GROUP_ID ?>").replaceWith(group_ajax_gif);

					// Get and replace with the groups select list
					$.post( post_url, { action: 'mc_ajax_callback', mail_chimp_get_groups: list },
						function( data ) {
							if ( data ) {
								$("#<?php echo self::GROUP_ID ?>").replaceWith(data);
							};
						}
					);
				});
			});
		</script>
		<?php
	}

	public static function return_mc_options() {

		if ( !current_user_can( 'edit_posts' ) ) {
			return; // security check
		}
		if ( isset( $_REQUEST['mail_chimp_get_lists'] ) && $_REQUEST['mail_chimp_get_lists'] != '' ) {
			update_option( self::API_KEY, $_REQUEST['mail_chimp_get_lists'] );
			self::display_list_id_field( null, $_REQUEST['mail_chimp_get_lists'] );
			exit();
		} elseif ( isset( $_REQUEST['mail_chimp_get_groups'] ) && $_REQUEST['mail_chimp_get_groups'] != '' ) {
			update_option( self::LIST_ID, $_REQUEST['mail_chimp_get_groups'] );
			self::display_group_id_field( null, $_REQUEST['mail_chimp_get_groups'] );
			exit();
		} elseif ( isset( $_REQUEST['mail_chimp_get_lists'] ) || isset( $_REQUEST['mail_chimp_get_groups'] ) ) {
			exit();
		}

	}

	public static function register() {
		do_action( 'gb_register_mailchimp' );
		self::add_list_service( __CLASS__, self::__( 'MailChimp' ) );
	}

	public static function init_mc( $api_key = NULL ) {
		require_once 'utilities/MCAPI.class.php';
		if ( NULL === $api_key ) {
			$api_key = self::$api_key;
		}
		self::$api = new GB_MCAPI( $api_key );
		self::$api->setTimeout( 5 );
		return self::$api;
	}

	public function process_subscription( $email = null, $location = null ) {

		$retval = self::subscribe( $_POST['email_address'], $_POST['deal_location'] );

		if ( self::$api->errorCode == '79' ) {
			parent::success( $_POST['deal_location'], $_POST['email_address'] );
		}
		if ( self::$api->errorMessage ) {
			Group_Buying_Controller::set_message( apply_filters( 'subscribe_mc_error', self::$api->errorMessage ), 'error' );
		}
		// if it's a success, set a cookie and redirect
		if ( !self::$api->errorCode || self::$api->errorCode == '214' ) {
			if ( $_POST['deal_location'] != null ) {
				parent::success( $_POST['deal_location'], $_POST['email_address'] );
			} else {
				parent::success( 0, $_POST['email_address'] );
			}
		}
	}

	public function process_registration_subscription( $user = null, $user_login = null, $user_email = null, $password = null, $post = null ) {

		if ( isset( $post[self::LOCATION_PREF_OPTION] ) ) {
			// Set the location options
			$account = Group_Buying_Account::get_instance( $user->ID );
			add_post_meta( $account->get_ID(), '_'.self::LOCATION_PREF_OPTION, $post[self::LOCATION_PREF_OPTION] );
			self::subscribe( $user_email, $post[self::LOCATION_PREF_OPTION] );
		}
	}

	public static function subscribe( $email = null, $locations = null, $account = null ) {

		if ( null == $email ) {
			if ( isset( $_POST['email_address'] ) ) {
				$email = $_POST['email_address'];
			} else {
				$current_user = wp_get_current_user();
				$email = $current_user->user_email;
			}

		}
		if ( null == $locations && isset( $_POST['deal_location'] ) ) {
			$locations = $_POST['deal_location'];
		}
		if ( null == $account || !is_a( $account, 'Group_Buying_Account' ) ) {
			$user = get_user_by( 'email', $email );
			$account = Group_Buying_Account::get_instance( $user->ID );
		}

		self::init_mc();

		if ( is_array( $locations ) ) {
			$groups = implode( ",", $locations );
			foreach ( $locations as $location ) {
				// Add the location just in case it's not their already.
				$response = self::$api->listInterestGroupAdd( self::$list_id, $location, self::$group_id );
				//logs
			}
		} else {
			$groups = $locations;
			// Add the location just in case it's not their already.
			$response = self::$api->listInterestGroupAdd( self::$list_id, $locations, self::$group_id );
		}

		// default merge variables
		$merge_vars = array(
			'FNAME' => $account->get_name( 'first' ),
			'LNAME' => $account->get_name( 'last' ),
			'GROUPINGS' => array(
				array( 'id' => self::$group_id, 'groups' => $groups ),

			),
			//'MC_LOCATION'=>array('LATITUDE'=>34.0413, 'LONGITUDE'=>-84.3473),

		);
		$merge_vars = apply_filters( 'subscribe_mc_groupins', $merge_vars, self::$group_id );
		//logs
		if ( GBS_DEV ) error_log( "merge_vars: " . print_r( $merge_vars, true ) );

		// subscribe the email already.
		$retval = self::$api->listSubscribe(
			self::$list_id,
			$email,
			$merge_vars,
			$email_type = 'html',
			self::$signup_doubleopt,
			$update_existing = TRUE,
			$replace_interests = FALSE,
			self::$signup_sendwelcome  // If double_optin is true, this has no effect.
		);

		//logs
		if ( GBS_DEV ) error_log( "retval: " . print_r( $retval, true ) );
		if ( GBS_DEV ) error_log( "error code: " . print_r( self::$api->errorCode, true ) );
		if ( GBS_DEV ) error_log( "error: " . print_r( self::$api->errorMessage, true ) );
		return $response;
	}

	public function register_settings() {
		self::init_mc();
		$page = Group_Buying_List_Services::get_settings_page();
		$section = 'gb_mailchimp_sub';
		add_settings_section( $section, self::__( 'MailChimp Subscription Settings' ), array( $this, 'display_settings_section' ), $page );
		register_setting( $page, self::API_KEY );
		register_setting( $page, self::LIST_ID );
		register_setting( $page, self::GROUP_ID );
		register_setting( $page, self::FIELD_ID );
		register_setting( $page, self::SIGNUP_DOUBLEOPT_OPTION );
		register_setting( $page, self::SIGNUP_SENDWELCOME_OPTION );

		add_settings_field( self::API_KEY, self::__( 'API Key' ), array( $this, 'display_api_key_field' ), $page, $section );
		add_settings_field( self::LIST_ID, self::__( 'Mailing List' ), array( $this, 'display_list_id_field' ), $page, $section );
		add_settings_field( self::GROUP_ID, self::__( 'Location Group' ), array( $this, 'display_group_id_field' ), $page, $section );
		add_settings_field( self::SIGNUP_DOUBLEOPT_OPTION, self::__( 'Double Opt-in' ), array( get_class(), 'display_service_doubleopt_option' ), $page, $section );
		add_settings_field( self::SIGNUP_SENDWELCOME_OPTION , self::__( 'Send Welcome Message' ), array( get_class(), 'display_service_sendwelcome_option' ), $page, $section );

		//add_settings_field(self::FIELD_ID, self::__('Location Field ID'), array($this, 'display_field_id_field'), $page, $section);

		if ( isset( $_POST['mc_sync_locations'] ) ) {
			self::sync_mailchimp_locations();
		}
	}

	/**
	 * Add the default pane to the account edit form
	 *
	 * @param array   $panes
	 * @param Group_Buying_Account $account
	 * @return array
	 */
	public function get_registration_panes( array $panes ) {
		if ( parent::$registration_option !== 'false' ) {
			unset( $panes['subscription'] );
			$preference = null;
			if ( parent::$registration_option == 'checked' ) {
				$preference = ( isset( $_COOKIE[ 'gb_location_preference' ] ) ) ? $_COOKIE[ 'gb_location_preference' ] : '' ;
			}
			$panes['mc_subs'] = array(
				'weight' => 99,
				'body' => self::load_view_string( 'account-prefs', array( 'name' => self::LOCATION_PREF_OPTION, 'options' => (array)$preference, 'optin' => parent::$registration_option ) ),
			);
		}
		return $panes;
	}

	/**
	 * Add the default pane to the account overview form
	 *
	 * @param array   $panes
	 * @param Group_Buying_Account $account
	 * @return array
	 */
	public function get_panes( array $panes, Group_Buying_Account $account ) {
		$options = get_post_meta( $account->get_ID(), '_'.self::LOCATION_PREF_OPTION );
		$panes['mc_subs'] = array(
			'weight' => 500,
			'body' => self::load_view_string( 'account-subscriptions', array( 'name' => self::LOCATION_PREF_OPTION, 'options' => (array)$options[0] ) ),
		);
		return $panes;
	}

	/**
	 * Add the default pane to the account edit form
	 * @deprecated Deprecated in version 2.2 in favor of account_notification_fields
	 * @param array   $panes
	 * @param Group_Buying_Account $account
	 * @return array
	 */
	public function get_edit_panes( array $panes, Group_Buying_Account $account ) {
		$options = get_post_meta( $account->get_ID(), '_'.self::LOCATION_PREF_OPTION );
		$panes['mc_subs'] = array(
			'weight' => 10,
			'body' => self::load_view_string( 'account-prefs', array( 'name' => self::LOCATION_PREF_OPTION, 'options' => $options[0] ) ),
		);
		return $panes;
	}



	/**
	 * Add the daily email preferences to the notification section already within the account edit.
	 *
	 * @param array   $fields
	 * @param Group_Buying_Account $account
	 * @return array
	 */
	public function account_notification_fields( $fields, Group_Buying_Account $account ) {
		$options = get_post_meta( $account->get_ID(), '_'.self::LOCATION_PREF_OPTION, TRUE );

		$view = '';
		foreach ( gb_get_locations( FALSE ) as $location ) {
			$checked = ( in_array( $location->slug, (array)$options ) ) ? 'checked="checked"' : '' ;
			$view .= '<span class="location_pref_input_wrap"><label><input type="checkbox" name="'.self::LOCATION_PREF_OPTION.'[]" value="'.$location->slug.'" '.$checked.'>'.$location->name.'</label></span>';
		}

		$mc_fields = array(
			'mc_subscription' => array(
				'weight' => 10,
				'label' => self::__( 'Subscriptions' ),
				'type' => 'bypass',
				'required' => FALSE,
				'output' => $view
			)
		);
		$fields = array_merge( $fields, $mc_fields );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}

	/**
	 * Process the form submission and save the meta
	 *
	 * @param string
	 * @return string
	 * @author Dan Cameron
	 */
	public static function process_form( Group_Buying_Account $account ) {
		$locations = isset( $_POST[self::LOCATION_PREF_OPTION] ) ? $_POST[self::LOCATION_PREF_OPTION] : null;
		if ( !empty( $locations ) ) {
			$user = $account->get_user();
			$retval = self::subscribe( $user->user_email, $locations, $account );
			delete_post_meta( $account->get_ID(), '_'.self::LOCATION_PREF_OPTION );
			add_post_meta( $account->get_ID(), '_'.self::LOCATION_PREF_OPTION, $locations );
		}

	}

	private static function load_view_string( $path, $args ) {
		ob_start();
		if ( !empty( $args ) ) extract( $args );
		$template = locate_template( 'gbs-addons/subscription/list-services/mc-views/'.$path.'.php', FALSE );
		include $template;
		return ob_get_clean();
	}

	public static function display_api_key_field() {
		echo '<input type="text" name="'.self::API_KEY.'" value="'.self::$api_key.'" id="'.self::API_KEY.'"/>';
	}

	public static function display_list_id_field( $null = NULL, $api_key = NULL ) {
		$api = self::init_mc( $api_key );
		$lists = $api->lists();
		if ( !empty( $lists ) ) {
?>
				<select name="<?php echo self::LIST_ID ?>" id="<?php echo self::LIST_ID ?>">
					<?php
			if ( !empty( $lists ) || $lists['total'] == '0' ) {
				foreach ( $lists['data'] as $key ) {
					echo '<option value="'.$key['id'].'" '.selected( self::$list_id, $key['id'] ).'>'.$key['name'].'</option>';
				}
			}
?>
				</select>
			<?php
		} else {
			echo '<span id="'.self::LIST_ID.'">'.gb__( 'No lists were found using that API key.' ).'</span>';
		}
	}

	public static function display_group_id_field( $null = NULL, $list_id = NULL, $api_key = NULL ) {
		if ( NULL === $list_id ) {
			$list_id = self::$list_id;
		}
		$api = self::init_mc( $api_key );
		$grouping = $api->listInterestGroupings( $list_id );
		if ( !empty( $grouping ) ) {
?>
				<select name="<?php echo self::GROUP_ID ?>" id="<?php echo self::GROUP_ID ?>">
					<?php
			foreach ( $grouping as $key ) {
				echo '<option value="'.$key['id'].'" '.selected( self::$group_id, $key['id'] ).'>'.$key['name'].'</option>';
			}
?>
				</select>
			<?php
		} else {
			echo '<span id="'.self::GROUP_ID.'">'.gb__( 'No groups were found under the list selected above.' ).'</span>';
		}
	}

	public static function display_field_id_field() {
		$grouping = self::$api->listInterestGroupings( self::$list_id );
		if ( self::$api_key != '' && !empty( $grouping ) ) {
			echo "<ul>";
			foreach ( $grouping as $key ) {
				if ( $key['id'] == self::$group_id ) {
					foreach ( $key['groups'] as $key => $value ) {
						echo '<li>' . $value['name'] . '</li>';
					}
				}
			}
			echo "</ul>";
		}
	}

	public static function display_service_doubleopt_option() {
		echo '<label><input type="radio" name="'.self::SIGNUP_DOUBLEOPT_OPTION.'" value="false" '.checked( 'false', self::$signup_doubleopt, FALSE ).'/> '.self::__( 'No' ).'</label><br />';
		echo '<label><input type="radio" name="'.self::SIGNUP_DOUBLEOPT_OPTION.'" value="true" '.checked( 'true', self::$signup_doubleopt, FALSE ).'/> '.self::__( 'Yes' ).'</label>';
	}
	public static function display_service_sendwelcome_option() {
		echo '<label><input type="radio" name="'.self::SIGNUP_SENDWELCOME_OPTION.'" value="false" '.checked( 'false', self::$signup_sendwelcome, FALSE ).'/> '.self::__( 'No' ).'</label><br />';
		echo '<label><input type="radio" name="'.self::SIGNUP_SENDWELCOME_OPTION.'" value="true" '.checked( 'true', self::$signup_sendwelcome, FALSE ).'/> '.self::__( 'Yes' ).'</label>';
	}

	public static function sync_mailchimp_locations() {
		$locations = get_terms( gb_get_location_tax_slug(), array( 'fields'=>'all', 'hide_empty' => 0 ) );
		foreach ( $locations as $location ) {
			self::$api->listInterestGroupAdd( self::$list_id, $location->slug, self::$group_id );
		}
	}
}
Group_Buying_MailChimp::register();
