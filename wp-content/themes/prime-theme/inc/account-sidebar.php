<?php 
global $wp;
 ?>

<?php if ( is_user_logged_in() ) : ?>

	<div id="account_menu_sidebar_wrap" class="widget clearfix">
	  	<div class="account_sidebar clearfix">
			<ul class="clearfix">
				<li class="account-link <?php if (gb_on_account_page() ) echo 'current_page' ?>">
					<a href="<?php gbs_account_link(); ?>" class="gb_ff contrast"><?php gb_e('My Account') ?></a>
				</li>
				<li class="purchases_link <?php if (gb_on_voucher_page() ) echo 'current_page' ?>">
					<a href="<?php gb_voucher_url(); ?>" class="gb_ff contrast"><?php gb_e('My Purchases') ?></a>
					<?php if (gb_on_voucher_page()): ?><ul>
						<li class="purchases_link <?php if (gb_on_voucher_active_page()) echo 'current_page' ?>">
							<a href="<?php gb_voucher_active_url(); ?>" class="gb_ff contrast"><?php gb_e('Active Vouchers') ?></a>
						</li>
						<li class="purchases_link <?php if (gb_on_voucher_used_page()) echo 'current_page' ?>">
							<a href="<?php gb_voucher_used_url(); ?>" class="gb_ff contrast"><?php gb_e('Used Vouchers') ?></a>
						</li>
						<li class="purchases_link <?php if (gb_on_voucher_expired_page()) echo 'current_page' ?>">
							<a href="<?php gb_voucher_expired_url(); ?>" class="gb_ff contrast"><?php gb_e('Expired Vouchers') ?></a>
						</li>
					</ul><?php endif ?>
				</li>
				<li class="cart-link <?php if (gb_on_cart_page() ) echo 'current_page' ?>">
					<a href="<?php gb_cart_url(); ?>" class="gb_ff contrast"><?php gb_e('My Cart') ?></a>
				</li>
			</ul>
	
		</div>

		<?php
			// Check account balances for (account) balance and affiliate (credits).
			$balance = gb_get_account_balance( get_current_user_id(), 'balance' );
			$reward_points = gb_get_account_balance( get_current_user_id(), 'points' );
		 ?>

		<?php if ( $balance ): ?>
			<div class="current_balance account_balance prime prime_alt clearfix">
				<?php gb_e('Account Balance:'); ?>
				<span class="current_balance_amount"><?php gb_formatted_money( $balance ); ?></span>	
			</div>
		<?php endif ?>

		<?php if ( $reward_points ): ?>
			<div class="current_balance reward_points prime prime_alt clearfix">
				<?php gb_e('Reward Points:'); ?>
				<span class="current_balance_amount"><?php gb_number_format( $reward_points, FALSE, ',' ); ?></span>	
			</div>	
		<?php endif ?>
	
		<?php if (gb_account_has_merchant()): ?>
	
			<div class="account_sidebar clearfix">
				<ul class="merchant clearfix">
					<li class="merchant-link <?php if ( gb_on_merchant_dashboard_page() ) echo 'current_page' ?>">
						<a href="<?php gb_merchant_account_url(); ?>" class="gb_ff contrast"><?php gb_e('Business Dashboard') ?></a>
					</li>
					<li class="submit-link <?php if ( gb_on_deal_submit_page() ) echo 'current_page' ?>">
						<a href="<?php gb_deal_submission_url(); ?>" class="gb_ff contrast"><?php gb_e('Submit Deal') ?></a>
					</li>
				</ul>
	
			</div>
			<div class="current_balance prime prime_alt clearfix">
				<?php gb_e('My Sales:'); ?>
				<span class="current_balance_amount"><?php gb_merchant_total_sold(); ?></span>			
			</div>
		<?php endif; ?>
	</div>
	
<?php endif; ?>