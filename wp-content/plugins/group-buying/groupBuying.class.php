<?php

/**
 * A fundamental class from which all other classes in the plugin should be derived.
 * The purpose of this class is to hold data useful to all classes.
 * @package GBS
 */

if ( !defined( 'GBS_DEV' ) )
	define( 'GBS_DEV', FALSE );

abstract class Group_Buying {

	/**
	 * Application text-domain
	 */
	const TEXT_DOMAIN = 'group-buying';
	/**
	 * Current version. Should match group-buying.php plugin version.
	 */
	const GB_VERSION = '4.4';
	/**
	 * DB Version
	 */
	const DB_VERSION = 1;
	/**
	 * Application Name
	 */
	const PLUGIN_NAME = 'Group Buying Site';
	/**
	 * GBS_DEV constant within the wp-config to turn on GBS debugging
	 * <code>
	 * define( 'GBS_DEV', TRUE/FALSE )
	 * </code>
	 */
	const DEBUG = GBS_DEV;

	/**
	 * A wrapper around WP's __() to add the plugin's text domain
	 *
	 * @param string  $string
	 * @return string|void
	 */
	public static function __( $string ) {
		return __( apply_filters( 'gb_string_'.sanitize_title( $string ), $string ), self::TEXT_DOMAIN );
	}

	/**
	 * A wrapper around WP's _e() to add the plugin's text domain
	 *
	 * @param string  $string
	 * @return void
	 */
	public static function _e( $string ) {
		return _e( apply_filters( 'gb_string_'.sanitize_title( $string ), $string ), self::TEXT_DOMAIN );
	}
}
