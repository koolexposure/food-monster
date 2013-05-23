<?php

/**
 * GBS Cart Template Functions
 *
 * @package GBS
 * @subpackage Cart
 * @category Template Tags
 */

/**
 * On cart Page
 * @see Group_Buying_Carts::is_cart_page()
 * @return boolean
 */
function gb_on_cart_page() {
	return Group_Buying_Carts::is_cart_page();
}

/**
 * Add to cart form
 * @see 
 * @param string $button_text Button Text
 * @return string              <form>
 */
function gb_add_to_cart_form( $button_text = 'Add to Cart' ) {
	echo apply_filters( 'gb_add_to_cart_form', gb_get_add_to_cart_form( null, $button_text ) );
}

/**
 * Return add to cart form
 * @param integer $deal_id     Deal ID
 * @param string  $button_text Add to Cart text
 * @return string               return <form>
 */
function gb_get_add_to_cart_form( $deal_id = 0,  $button_text = 'Add to Cart' ) {
	$_id = $deal_id;
	if ( !$deal_id ) {
		global $id;
		$_id = $id;
	}
	ob_start();
	Group_Buying_Carts::add_to_cart_form( $_id, $button_text );
	$form = ob_get_clean();
	return apply_filters( 'gb_get_add_to_cart_form', $form );
}

/**
 * Print Cart url
 * @see gb_get_cart_url()
 * @return string     echo
 */

function gb_add_to_cart_action() {
	echo apply_filters( 'gb_add_to_cart_action', gb_get_add_to_cart_action() );
}

/**
 * Return Cart url
 * @see gb_get_cart_url()
 * @return string
 */
function gb_get_add_to_cart_action() {
	return apply_filters( 'gb_get_add_to_cart_action', gb_get_cart_url() );
}

/**
 * Print Cart url
 * @see gb_get_cart_url()
 * @return string     echo
 */
function gb_cart_url() {
	echo apply_filters( 'gb_cart_url', gb_get_cart_url() );
}

/**
 * Return Cart url
 * @return string
 */
function gb_get_cart_url() {
	return apply_filters( 'gb_get_cart_url', Group_Buying_Carts::get_url() );
}

/**
 * Deals add to cart url
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_get_add_to_cart_url( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	return apply_filters( 'gb_get_add_to_cart_url', Group_Buying_Carts::add_to_cart_url( $post_id ), $post_id );
}

/**
 * print deals add to cart url
 * @see gb_get_add_to_cart_url()
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_add_to_cart_url( $post_id = 0 ) {
	echo apply_filters( 'gb_add_to_cart_url', gb_get_add_to_cart_url( $post_id ) );
}

/**
 * Get total of items in cart
 * @return integer
 */
function gb_get_cart_item_count() {
	$count = 0;
	$cart = Group_Buying_Cart::get_instance();
	if ( is_a( $cart, 'Group_Buying_Cart' ) ) {
		$count = $cart->item_count();
	}
	return apply_filters( 'gb_cart_item_count', $count );
}

/**
 * Print the total amount of items in a cart
 * @return integer
 */
function gb_cart_item_count() {
	echo apply_filters( 'gb_cart_item_count', gb_get_cart_item_count() );
}
