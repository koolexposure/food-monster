<?php
// default add-to-cart is being called directly from the single.php template. This is for attributes only.
if ( function_exists('gb_deal_has_attributes') && gb_deal_has_attributes() ) { // if this deal has attributes
	?>
	<form class="add-to-cart" method="post" action="<?php gb_add_to_cart_action(); ?>">
		<?php foreach ( $fields as $field ): ?>
			<?php echo $field; ?>
		<?php endforeach; ?>
		<input class="deal_purchase button submit" type="submit" value="<?php gb_e($button_text); ?>" />
	</form>
	<?php
}