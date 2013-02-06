<?php /**
 * Edit contact information pane.
 *
 * @package GBS
 * @subpackage Account
 * @used-by Group_Buying_Accounts_Edit_Profile::get_panes()
 */ ?>

<fieldset id="gb-account-contact-info">
	<legend><?php gb_e( 'Contact Information' ); ?></legend>
	<table class="contact form-table">
		<tbody>
			<?php foreach ( $fields as $key => $data ): ?>
				<tr>
					<?php if ( $data['type'] != 'checkbox' ): ?>
						<td><?php gb_form_label( $key, $data, 'contact' ); ?></td>
						<td><?php gb_form_field( $key, $data, 'contact' ); ?></td>
					<?php else: ?>
						<td colspan="2">
							<label for="gb_contact_<?php echo $key; ?>"><?php gb_form_field( $key, $data, 'contact' ); ?> <?php echo $data['label']; ?></label>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</fieldset>
