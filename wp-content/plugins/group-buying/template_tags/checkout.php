<?php

/**
 * GBS Checkout Template Functions
 *
 * @package GBS
 * @subpackage Checkout
 * @category Template Tags
 */

/**
 * Whether the current page is a checkout page.
 * @return bool 
 */
function gb_on_checkout_page() {
	if ( get_query_var( Group_Buying_Checkouts::CHECKOUT_QUERY_VAR ) ) {
		return TRUE;
	}
	return FALSE;
}

/**
 * Get the current checkout page 
 * @see Group_Buying_Checkouts::get_current_page()
 * @return string payment, review, or confirmation
 */
function gb_get_current_checkout_page( ) {
	$checkout = Group_Buying_Checkouts::get_instance();
	return $checkout->get_current_page();
}

/**
 * Get the current checkout page 
 * @see gb_get_current_checkout_page()
 * @return string
 */
function gb_current_checkout_page() {
	echo apply_filters( 'gb_current_checkout_page', gb_get_current_checkout_page() );
}

/**
 * Print checkout URL
 * @see gb_get_checkout_url()
 * @return string
 */
function gb_checkout_url() {
	echo apply_filters( 'gb_checkout_url', gb_get_checkout_url() );
}

/**
 * Get checkout URL
 * @see Group_Buying_Checkouts::get_url()
 * @return string
 */
function gb_get_checkout_url() {
	return apply_filters( 'gb_get_checkout_url', Group_Buying_Checkouts::get_url() );
}

/**
 * Ability to add an item directly to cart and send user to checkout bypassing the cart page.
 * @param integer $post_id $post->ID
 * @return string           
 */
function gb_get_add_to_checkout_url( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	return apply_filters( 'gb_get_add_to_checkout_url', Group_Buying_Carts::add_to_cart_url( $post_id, trailingslashit( get_option( Group_Buying_Checkouts::CHECKOUT_PATH_OPTION, 'checkout' ) ) ) );
}

/**
 * Print url to add an item directly to cart and send user to checkout bypassing the cart page.
 * @return string
 */
function gb_add_to_checkout_url( $post_id = 0 ) {
	echo apply_filters( 'gb_add_to_checkout_url', gb_get_add_to_checkout_url( $post_id ) );
}