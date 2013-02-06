<?php do_action( 'gb_meta_box_deal_limits_pre' ) ?>
<p>
	<label for="deal_min_purchases"><strong><?php gb_e( 'Minimum Required Purchases' ) ?>:</strong></label>
	<input type="text" id="deal_min_purchases" name="deal_min_purchases" value="<?php print $minimum; ?>" size="5" />
	<br /><small><?php gb_e( 'The number of purchases required before the deal is successfully made' ); ?></small>
</p>
<p>
	<label for="deal_max_purchases"><strong><?php gb_e( 'Maximum Purchases' ) ?>:</strong></label>
	<input type="text" id="deal_max_purchases" name="deal_max_purchases" value="<?php print $maximum; ?>" size="5" />
	<br /><small><?php gb_e( 'The maximum number of purchases allowed for this deal' ); ?></small>
</p>
<p>
	<label for="deal_max_purchases_per_user"><strong><?php gb_e( 'Maximum Purchases per User' ) ?>:</strong></label>
	<input type="text" id="deal_max_purchases_per_user" name="deal_max_purchases_per_user" value="<?php print $max_per_user; ?>" size="5" />
	<br /><small><?php gb_e( 'The maximum number of purchases allowed for this deal for one user' ); ?></small>
</p>
<?php do_action( 'gb_meta_box_deal_limits' ) ?>
