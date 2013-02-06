<?php do_action( 'gb_meta_box_deal_exp_pre' ) ?>
<p><input type="text" value="<?php echo date( 'm/d/Y G:i', $timestamp ); ?>" name="deal_expiration" id="deal_expiration" /></p>
<p><label for="deal_expiration_never"><input type="checkbox" name="deal_expiration_never" id="deal_expiration_never" <?php checked( $never_expires, TRUE ); ?> <?php if ( gb_has_dynamic_price() == true ) echo 'disabled'; ?>/> <?php self::_e( 'This deal does not expire.' ); ?></label></p>
<p><label for="deal_capture_before_expiration"><input type="checkbox"id="deal_capture_before_expiration" disabled="disabled" checked="checked"> <?php self::_e( 'Vouchers will show immediately after purchase.' ); ?></label></p>
<input type="hidden" name="deal_capture_before_expiration" value="TRUE">
<?php do_action( 'gb_meta_box_deal_exp' ) ?>