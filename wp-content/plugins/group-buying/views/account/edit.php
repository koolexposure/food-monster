<?php /**
 * Account edit form; loads panes within this edit form. 
 *
 * @package GBS
 * @subpackage Account
 * @used-by Group_Buying_Accounts_Edit_Profile::view_profile_form()
 */ ?>

<form id="gb_account_edit" class="registration_layout" action="<?php gb_account_edit_url(); ?>" method="post">
	<input type="hidden" name="gb_account_action" value="<?php echo Group_Buying_Accounts_Edit_Profile::FORM_ACTION; ?>" />
	<?php foreach ( $panes as $pane ) {
		echo $pane['body'];
	} ?>
	<?php do_action( 'gb_account_edit_form' ); ?>
</form>
