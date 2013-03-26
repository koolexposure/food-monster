<?php

/**
 * Simple template tag to display posted date for a post
 * @return string 
 */
function gbs_posted_on() {
	printf( gb__( '<span class="sep">Posted on </span><a href="%1$s" title="%2$s" rel="bookmark"><time class="entry-date" datetime="%3$s" pubdate>%4$s</time></a><span class="by-author"> <span class="sep"> by </span> <span class="author vcard"><a class="url fn n" href="%5$s" title="%6$s" rel="author">%7$s</a></span></span>' ),
		esc_url( get_permalink() ),
		esc_attr( get_the_time() ),
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() ),
		esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
		sprintf( esc_attr__( gb__( 'View all posts by %s' ) ), get_the_author() ),
		esc_html( get_the_author() )
	);
}

/**
 * Subscription form
 * @return string 
 */
if ( !function_exists('gb_platinum_subscription_form') ) {
	function gb_platinum_subscription_form() {
		global $wp_query;
		$query_slug = $wp_query->get_queried_object()->slug;
		$current_location = ( !empty( $query_slug ) ) ? $query_slug : $_COOKIE[ 'gb_location_preference' ] ;
	?>
	        <form action="" id="gb_subscription_form" method="post" class="clearfix">

	            <label for="email_address" class="font_large"><?php gb_e( 'Signup to get deals sent to you!' ); ?></label>
	            <input type="text" name="email_address" id="email_address" class="text-input" value="<?php gb_e( 'Enter your email address' ); ?>" onblur="if (this.value == '')  {this.value = '<?php gb_e( 'Enter your email address' ); ?>';}" onfocus="if (this.value == '<?php gb_e( 'Enter your email address' ); ?>') {this.value = '';}">
				<?php
		if ( $current_location != '' ) {
			echo '<input type="hidden" name="deal_location" value="'.$current_location.'" id="deal_location">';
		} else {
			$locations = gb_get_locations( false );
			$no_city_text = get_option( Group_Buying_List_Services::SIGNUP_CITYNAME_OPTION );
			if ( !empty( $locations ) || !empty( $no_city_text ) ) {
	?>
								<span class="option location_options_wrap">
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
			}
		} ?>
	            <input type="hidden" name="single_email" value="" id="single_email">
	            <?php wp_nonce_field( 'gb_subscription' ); ?>
	            <input type="submit" class="submit font_medium" name="gb_subscription" id="gb_subscription" value="<?php gb_e( 'Signup' ); ?>">
	        </form>
	    <?php
	}
}
