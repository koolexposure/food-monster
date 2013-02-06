<?php
/*
Plugin Name: Group Buying Syndication
Version: 0.1.0 Beta
Description: Syndicate deals across Group Buying Sites
Plugin URI: http://groupbuyingsite.com/aggregation
Author: GroupBuyingSite.com
Author URI: http://groupbuyingsite.com/features
Plugin Author: Dan Cameron
Plugin Author URI: http://sproutventure.com/
Contributors: Dan Cameron, Jonathan Brinley
Text Domain: group-buying
*/


/**
 * Load all the plugin files and initialize appropriately
 *
 * @return void
 */
if ( !function_exists( 'gbs_aggregator_load' ) ) { // play nice
	/**
	 * Load syndication service
	 * @return void
	 * @ignore
	 */
	function gbs_aggregator_load() {
		require_once 'GB_Aggregator_Plugin.php';
		GB_Aggregator_Plugin::init();
	}

	// Wait for GBS to load, then fire it up
	if ( class_exists( 'Group_Buying' ) ) { // gbs has already loaded
		gbs_aggregator_load();
	} else { // lie in wait
		add_action( 'group_buying_load', 'gbs_aggregator_load', 10, 0 );
	}
}
