<?php

/**
 * GBS Attributes Template Functions
 *
 * @package GBS
 * @subpackage Attribute
 * @category Template Tags
 */

/**
 * Does the deal have attributes
 * @param  integer $post_id Post ID
 * @return boolean          
 */
function gb_deal_has_attributes( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	if ( gb_get_deal_post_type() == get_post_type( $post_id ) ) {
		$attributes = Group_Buying_Attribute::get_attributes( $post_id, 'id' );
		if ( !empty( $attributes ) ) {
			return TRUE;
		}
	}
	return;
}

/**
 * Get the voucher's title (with attribute titles appeneded) based on the voucher id.
 * @param  integer $voucher_id Post ID of Voucher
 * @param  array $ids        Optional: Array of attribute ids
 * @return string             
 */
function gb_get_attribute_title_by_voucher_id( $voucher_id, $ids = null ) {
	if ( is_null( $ids ) ) {
		$ids = Group_Buying_Attributes::get_vouchers_attribute_id( $voucher_id );
	}
	if ( empty( $ids ) ) {
		return;
	}
	if ( is_array( $ids ) ) {
		$labels = array();
		foreach ( $ids as $id ) {
			$labels[] = get_the_title( $id );
		}
		$title = implode( ', ', $labels );
	} else { // 3.1.6 no longer returning arrays
		$title = get_the_title( $ids );
	}
	return apply_filters( 'gb_get_attribute_title_by_voucher_id', $title, $voucher_id );
}

/**
 * Print the voucher's title (with attribute titles appeneded) based on the voucher id.
 * @see  gb_get_attribute_title_by_voucher_id()
 * @param  integer $voucher_id Post ID of Voucher
 * @return string         echo    
 */
function gb_attribute_title_by_voucher_id( $voucher_id ) {
	echo apply_filters( 'gb_attribute_title_by_voucher_id', gb_get_attribute_title_by_voucher_id( $voucher_id ) );
}
