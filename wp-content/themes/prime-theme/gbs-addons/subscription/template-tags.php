<?php

/**
 * Get the preferred location based on user cookie
 *
 * @return string location slug
 */
if ( !function_exists( 'gb_get_preferred_location' ) ) {
	function gb_get_preferred_location() {
		if ( isset( $_COOKIE[ 'gb_location_preference' ] ) && $gb_location_preference = $_COOKIE[ 'gb_location_preference' ] ) {
			if ( !term_exists( $gb_location_preference, gb_get_deal_location_tax() ) ) {
				return FALSE;
			}
			return apply_filters( 'gb_get_preferred_location', $gb_location_preference );
		}
		return FALSE;
	}
}

/**
 * Subscription form
 * @param  boolean $show_locations       show the location option
 * @param  string  $select_location_text if showing locations what will the option label be
 * @param  string  $button_text          the form button text
 * @param  boolean $echo                 echo or return
 * @return string                        
 */
function gb_subscription_form( $show_locations = TRUE, $select_location_text = 'Select Location:', $button_text = 'Continue &rarr;', $echo = TRUE ) {
	ob_start();
		?>
		<form action="" id="gb_subscription_form" method="post" class="clearfix">
			<span class="option email_input_wrap clearfix">
				<label for="email_address" class="email_address"><?php gb_e( 'Join today to start getting awesome daily deals!' ); ?></label>
				<input type="text" name="email_address" id="email_address" value="<?php gb_e( 'Enter your email' ); ?>" onblur="if (this.value == '')  {this.value = '<?php gb_e( 'Enter your email' ); ?>';}" onfocus="if (this.value == '<?php gb_e( 'Enter your email' ); ?>') {this.value = '';}" >
			</span>
			<?php
				$locations = gb_get_locations( false );
				$no_city_text = get_option( Group_Buying_List_Services::SIGNUP_CITYNAME_OPTION );
				if ( ( !empty( $locations ) || !empty( $no_city_text ) ) && $show_locations ) {
					?>
						<span class="option location_options_wrap clearfix">
							<label for="locations"><?php gb_e( $select_location_text ); ?></label>
							<?php
								$current_location = null;
								if ( isset( $_COOKIE[ 'gb_location_preference' ] ) && $_COOKIE[ 'gb_location_preference' ] != '' ) {
									$current_location = $_COOKIE[ 'gb_location_preference' ];
								} elseif ( is_tax() ) {
									global $wp_query;
									$query_slug = $wp_query->get_queried_object()->slug;
									if ( isset( $query_slug ) && !empty( $query_slug ) ) {
										$current_location = $query_slug;
									}
								}
								echo '<select name="deal_location" id="deal_location" size="1">';
								foreach ( $locations as $location ) {
									echo '<option value="'.$location->slug.'" '.selected( $current_location, $location->slug ).'>'.$location->name.'</option>';
								}
								if ( !empty( $no_city_text ) ) {
									echo '<option value="notfound">'.esc_attr( $no_city_text ).'</option>';
								}
								echo '</select>';
						?>
						</span>
					<?php
				} ?>
			<?php wp_nonce_field( 'gb_subscription' );?>
			<span class="submit clearfix"><input type="submit" class="button-primary" name="gb_subscription" id="gb_subscription" value="<?php gb_e( $button_text ); ?>"></span>
		</form>
		<?php
	$view = ob_get_clean();
	if ( !$echo ) {
		return apply_filters( 'gb_subscription_form', $view, $show_locations, $select_location_text, $button_text );
	}
	echo apply_filters( 'gb_subscription_form', $view, $show_locations, $select_location_text, $button_text );
}

/**
 * Shortcode to add the subscription form to any post
 * Use [gb_sub]
 */
add_shortcode( 'gb_sub', 'gb_sub_form_shortcode' );

/**
 * Do gb_sub shortcode to show the gb subscription form within a post
 * @param  array $atts Array of attributes pased via shortcode
 * @return string       
 */
function gb_sub_form_shortcode( $atts ) {
	// For the shortcodes
	extract( shortcode_atts( array(
				'show_locations' => TRUE,
				'select_location_text' => 'Select Location:',
				'button_text' => 'Continue &rarr;'
			), $atts ) );
	return gb_subscription_form( $show_locations, $select_location_text, $button_text, FALSE );
}