<div id="deal_share" class="clearfix">
	
	<?php if ( !gb_is_deal_complete() ): ?>
		
		<?php if ( gb_has_purchase_min() && gb_has_purchases_limit() ): ?>
			<p><?php echo apply_filters( 'gb_message_share_max', sprintf( gb__( 'Help reach the goal of %s buyers (limited quantity of %s) and receive %s reward points when your friends save.' ), gb_get_min_purchases(), gb_get_max_purchases(), gb_get_affiliate_credit() ), gb_get_min_purchases(), gb_get_max_purchases(), gb_get_affiliate_credit() ) ?></p>
		<?php elseif ( gb_has_purchase_min() ): ?>
			<p><?php echo apply_filters( 'gb_message_share', sprintf( gb__( 'Help reach the goal of %s buyers and receive %s reward points when your friends save.' ), gb_get_min_purchases(), gb_get_affiliate_credit() ), gb_get_min_purchases(), gb_get_affiliate_credit() ) ?><?php  ?></p>
		<?php endif ?>

	<?php endif ?>

	<div id="share_link" class="clearfix">
		<?php gb_e( 'Share URL:' ) ?> <input class="share_link" onclick="this.select();" readonly="true" value="<?php gb_share_link(); ?>">
	</div><!-- #share_link -->

</div><!-- #deal_share -->