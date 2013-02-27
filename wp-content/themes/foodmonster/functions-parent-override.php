<?php


include 'metabox.php';

/**
 * This function file is loaded after the parent theme's function file. It's a great way to override functions, e.g. add_image_size sizes.
 *
 *
 */


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




?>