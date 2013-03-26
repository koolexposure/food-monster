<?php

/**
 * GBS Syndication Template Functions
 *
 * @package GBS
 * @subpackage Syndication
 * @category Template Tags
 */

/**
 * Is the deal an Aggregated/Syndicated Deal
 * @see GB_Aggregator_Destination::is_syndicated()
 * @param integer $deal_id Deal ID
 * @return boolean           
 */
function gb_is_deal_aggregated( $deal_id = 0 ) {
	if ( !$deal_id ) {
		global $post;
		$deal_id = $post->ID;
	}
	return apply_filters( 'gb_is_deal_aggregated', GB_Aggregator_Destination::is_syndicated( $deal_id ), $deal_id );
}

/**
 * Get the deals offsite/aggregated URI
 * @param integer $deal_id Deal ID
 * @return string           URI
 */
function gb_get_deal_aggregated_uri( $deal_id = 0 ) {
	if ( !$deal_id ) {
		global $post;
		$deal_id = $post->ID;
	}
	$source_url = get_post_meta( $deal_id, GB_Aggregator_Destination::DEAL_URI_META_KEY, TRUE );
	return apply_filters( 'gb_get_deal_aggregated_uri', $source_url, $deal_id );
}

/**
 * Get the deals offsite/aggregated URL
 * @param integer $deal_id Deal ID
 * @return string           URI
 */
function gb_get_deal_aggregated_link( $deal_id = 0 ) {
	if ( !$deal_id ) {
		global $post;
		$deal_id = $post->ID;
	}
	$source_url = get_post_meta( $deal_id, GB_Aggregator_Destination::DEAL_LINK_META_KEY, TRUE );
	return apply_filters( 'gb_get_deal_aggregated_link', $source_url, $deal_id );
}

/**
 * Get the deals offsite/aggregated Source
 * @param integer $deal_id Deal ID
 * @return string          
 */
function gb_get_deal_aggregated_source( $deal_id = 0 ) {
	if ( !$deal_id ) {
		global $post;
		$deal_id = $post->ID;
	}
	$source_url = get_post_meta( $deal_id, GB_Aggregator_Destination::DEAL_SOURCE_META_KEY, TRUE );
	return apply_filters( 'gb_get_deal_aggregated_source', $source_url, $deal_id );
}

/**
 * Get the deals offsite/aggregated Source URL
 * @param integer $deal_id Deal ID
 * @return string           URL
 */
function gb_get_deal_aggregated_source_url( $deal_id = 0 ) {
	if ( !$deal_id ) {
		global $post;
		$deal_id = $post->ID;
	}
	$source_url = get_post_meta( $deal_id, GB_Aggregator_Destination::DEAL_SOURCE_URL_META_KEY, TRUE );
	return apply_filters( 'gb_get_deal_aggregated_source_url', $source_url, $deal_id );
}
