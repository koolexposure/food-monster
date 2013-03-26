<form id="gb_merchant_register" class="registration_layout" method="post" action="<?php gb_merchant_edit_url(); ?>" enctype="multipart/form-data">
	<input type="hidden" name="gb_merchant_action" value="<?php echo Group_Buying_Merchants_Edit::FORM_ACTION; ?>" />
	<table class="form-table">
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
	<?php Group_Buying_Controller::load_view( 'merchant/edit-controls', array() ); ?>
</form>
