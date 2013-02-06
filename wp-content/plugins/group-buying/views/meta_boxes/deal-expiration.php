<?php do_action( 'gb_meta_box_deal_exp_pre' ) ?>
<p><input type="text" value="<?php echo date( 'm/d/Y G:i', $timestamp ); ?>" name="deal_expiration" id="deal_expiration" /></p>
<p><label for="deal_expiration_never"><input type="checkbox" name="deal_expiration_never" id="deal_expiration_never" <?php checked( $never_expires, TRUE ); ?> <?php if ( gb_has_dynamic_price() == true ) echo 'disabled'; ?>/> <?php gb_e( 'This deal does not expire.' ); ?></label></p>
<p><label for="deal_capture_before_expiration"><input type="checkbox" name="deal_capture_before_expiration" id="deal_capture_before_expiration" <?php checked( $show_vouchers, TRUE ); ?> <?php if ( gb_has_dynamic_price() == true ) echo 'disabled'; ?>/> <?php gb_e( 'Display vouchers and capture payments as soon as the deal tips.' ); ?></label></p>
<?php if ( gb_has_dynamic_price() ): ?>
	<p><small><?php gb_e( 'These options cannot be used in conjunction with dynamic pricing.' ); ?></small></p>
<?php endif ?>
<?php do_action( 'gb_meta_box_deal_exp' ) ?>
