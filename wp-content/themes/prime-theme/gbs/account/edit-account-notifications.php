<fieldset id="gb-account-subscription-info">
	<h2 class="section_heading gb_ff"><?php gb_e('E-Mail Preferences'); ?></h2>
	<table class="collapsable subscription form-table">
		<tbody>
			<?php foreach ( $fields as $key => $data ): ?>
				<tr>
					<?php if ( $data['type'] != 'checkbox' ): ?>
						<td><?php gb_form_label($key, $data, 'subscription'); ?></td>
						<td><?php gb_form_field($key, $data, 'subscription'); ?></td>
					<?php else: ?>
						<td colspan="2">
							<label for="gb_contact_<?php echo $key; ?>"><?php gb_form_field($key, $data, 'subscription'); ?> <?php echo $data['label']; ?></label>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</fieldset>