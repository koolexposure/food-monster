<?php /**
 * Registration form; loads panes withing this registration form. 
 *
 * @package GBS
 * @subpackage Account
 * @used-by Group_Buying_Accounts_Registration::view_registration_form()
 */ ?>

<form id="gb_account_register" class="registration_layout"  action="<?php gb_account_register_url(); ?>" method="post" enctype="multipart/form-data">
	<input type="hidden" name="gb_account_action" value="<?php echo Group_Buying_Accounts_Registration::FORM_ACTION; ?>" />
	<?php foreach ( $panes as $pane ) {
	echo $pane['body'];
} ?>
	<?php if ( isset( $args['redirect'] ) ) : ?>
		<input type="hidden" name="redirect_to" value="<?php echo $args['redirect']; ?>" />
	<?php elseif ( isset( $_REQUEST['redirect_to'] ) ) : ?>
		<input type="hidden" name="redirect_to" value="<?php echo $_REQUEST['redirect_to']; ?>" />
	<?php endif; ?>
	<?php do_action( 'gb_account_register_form' ); ?>
</form>
<script type="text/javascript">
jQuery(document).ready(function(){
  	jQuery("#gb_account_register").validate({
		rules: {
			'gb_user_login': "required",
		    'gb_user_email': "required",
		    'gb_user_password': "required",
		    'gb_user_password2': "required",
		    'gb_contact_first_name': "required",
		    'gb_contact_last_name': "required",
		    'gb_contact_street': "required",
		    'gb_contact_city': "required",
		    'gb_contact_zone': "required",
		    'gb_contact_postal_code': "required",
		    'gb_contact_country': "required"
		  }
	});
	jQuery("#gb_user_login").focus();
});
</script>
