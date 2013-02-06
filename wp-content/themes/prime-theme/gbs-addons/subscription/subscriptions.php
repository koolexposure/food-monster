<?php

if ( class_exists( 'Group_Buying_Controller' ) ) {

	include 'template-tags.php';

	abstract class Group_Buying_List_Services extends Group_Buying_Controller {
		const LIST_SUBSCRIBE_OPTION = 'gb_list_service';
		const SIGNUP_REDIRECT_OPTION = 'gb_signup_redirect';
		const SIGNUP_CITYNAME_OPTION = 'gb_signup_city_name';
		const SIGNUP_NOT_FOUND_OPTION = 'gb_signup_not_found';
		const SIGNUP_FOOTER_SCRIPTS_OPTION = 'gb_signup_footer_scripts';
		const REGISTRATION_OPTION = 'gb_signup_on_registration';
		const REGISTRATION_OPTIN = 'gb_subscription_option_in';
		const SUBSCRIPTION_FORM_CUSTOM = 'gb_subscription_custom_html';
		private static $list_service;
		private static $active_list_service_class;
		protected static $settings_page;
		private static $potential_processors = array();
		protected static $signup_redirect;
		protected static $default_city_name;
		protected static $not_found_redirect;
		protected static $footer_scripts;
		protected static $registration_option;
		protected static $subscription_custom_html;
		protected static $redirect = '';

		final public static function init() {
			self::$settings_page = self::register_settings_page( 'subscription', self::__( 'Group Buying Subscription Options' ), self::__( 'Subscription Settings' ), 15000, 'theme' );
			add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 0 );

			self::get_list_service();
			self::$signup_redirect = get_option( self::SIGNUP_REDIRECT_OPTION, '0' );
			self::$default_city_name = get_option( self::SIGNUP_CITYNAME_OPTION, 'I don&#39;t see my city' );
			self::$registration_option = get_option( self::REGISTRATION_OPTION, 'false' );
			self::$not_found_redirect = get_option( self::SIGNUP_NOT_FOUND_OPTION );
			self::$footer_scripts = get_option( self::SIGNUP_FOOTER_SCRIPTS_OPTION );
			self::$subscription_custom_html = get_option( self::SUBSCRIPTION_FORM_CUSTOM );

			if ( self::$registration_option != 'false' ) {
				add_action( 'gb_account_registration_panes', array( get_class(), 'show_registration_option' ) );
			}
			add_action( 'wp_footer', array( get_class(), 'footer_scripts' ) );

			if ( version_compare( get_bloginfo( 'version' ), '3.2.99', '>=' ) ) { // 3.3. only
				add_action( 'load-group-buying_page_group-buying/subscription', array( get_class(), 'options_help_section' ), 45 );
			}
		}

		public static function options_help_section() {
			$screen = get_current_screen();
			$screen->add_help_tab( array(
					'id'      => 'theme-options-subs', // This should be unique for the screen.
					'title'   => self::__( 'Subscription Services' ),
					'content' =>
					'<p><strong>' . self::__( 'Subscription Service?' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( 'Setting up a subscirption service is a key feature of the GBS themes. Subscriber&rsquo;s e-mail address and location preference are sent to the subscription service for the purpose of e-mail marketing. <ul><li>MailChimp setup instructions can be found <a href="%s">here</a>.</li><li>ConstantContact setup instructions can be found <a href="%s">here</a></li></ul>' ), 'http://groupbuyingsite.com/forum/showthread.php?711-MailChimp-Setup', 'http://groupbuyingsite.com/forum/showthread.php?713-Constant-Contact-Setup' ) . '</p>' .
					'<p>' . self::__( 'Notes: InfusionSoft is stil under development and no setup documentation is provided. The "Custom Form" option simply replaces the GBS generated form with something you enter in a new option.' ) . '</p>'
				) );
			$screen->add_help_tab( array(
					'id'      => 'theme-options-ss', // This should be unique for the screen.
					'title'   => self::__( 'Subscription Settings' ),
					'content' =>
					'<p><strong>' . self::__( 'Signup Redirect:' ) . '</strong></p>' .
					'<p>' . self::__( 'After a successful signup redirect to a selected page instead of dynamically redirecting them based on their choice.' ) . '</p>' .
					'<p><strong>' . self::__( 'Signup Extra City:' ) . '</strong></p>' .
					'<p>' . self::__( 'Locations in the dropdowns are generated from deal locations.  This setting allows for an additional option if the visitor doesn&rsquo;t see their city/location within the options.' ) . '</p>' .
					'<p><strong>' . self::__( 'City Not Found Redirection:' ) . '</strong></p>' .
					'<p>' . self::__( 'If the above option (extra city) is selected the user is redirected to this page instead of the default, all deals page.' ) . '</p>' .
					'<p><strong>' . self::__( 'Footer Scripts:' ) . '</strong></p>' .
					'<p>' . self::__( 'These scripts will inserted after a successful sign-up.' ) . '</p>'
				) );
		}

		/**
		 * Get an instance of the active subscription processor
		 *
		 * @static
		 * @return Group_Buying_List_Services|NULL
		 */
		public static function get_list_service() {
			do_action( 'gb_register_subscription_services' );
			// Get the option specifying which subscription processor to use
			self::$active_list_service_class = get_option( self::LIST_SUBSCRIBE_OPTION, 'Group_Buying_None' );
			self::$list_service = call_user_func( array( self::$active_list_service_class, 'get_instance' ) );
			return self::$list_service;
		}

		public static function footer_scripts() {

			if ( isset( $_GET['signup-success'] ) ) {
				echo self::$footer_scripts;
			}
		}

		/**
		 *
		 *
		 * @static
		 * @return string The ID of the subscription settings page
		 */
		public static function get_settings_page() {
			return self::$settings_page;
		}

		/*
		 * Singleton Design Pattern
		 * ------------------------------------------------------------- */
		final protected function __clone() {
			// cannot be cloned
			trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
		}

		final protected function __sleep() {
			// cannot be serialized
			trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
		}

		/**
		 *
		 *
		 * @static
		 * @abstract
		 * @return Group_Buying_List_Services|NULL
		 */
		protected static abstract function get_instance();

		protected function __construct() {
			self::process_subscription_post();
		}

		public static function process_subscription_post() {
			do_action( 'process_subscription_post', $_POST );

			add_action( 'gb_registration', array( self::$active_list_service_class, 'process_registration_subscription' ), 20, 5 );

			if ( isset( $_POST['gb_subscription'] ) ) {
				add_action( 'init', array( self::$active_list_service_class, 'process_subscription' ) );
			}
		}

		/**
		 * Process a subscription
		 *
		 * @abstract
		 */
		public abstract function process_subscription();

		/**
		 * Process a subscription
		 *
		 * @abstract
		 */
		public abstract function process_registration_subscription();

		/**
		 * Subclasses have to register to be listed as subscription options
		 *
		 * @abstract
		 * @return void
		 */
		public abstract static function register();

		public static function success( $location = 0, $email = '' ) {

			if ( !empty( $email ) ) {
				$message = sprintf( gb__( 'CONTACT %s ADDED.' ), $email );
				Group_Buying_Controller::set_message( $message, 'info' );
			} else {
				$message = sprintf( gb__( 'Thank You.' ), $email );
				Group_Buying_Controller::set_message( $message, 'info' );
			}

			// Set the redirect url
			$location = ( $location == 'notfound' ) ? FALSE : $location;
			if ( !$location && self::$not_found_redirect ) {
				self::$redirect = get_permalink( self::$not_found_redirect );
			} elseif ( self::$signup_redirect ) {
				self::$redirect = get_permalink( self::$signup_redirect );
			}
			if ( self::$redirect == '' ) {
				self::$redirect = gb_get_latest_deal_link( $location );
			}
			if ( function_exists( 'gb_set_location_preference' ) && $location ) {
				gb_set_location_preference( $location );
			}
			self::$redirect = add_query_arg( array( 'signup-success' => '1' ), self::$redirect );
			wp_redirect( apply_filters( 'gb_subscription_success_redirect_url', self::$redirect ) );
			exit();

		}

		public static function register_settings_fields() {

			register_setting( self::$settings_page, self::LIST_SUBSCRIBE_OPTION );
			register_setting( self::$settings_page, self::SIGNUP_REDIRECT_OPTION );
			register_setting( self::$settings_page, self::SIGNUP_CITYNAME_OPTION );
			register_setting( self::$settings_page, self::SIGNUP_NOT_FOUND_OPTION );
			register_setting( self::$settings_page, self::SIGNUP_FOOTER_SCRIPTS_OPTION );
			register_setting( self::$settings_page, self::REGISTRATION_OPTION );
			register_setting( self::$settings_page, self::SUBSCRIPTION_FORM_CUSTOM );


			add_settings_field( self::LIST_SUBSCRIBE_OPTION, self::__( 'Select Service' ), array( get_class(), 'display_list_service_selection' ), self::$settings_page );
			add_settings_field( self::SIGNUP_REDIRECT_OPTION, self::__( 'Signup Redirect' ), array( get_class(), 'display_list_service_redirect' ), self::$settings_page );
			add_settings_field( self::SIGNUP_CITYNAME_OPTION, self::__( 'Signup Extra City' ), array( get_class(), 'display_list_service_city' ), self::$settings_page );
			add_settings_field( self::SIGNUP_NOT_FOUND_OPTION, self::__( 'City Not Found Redirection' ), array( get_class(), 'display_list_service_not_found' ), self::$settings_page );
			add_settings_field( self::REGISTRATION_OPTION, self::__( 'Registration' ), array( get_class(), 'display_registration_subscription_option' ), self::$settings_page );
			add_settings_field( self::SIGNUP_FOOTER_SCRIPTS_OPTION, self::__( 'Footer Scripts' )."<p><small>".self::__( 'Use this for google Analytics or other tracking.' )."</small></p>", array( get_class(), 'display_list_service_scripts' ), self::$settings_page );
			if ( self::$active_list_service_class == 'Group_Buying_Custom' ) {
				add_settings_field( self::SUBSCRIPTION_FORM_CUSTOM, self::__( 'Custom HTML' )."<p><small>".self::__( 'Use this for a custom html form.' )."</small></p>", array( get_class(), 'display_custom_list_service' ), self::$settings_page );
			}
			//add_settings_section('services', self::__('Supported Services'), array(get_class(), 'display_list_services'), self::$settings_page);
		}

		final protected static function add_list_service( $class, $label ) {
			self::$potential_processors[$class] = $label;
		}

		public static function display_list_services() {
			?>
				<a href="http://eepurl.com/7y3E"><img src="https://us1.admin.mailchimp.com/_ssl/proxy.php?u=http%3A%2F%2Fgallery.mailchimp.com%2F089443193dd93823f3fed78b4%2Fimages%2FMC_MonkeyReward_15.1.png"></a>
			<?php
		}

		public static function display_list_service_selection() {
			echo '<select id="'.self::LIST_SUBSCRIBE_OPTION.'" name="'.self::LIST_SUBSCRIBE_OPTION.'">';
			foreach ( self::$potential_processors as $class => $label ) {
				echo '<option value="'.$class.'" '.selected( self::$active_list_service_class, $class ).'>'.$label.'</option>';
			}
			echo '</select>';
		}

		public static function display_list_service_redirect() {
			wp_dropdown_pages( array( 'name' => self::SIGNUP_REDIRECT_OPTION, 'echo' => 1, 'show_option_none' => self::__( '* Default' ), 'option_none_value' => '0', 'selected' => self::$signup_redirect ) );
		}


		public static function display_registration_subscription_option() {
			echo '<label><input type="radio" name="'.self::REGISTRATION_OPTION.'" value="true" '.checked( 'true', self::$registration_option, FALSE ).'/> '.self::__( 'Show subscription option on registration page.' ).'</label><br />';
			echo '<label><input type="radio" name="'.self::REGISTRATION_OPTION.'" value="checked" '.checked( 'checked', self::$registration_option, FALSE ).'/> '.self::__( 'Show subscription option on registration page (pre-checked).' ).'</label><br/>';
			echo '<label><input type="radio" name="'.self::REGISTRATION_OPTION.'" value="false" '.checked( 'false', self::$registration_option, FALSE ).'/> '.self::__( 'Remove subscription option on registration page.' ).'</label>';
		}

		public static function display_list_service_city() {
			echo '<input type="text" class="regular-text" name="'.self::SIGNUP_CITYNAME_OPTION.'" value="'.self::$default_city_name.'" />';
		}

		public static function display_list_service_not_found() {
			wp_dropdown_pages( array( 'name' => self::SIGNUP_NOT_FOUND_OPTION, 'echo' => 1, 'show_option_none' => self::__( '* Default' ), 'option_none_value' => '0', 'selected' => self::$not_found_redirect ) );
		}

		public static function display_list_service_scripts() {
			echo '<textarea rows="5" cols="40" name="'.self::SIGNUP_FOOTER_SCRIPTS_OPTION.'">'.self::$footer_scripts.'</textarea>';
		}

		public static function show_registration_option( array $panes ) {

			$view = '<label><input type="checkbox" class="regular-text" name="'.self::REGISTRATION_OPTIN.'" '.self::$registration_option.'/>';

			$cookie = gb_get_preferred_location();
			if ( empty( $cookie ) ) {
				$locations = gb_get_locations( false );
				$no_city_text = get_option( self::SIGNUP_CITYNAME_OPTION );
				if ( !empty( $locations ) || !empty( $no_city_text ) ) {

					$view .= self::__( ' Notify me of future deals from' ).'</label>';
					$view .= '<span class="option registration_location_options_wrap">';
					$view .= ' <select name="deal_location" id="deal_location" size="1">';
					$preference = ( isset( $_COOKIE[ 'gb_location_preference' ] ) ) ? $_COOKIE[ 'gb_location_preference' ] : '' ;
					foreach ( $locations as $location ) {
						$view .= '<option value="'.$location->slug.'" '.selected( $preference, $location->slug ).'>'.$location->name.'</option>';
					}
					if ( !empty( $no_city_text ) ) {
						$view .= '<option value="notfound">'.esc_attr( $no_city_text ).'</option>';
					}
					$view .= '</select>';

					$view .= '</span>';
				}
			} else {
				$view .= self::__( ' Notify me of future deals and updates.' ).'</label>';
			}

			$panes['subscription'] = array(
				'weight' => 99,
				'body' => $view,
			);
			return $panes;

		}

		public static function display_custom_list_service() {
			echo '<textarea rows="5" cols="40" id="'.self::SUBSCRIPTION_FORM_CUSTOM.'" name="'.self::SUBSCRIPTION_FORM_CUSTOM.'">'.self::$subscription_custom_html.'</textarea>';
		}

		public abstract function get_subscription_method();

	}
	// subscription processors
	foreach ( glob(  get_template_directory() . '/gbs-addons/subscription/list-services/*.class.php' ) as $file_path ) {
		require_once $file_path;
	}
	Group_Buying_List_Services::init();

}
