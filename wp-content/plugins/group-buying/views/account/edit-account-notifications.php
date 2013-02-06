<?php /**
 * Edit account subscription pane.
 *
 * @package GBS
 * @subpackage Account
 * @used-by Group_Buying_Notifications::get_panes()
 */ ?>

<fieldset id="gb-account-account-subscriptions">
	<legend><?php gb_e( 'E-Mail Preferences' ); ?></legend>
	<table class="account form-table">
		<tbody>
			<?php foreach ( $fields as $key => $data ): ?>
				<tr>
					<?php if ( $data['type'] != 'checkbox' ): ?>
						<td><?php gb_form_label( $key, $data, 'subscription' ); ?></td>
						<td><?php gb_form_field( $key, $data, 'subscription' ); ?></td>
					<?php else: ?>
						<td colspan="2">
							<label for="gb_account_<?php echo $key; ?>"><?php gb_form_field( $key, $data, 'subscription' ); ?> <?php echo $data['label']; ?></label>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</fieldset>
