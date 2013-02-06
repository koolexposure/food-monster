<?php

/**
 * GBS Location Template Functions
 *
 * @package GBS
 * @subpackage Location
 * @category Template Tags
 */

/**
 * Get the location taxonomy slug
 * @return string
 */
function gb_get_location_tax_slug() {
	return Group_Buying_Deal::LOCATION_TAXONOMY;
}

/**
 * Return a list of locations with formatting options
 * @param  string  $format     Format of the list: ul, ol, span, div, etc.
 * @param  boolean $hide_empty Hide empty locations (locations without any active posts/deals assigned)
 * @return string              formatted list of locations
 */
function gb_get_list_locations( $format = 'ul', $hide_empty = TRUE ) {
	// Form an array of all the locations ( the location term is called 'deals' )
	$locations = gb_get_locations( $hide_empty );

	if ( empty( $locations ) )
		return '';

	$tag = $format;

	$list = '';
	if ( $format == 'ul' || $format == 'ol' ) {
		$list .= "<".$format." class='locations-ul clearfix'>";
		$tag = 'li';
	}

	foreach ( $locations as $location ) {
		if ( $location->taxonomy == gb_get_location_tax_slug() ) {
			$link = get_term_link( $location->slug, gb_get_location_tax_slug() );
			$active = ( $location->name == gb_get_current_location() ) ? 'current_item' : 'item';
			$list .= "<".$tag." id='location_slug_".$location->slug."' class='location-item ".$active."'>";
			$list .= "<a href='".apply_filters( 'gb_list_locations_link', $link, $location->slug )."' title='".sprintf( gb__( 'Visit %s Deals' ), $location->name )."' id='location_slug_".$location->slug."'>".$location->name."</a>";
			$list .= "</".$tag.">";
		}
	}

	if ( $format == 'ul' || $format == 'ol' )
		$list .= "</".$format.">";

	return apply_filters( 'gb_get_list_locations', $list, $format, $hide_empty );
}

/**
 * Print a list of locations with formatting options
 * @see gb_get_list_locations()
 * @param  string  $format     Format of the list: ul, ol, span, div, etc.
 * @param  boolean $hide_empty Hide empty locations (locations without any active posts/deals assigned)
 * @return string              formatted list of locations
 */
function gb_list_locations( $format = 'ul', $hide_empty = TRUE ) {
	echo apply_filters( 'gb_list_locations', gb_get_list_locations( $format, $hide_empty ) );
}

/**
 * Get the current location being viewed
 * @param  boolean $slug Return the location slug, default to name
 * @return string        
 */
function gb_get_current_location( $slug = false ) {
	global $wp_query;
	if ( is_tax() ) {
		if ( $slug ) {
			return apply_filters( 'gb_get_current_location', $wp_query->get_queried_object()->slug );
		} else {
			return apply_filters( 'gb_get_current_location', $wp_query->get_queried_object()->name );
		}
	}

}

/**
 * Print currently viewed location name 
 * @see gb_get_current_location()
 * @return string
 */
function gb_current_location() {
	echo apply_filters( 'gb_current_location', gb_get_current_location() );
}

/**
 * Return the locations object
 * @see get_terms()
 * @param  boolean $hide_empty Hide empty locations (locations without any active posts/deals assigned)
 * @return array  returns an array of location objects
 */
function gb_get_locations( $hide_empty = TRUE ) {
	return apply_filters( 'gb_get_locations', get_terms( array( gb_get_location_tax_slug() ), array( 'hide_empty'=>$hide_empty, 'fields'=>'all' ) ) );
}
