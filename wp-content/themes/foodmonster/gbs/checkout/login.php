<fieldset id="gb-account-user-info">
	<table class="collapsable form-table">
		<tbody>
			<tr>
				<td><label for="log"><?php gb_e('Your Username') ?>:</label></td>
				<td><span class="gb-form-field gb-form-field-text gb-form-field-required"><input tabindex="11" type="text" name="log" id="log" class="text-input" />
			</span></td>
			</tr>

			<tr>
				<td><label for="pwd"><?php gb_e('Your Password') ?>:</label></td>
				<td><span class="gb-form-field gb-form-field-text gb-form-field-required"><input tabindex="12" type="password" name="pwd" id="pwd" class="text-input" />
			</span></td>
			</tr>
			<tr>
				<td>
					<?php wp_nonce_field('gb_login_action','gb_login'); ?>
					<?php do_action('gbs_login_form_fields') ?>
				</td>
				<td>
					<label for="rememberme" class="checkbox-label"><input name="rememberme" id="rememberme" type="checkbox" checked="checked" value="forever" /> <?php gb_e('Keep Me Signed In'); ?></label>
				</td>
			
			</tr>
		</tbody>
	</table>
</fieldset>

<p><a href="<?php echo wp_lostpassword_url(); ?>" title="<?php gb_e('Lost password&#63;'); ?>"><?php gb_e('Forgot your password&#63;'); ?></a></p>
