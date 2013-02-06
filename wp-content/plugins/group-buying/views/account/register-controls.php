<?php /**
 * Registration controls for contact and user information.
 *
 * @package GBS
 * @subpackage Account
 * @used-by Group_Buying_Accounts_Registration::get_panes()
 */ ?>

<?php do_action( 'gb_account_register_form_controls' ); ?>
<div class="account-register-controls">
	<input class="form-submit submit" type="submit" value="<?php gb_e( 'Register' ); ?>" />
</div>
