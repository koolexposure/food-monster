<?php include 'popupbox.php' ?>
<div id="header_wrap" class="prime boxed_prime clearfix">

	<div id="header" class="container clearfix">

		<h1 id="logo" class="clearfix">
			<a href="<?php echo site_url() ?>"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/logo.png"></a>
		</h1>
		
		<div id="navigation" class="container gb_ff clearfix">

			<div id="main_navigation" class="hor_navigation clearfix">
				<?php wp_nav_menu( array( 'sort_column' => 'menu_order', 'theme_location' => 'header', 'depth' =>'2', 'container' => 'none' ) ); ?>
			</div><!-- #navigation -->

			<?php if ( !is_user_logged_in() ): ?>
				<div id="nav_subscription" class="subscription_form clearfix">
					<span id="subscribe_dd" class="contrast"><?php gb_e( 'Get the Latest Deals' ) ?></span>
					<div id="subscription_form_wrap" class="cloak">
						<?php gb_subscription_form(); ?>
					</div>
				</div><!-- #header_subscription.subscription_form -->
			<?php endif ?>

		</div><!-- #navigation -->

		<div class="header_meta">
			<?php $locations = gb_get_locations();
			if ( !empty( $locations ) && !is_wp_error( $locations ) ) : ?>
				<div id="location">
					<div class="header-locations-drop-link gb_ff">
						<span class="current_location"><?php gb_current_location_extended(); ?></span>

						<div id="locations_header_wrap" class="clearfix cloak header_color font_small">
							<?php gb_list_locations(); ?>
							</div><!-- #locations_header_wrap. -->
						</div>
				</div>
			<?php endif; ?>

			<div id="login_form">
				<div id="login_wrap" class="gb_ff font_small clearfix">
					<?php if ( !is_user_logged_in() ): ?>
<a href="#TB_inline?height=300&width=500&inlineId=popupform" class="thickbox head-login-drop-link"><?php gb_e( 'Become a Food Monster' ) ?></a>


						<?php gb_facebook_button(); ?>
					<?php else: ?>
						<div class="<?php if ( !is_user_logged_in() ) echo 'hide'; ?>">
							<span class="header_name">
								<span class="gravatar"><?php gb_gravatar() ?></span>
								<?php gb_e( 'Hi,' ) ?>
								<a href="<?php gbs_account_link() ?>" class="name" title="<?php gb_e( 'Your Account' ) ?>"><?php gb_name() ?></a>
							</span>
							<span class="header_cart"><a href="<?php gb_cart_url() ?>"><?php gb_e( 'Your Cart' ) ?><span class="cart_count"> (<?php gb_cart_item_count() ?>)</span></a></span> | <?php gb_logout_url(); ?>
						</div>
					<?php endif ?>

				</div><!-- #login_wrap -->
			</div>

		</div>

	</div><!-- #header -->

</div><!-- #header_wrap -->


