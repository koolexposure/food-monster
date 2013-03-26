<?php 
do_action('gb_deals_view');
// Has option
$page_id = get_option('gb_nodeal_content'); 

if ( $page_id ) {
	// Get the page that will show the default content
	$post = get_page( $page_id ); 
	$content = apply_filters('the_content', $post->post_content); 
	print do_shortcode($content); 
} else {
	gb_e('There are currently no deals for this location.');
}