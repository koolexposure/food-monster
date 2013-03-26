<?php

/**
 * GBS Payment Template Functions
 *
 * @package GBS
 * @subpackage Payment
 * @category Template Tags
 */

/**
 * Get Purchase Post Type
 * @return string
 */
function gb_get_purchase_post_type() {
	return Group_Buying_Purchase::POST_TYPE;
}

/**
 * Get the Payment Post Type
 * @return string
 */
function gb_get_payment_post_type() {
	return Group_Buying_Payment::POST_TYPE;
}

/**
 * Print the currency symbol option
 * @see gb_get_currency_symbol()
 * @return string
 */
function gb_currency_symbol() {
	echo apply_filters( 'gb_currency_symbol', gb_get_currency_symbol() );
}

/**
 * Get the currency symbol, filtering out the location string(%)
 * @param boolean $filter_location filter out the location string from return
 * @return return                   
 */
function gb_get_currency_symbol( $filter_location = TRUE ) {
	$string = Group_Buying_Payment_Processors::get_currency_symbol();
	if ( $filter_location && strstr( $string, '%' ) ) {
		$string = str_replace( '%', '', $string );
	}
	return apply_filters( 'gb_get_currency_symbol', $string );
}

/**
 * Print an amount as formatted money. 
 * @see gb_get_formatted_money()
 * @param integer $amount amount to convert to money format 
 * @return string
 */
function gb_formatted_money( $amount, $decimals = TRUE ) {
	echo apply_filters( 'gb_formatted_money', gb_get_formatted_money( $amount, $decimals ), $amount );
}

/**
 * Return an amount as formatted money. Place symbol based on location.
 * @param integer $amount amount to convert to money format
 * @return string         
 */
function gb_get_formatted_money( $amount, $decimals = TRUE ) {
	$orig_amount = $amount;
	$symbol = gb_get_currency_symbol( FALSE );
	$number = number_format( floatval( $amount ), 2 );
	if ( strstr( $symbol, '%' ) ) {
		$string = str_replace( '%', $number, $symbol );
	} else {
		$string = $symbol . $number;
	}
	if ( $number < 0 ) {
		$string = '-'.str_replace( '-', '', $string );
	}
	if ( !$decimals ) {
		$string = str_replace('.00','', $string);
	}
	return apply_filters( 'gb_get_formatted_money', $string, $orig_amount );
}
