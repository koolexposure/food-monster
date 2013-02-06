
<div class="checkout_block right_form clearfix">

	<div class="paymentform_info">
		<h2 class="table_heading section_heading background_alt font_medium gb_ff"><?php gb_e('Gift Options'); ?></h2>
	</div>
	<fieldset id="gb_billing">
		<table class="billing">
			<tbody>
				<?php foreach ( $fields as $key => $data ): ?>
					<tr>
						<?php if ( $data['type'] != 'checkbox' ): ?>
							<td><?php gb_form_label($key, $data, 'gifting'); ?></td>
							<td><?php gb_form_field($key, $data, 'gifting'); ?></td>
						<?php else: ?>
							<td colspan="2">
								<label for="gb_gifting_<?php echo $key; ?>"><?php gb_form_field($key, $data, 'gifting'); ?> <?php echo $data['label']; ?></label>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>

</div>