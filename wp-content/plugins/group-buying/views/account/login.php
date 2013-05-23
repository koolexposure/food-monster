<?php /**
 * Login form.
 *
 * @package GBS
 * @subpackage Account
 * @used-by Group_Buying_Accounts_Login::view_profile()
 */ ?>

<form action="<?php gb_account_login_url(); ?>" method="post" id="gbs_login" class="registration_layout clearfix">

	<h3 class="register_message message clearfix"><a href="<?php gb_account_register_url() ?>" title="Register"><?php gb_e( 'Don&rsquo;t have an account&#63; Register.' ); ?></a></h3>

	<table class="account">
		<tbody>
			<tr>
				<td><label for="log"><?php gb_e( 'Your Username' ) ?>:</label></td>
				<td><span class="gb-form-field gb-form-field-text gb-form-field-required"><input tabindex="11" type="text" name="log" id="log" class="text-input" />
			</span></td>
			</tr>

			<tr>
				<td><label for="pwd"><?php gb_e( 'Your Password' ) ?>:</label></td>
				<td><span class="gb-form-field gb-form-field-text gb-form-field-required"><input tabindex="12" type="password" name="pwd" id="pwd" class="text-input" />
			</span></td>
			</tr>
			<tr>
				<td>
					<?php if ( isset( $args['redirect'] ) ) : ?>
						<input type="hidden" name="redirect_to" value="<?php echo $args['redirect']; ?>" />
					<?php endif; ?>
					<?php echo $args['submit']; ?>
				</td>
				<td>
				<?php wp_nonce_field( 'gb_login_action', 'gb_login' ); ?>
				<?php do_action( 'gbs_login_form_fields' ) ?>
					<label for="rememberme" class="checkbox-label"><input name="rememberme" id="rememberme" type="checkbox" checked="checked" value="forever" /> <?php gb_e( 'Keep Me Signed In' ); ?></label>
				</td>

			</tr>
		</tbody>
	</table>

	<p><a href="<?php echo wp_lostpassword_url(); ?>" title="<?php gb_e( 'Lost password&#63;' ); ?>"><?php gb_e( 'Forgot your password&#63;' ); ?></a></p>

</form>
<script type="text/javascript">
jQuery(document).ready(function(){
  	jQuery("#gbs_login").validate({
		rules: {
			'log': "required",
			'pwd': "required"
		  }
	});
	jQuery("#log").focus();
});
</script>
