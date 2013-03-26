<?php

/**
 * GBS Syndication Service.
 *
 * @package GBS
 * @subpackage Syndication
 */

abstract class GB_Aggregator_Plugin extends Group_Buying_Controller {
	const COMPONENTS_TO_LOAD_OPTION = 'gb_load_aggregator_components';
	protected static $settings_page = '';
	protected static $about_link = 'http://groupbuyingsite.com/forum/showthread.php?2843-Welcome-to-the-Syndication-Service';

	public static function init() {
		self::load_plugin();
		self::$settings_page = self::register_settings_page( 'gb_aggregator_settings', self::__( 'Group Buying Network Syndication Settings' ), self::__( 'Syndication' ), 500, FALSE, 'general', TRUE );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ), 10, 0 );
		add_action( self::CRON_HOOK, array( __CLASS__, 'flush_taxa_cache' ), 0, 0 );
	}

	public static function register_settings() {
		add_settings_section( 'general', '', array( __CLASS__, 'display_settings_section' ), self::$settings_page );
		register_setting( self::$settings_page, self::COMPONENTS_TO_LOAD_OPTION );
		add_settings_field( self::COMPONENTS_TO_LOAD_OPTION, self::__( 'Enabled Services' ), array( __CLASS__, 'display_option_enabled_components' ), self::$settings_page, 'general' );
	}

	public static function display_option_enabled_components() {
		$enabled = get_option( self::COMPONENTS_TO_LOAD_OPTION, array() );
		$available = array(
			'source' => self::__( 'Send your deals to the GBS syndication service.' ),
			'destination' => self::__( 'Receive deals from the GBS syndication service.' ),
			'master' => self::__( 'Be the syndication server' ),
		);
		if ( !file_exists( self::plugin_path( 'GB_Aggregator_Web_Service.php' ) ) ) {
			unset( $available['master'] );
		}
		foreach ( $available as $key => $label ) {
			printf( '<label><input type="checkbox" name="%s[]" value="%s" %s />&nbsp;%s</label><br />', self::COMPONENTS_TO_LOAD_OPTION, $key, checked( TRUE, in_array( $key, $enabled ), FALSE ), $label );
		}
		echo '<div class="error fade"><p>' . sprintf( self::__( 'Please <a href="%s" target="_blank">read about the GBS syndication service</a> before enabling any services on your site.' ), self::$about_link ) . '</p></div>';
	}

	private static function load_plugin() {
		$to_load = get_option( self::COMPONENTS_TO_LOAD_OPTION, array() );

		if ( !$to_load ) {
			return; // nothing to load
		}

		// the others use this
		require_once self::plugin_path( 'GB_Aggregator_Client.php' );

		// Send Deals
		if ( in_array( 'source', $to_load ) ) {
			require_once self::plugin_path( 'GB_Aggregator_Source.php' );
			GB_Aggregator_Source::init();
		}

		// Receive Deals
		if ( in_array( 'destination', $to_load ) ) {
			require_once self::plugin_path( 'GB_Aggregator_Destination.php' );
			GB_Aggregator_Destination::init();
		}

		if ( in_array( 'master', $to_load ) && file_exists( self::plugin_path( 'GB_Aggregator_Web_Service.php' ) ) ) {
			require_once self::plugin_path( 'GB_Aggregator_Web_Service.php' );
			GB_Aggregator_Web_Service::init();
		}

		require_once GB_PATH.'/template_tags/syndication.php';
	}

	/**
	 * Get the absolute system path to the plugin directory, or a file therein
	 *
	 * @static
	 * @param string  $path
	 * @return string
	 */
	protected static function plugin_path( $path ) {
		$base = dirname( __FILE__ );
		if ( $path ) {
			return trailingslashit( $base ).$path;
		} else {
			return untrailingslashit( $base );
		}
	}

	/**
	 * Get the absolute URL to the plugin directory, or a file therein
	 *
	 * @static
	 * @param string  $path
	 * @return string
	 */
	protected static function plugin_url( $path ) {
		return plugins_url( $path, __FILE__ );
	}

	/**
	 * Get taxonomy terms from the server
	 *
	 * @static
	 * @return array
	 */
	protected static function get_taxa(  ) {
		$taxa = get_option( 'gb_aggregator_taxonomy_cache', array() );
		if ( !$taxa ) {
			$taxa = self::flush_taxa_cache();
		}
		return $taxa;
	}

	public static function flush_taxa_cache() {
		if ( !class_exists( 'GB_Aggregator_Client' ) )
			return; // nothing to do if the service isn't loaded

		$client = new GB_Aggregator_Client();
		$taxa = $client->get_taxonomy_terms();
		if ( $taxa ) {
			update_option( 'gb_aggregator_taxonomy_cache', $taxa );
			return $taxa;
		}
		return array();
	}
}
