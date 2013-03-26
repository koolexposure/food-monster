<?php

/**
 * GBS Merchant Template Functions
 *
 * @package GBS
 * @subpackage Merchant
 * @category Template Tags
 */


/////////////
// Utility //
/////////////

/**
 * Get the merchant post type
 * @return string 
 */
function gb_get_merchant_post_type() {
	return Group_Buying_Merchant::POST_TYPE;
}

/**
 * Currently viewing account page
 * @return boolean
 */
function gb_on_merchant_account_page() {
	if ( gb_on_merchant_dashboard_page() && gb_on_deal_submit_page() ) {
		return true;
	}
	return;
}

/**
 * Currently viewing merchant dashboard
 * @return boolean
 */
function gb_on_merchant_dashboard_page() {
	if ( get_query_var( Group_Buying_Merchants_Dashboard::BIZ_DASH_QUERY_VAR ) ) {
		return true;
	}
	return;
}

/**
 * Currently viewing deal submit page
 * @return boolean
 */
function gb_on_deal_submit_page() {
	if ( get_query_var( Group_Buying_Deals_Submit::SUBMIT_QUERY_VAR ) ) {
		return true;
	}
	return;
}


/**
 * Merchant category taxonomy name
 *
 * @return string taxonomy slug
 */
function gb_get_merchant_cat_slug() {
	return Group_Buying_Merchant::MERCHANT_TYPE_TAXONOMY;
}

//////////
// URLS //
//////////

/**
 * Print merchant account/dashboard url
 * @see gb_get_merchant_account_url()
 * @return string 
 */
function gb_merchant_account_url() {
	echo apply_filters( 'gb_merchant_account_url', gb_get_merchant_account_url() );
}

/**
 * Return merchant account/dashboard url
 * @return string 
 */
function gb_get_merchant_account_url() {
	$url = Group_Buying_Merchants_Dashboard::get_url();
	return apply_filters( 'gb_get_merchant_account_url', $url );
}

/**
 * Get merchants url
 * @return string
 */
function gb_get_merchants_url() {
	$url = Group_Buying_Merchant::get_url();
	return apply_filters( 'gb_get_merchants_url', $url );
}

/**
 * Print merchants url
 * @see gb_get_merchants_url()
 * @return string
 */
function gb_merchants_url() {
	echo apply_filters( 'gb_merchants_url', gb_get_merchants_url() );
}

/**
 * Print merchant merchant/register url
 * @see gb_get_merchant_registration_url()
 * @return string 
 */
function gb_merchant_registration_url() {
	echo apply_filters( 'gb_merchant_registration_url', gb_get_merchant_account_submit_url() );
}

/**
 * Return merchant merchant/register url
 * @return string 
 */
function gb_get_merchant_registration_url() {
	$url = Group_Buying_Merchants_Registration::get_url();
	return apply_filters( 'gb_get_merchant_registration_url', $url );
}

/**
 * Return a merchant's url
 * @param  integer $post_id Post/Merchant ID
 * @return string          
 */
function gb_get_merchant_url( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	if ( empty( $merchant ) ) return;
	return apply_filters( 'gb_get_merchant_url', get_permalink( $merchant->get_ID() ) );
}

/**
 * Does the merchant have a url
 * @param  integer $post_id Merchant ID
 * @return string          
 */
function gb_has_merchant_url( $post_id = 0 ) {
	return ( apply_filters( 'gb_get_merchant_url', gb_get_merchants_url( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print merchant's url
 * @param  integer $post_id Merchant ID
 * @return string          
 */
function gb_merchant_url( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_url', gb_get_merchant_url( $post_id ) );
}

/**
 * Claim URL for Merchants to manage vouchers
 * @param  integer $code     Voucher code that is going to be claimed
 * @param  string $redirect  add a redirect query arg to the returned url
 * @return string           
 */
function gb_get_voucher_claim_url( $code = null, $redirect = null ) {
	$url = Group_Buying_Merchants_Voucher_Claim::get_url();
	if ( null != $redirect ) {
		$url = add_query_arg( array( 'redirect_to' => $redirect ), $url );
	}
	if ( null == $code ) {
		return apply_filters( 'gb_get_merchants_url', $url );
	}
	$url = add_query_arg( array( Group_Buying_Merchants_Voucher_Claim::BIZ_VOUCHER_CLAIM_ARG => $code ), $url );
	return apply_filters( 'gb_get_merchants_url', $url );
}

/**
 * URL for all merchant types. Taxonomy archive page.
 * @return string URL
 */
function gb_get_merchant_type_url() {
	return apply_filters( 'gb_get_merchant_type_url', site_url( trailingslashit( Group_Buying_Merchant::MERCHANT_TYPE_TAX_SLUG ) ) );
}

/**
 * Print merchant type archive url
 * @return string
 */
function gb_merchant_type_url() {
	echo apply_filters( 'gb_merchant_type_url', gb_get_merchant_type_url() );
}

/**
 * Print Merchant registration url
 * @see gb_get_merchant_register_url()
 * @return string
 */
function gb_merchant_register_url() {
	echo apply_filters( 'gb_merchant_register_url', gb_get_merchant_register_url() );
}

/**
 * Return Merchant registration url
 * @see Group_Buying_Merchants_Registration::get_url()
 * @return string
 */
function gb_get_merchant_register_url() {
	$url = Group_Buying_Merchants_Registration::get_url();
	return apply_filters( 'gb_get_merchant_register_url', $url );
}

/**
 * Print Merchant account edit url
 * @see gb_get_merchant_edit_url()
 * @return string
 */
function gb_merchant_edit_url() {
	echo apply_filters( 'gb_merchant_edit_url', gb_get_merchant_edit_url() );
}

/**
 * Return Merchant account edit url
 * @see Group_Buying_Merchants_Edit::get_url()
 * @return string
 */
function gb_get_merchant_edit_url() {
	$url = Group_Buying_Merchants_Edit::get_url();
	return apply_filters( 'gb_get_merchant_edit_url', $url );
}


/////////////////////
// Misc. Functions //
/////////////////////

/**
 * Return all merchants this user is authorized to manage
 * @param  integer $user_id $user->ID
 * @return array         array of merchant IDs
 */
function gb_get_merchants_by_account( $user_id = 0 ) {
	if ( null == $user_id ) {
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
	}
	$merchants = Group_Buying_Merchant::get_merchants_by_account( $user_id );
	return apply_filters( 'gb_get_merchants_by_account', $merchants, $user_id );
}

/**
 * Return all merchants this user is authorized to manage
 * @param  integer $user_id $user->ID
 * @return array         array of merchant IDs
 */
function gb_get_merchant_authorized_users( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_instance( $post_id );
	return apply_filters( 'gb_get_merchant_authorized_users', $merchant->get_authorized_users(), $post_id );
}

/**
 * Does this deal have a merchant assigned to it?
 * @param  integer $post_id Post or Deal ID
 * @return boolean
 */
function gb_has_merchant( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_id', !gb_get_merchant_id( $post_id ) ) ) ? FALSE: TRUE ;
}

/**
 * Get the merchant ID for a deal
 * @param  integer $post_id Post or Deal ID
 * @return integer
 */
function gb_get_merchant_id( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	if ( !is_object( $merchant ) ) return FALSE;
	return apply_filters( 'gb_get_merchant_id', $merchant->get_id() );
}

/**
 * Get all purchase ids associated with a merchant. Based on deals associated with a merchant.
 * @param  integer $post_id Merchant ID
 * @return array|-1         Array of all purchase ids
 */
function gb_get_merchants_purchase_ids( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deals = gb_get_merchants_deal_ids( gb_account_merchant_id() );
	$purchases = Group_Buying_Purchase::get_purchases( array( 'deal' => $deals ) );
	if ( empty( $purchases ) ) {
		$purchases = array(0);
	}
	return apply_filters( 'gb_get_merchants_purchase_ids', $purchases, $post_id );
}

/**
 * Get all deals associated with a merchant
 * @param  integer $post_id Merchant ID
 * @return array|-1         Array of all deal ids
 */
function gb_get_merchants_deal_ids( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	$deals = $merchant->get_deal_ids();
	if ( empty( $deals ) ) {
		return -1;
	}
	return apply_filters( 'gb_get_merchants_deal_ids', $deals, $post_id );
}

/**
 * Get all deals associated with a merchant
 * @param  integer $post_id Merchant ID
 * @return array         Array of all deal ids
 */
function gb_get_merchant_deals( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_instance( $post_id );
	return apply_filters( 'gb_get_merchant_deals', $merchant->get_deal_ids() );
}

/**
 * Checks to see if a merchant has any associated deals
 * @param  integer $post_id Merchant ID
 * @return boolean           
 */
function gb_has_merchant_deals( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_deals', !is_array( gb_get_merchant_deals( $post_id ) ) ) ) ? FALSE : TRUE ;
}

/**
 * Return a WP_Query object of deals associated with a merchant.
 * @param  integer $post_id Merchant ID
 * @param  array   $args    args to extend the default WP_Query args
 * @return class object
 */
function gb_get_merchant_deals_query( $post_id = 0, $args = array() ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merch_deals = null;
	$merchant_deals = gb_get_merchant_deals( $post_id );
	if ( empty( $merchant_deals ) ) return FALSE;
	$defaults=array(
		'post_type' => gb_get_deal_post_type(),
		'post__in' => $merchant_deals,
		'post_status' => 'publish'
	);
	$args = wp_parse_args( $args, $defaults );
	$deals = new WP_Query( $args );

	return apply_filters( 'gb_get_merchant_deals_query', $deals );
}


////////////////////
// Merchant Types //
////////////////////

/**
 * Return the merchant types object
 * @see get_terms()
 * @param  boolean $hide_empty Hide empty types (types without any active posts/deals assigned)
 * @return array  returns an array of merchant type objects
 */
function gb_get_merchant_types( $empty = true ) {
	return apply_filters( 'gb_get_merchant_types', get_terms( Group_Buying_Merchant::MERCHANT_TYPE_TAXONOMY, array( 'hide_empty'=>$empty, 'fields'=>'all' ) ) );

}

/**
 * Get the current merchant type being viewed
 * @param  boolean $slug Return the type slug, default to name
 * @return string        
 */
function gb_get_current_merchant_type( $slug = false ) {
	$taxonomy = get_query_var( 'taxonomy' );
	if ( $taxonomy == Group_Buying_Merchant::MERCHANT_TYPE_TAXONOMY ) {
		global $wp_query;
		if ( $slug ) {
			return apply_filters( 'gb_get_current_merchant_type', $wp_query->get_queried_object()->slug );
		} else {
			return apply_filters( 'gb_get_current_merchant_type', $wp_query->get_queried_object()->name );
		}
	}
	return FALSE;
}

/**
 * Print the current merchant type name
 * @see gb_get_current_merchant_type()
 * @return string        
 */
function gb_current_merchant_type() {
	echo apply_filters( 'gb_current_merchant_type', gb_get_current_merchant_type() );
}

/**
 * Print a list of merchant types for a particular merchant
 * @param  integer $post_id  [description]
 * @param  string  $format   format the list should be constructed with: url, ol, span, div, etc.
 * @param  boolean $all_link Whether to show an "All" link that will send the user to the merchants url
 * @param  string  $ulclass  ul/format class
 * @return string            
 */
function gb_get_merchants_types_list( $post_id = 0, $format = 'ul', $all_link = FALSE, $ulclass = 'merchant-type-ul clearfix' ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$types = gb_get_merchants_types( $post_id );

	if ( empty( $types ) )
		return '';

	$tag = $format;

	$list = '';
	if ( $format == 'ul' || $format == 'ol' ) {
		$list .= "<".$format." class='".$ulclass."'>";
		$tag = 'li';
	}

	if ( $all_link != FALSE ) {
		$list .= "<".$tag." id='location_slug_all' class='type-item'>";
		$list .= "<a href='".gb_get_merchants_url()."' title='Visit All Merchant Types Deals' id='location_slug_all'>".$all_link."</a>";
		$list .= "</".$tag.">";
	}
	foreach ( $types as $type ) {
		$link = get_term_link( $type->slug, Group_Buying_Merchant::MERCHANT_TYPE_TAXONOMY );
		$active = ( $type->name == gb_get_current_merchant_type() ) ? 'current_item' : 'item';
		$list .= "<".$tag." id='location_slug_".$type->slug."' class='type-item ".$active."'>";
		$list .= "<a href='".$link."' title='Visit ".$type->name."s Deals' id='location_slug_".$type->slug."'>".$type->name."</a>";
		$list .= "</".$tag.">";
	}

	if ( $format == 'ul' || $format == 'ol' )
		$list .= "</".$format.">";

	echo apply_filters( 'gb_get_merchant_types_list', $list, $format );

}

/**
 * Print a list of all merchant types
 * @param  string  $format   format the list should be constructed with: url, ol, span, div, etc.
 * @param  boolean $all_link Whether to show an "All" link that will send the user to the merchants url
 * @param  string  $ulclass  ul/format class
 * @return string            
 */
function gb_get_all_merchant_types_list( $format = 'ul', $all_link = FALSE, $ulclass = 'merchant-type-ul clearfix' ) {
	$types = gb_get_merchant_types();

	if ( empty( $types ) )
		return '';

	$tag = $format;

	$list = '';
	if ( $format == 'ul' || $format == 'ol' ) {
		$list .= "<".$format." class='".$ulclass."'>";
		$tag = 'li';
	}

	if ( $all_link != FALSE ) {
		$list .= "<".$tag." id='location_slug_all' class='type-item'>";
		$list .= "<a href='".gb_get_merchants_url()."' title='Visit All Merchant Types Deals' id='location_slug_all'>".$all_link."</a>";
		$list .= "</".$tag.">";
	}
	foreach ( $types as $type ) {
		$link = get_term_link( $type->slug, Group_Buying_Merchant::MERCHANT_TYPE_TAXONOMY );
		$active = ( $type->name == gb_get_current_merchant_type() ) ? 'current_item' : 'item';
		$list .= "<".$tag." id='location_slug_".$type->slug."' class='type-item ".$active."'>";
		$list .= "<a href='".$link."' title='Visit ".$type->name."s Deals' id='location_slug_".$type->slug."'>".$type->name."</a>";
		$list .= "</".$tag.">";
	}

	if ( $format == 'ul' || $format == 'ol' )
		$list .= "</".$format.">";

	echo apply_filters( 'gb_get_merchant_types_list', $list, $format );

}

/**
 * Get a merchant's assigned types
 * @see wp_get_object_terms()
 * @param  integer $post_id Merchant ID
 * @return objects          Term objects
 */
function gb_get_merchants_types( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	return apply_filters( 'gb_get_merchants_types', wp_get_object_terms( $post_id, Group_Buying_Merchant::MERCHANT_TYPE_TAXONOMY ) );
}

/**
 * Return the Merchant type. If a merchant has more than one type the first in the array is returned.
 * @param  integer $post_id Merchant ID
 * @return object           term object
 */
function gb_get_merchants_type( $post_id = 0 ) {
	$types = gb_get_merchants_types( $post_id );
	if ( empty( $types ) ) return;
	return apply_filters( 'gb_get_merchants_type', $types[0] );
}

/**
 * Print the merchant's type url.
 * @param  integer $post_id Merchant ID
 * @return string           
 */
function gb_merchants_type_url( $post_id = 0 ) {
	$type = gb_get_merchants_type( $post_id );
	if ( empty( $type ) ) return;
	$link = '<a href="'.get_term_link( $type->slug, Group_Buying_Merchant::MERCHANT_TYPE_TAXONOMY ).'">'.$type->name.'</a>';

	echo apply_filters( 'gb_merchants_type_url', $link );
}

/////////////////
// Information //
/////////////////

/**
 * Get the total sold count for a particular merchant. Total is cached via transients for 30 minutes.
 * @param  integer|null  $merchant_id Merchant ID
 * @param  boolean $refresh     Refresh the transient cache
 * @return integer
 */
function gb_get_merchant_total_sold( $merchant_id = null, $refresh = false ) {

	if ( null == $merchant_id ) {
		$merchant_id = reset( gb_get_merchants_by_account() ); // might as well return the first, todo loop it
	}

	$cache_key = 'gb_total_sold_'.$merchant_id;
	if ( !$refresh ) {
		$cache = get_transient( $cache_key );
		if ( !empty( $cache ) ) {
			return $cache;
		}
	}

	$count = 0;
	if ( !is_array( gb_get_merchants_deal_ids( $merchant_id ) ) ) {
		return apply_filters( 'gb_get_merchant_total_sold', $count );
	}

	$args=array(
		'post_type' => gb_get_deal_post_type(),
		'post__in' => gb_get_merchants_deal_ids( $merchant_id ),
		'post_status' => 'any',
		'posts_per_page' => -1, // return this many

	);
	$merch_deals = new WP_Query( $args );  // TODO new WP_Query( array( 'fields' => 'ids' ) );
	if ( $merch_deals->have_posts() ) {
		while ( $merch_deals->have_posts() ) : $merch_deals->the_post();
		$count += (int) gb_get_number_of_purchases();
		endwhile;
	}
	set_transient( $cache_key, $count, 60*30 );
	return apply_filters( 'gb_get_merchant_total_sold', $count );
}

/**
 * Print the total sold count for a particular merchant.
 * @see gb_get_merchant_total_sold()
 * @param  integer|null  $merchant_id Merchant ID
 * @return integer
 */
function gb_merchant_total_sold( $merchant_id = null ) {
	echo apply_filters( 'gb_merchant_total_sold', gb_get_merchant_total_sold( $merchant_id ) );
}

/**
 * Merchant Name
 * @param  integer $post_id Merchant ID
 * @return string           
 */
function gb_get_merchant_name( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	if ( empty( $merchant ) ) return;
	return apply_filters( 'gb_get_merchant_name', get_the_title( $merchant->get_ID() ) );
}

/**
 * Does merchant have a name set
 * @see gb_get_merchant_name()
 * @param  integer $post_id Merchant ID
 * @return boolean
 */
function gb_has_merchant_name( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_name', gb_get_merchant_name( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print merchant name
 * @see gb_get_merchant_name()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_name( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_name', gb_get_merchant_name( $post_id ) );
}

/**
 * Get the merchant description/excerpt
 * @param  integer $post_id   [description]
 * @param  string  $read_more read more link text
 * @return [type]             [description]
 */
function gb_get_merchant_excerpt( $post_id = 0, $read_more = '...' ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	if ( empty( $merchant ) ) return;
	$excert = gb_get_excerpt_char_truncation( '400', $merchant->get_ID() );
	return apply_filters( 'gb_get_merchant_excerpt', $excert, $post_id, $read_more );
}

/**
 * Does the merchant have an description/excerpt to show
 * @see gb_get_merchant_excerpt()
 * @param  integer $post_id Merchant ID
 * @return boolean           
 */
function gb_has_merchant_excerpt( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_excerpt', gb_get_merchant_excerpt( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print the merchant excerpt
 * @see gb_get_merchant_excerpt()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_excerpt( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_excerpt', gb_get_merchant_excerpt( $post_id ) );
}

/**
 * Get the merchant's contact name
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_get_merchant_contact_name( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	return apply_filters( 'gb_get_merchant_contact_name', $merchant->get_contact_name() );
}

/**
 * Does the merchant have a contact name assigned
 * @param  integer $post_id Merchant ID
 * @return boolean
 */
function gb_has_merchant_contact_name( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_contact_name', gb_get_merchant_contact_name( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print the contact name
 * @see gb_get_merchant_contact_name()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_contact_name( $post_id = 0 ) {
	echo apply_filters( 'gb_contact_merchant_name', gb_get_merchant_contact_name( $post_id ) );
}

/**
 * Get the merchant's city
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_get_merchant_city( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	return apply_filters( 'gb_get_merchant_city', $merchant->get_contact_city() );
}

/**
 * Does the merchant have a city assigned
 * @param  integer $post_id Merchant ID
 * @return boolean
 */
function gb_has_merchant_city( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_city', gb_get_merchant_city( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print the merchant city
 * @see gb_get_merchant_city()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_city( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_city', gb_get_merchant_city( $post_id ) );
}

/**
 * Get the merchant's street address
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_get_merchant_street( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	return apply_filters( 'gb_get_merchant_street', $merchant->get_contact_street() );
}

/**
 * Does the merchant have a street address assigned
 * @param  integer $post_id Merchant ID
 * @return boolean
 */
function gb_has_merchant_street( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_street', gb_get_merchant_street( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print the street address of a merchant
 * @see gb_get_merchant_street()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_street( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_street', gb_get_merchant_street( $post_id ) );
}

/**
 * Get the merchant's state
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_get_merchant_state( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	return apply_filters( 'gb_get_merchant_state', $merchant->get_contact_state() );
}

/**
 * Does the merchant have a state assigned
 * @param  integer $post_id Merchant ID
 * @return boolean
 */
function gb_has_merchant_state( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_state', gb_get_merchant_state( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print the state of a merchant
 * @see gb_get_merchant_state()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_state( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_state', gb_get_merchant_state( $post_id ) );
}

/**
 * Get the merchant's zip
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_get_merchant_zip( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	return apply_filters( 'gb_get_merchant_zip', $merchant->get_contact_postal_code() );
}

/**
 * Does the merchant have a zip assigned
 * @param  integer $post_id Merchant ID
 * @return boolean
 */
function gb_has_merchant_zip( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_zip', gb_get_merchant_zip( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print the merchants zip
 * @see gb_get_merchant_zip()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_zip( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_zip', gb_get_merchant_zip( $post_id ) );
}

/**
 * Get the merchant's country
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_get_merchant_country( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	return apply_filters( 'gb_get_merchant_country', $merchant->get_contact_country() );
}

/**
 * Does the merchant have a country assigned
 * @param  integer $post_id Merchant ID
 * @return boolean
 */
function gb_has_merchant_country( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_country', gb_get_merchant_country( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print the merchant's country
 * @see gb_get_merchant_country()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_country( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_country', gb_get_merchant_country( $post_id ) );
}

/**
 * Get the merchant's phone
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_get_merchant_phone( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Merchant::get_instance( $post_id );
	return apply_filters( 'gb_get_merchant_phone', $deal->get_contact_phone() );
}

/**
 * Does the merchant have a phone assigned
 * @param  integer $post_id Merchant ID
 * @return boolean
 */
function gb_has_merchant_phone( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_phone', gb_get_merchant_phone( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print the phone of a merchant
 * @see gb_get_merchant_phone()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_phone( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_phone', gb_get_merchant_phone( $post_id ) );
}

/**
 * Get the merchant's website
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_get_merchant_website( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	if ( !$merchant ) {
		return apply_filters( 'gb_get_merchant_website', '' );
	}
	return apply_filters( 'gb_get_merchant_website', $merchant->get_website() );
}

/**
 * Does the merchant have a website url assigned
 * @param  integer $post_id Merchant ID
 * @return boolean
 */
function gb_has_merchant_website( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_website', gb_get_merchant_website( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print the website url of a merchant
 * @see gb_get_merchant_website()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_website( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_website', gb_get_merchant_website( $post_id ) );
}

/**
 * Get the merchant's facebook url
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_get_merchant_facebook( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	return apply_filters( 'gb_get_merchant_facebook', $merchant->get_facebook() );
}

/**
 * Does the merchant have a facebook url assigned
 * @param  integer $post_id Merchant ID
 * @return boolean
 */
function gb_has_merchant_facebook( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_facebook', gb_get_merchant_facebook( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print the facebook url of a merchant
 * @see gb_get_merchant_facebook()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_facebook( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_facebook', gb_get_merchant_facebook( $post_id ) );
}

/**
 * Get the merchant's twitter url
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_get_merchant_twitter( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$merchant = Group_Buying_Merchant::get_merchant_object( $post_id );
	return apply_filters( 'gb_get_merchant_twitter', $merchant->get_twitter() );
}

/**
 * Does the merchant have a twitter url assigned
 * @param  integer $post_id Merchant ID
 * @return boolean
 */
function gb_has_merchant_twitter( $post_id = 0 ) {
	return ( apply_filters( 'gb_has_merchant_twitter', gb_get_merchant_twitter( $post_id ) != '' ) ) ? TRUE : FALSE ;
}

/**
 * Print the twitter url of a merchant
 * @see gb_get_merchant_twitter()
 * @param  integer $post_id Merchant ID
 * @return string
 */
function gb_merchant_twitter( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_twitter', gb_get_merchant_twitter( $post_id ) );
}
