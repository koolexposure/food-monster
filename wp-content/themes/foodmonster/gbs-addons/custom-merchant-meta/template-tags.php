<?php

/**
 * Get the merchant content for the deal
 *
 * @param integer $deal_id Deal ID
 * @return string           Content, shortcodes are executed
 */
if ( !function_exists( 'gb_get_merchant_content' ) ) {
	function gb_get_merchant_content( $merchant_id = null ) {
		if ( null === $merchant_id ) {
			global $post;
			$merchant_id = $post->ID;
		}
		$content = Group_Buying_Merchant_Content::get_merchant_content( $merchant_id );
		return apply_filters( 'gb_get_merchant_content', do_shortcode( $content ) );
	}
}

/**
 * Print the merchant content for a deal
 *
 * @param deal    $post_id Deal ID
 * @return string          content, shortcodes are executed
 */
if ( !function_exists( 'gb_merchant_content' ) ) {
	function gb_merchant_content( $merchant_id = null ) {
		echo apply_filters( 'gb_merchant_content', gb_get_merchant_content( $merchant_id ) );
	}
}

/**
 * Does the deal have merchant content available
 *
 * @param integer $deal_id Deal ID
 * @return BOOL          TRUE|FALSE
 */
if ( !function_exists( 'gb_has_merchant_content' ) ) {
	function gb_has_merchant_content( $merchant_id = null ) {
		$content = gb_get_merchant_content( $merchant_id );
		$has = ( $content == '' ) ? FALSE : TRUE ;
		return apply_filters( 'gb_has_merchant_content', $has );

	}
	
}
