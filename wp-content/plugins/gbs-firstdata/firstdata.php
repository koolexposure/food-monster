<?php
/*
Plugin Name: Group Buying Payment Processor - First Data
Version: .1
Plugin URI: http://sproutventure.com/wordpress/group-buying
Description: First Data
Author: Sprout Venture
Author URI: http://sproutventure.com/wordpress
Plugin Author: Dan Cameron
Contributors: Dan Cameron
Text Domain: group-buying
Domain Path: /lang
*/

add_action('gb_register_processors', 'gb_load_firstdata');
function gb_load_firstdata() {
	require_once('firstdata.class.php');
}