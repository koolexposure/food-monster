<table class="confirmation_table purchase_table" width="100%">
	<thead>
		<tr>
			<th scope="col" colspan="2" class="cart-name gb_ff font_medium"><?php gb_e('Your Order Summary'); ?></th>
			<th scope="col" colspan="1" class="cart-quantity gb_ff font_medium"><?php gb_e('Quantity'); ?></th>
			<th scope="col" colspan="1" class="cart-price gb_ff font_medium"><?php gb_e('Price'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<?php if ( $shipping > 0 ): ?>
			<tr class="cart-line-item">
				<th scope="row" colspan="3"><?php gb_e('Shipping'); ?></th>
				<td class="cart-line-item-shipping">
					<?php gb_formatted_money($shipping); ?>
				</td>
			</tr>
		<?php endif ?>
		<?php if ( $tax > 0 ): ?>
			<tr class="cart-line-item">
				<th scope="row" colspan="3"><?php gb_e('Tax'); ?></th>
				<td class="cart-line-item-tax">
					<?php gb_formatted_money($tax); ?>
				</td>
			</tr>
		<?php endif ?>
		<tr class="cart-line-item">
			<th scope="row" colspan="3"><?php gb_e('Total'); ?></th>
			<td class="cart-line-item-total">
				<?php gb_formatted_money($total); ?>
			</td>
		</tr>
		<tr class="cart-line-item">
			<th scope="row" colspan="3"><?php gb_e('Order Number'); ?></th>
			<td class="cart-line-item-order-number">#<?php echo $order_number; ?></td>
		</tr>
		<?php if ( isset($checkout->cache['affiliate_credits']) && $checkout->cache['affiliate_credits'] > 0 ): ?>
			<tr class="cart-line-item">
				<th scope="row" colspan="3"><?php gb_e('Credits Used'); ?></th>
				<td class="cart-line-item-tax">
					<?php echo gb_get_number_format($checkout->cache['affiliate_credits'],'.',','); ?>
				</td>
			</tr>
		<?php endif ?>
	</tfoot>

	<tbody>
		<?php foreach ($products as $product): ?>
			<tr>
				<td class="purchase_deal_title" colspan="2">
					<?php 
						$deal = Group_Buying_Deal::get_instance($product['deal_id']);
						echo $deal->get_title($product['data']); ?>
				</td>
				<td class="center-align"><?php echo $product['quantity']; ?></td>
				<td class="cart-price"><?php echo gb_formatted_money($product['unit_price']); ?></td>
			</tr>
		<?php endforeach ?>
	</tbody>

</table>
<?php 
	// Build a multidimensional array with the key being the deal id.
	// Test if any vouchers are not yet available.
	$vouchers = gb_get_vouchers_by_purchase_id( $order_number );
	$deal_and_vouchers = array();
	$all_vouchers_active = TRUE;
	if ( !empty( $vouchers ) ) {
		foreach ( $vouchers as $voucher_id ) {
			$deal_id = gb_get_vouchers_deal_id( $voucher_id );
			$deal_and_vouchers[$deal_id][] = $voucher_id;
			if ( !gb_is_voucher_active( $voucher_id ) ) {
				$all_vouchers_active = FALSE;
			}
		}
	}
	// Markup is heavily borrowed from account/view.php
	if ( !empty( $deal_and_vouchers ) ) {
		?>
			<div id="purchase_vouchers" class="clearfix">
				

				<?php if ( Group_Buying_Purchase::POST_TYPE != get_query_var('post_type') ): // Don't show on purchase template ?>

					<?php if ( !is_user_logged_in() || ( function_exists( 'gb_is_user_guest_purchaser' ) && gb_is_user_guest_purchaser() ) ) : // Don't show if the user is logged in and a guest user ?>

						<?php if ( !$all_vouchers_active ): // only show if necessary ?>
							<p class="contrast_light message"><strong><?php gb_e( 'Some of your order is pending.' ); ?></strong>  <br/><?php gb_e( 'Save this url to retrieve your invoice(s) later:' ); ?> <a href="<?php echo $lookup_url; ?>"><?php echo $lookup_url; ?></a></p>
						<?php else: ?>
							<p class="contrast_light message"><?php gb_e( 'Save this url to retrieve your invoice(s) later:' ); ?> <a href="<?php echo $lookup_url; ?>"><?php echo $lookup_url; ?></a></p>
						<?php endif ?>
					<?php endif; ?>
				<?php endif ?>


				<?php foreach ( $deal_and_vouchers as $deal_id => $vouchers ): ?>
					<div class="voucher_post dash_section clearfix">
				       
						<h2 class="section_heading background_alt gb_ff"><a href="<?php echo get_permalink($deal_id); ?>" title="<?php echo get_the_title( $deal_id ); ?>"><?php echo get_the_title( $deal_id ); ?></a></h2>
						
						<div class="voucher_left">
						
							<div class="voucher_thumb">
								<p><?php echo get_the_post_thumbnail( $deal_id, 'gbs_200x150' ); ?></p>
							</div>
							
							
							<p class="all_caps"><a href="<?php echo get_permalink( $deal_id ); ?>" class="button"><?php gb_e('View Deal') ?></a></p>
							
							
			       			<?php if ( gb_has_merchant_name( $deal_id ) ): ?>
								<br/>
								<p class="merchant_link font_xx_small all_caps"><a href="<?php gb_merchant_url( $deal_id ) ?>" class="button"><?php gb_e('Merchant Info') ?></a></p>
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
									<?php foreach ( $vouchers as $voucher_id ): ?>
										<tr>
												<td>
													<?php gb_voucher_code( $voucher_id ) ?>
												</td>
												<td class="td_status va-middle">
													<span class="">
														<?php
														if ( gb_has_shipping( $deal_id ) ) {
															gb_e('Shipped');
														}
														elseif ( gb_is_voucher_claimed( $voucher_id ) ) {
														 	printf( gb__('Used %s'), date( get_option('date_format'), gb_get_voucher_claimed( $voucher_id ) ) );
														 } else {
															?>	
																<span class="clearfix">
																	<a href="#" rel="<?php echo $voucher_id ?>" class="voucher_mark_redeemed alt_button"><?php gb_e('Mark as used') ?></a>
																</span>
															<?php
														}
													?>
													</span>
												</td>
												<td class="va-middle">
													<?php if ( gb_is_voucher_claimed( $voucher_id )): ?>
														<span class="clearfix">
														  <span class="button"><?php gb_e('Redeemed') ?></span>
														</span>
													<?php elseif ( gb_is_voucher_active( $voucher_id ) ): ?>
														<a href="<?php gb_voucher_permalink( $voucher_id, TRUE ) ?>" title="<?php gb_e( 'Download Voucher' ) ?>" class="button voucher_download"><?php gb_e( 'Download' ) ?></a>
													<?php else: ?>
														<span class="clearfix">
															<span class="alt_button"><?php gb_e('Pending') ?></span>
														</span>
													<?php endif ?>
												</td>
												<td class="td_expires">
													
													<?php 
														if ( gb_get_voucher_expiration_date( $voucher_id ) ): ?>
														<?php gb_voucher_expiration_date( $voucher_id ); ?>
													<?php else: ?>
														N/A
													<?php endif ?>
												</td>
											</tr>
									<?php endforeach ?>
								</tbody>
							</table>

							<div class="social_buttons clearfix">
								<span class='st_facebook_large' displayText='Facebook' st_url="<?php gb_share_link( $deal_id ) ?>"></span>
								<span class='st_twitter_large' displayText='Tweet' st_url="<?php gb_share_link( $deal_id ) ?>"></span>
								<span class='st_pinterest_large' displayText='Pinterest' st_url="<?php gb_share_link( $deal_id ) ?>"></span>
								<span class='st_sharethis_large' displayText='ShareThis' st_url="<?php gb_share_link( $deal_id ) ?>"></span>
								<span class='st_email_large' displayText='Email' st_url="<?php gb_share_link( $deal_id ) ?>"></span>
							</div><!-- #label -->

						</div><!-- End .my_deals_details -->
					
					</div>
				<?php endforeach ?>
			</div><!-- #purchase_vouchers.-->
		<?php
	}
 ?>
