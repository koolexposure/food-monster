<fieldset id="gb-account-account-info">
	<h2 class="section_heading gb_ff"><?php gb_e('Account Information'); ?></h2>
	<table class="collapsable contact form-table">
		<tbody>
			<?php foreach ( $fields as $key => $data ): ?>
				<tr>
					<?php if ( $data['type'] != 'checkbox' ): ?>
						<td><?php gb_form_label($key, $data, 'account'); ?></td>
						<td><?php gb_form_field($key, $data, 'account'); ?></td>
					<?php else: ?>
						<td colspan="2">
							<label for="gb_account_<?php echo $key; ?>"><?php gb_form_field($key, $data, 'account'); ?> <?php echo $data['label']; ?></label>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</fieldset>