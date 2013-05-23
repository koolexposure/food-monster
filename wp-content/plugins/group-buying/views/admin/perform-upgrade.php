<div class="wrap">
	<h2><?php gb_e( 'Update Group Buying' ); ?></h2>

	<div class="error">
		<p><?php gb_e( 'If the leave this page the update will be run in the background; you will not want to run it again for at least 30 minutes if it ends up not finishing. ' ); ?></p>
	</div>

	<?php Group_Buying_Upgrades::perform_upgrade(); ?>
</div>
