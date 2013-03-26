<?php

/**
 * GBS Gift Template Functions
 *
 * @package GBS
 * @subpackage Gift
 * @category Template Tags
 */

/**
 * Gift redemption URL
 * @see gb_get_gift_redemption_url()
 * @return string
 */
function gb_gift_redemption_url() {
	echo apply_filters( 'gb_gift_redemption_url', gb_get_gift_redemption_url() );
}

/**
 * Get the gift redemption url
 * @see Group_Buying_Gifts::get_url()
 * @return string
 */
function gb_get_gift_redemption_url() {
	$url = Group_Buying_Gifts::get_url();
	return apply_filters( 'gb_get_gift_redemption_url', $url );
}

/**
 * Send as gift url. Link user to checkout, bypassing cart and setting 'gifter' query string to check the "This is a gift" option on checkout.
 * @param integer|null $post_id Post ID to purchase as a gift.
 * @return string
 */
function gb_get_send_as_gift_url( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	return apply_filters( 'gb_get_send_as_gift_url', add_query_arg( array( 'gifter' => 1 ), gb_get_add_to_checkout_url() ) );
}
/**
 * Prints send as gift url
 * @see gb_get_send_as_gift_url()
 * @param integer|null $post_id Post ID
 * @return string          
 */
function gb_send_as_gift_url( $post_id = 0 ) {
	echo apply_filters( 'gb_send_as_gift_url', gb_get_send_as_gift_url( $post_id ) );
}
