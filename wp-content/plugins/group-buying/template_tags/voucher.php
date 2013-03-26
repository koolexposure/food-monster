<?php

/**
 * GBS Voucher Template Functions
 *
 * @package GBS
 * @subpackage Voucher
 * @category Template Tags
 */

///////////////
// Utilities //
///////////////

/**
 * Get the voucher post type
 * @return string
 */
function gb_get_voucher_post_type() {
	return Group_Buying_Voucher::POST_TYPE;
}

/**
 * Currently viewing the vouchers loop/archive
 * @return boolean
 */
function gb_on_voucher_page() {
	if ( Group_Buying_Voucher::is_voucher_query() && !is_single() ) {
		return true;
	}
	return;
}

/**
 * Currently viewing the active vouchers loop
 * @return boolean
 */
function gb_on_voucher_active_page() {
	if ( get_query_var( Group_Buying_Vouchers::FILTER_QUERY_VAR ) == Group_Buying_Vouchers::FILTER_ACTIVE_QUERY_VAR ) {
		return true;
	}
	return;
}

/**
 * Currently viewing the expired vouchers loop
 * @return boolean
 */
function gb_on_voucher_expired_page() {
	if ( get_query_var( Group_Buying_Vouchers::FILTER_QUERY_VAR ) == Group_Buying_Vouchers::FILTER_EXPIRED_QUERY_VAR ) {
		return true;
	}
	return;
}

/**
 * Currently viewing the used vouchers loop
 * @return boolean
 */
function gb_on_voucher_used_page() {
	if ( get_query_var( Group_Buying_Vouchers::FILTER_QUERY_VAR ) == Group_Buying_Vouchers::FILTER_USED_QUERY_VAR ) {
		return true;
	}
	return;
}

/**
 * Get all vouchers from a single purchase id
 * @param  integer $purchase_id 
 * @return array               
 */
function gb_get_vouchers_by_purchase_id( $purchase_id = 0 ) {
	return Group_Buying_Voucher::get_vouchers_for_purchase( $purchase_id );
}

//////////
// URLS //
//////////

/**
 * Print Vouchers URL
 * @see gb_get_vouchers_url()
 * @return string
 */
function gb_voucher_url() {
	echo  apply_filters( 'gb_voucher_url', gb_get_vouchers_url() );
}

/**
 * Print Vouchers URL
 * @see gb_get_vouchers_url()
 * @return string
 */
function gb_vouchers_url() {
	echo  apply_filters( 'gb_voucher_url', gb_get_vouchers_url() );
}

/**
 * Get Vouchers URL
 * @see Group_Buying_Vouchers::get_url()
 * @return string
 */
function gb_get_vouchers_url() {
	return apply_filters( 'gb_get_vouchers_url', Group_Buying_Vouchers::get_url() );
}

/**
 * Print used Vouchers URL
 * @see gb_get_voucher_used_url()
 * @return string
 */
function gb_voucher_used_url() {
	echo  apply_filters( 'gb_voucher_used_url', gb_get_voucher_used_url() );
}

/**
 * Get used Vouchers URL
 * @see Group_Buying_Vouchers::get_used_url()
 * @return string
 */
function gb_get_voucher_used_url() {
	return apply_filters( 'gb_get_voucher_used_url', Group_Buying_Vouchers::get_used_url() );
}

/**
 * Print expired Vouchers URL
 * @see gb_get_voucher_expired_url()
 * @return string
 */
function gb_voucher_expired_url() {
	echo  apply_filters( 'gb_voucher_expired_url', gb_get_voucher_expired_url() );
}

/**
 * Get expired Vouchers URL
 * @see Group_Buying_Vouchers::get_expired_url()
 * @return string
 */
function gb_get_voucher_expired_url() {
	return apply_filters( 'gb_get_voucher_expired_url', Group_Buying_Vouchers::get_expired_url() );
}

/**
 * Print active Vouchers URL
 * @see gb_get_voucher_active_url()
 * @return string
 */
function gb_voucher_active_url() {
	echo  apply_filters( 'gb_voucher_active_url', gb_get_voucher_active_url() );
}

/**
 * Get active Vouchers URL
 * @see Group_Buying_Vouchers::get_active_url()
 * @return string
 */
function gb_get_voucher_active_url() {
	return apply_filters( 'gb_get_voucher_active_url', Group_Buying_Vouchers::get_active_url() );
}

/**
 * Print the Voucher link; filtered for add-on use.
 * @param  integer $voucher_id Voucher ID
 * @return string              html
 */
function gb_voucher_link( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	$link = '<a href="'.gb_get_voucher_permalink( $voucher_id ).'" title="'.gb__( 'Download Voucher' ).'" class="alt_button voucher_download">'.gb__( 'Download' ).'</a>';
	echo apply_filters( 'gb_voucher_link', $link, $voucher_id );
}

/**
 * Print a vouchers permalink/url
 * @param  integer $voucher_id Voucher ID
 * @param  bool $temp_access Creates a nonce (that will expire) to allow the voucher to be accessed directly via a url. Be careful to secure this link behind some other validation.
 * @return string              
 */
function gb_voucher_permalink( $voucher_id = 0, $temp_access = FALSE ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	$voucher_url = gb_get_voucher_permalink( $voucher_id );
	if ( $temp_access ) {
		$voucher_url = add_query_arg( array( '_wpnonce' => gb_create_temp_voucher_access_nonce( Group_Buying_Voucher::TEMP_ACCESS_KEY . $voucher_id ) ), $voucher_url ); 
	}
	echo apply_filters( 'gb_voucher_permalink', $voucher_url, $voucher_id, $temp_access );
}
/**
 * Creates a random, one time use token. Copy of wp_create_nonce but handles guest checkout.
 * @see wp_create_nonce
 *
 * @since 2.0.3
 *
 * @param string|int $action Scalar value to add context to the nonce.
 * @return string The one use form token
 */
function gb_create_temp_voucher_access_nonce( $action = -1 ) {
	$uid = 0;
	if ( !gb_is_user_guest_purchaser() ) {
		$user = wp_get_current_user();
		$uid = (int) $user->ID;
	}
	$i = wp_nonce_tick();
	return apply_filters( 'gb_create_temp_voucher_access_nonce', substr(wp_hash($i . $action . $uid, 'nonce'), -12, 10) );
}

/**
 * Return a vouchers permalink/url
 * @param  integer $voucher_id Voucher ID
 * @return string              
 */
function gb_get_voucher_permalink( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	return apply_filters( 'gb_get_voucher_permalink', get_permalink( $voucher_id ), $voucher_id );
}

/**
 * Does the voucher have a preview available. Enabled by editor when creating deal.
 * @param  integer $deal_id Deal ID or Voucher ID
 * @return boolean          
 */
function gb_voucher_preview_available( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	// If a voucher id is passed get the deal id
	if ( get_post_type( $post_id ) === gb_get_voucher_post_type() ) {
		$post_id = gb_get_vouchers_deal_id( $post_id );
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_voucher_preview_available', Group_Buying_Deals_Preview::has_key( $deal ) );
}

/**
 * Get the voucher preview url
 * @param  integer $post_id Voucher ID or Deal ID
 * @return string           
 */
function gb_get_voucher_preview_link( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	// If a voucher id is passed get the deal id
	if ( get_post_type( $post_id ) === gb_get_voucher_post_type() ) {
		$post_id = gb_get_vouchers_deal_id( $post_id );
	}
	if ( gb_voucher_preview_available( $post_id ) ) {
		$deal = Group_Buying_Deal::get_instance( $post_id );
		return apply_filters( 'gb_get_voucher_preview_link', Group_Buying_Deals_Preview::get_voucher_preview_link( $deal ) );
	}
	return;
}

/**
 * Print the voucher preview url
 * @param  integer $post_id Voucher ID or Deal ID
 * @return string           
 */
function gb_voucher_preview_link( $post_id = 0 ) {
	echo apply_filters( 'gb_voucher_preview_link', gb_get_voucher_preview_link( $post_id ) );
}


///////////
// Misc. //
///////////

function gb_is_voucher_active( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	return apply_filters( 'gb_is_voucher_active', $voucher->is_active(), $voucher_id );
}

/**
 * Return an array of deal ids associated with the current users purchased vouchers
 * @param  string $status expired, active, used
 * @return array         
 */
function gb_get_purchased_deals_with_vouchers( $status = NULL ) {
	return apply_filters( 'gb_get_purchased_deals_with_vouchers', Group_Buying_Vouchers::get_deal_ids( $status ) );
}

/**
 * Print voucher archive page title
 * @see gb_get_voucher_page_title()
 * @return string
 */
function gb_voucher_page_title() {
	echo gb_get_voucher_page_title();
}

/**
 * Get the voucher page title based on what's being filtered
 * @return string
 */
function gb_get_voucher_page_title() {
	if ( gb_on_voucher_used_page() ) {
		$title = gb__( 'Used Purchases' );
	} elseif ( gb_on_voucher_expired_page() ) {
		$title = gb__( 'Expired Purchases' );
	} elseif ( gb_on_voucher_active_page() ) {
		$title = gb__( 'Active Purchases' );
	} else {
		$title = gb__( 'All Purchases' );
	}
	return $title;
}

/**
 * Return the deal object associated with a voucher
 * @param  integer $voucher_id Voucher ID
 * @return object              Group_Buying_Deal
 */
function gb_get_vouchers_deal( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	if ( !is_a( $voucher, 'Group_Buying_Voucher' ) ) {
		return FALSE;
	}
	$deal = $voucher->get_deal();
	if ( !is_a( $deal, 'Group_Buying_Deal' ) ) {
		return FALSE;
	}
	return apply_filters( 'gb_get_voucher_deal', $deal, $voucher_id );
}

/**
 * Return the deal ID associated with a voucher
 * @param  integer $voucher_id Voucher ID
 * @return integer             
 */
function gb_get_vouchers_deal_id( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	if ( !is_a( $voucher, 'Group_Buying_Voucher' ) ) {
		return FALSE;
	}
	$deal = $voucher->get_deal();
	if ( !is_a( $deal, 'Group_Buying_Deal' ) ) {
		return FALSE;
	}
	return apply_filters( 'gb_get_voucher_deal_id', $deal->get_ID(), $voucher_id );
}

/**
 * Get a vouchers claimed date
 * @param  integer $voucher_id Voucher ID
 * @return integer|void        timestamp
 */
function gb_get_voucher_claimed( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	if ( !$voucher_id ) {
		return FALSE;
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	if ( !is_a( $voucher, 'Group_Buying_Voucher' ) ) {
		return FALSE;
	}
	return apply_filters( 'gb_get_voucher_claimed', $voucher->get_claimed_date(), $voucher_id );
}

/**
 * Check if a voucher is claimed
 * @param  integer $voucher_id Voucher ID
 * @return boolean
 */
function gb_is_voucher_claimed( $voucher_id = 0 ) {
	if ( gb_get_voucher_claimed( $voucher_id ) ) {
		$return = TRUE;
	} else {
		$return = FALSE;
	}
	return apply_filters( 'gb_has_voucher_claimed', $return, $voucher_id );
}

///////////////////////
// Content / Display //
///////////////////////

/**
 * Print the vouchers logo
 * @see gb_get_voucher_logo()
 * @param  integer $voucher_id Voucher ID
 * @return string             
 */
function gb_voucher_logo( $voucher_id = 0 ) {
	echo apply_filters( 'gb_voucher_logo', gb_get_voucher_logo( $voucher_id ), $voucher_id );
}

/**
 * Get the vouchers logo
 * @param  integer $voucher_id Voucher ID
 * @return string              
 */
function gb_get_voucher_logo( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	if ( !$voucher_id ) {
		return '';
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	return apply_filters( 'gb_get_voucher_logo', $voucher->get_logo(), $voucher_id );
}

/**
 * Print the vouchers logo image
 * @see gb_get_voucher_logo()
 * @param  integer $voucher_id Voucher ID
 * @return string      HTML       
 */
function gb_voucher_logo_image( $voucher_id = 0 ) {
	echo apply_filters( 'gb_voucher_logo_image', gb_get_voucher_logo_image( $voucher_id ), $voucher_id );
}

/**
 * Get the vouchers logo image
 * @param  integer $voucher_id Voucher ID
 * @return string      HTML        
 */
function gb_get_voucher_logo_image( $voucher_id = 0 ) {
	$url = gb_get_voucher_logo( $voucher_id );
	$html = sprintf( '<img src="%s" alt="" />', $url );
	return apply_filters( 'gb_get_voucher_logo_image', $html, $voucher_id );
}

/**
 * Does the voucher have a logo
 * @see gb_get_voucher_logo()
 * @param  integer $voucher_id Voucher ID
 * @return string             
 */
function gb_has_voucher_logo( $voucher_id = 0 ) {
	$logo = gb_get_voucher_logo( $voucher_id );
	return strlen( $logo ) > 0;
}

/**
 * Print the vouchers code
 * @see gb_get_voucher_code()
 * @param  integer $voucher_id Voucher ID
 * @return string             
 */
function gb_voucher_code( $voucher_id = 0 ) {
	echo apply_filters( 'gb_voucher_code', gb_get_voucher_code( $voucher_id ), $voucher_id );
}

/**
 * Get the vouchers code
 * @param  integer $voucher_id Voucher ID
 * @return string              
 */
function gb_get_voucher_code( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	if ( !$voucher_id ) {
		return '';
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	return apply_filters( 'gb_get_voucher_code', $voucher->get_serial_number(), $voucher_id );
}

/**
 * Print the vouchers security code
 * @see gb_get_voucher_security_code()
 * @param  integer $voucher_id Voucher ID
 * @return string             
 */
function gb_voucher_security_code( $voucher_id = 0 ) {
	echo apply_filters( 'gb_voucher_security_code', gb_get_voucher_security_code( $voucher_id ), $voucher_id );
}

/**
 * Get the vouchers security code
 * @param  integer $voucher_id Voucher ID
 * @return string              
 */
function gb_get_voucher_security_code( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	if ( !$voucher_id ) {
		return '';
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	return apply_filters( 'gb_get_voucher_security_code', $voucher->get_security_code(), $voucher_id );
}

/**
 * Print the vouchers fine print
 * @see gb_get_voucher_fine_print()
 * @param  integer $voucher_id Voucher ID
 * @return string             
 */
function gb_voucher_fine_print( $voucher_id = 0 ) {
	echo apply_filters( 'gb_voucher_fine_print', gb_get_voucher_fine_print( $voucher_id ), $voucher_id );
}

/**
 * Get the vouchers fine print
 * @param  integer $voucher_id Voucher ID
 * @return string              
 */
function gb_get_voucher_fine_print( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	if ( !$voucher_id ) {
		return '';
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	return apply_filters( 'gb_get_voucher_fine_print', $voucher->get_fine_print(), $voucher_id );
}

/**
 * Print the vouchers map
 * @see gb_get_voucher_map()
 * @param  integer $voucher_id Voucher ID
 * @return string             
 */
function gb_voucher_map( $voucher_id = 0 ) {
	echo apply_filters( 'gb_voucher_map', gb_get_voucher_map( $voucher_id ), $voucher_id );
}

/**
 * Get the vouchers map
 * @param  integer $voucher_id Voucher ID
 * @return string              
 */
function gb_get_voucher_map( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	if ( !$voucher_id ) {
		return '';
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	return apply_filters( 'gb_get_voucher_map', $voucher->get_map(), $voucher_id );
}

/**
 * Print the vouchers instructions
 * @see gb_get_voucher_usage_instructions()
 * @param  integer $voucher_id Voucher ID
 * @return string             
 */
function gb_voucher_usage_instructions( $voucher_id = 0 ) {
	echo apply_filters( 'gb_voucher_usage_instructions', gb_get_voucher_usage_instructions( $voucher_id ), $voucher_id );
}

/**
 * Get the vouchers instructions
 * @param  integer $voucher_id Voucher ID
 * @return string              
 */
function gb_get_voucher_usage_instructions( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	if ( !$voucher_id ) {
		return '';
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	return apply_filters( 'gb_get_voucher_usage_instructions', $voucher->get_usage_instructions(), $voucher_id );
}

/**
 * Print the vouchers locations
 * @see gb_get_voucher_usage_instructions()
 * @param  integer $voucher_id Voucher ID
 * @return string          UL list   
 */
function gb_voucher_locations( $voucher_id = 0 ) {
	$locations = gb_get_voucher_locations( $voucher_id );
	$out = '';
	if ( !empty( $locations ) ) {
		$out .= '<ul class="voucher_locations"><li>';
		$out .= implode( '</li><li>', $locations );
		$out .= '</li></ul>';
	}
	echo apply_filters( 'gb_voucher_locations', $out, $voucher_id );
}

/**
 * Get the vouchers locations
 * @param  integer $voucher_id Voucher ID
 * @return string              
 */
function gb_get_voucher_locations( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	if ( !$voucher_id ) {
		return '';
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	return array_filter( apply_filters( 'gb_get_voucher_locations', $voucher->get_locations(), $voucher_id ) );
}

/**
 * Print the vouchers expiration date
 * @see gb_get_voucher_expiration_date()
 * @param  integer $voucher_id Voucher ID
 * @param  string  $format     Date format
 * @return string             
 */
function gb_voucher_expiration_date( $voucher_id = 0, $format = '' ) {
	if ( $format == '' ) {
		$format = get_option( "date_format" );
	}
	$date = gb_get_voucher_expiration_date( $voucher_id );
	$date = ( $date != '' ) ? date( $format, $date ) : '';
	echo apply_filters( 'gb_voucher_expiration_date', $date, $voucher_id );
}

/**
 * Get the vouchers expiration date
 * @param  integer $voucher_id Voucher ID
 * @return string              
 */
function gb_get_voucher_expiration_date( $voucher_id = 0 ) {
	if ( !$voucher_id ) {
		global $post;
		$voucher_id = $post->ID;
	}
	if ( !$voucher_id ) {
		return '';
	}
	$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
	if ( !is_a( $voucher, 'Group_Buying_Voucher' ) ) {
		return FALSE;
	}
	$date = $voucher->get_expiration_date();
	if ( empty( $date ) ) $date = false;
	return apply_filters( 'gb_get_voucher_expiration_date', $date, $voucher_id );
}

/**
 * Get the universal voucher logo
 * @return string
 */
function gb_get_univ_voucher_logo() {
	return apply_filters( 'gb_get_univ_voucher_logo', Group_Buying_Vouchers::get_voucher_logo() );
}

/**
 * Print the universal voucher logo
 * @see gb_get_univ_voucher_logo()
 * @return string html
 */
function gb_univ_voucher_logo() {
	$url = gb_get_univ_voucher_logo();
	$html = sprintf( '<img src="%s" alt="" />', $url );
	echo apply_filters( 'gb_univ_voucher_logo', $html );
}

/**
 * Is a universal voucher logo set
 * @return boolean
 */
function gb_has_univ_voucher_logo() {
	$logo = gb_get_univ_voucher_logo();
	return strlen( $logo ) > 0;
}

/**
 * Get the universal fine print
 * @return string
 */
function gb_get_univ_voucher_fine_print() {
	return apply_filters( 'gb_get_univ_voucher_fine_print', Group_Buying_Vouchers::get_voucher_fine_print() );
}

/**
 * Print the universal fine print
 * @see gb_get_univ_voucher_fine_print()
 * @return string
 */
function gb_univ_voucher_fine_print() {
	echo apply_filters( 'gb_univ_voucher_fine_print', gb_get_univ_voucher_fine_print() );
}

/**
 * Get the universal support option
 * @return string
 */
function gb_get_voucher_support1() {
	return apply_filters( 'gb_get_voucher_support1', Group_Buying_Vouchers::get_voucher_support1() );
}

/**
 * Print the universal support option
 * @see gb_get_voucher_support1()
 * @return string
 */
function gb_voucher_support1() {
	echo apply_filters( 'gb_voucher_support1', gb_get_voucher_support1() );
}

/**
 * Get the second universal support option
 * @return string
 */
function gb_get_voucher_support2() {
	return apply_filters( 'gb_get_voucher_support2', Group_Buying_Vouchers::get_voucher_support2() );
}

/**
 * Print the second universal support option
 * @see gb_get_voucher_support2()
 * @return string
 */
function gb_voucher_support2() {
	echo apply_filters( 'gb_voucher_support2', gb_get_voucher_support2() );
}

/**
 * Get the universal legal information
 * @return string
 */
function gb_get_voucher_legal() {
	return apply_filters( 'gb_get_voucher_legal', Group_Buying_Vouchers::get_voucher_legal() );
}

/**
 * Print the universal legal information
 * @see gb_get_voucher_legal()
 * @return string
 */
function gb_voucher_legal() {
	echo apply_filters( 'gb_voucher_legal', gb_get_voucher_legal() );
}




