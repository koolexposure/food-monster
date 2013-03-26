<?php
/*
Plugin Name: Group Buying Addon - Merchant Meta
Version: 1
Plugin URI: http://groupbuyingsite.com/marketplace
Description: Add Meta Fields to the merchant post type.
Author: Sprout Venture
Author URI: http://sproutventure.com/wordpress
Plugin Author: Dan Cameron
Contributors: Dan Cameron 
Text Domain: group-buying
Domain Path: /lang

*/

add_action('plugins_loaded', 'gb_merchant_meta');
function gb_merchant_meta() {
	if (class_exists('Group_Buying_Controller')) {
		require_once('merchantMeta.class.php');
		require_once('library/template-tags.php');
		Group_Buying_Merchant_Meta_Addon::init();
	}
}