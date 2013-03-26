<?php

/**
 * GBS Affiliate Template Functions
 *
 * @package GBS
 * @subpackage Affiliate
 * @category Template Tags
 */

/**
 * Get the affiliate credit option, credits received if a deal is successfully shared.
 *
 * @return integer
 */
function gb_get_affiliate_credit() {
	return apply_filters( 'gb_get_affiliate_credit', get_option( Group_Buying_Affiliates::AFFILIATE_CREDIT_OPTION, '0' ) );
}
/**
 * Print the affiliate credit option, credits received if a deal is successfully shared.
 * @see gb_get_affiliate_credit()
 * @return integer echo
 */
function gb_affiliate_credit() {
	echo apply_filters( 'gb_affiliate_credit', gb_get_affiliate_credit() );
}

/**
 * Return share link for affiliates
 *
 * @param integer $deal_id      Deal ID
 * @param string  $member_login user_login
 * @param boolean $directlink   Does not pass affiliate query arg
 * @return string
 */
function gb_get_share_link( $deal_id = NULL, $member_login = NULL, $directlink = false ) {
	if ( NULL === $deal_id ) {
		global $post;
		$deal_id = $post->ID;
	}
	if ( !$deal_id ) {
		return '';
	}
	$link = Group_Buying_Affiliates::get_share_link( $deal_id, $member_login, $directlink );
	return apply_filters( 'gb_get_share_link', $link, $deal_id, $member_login, $directlink );
}

/**
 * Echo share link for affiliates
 * @see gb_get_share_link()
 * @param integer $deal_id      Deal ID
 * @param string  $member_login user_login
 * @param boolean $directlink   Does not pass affiliate query arg
 * @return string echo
 */
function gb_share_link( $deal_id = NULL, $member_login = NULL, $directlink = false ) {
	echo apply_filters( 'gb_share_link', gb_get_share_link( $deal_id, $member_login, $directlink ), $deal_id, $member_login, $directlink );
}

/**
 * Is the bitly integration active
 *
 * @return boolean
 */
function gb_is_bitly_active() {
	return Group_Buying_Affiliates::is_bitly_active();
}

/**
 * Get a bit.ly shortened URL
 *
 * @param string  $url URL to shorten
 * @return string
 */
function gb_get_short_url( $url ) {
	$short_url = Group_Buying_Affiliates::get_short_url( $url );
	return apply_filters( 'gb_get_short_url', $short_url, $url );
}

/**
 * Return share stats based on user and deal
 *
 * @param integer $deal_id      Deal ID
 * @param string  $member_login user_login
 * @return object|FALSE          return object or FALSE if no info is available
 */
function gb_get_share_stats( $deal_id = NULL, $member_login = NULL ) {
	$link = Group_Buying_Affiliates::get_share_link( $deal_id, $member_login );
	return apply_filters( 'gb_get_share_stats', gb_get_share_stats_by_short_url( $link ), $deal_id, $member_login );
}

/**
 * Returns share stats for a shortened url
 *
 * @param string  $short_url The URL
 * @return object|FALSE        return an object or FALSE if no info is available
 */
function gb_get_share_stats_by_short_url( $short_url = NULL ) {
	if ( NULL === $short_url ) {
		$short_url = Group_Buying_Affiliates::get_share_link();
	}
	$stats = Group_Buying_Affiliates::get_bitly_short_url_stats( $short_url );
	return apply_filters( 'gb_get_share_stats_by_short_url', $stats, $short_url );
}

/**
 * Get the clicks for a shared link
 *
 * @param integer $deal_id      Deal ID
 * @param string  $member_login user_login
 * @return integer              Clicks
 */
function gb_get_share_clicks( $deal_id = NULL, $member_login = NULL ) {
	$link = Group_Buying_Affiliates::get_share_link( $deal_id, $member_login );
	return apply_filters( 'gb_get_share_clicks', gb_get_share_clicks_by_short_url( $link ), $deal_id, $member_login );
}

/**
 * Get the clicks for a shortened link
 *
 * @param string  $short_url The URL
 * @return integer              Clicks
 */
function gb_get_share_clicks_by_short_url( $short_url = NULL ) {
	if ( NULL === $short_url ) {
		$short_url = Group_Buying_Affiliates::get_share_link();
	}
	$clicks = Group_Buying_Affiliates::get_bitly_short_url_clicks( $short_url );
	return apply_filters( 'gb_get_share_clicks_by_short_url', $clicks, $short_url );
}
