<?php

/**
 * Notification Controller
 *
 * @package GBS
 * @subpackage Notification
 */
class Group_Buying_Notifications extends Group_Buying_Controller {

	const META_BOX_PREFIX = 'gb_notification_shortcodes_';
	const NOTIFICATIONS_OPTION_NAME = 'gb_notifications';
	const EMAIL_FROM_NAME = 'gb_notification_from_name';
	const EMAIL_FROM_EMAIL = 'gb_notification_from_email';
	const EMAIL_FORMAT = 'gb_send_as_html';
	const NOTIFICATION_SUB_OPTION = 'gb_subscription_notifications';

	private static $notification_from_name;
	private static $notification_from_email;
	private static $notification_format;
	public static $notification_types;
	private static $shortcodes;
	private static $data;

	public static function init() {
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 20, 0 );

		self::$notification_from_name = get_option( self::EMAIL_FROM_NAME, get_bloginfo( 'name' ) );
		self::$notification_from_email = get_option( self::EMAIL_FROM_EMAIL, get_bloginfo( 'admin_email' ) );
		self::$notification_format = get_option( self::EMAIL_FORMAT, 'TEXT' );

		add_action( 'add_meta_boxes', array( get_class(), 'add_meta_boxes' ) );
		add_action( 'save_post', array( get_class(), 'save_meta_boxes' ), 10, 2 );
		add_action( 'load-post.php', array( get_class(), 'queue_notification_js' ) );
		add_action( 'load-post-new.php', array( get_class(), 'queue_notification_js' ) );
		add_action( 'admin_init', array( get_class(), 'create_notifications' ) );

		// Admin columns
		self::$settings_page = self::register_settings_page( 'notifications', self::__( 'Notifications' ), self::__( 'Notification Settings' ), 15, FALSE, 'general', array( get_class(), 'display_table' ) );
		add_action( 'admin_menu', array( get_class(), 'admin_menu' ) );

		// Subscription Settings
		add_filter( 'gb_account_edit_panes', array( get_class(), 'get_edit_panes' ), 20, 2 );
		add_action( 'gb_process_account_edit_form', array( get_class(), 'process_form' ) );

		self::hook_notifications();
	}

	public function admin_menu() {
		if ( version_compare( get_bloginfo( 'version' ), '3.2.99', '>=' ) ) { // 3.3. only
			add_action( 'load-edit.php', array( get_class(), 'help_section' ) );
			add_action( 'load-post.php', array( get_class(), 'help_section' ) );
			add_action( 'load-post-new.php', array( get_class(), 'help_section' ) );
		}
	}

	public static function help_section() {
		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : FALSE;
		if ( $post_id ) {
			$post_type = get_post_type( $post_id );
		} else {
			$post_type = ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) ) ? $_REQUEST['post_type'] : null ;
		}
		if ( $post_type == Group_Buying_Notification::POST_TYPE ) {
			$screen = get_current_screen();
			$screen->add_help_tab( array(
					'id'      => 'notification-help', // This should be unique for the screen.
					'title'   => self::__( 'Customize Notifications' ),
					'content' =>
					'<p><strong>' . self::__( 'Notifications are emails that are generated and sent by the GBS system.' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( '<a href="%s">General Options</a> includes a few notification options, including: "From Name", "From Email" and Email Format.".' ), admin_url( 'admin.php?page=group-buying/gb_settings#notification_settings' ) ) . '</p>',
				) );
			$screen->add_help_tab( array(
					'id'      => 'notification-help-disable', // This should be unique for the screen.
					'title'   => self::__( 'Disable Notifications' ),
					'content' =>
					'<p><strong>' . self::__( 'How to disable a notification.' ) . '</strong>' .
					'<ol>' .
					'<li>' . self::__( 'Select the Notification Type that you\'d like to disable.' ) . '</li>' .
					'<li>' . self::__( 'Check the disable notification within the Notification Type meta box on the right.' ) . '</li>' .
					'<li>' . self::__( 'Save or "Update" the notification.' ) . '</li>' .
					'</ol></p>' ,
				) );
			$screen->add_help_tab( array(
					'id'      => 'notification-help-html', // This should be unique for the screen.
					'title'   => self::__( 'HTML E-Mails' ),
					'content' =>
					'<p><strong>' . self::__( 'HTML emails are for advanced users.' ) . '</strong>' .
					'<ul>' .
					'<li>' . sprintf( self::__( 'Enable HTML emails within <a href="%s">General Options</a> under Notification Settings.' ), admin_url( 'admin.php?page=group-buying/gb_settings#notification_settings' ) ) . '</li>' .
					'<li>' . self::__( 'HTML emails must include HTML linebreaks. Linebreaks in the Visual editor will not appear in the emails.' ) . '</li>' .
					'</ul></p>' ,
				) );
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
				'<p>' . self::__( '<a href="http://groupbuyingsite.com/forum/showthread.php?724-Customize-Notifications" target="_blank">Documentation on Managing Notifications</a>' ) . '</p>' .
				'<p>' . self::__( '<a href="http://groupbuyingsite.com/forum/" target="_blank">Support Forums</a>' ) . '</p>'
			);
		}
	}

	private static function hook_notifications() {
		add_action( 'purchase_completed', array( get_class(), 'purchase_notification' ), 10, 1 );
		add_action( 'gb_gift_notification', array( get_class(), 'gift_notification' ), 10, 1 );
		add_action( 'deal_success', array( get_class(), 'deal_closed_notification' ), 10, 1 );
		add_action( 'deal_failed', array( get_class(), 'deal_failed_notification' ), 10, 1 );
		add_action( 'gb_registration', array( get_class(), 'registration_notification' ), 10, 5 );
		add_action( 'gb_retrieve_password_notification', array( get_class(), 'retrieve_password_notification' ), 10, 1 );
		add_action( 'gb_password_reset_notification', array( get_class(), 'password_reset_notification' ), 10, 1 );
		add_action( 'gb_apply_credits', array( get_class(), 'applied_credits' ), 10, 4 );
		add_action( 'gb_admin_notification', array( get_class(), 'admin_notification' ), 10, 2 );
		add_action( 'voucher_activated', array( get_class(), 'voucher_notification' ), 10, 1 );
		
		if ( GBS_DEV ) {
			add_action( 'init', array( get_class(), 'voucher_exp_notification' ) );
		} else {
			add_action( self::CRON_HOOK, array( get_class(), 'voucher_exp_notification' ) );
		}
	}

	public static function create_notifications() {
		self::init_types();
		foreach ( self::$notification_types as $notification_type => $data ) {
			$notification = self::get_notification( $notification_type );
			if ( is_null( $notification ) ) {
				$post_id = wp_insert_post( array(
						'post_status' => 'publish',
						'post_type' => Group_Buying_Notification::POST_TYPE,
						'post_title' => $data['default_title'],
						'post_content' => $data['default_content']
					) );
				$notification = Group_Buying_Notification::get_instance( $post_id );
				self::save_meta_box_gb_notification_type( $notification, $post_id, $notification_type );
				if ( $data['default_disabled'] ) {
					$notification->set_disabled( 'TRUE' );
				}
			}
		}

	}

	private static function init_types() {
		if ( !isset( self::$notification_types ) ) {
			// Notification types include a name and a list of shortcodes
			$default_notification_types = array(
				'purchase' => array(
					'name' => self::__( 'Deal Purchase' ),
					'description' => self::__( 'Customize the email that is sent to users after purchase.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'purchase_details', 'transid', 'site_title', 'site_url', 'total_paid', 'credits_used', 'rewards_used', 'total', 'billing_address', 'shipping_address' ),
					'default_title' => self::__( 'Purchase Confirmation from ' . get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/purchase', NULL )
				),
				'deal_closed' => array(
					'name' => self::__( 'Deal Closed' ),
					'description' => self::__( 'Customize the email that congratulates users that a deal successfully finished.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'deal_url', 'deal_title', 'site_title', 'site_url' ),
					'default_title' => self::__( 'Deal Expired at ' . get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/deal-closed', NULL )
				),
				'deal_failed' => array(
					'name' => self::__( 'Deal Failed' ),
					'description' => self::__( 'Customize the email that informs them the deal they purchases failed to reach the minimum buyers.' ),
					'shortcodes' => array( 'date', 'name', 'deal_url', 'deal_title', 'site_title', 'site_url' ),
					'default_title' => self::__( 'Deal Failed to tip at ' . get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/deal-failed', NULL )
				),
				'registration' => array(
					'name' => self::__( 'Registration' ),
					'description' => self::__( 'Customize the email that is sent to users when they register.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'site_title', 'site_url' ),
					'default_title' => self::__( 'Registration Confirmation at ' . get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/registration', NULL ),
					'allow_preference' => FALSE
				),
				'password_reset' => array(
					'name' => self::__( 'Password Reset Confirmation' ),
					'description' => self::__( 'Customize the confirmation email that is sent to users when they request a password reset.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'site_title', 'site_url', 'confirmation_url' ),
					'default_title' => self::__( 'Password Reset Notice from '  . get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/password-reset-confirmation', NULL ),
					'allow_preference' => FALSE
				),
				'temporary_password' => array(
					'name' => self::__( 'Temporary Password Notification' ),
					'description' => self::__( 'Customize the notification email that is sent to users when their password has been reset.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'site_title', 'site_url', 'temp_password' ),
					'default_title' => self::__( 'Your New Password for ' . get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/temporary-password-notification', NULL ),
					'allow_preference' => FALSE
				),
				'gift_notification' => array(
					'name' => self::__( 'Gift Notification' ),
					'description' => self::__( 'Customize the notification email that is sent a gift from a user.' ),
					'shortcodes' => array( 'date', 'gift_sender', 'gift_message', 'gift_code', 'gift_redemption_url', 'site_title', 'site_url', 'gift_details' ),
					'default_title' => self::__( 'Redeem your gift at ' . get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/gift-redemption', NULL ),
					'allow_preference' => FALSE // user_id not passed in $data array, plus they should get these no matter what.
				),
				'applied_credits' => array(
					'name' => self::__( 'Credits Rewarded Notification' ),
					'description' => self::__( 'Customize the notification email that is sent a a user when they receive a credit.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'site_title', 'site_url', 'reward' ),
					'default_title' => self::__( 'A Reward from ' . get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/applied-credits', NULL )
				),
				'voucher_notification' => array(
					'name' => self::__( 'Voucher Activated Notification' ),
					'description' => self::__( 'Customize the notification email that is sent a a user when a voucher of theirs is created.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'site_title', 'site_url', 'deal_url', 'deal_title', 'voucher_url', 'voucher_logo', 'voucher_serial', 'voucher_expiration', 'voucher_how_to', 'voucher_locations', 'voucher_fine_print', 'voucher_security' ),
					'default_title' => self::__( 'Your deal is ready at ' . get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/voucher-created', NULL )
				),
				'voucher_exp_notification' => array(
					'name' => self::__( 'Voucher Expiration Notification' ),
					'description' => self::__( 'Customize the notification email that is sent a a user when a voucher of theirs is about to expire.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'site_title', 'site_url', 'deal_url', 'deal_title', 'voucher_url', 'voucher_logo', 'voucher_serial', 'voucher_expiration', 'voucher_how_to', 'voucher_locations', 'voucher_fine_print', 'voucher_security' ),
					'default_title' => self::__( 'Your voucher is about to expire at ' . get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/voucher-exp-soon', NULL ),
					'default_disabled' => TRUE
				),
			);
			self::$notification_types = apply_filters( 'gb_notification_types', $default_notification_types );
		}
		if ( !isset( self::$shortcodes ) ) {
			// Notification shortcodes include the code, a description, and a callback
			// Most shortcodes should be defined by a different controller using the 'gb_notification_shortcodes' filter
			$default_shortcodes = array(
				'date' => array(
					'description' => self::__( 'Used to display the date.' ),
					'callback' => array( get_class(), 'shortcode_date' )
				),
				'name' => array(
					'description' => self::__( 'Used to display the user&rsquo;s name.' ),
					'callback' => array( get_class(), 'shortcode_sender_name' )
				),
				'username' => array(
					'description' => self::__( 'Used to display the user&rsquo;s name (registration & password notifications.' ),
					'callback' => array( get_class(), 'shortcode_username' )
				),
				'rewards_used' => array(
					'description' => self::__( 'Used to display the rewards used.' ),
					'callback' => array( get_class(), 'shortcode_rewards_used' )
				),
				'credits_used' => array(
					'description' => self::__( 'Used to display the credits used.' ),
					'callback' => array( get_class(), 'shortcode_credits_used' )
				),
				'total_paid' => array(
					'description' => self::__( 'Used to display the total paid/charge.' ),
					'callback' => array( get_class(), 'shortcode_total_paid' )
				),
				'purchase_details' => array(
					'description' => self::__( 'Used to display purchase details: title, price, shipping & url.' ),
					'callback' => array( get_class(), 'shortcode_purchase_details' )
				),
				'deal_url' => array(
					'description' => self::__( 'Used to display the deal url.' ),
					'callback' => array( get_class(), 'shortcode_deal_url' )
				),
				'deal_title' => array(
					'description' => self::__( 'Used to display name of the deal.' ),
					'callback' => array( get_class(), 'shortcode_deal_title' )
				),
				'transid' => array(
					'description' => self::__( 'Used to display the transaction id.' ),
					'callback' => array( get_class(), 'shortcode_transid' )
				),
				'site_title' => array(
					'description' => self::__( 'Used to display the site name.' ),
					'callback' => array( get_class(), 'shortcode_site_title' )
				),
				'site_url' => array(
					'description' => self::__( 'Used to display the site url.' ),
					'callback' => array( get_class(), 'shortcode_site_url' )
				),
				'total' => array(
					'description' => self::__( 'Used to display purchase total.' ),
					'callback' => array( get_class(), 'shortcode_total' )
				),
				'confirmation_url' => array(
					'description' => self::__( 'Used to display the password reset confirmation url.' ),
					'callback' => array( get_class(), 'shortcode_confirmation_url' )
				),
				'temp_password' => array(
					'description' => self::__( 'Used to display the temporary password.' ),
					'callback' => array( get_class(), 'shortcode_temp_password' )
				),
				'gift_sender' => array(
					'description' => self::__( 'Used to display the gifter&rsquo;s name.' ),
					'callback' => array( get_class(), 'shortcode_gift_sender_name' )
				),
				'gift_message' => array(
					'description' => self::__( 'Used to display the message from the gifter.' ),
					'callback' => array( get_class(), 'shortcode_gift_sender_message' )
				),
				'gift_code' => array(
					'description' => self::__( 'Used to display the gift code. <strong>This is required!</strong>' ),
					'callback' => array( get_class(), 'shortcode_gift_code' )
				),
				'gift_redemption_url' => array(
					'description' => self::__( 'Used to display the redemption url. <strong>This is required!</strong>' ),
					'callback' => array( get_class(), 'shortcode_gift_redemption_url' )
				),
				'reward' => array(
					'description' => self::__( 'Used to display the credits rewarded.' ),
					'callback' => array( get_class(), 'shortcode_applied_credits' )
				),
				'shipping_address' => array(
					'description' => self::__( 'Used to display the shipping address of the purchaser.' ),
					'callback' => array( get_class(), 'shortcode_shipping_address' )
				),
				'billing_address' => array(
					'description' => self::__( 'Used to display the billing address of the purchaser.' ),
					'callback' => array( get_class(), 'shortcode_billing_address' )
				),
				'gift_details' => array(
					'description' => self::__( 'Used to display the purchase details for a gift.' ),
					'callback' => array( get_class(), 'shortcode_gift_details' )
				),
				'voucher_url' => array(
					'description' => self::__( 'Used to display the voucher&rsquo;s url.' ),
					'callback' => array( get_class(), 'shortcode_voucher_url' )
				),
				'voucher_serial' => array(
					'description' => self::__( 'Used to display the voucher&rsquo;s serial code.' ),
					'callback' => array( get_class(), 'shortcode_voucher_serial' )
				),
				'voucher_expiration' => array(
					'description' => self::__( 'Used to display the voucher&rsquo;s expiration.' ),
					'callback' => array( get_class(), 'shortcode_voucher_exp' )
				),
				'voucher_how_to' => array(
					'description' => self::__( 'Used to display the voucher&rsquo;s how to.' ),
					'callback' => array( get_class(), 'shortcode_voucher_how_to' )
				),
				'voucher_locations' => array(
					'description' => self::__( 'Used to display the voucher&rsquo;s locations.' ),
					'callback' => array( get_class(), 'shortcode_voucher_locations' )
				),
				'voucher_fine_print' => array(
					'description' => self::__( 'Used to display the voucher&rsquo;s fine print.' ),
					'callback' => array( get_class(), 'shortcode_voucher_fine_print' )
				),
				'voucher_security' => array(
					'description' => self::__( 'Used to display the voucher&rsquo;s security code.' ),
					'callback' => array( get_class(), 'shortcode_voucher_security_code' )
				),
				'voucher_logo' => array(
					'description' => self::__( 'Used to display the voucher&rsquo;s logo.' ),
					'callback' => array( get_class(), 'shortcode_voucher_logo' )
				)
			);
			self::$shortcodes = apply_filters( 'gb_notification_shortcodes', $default_shortcodes );
		}
	}

	public static function add_meta_boxes() {
		self::init_types();
		foreach ( self::$notification_types as $type_id => $type ) {
			add_meta_box( self::META_BOX_PREFIX . $type_id, sprintf( self::__( '%s Shortcodes' ), $type['name'] ), array( get_class(), 'show_meta_box' ), Group_Buying_Notification::POST_TYPE, 'advanced', 'high' );
		}
		add_meta_box( 'gb_notification_type', self::__( 'Notification Type' ), array( get_class(), 'show_meta_box' ), Group_Buying_Notification::POST_TYPE, 'side', 'low' );
	}

	public static function queue_notification_js() {
		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : -1;
		if ( ( isset( $_GET['post_type'] ) && Group_Buying_Notification::POST_TYPE == $_GET['post_type'] ) || Group_Buying_Notification::POST_TYPE == get_post_type( $post_id ) ) {
			wp_enqueue_script( 'group-buying-admin-notification', GB_URL . '/resources/js/notification.admin.gbs.js', array( 'jquery' ), Group_Buying::GB_VERSION );
		}
	}

	public static function show_meta_box( $post, $metabox ) {
		$notification = Group_Buying_Notification::get_instance( $post->ID );
		$id = preg_replace( '/^' . preg_quote( self::META_BOX_PREFIX ) . '/', '', $metabox['id'] );
		if ( isset( self::$notification_types[$id] ) ) {
			self::load_view( 'meta_boxes/notification-shortcodes', array(
					'id' => $id,
					'type' => self::$notification_types[$id],
					'shortcodes' => self::$shortcodes
				) );
		} else {
			if ( 'gb_notification_type' == $metabox['id'] ) {
				self::load_view( 'meta_boxes/notification-type', array(
						'notification_id' => $post->ID,
						'notification_types' => self::$notification_types,
						'notifications' => get_option( self::NOTIFICATIONS_OPTION_NAME, array() ),
						'disabled' => $notification->get_disabled()
					), FALSE );
			} else {
				self::unknown_meta_box( $metabox['id'] );
			}
		}
	}

	public static function save_meta_boxes( $post_id, $post ) {
		// only continue if it's a notification post
		if ( $post->post_type != Group_Buying_Notification::POST_TYPE ) {
			return;
		}
		// don't do anything on autosave, auto-draft, bulk edit, or quick edit
		if ( wp_is_post_autosave( $post_id ) || $post->post_status == 'auto-draft' || defined( 'DOING_AJAX' ) || isset( $_GET['bulk_edit'] ) ) {
			return;
		}
		self::init_types();
		// save all the meta boxes
		$notification = Group_Buying_Notification::get_instance( $post_id );
		self::save_meta_box_gb_notification_type( $notification, $post_id, $_POST['notification_type'] );
	}

	public static function save_meta_box_gb_notification_type( $notification, $post_id, $notification_type = NULL ) {
		if ( NULL === $notification_type ) {
			$notification_type = $_POST['notification_type'];
		}

		$notifications = get_option( self::NOTIFICATIONS_OPTION_NAME, array() );

		// Remove any existing notification types that point to the post currently being saved
		$notifications = array_flip( $notifications );
		unset( $notifications[$post_id] );
		$notifications = array_flip( $notifications );

		if ( isset( self::$notification_types[$notification_type] ) ) {

			// Associate this post with the given notification type
			$notifications[$notification_type] = $post_id;
			update_option( self::NOTIFICATIONS_OPTION_NAME, $notifications );
		}

		if ( isset( $_POST['notification_type_disabled'] ) && $_POST['notification_type_disabled'] == 'TRUE' ) {
			$notification->set_disabled( 'TRUE' );
		} else {
			$notification->set_disabled( 0 );
		}
	}

	public static function register_columns( $columns ) {
		unset( $columns['date'] );
		unset( $columns['title'] );
		$columns['type'] = self::__( 'Notification Type' );
		$columns['subject'] = self::__( 'Subject' );
		$columns['message'] = self::__( 'Message' );
		return $columns;
	}


	public static function column_display( $column_name, $id ) {
		self::init_types();

		switch ( $column_name ) {
		case 'subject':
			echo '<a href="'.get_edit_post_link( $id ).'">'.get_the_title( $id ).'</a>';
			break;
		case 'type':
			$key = array_search( $id, get_option( self::NOTIFICATIONS_OPTION_NAME, array() ) );
			$name = Group_Buying_Notifications::$notification_types[$key]['name'];
			echo '<a href="'.get_edit_post_link( $id ).'">'.esc_html( $name ).'</a>';
			break;
		case 'message':
			echo get_the_excerpt( $id );
			break;

		default:
			break;
		}
	}

	public static function get_notification( $notification_type ) {
		self::init_types();
		if ( isset( self::$notification_types[$notification_type] ) ) {
			$notifications = get_option( self::NOTIFICATIONS_OPTION_NAME );
			if ( isset( $notifications[$notification_type] ) ) {
				$notification_id = $notifications[$notification_type];
				$notification = Group_Buying_Notification::get_instance( $notification_id );
				if ( $notification != null ) {
					$post = $notification->get_post();

					// Don't return the notification if isn't published (excludes deleted, draft, and future posts)
					if ( 'publish' == $post->post_status ) {
						return $notification;
					}
				}
			}
		}
		return null;
	}

	public static function is_disabled( $notification_name ) {
		$notification = self::get_notification( $notification_name );
		if ( is_a( $notification, 'Group_Buying_Notification' ) ) {
			return $notification->is_disabled();
		}
		return;
	}

	public static function get_notification_title( $notification_name, $data = null ) {
		self::$data = $data;
		$notification = self::get_notification( $notification_name );
		if ( !is_null( $notification ) ) {
			$notification_post = $notification->get_post();
			$title = $notification_post->post_title;
			$title = self::do_shortcodes( $notification_name, $title );
			return apply_filters( 'gb_get_notification_title', $title, $notification_name, $data );
		} elseif ( isset( self::$notification_types[$notification_name] ) && isset( self::$notification_types[$notification_name]['default_title'] ) ) {
			$title = self::$notification_types[$notification_name]['default_title'];
			$title = self::do_shortcodes( $notification_name, $title );
			return apply_filters( 'gb_get_notification_title', $title, $notification_name, $data );
		}

		return apply_filters( 'gb_get_notification_title', '', $notification_name, $data );
	}

	public static function get_notification_content( $notification_name, $data = null ) {
		self::$data = $data;
		$notification = self::get_notification( $notification_name );
		if ( !is_null( $notification ) ) {
			$notification_post = $notification->get_post();
			$content = $notification_post->post_content;
			$content = self::do_shortcodes( $notification_name, $content );
			return apply_filters( 'gb_get_notification_content', $content, $notification_name, $data );
		} elseif ( isset( self::$notification_types[$notification_name] ) && isset( self::$notification_types[$notification_name]['default_content'] ) ) {
			$content = self::$notification_types[$notification_name]['default_content'];
			$content = self::do_shortcodes( $notification_name, $content );
			return apply_filters( 'gb_get_notification_content', $content, $notification_name, $data );
		}
		return apply_filters( 'gb_get_notification_content', '', $notification_name, $data );
	}

	public static function send_notification( $notification_name, $data = array(), $to, $from_email = null, $from_name = null, $html = null ) {
		// The options registered in the notification type array
		$registered_notification = self::$notification_types[$notification_type];

		// don't send disabled notifications
		if ( self::is_disabled( $notification_name ) ) {
			return;
		}

		// Check to see if this notification can be disabled first
		if ( isset( $registered_notification['allow_preference'] ) && $registered_notification['allow_preference'] ) {
			// Check to see if the user has disabled this notification
			if ( isset( $data['user_id'] ) ) {
				$account = Group_Buying_Account::get_instance( $data['user_id'] );
				if ( is_a( $account, 'Group_Buying_Account' ) && self::user_disabled_notification( $notification_name, $account ) ) {
					return;
				}
			}
		}

		// So shortcode handlers know whether the email is being sent as html or plaintext
		if ( null == $html ) {
			$html = ( self::$notification_format == 'HTML' ) ? TRUE : FALSE ;
		}
		$data['html'] = $html;

		// don't send a notification that has already been sent
		if ( self::was_notification_sent( $notification_name, $data, $to ) ) {
			if ( self::DEBUG ) error_log( "Message Already Sent: " . print_r( $data, true ) );
			return;
		}

		$notification_title = self::get_notification_title( $notification_name, $data );
		$notification_content = self::get_notification_content( $notification_name, $data );

		// Don't send notifications with empty titles or content
		if ( empty( $notification_title ) || empty( $notification_content ) ) {
			return;
		}

		// Plugin addons can suppress specific notifications by filtering 'gb_suppress_notification'
		$suppress_notification = apply_filters( 'gb_suppress_notification', FALSE, $notification_name, $data, $from_email, $from_name, $html );
		if ( $suppress_notification ) {
			return;
		}

		$from_email = ( null == $from_email ) ? self::$notification_from_email : $from_email ;
		$from_name = ( null == $from_name ) ? self::$notification_from_name : $from_name ;

		if ( $html ) {
			$headers = array(
				"From: ".$from_name." <".$from_email.">",
				"Content-Type: text/html"
			);
		} else {
			$headers = array(
				"From: ".$from_name." <".$from_email.">",
			);
		}
		$headers = implode( "\r\n", $headers ) . "\r\n";
		$filtered_headers = apply_filters( 'gb_notification_headers', $headers, $notification_name, $data, $from_email, $from_name, $html );

		if ( self::DEBUG ) error_log( "notification content: " . print_r( $notification_content, true ) );
		wp_mail( $to, $notification_title, $notification_content, $filtered_headers );
		self::mark_notification_sent( $notification_name, $data, $to );
	}

	/**
	 * Log that a notification was sent, so we don't send it again
	 *
	 * @static
	 * @param string  $notification_name
	 * @param array   $data
	 * @param string  $to
	 * @return void
	 */
	public static function mark_notification_sent( $notification_name, $data, $to ) {
		global $blog_id;
		$user_id = self::get_notification_user_id( $to, $data );
		if ( !$user_id ) {
			return; // don't know who it is, so we can't log it
		}
		add_user_meta( $user_id, $blog_id.'_gbs_notification-'.$notification_name, self::get_hash( $data ) );
	}

	/**
	 *
	 *
	 * @static
	 * @param string  $notification_name
	 * @param array   $data
	 * @param string  $to
	 * @return bool Whether this notification has been sent previously
	 */
	public static function was_notification_sent( $notification_name, $data, $to ) {
		global $blog_id;
		$user_id = self::get_notification_user_id( $to, $data );
		if ( !$user_id ) {
			return FALSE;
		}

		$meta = get_user_meta( $user_id, $blog_id.'_gbs_notification-'.$notification_name, FALSE );
		if ( in_array( self::get_hash( $data ), $meta ) ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Convert the data array into a hash
	 *
	 * @static
	 * @param array   $data
	 * @return string
	 */
	private static function get_hash( $data ) {
		foreach ( $data as $key => $value ) {
			// many objects can't be serialized, so convert them to something else
			if ( is_object( $value ) && method_exists( $value, 'get_id' ) ) {
				$data[$key] = array( 'class' => get_class( $value ), 'id' => $value->get_id() );
			}
		}
		return md5( serialize( $data ) );
	}

	/**
	 * Utility function to get the user ID that the given information would be sent to.
	 *
	 * @static
	 * @param string  $to   The user's email address
	 * @param array   $data
	 * @return int
	 */
	private static function get_notification_user_id( $to = '', $data = array() ) {
		$user_id = 0;
		// first, see if it's stored in the data
		if ( isset( $data['user_id'] ) ) {
			$user_id = $data['user_id'];
		} elseif ( isset( $data['user'] ) ) {
			if ( is_numeric( $data['user'] ) ) {
				$user_id = $data['user'];
			} elseif ( is_object( $data['user'] ) && isset( $data['user']->ID ) ) {
				$user_id = $data['user']->ID;
			}
		}
		if ( isset( $data['user'] ) && is_a( $data['user'], 'WP_User' ) ) {
			return $data['user']->ID;
		}
		// then try to determine based on email address
		if ( !$user_id ) {
			$email = ( isset( $data['user_email'] ) && $data['user_email'] != '' ) ? $data['user_email'] : $to ;
			$user = get_user_by( 'email', $to );
			if ( $user && isset( $user->ID ) ) {
				$user_id = $user->ID;
			}
		}

		return $user_id;
	}

	public static function do_shortcodes( $notification_name, $content ) {
		foreach ( self::$notification_types[$notification_name]['shortcodes'] as $shortcode ) {
			add_shortcode( $shortcode, array( get_class(), 'notification_shortcode' ) );
		}
		$content = do_shortcode( $content );
		foreach ( self::$notification_types[$notification_name]['shortcodes'] as $shortcode ) {
			remove_shortcode( $shortcode );
		}
		return $content;
	}

	public static function notification_shortcode( $atts, $content, $code ) {
		if ( isset( self::$shortcodes[$code] ) ) {
			$shortcode = call_user_func( self::$shortcodes[$code]['callback'], $atts, $content, $code, self::$data );
			return apply_filters( 'gb_notification_shortcode_'.$code, $shortcode, $atts, $content, $code, self::$data );

		}
		return '';
	}

	public static function shortcode_date( $atts, $content, $code, $data ) {
		// Currently undocumented, but a "format" attribute can be used to customize the date format
		$atts = shortcode_atts( array( 'format' => get_option( 'date_format' ) ), $atts );
		return date( $atts['format'], current_time( 'timestamp', 1 ) );
	}

	public static function shortcode_username( $atts, $content, $code, $data ) {
		$user_id = self::get_notification_user_id( 0, $data );
		if ( is_numeric( $user_id ) ) {
			$user = get_userdata( $user_id );
			return $user->user_login;
		}
		return self::__( 'Customer' );
	}

	public static function shortcode_rewards_used( $atts, $content, $code, $data  ) {
		$purchase = $data['purchase'];
		$credits_used = $purchase->get_total( Group_Buying_Affiliate_Credit_Payments::PAYMENT_METHOD );
		return $credits_used;
	}

	public static function shortcode_credits_used( $atts, $content, $code, $data  ) {
		$purchase = $data['purchase'];
		$credits_used = $purchase->get_total( Group_Buying_Account_Balance_Payments::PAYMENT_METHOD );
		return $credits_used;
	}

	public static function shortcode_total_paid( $atts, $content, $code, $data ) {
		$purchase = $data['purchase'];
		return gb_get_formatted_money( $purchase->get_total() );
	}

	public static function shortcode_purchase_details( $atts, $content, $code, $data ) {
		//title, price, shipping & url.
		$purchase = $data['purchase'];
		$products = $purchase->get_products();
		$tax_total = gb_get_formatted_money( $purchase->get_tax_total() );
		$shipping_total = gb_get_formatted_money( $purchase->get_shipping_total() );
		$total = gb_get_formatted_money( $purchase->get_total() );
		$subtotal = gb_get_formatted_money( $purchase->get_subtotal() );
		$output = '';
		if ( isset( $data['html'] ) && $data['html'] ) {
			$output .= '<table width="500px"><thead><tr>';
			$output .= '<th scope="col" colspan="2" align="left">'.self::__( 'Your Order Summary' ).'</th>';
			$output .= '<th scope="col" colspan="1" align="center">'.self::__( 'Quantity' ).'</th>';
			$output .= '<th scope="col" colspan="1" align="center">'.self::__( 'Price' ).'</th>';
			$output .= '</tr></thead>';
			$output .= '<tfoot>';
			$output .= '<tr><th scope="row" colspan="3" align="right">'.self::__( 'Subtotal' ).'</th><td align="center">'.$subtotal.'</td></tr>';
			$output .= '<tr><th scope="row" colspan="3" align="right">'.self::__( 'Shipping' ).'</th><td align="center">'.$shipping_total.'</td></tr>';
			$output .= '<tr><th scope="row" colspan="3" align="right">'.self::__( 'Tax' ).'</th><td align="center">'.$tax_total.'</td></tr>';
			$output .= '<tr><th scope="row" colspan="3" align="right">'.self::__( 'Total' ).'</th><td align="center">'.$total.'</td></tr>';
			$output .= '</tfoot>';
			$output .= '<tbody>';
		}
		foreach ( $products as $product ) {
			$deal_id = (int) $product['deal_id'];
			$deal = Group_Buying_Deal::get_instance( $deal_id );
			$deal_title = $deal->get_title();
			$price = gb_get_formatted_money( $product['price'] );
			$quantity = $product['quantity'];
			$shipping = $deal->get_shipping();
			$url = get_permalink( $deal_id );

			if ( isset( $data['html'] ) && $data['html'] ) {
				$output .= '<tr><td colspan="2" align="right"><a href="'.$url.'">'.$deal_title.'</a></td><td align="center">'.$quantity.'</td><td align="center">'.$price.'</td></tr>';
			} else {
				$output .= self::__( 'Deal' ) . ": $deal_title\n";
				$output .= self::__( 'Quantity' ) . ": $quantity\n";
				$output .= self::__( 'Price' ) . ": $price\n";
				$output .= self::__( 'Shipping' ) . ": $shipping\n";
				$output .= self::__( 'URL' ) . ": $url\n\n";
			}
		}
		if ( isset( $data['html'] ) && $data['html'] ) {
			$output .= '</tbody></table>';
		} else {
			$output .= self::__( 'Shipping Total' ) . ": $shipping_total\n";
			$output .= self::__( 'Tax Total' ) . ": $tax_total\n";
			$output .= self::__( 'Total' ) . ": $total\n";
		}
		return apply_filters( 'gb_shortcode_purchase_details', $output, $purchase, $products, $atts, $content, $code, $data );
	}

	public static function shortcode_gift_details( $atts, $content, $code, $data ) {
		$gift = $data['gift'];
		$purchase = $gift->get_purchase();
		$products = $purchase->get_products();
		$output = '';
		if ( isset( $data['html'] ) && $data['html'] ) {
			$output .= '<table width="500px"><tr><th>' . self::__( 'Quantity' ) . '</th><th>' . self::__( 'Deal' ) . '</th><th>' . self::__( 'URL' ) . '</th></tr>';
		}
		foreach ( $products as $product ) {
			$deal_id = (int) $product['deal_id'];
			$deal = Group_Buying_Deal::get_instance( $deal_id );
			$deal_title = $deal->get_title();
			$quantity = $product['quantity'];
			$url = get_permalink( $deal_id );

			if ( isset( $data['html'] ) && $data['html'] ) {
				$output .= "<tr><td>$quantity</td><td>$deal_title</td><td><a href=\"$url\">$url</a></td></tr>";
			} else {
				$output .= self::__( 'Quantity' ) . ": $quantity\n";
				$output .= self::__( 'Deal' ) . ": $deal_title\n";
				$output .= self::__( 'URL' ) . ": $url\n\n";
			}
		}
		if ( isset( $data['html'] ) && $data['html'] ) {
			$output .= '</table>';
		}
		return apply_filters( 'gb_shortcode_gift_details', $output, $purchase, $products );
	}

	public static function shortcode_deal_url( $atts, $content, $code, $data ) {
		if ( isset( $data['deal'] ) ) {
			$deal = $data['deal'];
			return get_permalink( $deal->get_ID() );
		}
		return '';
	}

	public static function shortcode_deal_title( $atts, $content, $code, $data ) {
		if ( isset( $data['deal'] ) ) {
			$deal = $data['deal'];
			return get_the_title( $deal->get_ID() );
		}
		return '';
	}

	public static function shortcode_voucher_url( $atts, $content, $code, $data ) {
		if ( isset( $data['voucher'] ) ) {
			$voucher = $data['voucher'];
			return get_permalink( $voucher->get_ID() );
		}
		return '';
	}

	public static function shortcode_voucher_serial( $atts, $content, $code, $data ) {
		if ( isset( $data['voucher'] ) ) {
			$voucher = $data['voucher'];
			return $voucher->get_serial_number();
		}
		return '';
	}

	public static function shortcode_voucher_exp( $atts, $content, $code, $data ) {
		if ( isset( $data['voucher'] ) ) {
			$voucher = $data['voucher'];
			$date = $voucher->get_expiration_date();
			$formated_date = ( $date != '' ) ? date( 'm/d/Y', $date ) : '';
			return $formated_date;
		}
		return '';
	}

	public static function shortcode_voucher_how_to( $atts, $content, $code, $data ) {
		if ( isset( $data['voucher'] ) ) {
			$voucher = $data['voucher'];
			$how_to = $voucher->get_usage_instructions();
			if ( !empty( $how_to ) ) {
				return $how_to;
			}
		}
		return '';
	}

	public static function shortcode_voucher_locations( $atts, $content, $code, $data ) {
		if ( isset( $data['voucher'] ) ) {
			$voucher = $data['voucher'];
			$locations = $voucher->get_locations();
			if ( !empty( $locations ) ) {
				$out = '';
				if ( isset( $data['html'] ) && $data['html'] ) {
					$out .= '<ul class="voucher_locations"><li>';
					$out .= implode( '</li><li>', $locations );
					$out .= '</li></ul>';
				} else {
					$out .= implode( ', ', $locations );
				}
			}
			return $out;
		}
		return '';
	}

	public static function shortcode_voucher_fine_print( $atts, $content, $code, $data ) {
		if ( isset( $data['voucher'] ) ) {
			$voucher = $data['voucher'];
			$out = $voucher->get_fine_print();
			if ( !empty( $out ) ) {
				return $out;
			}
		}
		return '';
	}

	public static function shortcode_voucher_security_code( $atts, $content, $code, $data ) {
		if ( isset( $data['voucher'] ) ) {
			$voucher = $data['voucher'];
			return $voucher->get_security_code();
		}
		return '';
	}

	public static function shortcode_voucher_logo( $atts, $content, $code, $data ) {
		if ( isset( $data['voucher'] ) ) {
			$voucher = $data['voucher'];
			$out = $voucher->get_logo();
			if ( !empty( $out ) ) {
				return $out;
			}
		}
		return '';
	}

	public static function shortcode_voucher_map( $atts, $content, $code, $data ) {
		if ( isset( $data['voucher'] ) ) {
			$voucher = $data['voucher'];
			$out = $voucher->get_voucher_map();
			if ( !empty( $out ) ) {
				return $out;
			}
		}
		return '';
	}

	public static function shortcode_transid( $atts, $content, $code, $data ) {
		$purchase = $data['purchase'];
		$id = $purchase->get_id();
		return $id;
	}

	public static function shortcode_site_title( $atts, $content, $code, $data ) {
		return get_bloginfo( 'name' );
	}

	public static function shortcode_site_url( $atts, $content, $code, $data ) {
		return home_url();
	}

	public static function shortcode_total( $atts, $content, $code, $data ) {
		if ( isset( $data['purchase'] ) ) {
			$purchase = $data['purchase'];
			$purchase_total = $purchase->get_total();
			return gb_get_formatted_money( $purchase_total );
		}
		return '';
	}

	public static function shortcode_confirmation_url( $atts, $content, $code, $data ) {
		$url = add_query_arg( 'key', $data['key'], Group_Buying_Accounts_Retrieve_Password::get_url() );
		return $url;
	}

	public static function shortcode_temp_password( $atts, $content, $code, $data ) {
		$new_pass = $data['new_pass'];
		return $new_pass;
	}

	public static function shortcode_applied_credits( $atts, $content, $code, $data ) {
		$credits = $data['applied_credits'];
		return $credits;
	}

	public static function shortcode_sender_name( $atts, $content, $code, $data ) {
		if ( self::DEBUG ) {
			error_log( "shortcode_sender_name: " . print_r( $data['user_id'], true ) );
		}
		$user_id = self::get_notification_user_id( 0, $data );
		if ( is_int( $user_id ) && $user_id > 0 ) {
			$account = Group_Buying_Account::get_instance( $user_id );
			if ( is_a( $account, 'Group_Buying_Account' ) ) {
				$get_name = $account->get_name();
				if ( $get_name != '' ) {
					return $get_name;
				}
			}

			// Fallback to Username
			$user = get_userdata( $user_id );
			return $user->user_login;

		}

	}


	public static function shortcode_billing_address( $atts, $content, $code, $data ) {
		$account = Group_Buying_Account::get_instance( $data['user_id'] );
		if ( !is_a( $account, 'Group_Buying_Account' ) ) {
			return '';
		}
		$get_address = $account->get_address();
		$seperator = ( isset( $data['html'] ) && $data['html'] ) ? "<br/>" : "\n" ;
		$address = ( empty( $get_address ) || $get_address == '' ) ? self::__( 'N/A' ) : gb_format_address( $get_address, 'string', $seperator );
		return $address;
	}

	public static function shortcode_shipping_address( $atts, $content, $code, $data ) {
		$purchase = $data['purchase'];
		$payments = Group_Buying_Payment::get_payments_for_purchase( $purchase->get_id() );
		$shipping = self::__( 'N/A' );
		foreach ( $payments as $payment_id ) {
			if ( !$set ) {
				$payment = Group_Buying_Payment::get_instance( $payment_id );
				$get_shipping = $payment->get_shipping_address();
				if ( is_array( $get_shipping ) && !empty( $get_shipping ) ) {
					$seperator = ( isset( $data['html'] ) && $data['html'] ) ? "<br/>" : "\n" ;
					$shipping = gb_format_address( $get_shipping, 'string', $seperator );
					$set = true;
				}
			}
		}
		return $shipping;
	}

	public static function shortcode_sender_message( $atts, $content, $code, $data ) {
		$message = $data['message'];
		return $message;
	}

	public static function shortcode_gift_sender_name( $atts, $content, $code, $data ) {
		if ( isset( $data['gift'] ) ) {
			$gift = $data['gift'];
			$purchase = $gift->get_purchase();
			$sender_id = $purchase->get_original_user();
			$account = Group_Buying_Account::get_instance( $sender_id );
			$sender_name = $account->get_name();
			return $sender_name;
		}
		return '';
	}

	public static function shortcode_gift_sender_message( $atts, $content, $code, $data ) {
		if ( isset( $data['gift'] ) ) {
			$gift = $data['gift'];
			$message = $gift->get_message();
			return $message;
		}
		return '';
	}

	public static function shortcode_gift_code( $atts, $content, $code, $data ) {
		if ( isset( $data['gift'] ) ) {
			$gift = $data['gift'];
			$code = $gift->get_coupon_code();
			return $code;
		}
		return '';
	}

	public static function shortcode_gift_redemption_url( $atts, $content, $code, $data ) {
		$url = Group_Buying_Gifts::get_url();
		return $url;
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_notification_settings';
		add_settings_section( $section, self::__( 'Notification Settings' ), array( get_class(), 'display_settings_section' ), $page );
		// Settings
		register_setting( $page, self::EMAIL_FROM_NAME );
		register_setting( $page, self::EMAIL_FROM_EMAIL );
		register_setting( $page, self::EMAIL_FORMAT );
		// Fields
		add_settings_field( self::EMAIL_FROM_NAME, self::__( 'Default From Name' ), array( get_class(), 'display_notification_from_name' ), $page, $section );
		add_settings_field( self::EMAIL_FROM_EMAIL, self::__( 'Default From Email' ), array( get_class(), 'display_notification_from_email' ), $page, $section );
		add_settings_field( self::EMAIL_FORMAT, self::__( 'Send e-mail as' ), array( get_class(), 'display_notification_format' ), $page, $section );
		add_settings_field( 'notification_disable', self::__( 'Disable Notifications' ), array( get_class(), 'display_notification_disable' ), $page, $section );
	}

	public static function display_notification_from_name() {
		echo '<input type="text" name="'.self::EMAIL_FROM_NAME.'" value="'.self::$notification_from_name.'" size="40" />';
	}

	public static function display_notification_from_email() {
		echo '<input type="text" name="'.self::EMAIL_FROM_EMAIL.'" value="'.self::$notification_from_email.'" size="40" />';
	}

	public static function display_notification_format() {
		echo '<select name="'.self::EMAIL_FORMAT.'"><option value="HTML" '.selected( 'HTML', self::$notification_format, FALSE ).'>HTML</option><option value="TEXT" '.selected( 'TEXT', self::$notification_format, FALSE ).'>Plain Text</option></select><br/><span class="description">'.self::__( 'Default notifications are plain text. If setting to HTML you will need create custom HTML notifications.' ).'</span>';
	}

	public static function display_notification_disable() {
		printf( self::__( 'Disable <a href="%s">notifications</a> with the disable option on the notification edit page.' ), gb_admin_url( 'notifications' ), GB_RESOURCES.'img/docs/disable-notification.png' );
	}

	public static function get_user_email( $user = false ) {
		if ( false == $user ) {
			$user = get_current_user_id();
		}
		if ( is_int( $user ) ) {
			$user = get_userdata( $user );
		}
		$user_email = $user->user_email;
		$name = gb_get_name( $user->ID );

		if ( empty( $name ) ) {
			$to = $user_email;
		} else {
			$to = "$name <$user_email>";
		}
		return $to;
	}

	function purchase_notification( $purchase ) {
		$user_id = $purchase->get_user();
		if ( $user_id == -1 ) { // purchase will be set to -1 if it's a gift.
			$user_id = $purchase->get_original_user();
		}
		$to = self::get_user_email( $user_id );
		$data = array(
			'user_id' => $user_id,
			'purchase' => $purchase
		);
		self::send_notification( 'purchase', $data, $to );
	}

	function deal_closed_notification( $deal ) {
		$purchase_ids = Group_Buying_Purchase::get_purchases( array( 'deal' => $deal->get_id() ) );
		foreach ( $purchase_ids as $purchase_id ) {
			$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
			$user_id = $purchase->get_user();
			$user = get_userdata( $user_id );
			$to = self::get_user_email( $user );
			$data = array(
				'user_id' => $user_id,
				'deal' => $deal
			);
			self::send_notification( 'deal_closed', $data, $to );
		}
	}

	function deal_failed_notification( $deal ) {
		$purchase_ids = Group_Buying_Purchase::get_purchases( array( 'deal' => $deal->get_id() ) );
		foreach ( $purchase_ids as $purchase_id ) {
			$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
			$user_id = $purchase->get_user();
			$user = get_userdata( $user_id );
			$to = self::get_user_email( $user );
			$data = array(
				'user_id' => $user_id,
				'deal' => $deal
			);
			self::send_notification( 'deal_failed', $data, $to );
		}
	}

	function registration_notification( $user, $sanitized_user_login, $user_email, $password, $password ) {
		$data = array(
			'user' => $user,
			'user_login' => $sanitized_user_login,
			'user_email' => $user_email,
			'password' => $password
		);
		$to = self::get_user_email( $user );
		self::send_notification( 'registration', $data, $to );
	}

	function retrieve_password_notification( $data ) {
		$user = $data['user'];
		$to = self::get_user_email( $user );
		self::send_notification( 'password_reset', $data, $to );
	}

	function password_reset_notification( $data ) {
		$user = $data['user'];
		$to = self::get_user_email( $user );
		self::send_notification( 'temporary_password', $data, $to );
	}

	function admin_notification( $info, $data = array() ) { // TODO 3.x Build this out so it can be a true notification.
		$to = get_option( 'admin_email' );
		$from = get_option( 'blogname' );
		$headers = array( "From: ".$from." <".$to.">" );
		$header = implode( "\r\n", $headers ) . "\r\n";
		wp_mail( $to, $info['subject'], $info['content'], $header );
	}

	function gift_notification( $data ) {
		$gift = $data['gift'];
		$recipient = $gift->get_recipient();
		self::send_notification( 'gift_notification', $data, $recipient );
	}

	function voucher_notification( $voucher ) {
		$purchase = $voucher->get_purchase();
		$deal = $voucher->get_deal();

		$user_id = $purchase->get_user();
		if ( $user_id !== -1 ) { // purchase will be set to -1 if it's a gift.
			$recipient = self::get_user_email( $user_id );

			$data = array(
				'user_id' => $user_id,
				'voucher' => $voucher,
				'purchase' => $purchase,
				'deal' => $deal
			);
			self::send_notification( 'voucher_notification', $data, $recipient );
		}
	}

	function voucher_exp_notification() {
		$exp_meta_key = '_voucher_exp_notice_flag';

		// Versions of WP prior to 3.5 didn't support NOT EXISTS
		if ( version_compare( get_bloginfo( 'version' ), '3.5', '>=' ) ) {
			// WP_Query exp soon vouchers
			$args = array(
				'post_type' => Group_Buying_Voucher::POST_TYPE,
				'post_status' => 'publish',
				'posts_per_page' => 100,
				'fields' => 'ids',
				'gb_bypass_filter' => TRUE,
				'meta_query' => array(
					array(
						'key' => $exp_meta_key,
						'compare' => 'NOT EXISTS'
					)
				)
			);
			$vouchers = new WP_Query( $args );
			$voucher_ids = $vouchers->posts;
		}
		else {
			global $wpdb;

			$query = "
				SELECT SQL_CALC_FOUND_ROWS $wpdb->posts.ID
				FROM $wpdb->posts
				LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '$exp_meta_key' )
				WHERE 1=1 
				AND $wpdb->posts.post_type = 'gb_voucher'
				AND ( $wpdb->posts.post_status = 'publish' )
				AND ( $wpdb->postmeta.post_id IS NULL ) 
				GROUP BY $wpdb->posts.ID 
				ORDER BY $wpdb->posts.post_date DESC
				LIMIT 0, 100
				";

			$voucher_ids = $wpdb->get_col( $query );

		}

		// If no vouchers are found
		if ( empty( $voucher_ids ) )
			return;

		foreach ( $voucher_ids as $voucher_id ) {
			$flag = FALSE;
			$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
			$deal = $voucher->get_deal();

			if ( is_a( $deal, 'Group_Buying_Deal' ) ) {
				$expiration = $voucher->get_expiration_date();
				$period = apply_filters( 'voucher_expiration_notification_period', 2*24*60*60 );
				$exp_window = current_time( 'timestamp' )-$period;
				
				// Has expiration, expiration date after window and expiration hasn't passed.
				if ( $expiration && $expiration > $exp_window && $expiration < current_time( 'timestamp' ) ) {
					$purchase = $voucher->get_purchase();

					$user_id = $purchase->get_user();
					if ( $user_id !== -1 ) { // purchase will be set to -1 if it's a gift.
						$recipient = self::get_user_email( $user_id );

						$data = array(
							'user_id' => $user_id,
							'voucher' => $voucher,
							'purchase' => $purchase,
							'deal' => $deal
						);
						self::send_notification( 'voucher_exp_notification', $data, $recipient );
					}
					$flag = TRUE;
				}
			} else { $flag = TRUE; } // Flag to prevent this voucher from being returned again.

			if ( $flag ) {
				// flag all vouchers at this point
				add_post_meta( $voucher_id, $exp_meta_key, time() );
			}
			
		}
	}

	function applied_credits( $account, $payment, $credits, $type ) {
		$user_id = $account->get_user_id();
		$to = self::get_user_email( $user_id );
		$data = array(
			'user_id' => $user_id,
			'applied_credits' => $credits,
			'payment' => $payment,
			'type' => $type
		);
		self::send_notification( 'applied_credits', $data, $to );
	}

	/**
	 * Add the default pane to the account edit form
	 *
	 * @param array   $panes
	 * @param Group_Buying_Account $account
	 * @return array
	 */
	public function get_edit_panes( array $panes, Group_Buying_Account $account ) {
		$options = get_post_meta( $account->get_ID(), self::NOTIFICATION_SUB_OPTION );
		$panes['notification_subscriptions'] = array(
			'weight' => 50,
			'body' => self::load_view_to_string( 'account/edit-account-notifications', array( 'fields' => self::account_notification_fields( $account ) ) ),
		);
		return $panes;
	}

	/**
	 * Fields used in the subscription section. 
	 * Filterable with gb_account_edit_account_notificaiton_fields
	 * 
	 * @param  object $account Group_Buying_Account
	 * @return array
	 */
	private function account_notification_fields( $account = NULL ) {
		self::init_types(); // init the types so they can be used
		if ( !$account ) {
			$account = Group_Buying_Account::get_instance();
		}
		$view = '';
		foreach ( get_option( self::NOTIFICATIONS_OPTION_NAME ) as $notification_type => $id ) {
		// Loop through the mapped notification types to post ids
			// Notification instance to check if disabled
			$notification = Group_Buying_Notification::get_instance( $id );
			// The options registered in the notification type array
			$registered_notification = self::$notification_types[$notification_type];
			// Only some notifications can be disabled by the user
			$preference_available = ( isset( $registered_notification['allow_preference'] ) && !$registered_notification['allow_preference']) ? FALSE : TRUE ;
			// If the preference is allowed, if not disabled and if the notification is still registered (e.g. disabled add-on )
			if ( $preference_available && !$notification->is_disabled() && !empty( $registered_notification ) ) {
				// build the view for each option
				$view .= '<span class="notification_preference_wrap clearfix"><label class="checkbox"><input type="checkbox" name="'.self::NOTIFICATION_SUB_OPTION.'[]" value="'.$notification_type.'" '.checked( self::user_disabled_notification( $notification_type, $account ), FALSE, FALSE ).' class="checkbox">'.$registered_notification['name'].'<br/><small>Subject: "'.get_the_title( $notification->get_ID() ).'"</small></label></span>';
			}			
		}

		$fields = array(
			'notifications' => array(
				'weight' => 20,
				'label' => self::__( 'Notifications' ),
				'type' => 'bypass',
				'required' => FALSE,
				'output' => $view
			)
		);
		$fields = apply_filters( 'gb_account_edit_account_notificaiton_fields', $fields, $account );
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
		$notifications = isset( $_POST[self::NOTIFICATION_SUB_OPTION] ) ? $_POST[self::NOTIFICATION_SUB_OPTION] : array('0');
		delete_post_meta( $account->get_ID(), '_'.self::NOTIFICATION_SUB_OPTION );
		add_post_meta( $account->get_ID(), '_'.self::NOTIFICATION_SUB_OPTION, $notifications );
	}

	public function user_disabled_notification( $notification_type, Group_Buying_Account $account ){
		$account_preferences = get_post_meta( $account->get_ID(), '_'.self::NOTIFICATION_SUB_OPTION, TRUE ); // user's preferences
		$user_disabled = ( in_array( $notification_type, (array)$account_preferences ) || empty( $account_preferences ) ) ? FALSE : TRUE ;
		return $user_disabled;
	}

	public static function display_table() {
		//Create an instance of our package class...
		$wp_list_table = new Group_Buying_Notifications_Table();
		//Fetch, prepare, sort, and filter our data...
		$wp_list_table->prepare_items();
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2 class="nav-tab-wrapper">
			<?php self::display_admin_tabs(); ?>
		</h2>

		<form id="payments-filter" method="get">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<?php $wp_list_table->display() ?>
		</form>
	</div>
	<?php
	}
}


if ( !class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class Group_Buying_Notifications_Table extends WP_List_Table {
	protected static $post_type = Group_Buying_Notification::POST_TYPE;

	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
				'singular' => 'notification',     // singular name of the listed records
				'plural' => 'notifications', // plural name of the listed records
				'ajax' => false     // does this table support ajax?
			) );

	}

	function extra_tablenav( $which ) {
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
			return apply_filters( 'gb_mngt_notification_column_'.$column_name, $item ); // do action for those columns that are filtered in
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
		$key = array_search( $item->ID, get_option( Group_Buying_Notifications::NOTIFICATIONS_OPTION_NAME, array() ) );
		$name = Group_Buying_Notifications::$notification_types[$key]['name'];
		$notification = Group_Buying_Notification::get_instance( $item->ID );
		$status = ( $notification->get_disabled() ) ? '<span style="color:red">'.gb__( 'disabled' ).'</span>' : '<span>'.gb__( 'active' ).'</span>' ;

		//Build row actions
		$actions = array(
			'edit'    => sprintf( '<a href="%s">Edit</a>', get_edit_post_link( $item->ID ) ),
		);

		//Return the title contents
		return sprintf( '%1$s <span style="color:silver">(status: %2$s)</span>%3$s',
			$name,
			$status,
			$this->row_actions( $actions )
		);
	}

	function column_subject( $item ) {
		echo $item->post_title;
	}

	function column_message( $item ) {
		echo $item->post_content;
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
			'title' => gb__('Type'),
			'subject'  => gb__('Subject'),
			'message'  => gb__('Message')
		);
		return apply_filters( 'gb_mngt_notification_columns', $columns );
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
		return apply_filters( 'gb_mngt_notification_sortable_columns', $sortable_columns );
	}


	/**
	 * Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 * */
	function get_bulk_actions() {
		$actions = array();
		return apply_filters( 'gb_mngt_notifications_bulk_actions', $actions );
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

		$args=array(
			'post_type' => Group_Buying_Notification::POST_TYPE,
			'post_status' => 'publish',
			'posts_per_page' => $per_page,
			'paged' => $this->get_pagenum(),
		);
		// Search
		if ( isset( $_GET['s'] ) && $_GET['s'] != '' ) {
			$args = array_merge( $args, array( 's' => $_GET['s'] ) );
		}
		// Filter by date
		if ( isset( $_GET['m'] ) && $_GET['m'] != '' ) {
			$args = array_merge( $args, array( 'm' => $_GET['m'] ) );
		}
		$notifications = new WP_Query( $args );

		/**
		 * REQUIRED. *Sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = apply_filters( 'gb_mngt_notifications_items', $notifications->posts );

		/**
		 * REQUIRED. Register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
				'total_items' => $notifications->found_posts,                //WE have to calculate the total number of items
				'per_page'  => $per_page,                    //WE have to determine how many items to show on a page
				'total_pages' => $notifications->max_num_pages   //WE have to calculate the total number of pages
			) );
	}

}