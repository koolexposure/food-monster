<?php

/**
 * Theme Constants
 */
define( 'GBS_THEME_NAME', 'Prime' );
define( 'GBS_THEME_SLUG', 'prime_theme' ); // theme slug for updater
define( 'GBS_THEME_VERSION', '2.2' );
define( 'GB_THEME_COMPAT_VERSION', '3.8' );
define( 'GB_THEME_CHILD_THEME', 'https://github.com/GroupBuyingSite/gbs-prime-child-theme/zipball/master' );

define( 'SS_BASE_URL', get_template_directory_uri() . '/' );

/**
 * Update options upon theme activation.
 */
if ( is_admin() && isset( $_GET['activated'] ) && $pagenow == 'themes.php' ) {
	update_option( 'gb_adv_thumbs', '1' ); // enable advanced thumbnailing
	update_option( 'gb_adv_thumbs_sc', '1' );
}

// confirm GBS is installed and activated
if ( !function_exists( 'group_buying_load' ) ) {
	if ( !is_admin() && $_SERVER['PHP_SELF'] != '/wp-login.php'  ) {
		wp_die( 'Please <a href="'.get_admin_url().'plugins.php">Activate</a> and <a href="'.get_admin_url().'admin.php?page=group-buying/gb_settings">Authorize</a> Group Buying' );
	}
	return;
}

// check version requirements
if ( !version_compare( Group_Buying::GB_VERSION, GB_THEME_COMPAT_VERSION, '>=' ) ) {
	if ( !is_admin() && $_SERVER['PHP_SELF'] != '/wp-login.php'  ) {
		wp_die( 'This theme requires GBS to be upgraded to at least version '. GB_THEME_COMPAT_VERSION );
	}
	return;
}

function gb_ptheme_current_version() { return GBS_THEME_VERSION; }

// Remove that pesky admin bar for users
if ( !is_admin() && !current_user_can( 'edit_posts' ) ) {
	show_admin_bar( false );
}

//////////////////
// Theme Styles //
//////////////////

function gbs_theme_register_styles() {

	if ( !is_admin() ) {
		wp_register_style( 'template_style', get_template_directory_uri().'/style.css', null , gb_ptheme_current_version(), 'screen' );
		wp_register_style( 'media_queries_style', get_template_directory_uri().'/style-media-queries.css', null, gb_ptheme_current_version() );
		// Register Child Theme Style automatically.
		if ( TEMPLATEPATH != STYLESHEETPATH ) {
			wp_register_style( 'gbs_child_style', get_bloginfo( 'stylesheet_url' ), null, gb_ptheme_current_version() );
		}
	}

}
add_action( 'init', 'gbs_theme_register_styles' );

function gbs_theme_enqueue_styles() {

	if ( !is_admin() ) {
		wp_enqueue_style( 'template_style' );
		wp_enqueue_style( 'media_queries_style' );
		if ( TEMPLATEPATH != STYLESHEETPATH  ) {
			wp_enqueue_style( 'gbs_child_style' );
		}
	}

}
add_action( 'wp_enqueue_scripts', 'gbs_theme_enqueue_styles' );

///////////////////
// Theme Scripts //
///////////////////

function gbs_theme_register_scripts() {

	if ( !is_admin() ) {
		wp_register_script( 'gbs-jquery-template', get_template_directory_uri().'/js/jquery.template.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs' ), gb_ptheme_current_version(), false );
		wp_register_script( 'gbs-ul-to-select', get_template_directory_uri() . '/js/jquery.mobilemenu.js', array( 'jquery' ) , gb_ptheme_current_version(), true );
	}

}
add_action( 'init', 'gbs_theme_register_scripts' );

function gbs_theme_enqueue_scripts() {

	if ( !is_admin() ) {

		// WP Core Scripts
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );

		// GBS Scripts
		wp_enqueue_script( 'gbs-jquery-template' );
		wp_enqueue_script( 'gbs-ul-to-select' );
		
		// For threaded comments
		if ( is_singular() && get_option( 'thread_comments' ) )
			wp_enqueue_script( 'comment-reply' );

		// Localization
		$gbs_template_jquery_translation_array = array( 'used' => gb__( 'Used ' ) );
		wp_localize_script( 'gbs-jquery-template', 'gbs_js_object', $gbs_template_jquery_translation_array );
	}
}
add_action( 'wp_enqueue_scripts', 'gbs_theme_enqueue_scripts' );

function gb_head_script_variables() {
	?>
	<script type="text/javascript">
		var gb_ajax_url = '<?php echo admin_url() ?>/admin-ajax.php';
		var gb_ajax_gif = '<img src="<?php echo get_admin_url() ?>/images/wpspin_light.gif" id="ajax_gif">';
	</script>
	<?php
}
add_action( 'wp_head', 'gb_head_script_variables' );

///////////////////
// Theme Support //
///////////////////

// This theme uses post thumbnails
add_theme_support( 'post-thumbnails' );
add_image_size( 'gbs_60x60', 60, 60, true );
add_image_size( 'gbs_208x120', 208, 120, true );
add_image_size( 'gbs_700', 700, 1500, true ); // Deal page size
add_image_size( 'gbs_300x180', 300, 180, true );
add_image_size( 'gbs_250x110', 250, 110, true );
add_image_size( 'gbs_150w', 150, 9999 );
add_image_size( 'gbs_100x100', 100, 100, true );
add_image_size( 'gbs_200x150', 200, 150, true );
add_image_size( 'gbs_160x100', 160, 100, true );

// Add default posts and comments RSS feed links to head
add_theme_support( 'automatic-feed-links' );

// This theme uses wp_nav_menu() in one location.
register_nav_menus( array(
		'header' => gb__( 'Header Menu' )
	) );

if ( !function_exists( 'custom-background' ) ) {
	add_theme_support( 'custom-background' );
}

// This theme allows users to set a custom background
add_theme_support( 'custom-background' );

// Make theme available for translation
// Translations can be filed in the /lang/ directory
load_theme_textdomain( Group_Buying::TEXT_DOMAIN, trailingslashit( get_template_directory() ) . 'lang' );

$locale = get_locale();
$child_locale_file = trailingslashit( STYLESHEETPATH ) . 'lang/'.Group_Buying::TEXT_DOMAIN.'-'.$locale.'.mo';
$locale_file = trailingslashit( get_template_directory() ) . 'lang/'.Group_Buying::TEXT_DOMAIN.'-'.$locale.'.mo';
if ( is_readable( $child_locale_file ) ) {
	load_textdomain( Group_Buying::TEXT_DOMAIN, $child_locale_file );
} elseif ( is_readable( $locale_file ) ) {
	load_textdomain( Group_Buying::TEXT_DOMAIN, $locale_file );
}

///////////////////
// REQUIRE FILES //
///////////////////

// Simple array of files to require
$required_files = array(
	// Load translator before anything else
	'/gbs-addons/translate/translator.php',
	// Template sidebars
	'/functions/sidebars.php',
	// Template hooks/actions/filters
	'/functions/hooks.php',
	'/functions/filters.php',
	// Template Tags
	'/functions/template-tags.php',
	// GBS Add-Ons// GBS Add-Ons
	'/gbs-addons/options/theme-options.php',
	'/gbs-addons/advanced-thumbnail/advanced-thumbnails.php',
	'/gbs-addons/facebook/facebook.class.php',
	'/gbs-addons/share/share.class.php',
	'/gbs-addons/subscription/subscriptions.php',
	'/gbs-addons/custom-deal-meta/custom-deal-meta.php',
	'/gbs-addons/updater/updater.php',
);
// Loop through the files checking if they exist in the child theme first.
foreach ( $required_files as $file ) {
	$directory = get_template_directory();
	// If a child theme is being used change the directory to that theme if the files exists.
	if ( get_template_directory() != get_stylesheet_directory() && file_exists( get_stylesheet_directory() . $file ) ) {
		$directory = get_stylesheet_directory();
	}
	require_once $directory . $file;
}

// include functions-parent-override.php from the child theme if it exists. the child theme functions.php is loaded before the template functions.php so we need a place to load overrides afterwards (if necessary)
if ( get_template_directory() != get_stylesheet_directory() && file_exists( get_stylesheet_directory() .'/functions-parent-override.php' )) {
	include_once get_stylesheet_directory() .'/functions-parent-override.php';
}
