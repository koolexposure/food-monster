<div class="checkout_block left_form billing_info clearfix">

	<div class="paymentform_info">
		<h2 class="table_heading section_heading background_alt font_medium gb_ff"><?php gb_e('Your Shipping Information'); ?></h2>
	</div>
	
	<fieldset id="gb-shipping">
		<table class="shipping">
			<tbody>
				<?php foreach ( $fields as $key => $data ): ?>
					<tr>
						<?php if ( $data['type'] != 'checkbox' ): ?>
							<td><?php gb_form_label($key, $data, 'shipping'); ?></td>
							<td><?php gb_form_field($key, $data, 'shipping'); ?></td>
						<?php else: ?>
							<td colspan="2">
								<label for="gb_shipping_<?php echo $key; ?>"><?php gb_form_field($key, $data, 'shipping'); ?> <?php echo $data['label']; ?></label>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>

</div>