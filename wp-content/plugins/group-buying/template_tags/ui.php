<?php

/**
 * GBS Theme Template Functions
 *
 * @package GBS
 * @subpackage Theme
 * @category Template Tags
 */

//////////////////
// Today's deal //
//////////////////

/**
 * Is this deal today's deal
 * @param integer $post_id Deal ID
 * @return boolean
 */
function gb_is_todays_deal( $post_id = 0 ) {
    if ( !$post_id ) {
        global $post;
        $post_id = $post->ID;
    }
    $bool = FALSE;
    if ( isset( $_GET['todays_deal'] ) && 1 == $_GET['todays_deal'] ) {
        // TODO actually check against todays deal query
        $bool = TRUE;
    }
    return apply_filters( 'gb_is_todays_deal', $bool, $post_id );
}

/**
 * Today's deal URL
 * @return string
 */
function gb_get_todays_deal_url() {
    return apply_filters( 'gb_get_todays_deal_url', site_url( gb_get_todays_deal_path() ) );
}

/**
 * Today's deal path option
 * @return string
 */
function gb_get_todays_deal_path() {
    return apply_filters( 'gb_get_todays_deal_path', Group_Buying_UI::$todays_deal_path );
}


////////////////
// Truncation //
////////////////

/**
 * Truncate a string, strip tags and append a more link
 * @param string  $text           string to truncate
 * @param integer $excerpt_length output length
 * @param boolean $more_link      add a more link
 * @return string                  truncated string w or w/o more link
 */
function gb_get_truncate( $text, $excerpt_length = 44, $more_link = false ) {

    $text = strip_shortcodes( $text );

    $text = apply_filters( 'the_excerpt', $text );
    $text = str_replace( ']]>', ']]&gt;', $text );
    $text = strip_tags( $text, '<a><strong><em><b><del><i><font><cite>' );

    $words = explode( ' ', $text, $excerpt_length + 1 );
    if ( count( $words ) > $excerpt_length ) {
        array_pop( $words );
        $text = implode( ' ', $words );
        $text = rtrim( $text );
        $text .= '&hellip;';
    }
    if ( $more_link ) {
        $text = $text.' '.'<a href="'.$more_link.'" class="more">&nbsp;&raquo;</a>';
    } else {
        $text .= '...';
    }
    return apply_filters( 'gb_get_truncate', $text, $excerpt_length, $more_link );
}

/**
 * Echo the truncated string
 * @see gb_get_truncate()
 * @param string  $text           string to truncate
 * @param integer $excerpt_length output length
 * @param boolean $more_link      add a more link
 * @return string                  truncated string w or w/o more link
 */
function gb_truncate( $text, $excerpt_length = 44, $more_link = false ) {
    echo apply_filters( 'gb_truncate', gb_get_truncate( $text, $excerpt_length, $more_link ) );
}

/**
 * Truncate a posts (deal, merchant, etc.) excerpt.
 * @param integer  $len    Length excerpt will be truncated to
 * @param integer $post_id Post ID (Optional)
 * @return string
 */
function gb_get_excerpt_char_truncation( $len = 200, $post_id = 0 ) {
    if ( !$post_id ) {
        global $post;
        $post_id = $post->ID;
    }
    $post = get_post( $post_id );
    $excerpt = $post->post_excerpt;
    if (  empty( $excerpt ) ) {
        $excerpt = $post->post_content;
    }
    $new_excerpt = substr( $excerpt, 0, $len ); //truncate excerpt according to $len
    if ( strlen( $new_excerpt ) < strlen( $excerpt ) ) {
        $new_excerpt = $new_excerpt.'...';
    }
    return apply_filters( 'gb_get_excerpt_char_truncation', $new_excerpt, $len, $post_id );
}

/**
 * Print a truncated excerpt for a post (deal, merchant, etc.).
 * @see gb_get_excerpt_char_truncation()
 * @param integer  $len    Length excerpt will be truncated to
 * @param integer $post_id Post ID (Optional)
 * @return string
 */
function gb_excerpt_char_truncation( $len = 200, $post_id = 0 ) {
    echo apply_filters( 'gb_excerpt_char_truncation', gb_get_excerpt_char_truncation( $len, $post_id ) );
}

/**
 * Truncate a posts (deal, merchant, etc.) title.
 * @param integer  $len    Length title will be truncated to
 * @param integer $post_id Post ID (Optional)
 * @return string
 */
function gb_get_title_char_truncation( $len = 50, $post_id = 0 ) {
    if ( !$post_id ) {
        global $post;
        $post_id = $post->ID;
    }
    $title = get_the_title( $post_id );
    $new_title = gb_get_html_truncation( $len, $title );
    if ( strlen( $title ) != strlen( $new_title ) ) {
        $new_title .= '...';
    }
    return apply_filters( 'gb_title_char_truncation', $new_title, $len, $post_id );
}

/**
 * Print a truncated title for a post (deal, merchant, etc.).
 * @see gb_get_title_char_truncation()
 * @param integer  $len    Length excerpt will be truncated to
 * @param integer $post_id Post ID (Optional)
 * @return string
 */
function gb_title_char_truncation( $len = 200, $post_id = 0 ) {
    echo apply_filters( 'gb_title_char_truncation', gb_get_title_char_truncation( $len, $post_id ) );
}

/**
 * Truncate some HTML while not counting HTML elements against the max_length
 * @param integer $max_length Length the string will be truncated to
 * @param string $html      HTML to format truncate
 * @return string            truncated string
 */
function gb_get_html_truncation( $max_length, $html ) {
    $printed_length = 0;
    $position = 0;
    $tags = array();
    $out = '';

    while ( $printed_length < $max_length && preg_match( '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;}', $html, $match, PREG_OFFSET_CAPTURE, $position ) ) {
        list( $tag, $tag_position ) = $match[0];

        // Print text leading up to the tag.
        $str = substr( $html, $position, $tag_position - $position );
        if ( $printed_length + strlen( $str ) > $max_length ) {
            $out .= ( substr( $str, 0, $max_length - $printed_length ) );
            $printed_length = $max_length;
            break;
        }

        $out .= ( $str );
        $printed_length += strlen( $str );

        if ( $tag[0] == '&' ) {
            // Handle the entity.
            $out .= ( $tag );
            $printed_length++;
        } else {
            // Handle the tag.
            $tag_name = $match[1][0];
            if ( $tag[1] == '/' ) {
                // This is a closing tag.

                $openingTag = array_pop( $tags );
                assert( $openingTag == $tag_name ); // check that tags are properly nested.

                $out .= ( $tag );
            } else if ( $tag[strlen( $tag ) - 2] == '/' ) {
                    // Self-closing tag.
                    $out .= ( $tag );
                } else {
                // Opening tag.
                $out .= ( $tag );
                $tags[] = $tag_name;
            }
        }

        // Continue after the tag.
        $position = $tag_position + strlen( $tag );
    }

    // Print any remaining text.
    if ( $printed_length < $max_length && $position < strlen( $html ) ) {
        $out .= ( substr( $html, $position, $max_length - $printed_length ) );
    }


    // Close any open tags.
    while ( ! empty( $tags ) ) {
        $out .= sprintf( '</%s>', array_pop( $tags ) );
    }

    return apply_filters( 'gb_get_html_truncation', $out, $max_length, $html );
}

/**
 * Print truncated HTML
 * @param integer $max_length Length the string will be truncated to
 * @param string $html      HTML to format truncate
 * @return string            truncated string
 */
function gb_html_truncation( $max_length = 200, $html ) {
    echo apply_filters( 'gb_html_truncation', gb_get_html_truncation( $max_length, $html ) );
}