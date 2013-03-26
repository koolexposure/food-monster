<div class="updated">
	<p><?php gb_e( '<strong>Do not upgrade unless you have already run this update on a test site and compared the information for validity.</strong> Upgrading from 2.x will convert all of your deals, vouchers and purchases to a whole new format. ' ); ?></p>
</div>

<div class="error">
	<p><?php gb_e( '<strong>Important!</strong> Backup your database before running.' ); ?></p>
</div>

<div class="wrap">
	<h2><?php gb_e( 'Update Group Buying' ); ?></h2>
	<form method="get">
		<input type="hidden" name="action" value="<?php echo Group_Buying_Upgrades::FORM_ACTION; ?>" />
		<input type="hidden" name="page" value="<?php echo Group_Buying_Upgrades::TEXT_DOMAIN . '/' . Group_Buying_Upgrades::MENU_NAME; ?>" />

		<h3>
			<?php gb_e( 'Group Buying has been updated and needs to bring old deals, merchants, purchases, and vouchers up to date.' ); ?>
		</h3>
		<p>
			<?php gb_e( 'Depending on how much data there is, this may take a while.' ); ?>
		</p>
		<?php submit_button( gb__( 'Update' ) ); ?>
	</form>
</div>
