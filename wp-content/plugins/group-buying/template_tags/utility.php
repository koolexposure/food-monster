<?php

/**
 * GBS Utility Template Functions
 *
 * @package GBS
 * @subpackage Utility
 * @category Template Tags
 */

/**
 * A wrapper around WP's __() to add the plugin's text domain
 * @see Group_Buying_Controller::__()
 * @param string  $string
 * @return string|void
 */
function gb__( $string ) {
	return Group_Buying_Controller::__( $string );
}

/**
 * A wrapper around WP's _e() to add the plugin's text domain
 * @see Group_Buying_Controller::_e()
 * @param string  $string
 * @return void
 */
function gb_e( $string ) {
	return Group_Buying_Controller::_e( $string );
}

/**
 * is the site forcing logins
 * @see Group_Buying_Controller::login_required()
 * @return boolean 
 */
function gb_login_required() {
	return Group_Buying_Controller::login_required();
}

/**
 * Set a global GBS message
 * @param string $message message to display
 * @param string $type    info or error
 * @return void          
 */
function gb_set_message( $message = '', $type = 'info' ) {
	$type = ( $type == 'error' ) ? Group_Buying_Controller::MESSAGE_STATUS_ERROR : Group_Buying_Controller::MESSAGE_STATUS_INFO ;
	Group_Buying_Controller::set_message( $message, $type );
}

/**
 * Display GBS global messages. 
 * 
 * @param  string  $type Type of message (info, error)
 * @param  boolean $ajax If TRUE, the GBS messages will be loaded via AJAX. This requires that gb_display_messages be wrapped with a div (preferably hidden via CSS), since the default script will try to slide the parent class out of the way after it's displayed for 5 seconds.
 * 
 * The filter 'gb_display_messages_via_ajax' has been added to easily allow AJAX messaging
 *   add_filter( 'gb_display_messages_via_ajax', '__return_true()' );
 * 
 * Required Setup:
 * 	* gb_display_messages() to be wrapped with element (i.e. div, span, p)
 * 	* Remove GBS filter that injects messaging into the loop start
 * 	    remove_action( 'loop_start', array( 'Group_Buying_Controller', 'do_loop_start' ) );
 *
 * @return string        formatted messages
 */
function gb_display_messages( $type = null, $ajax = FALSE ) {

	if ( apply_filters( 'gb_display_messages_via_ajax', $ajax ) ) {
		$success = apply_filters( 'gb_display_messages_success_js', "$('#gb_ajax_messages').parent().has('.gb-message').show().delay(5000).slideUp();" );
		?>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$.ajax({
			url: <?php echo admin_url('admin-ajax.php'); ?>,
			type: "POST",
			data: {
				action: 'gb_display_messages',
				gb_message_type: '<?php echo $type ?>'
			},
			success: function(result){
				$("#gb_ajax_messages").append(result);
				<?php echo $success; ?>
			}
		});
	});
</script>
<div id="gb_ajax_messages"></div><!-- #gb_ajax_messages -->
		<?php
	} else {
		Group_Buying_Controller::display_messages( $type );
	}
	
}

/**
 * Is the site using permalinks
 * @see Group_Buying_Controller::using_permalinks()
 * @return boolean
 */
function gb_using_permalinks() {
	return Group_Buying_Controller::using_permalinks();
}

/**
 * Get GBS admin url 
 * @param  string $section Section
 * @return string          URL
 */
function gb_admin_url( $section = NULL ) {
	$admin_url = admin_url('admin.php?page=group-buying/'.$section);
	return untrailingslashit($admin_url);
}

/**
 * Convert string to a number format
 * @param integer $value         number to format
 * @param string  $dec_point     Decimal
 * @param string  $thousands_sep Thousand separator
 * @return string                 
 */
function gb_get_number_format( $value = 1, $dec_point = '.' , $thousands_sep = '' ) {
	$fraction = ( is_null($dec_point) || !$dec_point ) ? 0 : 2 ;
	return apply_filters( 'gb_get_number_format', number_format( floatval( $value ), $fraction, $dec_point, $thousands_sep ) );
}
function gb_number_format( $value = 1, $dec_point = '.' , $thousands_sep = '', $fraction = 2 ) {
	echo apply_filters( 'gb_number_format', gb_get_number_format( $value, $dec_point, $thousands_sep ) );
}

/**
 * Get Current Location for theme display
 *
 * @return string
 */
function gb_get_current_tax( $slug = false ) {
	global $wp_query;
	if ( $slug ) {
		return apply_filters( 'gb_get_current_tax', $wp_query->get_queried_object()->slug );
	} else {
		return apply_filters( 'gb_get_current_tax', $wp_query->get_queried_object()->name );
	}
}
function gb_current_tax() {
	echo apply_filters( 'gb_current_tax', gb_get_current_tax() );
}

/**
 * Utility to return the $_COOKIE var
 * @param string $cookie_name cookie name to retrieve
 * @return array              $_COOKIE
 */
function gb_get_cookie( $cookie_name = 'gb_deals_site' ) {
	$cookie = '';
	if ( isset( $_COOKIE[ $cookie_name ] ) ) {
		$cookie = $_COOKIE[ $cookie_name ];
	}
	return apply_filters( 'gb_get_cookie', $cookie, $cookie_name );
}

/**
 * Recursive check to see if an array is empty
 * @param array $array array to check
 * @return boolean
 */
function gb_utility_array_empty( $array = array() ) {
	if ( is_array( $array ) ) {
		foreach ( $array as $value ) {
			if ( !gb_utility_array_empty( $value ) ) {
				return false;
			}
		}
	}
	elseif ( !empty( $array ) ) {
		return false;
	}
	return true;
}


/////////////////////
// Developer Tools //
/////////////////////

if ( !function_exists( 'prp' ) ) {
	/**
	 * print_r with a <pre> wrap
	 * @param array $array
	 * @return
	 */
	function prp( $array ) {
		echo '<pre style="white-space:pre-wrap;">';
		print_r( $array );
		echo '</pre>';
	}
}

if ( !function_exists( 'pp' ) ) {
	/**
	 * more elegant way to print_r an array
	 * @return string
	 */
	function pp() {
		$msg = __v_build_message( func_get_args() );
		echo '<pre style="white-space:pre-wrap; text-align: left; '.
			'font: normal normal 11px/1.4 menlo, monaco, monospaced; '.
			'background: white; color: black; padding: 5px;">'.$msg.'</pre>';
	}
	/**
	 * more elegant way to display a var dump
	 * @return string
	 */
	function dp() {
		$msg = __v_build_message( func_get_args(), 'var_dump' );
		echo '<pre style="white-space:pre-wrap;; text-align: left; '.
			'font: normal normal 11px/1.4 menlo, monaco, monospaced; '.
			'background: white; color: black; padding: 5px;">'.$msg.'</pre>';
	}

	/**
	 * simple error logging function
	 * @return [type] [description]
	 */
	function ep() {
		$msg = __v_build_message( func_get_args() );
		error_log( '**: '.$msg );
	}

	/**
	 * utility for ep, pp, dp
	 * @param array $vars
	 * @param string $func function
	 * @param string $sep  seperator
	 * @return void|string
	 */
	function __v_build_message( $vars, $func = 'print_r', $sep = ', ' ) {
		$msgs = array();

		if ( !empty( $vars ) ) {
			foreach ( $vars as $var ) {
				if ( is_bool( $var ) ) {
					$msgs[] = ( $var ? 'true' : 'false' );
				}
				elseif ( is_scalar( $var ) ) {
					$msgs[] = $var;
				}
				else {
					switch ( $func ) {
					case 'print_r':
					case 'var_export':
						$msgs[] = $func( $var, true );
						break;
					case 'var_dump':
						ob_start();
						var_dump( $var );
						$msgs[] = ob_get_clean();
						break;
					}
				}
			}
		}

		return implode( $sep, $msgs );
	}
}

/**
 * Is site authorized
 * @ignore
 * @return boolean
 */
function gb_is_authorized() {
	return Group_Buying_Update_Check::has_stored_api_key();
}