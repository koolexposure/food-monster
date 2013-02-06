<?php

///////////////
// Messaging //
///////////////

/**
 * Don't allow GBS to inject messaging into the loop
 */
remove_action( 'loop_start', array( 'Group_Buying_Controller', 'do_loop_start' ) );

//////////////////////////////
// Location redirects, etc. //
//////////////////////////////

// redirect from homepage
add_action( 'pre_gbs_head', 'gb_redirect_from_home' );
// Set location
add_action( 'gb_deal_view', 'gb_set_location_preference' );
add_action( 'gb_deals_view', 'gb_set_location_preference' );

/**
 * Redirect to the latest deal
 * @return null redirect
 */
function gb_redirect_from_home() {

	// Redirect away from almost everything if the force login option is enabled.
	if ( !is_user_logged_in() && gb_force_login_option() != 'false' ) {
		if (
			( is_home() && 'subscriptions' == gb_force_login_option() ) ||
			gb_on_login_page() ||
			gb_on_reset_password_page() ) {
			return;
		} else {
			gb_set_message( 'Force Login Activated, Membership Required.' );
			gb_login_required();
			return;
		}
	}

	// Redirect if the user is logged in or has a location preference.
	if ( is_home() ) {
		if ( is_user_logged_in() || gb_has_location_preference() ) { // redirect for logged in users
			wp_redirect( apply_filters( 'gb_latest_deal_redirect', gb_get_latest_deal_link() ) );
			exit();
		}
	}

}

/**
 * Set the users location preference
 *
 * @param string  $location GB location (taxonomy) slug
 * @return bool|string           Return location added or FALSE
 */
function gb_set_location_preference( $location = null ) {

	$cookie_time = apply_filters( 'gb_set_location_preference_time', time() + 24 * 60 * 60 * 30 );
	// for those special redirects with a query var
	if ( !headers_sent() && isset( $_GET['location'] ) && $_GET['location'] != '' ) {
		setcookie( 'gb_location_preference', $_GET['location'], $cookie_time, '/' );
		do_action( 'gb_set_location_preference', $_GET['location'] );
		return $location;
	}

	if ( null == $location && is_tax() ) {
		global $wp_query;
		$wp_query_ob = $wp_query->get_queried_object();
		if ( $wp_query_ob->taxonomy == gb_get_deal_location_tax() ) {
			$location = $wp_query_ob->slug;
		}
	}
	if ( !headers_sent() && null != $location ) {
		setcookie( 'gb_location_preference', $location, $cookie_time, '/' );
		do_action( 'gb_set_location_preference', $location );
		return $location;
	}
	// TODO Expand this out, location should be set by account.
	return FALSE;
}

/**
 * Get location preference based on cookie
 *
 * @param integer $user_id User ID (currently not used)
 * @return string|FALSE          location or false if no location cookie is set
 */
function gb_get_location_preference( $user_id = null ) {
	// TODO Expand this out, location should be set by account.
	if ( isset( $_COOKIE[ 'gb_location_preference' ] ) && $_COOKIE[ 'gb_location_preference' ] != '' ) {
		return $_COOKIE[ 'gb_location_preference' ];
	}
	return FALSE;
}

/**
 * Does user have a location preference
 *
 * @param integer $user_id User ID (currently not used)
 * @return BOOL             TRUE|FALSE
 */
function gb_has_location_preference( $user_id = null ) {
	if ( FALSE != gb_get_location_preference() ) {
		return TRUE;
	}
	return;
}

///////////////////////
// Filter WP classes //
///////////////////////

// Filter post_class and add the odd_even classes and timestamps for expiration
add_filter ( 'post_class' , 'gb_extend_post_class' );
// Browser classes
add_filter( 'body_class', 'body_browser_classes' );

/**
 * Add custom post classes for deals
 * @see gb_get_days_left()
 * @param  array $classes post_class
 * @return array          array of psot classes
 */
function gb_extend_post_class( $classes ) {
	global $current_class;
	$classes[] = $current_class;
	$current_class = ( $current_class == 'item_odd' ) ? 'item_even' : 'item_odd'; // Reset
	if ( gb_deal_availability() && gb_has_expiration() ) {
		$end_date = gb_get_expiration_date();
		$difference = $end_date - current_time( 'timestamp' );
		if ( floor( $difference/60/60/24 ) >= 1 ) {
			if ( floor( $difference/60/60/24 ) == 1 ) {
				$classes[] = 'day_remaining';
				$classes[] = ( floor( $difference/60/60/24 ) ) . '-day_remaining';
			} else {
				$classes[] = 'days_remaining';
				$classes[] = ( floor( $difference/60/60/24 ) ) . '-days_remaining';
			}
		} elseif ( floor( $difference/60/60 ) >= 1 ) {
			if ( floor( $difference/60/60 ) == 1 ) {
				$classes[] = 'less_hour_remaining'; // Less than an hour remaining
				$classes[] = 'hour_remaining';
				$classes[] = ( floor( $difference/60/60 ) ) . '-hour_remaining';
			} else {
				$classes[] = 'less_day_remaining';
				$classes[] = 'hours_remaining';
				$classes[] = ( floor( $difference/60/60 ) ) . '-hours_remaining';
			}
		} else {
			if ( floor( $difference/60 ) == 1 ) {
				$classes[] = 'less_hour_remaining'; // Less than an hour remaining
				$classes[] = 'minute_remaining';
				$classes[] = ( floor( $difference/60 ) ) . '-minute_remaining';
			} else {
				$classes[] = 'less_hour_remaining'; // Less than an hour remaining
				$classes[] = 'minutes_remaining';
				$classes[] = ( floor( $difference/60 ) ) . '-minutes_remaining';
			}
		}
	}
	return $classes;
}

/**
 * Add browser types to the body class
 *
 * @package default
 * @author Dan Cameron
 */
function body_browser_classes( $classes ) {
	global $is_lynx, $is_gecko, $is_IE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone;

	// Browser classes
	if ( $is_lynx ) $classes[] = 'lynx';
	elseif ( $is_gecko ) $classes[] = 'gecko';
	elseif ( $is_opera ) $classes[] = 'opera';
	elseif ( $is_NS4 ) $classes[] = 'ns4';
	elseif ( $is_safari ) $classes[] = 'safari';
	elseif ( $is_chrome ) $classes[] = 'chrome';
	elseif ( $is_IE ) {
		$classes[] = 'ie';
		//if the browser is IE6
		if ( strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 6' ) ) {
			$classes[] = 'ie6'; //add 'ie6' class to the body class array
		}
		//if the browser is IE7
		if ( strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 7' ) ) {
			$classes[] = 'ie7'; //add 'ie7' class to the body class array
		}
		//if the browser is IE8
		if ( strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 8' ) ) {
			$classes[] = 'ie8'; //add 'ie8' class to the body class array
		}
	}
	elseif ( $is_iphone ) $classes[] = 'iphone';
	else $classes[] = 'unknown';
	return $classes;
}


//////////////
// Comments //
//////////////

/**
 * Template for comments and pingbacks
 * @param  object $comment comment object
 * @param  array $args     
 * @param  integer $depth   depth of comments
 * @return string          
 */
function group_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;

	switch ( $comment->comment_type ) :
	case '' :
?>
				<li class="comment">
					<div id="comment-<?php comment_ID(); ?>" class="comment-wrap clearfix">

						<div class="comment-content">
							<div class="comment-meta">
								<div class="comment-author-avatar avatar">
									<?php if ( function_exists( 'get_avatar' ) ) {
		echo get_avatar( $comment, 55 );
	}; ?>
								</div>
								<div class="reply_link">
									<?php if ( function_exists( 'comment_reply_link' ) ) {
		comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ), $comment, $post );
	}  ?>
								</div>
								<cite><?php comment_author_link() ?></cite>
								<a href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ) ?>" class="comment-permalink"><?php comment_date( 'l, F j Y' ); ?></a><?php edit_comment_link( gb__( '(Edit)' ), '&nbsp;&nbsp;', '' ) ?>
							</div>

						    <div class="comment-text comment-body">
								<?php comment_text() ?>
						        <?php if ( $comment->comment_approved == '0' ) : ?>
						        <p><em><?php gb_e( 'Your comment is awaiting moderation.' ); ?></em></p>
						        <?php endif; ?>
						    </div>
						</div>
					</div>
				</li>
			<?php
	break;
case 'pingback'  :
case 'trackback' :
?>
			<div id="comment-<?php comment_ID(); ?>" class="trackback comment-wrap <?php cfct_comment_class(); ?> clearfix">
				<div class="comment-content">
					<div class="comment-meta">
						<cite><?php comment_author_link() ?></cite>
						<a href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ) ?>" class="comment-permalink"><?php printf( gb__( 'Tracked on %s' ), comment_date() ); ?></a> <?php edit_comment_link( gb__( '(Edit)' ), '&nbsp;&nbsp;', '' ) ?>
					</div>

				    <div class="comment-text">
						<?php str_replace( '[...]', '...', comment_text() ); ?>
				    </div>
				</div>
			</div>
			<?php
	break;
	endswitch;
}