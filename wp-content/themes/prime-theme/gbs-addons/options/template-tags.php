<?php

////////////////////
// Template based //
////////////////////

/**
 * Get header logo. Uses location meta to return location based header if available.
 *
 * @param string  $location      Location slug
 * @param string  $term_logo_url default logo url
 * @return string                URL to image
 */
if ( !function_exists( 'gb_get_header_logo' ) ) {
	function gb_get_header_logo( $location = null, $term_logo_url = null ) {
		global $wp_query;
		$postID = $wp_query->post->ID;
		$current_location = ( gb_get_current_location_extended() != '' ) ? gb_get_current_location_extended() : FALSE ;

		if ( FALSE != $current_location ) {
			// get the customized img url based on location settings
			$terms = get_the_terms( $postID, gb_get_deal_location_tax() );
			if ( $terms ) {
				foreach ( $terms as $term ) {
					if ( $current_location == $term->name ) {
						$term_logo_url = get_metadata( 'location_terms', $term->term_id, 'logo_image_url', TRUE );
						break; // if we found a match make sure to stop the loop
					}
				}
			}
		}
		// if all that worked let's use the custom image, otherwise default to the config option.
		if ( isset( $term_logo_url ) && $term_logo_url != '' ) {
			$img_url = $term_logo_url;
		} elseif ( gb_get_theme_header_logo() != '' ) {
			$img_url = gb_get_theme_header_logo();
		} else {
			$img_url = get_bloginfo( 'template_directory' ) . "/img/logo.png";
		}
		return apply_filters( 'gb_get_header_logo', $img_url, gb_get_theme_header_logo(), $term_logo_url, $location );
	}
}

/**
 * Print header location URL
 *
 * @param string  $location Location slug
 * @return string
 */
if ( !function_exists( 'gb_header_logo' ) ) {
	function gb_header_logo( $location = null ) {
		echo apply_filters( 'gb_header_logo', gb_get_header_logo( $location ), $location );
	}
}

/**
 * Return the theme header logo option
 * @return string
 */
if ( !function_exists( 'gb_get_theme_header_logo' ) ) {
	function gb_get_theme_header_logo() {
		return apply_filters( 'gb_get_theme_header_logo', get_option( Group_Buying_Theme_UI::HEADER_LOGO_OPTION ) );
	}
}

/**
 * Echo the theme header logo option
 * @return string
 */
if ( !function_exists( 'gb_theme_header_logo' ) ) {
	function gb_theme_header_logo() {
		echo apply_filters( 'gb_theme_header_logo', gb_get_theme_header_logo() );
	}
}

/**
 * Returns the user avatar. Filtered by the facebook integration and uses gravatar as a fallback
 *
 * @param integer $size    the pixel size of the avatar
 * @param integer $user_id User ID
 * @param string  $default default image if user doesn't have an avatar available
 * @return string           img url
 */
if ( !function_exists( 'gb_gravatar' ) ) {
	function gb_gravatar( $size = 18, $user_id = 0, $default = null ) {
		if ( !$user_id ) {
			$user_id = get_current_user_id();
		}
		echo apply_filters( 'gb_gravatar', get_avatar( $user_id, $size, $default ), $user_id, $size, $default );

	}
}


//////////////////////////////////////////
// Theme scripts and stylesheet options //
//////////////////////////////////////////

/**
 * Return force login option
 * @return string|bool
 */
if ( !function_exists( 'gb_force_login_option' ) ) {
	function gb_force_login_option() {
		return get_option( Group_Buying_Theme_UI::FORCE_LOGIN, 'false' );
	}
}

/**
 * Return flavor option
 * @return string
 */
if ( !function_exists( 'gb_get_theme_flavor' ) ) {
	function gb_get_theme_flavor() {
		return apply_filters( 'gb_get_theme_flavor', get_option( Group_Buying_Theme_UI::FLAVOR_OPTION_OPTION ) );
	}
}

/**
 * Echo the flavor option
 * @return string
 */
if ( !function_exists( 'gb_theme_flavor' ) ) {
	function gb_theme_flavor() {
		echo apply_filters( 'gb_theme_flavor', gb_get_theme_flavor() );
	}
}

/**
 * Return custom css option
 * @return string
 */
if ( !function_exists( 'gb_get_theme_custom_css' ) ) {
	function gb_get_theme_custom_css() {
		return apply_filters( 'gb_get_theme_custom_css', get_option( Group_Buying_Theme_UI::CUSTOM_CSS_OPTION ) );
	}
}

/**
 * Echo the custom CSS option
 * @return string
 */
if ( !function_exists( 'gb_theme_custom_css' ) ) {
	function gb_theme_custom_css() {
		echo apply_filters( 'gb_theme_custom_css', gb_get_theme_custom_css() );
	}
}

/**
 * Footer scripts option
 *
 * @return string returns option
 */
if ( !function_exists( 'gb_get_theme_footer_scripts' ) ) {
	function gb_get_theme_footer_scripts() {
		return apply_filters( 'gb_get_theme_footer_scripts', get_option( Group_Buying_Theme_UI::FOOTER_SCRIPT_OPTION ) );
	}
}

/**
 * Prints footer script options
 *
 * @return string echo
 */
if ( !function_exists( 'gb_theme_footer_scripts' ) ) {
	function gb_theme_footer_scripts() {
		echo apply_filters( 'gb_theme_footer_scripts', gb_get_theme_footer_scripts() );
	}
}

/////////////////////
// Content Related //
/////////////////////

/**
 * Function to print the current deals viewed title
 *
 * @return string
 */
if ( !function_exists( 'gb_deals_index_title' ) ) {
	function gb_deals_index_title() {
		if ( is_tax() ) {
			$location = get_query_var( gb_get_deal_location_tax() );
			$category = get_query_var( 'gb_category' );
			$tags = get_query_var( 'gb_tag' );
			if ( !empty( $location ) ) {
				$term = get_term_by( 'slug', $location, gb_get_deal_location_tax() );
			} elseif ( !empty( $category ) ) {
				$term = get_term_by( 'slug', $category, gb_get_deal_cat_slug() );
			} elseif ( !empty( $tags ) ) {
				$term = get_term_by( 'slug', $tags, gb_get_deal_tag_slug() );
			}
			$title = $term->name . ' ' . gb__( 'Deals' );
		}
		if ( empty( $title ) ) {
			$title = gb__( 'Current Deals' );
		}
		echo apply_filters( 'gb_deals_index_title', $title );
	}
}


///////////
// Links //
///////////

/**
 * Returns the feed link for the current viewed location
 *
 * @param string  $context context the feed should use. E.g. location slug.
 * @return string          feed link
 */
if ( !function_exists( 'gbs_feed_link' ) ) {
	function gbs_feed_link( $context = '' ) {
		if ( empty( $context ) ) {
			$context = $_COOKIE[ 'gb_location_preference' ];
		}
		if ( !empty( $context ) && !is_home() && !is_front_page() ) {
			if ( gb_using_permalinks() ) {
				global $wp_rewrite;
				$rewrite_prestructure = $wp_rewrite->front;
				if ( !empty( $rewrite_prestructure ) ) {
					return site_url( $rewrite_prestructure . 'deals/' . $context . '/feed/' );
				}
				$feed_link = gb_get_deals_link() . '/feed/';
			} else {
				$feed_link = add_query_arg( array( 'deals' => $context, 'post_type' => gb_get_deal_post_type() ), get_bloginfo( 'rss2_url' ) );
			}
		} else {
			$feed_link = get_bloginfo( 'rss2_url' );
		}
		return apply_filters( 'gbs_feed_link', $feed_link, $context );
	}
}

/**
 * Return twitter option
 * @return string
 */
if ( !function_exists( 'gb_get_theme_twitter' ) ) {
	function gb_get_theme_twitter() {
		return apply_filters( 'gb_get_theme_twitter', get_option( Group_Buying_Theme_UI::TWITTER_OPTION ) );
	}
}

/**
 * print the twitter url option
 * @return string
 */
if ( !function_exists( 'gb_theme_twitter' ) ) {
	function gb_theme_twitter() {
		echo apply_filters( 'gb_theme_twitter', gb_get_theme_twitter() );
	}
}

/**
 * Return facebook url option
 * @return string
 */
if ( !function_exists( 'gb_get_theme_facebook' ) ) {
	function gb_get_theme_facebook() {
		return apply_filters( 'gb_get_theme_facebook', get_option( Group_Buying_Theme_UI::TWITTER_OPTION ) );
	}
}

/**
 * Print the facebook options option
 * @return string
 */
if ( !function_exists( 'gb_theme_facebook' ) ) {
	function gb_theme_facebook() {
		echo apply_filters( 'gb_theme_facebook', gb_get_theme_facebook() );
	}
}



///////////////
// Locations //
///////////////

/**
 * Returns the current location for the menu
 *
 * @param string  $return return from object
 * @param boolean $single Is on a single deal page
 * @return string|mixed
 */
if ( !function_exists( 'gb_get_current_location_extended' ) ) {
	function gb_get_current_location_extended( $return = 'name', $single = TRUE ) {
		$return = ( $return == 'id' ) ? 'term_id' : strtolower( $return ) ;
		$gb_get_current_location = gb__( 'Choose your city' );

		if ( isset( $_GET['location'] ) && $_GET['location'] != '' ) {
			$term_object = get_term_by( 'slug', $_GET['location'], gb_get_deal_location_tax() );
		} elseif ( is_tax() ) {
			global $wp_query;
			$term_object = $wp_query->get_queried_object();
		} elseif ( isset( $_COOKIE[ 'gb_location_preference' ] ) && $_COOKIE[ 'gb_location_preference' ] != '' ) {
			$term_object = get_term_by( 'slug', $_COOKIE[ 'gb_location_preference' ], gb_get_deal_location_tax() );
		} elseif ( is_single() && $single ) {
			global $wp_query;
			// Get the post ID
			$post_id = $wp_query->post->ID;
			// Terms array
			$terms = get_the_terms( $post_id, gb_get_location_tax_slug() );
			if ( $terms ) {
				// loop through each term and find current background color and logo img url
				foreach ( $terms as $term ) {
					// if we find a match
					if ( gb_get_current_location_extended( 'term_id', FALSE ) == $term->term_id ) {
						$term_object = $term;
						break;
					}
				}
			}
		}
		if ( is_object($term_object) ) {
			if ( $term_object->taxonomy == gb_get_deal_location_tax() ) {
				$gb_get_current_location = $term_object->$return;
			}
		}
		return apply_filters( 'gb_get_current_location_extended', $gb_get_current_location, $return );
	}
}

/**
 * Prints the current location for the menu
 *
 * @param string  $return return from object
 * @return string|mixed
 */
if ( !function_exists( 'gb_current_location_extended' ) ) {
	function gb_current_location_extended( $return = 'name' ) {
		$get_current_location = gb_get_current_location_extended( $return );
		$current_location = ( $get_current_location != '' ) ? $get_current_location : gb__( 'Choose your city' ) ;
		echo apply_filters( 'gb_current_location_extended', $current_location );
	}
}

/**
 * Used on the homepage to redirect the user to their selected location.
 *
 * @param string  $location_slug Location slug
 * @return void                redirect
 */
if ( !function_exists( 'location_redirect' ) ) {
	function location_redirect( $location_slug = null ) {
		if ( null === $location_slug && isset( $location_cookie ) ) {
			$location_slug = $_COOKIE[ 'gb_location_preference' ];
		}
		if ( isset( $location_slug ) ) {
			if ( gb_using_permalinks() ) {
				$redirect = site_url() . '/deals/' . $location_slug; // TODO Don't hardocdes deals
			} else {
				$redirect = add_query_arg( array( gb_get_deal_tag_slug() => $location_slug ), get_site_url() );
			}
			wp_redirect( apply_filters( 'location_redirection', $redirect, $location_slug ) );
			exit();
		}
	}
}