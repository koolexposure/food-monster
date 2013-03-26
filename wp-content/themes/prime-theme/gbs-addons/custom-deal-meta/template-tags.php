<?php

/**
 * Get the featured content for the deal
 *
 * @param integer $deal_id Deal ID
 * @return string           Content, shortcodes are executed
 */
if ( !function_exists( 'gb_get_featured_content' ) ) {
	function gb_get_featured_content( $deal_id = null ) {
		if ( null === $deal_id ) {
			global $post;
			$deal_id = $post->ID;
		}
		$content = Group_Buying_Featured_Content::get_featured_content( $deal_id );
		return apply_filters( 'gb_get_featured_content', do_shortcode( $content ) );
	}
}

/**
 * Print the featured content for a deal
 *
 * @param deal    $post_id Deal ID
 * @return string          content, shortcodes are executed
 */
if ( !function_exists( 'gb_featured_content' ) ) {
	function gb_featured_content( $deal_id = null ) {
		echo apply_filters( 'gb_featured_content', gb_get_featured_content( $deal_id ) );
	}
}

/**
 * Does the deal have featured content available
 *
 * @param integer $deal_id Deal ID
 * @return BOOL          TRUE|FALSE
 */
if ( !function_exists( 'gb_has_featured_content' ) ) {
	function gb_has_featured_content( $deal_id = null ) {
		$content = gb_get_featured_content( $deal_id );
		$has = ( $content == '' ) ? FALSE : TRUE ;
		return apply_filters( 'gb_has_featured_content', $has );

	}
}
