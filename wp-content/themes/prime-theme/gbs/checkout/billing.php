<div class="checkout_block left_form billing_info clearfix">

	<div class="paymentform_info">
		<h2 class="table_heading section_heading background_alt font_medium gb_ff"><?php gb_e('Your Billing Information'); ?></h2>
	</div>
	<fieldset id="gb_billing">
		<table class="billing">
			<tbody>
				<?php foreach ( $fields as $key => $data ): ?>
					<tr>
						<?php if ( $data['type'] != 'checkbox' ): ?>
							<td><?php gb_form_label($key, $data, 'billing'); ?></td>
							<td><?php gb_form_field($key, $data, 'billing'); ?></td>
						<?php else: ?>
							<td colspan="2">
								<label for="gb_billing_<?php echo $key; ?>"><?php gb_form_field($key, $data, 'billing'); ?> <?php echo $data['label']; ?></label>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>

</div>