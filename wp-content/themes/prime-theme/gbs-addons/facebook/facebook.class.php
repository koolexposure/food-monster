<?php

/*
Plugin Name: Group Buying Facebook Login Module
Version: 3.0
Plugin URI: http://groupbuyingsite.com/features
Description: Allows users to login using their Facebook credentials and creates a Wordpress account for them.
Author: GroupBuyingSite.com
Author URI: http://groupbuyingsite.com/features
Plugin Author: Nathan Stryker & Dan Cameron
Plugin Author URI: http://sproutventure.com/
*/


if ( class_exists( 'Group_Buying_Theme_UI' ) ) {

	include 'template-tags.php';

	class Group_Buying_Facebook_Connect extends Group_Buying_Theme_UI {
		const APP_ID = 'gb_fb_app_id';
		const KEY = 'gb_fb_security_key';
		const SESSION_KEY = 'gb_fb_tk';
		const SESSION_KEY_EXP = 'gb_fb_tk_exp';
		const REG_REDIRECT = 'gb_fb_reg_redirect';
		private static $instance;
		protected static $theme_settings_page;
		private static $version = '3';
		private static $fb_app_id;
		private static $fb_sec;
		private static $reg_redirect;

		public static function init() {

			self::get_instance();

			self::$fb_app_id = get_option( self::APP_ID );
			self::$fb_sec = get_option( self::KEY );
			self::$reg_redirect = get_option( self::REG_REDIRECT, gb_get_account_url() );
			add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 0 );

			if ( self::$fb_app_id != '' || self::$fb_app_id != '' ) {
				add_action( 'parse_request', array( get_class(), 'connect' ) );
				add_action( 'wp_footer', array( get_class(), 'footer_code' ) );
				add_filter( 'gb_logout_url' , array( get_class(), 'log_out_url' ), 20 );
				add_action( 'gb_account_register_form_controls', array( get_class(), 'show_registration_option' ) );
				add_filter( 'get_avatar', array( get_class(), 'fb_avatar' ), 10, 4 );
			}

			if ( version_compare( get_bloginfo( 'version' ), '3.2.99', '>=' ) ) { // 3.3. only
				add_action( 'load-group-buying_page_group-buying/theme_options', array( get_class(), 'options_help_section' ), 45 );
			}

		}

		public static function options_help_section() {
			$screen = get_current_screen();
			$screen->add_help_tab( array(
					'id'      => 'theme-options-fb', // This should be unique for the screen.
					'title'   => self::__( 'Facebook Settings' ),
					'content' =>
					'<p><strong>' . self::__( 'Facebook Connect?' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( 'Allow users to login and register via Facebook. Configuration documentation can be found <a href="%s">here</a>' ), 'http://groupbuyingsite.com/forum/showthread.php?573-Allow-Users-to-Login-with-their-Facebook-Account' ) . '</p>'
				) );
		}

		public static function fb_avatar( $gravatar, $user_id = 0, $size = 18, $default = null ) {
			$current_user = wp_get_current_user();
			if ( $user_id == $current_user->ID && self::is_facebook_logged_in() ) {
				return '<img src="https://graph.facebook.com/'.self::get_facebook_uid().'/picture?return_ssl_resources=1" />';
			}
			return $gravatar;

		}

		public static function footer_code() {
			if ( !is_admin() ) {
				if ( isset( $_REQUEST['redirect_to'] ) && !empty( $_REQUEST['redirect_to'] ) ) {
					$redirect = str_replace( site_url(), '', $_POST['redirect_to'] ); // in case the home_url is already added
					$url = site_url( $redirect );
				} else {
					$url = gb_get_last_viewed_redirect_url();
				}
				$scope = apply_filters( 'gb_facebook_scope', 'email,user_location,user_status,read_stream,publish_stream' );
				ob_start();
?>
				    <div id="fb-root"></div>
					<script>
						window.fbAsyncInit = function() { FB.init({appId: <?php echo self::$fb_app_id ?>, status: true, cookie: true, xfbml: true, oauth: true}); };
						(function() {
							var e = document.createElement('script'); e.async = true;
							e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
							document.getElementById('fb-root').appendChild(e);
						}());
						function logoutFacebookUser() {
							FB.logout(function(response) {
							  window.location = "<?php echo gb_get_logout_url(); ?>";
							});
						}
						function fbActionConnect() {
							FB.login(function(response) {
							  if (response.authResponse) {
							  	window.location = "<?php echo $url; ?>";
							  }
							}, {scope:'<?php echo $scope ?>'});
						}
					</script>
			    <?php
				$view = ob_get_clean();
				echo apply_filters( 'gb_facebook_footer_code', $view, self::$fb_app_id, $url );

			}
		}

		public static function log_out_url( $link ) {
			if ( self::is_facebook_logged_in() != false ) {
				$fb_link = '<a onclick="logoutFacebookUser()" href="javascript:void()" title="'.gb__( 'Logout' ).'" class="logout">'.gb__( 'Logout' ).'</a>';
				$link = apply_filters( 'gb_facebook_log_out_url', $fb_link );
			}
			echo $link;
		}

		/**
		 * Checks if the user is logged into facebook, if so logs them into WP.
		 *
		 * @return void
		 * @author Dan Cameron
		 */
		public static function connect() {

			if ( is_user_logged_in() ) {
				return;
			}
			
			if ( self::is_facebook_logged_in() ) {

				$user_data = self::get_facebook_data();

				// fb user object
				if ( self::DEBUG ) error_log( "user_data : " . print_r( $user_data , true ) );

				if ( empty( $user_data ) )
					return;

				// facebook name
				$fb_uid   = $user_data->id;
				$fb_username  = ( isset( $user_data->username ) && $user_data->username != '' ) ? utf8_decode( $user_data->username ) : utf8_decode( $user_data->name );
				$fb_name   = utf8_decode( $user_data->name );
				$fb_email   = $user_data->email;
				$fb_last_name  = utf8_decode( $user_data->last_name );
				$fb_first_name  = utf8_decode( $user_data->first_name );
				$fb_url   = $user_data->url;

				// look for users with the fb_uid match
				global $wpdb;
				global $blog_id;

				$wp_user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s", $blog_id.'_fb_uid', $fb_uid ) );

				if ( empty( $wp_user_id ) ) {
					// Try to find the user via the deprecated method with the fb_uid meta key without blog_id prefix
					$wp_user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'fb_uid' AND meta_value = %s", $fb_uid ) );
					if ( !empty( $wp_user_id ) ) {
						// Store new meta key so we don't do this again.
						update_user_meta( $wp_user_id, $blog_id.'_fb_uid', $fb_uid );
					}
				}

				if ( empty( $wp_user_id ) ) {
					// Look for a user with the same email
					$wp_user_obj = get_user_by( 'email', $fb_email );

					// get the userid from the fb email if the query failed
					$wp_user_id = $wp_user_obj->ID;
					if ( self::DEBUG ) error_log( "wp_user_id: " . print_r( $wp_user_id, true ) );
				}

				if ( !empty( $wp_user_id ) ) {
					// set cookies manually since wp_signon requires the username/password combo.
					self::set_cookies( $wp_user_id );
					// Redirect after login
					if ( isset( $_REQUEST['redirect_to'] ) && !empty( $_REQUEST['redirect_to'] ) ) {
						$redirect = str_replace( home_url(), '', $_POST['redirect_to'] ); // in case the home_url is already added
						wp_redirect( home_url( $redirect ) );
					} 
					else {
						wp_redirect( gb_get_last_viewed_redirect_url() );
					}
					exit();
				}
				else { // register the user
					$data = array(
						'gb_contact_first_name' => $fb_first_name,
						'gb_contact_last_name' => $fb_last_name,
					);
					$user_id = Group_Buying_Accounts_Registration::create_user( $fb_username, $fb_email, null, $data );

					if ( $user_id ) {
						$user_data_array = json_decode( json_encode( $user_data ), true ); // convert object to array
						$user = new WP_User( $user_id );
						do_action( 'gb_registration', $user, $fb_username, $fb_email, $password, $user_data_array );

						if ( !empty( $fb_url ) ) {
							update_user_meta( $user_id, 'url', $fb_url );
						}
						update_user_meta( $user_id, $blog_id.'_fb_uid', $fb_uid );

						// set cookies manually since wp_signon requires the username/password combo.
						self::set_cookies( $user_id );

						$url = add_query_arg( array( 'message' => 'registered', 'facebook_reg' => 1 ), self::$reg_redirect );
						wp_redirect( $url, 303 );
						exit();
					} else {
						$url = gb_get_account_url();
						$url = add_query_arg( 'message', 'facebook_registration_fail',  gb_get_account_register_url() );
						wp_redirect( $url, 303 );
						exit();
					}
				}
			}
		}

		public static function register_settings_fields() {
			$page = parent::$theme_settings_page;
			$section = 'gb_facebook';
			add_settings_section( $section, self::__( 'Facebook Connect Section' ), array( get_class(), 'display_facebook_section' ), $page );
			register_setting( $page, self::APP_ID );
			register_setting( $page, self::KEY );
			register_setting( $page, self::REG_REDIRECT );

			add_settings_field( self::APP_ID, self::__( 'Facebook App ID' ), array( get_class(), 'display_app_id' ), $page, $section );
			add_settings_field( self::KEY, self::__( 'Facebook Secret' ), array( get_class(), 'display_security_key' ), $page, $section );
			add_settings_field( self::REG_REDIRECT, self::__( 'Redirect to this URL after Registration' ), array( get_class(), 'display_reg_redirect' ), $page, $section );
		}

		public static function display_facebook_section() {
			echo self::__( 'Your facebook settings to allow members to login and visitors to register.' );
		}

		public static function display_app_id() {
			echo '<input type="text" class="regular-text" name="'.self::APP_ID.'" value="'.self::$fb_app_id.'" />';
		}

		public static function display_security_key() {
			echo '<input type="text" class="regular-text" name="'.self::KEY.'" value="'.self::$fb_sec.'" />';
		}

		public static function display_reg_redirect() {
			echo '<input type="text" class="regular-text" name="'.self::REG_REDIRECT.'" value="'.self::$reg_redirect.'" />';
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

		private function __construct() {}


		private static function set_cookies( $user_id = 0, $remember = true ) {
			if ( !function_exists( 'wp_set_auth_cookie' ) )
				return false;
			if ( !$user_id )
				return false;
			if ( !$user = get_userdata( $user_id ) )
				return false;

			wp_clear_auth_cookie();
			wp_set_auth_cookie( $user_id, $remember );
			wp_set_current_user( $user_id );

			return true;
		}

		/**
		 * hook into the login page to show facebook button if no cookie
		 *
		 * @author Nathan Stryker
		 */
		public static function button( $button_text = 'Login with Facebook' ) {

			if ( !empty( self::$fb_app_id ) || !empty( self::$fb_sec ) ) {
				return apply_filters( 'gb_fb_add_login_button', '<span class="field clearfix"><a href="javascript:" onclick="fbActionConnect();" class="fb_button fb_button_medium"><span class="fb_button_text">'.self::__( $button_text ).'</span></a>' );
			}
		}

		public static function get_facebook_authorization_request() {

			$signed_request = self::parse_signed_request( $_COOKIE['fbsr_' . self::$fb_app_id], self::$fb_sec );
			if ( self::DEBUG ) error_log( "signed request: " . print_r( $signed_request, true ) );

			if ( !is_null( $signed_request ) ) {
				$url = 'https://graph.facebook.com/oauth/access_token?client_id='.self::$fb_app_id.'&redirect_uri=&client_secret='.self::$fb_sec.'&code='.$signed_request['code'];
				
				// Get FB response
				$access_token_response = self::get_web_data( $url );
				if ( self::DEBUG ) error_log( "get_facebook_authorization_request response: " . print_r( $access_token_response , true ) );
				
				// Get vars
				parse_str( $access_token_response ); // parse to vars

				if ( $access_token ) {
					// Store access token
					self::store_facebook_access_token( $access_token, time()+$expires );

					$signed_request['access_token'] = $access_token;

					if ( $expires == 0 ) {
						$signed_request['expires'] = 0;
					} 
					else { $signed_request['expires'] = $expires; }
				}
				else {
					$errors = json_decode( $access_token_response );
					if ( self::DEBUG ) gb_set_message( gb__('<strong>Facebook Connect Error:</strong> ') . $errors->error->message, Group_Buying_Controller::MESSAGE_STATUS_ERROR );
				}

			}
			return $signed_request;
		}

		public static function is_facebook_logged_in() {
			if ( isset( $_COOKIE['fbsr_' . self::$fb_app_id] ) ) {
				$uid = self::get_facebook_uid();
				if ( is_numeric( $uid ) ) {
					return $uid;
				}
			}
			return FALSE;
		}

		// The the FB user_id
		public static function get_facebook_uid() {
			// If logged in get the uid from the DB
			if ( is_user_logged_in() ) {
				global $blog_id;
				$uid = get_user_meta( get_current_user_id(), $blog_id.'_fb_uid', TRUE );
				if ( $uid ) { // If there's a uid set already return it.
					return $uid;
				}
			}
			// Attempt to get uid from the me graph and cached token
			$user_data = self::get_facebook_data();
			if ( !empty( $user_data ) ) {
				$uid = $user_data->id;
				if ( $uid ) {
					return $uid;
				}
			}
			// Get uid from API call
			$request = self::get_facebook_authorization_request();
			// return user_id if set in request
			return $request !== null && isset( $request['user_id'] ) ? $request['user_id'] : null;
		}

		// Get the access token from the Facebook cookie data
		public static function get_facebook_access_token() {
			// Check PHP session first
			if ( !headers_sent() ) {
				session_start();
				$session_token = $_SESSION[self::SESSION_KEY];
				if ( $session_token && $_SESSION[self::SESSION_KEY_EXP] > time() ) {
					return $session_token;
				}
			}
			// Try to get the cached token
			$cached_token = get_transient( 'gb_facebook_access_token_'.$_SERVER['REMOTE_ADDR'] );
			if ( $cached_token ) {
				return $cached_token;
			}
			// If token has expired or not previously set
			$request = self::get_facebook_authorization_request();
			// return access_token if set
			return $request !== null && isset( $request['access_token'] ) ? $request['access_token'] : null;
		}

		public static function store_facebook_access_token( $token, $expiration = 0 ) {
			// Use PHP sessions by default
			if ( !headers_sent() ) {
				session_start();
				$_SESSION[self::SESSION_KEY] = $token;
				$_SESSION[self::SESSION_KEY_EXP] = time()+$expiration;
			}

			// Store as a transient as a backup.
			if ( !$expiration ) {
				$expiration = 60*10; // five minutes
			}
			set_transient( 'gb_facebook_access_token_'.$_SERVER['REMOTE_ADDR'], $token, $expiration );
		}

		public static function get_facebook_data( $token = FALSE ) {
			if ( !$token ) { // Get token from facebook cookie
				$token = self::get_facebook_access_token();
			}

			// If no token don't continue
			if ( $token == '' )
				return FALSE;

			// Attempt to get data cache
			$cache_key = 'gb_facebook_access_token_'.$token;
			$cached_data = get_transient( $cache_key );
			if ( $cached_data ) {
				return $cached_data;
			}

			// get the data and set a cache
			$url = 'https://graph.facebook.com/me?access_token='.$token;
			$user_data = json_decode( self::get_web_data( $url ) );
			set_transient( $cache_key, $user_data, 21600 );
			return $user_data;
		}

		public static function set_user_status( $status ) {
			$uid = self::get_facebook_uid();
			$token = self::get_facebook_access_token();

			if ( !$uid || !$token )
				return;

			$uri = 'https://graph.facebook.com/me/feed';
			$response = wp_remote_post( $uri, array(
					'method' => 'POST',
					'timeout' => apply_filters( 'http_request_timeout', 15 ),
					'sslverify' => false,
					'body' => array(
						'access_token' => $api_key,
						'message' => $status
					),
				) );
			if ( is_wp_error( $response ) ) {
				return NULL;
			}
			return wp_remote_retrieve_body( $response );
		}

		public static function get_web_data( $uri ) {

			$response = wp_remote_post( $uri, array(
					'method' => 'GET',
					'timeout' => apply_filters( 'http_request_timeout', 15 ),
					'sslverify' => false
				) );

			if ( is_wp_error( $response ) ) {
				return NULL;
			}

			return wp_remote_retrieve_body( $response );

		}

		public static function parse_signed_request( $signed_request, $secret ) {
			list( $encoded_sig, $payload ) = explode( '.', $signed_request, 2 );

			// decode the data
			$sig = self::base64_url_decode( $encoded_sig );
			$data = json_decode( self::base64_url_decode( $payload ), true );

			if ( strtoupper( $data['algorithm'] ) !== 'HMAC-SHA256' ) {
				if ( self::DEBUG ) error_log( 'Unknown algorithm. Expected HMAC-SHA256' );
				return null;
			}

			// check sig
			$expected_sig = hash_hmac( 'sha256', $payload, $secret, $raw = true );
			if ( $sig !== $expected_sig ) {
				if ( self::DEBUG ) error_log( 'Bad Signed JSON signature!' );
				return null;
			}

			return $data;
		}

		public static function base64_url_decode( $input ) {
			return base64_decode( strtr( $input, '-_', '+/' ) );
		}

		public static function show_registration_option() {
			echo '<div id="facebook_registration_button" class="facebook_button clearfix">';
			echo self::button( 'Register with Facebook' );
			echo '</div><!-- #facebook_registration_button.facebook_button-->';

		}


	}
}
add_action( 'init', array( 'Group_Buying_Facebook_Connect', 'init' )  );