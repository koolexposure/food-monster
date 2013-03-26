<fieldset id="gb-account-contact-info">
	<h2 class="section_heading gb_ff"><?php gb_e('Contact Information'); ?></h2>
	<table class="collapsable contact form-table">
		<tbody>
			<?php foreach ( $fields as $key => $data ): ?>
				<tr>
					<?php if ( $data['type'] != 'checkbox' ): ?>
						<td><?php gb_form_label($key, $data, 'contact'); ?></td>
						<td><?php gb_form_field($key, $data, 'contact'); ?></td>
					<?php else: ?>
						<td colspan="2">
							<label for="gb_contact_<?php echo $key; ?>"><?php gb_form_field($key, $data, 'contact'); ?> <?php echo $data['label']; ?></label>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</fieldset>