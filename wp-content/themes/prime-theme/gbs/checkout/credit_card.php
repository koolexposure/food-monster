<div class="checkout_block right_form clearfix">

	<div class="paymentform_info">
		<h2 class="table_heading section_heading background_alt font_medium gb_ff"><?php gb_e('Payment Information'); ?></h2>
	</div>
	<fieldset id="gb-credit-card">
		<table class="credit-card">
			<tbody>
				<?php foreach ( $fields as $key => $data ): ?>
					<?php if ( $data['weight'] < 1 && !in_array($key, array('cc_name', 'cc_number', 'cc_expiration_month', 'cc_expiration_year', 'cc_cvv')) ): ?>
					<tr>
						<?php if ( $data['type'] != 'checkbox' ): ?>
							<td><?php gb_form_label($key, $data, 'credit'); ?></td>
							<td><?php gb_form_field($key, $data, 'credit'); ?></td>
						<?php else: ?>
							<td colspan="2">
								<label for="gb_credit_<?php echo $key; ?>"><?php gb_form_field($key, $data, 'credit'); ?> <?php echo $data['label']; ?></label>
							</td>
						<?php endif; ?>
					</tr>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php if ( $fields['cc_name'] ): ?>
					<tr class="gb_credit_card_field_wrap">
						<td><?php gb_form_label('cc_name', $fields['cc_name'], 'credit'); ?></td>
						<td><?php gb_form_field('cc_name', $fields['cc_name'], 'credit'); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $fields['cc_number'] ): ?>
					<tr class="gb_credit_card_field_wrap">
						<td><?php gb_form_label('cc_number', $fields['cc_number'], 'credit'); ?></td>
						<td><?php gb_form_field('cc_number', $fields['cc_number'], 'credit'); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $fields['cc_expiration_month'] && $fields['cc_expiration_year'] ): ?>
					<tr class="gb_credit_card_field_wrap">
						<td><?php gb_form_label('cc_expiration_year', $fields['cc_expiration_year'], 'credit'); ?></td>
						<td><?php gb_form_field('cc_expiration_month', $fields['cc_expiration_month'], 'credit'); ?> <?php gb_form_field('cc_expiration_year', $fields['cc_expiration_year'], 'credit'); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $fields['cc_cvv'] ): ?>
					<tr class="gb_credit_card_field_wrap">
						<td><?php gb_form_label('cc_cvv', $fields['cc_cvv'], 'credit'); ?></td>
						<td><?php gb_form_field('cc_cvv', $fields['cc_cvv'], 'credit'); ?></td>
					</tr>
				<?php endif; ?>
				<?php foreach ( $fields as $key => $data ): ?>
					<?php if ( $data['weight'] > 1 && !in_array($key, array('cc_name', 'cc_number', 'cc_expiration_month', 'cc_expiration_year', 'cc_cvv')) ): ?>
					<tr>
						<?php if ( $data['type'] != 'checkbox' ): ?>
							<td><?php gb_form_label($key, $data, 'credit'); ?></td>
							<td><?php gb_form_field($key, $data, 'credit'); ?></td>
						<?php else: ?>
							<td colspan="2">
								<label for="gb_credit_<?php echo $key; ?>"><?php gb_form_field($key, $data, 'credit'); ?> <?php echo $data['label']; ?></label>
							</td>
						<?php endif; ?>
					</tr>
					<?php endif; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>

</div>