<form id="gb_cart" action="<?php gb_cart_url(); ?>" method="post">
	<table class="cart gb_table purchase-table">
		<thead>
			<tr>
			<?php foreach ( $columns as $key => $label ): ?>
				<th class="cart-<?php esc_attr_e( $key ); ?>" scope="col"><?php esc_html_e( $label ); ?></th>
			<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $items as $item ): ?>
				<tr class="cart-line-item-row-<?php esc_attr_e( $key ); ?>">
					<?php foreach ( $columns as $key => $label ): ?>
						<td class="cart-<?php esc_attr_e( $key ); ?>">
							<?php if ( isset( $item[$key] ) ) { echo $item[$key]; } ?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			<?php foreach ( $line_items as $key => $line ): ?>
				<tr class="cart-line-item cart-line-item-row-<?php esc_attr_e( $key ); ?>">
					<th scope="row" colspan="<?php echo count( $columns )-1; ?>"><?php esc_html_e( $line['label'] ); ?></th>
					<td class="cart-line-item-<?php esc_attr_e( $key ); ?>"><?php esc_html_e( $line['data'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<div class="cart-controls">
		<?php foreach ( $cart_controls as $control ): ?>
			<?php echo $control; ?>
		<?php endforeach; ?>
	</div>
</form>
