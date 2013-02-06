<?php

/*
Plugin Name: Group Buying Advanced Thumbnails
Version: 3.0
Plugin URI: http://groupbuyingsite.com/features
Description: Allows users to use TimThumb for thumbnail cropping
Author: GroupBuyingSite.com
Author URI: http://groupbuyingsite.com/features
Plugin Author: Dan Cameron
Plugin Author URI: http://sproutventure.com/
*/


if ( class_exists( 'Group_Buying_Theme_UI' ) && !class_exists( 'Group_Buying_Advanced_Thumbs' ) ) {

	include 'template-tags.php';

	class Group_Buying_Advanced_Thumbs extends Group_Buying_Theme_UI {
		const ACTIVATED = 'gb_adv_thumbs';
		const QUALITY = 'gb_adv_thumbs_quality';
		const ZC = 'gb_adv_thumbs_sc';
		const ALIGN = 'gb_adv_thumbs_align';
		const SHARPEN = 'gb_adv_thumbs_sharpen';
		const COLOR = 'gb_adv_thumbs_cc';
		private static $instance;
		protected static $theme_settings_page;
		private static $version = '1.1';
		public static $documentation = 'http://groupbuyingsite.com/forum/showthread.php?2243-Advanced-Thumbnail-Setup-Instructions-(Premium-Theme-1.2)';
		private static $active;
		private static $quality;
		private static $align;
		private static $zc;
		private static $sharpen;
		private static $cc;

		private function __construct() {
			self::$active = get_option( self::ACTIVATED, '0' );
			self::$quality = get_option( self::QUALITY, '89' );
			self::$align = get_option( self::ALIGN, '0' );
			self::$sharpen = get_option( self::SHARPEN, '0' );
			self::$zc = get_option( self::ZC, '2' );
			self::$cc = get_option( self::COLOR, '0000000' );
			add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 0 );

			if ( self::$active == '1' ) {
				// reset the image_sizes
				add_image_size( 'gbs_deal_thumbnail', 656, 399, false );
				add_image_size( 'gbs_loop_thumb', 208, 120, true );
				add_image_size( 'gbs_widget_thumb', 60, 60, true );
				add_image_size( 'gbs_merchant_loop', 150, null, true );
				add_image_size( 'gbs_merchant_thumb', 255, null, false );
				add_image_size( 'gbs_voucher_thumb', 255, 220, false );
				add_image_size( 'merchant_post_thumb', 160, 100, true );
				add_image_size( 'gbs_700x400', 700, 400, false );
				add_image_size( 'gbs_300x180', 300, 180, true );
				add_image_size( 'gbs_250x110', 250, 110, true );
				add_image_size( 'gbs_150w', 150, null, true );
				add_image_size( 'gbs_100x100', 100, 100, false );
				add_image_size( 'gbs_200x150', 200, 150, false );
				add_image_size( 'gbs_160x100', 160, 100, true );
				// Do the work
				add_filter( 'image_downsize', array( get_class(), 'filter_image_downsize' ), 10, 3 );
			}
		}

		public static function init() {
			self::get_instance();
			if ( version_compare( get_bloginfo( 'version' ), '3.2.99', '>=' ) ) { // 3.3. only
				add_action( 'load-group-buying_page_group-buying/theme_options', array( get_class(), 'options_help_section' ), 45 );
			}

		}

		public static function options_help_section() {
			$screen = get_current_screen();
			$screen->add_help_tab( array(
					'id'      => 'theme-options-thumbs', // This should be unique for the screen.
					'title'   => self::__( 'Advanced Thumbnails' ),
					'content' =>
					'<p><strong>' . self::__( 'Advanced Thumbnails?' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( 'The WordPress cropping functionality is rather limited, GBS includes TimThumb to allow advanced cropping and resizing. Documentation for the settings below and how to setup your server for caching can be found <a href="%s">here</a>. Warning: some hosts will not allow this functionality and some additional (unsupported) configuration may be required.' ), Group_Buying_Advanced_Thumbs::$documentation ) . '</p>'
				) );
		}

		public function filter_image_downsize( $bool, $id, $size ) {
			global $_wp_additional_image_sizes;

			if ( is_array( $_wp_additional_image_sizes ) && ( is_array( $size ) || array_key_exists( $size, $_wp_additional_image_sizes ) ) ) {

				$src = wp_get_attachment_image_src( $id, 'full' );
				$src = $src[0];

				if ( is_array( $size ) ) {
					$w = $size[0];
					$h = $size[1];
				} else {
					$w = $_wp_additional_image_sizes[$size]['width'];
					$h = $_wp_additional_image_sizes[$size]['height'];
				}

				$url = add_query_arg(
					array(
						'src' => $src,
						'w' => $w,
						'h' => $h,
						'zc' => self::$zc,
						's' => self::$sharpen,
						'a' => self::$align,
						'q' => self::$quality,
						'cc' => self::$cc,
					),
					get_bloginfo( 'template_url' ) . '/gbs-addons/advanced-thumbnail/timthumb.php' );

				return array( $url, $w, $h, false );
			}
		}

		public static function register_settings_fields() {
			$page = parent::$theme_settings_page;
			$section = 'gb_theme_thumbs';
			add_settings_section( $section, self::__( 'Thumbnails' ), array( get_class(), 'display_section' ), $page );
			register_setting( $page, self::ACTIVATED );
			register_setting( $page, self::QUALITY );
			register_setting( $page, self::ZC );
			register_setting( $page, self::ALIGN );
			register_setting( $page, self::SHARPEN );
			register_setting( $page, self::COLOR );
			add_settings_field( self::ACTIVATED, self::__( 'TimThumb Resizing' ), array( get_class(), 'display_option' ), $page, $section );
			add_settings_field( self::QUALITY, self::__( 'Resize Quality' ), array( get_class(), 'display_option_quality' ), $page, $section );
			add_settings_field( self::ZC, self::__( 'Zoom Crop' ), array( get_class(), 'display_option_zc' ), $page, $section );
			add_settings_field( self::ALIGN, self::__( 'Crop Alignment' ), array( get_class(), 'display_option_align' ), $page, $section );
			add_settings_field( self::SHARPEN, self::__( 'Sharpen' ), array( get_class(), 'display_option_sharpen' ), $page, $section );
			add_settings_field( self::COLOR, self::__( 'Crop Background Color' ), array( get_class(), 'display_option_background_color' ), $page, $section );
		}

		public static function display_section() {
			printf( self::__( 'Activate TimThumb for Thumbnail resizing and adjust quality, crop alignment and whether to sharpen the resized image. Documentation for the settings below and how to setup your server for caching can be found <a href="%s" target="_blank">here</a>.' ), self::$documentation );
		}

		public static function display_option() {
			echo '<label name="'.self::ACTIVATED.'"><input type="checkbox" value="1" name="'.self::ACTIVATED.'" '.checked( '1', self::$active, false ).' /> '.self::__( 'Enabling this will allow your thumbnails to be cropped and resized by timbthumb (v.2.8) instead of the limited cropping WP provides.' ).'</label>';
		}

		public static function display_option_quality() {
			echo '<input type="text" maxlength="3" class="small-text" name="'.self::QUALITY.'" value="'.self::$quality.'" />';
		}

		public static function display_option_zc() {
			echo '<input type="text" maxlength="2" class="small-text" name="'.self::ZC.'" value="'.self::$zc.'" /><br/><span class="description">'.self::__( '1: Crop and resize to best fit the dimensions.<br/>2: Resize proportionally to fit entire image into specified dimensions, and add borders if required.<br/>3: Resize proportionally adjusting size of scaled image so there are no borders gaps' ).'</span>';
		}

		public static function display_option_align() {
			echo '<input type="text" maxlength="2" class="small-text" name="'.self::ALIGN.'" value="'.self::$align.'" /><br/><span class="description">'.self::__( 'Leaving this blank or using a "0" will force the alignment to the center. More info found <a href="http://www.binarymoon.co.uk/demo/timthumb-cropping/">here</a>.' ).'</span>';
		}

		public static function display_option_sharpen() {
			echo '<input type="text" maxlength="1" class="small-text" name="'.self::SHARPEN.'" value="'.self::$sharpen.'" />';
		}

		public static function display_option_background_color() {
			echo '#<input id="background_color" type="text" maxlength="6" class="color_picker" name="'.self::COLOR.'" value="'.self::$cc.'" style="width:5em;"/>';
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

	}
}
add_action( 'init', array( 'Group_Buying_Advanced_Thumbs', 'init' )  );
