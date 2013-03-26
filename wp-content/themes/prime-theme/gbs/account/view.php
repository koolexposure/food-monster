<?php 
	$current_user = wp_get_current_user();
	?>
	
<div id="dashboard_container" class="dashboard_container clearfix">
	
	<?php do_action('account_section_before_account') ?>

	<div id="account_settings_section" class="dash_section clearfix">
		<h2 class="section_heading background_alt gb_ff"><?php gb_e('Your Account Settings'); ?> <a class="section_heading_link font_x_small alt_link" href="<?php gb_account_edit_url(); ?>"><?php gb_e('Edit'); ?></a></h2>

		<div class="user_info clearfix">
			<p><span class="contact_title"><?php gb_e('Email:') ?></span> <?php echo $current_user->user_email ?></p>
			<p><span class="contact_title"><?php gb_e('Password:') ?></span> *******</p>
		</div><!-- .user_info -->

		<?php foreach ( $panes as $pane ) {
				echo $pane['body'];
			} ?>
		<?php do_action('account_settings_section') ?>
	</div><!-- #account_settings_section -->

	<?php do_action('account_section_before_biz') ?>

	<div id="biz_section" class="dash_section clearfix">
		<h2 class="section_heading background_alt gb_ff"><?php gb_e('Business Account') ?> <?php if (gb_account_has_merchant()) { ?><a class="section_heading_link font_x_small alt_link"  href="<?php gb_merchant_edit_url(); ?>"><?php gb_e('Edit'); ?></a><?php } ?></h2>
		<?php if (gb_account_has_merchant()): ?>
			<p><span class="contact_title"><?php gb_e('Company Name:') ?></span> <?php echo gb_merchant_name(gb_account_merchant_id()); ?></p>
			<p><span class="contact_title"><?php gb_e('Address:') ?></span> <?php echo gb_merchant_street(gb_account_merchant_id()); ?></p>
			<p><span class="contact_title"><?php gb_e('City:') ?></span> <?php echo gb_merchant_city(gb_account_merchant_id()); ?></p>
			<p><span class="contact_title"><?php gb_e('State:') ?></span> <?php echo gb_merchant_state(gb_account_merchant_id()); ?></p>
			<p><span class="contact_title"><?php gb_e('Zip:') ?></span> <?php echo gb_merchant_zip(gb_account_merchant_id()); ?></p>
			<p><span class="contact_title"><?php gb_e('Country:') ?></span> <?php echo gb_merchant_country(gb_account_merchant_id()); ?></p>
			<p><span class="contact_title"><?php gb_e('Phone:') ?></span> <?php echo gb_merchant_phone(gb_account_merchant_id()); ?></p>
			<p><span class="contact_title"><?php gb_e('Website:') ?></span> <?php echo gb_merchant_website(gb_account_merchant_id()); ?></p>
			<p><span class="contact_title"><?php gb_e('Facebook:') ?></span> <?php echo gb_merchant_facebook(gb_account_merchant_id()); ?></p>
			<p><span class="contact_title"><?php gb_e('Twitter:') ?></span> <?php echo gb_merchant_twitter(gb_account_merchant_id()); ?></p>
			<p><span class="contact_title"><?php gb_e('Business Category:') ?></span> <?php echo gb_merchants_type_url(gb_account_merchant_id()); ?></p>

			<p>
				<?php
					$merchant = get_post(gb_account_merchant_id());
					if ( !empty($merchant->post_content) ) {
						?>
							<h3><?php gb_e('Your Business Description'); ?></h3>
						<?php
						echo apply_filters('the_content', $merchant->post_content );
					}
					?>
			</p>

		<?php else: ?>
			<?php gb_e('Are you a business owner?') ?> <a class="header_edit_link" href="<?php gb_merchant_register_url(); ?>"><?php gb_e('Register your business now.'); ?></a>
		<?php endif ?>
		<?php do_action('account_biz_section') ?>
	</div><!-- #biz_section -->
	
	<?php do_action('account_section_before_dash') ?>

	<div class="dash_section">
		
		<h2 class="section_heading background_alt gb_ff"><?php gb_e('My Purchases Overview'); ?> <a class="section_heading_link font_x_small alt_link"  href="<?php gb_voucher_url() ?>" title="<?php gb_e('Browse all my Deals'); ?>"><?php gb_e('See All&#63;'); ?></a></h2>
		
		<?php

			$vouchers= null;
			$args=array(
				'post_type' => gb_get_voucher_post_type(),
				'post_status' => 'publish',
				'posts_per_page' => 10, // return this many
				
			);
			$vouchers = new WP_Query($args);
			
			if ($vouchers->have_posts()) {
				?>
				<table class="purchase_table vouchers_table gb_table purchases"><!-- Begin .gb_table -->
	
					<thead>
						<tr>
							<th class="purchase_deal_title th_voucher"><?php gb_e('Voucher'); ?></th>
							<th class="th_status"><?php gb_e('Status'); ?></th>
							<th class="th_voucher"><?php gb_e('Download'); ?></th>
							<th class="th_expires"><?php gb_e('Expires'); ?></th>
						</tr>
					</thead>
					
					<tbody>
					<?php
					while ($vouchers->have_posts()) : $vouchers->the_post();
						$dealID = gb_get_vouchers_deal_id();
						?>
						<tr>
							<td class="purchase_deal_title">
								<span class="deal_title clearfix"><?php echo get_the_title($postID) ?></span>
								<?php if (gb_has_merchant_name($dealID)): ?>
									<br/>
									<p class="merchant_link font_xx_small all_caps"><a href="<?php gb_merchant_url($dealID) ?>" class="button contrast_button"><?php gb_e('Merchant Info') ?></a></p>
								<?php endif ?>
							<td class="td_status">
								<?php
									if ( gb_has_shipping($dealID)) {
										gb_e('Shipped');
									}
									elseif ( gb_is_voucher_claimed( get_the_ID() ) ) {
									 	printf(gb__('Used %s'),date(get_option('date_format'), gb_get_voucher_claimed()));
									 } else {
										?>	
											<span class="clearfix">
												<a href="#" rel="<?php the_ID() ?>" class="voucher_mark_redeemed alt_button contrast_button"><?php gb_e('Mark as used') ?></a>
											</span>
										<?php
									}
								?>
							</td>
							<td class="td_voucher">
								<?php if ( gb_is_voucher_claimed( get_the_ID() )): ?>
									<span class="clearfix">
										<span class="button contrast_button"><?php gb_e('Redeemed.') ?></span>
									</span>
								<?php else: ?>
									<?php gb_voucher_link() ?>
								<?php endif ?>
							</td>
							</td>
							<td class="td_expires">
								
								<?php 
									if ( gb_get_voucher_expiration_date() ): ?>
									<?php gb_voucher_expiration_date(); ?>
								<?php else: ?>
									N/A
								<?php endif ?>
							</td>
						</tr>
						<?php
					endwhile;
					?>
					</tbody>
				</table><!-- End .gb_table -->
				<?php if ($vouchers->found_posts > 10): ?>
					<p><?php gb_e('This is a summary of your most recent purchases.'); ?> <a href="<?php gb_voucher_url() ?>" title="Browse all my deals"><?php gb_e('See All&#63;'); ?></a></p>
				<?php endif ?>

				<?php
			} else {
				?>
					<p><?php gb_e('You have not purchased any '); ?><a href="<?php echo gb_get_deals_link() ?>" title="<?php gb_e('Browse Active Deals') ?>"><?php gb_e('Deals'); ?></a>.</p>
				<?php
			}

		?>
		<?php do_action('account_dash_section') ?>
	</div>
</div>