<div id="checkout_login_register_wrap" class="clearfix">

	<div class="paymentform-info">
		<h2 class="section_heading gb_ff"><?php gb_e( 'Sign-up or Sign-in' ); ?></h2>
	</div>

	<div id="checkout_login_register_forms" class="clearfix">
		<div id="checkout_registration_form_wrap" class="checkout_login_block clearfix">

			<div id="checkout_registration_form" class="clearfix">
				<?php print $args['registration_form']; ?>
			</div><!-- #checkout_registration_form.-->

		</div>

		<div id="checkout_login_form_wrap" class="checkout_login_block clearfix">

			<div id="checkout_login_form" class="clearfix">
				<?php print $args['login_form']; ?>
			</div>

		</div>
	</div><!--  .checkout_login_register_forms -->

	<input type="hidden" name="gb_account_action" value="gb_account_register" />
	<input type="hidden" name="gb_login_or_register" value="1" />

</div>
