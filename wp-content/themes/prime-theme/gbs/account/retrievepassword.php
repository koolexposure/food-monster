<form name="lostpasswordform" id="lostpasswordform" action="<?php echo wp_lostpassword_url(); ?>" method="post" class="registration_layout main_block clearfix">
	<h3 class="register_message message clearfix"><?php gb_e('Please enter your username or the e-mail address you used when signing up.'); ?></h3>
	<table class="collapsable account form-table">
		<tbody>
			<tr>
				<td><label for="user_login"><?php gb_e('Your Username or Email') ?>:</label></td>
				<td><span class="gb-form-field gb-form-field-text gb-form-field-required"><input tabindex="21" type="text" name="user_login" id="user_login" class="text-input" /></span></td>
			</tr>
			<?php do_action('lostpassword_form'); ?>
			<tr>
				<td>
					<input tabindex="22" type="submit" name="wp-submit" id="wp-submit" class="form-submit" value="<?php gb_e('Get New Password'); ?>" tabindex="100" />
				</div><!-- End. right_form -->
			</tr>
		</tbody>
	</table>
</form>
<script type="text/javascript">
jQuery(document).ready(function(){
  	jQuery("#lostpasswordform").validate({
		rules: {
			'user_login': "required"
		  }
	});
	
	jQuery("#user_login").focus();
 });
</script>