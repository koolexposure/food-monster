<?php /**
 * User registration pane.
 *
 * @package GBS
 * @subpackage Account
 * @used-by Group_Buying_Accounts_Registration::user_pane()
 */ ?>

<?php do_action( 'gb_account_register_form_user_account' ); ?>
<fieldset id="gb-account-user-info">
	<legend><?php gb_e( 'Create Account' ); ?></legend>
	<table class="user">
		<tbody>
			<?php foreach ( $fields as $key => $data ): ?>
				<tr>
					<?php if ( $data['type'] != 'checkbox' ): ?>
						<td><?php gb_form_label( $key, $data, 'user' ); ?></td>
						<td><?php gb_form_field( $key, $data, 'user' ); ?></td>
					<?php else: ?>
						<td colspan="2">
							<label for="gb_user_<?php echo $key; ?>"><?php gb_form_field( $key, $data, 'user' ); ?> <?php echo $data['label']; ?></label>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</fieldset>
