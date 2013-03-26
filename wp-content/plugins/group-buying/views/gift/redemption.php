<form id="gb_gift_redemption" class="registration_layout clearfix" action="<?php gb_gift_redemption_url(); ?>" method="post">
	<input type="hidden" name="gb_gift_action" value="<?php echo Group_Buying_Gifts::FORM_ACTION; ?>" />

<fieldset id="gb-account-contact-info">
	<table class="account">
		<tbody>
			<tr>
				<td><label for="gb_gift_redemption_email"><?php gb_e( 'Email Address' ); ?></label><br/><small>(<?php gb_e( 'The email address the gift was sent to.' ); ?>)</small></td>
				<td><span class="gb-form-field gb-form-field-text gb-form-field-required"><input type="text" value="<?php esc_attr_e( $email ); ?>" id="gb_gift_redemption_email" name="gb_gift_redemption_email" size="40" /></span></td>
			</tr>

			<tr>
				<td><label for="gb_gift_redemption_code"><?php gb_e( 'Coupon Code' ); ?></td>
				<td><span class="gb-form-field gb-form-field-text gb-form-field-required"><input type="text" value="" id="gb_gift_redemption_code" name="gb_gift_redemption_code" size="40" /></span></td>
			</tr>
			<?php do_action( 'gb_gift_redemption_form' ); ?>
		</tbody>
	</table>
</fieldset>

<input type="submit" class="submit" value="<?php gb_e( 'Claim Your Gift' ); ?>" />

</form>
<script type="text/javascript">
jQuery(document).ready(function(){
  	jQuery("#gb_gift_redemption").validate({
		rules: {
			'gb_gift_redemption_email': "required",
			'gb_gift_redemption_code': "required"
		  }
	});
	jQuery("#gb_gift_redemption_email").focus();
});
</script>
