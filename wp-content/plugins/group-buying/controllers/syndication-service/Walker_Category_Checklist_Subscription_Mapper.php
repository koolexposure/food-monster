<?php

/**
 * GBS Syndication Service walker class.
 *
 * @package GBS
 * @subpackage Syndication
 * @ignore
 */
class Walker_Category_Checklist_Subscription_Mapper extends Walker_Category_Checklist {
	function start_el( &$output, $category, $depth, $args ) {
		extract( $args );
		$name = $subscription_option;
		$map = wp_dropdown_categories( array(
				'show_option_none' => __( 'None' ),
				'hierarchical' => TRUE,
				'echo' => FALSE,
				'name' => $map_option.'['.$category->term_id.']',
				'selected' => isset( $mappings[$category->term_id] )?$mappings[$category->term_id]:0,
				'taxonomy' => $local_taxonomy,
				'orderby' => 'name',
				'hide_empty' => FALSE,
			) );
		$output .= "\n<li>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="'.$name.'[]" ' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters( 'the_category', $category->name ) ) . '</label> <span title="'.__( 'maps to' ).'">&#x21D2;</span> '.$map;
	}
}
