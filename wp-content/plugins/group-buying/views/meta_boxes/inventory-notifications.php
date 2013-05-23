<div class="inventory-notifications">
	<p>
		<label><input id="inventory-notification-toggle" type="checkbox" name="<?php echo Group_Buying_Fulfillment::NOTIFICATION_STATUS_META_KEY; ?>" value="1" <?php checked($notify); ?> /> <?php gb_e('Send low inventory notification'); ?></label>
	</p>
	<div class="inventory-notification-options">
		<p>
			<label for="<?php echo Group_Buying_Fulfillment::NOTIFICATION_LEVEL_META_KEY; ?>"><strong><?php gb_e( 'Inventory Level' ) ?>:</strong></label>
			<input type="text" id="<?php echo Group_Buying_Fulfillment::NOTIFICATION_LEVEL_META_KEY; ?>" name="<?php echo Group_Buying_Fulfillment::NOTIFICATION_LEVEL_META_KEY; ?>" value="<?php print $level; ?>" size="5" />
			<br /><span class="description"><?php gb_e( 'Send a notification when this many are left in stock' ); ?></span>
		</p>
		<p>
			<label for="<?php echo Group_Buying_Fulfillment::NOTIFICATION_RECIPIENT_META_KEY; ?>"><strong><?php gb_e( 'Notification Email' ) ?>:</strong></label>
			<input type="text" id="<?php echo Group_Buying_Fulfillment::NOTIFICATION_RECIPIENT_META_KEY; ?>" name="<?php echo Group_Buying_Fulfillment::NOTIFICATION_RECIPIENT_META_KEY; ?>" value="<?php print $recipient; ?>" size="30" />
			<br /><span class="description"><?php printf(gb__( 'Notifications will be sent to this address. If blank, emails will be sent to %s.' ), esc_html(get_option('admin_email'))); ?></span>
		</p>
	</div>
</div>