<table class="form-table">
	<tbody>
		<tr>
			<th scope="row"><label for="account_first_name"><?php gb_e( 'First Name' ) ?>:</label></th>
			<td><input type="text" id="account_first_name" name="account_first_name" value="<?php print $first_name; ?>" size="40" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="account_last_name"><?php gb_e( 'Last Name' ) ?>:</label></th>
			<td><input type="text" id="account_last_name" name="account_last_name" value="<?php print $last_name; ?>" size="40" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="account_street"><?php gb_e( 'Street' ) ?>:</label></th>
			<td><textarea id="account_street" name="account_street" rows="2" cols="26"><?php print $street; ?></textarea></td>
		</tr>
		<tr>
			<th scope="row"><label for="account_city"><?php gb_e( 'City' ) ?>:</label></th>
			<td><input type="text" id="account_city" name="account_city" value="<?php print $city; ?>" size="40" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="account_zone"><?php gb_e( 'State' ) ?>:</label></th>
			<td><select id="account_zone" name="account_zone" class="select2" style="width:300px;">
				<?php $options = Group_Buying_Controller::get_state_options( array( 'include_option_none' => gb__( ' -- Select a State -- ' ) ) ); ?>
				<?php foreach ( $options as $group => $states ) : ?>
					<optgroup label="<?php echo $group ?>">
						<?php foreach ( $states as $option_key => $option_label ): ?>
							<option value="<?php echo $option_key; ?>" <?php selected( $option_key, $zone ) ?>><?php echo $option_label; ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select></td>
		</tr>
		<tr>
			<th scope="row"><label for="account_postal_code"><?php gb_e( 'ZIP Code' ) ?>:</label></th>
			<td><input type="text" id="account_postal_code" name="account_postal_code" value="<?php print $postal_code; ?>" size="40" /></td>
		</tr>
		<tr>
			<th scope="row"><label for="account_country"><?php gb_e( 'Country' ) ?>:</label></th>
			<td><select id="account_country" name="account_country" class="select2" style="width:300px;">
				<?php $options = Group_Buying_Controller::get_country_options( array( 'include_option_none' => gb__( ' -- Select a Country -- ' ) ) ); ?>
				<?php foreach ( $options as $key => $label ): ?>
					<option value="<?php esc_attr_e( $key ); ?>" <?php selected( $key, $country ); ?>><?php esc_html_e( $label ); ?></option>
				<?php endforeach; ?>
			</select></td>
		</tr>
	</tbody>
</table>
