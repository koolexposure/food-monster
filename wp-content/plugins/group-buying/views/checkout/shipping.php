<div class="checkout_block left_form clearfix">

	<div class="paymentform-info">
		<h2 class="gb_ff"><?php gb_e( 'Shipping Information' ); ?></h2>
	</div>
	<fieldset id="gb-shipping">
		<table class="shipping">
			<tbody>
				<?php foreach ( $fields as $key => $data ): ?>
					<tr>
						<?php if ( $data['type'] != 'checkbox' ): ?>
							<td><?php gb_form_label( $key, $data, 'shipping' ); ?></td>
							<td><?php gb_form_field( $key, $data, 'shipping' ); ?></td>
						<?php else: ?>
							<td colspan="2">
								<label for="gb_shipping_<?php echo $key; ?>"><?php gb_form_field( $key, $data, 'shipping' ); ?> <?php echo $data['label']; ?></label>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>

</div>
