<script type="text/javascript">
	jQuery(document).ready(function() {
		var $guest_check = jQuery('#gb_guest_purchase_check');
		// Hide the registration option and show the hidden guest checkout below.
		// This showing and hidding allows for browsers without js to still have the option.
		jQuery('[for="gb_user_guest_purchase"]').parent().hide();
		jQuery('#guest_purchase_checkbox_wrap').show();
		// Show and Hide the Register and Login Forms, plus check the 
		$guest_check.bind( 'change', function() {
			jQuery('#checkout_login_register_forms').fadeToggle(); // hide
			jQuery('#gb_user_guest_purchase').attr('checked', this.checked ); // set the value on the hidden guest checkout option
		});
	});
</script>
<div id="checkout_login_register_wrap" class="border_bottom clearfix">
	
	<div class="clearfix">
		<h3 class="main_heading gb_ff"><span class="title_highlight"><?php gb_e('Sign-up, Sign-in or Guest Purchase'); ?></span></h3>
	</div>
	
	<div id="checkout_login_register_forms" class="clearfix">
		<div id="checkout_registration_form_wrap"  class="checkout_block left_form clearfix">
			<div class="paymentform_info">
				<h2 class="table_heading section_heading font_medium gb_ff"><?php _e('Register'); ?></h2>
			</div>
			<div id="checkout_registration_form" class="clearfix">
				<?php print $args['registration_form']; ?>
			</div><!-- #checkout_registration_form.-->
			
		</div>

		<div id="checkout_login_form_wrap"  class="checkout_block right_form clearfix">
			<div class="paymentform_info">
				<h2 class="table_heading section_heading font_medium gb_ff"><?php _e('Login'); ?></h2>
			</div>
			<div id="checkout_login_form" class="clearfix">
				<?php print $args['login_form']; ?>
			</div>
			
		</div>
		<input type="hidden" name="gb_account_action" value="gb_account_register" />
		<input type="hidden" name="gb_login_or_register" value="1" />
	</div><!--  .checkout_login_register_forms -->

	<span id="guest_purchase_checkbox_wrap" class="contrast_light message cloak clearfix">
		<label for="gb_guest_purchase_check">
			<input type="checkbox" name="gb_guest_purchase_check" id="gb_guest_purchase_check"> <?php gb_e('Guest Purchase') ?>
		</label>
	</span>

</div>

