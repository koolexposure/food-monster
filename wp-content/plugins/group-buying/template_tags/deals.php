<?php

/**
 * GBS Deal Template Functions
 *
 * @package GBS
 * @subpackage Deal
 * @category Template Tags
 */

///////////////
// Constants //
///////////////

/**
 * Deal post type
 *
 * @return string deal post type registered with WP
 */
function gb_get_deal_post_type() {
	return Group_Buying_Deal::POST_TYPE;
}

/**
 * Location taxonomy name
 *
 * @return string taxonomy slug
 */
function gb_get_deal_location_tax() {
	return Group_Buying_Deal::LOCATION_TAXONOMY;
}

/**
 * Deal category taxonomy name
 *
 * @return string taxonomy slug
 */
function gb_get_deal_cat_slug() {
	return Group_Buying_Deal::CAT_TAXONOMY;
}

/**
 * Deal taxonomy name
 *
 * @return string taxonomy slug
 */
function gb_get_deal_tag_slug() {
	return Group_Buying_Deal::TAG_TAXONOMY;
}


//////////
// URLS //
//////////

function gb_get_deal_edit_url( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$url = Group_Buying_Deals_Edit::get_url( $post_id );
	return apply_filters( 'gb_get_deal_edit_url', $url );
}
function gb_deal_edit_url( $post_id = 0 ) {
	echo apply_filters( 'gb_deal_edit_url', gb_get_deal_edit_url( $post_id ) );
}

function gb_get_deal_submission_url() {
	$url = Group_Buying_Deals_Submit::get_url();
	return apply_filters( 'gb_get_deal_submission_url', $url );
}

function gb_deal_submission_url() {
	echo apply_filters( 'gb_deal_submission_url', gb_get_deal_submission_url() );
}

/**
 * Query for the most recent deal based on location. Cache for two minutes for performance.
 * @param  string  $location  Deal location
 * @param  boolean $return_id Return the deal_id if TRUE, otherwise return deals permalink
 * @return string|integer     permalink|deal_id
 */
function gb_get_latest_deal_link( $location = null, $return_id = false ) {
	// get prefered location if it's set from the premium theme
	if ( function_exists( 'gb_get_preferred_location' ) && null == $location ) {
		if ( term_exists( gb_get_preferred_location() ) ) {
			$location = gb_get_preferred_location();
		}
	}
	$latest_deal_id_cache = get_transient( 'gb_latest_deal_id_'.$location );
	if ( !$latest_deal_id_cache ) {
		$args=array(
			'post_type' => gb_get_deal_post_type(),
			'post_status' => 'publish',
			'showposts' => 1,
			'meta_query' => array(
				array(
					'key' => '_expiration_date',
					'value' => array( 0, current_time( 'timestamp' ) ),
					'compare' => 'NOT BETWEEN'
				)
			)
		);

		if ( $location != '' ) {
			$args = array_merge( array( gb_get_deal_location_tax() => $location ), $args );
		}

		$latest_deal = get_posts( $args );
		if ( !empty( $latest_deal ) ) {
			foreach ( $latest_deal as $post ) :
				set_transient( 'gb_latest_deal_id_'.$location, $post->ID, 60*2 );
			if ( $return_id ) {
				return $post->ID;
			}
			$link = get_permalink( $post->ID );
			endforeach;
		}
	} else {
		if ( $return_id ) {
			return $latest_deal_id_cache;
		}
		$link = get_permalink( $latest_deal_id_cache );
	}
	if ( empty( $link ) ) { // fallback to a location loop
		$link = gb_get_deals_link( $location );
	}
	return apply_filters( 'get_gbs_latest_deal_link', $link, $location, $return_id );
}

/**
 * Get the link to a deals archive, preferably one based on location.
 * @param  string $location Slug of the location to get the term_link.
 * @return string           return the term_link of a location of the deals archive link
 */
function gb_get_deals_link( $location = null ) {
	if ( null != $location ) {
		$link = get_term_link( $location, gb_get_location_tax_slug() );
		if ( $link == $location ) {
			$link = get_post_type_archive_link( gb_get_deal_post_type() );
		}
	} else {
		$link = get_post_type_archive_link( gb_get_deal_post_type() );
	}
	return apply_filters( 'gb_get_deals_link', $link, $location );
}


//////////////////////////
// URLS / Deal Previews //
//////////////////////////

/**
 * Is a preview of this deal available if it's currently a draft
 * @param  [type] $post_id [description]
 * @return boolean          
 */
function gb_deal_preview_available( $post_id = null ) {
	if ( null === $post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_deal_preview_available', Group_Buying_Deals_Preview::has_key( $deal ) );
}

/**
 * Get the preview url to a deal
 * @param  [type] $post_id [description]
 * @return string          url
 */
function gb_get_deal_preview_link( $post_id = null ) {
	if ( null === $post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	if ( gb_deal_preview_available( $post_id ) ) {
		$deal = Group_Buying_Deal::get_instance( $post_id );
		return apply_filters( 'gb_get_deal_preview_link', Group_Buying_Deals_Preview::get_preview_link( $deal ) );
	}
	return;
}

/**
 * Print the preview url for the current deal
 * @param  [type] $post_id [description]
 * @return string          url
 */
function gb_deal_preview_link( $post_id = null ) {
	echo apply_filters( 'gb_deal_preview_link', gb_get_deal_preview_link( $post_id ) );
}


/////////////
// Utility //
/////////////


/**
 * Get array of accounts that have purchased a specific deal
 *
 * @see Group_Buying_Purchase::get_purchases()
 * @param integer $post_id Deal ID
 * @param bool $user_ids If true the function will return user_ids instead of account ids
 * @return array
 */
function gb_get_deal_purchasers( $post_id = 0, $user_ids = FALSE  ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$ids = array();
	$purchase_ids = Group_Buying_Purchase::get_purchases( array( 'deal' => $post_id ) );
	foreach ( $purchase_ids as $purchase_id ) {
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		if ( $user_ids ) {
			$id = $purchase->get_user();
		}
		else {
			$id = $purchase->get_account_id();
		}
		// Build array
		if ( !in_array( $id, $ids ) ) {
			$ids[] = $id;
		}
	}
	return apply_filters( 'gb_get_deal_purchasers', $ids, $post_id, $user_id );
}

/**
 * Get a list of successful Purchases by a given account
 *
 * @see Group_Buying_Deal::get_purchases_by_account()
 * @param integer $post_id Deal ID
 * @param integer $user_id User ID
 * @return array
 */
function gb_get_purchases_by_account( $post_id = 0, $user_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$account = Group_Buying_Account::get_instance( $user_id );
	$deal = Group_Buying_Deal::get_instance( $post_id  );
	return apply_filters( 'gb_get_purchase_by_account', $deal->get_purchases_by_account( $account->get_id() ) );
}

/**
 * Get the last viewed deal or return to an appropriate deal instead.
 *
 * @param string  $last_viewed Slug of last viewed post
 * @return string               url to redirect to
 */
function gb_get_last_viewed_redirect_url( $last_viewed = null ) {
	if ( null == $last_viewed ) {
		$last_viewed = gb_get_cookie( 'last-deal-viewed' );
	}

	if ( isset( $_GET['redirect_to'] ) ) {
		$url = $_GET['redirect_to'];
	} elseif ( !empty( $last_viewed ) ) {
		$post_type = get_post_type_object( gb_get_deal_post_type() );
		$url = site_url( trailingslashit( $post_type->rewrite['slug'] ) . $last_viewed );;
	} elseif ( isset( $_POST['deal_redirect'] ) ) {
		$post_type = get_post_type_object( gb_get_deal_post_type() );
		$url = site_url( trailingslashit( $post_type->rewrite['slug'] ) . $_POST['deal_redirect'] );
	} else {
		$url = gb_get_latest_deal_link();
	}
	return apply_filters( 'gb_get_last_viewed_redirect_url', $url );
}

/**
 * Is the current deal complete
 *
 * @see Group_Buying_Deal::is_closed()
 * @param integer $post_id Deal ID
 * @return boolean
 */
function gb_is_deal_complete( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_deal_savings', $deal->is_closed() );
}

/**
 * Get the status of a deal
 *
 * @see Group_Buying_Deal::get_status()
 * @param integer $post_id Deal ID
 * @return string           open|pending|closed|unknown
 */
function gb_get_status( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_status', $deal->get_status() );
}

/**
 * Has this user purchased this deal already
 *
 * @see gb_get_purchases_by_account()
 * @param integer $post_id [description]
 * @param integer $user_id User ID
 * @return boolean
 */
function gb_has_purchased( $post_id = 0, $user_id = 0 ) { // TODO
	$bool = FALSE;
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$purchases = gb_get_purchases_by_account( $post_id, $user_id );
	if ( is_array( $purchases ) && !empty( $purchases )  ) {
		$bool = TRUE;
	}
	return apply_filters( 'gb_has_purchased', $bool, $post_id );
}

/**
 * Has this deal reached it's purchase tipping point
 *
 * @see gb_get_number_of_purchases() && gb_get_min_purchases()
 * @param integer $post_id Deal ID
 * @return boolean
 */
function gb_has_deal_tipped( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$bool = FALSE;
	if ( !gb_has_purchase_min( $post_id ) ) {
		$bool = TRUE;
	}
	$purchases = gb_get_number_of_purchases( $post_id );
	$purchase_min = gb_get_min_purchases( $post_id );
	if ( $purchases >= $purchase_min ) {
		$bool = TRUE;
	}
	return apply_filters( 'gb_has_deal_tipped', $bool, $post_id );
}

/**
 * The current availability for the deal. Based on tipping point and expiration.
 *
 * @see Group_Buying_Deal::is_open()
 * @param integer $post_id Deal ID
 * @return boolean
 */
function gb_deal_availability( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$deal = Group_Buying_Deal::get_instance( $post_id );
	if ( !is_a( $deal, 'Group_Buying_Deal' ) ) {
		return FALSE;
	}
	return apply_filters( 'gb_get_status', $deal->is_open() );

}

/**
 * Is the deal sold out, i.e. reached its purchase limit.
 *
 * @see Group_Buying_Deal::is_sold_out()
 * @param integer $post_id Deal ID
 * @return boolean
 */
function gb_is_sold_out( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_is_sold_out', $deal->is_sold_out() );
}


/**
 * Return the total number of purchases for the deal
 *
 * @see Group_Buying_Deal::get_number_of_purchases()
 * @param integer $post_id     Deal ID
 * @param boolean $recalculate Reset the stored purchase tally and recalculate total
 * @return integer
 */
function gb_get_number_of_purchases( $post_id = 0, $recalculate = false ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_number_of_purchases', $deal->get_number_of_purchases( $recalculate ) );
}

/**
 * Return the total number of purchases for the deal
 *
 * @see gb_get_number_of_purchases()
 * @param integer $post_id     Deal ID
 * @param boolean $recalculate Reset the stored purchase tally and recalculate total
 * @return integer
 */
function gb_number_of_purchases( $post_id = 0, $recalculate = false ) {
	echo apply_filters( 'gb_number_of_purchases', gb_get_number_of_purchases( $post_id, $recalculate ) );
}

/**
 * Return the merchant for a deal
 *
 * @param integer $post_id Deal ID
 * @return object           Group_Buying_Merchant
 */
function gb_get_merchant( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_merchant', $deal->get_merchant() );
}

/**
 * Is the this deal todays deal.
 * @param  integer $todays_deal_id Todays deal_id, will use gb_get_latest_deal_link() if 0|FALSE is given
 * @param  integer $post_id        Deal ID
 * @return boolean
 */
function gb_is_latest_deal_page( $todays_deal_id = 0, $post_id = 0 ) {
	$location = gb_get_preferred_location();
	if ( !$todays_deal_id ) {
		$latest_deal_id_cache = get_transient( 'gb_latest_deal_id_'.$location ); // get cache, match gb_get_latest_deal_link
		if ( !$latest_deal_id_cache ) { // if no cache get the id directly
			$latest_deal_id_cache = gb_get_latest_deal_link( $location, true );
		}
		$todays_deal_id = $latest_deal_id_cache;
	}
	if ( !$todays_deal_id )
		return; // nothing more to do

	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	if ( $post_id == $todays_deal_id ) {
		return TRUE;
	}
	return FALSE;
}


///////////
// Views //
///////////

function gb_deal_submit_form() {
	$deal_sub = Group_Buying_Deals_Submit::get_instance();
	if ( $deal_sub ) {
		echo $deal_sub->get_form();
	} else {
		return FALSE;
	}
}

/**
 * Print a countdown timer with appropriate script tag for the count-down library found in most GBS themes.
 *
 * @param boolean $compact Option to display the countdown in a compact view
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_deal_countdown( $compact = false, $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	if ( !gb_is_deal_complete( $post_id ) ) {
		$end_date = gb_get_deal_end_date( "F j\, Y H:i:s", $post_id );
		$out = '<script type="text/javascript">';
		$out .= 'jQuery(function () {';
		$out .= "var date = new Date ('".$end_date."' );";
		$out .= "jQuery('#countdown-timer-".$post_id."').countdown( { ";

		// countdown args
		$args = "until: date, timezone: ".get_option( 'gmt_offset' );
		if ( $compact ) { $args .= ", compact: true, format: 'DHMS', description: ''"; }

		$out .= apply_filters( 'gb_deal_countdown_js_args', $args );
		$out .= ' } );';
		$out .= ' });';
		$out .= '</script>';
		$out .= '<div id="countdown_timer_wrap-'.$post_id.'" class="countdown_timer_wrap"><span class="countdown_timer_label">'.gb__( 'Expires in: ' ).'</span><span id="countdown-timer-'.$post_id.'" class="countdown-timer"></span></div>';

	} else {
		$out = "<div id='countdown-timer' class='expired'><span>".gb__( 'Complete' )."</span></div>";
	}
	echo apply_filters( 'gb_deal_countdown', $out, $compact, $post_id );
}


//////////
// Data //
//////////


///////////////////////
// Data / Expiration //
///////////////////////

/**
 * Does the current deal have an expiration
 *
 * @param integer $post_id Deal ID
 * @return boolean
 */
function gb_has_expiration( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_has_expiration', TRUE != $deal->never_expires() );

}

/**
 * Get the deals expiration date
 *
 * @param string  $format  date() format
 * @param integer $post_id Deal ID
 * @return string           date formatted timestamp
 */
function gb_get_deal_end_date( $format = 'F j\, Y H:i:s', $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$date = date( $format , gb_get_expiration_date( $post_id ) );
	return apply_filters( 'gb_deal_end_date', $date );

}

/**
 * Get UNIX timestamp of deal expiration
 *
 * @param integer $post_id Deal ID
 * @return integer          timestamp
 */
function gb_get_expiration_date( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_expiration_date', $deal->get_expiration_date() );
}

/**
 * Print UNIX timestamp of deal expiration
 *
 * @see gb_get_expiration_date()
 * @param integer $post_id Deal ID
 * @return integer          timestamp
 */
function gb_expiration_date( $post_id = 0 ) {
	echo apply_filters( 'gb_expiration_date', gb_get_expiration_date( $post_id ) );
}

/**
 * Get the time left for a deal.
 *
 * @param integer $post_id Deal ID
 * @return string           Returns relative time, example: 1 day, 10 days, 1 hour, 2 minutes
 */
function gb_get_days_left( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	if ( gb_deal_availability() ) {
		$end_date = gb_get_expiration_date( $post_id );
		$difference = $end_date - current_time( 'timestamp' );
		if ( floor( $difference/60/60/24 ) >= 1 ) {
			if ( floor( $difference/60/60/24 ) == 1 ) {
				$remain = ( floor( $difference/60/60/24 ) ) . gb__( ' day' );
			} else {
				$remain = ( floor( $difference/60/60/24 ) ) . gb__( ' days' );
			}
		} elseif ( floor( $difference/60/60 ) >= 1 ) {
			if ( floor( $difference/60/60 ) == 1 ) {
				$remain = ( floor( $difference/60/60 ) ) . gb__( ' hour' );
			} else {
				$remain = ( floor( $difference/60/60 ) ) . gb__( ' hours' );
			}
		} else {
			if ( floor( $difference/60 ) == 1 ) {
				$remain = ( floor( $difference/60 ) ) . gb__( ' minute' );
			} else {
				$remain = ( floor( $difference/60 ) ) . gb__( ' minutes' );
			}
		}
	} else {
		$remain = gb__( 'No time' );
	}
	return apply_filters( 'gb_get_days_left', $remain );
}

/**
 * Print the time left for a deal.
 *
 * @see gb_get_days_left()
 * @param integer $post_id Deal ID
 * @return string           Prints relative time, example: 1 day, 10 days, 1 hour, 2 minutes
 */
function gb_days_left( $post_id = 0 ) {
	echo apply_filters( 'gb_days_left', gb_get_days_left( $post_id ) );
}
/**
 * Get the price of the current deal
 *
 * @param integer $post_id   Deal ID
 * @param boolean $formatted Return formatted price with gb_geT_formatted_money()
 * @return float
 */
function gb_get_price( $post_id = 0, $formatted = false ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );

	$get_price = $deal->get_price();
	$price = ( $formatted ) ? gb_get_formatted_money( $get_price ) : $get_price ;
	return apply_filters( 'gb_get_price', $price, $formatted );
}

//////////////////
// Data / Price //
//////////////////

/**
 * Echo the price of the current deal
 *
 * @see gb_get_price()
 * @param integer $post_id   Deal ID
 * @param boolean $formatted Return formatted price with gb_get_formatted_money()
 * @return float
 */
function gb_price( $post_id = 0, $formatted = true ) {
	echo apply_filters( 'gb_price', gb_get_price( $post_id, $formatted ) );
}

/**
 * Does the current deal have dynamic pricing set?
 *
 * @see Group_Buying_Deal::has_dynamic_price()
 * @param integer $post_id Deal ID
 * @return boolean
 */
function gb_has_dynamic_price( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_has_dynamic_price', $deal->has_dynamic_price() );
}

/**
 * Get the deal dynamic prices
 *
 * @see Group_Buying_Deal::get_dynamic_price()
 * @param integer $post_id Deal ID
 * @return array
 */
function gb_get_dynamic_prices( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );

	$dynamic_prices = $deal->get_dynamic_price();
	return apply_filters( 'gb_get_dynamic_prices', $dynamic_prices );
}

/**
 * Display a ul list of dynamic prices for the current deal.
 *
 * @param integer $post_id  Deal ID
 * @param boolean $show_all Show only the dynamic prices that have not already been met
 * @return string            <ul> list
 */
function gb_dynamic_prices( $post_id = 0, $show_all = false ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$dynamic_prices = gb_get_dynamic_prices( $post_id );
	$current_price = gb_get_price( $post_id );
	if ( empty( $dynamic_prices ) ) return;
	if ( !$show_all ) {
		foreach ( $dynamic_prices as $limit => $price ) {
			if ( gb_get_number_of_purchases( $post_id ) > $limit ) {
				if ( $current_price != $price ) { // don't take current
					unset( $dynamic_prices[$limit] );
				}
			}
		}
	}
	if ( empty( $dynamic_prices ) ) return;
	$width = 100/count( $dynamic_prices );
	$out = '<ul class="milestone_pricing gb_fx">';
	$i = 1;
	foreach ( $dynamic_prices as $limit => $price ) {
		$next = $limit - gb_get_number_of_purchases( $post_id );
		$out .= '<li class="ms instance_' . $i . '" style="width:' . $width . '%">';
		$out .= '<div>';
		$out .= '<span class="gb_fx">' . str_replace( '.00', '', gb_get_formatted_money( $price ) ) . '</span> ' . sprintf( gb__( ' after %s' ), $limit );
		$out .= '</div>';
		$out .= '</li>';
		$i++;
	}
	$out .= '</ul>';
	echo apply_filters( 'gb_dynamic_prices', $out, $dynamic_prices, $show_all );
}

/**
 * Return the min purchases required for the price to change to the next dynamic price.
 *
 * @param integer $post_id Deal ID
 * @return integer
 */
function gb_next_dynamic_price_min( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$dynamic_prices = gb_get_dynamic_prices( $post_id );
	if ( empty( $dynamic_prices ) ) return;
	if ( !$show_all ) {
		foreach ( $dynamic_prices as $limit => $price ) {
			if ( gb_get_number_of_purchases( $post_id ) > $limit ) {
				unset( $dynamic_prices[$limit] );
			}
		}
	}
	reset( $dynamic_prices );
	return apply_filters( 'gb_next_dynamic_price_min', key( $dynamic_prices ) );
}

/**
 * Return the next price if the minimum purchases are made for the dynamic price.
 *
 * @param integer $post_id Deal ID
 * @return integer
 */
function gb_next_dynamic_price( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$dynamic_prices = gb_get_dynamic_prices( $post_id );
	if ( empty( $dynamic_prices ) ) return;
	if ( !$show_all ) {
		foreach ( $dynamic_prices as $limit => $price ) {
			if ( gb_get_number_of_purchases( $post_id ) > $limit ) {
				unset( $dynamic_prices[$limit] );
			}
		}
	}
	return apply_filters( 'gb_next_dynamic_price', array_shift( $dynamic_prices ) );
}



/////////////////
// Data / Misc //
/////////////////

/**
 * Get deal worth
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_get_deal_worth( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_deal_worth', $deal->get_value() );
}

/**
 * Print the current deals worth
 *
 * @param integer $post_id Deal ID
 * @return string           echo
 */
function gb_deal_worth( $post_id = 0 ) {
	echo apply_filters( 'gb_deal_worth', gb_get_deal_worth( $post_id ) );
}

/**
 * Get the savings for the current deal
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_get_deal_savings( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	$savings = $deal->get_amount_saved();
	if ( empty( $savings ) || $savings = '' ) {
		$original_price = str_replace( '$', '', gb_get_deal_worth( $post_id ) );
		if ( empty( $original_price ) ) {
			return apply_filters( 'gb_get_deal_savings', '' );
		}
		$price = gb_get_price( $post_id );
		$savings = gb_get_formatted_money( $original_price - $price );
	}
	return apply_filters( 'gb_get_deal_savings', $savings  );
}

/**
 * Print the savings for the current deal
 *
 * @see gb_get_deal_savings()
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_deal_savings( $post_id = 0 ) {
	echo apply_filters( 'gb_deal_savings', gb_get_deal_savings( $post_id ) );
}

/**
 * Calculated savings based on deal price and deal worth; defaults to deal savings if exists
 *
 * @param integer $post_id Deal ID
 * @param float   $price   Base price; defaults to deal_price
 * @return string           %
 */
function gb_get_amount_saved( $post_id = 0, $price = null ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	if ( null === $price ) {
		$price = gb_get_price( $post_id );
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	$savings = $deal->get_amount_saved();
	if ( !empty( $savings ) || $savings != '' ) {
		return apply_filters( 'gb_get_amount_saved', $savings );
	}
	$original_price = str_replace( '$', '', gb_get_deal_worth( $post_id ) );
	if ( empty( $original_price ) ) return 0;
	$savings = $original_price - $price;
	$savings = number_format( ( $savings/$original_price )*100, 0 );
	return apply_filters( 'gb_get_amount_saved', $savings.'%' );
}

/**
 * Calculated savings based on deal price and deal worth; defaults to deal savings if exists
 *
 * @see gb_get_amount_saved()
 * @param integer $post_id Deal ID
 * @return string           <span>
 */
function gb_amount_saved( $post_id = 0 ) {
	echo apply_filters( 'gb_amount_saved', "<span class='deal-savings'>".gb_get_amount_saved( $post_id )."</span>" );
}

/**
 * Get deal highlights
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_get_highlights( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_highlights', $deal->get_highlights() );
}

/**
 * Print the current deal highlights
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_highlights( $post_id = 0 ) {
	echo apply_filters( 'gb_highlights', gb_get_highlights( $post_id ) );
}

/**
 * Get deal fine print
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_get_fine_print( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_fine_print', $deal->get_fine_print() );
}

/**
 * Get deal fine print
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_fine_print( $post_id = 0 ) {
	echo apply_filters( 'gb_fine_print', gb_get_fine_print( $post_id ) );
}

/**
 * Get deal rss excerpt
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_get_rss_excerpt( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_rss_excerpt', $deal->get_rss_excerpt() );
}

/**
 * Get the current deals rss excerpt
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_rss_excerpt( $post_id = 0 ) {
	echo apply_filters( 'gb_rss_excerpt', gb_get_rss_excerpt( $post_id ) );
}

/**
 * Get deal map
 * @param  integer $post_id [description]
 * @return string           
 */
function gb_get_map( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_map', $deal->get_voucher_map() );
}

/**
 * Print the current deals map
 * @param  integer $post_id [description]
 * @return string           iframe
 */
function gb_map( $post_id = 0 ) {
	echo apply_filters( 'gb_map', gb_get_map( $post_id ) );
}

/**
 * Does the current deal have a map
 * @param  integer $post_id [description]
 * @return boolean           
 */
function gb_has_map( $post_id = 0 ) {
	$map = gb_get_map( $post_id );
	$has = ( $map == '' ) ? FALSE : TRUE ;
	return apply_filters( 'gb_has_map', $has );

}

/**
 * Get the shipping rate for a deal
 * @param  integer $post_id [description]
 * @return float
 */
function gb_get_shipping( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );

	return apply_filters( 'gb_get_shipping', $deal->get_shipping() );
}

/**
 * Does the current deal have shipping
 * @param  integer $post_id [description]
 * @return boolean           
 */
function gb_has_shipping( $post_id = 0 ) {
	$shipping = gb_get_shipping( $post_id );
	$has = ( $shipping = '' || $shipping < '0.01' ) ? FALSE : TRUE ;
	return apply_filters( 'gb_has_shipping', $has );
}

/**
 * Print the shipping rate for the current deal
 * @param  integer $post_id [description]
 * @return float
 */
function gb_shipping( $post_id = 0 ) {
	echo apply_filters( 'gb_shipping', gb_get_shipping( $post_id ) );
}

/**
 * Get the tax for a deal
 * @param  integer $post_id [description]
 * @return float
 */
function gb_get_tax( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_tax', $deal->get_tax() );
}

/**
 * Does the current deal have have tax
 * @param  integer $post_id [description]
 * @return boolean           
 */
function gb_has_tax( $post_id = 0 ) {
	$tax = gb_get_tax( $post_id );
	$has = ( $tax = '' || $tax < '0.01' ) ? FALSE : TRUE ;
	return apply_filters( 'gb_has_tax', $has );
}

/**
 * Print the current deals tax
 * @param  integer $post_id [description]
 * @return float
 */
function gb_tax( $post_id = 0 ) {
	echo apply_filters( 'gb_tax', gb_get_tax( $post_id ) );
}

/**
 * Get the deals how to use
 * @param  integer $post_id [description]
 * @return string           
 */
function gb_get_how_to_use( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_how_to_use', $deal->get_voucher_how_to_use() );
}

/**
 * Print the current deals how to use
 * @param  integer $post_id [description]
 * @return string           
 */
function gb_how_to_use( $post_id = 0 ) {
	echo apply_filters( 'gb_how_to_use', gb_get_how_to_use( $post_id ) );
}

////////////////////
// Data / Voucher //
////////////////////

/**
 * Get deal voucher locations
 * @param  integer $post_id [description]
 * @return array
 */
function gb_get_deal_voucher_locations( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return array_filter( apply_filters( 'gb_get_deal_locations', $deal->get_voucher_locations() ) );
}

/**
 * Print an unordered list of locations for the voucher
 * @param  integer $post_id [description]
 * @return string           <ul>
 */
function gb_deal_voucher_locations( $post_id = 0 ) {
	$locations = gb_get_deal_voucher_locations( $post_id );
	if ( !empty( $locations ) ) {
		$out = "<ul class='voucher_locations'>";
		foreach ( $locations as $local ) {
			$out .= "<li>".$local."</li>";
		}
		$out .= "</ul>";
	}
	echo apply_filters( 'gb_deal_voucher_locations', $out );
}

/**
 * Get the deals voucher expiration
 * @param  integer $post_id [description]
 * @return integer           timestamp
 */
function gb_get_deal_voucher_exp_date( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_deal_voucher_exp_date', $deal->get_voucher_expiration_date() );
}

/**
 * Print the voucher expiration timestamp
 * @see gb_get_deal_voucher_exp_date()
 * @param  integer $post_id [description]
 * @return integer           
 */
function gb_deal_voucher_exp_date( $post_id = 0 ) {
	echo apply_filters( 'gb_deal_voucher_exp_date', gb_get_deal_voucher_exp_date( $post_id ) );
}

////////////////////////////
// Data / Purchase Limits //
////////////////////////////

/**
 * Get minimum required purchases for a deal
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_get_min_purchases( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_min_purchases', $deal->get_min_purchases() );
}

/**
 * Does the current deal have a purchase minimum, i.e. tipping point
 *
 * @param integer $post_id Deal ID
 * @return boolean
 */
function gb_has_purchase_min( $post_id = 0 ) {
	$min = gb_get_min_purchases( $post_id );
	$has = ( $min = '' || $min < 1 ) ? FALSE : TRUE ;
	return apply_filters( 'gb_has_purchase_min', $has );
}

/**
 * Print minimum required purchases for the current deal
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_min_purchases( $post_id = 0 ) {
	echo apply_filters( 'gb_min_purchases', gb_get_min_purchases( $post_id ) );
}

/**
 * Get maximum purchases for a deal
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_get_max_purchases( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_get_max_purchases', $deal->get_max_purchases() );
}

/**
 * Does the current deal have a purchase limit
 *
 * @param integer $post_id Deal ID
 * @return boolean
 */
function gb_has_purchases_limit( $post_id = 0 ) {
	$max = gb_get_max_purchases( $post_id );
	$has = ( $max = '' || $max < 1 ) ? FALSE : TRUE ;
	return apply_filters( 'gb_has_purchases_limit', $has );
}

/**
 * Print maximum purchases for the current deal
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_max_purchases( $post_id = 0 ) {
	echo apply_filters( 'gb_max_purchases', gb_get_max_purchases( $post_id ) );
}

/**
 * Get remaining required purchases for a deal
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_get_remaining_required_purchases( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_remaining_required_purchases', $deal->get_remaining_required_purchases() );
}

/**
 * Print remaining required purchases for the current deal
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_remaining_required_purchases( $post_id = 0 ) {
	echo apply_filters( 'gb_remaining_required_purchases', gb_get_remaining_required_purchases( $post_id ) );
}

/**
 * Get maximum purchases allowed per user for a deal
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_get_max_purchases_per_user( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$deal = Group_Buying_Deal::get_instance( $post_id );
	return apply_filters( 'gb_get_max_purchases_per_user', $deal->get_max_purchases_per_user() );
}

/**
 * Print maximum purchases allowed per user for the current deal
 *
 * @param integer $post_id Deal ID
 * @return string
 */
function gb_max_purchases_per_user( $post_id = 0 ) {
	echo apply_filters( 'gb_max_purchases_per_user', gb_get_max_purchases_per_user( $post_id ) );
}

/////////////////////
// Data / Taxonomy //
/////////////////////


/**
 * Locations a deal has assigned
 *
 * @see wp_get_object_terms()
 * @param integer $post_id Deal ID
 * @return object           WP_Taxonomy
 */
function gb_get_deal_locations( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	return apply_filters( 'gb_get_deal_locations', wp_get_object_terms( $post_id, gb_get_deal_location_tax() ) );
}

/**
 * Print the locations the current deal is assigned.
 *
 * @param integer $post_id [description]
 * @return string           <ul> list
 */
function gb_deal_locations( $post_id = 0 ) {
	$locations = gb_get_deal_locations( $post_id );
	$out = '<ul class="location_url clearfix">';
	foreach ( $locations as $location ) {
		$out .= '<li><a href="'.get_term_link( $location->slug, gb_get_location_tax_slug() ).'">'.$location->name.'</a></li>';
	}
	$out .= '</ul>';
	echo apply_filters( 'gb_deal_locations', $out );
}


/**
 * Tags a deal has assigned
 *
 * @see wp_get_object_terms()
 * @param integer $post_id Deal ID
 * @return object           WP_Taxonomy
 */
function gb_get_deal_tags( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	return apply_filters( 'gb_get_deal_tags', wp_get_object_terms( $post_id, gb_get_deal_tag_slug() ) );
}

/**
 * Print the tags the current deal is assigned.
 *
 * @param integer $post_id [description]
 * @return string           <ul> list
 */
function gb_deal_tags( $post_id = 0 ) {
	$cats = gb_get_deal_tags( $post_id );
	$out = '<ul class="deal_tags clearfix">';
	foreach ( $cats as $cat ) {
		$out .= '<li><a href="'.get_term_link( $cat->slug, gb_get_deal_tag_slug() ).'">'.$cat->name.'</a></li>';
	}
	$out .= '</ul>';
	echo apply_filters( 'gb_deal_tags', $out );
}

/**
 * Categories a deal has assigned
 *
 * @see wp_get_object_terms()
 * @param integer $post_id Deal ID
 * @return object           WP_Taxonomy
 */
function gb_get_deal_categories( $post_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	return apply_filters( 'gb_get_deal_categories', wp_get_object_terms( $post_id, gb_get_deal_cat_slug() ) );
}

/**
 * Print the categories the current deal is assigned.
 *
 * @param integer $post_id [description]
 * @return string           <ul> list
 */
function gb_deal_categories( $post_id = 0 ) {
	$cats = gb_get_deal_categories( $post_id );
	$out = '<ul class="deal_categories clearfix">';
	foreach ( $cats as $cat ) {
		$out .= '<li><a href="'.get_term_link( $cat->slug, gb_get_deal_cat_slug() ).'">'.$cat->name.'</a></li>';
	}
	$out .= '</ul>';
	echo apply_filters( 'gb_deal_categories', $out );
}

////////////////////////////////////
// Categories, Tags and Locations //
////////////////////////////////////

/**
 * Return the categories object
 *
 * @see get_terms()
 * @param boolean $hide_empty Hide empty locations (categories without any active posts/deals assigned)
 * @return array  returns an array of location objects
 */
function gb_get_categories( $hide_empty = TRUE ) {
	return apply_filters( 'gb_get_categories', get_terms( array( gb_get_deal_cat_slug() ), array( 'hide_empty' => $hide_empty, 'fields' => 'all' ) ) );
}

/**
 * Return a list of categories with formatting options
 * @param  string  $format     Format of the list: ul, ol, span, div, etc.
 * @param  boolean $hide_empty Hide empty locations (categories without any active posts/deals assigned)
 * @return string              formatted list of locations
 */
function gb_list_categories( $format = 'ul' ) {
	$categories = gb_get_categories();

	if ( empty( $categories ) )
		return '';

	$tag = $format;

	$list = '';
	if ( $format == 'ul' || $format == 'ol' ) {
		$list .= "<".$format." class='categories-ul clearfix'>";
		$tag = 'li';
	}

	foreach ( $categories as $category ) {
		$link = get_term_link( $category->slug, gb_get_deal_cat_slug() );
		$active = ( $category->name == gb_get_current_location() ) ? 'current_item' : 'item';
		$list .= "<".$tag." id='category_slug_".$category->slug."' class='category-item ".$active."'>";
		$list .= "<a href='".apply_filters( 'gb_list_category_link', $link )."' title='".sprintf( gb__( 'Visit %s Deals' ), $category->name )."' id=category_slug_".$category->slug."'>".$category->name."</a>";
		$list .= "</".$tag.">";
	}

	if ( $format == 'ul' || $format == 'ol' )
		$list .= "</".$format.">";

	echo apply_filters( 'gb_list_categories', $list, $format );
}

/**
 * Return the tags object
 *
 * @see get_terms()
 * @param boolean $hide_empty Hide empty tags (tags without any active posts/deals assigned)
 * @return array  returns an array of tag objects
 */
function gb_get_tags( $hide_empty = TRUE ) {
	return apply_filters( 'gb_get_tags', get_terms( array( gb_get_deal_tag_slug() ), array( 'hide_empty' => $hide_empty, 'fields' => 'all' ) ) );
}

/**
 * Return a list of tags with formatting options
 * @param  string  $format     Format of the list: ul, ol, span, div, etc.
 * @param  boolean $hide_empty Hide empty tags (tags without any active posts/deals assigned)
 * @return string              formatted list of tags
 */
function gb_list_tags( $format = 'ul' ) {
	$tags = gb_get_tags();

	if ( empty( $categories ) )
		return '';

	$tag = $format;

	$list = '';
	if ( $format == 'ul' || $format == 'ol' ) {
		$list .= "<".$format." class='tags-ul clearfix'>";
		$tag = 'li';
	}

	foreach ( $tags as $tag ) {
		$link = get_term_link( $tag->slug, gb_get_deal_tag_slug() );
		$active = ( $tag->name == gb_get_current_location() ) ? 'current_item' : 'item';
		$list .= "<".$tag." id='tag_slug_".$tag->slug."' class='tag-item ".$active."'>";
		$list .= "<a href='".apply_filters( 'gb_list_tag_link', $link )."' title='".sprintf( gb__( 'Visit %s Deals' ), $tag->name )."' id=tag_slug_".$tag->slug."'>".$tag->name."</a>";
		$list .= "</".$tag.">";
	}

	if ( $format == 'ul' || $format == 'ol' )
		$list .= "</".$format.">";

	echo apply_filters( 'gb_list_categories', $list, $format );
}