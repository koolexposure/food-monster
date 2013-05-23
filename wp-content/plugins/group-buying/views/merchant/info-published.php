<?php
// This view is displayed on the merchant/ page when the current user has a published merchant registration
?>
<table class="form-table">
	<tbody>
		<?php foreach ( $fields as $key => $data ): ?>
			<tr>
				<?php if ( $data['type'] != 'checkbox' ): ?>
					<td><?php echo $data['label']; ?></td>
					<?php if ( $data['type'] == 'file' ): ?>
						<td><img src="<?php echo $data['default']; ?>" height="150px"></td>
					<?php else: ?>
						<td><?php echo $data['default']; ?></td>
					<?php endif ?>
				<?php else: ?>
					<td colspan="2">
						<label for="gb_contact_<?php echo $key; ?>"><?php gb_form_field( $key, $data, 'contact' ); ?> <?php echo $data['label']; ?></label>
					</td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<p>
	<a href="<?php echo esc_url( Group_Buying_Merchants_Edit::get_url() ); ?>" class="gb_ff form-submit"><?php gb_e( 'Edit Merchant Information' ); ?></a>
</p>
