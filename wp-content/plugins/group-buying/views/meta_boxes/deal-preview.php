<?php do_action( 'gb_meta_box_deal_preview_pre' ) ?>
<p><label for="deal_preview"><input type="checkbox" name="deal_preview" id="deal_preview" <?php checked( $deal_preview, '1' ); ?> value="TRUE"/> <?php gb_e( 'Enable Private Previews.' ); ?></label></p>
<?php if ( $deal_preview == 'TRUE' ): ?>
	<p><a href="<?php echo $deal_preview_url ?>" id="voucher_preview" class="button"><?php gb_e( 'Deal Preview' ) ?></a>&nbsp;&nbsp;&nbsp;
<?php endif ?>
<?php if ( !in_array( $post->post_status, array( 'auto-draft' ) ) ) : ?>
	<a href="<?php echo $voucher_preview_url ?>" id="voucher_preview" class="button"><?php gb_e( 'Voucher Preview' ) ?></a></p>
<?php else: ?>
	<a href="javascript:void()" id="voucher_preview" class="casper button"><?php gb_e( 'Voucher Preview' ) ?></a></p>
<?php endif ?>
<?php do_action( 'gb_meta_box_deal_preview' ) ?>
