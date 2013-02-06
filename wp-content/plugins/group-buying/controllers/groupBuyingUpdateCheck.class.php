<?php

/**
 * Automatic updates controller.
 *
 * @package GBS
 * @subpackage Base
 */
class Group_Buying_Update_Check extends Group_Buying_Controller {

	const PLUGIN_NAME = 'group_buying_site'; // plugin_basename( GB_PATH . '/group-buying.php' ); // TODO at some point GBS needs to use the correct slug.
	const API_URL = 'http://groupbuyingsite.com/check-key/';
	//const API_URL = 'http://staging.groupbuyingsite.com/check-key/';
	const API_KEY_OPTION = 'api_key';

	public static $page_slug = 'group-buying/gb_settings';
	public static $api_key;
	public static $error;

	static function init() {
		add_action( 'init', array( get_class(), 'check_api_key' ), 100, 0 );
		add_action( 'init', array( get_class(), 'check_key' ), 5 );
		add_action( 'init', array( get_class(), 'multipass' ), 200, 0 );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 0 );
		// Plugin API for purchase
		add_filter( 'plugins_api_result', array( get_class(), 'plugins_api_result' ), 10, 3 );
		add_action( 'install_plugins_pre_plugin-information', array( get_class(), 'upgrade_popup' ) ); // thickbox info

	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_api_key';
		add_settings_section( $section, self::__( 'Group Buying Site API Key' ), array( get_class(), 'display_api_key_section' ), $page );

		// Settings
		register_setting( $page, self::get_api_key_option_name() );
		add_settings_field( self::get_api_key_option_name(), self::__( 'API Key' ), array( get_class(), 'display_api_key' ), $page, $section );
	}

	public static function display_api_key_section() {
		echo self::__( 'Enter your API key to activate Group Buying Site' );
	}

	public static function display_api_key() {
		$style = ( self::has_stored_api_key() ) ? 'background:#ceffbb;border-color:#8ae22d;' : 'background:#ffebe8;border-color:#c00;' ; // OMFG inline styles, I know right?
		echo '<input type="text" name="'.self::get_api_key_option_name().'" id="'.self::get_api_key_option_name().'" style="'.$style.'" value="' . self::get_api_key() . '"  size="40" /><br />';
	}

	static function check_api_key() {
		global $wp_version;
		if ( !self::has_stored_api_key() ) {
			$api_key = self::get_api_key();
			if ( is_null( $api_key ) ) {
				add_action( 'admin_notices', array( get_class(), 'invalid_api_key' ) );
			} else {
				$valid = self::is_valid_key( $api_key );
				if ( !$valid ) {
					add_action( 'admin_notices', array( get_class(), 'invalid_api_key' ) );
					if ( isset( self::$error ) && is_admin() ) {
						add_action( 'admin_notices', array( get_class(), 'error_api_key' ) );
					}
				} else {
					if ( isset( self::$error ) && $valid == 'ERROR' ) {
						add_action( 'admin_notices', array( get_class(), 'error_api_key' ) );
					} else {
						self::set_stored_api_key( $api_key );
						add_action( 'pre_set_site_transient_update_plugins', array( get_class(), 'check_for_updates' ) );
					}
				}
			}
		} else {
			$api_key = self::get_stored_api_key();
			add_filter( 'pre_set_site_transient_update_plugins', array( get_class(), 'check_for_updates' ) );
		}
		self::$api_key = $api_key;
	}

	static function has_stored_api_key() {
		$validated_key = self::get_stored_api_key();
		return false != $validated_key;
	}

	static function get_stored_api_key() {
		// delete_transient( self::PLUGIN_NAME . '_validated_key' );
		return get_transient( self::PLUGIN_NAME . '_validated_key' );
	}

	static function set_stored_api_key( $api_key ) {
		set_transient( self::PLUGIN_NAME . '_validated_key', $api_key, 60*60*12 );
		self::set_persistant_stored_api_key( $api_key );
	}

	static function has_valid_api_key() {
		$validated_key = get_option( self::PLUGIN_NAME . '_valid_key', FALSE );
		if ( FALSE != $validated_key ) {
			return TRUE;
		}
		return FALSE;
	}

	static function set_persistant_stored_api_key( $api_key ) {
		add_option( self::PLUGIN_NAME . '_valid_key', $api_key );
	}

	/**
	 * Hey WTF?!!
	 * It's a sad day in our households if you're looking at this function to
	 * bypass the API check. Seriously, it's not expensive; compared to how
	 * much work we actually put into this.
	 *
	 * If you're a developer, contact us.
	 *
	 */
	static function check_key() {
		if ( is_admin() ) {
			if ( !self::has_stored_api_key() ) {
				$api_key = self::get_api_key();
				if ( is_null( $api_key ) ) {
					remove_action( 'init', array( 'Group_Buying_Post_Type', 'register_post_types' ) );
				} else {
					$valid = self::is_valid_key( $api_key );
					if ( isset( self::$error ) && $valid == 'ERROR' ) {
						add_action( 'admin_notices', array( get_class(), 'error_api_key' ) );
						return;
					}
					if ( $valid ) {
						self::set_stored_api_key( $api_key );
						return;
					}
					if ( !self::has_valid_api_key() || self::has_valid_api_key() ) {
						remove_action( 'init', array( 'Group_Buying_Post_Type', 'register_post_types' ) );
					}
				}
			}
		}
		return;
	}

	static function is_valid_key( $api_key, $delay = false ) {
		global $wp_version;

		// Delay the api key in case the server is down.
		$delay_key = self::PLUGIN_NAME . '_last_api_call';
		$delay = get_transient( $delay_key );
		if ( $delay ) {
			$minutes = round( ( $delay-time() )/60 );
			self::$error = 'API server connection error. Next validation check in '.$minutes.' minutes, contact support if this error persists more than 6 hours.';
			return 'ERROR';
		}

		$validation = wp_remote_post( self::API_URL, array(
				'body' => array(
					'key' => $api_key,
					'plugin' => self::PLUGIN_NAME,
					'url' => home_url(),
					'wp_version' => get_bloginfo( 'version' ),
					'plugin_version' => Group_Buying::GB_VERSION
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url()
			) );

		if ( is_wp_error( $validation ) || $validation['response']['code'] != '200' ) {
			self::$error = 'API server connection error. Next validation in 60 minutes.';
			set_transient( $delay_key, time()+3600, 3600 ); // Delay an hour
			return 'ERROR';
		}
		$response = json_decode( wp_remote_retrieve_body( $validation ) );
		if ( is_null( $response ) || ( isset( $response->error ) && $response->error ) ) {
			self::$error = $response->error_message;
			return FALSE;
		} else {
			if ( isset( $response->error_message ) && $response->error_message != '' ) {
				self::$error = $response->error_message;
			}
			return TRUE;
		}
	}

	static function check_for_updates( $transient ) {
		$api_key = self::get_api_key();
		
		if ( empty( $api_key ) ) {
			return $transient;
		}

		$api_data = self::api_response();
		if ( !is_wp_error( $api_data ) ) {
			if ( version_compare( Group_Buying::GB_VERSION, $api_data->version, '<' ) ) {
				$api_data->slug = plugin_basename( GB_PATH . '/group-buying.php' ); // Use the slug that this plugin forces
				$transient->response[$api_data->slug] = $api_data;
			}
		}
		return $transient;
	}

	static function multipass() {
		if ( is_multisite() && GB_IS_AUTHORIZED_WPMU_SITE && defined( 'GBS_API_KEY' )  ) {
			remove_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 0 );
			remove_action( 'admin_notices', array( get_class(), 'invalid_api_key' ), 10, 0 );
			// remove_action( 'admin_notices', array( get_class(), 'error_api_key' ), 10, 0  );
		}
		elseif ( is_multisite() ) {
			add_action( 'admin_notices', array( get_class(), 'error_mu_install' ) );
			remove_action( 'init', array( 'Group_Buying_Post_Type', 'register_post_types' ) );
		}
	}

	static function has_update_response() {
		$reponse = self::get_update_response();
		return false != $reponse;
	}

	static function set_update_response( $response ) {
		set_transient( self::PLUGIN_NAME . '_gb_update_check', $response, 60*60*6 ); // split the 12 hour check
	}

	static function get_update_response() {
		return get_transient( self::PLUGIN_NAME . '_gb_update_check' );
	}

	static function get_api_key() {
		if ( defined( 'GBS_API_KEY' ) ) {
			return GBS_API_KEY;
		}
		return get_option( self::get_api_key_option_name() );
	}

	static function get_api_key_option_name() {
		return self::PLUGIN_NAME . '_' . self::API_KEY_OPTION;
	}

	static function invalid_api_key() {
		echo '<div class="error fade"><p>' . sprintf( self::__( 'In order to use Group Buying Site, you need to <a href="%s">enter a valid API Key</a>.' ), add_query_arg( 'page', self::$page_slug, admin_url( 'admin.php' ) ) ) . '</p></div>';
	}

	static function error_api_key() {
		if ( is_multisite() ) {
			echo '<div class="error fade"><p>' . sprintf( self::__( '<strong>API ERROR:</strong> Contact your site administrator about this error. <em>Error Code: %s</em>' ), self::$error ) . '</p></div>';
		} else {
			echo '<div class="error fade"><p>' . sprintf( self::__( '<strong>Group Buying Site API KEY error:</strong> %s' ), self::$error ) . '</p></div>';
		}
	}

	static function error_mu_install() {
		echo '<div class="error fade"><p>' . self::__( '<strong>This version of Group Buying Site is not authorized and compatible with WPMU.</strong>' ). '</p></div>';
	}
	
	public static function api_response() {
		if ( !self::has_update_response() ) {
			$response = wp_remote_post( self::API_URL, array( 'body' => array(
						'key' => self::get_api_key(),
						'plugin' => self::PLUGIN_NAME,
						'url' => home_url(),
						'wp_version' => get_bloginfo( 'version' ),
						'plugin_version' => Group_Buying::GB_VERSION
					) ) );

			$data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( is_wp_error( $response ) || $response['response']['code'] != '200' ) {
				return new WP_Error( 'api_response_error', 'An unexpected error occurred.' );
			}
			self::set_update_response( $data );
		} else {
			$data = self::get_update_response();
		}
		return $data;
	}

	public function plugins_api_result( $response, $action, $api_args ) {

		if ( isset( $response->errors ) && strpos( $api_args->slug, 'group-buying.php' ) ) {
			$api_data = self::api_response();

			if ( !is_wp_error( $api_data ) ) {
				$response = new stdClass();
				// set the correct variables
				$response->name = $api_data->post_title;
				$response->version = $api_data->version;
				$response->download_link = $api_data->download_url;
				$response->tested = $api_data->wp_version_tested;
			}
		}
		return $response;
	}

	function upgrade_popup() {
		if ( !strpos( $_GET['plugin'], 'group-buying.php' ) )
			return;
		
		$api_data = self::api_response();
		if ( is_wp_error( $api_data ) ) {
			echo '<p>Could not retrieve version details. Please try again.</p>';
		}
		else {
			print $api_data->update_info;
		}
		exit;
	}
}
