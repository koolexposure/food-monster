<?php
include 'metabox.php';

///////////////////
// REQUIRE FILES //
///////////////////

// Simple array of files to require
$required_files = '/gbs-addons/custom-merchant-meta/custom-merchant-meta.php';

$directory = get_stylesheet_directory();

require $directory . $required_files;


/**
 * This function file is loaded after the parent theme's function file. It's a great way to override functions, e.g. add_image_size sizes.
 *
 *
 */
function add_themescript(){
    if(!is_admin()){
    wp_enqueue_script('jquery');
    wp_enqueue_script('thickbox',null,array('jquery'));
    wp_enqueue_style('thickbox.css', '/'.WPINC.'/js/thickbox/thickbox.css', null, '1.0');
    }

}
add_action('init','add_themescript');
function post_type_slides() {
register_post_type(
                     'slides', 
                     array( 'public' => true,
					 		'publicly_queryable' => true,
							'hierarchical' => false,
							'menu_icon' => get_stylesheet_directory_uri() . '/images/slide.png',
                    		'labels'=>array(
    									'name' => _x('Slides', 'post type general name'),
    									'singular_name' => _x('Slide', 'post type singular name'),
    									'add_new' => _x('Add New', 'slide item'),
    									'add_new_item' => __('Add New slide item'),
    									'edit_item' => __('Edit slide item'),
    									'new_item' => __('New slide item'),
    									'view_item' => __('View slide item'),
    									'search_items' => __('Search slide item'),
    									'not_found' =>  __('No slide item found'),
    									'not_found_in_trash' => __('No slide items found in Trash'), 
    									'parent_item_colon' => ''
  										),							 
                             'show_ui' => true,
							 'menu_position'=>5,
						 'register_meta_box_cb' => 'mytheme_add_box',
                             'supports' => array(
							 			'title',
										'editor'
										)
							) 
					);
				} 
add_action('init', 'post_type_slides');
/*
function my_connection_types() {
	p2p_register_connection_type( array(
		'name' => 'pages_to_gb_merchant',
		'from' => 'page',
		'to' => 'gb_merchant'
	) );
}
add_action( 'wp_loaded', 'my_connection_types' );
*/	
/*	
	if ( ! function_exists('post_type_merchant_page') ) {
	// Register Custom Post Type
	function post_type_merchant_page() {
		$labels = array(
			'name'                => _x( 'Merchant Pages', 'Post Type General Name', 'text_domain' ),
			'singular_name'       => _x( 'Merchant Page', 'Post Type Singular Name', 'text_domain' ),
			'menu_name'           => __( 'Merchant Pages', 'text_domain' ),
			'parent_item_colon'   => __( 'Merchant:', 'text_domain' ),
			'all_items'           => __( 'All Merchant Pages', 'text_domain' ),
			'view_item'           => __( 'View Merchant Pages', 'text_domain' ),
			'add_new_item'        => __( 'Add Merchant Page', 'text_domain' ),
			'add_new'             => __( 'New Merchant Page', 'text_domain' ),
			'edit_item'           => __( 'Edit Merchant Page', 'text_domain' ),
			'update_item'         => __( 'Update Merchant Page', 'text_domain' ),
			'search_items'        => __( 'Search Merchant Page', 'text_domain' ),
			'not_found'           => __( 'No Merchant Page found', 'text_domain' ),
			'not_found_in_trash'  => __( 'No Merchant Pagefound in Trash', 'text_domain' ),
		);
		$rewrite = array(
			'slug'                => 'business/page',
			'with_front'          => false,
		);
		$args = array(
			'label'               => __( 'merchant_page', 'text_domain' ),
			'description'         => __( 'Merchant Page', 'text_domain' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes', 'post-formats', ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'hierarchical'        => true,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => '',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'query_var'           => 'merchant_page',
			'rewrite'             => $rewrite,
			'capability_type'     => 'page',
		);

		register_post_type( 'merchant_page', $args );
	}
	// Hook into the 'init' action
	add_action( 'wp_loaded', 'post_type_merchant_page', 0 );
	}
	*/
/*	
	function modify_merchant() {
	    if ( post_type_exists( 'gb_merchant' ) ) {

	        global $wp_post_types;
	        $wp_post_types['gb_merchant']->hierarchical = true;
	        $args = $wp_post_types['gb_merchant'];
	        add_post_type_support('gb_merchant','page-attributes');
	    }
	}
	add_action( 'wp_loaded', 'modify_merchant', 1 );		
add_action('add_meta_boxes',  'merchant_parent_meta_box');
function merchant_parent_meta_box() { 
	add_meta_box('gb_merchant-parent', 'Merchant', 'merchant_attributes_meta_box', 'merchant_page', 'side', 'high');}
function merchant_attributes_meta_box($post) {
 $post_type_object = get_post_type_object($post->post_type);
    if ( $post_type_object->hierarchical ) {
      $pages = wp_dropdown_pages(array('post_type' => 'gb_merchant', 'selected' => $post->post_parent, 'name' => 'parent_id', 'show_option_none' => __('(no parent)'), 'sort_column'=> 'menu_order, post_title', 'echo' => 0));
      if ( ! empty($pages) ) {
        echo $pages;
      } // end empty pages check
    } // end hierarchical check.
  }
*/
// Front end only, don't hack on the settings page
if ( ! is_admin() ) {
    // Hook in early to modify the menu
    // This is before the CSS "selected" classes are calculated
    add_filter( 'wp_get_nav_menu_items', 'replace_placeholder_nav_menu_item_with_latest_post', 10, 3 );
}
// Replaces a custom URL placeholder with the URL to the latest post
function replace_placeholder_nav_menu_item_with_latest_post( $items, $menu, $args ) {
    // Loop through the menu items looking for placeholder(s)
    foreach ( $items as $item ) {
 
        // Is this the placeholder we're looking for?
        if ( '#latest' != $item->url )
            continue;
 
        // Get the latest post
        $latestpost = get_posts( array(
            'numberposts' => 1, 'post_type' => 'gb_merchant',
        ) );
        if ( empty( $latestpost ) )
            continue;
        // Replace the placeholder with the real URL
        $item->url = get_permalink( $latestpost[0]->ID );
    }
    // Return the modified (or maybe unmodified) menu items array
    return $items;
}
?>