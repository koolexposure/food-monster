<?php
	get_header();
	?>
	<div id="account" class="container prime main clearfix">
		
		<div id="content" class="vouchers clearfix">
			
			<div class="page_title clearfix">
				<h1 class="main_heading gb_ff"><span class="title_highlight"><?php gb_voucher_page_title() ?></span></h1>
			</div>
			
			<?php
			global $wp_query;
			$purchases = gb_get_purchased_deals_with_vouchers();
			if ( $purchases ) {
				$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
				$args=array(
					'post_type' => gb_get_deal_post_type(),
					'post__in' => $purchases,
					'post_status' => 'any',
					'posts_per_page' => 5, // return this many
					'paged' => $paged
				);
				$deals = new WP_Query($args);
				while ($deals->have_posts()) : $deals->the_post();

					$dealID = get_the_ID();

					?>
					
					<div class="voucher_post dash_section clearfix">
				       
						<h2 class="section_heading background_alt gb_ff"><a href="<?php echo get_permalink($dealID); ?>" title="<?php the_title(); ?>"><?php echo get_the_title(); ?></a></h2>
						
						<div class="voucher_left">
						
							<div class="voucher_thumb">
								<p><?php the_post_thumbnail('gbs_200x150'); ?></p>
							</div>
							
							
							<p class="all_caps"><a href="<?php echo get_permalink($dealID); ?>" title="<?php the_title(); ?>" class="button"><?php gb_e('View Deal') ?></a></p>
							
							
			       			<?php if (gb_has_merchant_name()): ?>
								<br/>
								<p class="merchant_link font_xx_small all_caps"><a href="<?php gb_merchant_url($dealID) ?>" class="button"><?php gb_e('Merchant Info') ?></a></p>
							<?php endif ?>

						</div>
						<div class="voucher_table_wrap">
																						
							<table class="purchase_table vouchers_table gb_table purchases">
								<thead>
									<tr>
										<th class=""><?php gb_e('Code'); ?></th>
										<th class="th_status"><?php gb_e('Status'); ?></th>
										<th class=""><?php gb_e('Download'); ?></th>
										<th class="th_expires"><?php gb_e('Expires'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									$vouchers= null;
									$args=array(
										'post_type' => gb_get_voucher_post_type(),
										'post_status' => 'publish',
										'meta_query' => array(
											array(
												'key' => '_voucher_deal_id',
												'value' => get_the_ID(),
												'compare' => '='
											)
										),
										'posts_per_page' => -1, // return this many
									);
									$vouchers = new WP_Query($args);
									if ($vouchers->have_posts()) {
									
										while ($vouchers->have_posts()) : $vouchers->the_post();
										// if on the active page don't show expired deals
										if ( !gb_on_voucher_active_page() || ( gb_on_voucher_active_page() && !gb_is_voucher_claimed( get_the_ID() ) ) ) { // TODO remove this check when ticket #18158 is added to core and filter_voucher_query removes claimed vouchers.
											$voucher = Group_Buying_Voucher::get_instance(get_the_ID());
											$purchase = $voucher->get_purchase();
											?>
											<tr>
												<td>
													<?php gb_voucher_code() ?>
												</td>
												<td class="td_status va-middle">
													<span class="">
														<?php
														if ( gb_has_shipping($dealID)) {
															gb_e('Shipped');
														}
														elseif ( gb_is_voucher_claimed( get_the_ID() ) ) {
														 	printf(gb__('Used %s'),date(get_option('date_format'), gb_get_voucher_claimed()));
														 } else {
															?>	
																<span class="clearfix">
																	<a href="#" rel="<?php the_ID() ?>" class="voucher_mark_redeemed alt_button"><?php gb_e('Mark as used') ?></a>
																</span>
															<?php
														}
													?>
													</span>
												</td>
												<td class="va-middle">
													<?php if ( gb_is_voucher_claimed( get_the_ID() )): ?>
														<span class="clearfix">
														  <span class="button"><?php gb_e('Redeemed') ?></span>
														</span>
													<?php else: ?>
														<?php gb_voucher_link() ?>
													<?php endif ?>
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
										} // End if claimed check
										endwhile;
									}	
									?>
								</tbody>
							</table>

						</div><!-- End .my_deals_details -->
					
					</div>
					<?php
				endwhile;
				if (  $deals->max_num_pages > 1 ) :
					?>
					<div id="nav-below" class="navigation clearfix">
						<?php 
							$pages = intval(ceil($deals->found_posts / 5) );
							if ( $paged < $pages ) :  ?>
									<div class="nav-previous"><?php next_posts_link( gb__( '<span class="meta-nav">&larr;</span> Older purchases' ) ); ?></div>
						<?php endif ?>
						<div class="nav-next"><?php previous_posts_link( gb__( 'Latest purchases <span class="meta-nav">&rarr;</span>' ) ); ?></div>
					</div><!-- #nav-below -->
					<?php
				endif;
			} else {
				?>
					<p><?php gb_e('No vouchers found.'); ?></p>
				<?php
			} ?>
		</div><!-- #content -->

		<div class="sidebar">

			<?php get_template_part( 'inc/account-sidebar' ); ?>
			<?php dynamic_sidebar( 'account-sidebar' ); ?>

		</div>
			
		</div><!-- #content_wrap -->	
		
	</div><!-- #single_deal -->
	
<?php get_footer(); ?>