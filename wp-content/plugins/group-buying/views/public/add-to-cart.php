<form class="add-to-cart" method="post" action="<?php gb_add_to_cart_action(); ?>">
	<?php foreach ( $fields as $field ): ?>
		<?php echo $field; ?>
	<?php endforeach; ?>
	<input type="submit" value="<?php gb_e( $button_text ); ?>" />
</form>
