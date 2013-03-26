<?php /**
 * View profile page. 
 *
 * @package GBS
 * @subpackage Account
 * @used-by Group_Buying_Accounts::view_profile()
 */ ?>

<div id="gb_account_view">
	<?php foreach ( $panes as $pane ) {
		echo $pane['body'];
	} ?>
	<?php do_action( 'gb_account_view' ); ?>
	<p><a href="<?php gb_account_edit_url(); ?>"><?php gb_e( 'Edit Your Account' ); ?></a></p>
</div>
