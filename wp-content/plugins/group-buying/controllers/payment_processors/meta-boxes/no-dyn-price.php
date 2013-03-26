<p>
	<label for="deal_base_price"><strong><?php self::_e( 'Price' ); ?>:</strong></label>
	&nbsp;
	<?php gb_currency_symbol();  ?><input id="deal_base_price" type="text" size="5" value="<?php echo $price; ?>" name="deal_base_price" />
</p>

<span class="meta_box_block_divider"></span>
<?php do_action( 'gb_meta_box_deal_price_left', $price, $dynamic_price, $shipping, $shippable, $shipping_dyn, $shipping_mode, $tax, $taxable, $taxrate ) ?>

<?php do_action( 'gb_meta_box_deal_price_right', $price, $dynamic_price, $shipping, $shippable, $shipping_dyn, $shipping_mode, $tax, $taxable, $taxrate ) ?>

<?php do_action( 'gb_meta_box_deal_price', $price, $dynamic_price, $shipping, $shippable, $shipping_dyn, $shipping_mode, $tax, $taxable, $taxrate ) ?>
