<?php

/**
 * GBS Router
 *
 * @package GBS
 * @subpackage Router
 * @version  0.3.1
 */

/**
 * Load all the plugin files and initialize appropriately
 *
 * @return void
 */
if ( !function_exists( 'GB_Router_load' ) ) {
	/**
	 * Load GB Router
	 * @return void
	 * @ignore
	 */
	function GB_Router_load() {
		// load the base class
		require_once 'GB_Router_Utility.class.php';
		require_once 'GB_Router.class.php';
		require_once 'GB_Route.class.php';
		require_once 'GB_Router_Page.class.php';
		add_action( 'init', array( 'GB_Router_Utility', 'init' ), -100, 0 );
		add_action( GB_Router_Utility::PLUGIN_INIT_HOOK, array( 'GB_Router_Page', 'init' ), 0, 0 );
		add_action( GB_Router_Utility::PLUGIN_INIT_HOOK, array( 'GB_Router', 'init' ), 1, 0 );
	}
	// Fire it up!
	GB_Router_load();
}
