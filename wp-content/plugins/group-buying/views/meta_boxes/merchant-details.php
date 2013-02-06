<p>
	<label for="contact_name"><?php gb_e( 'Contact Name' ); ?></label><br />
	<input type="text" name="contact_name" id="contact_name" value="<?php echo esc_attr( $contact_name ); ?>" class="large-text" />
</p>
<p>
	<label for="contact_street"><?php gb_e( 'Contact Street' ); ?></label><br />
	<input type="text" name="contact_street" id="contact_street" value="<?php echo esc_attr( $contact_street ); ?>" class="large-text" />
</p>
<p>
	<label for="contact_city"><?php gb_e( 'Contact City' ); ?></label><br />
	<input type="text" name="contact_city" id="contact_city" value="<?php echo esc_attr( $contact_city ); ?>" class="large-text" />
</p>
<p>
	<label for="contact_state"><?php gb_e( 'Contact State' ); ?></label><br />
	<select name="contact_state" id="contact_state" class="select2" style="width:350px">
			<option></option>
			<?php $options = Group_Buying_Controller::get_state_options(); ?>
			<?php foreach ( $options as $group => $states ) : ?>
				<optgroup label="<?php echo $group ?>">
					<?php foreach ( $states as $option_key => $option_label ): ?>
						<option value="<?php echo $option_key; ?>" <?php selected( $option_key, $contact_state ) ?>><?php echo $option_label; ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
	</select>
</p>
<p>
	<label for="contact_postal_code"><?php gb_e( 'Contact Postal Code' ); ?></label><br />
	<input type="text" name="contact_postal_code" id="contact_postal_code" value="<?php echo esc_attr( $contact_postal_code ); ?>" size="5" />
</p>
<p>
	<label for="contact_country"><?php gb_e( 'Contact Country' ); ?></label><br />
	<select name="contact_country" id="contact_country" class="select2" style="width:350px">
		<option></option>
		<?php $options = Group_Buying_Controller::get_country_options(); ?>
		<?php foreach ( $options as $key => $label ): ?>
			<option value="<?php esc_attr_e( $key ); ?>" <?php selected( $key, $contact_country ); ?>><?php esc_html_e( $label ); ?></option>
		<?php endforeach; ?>
	</select>
</p>
<p>
	<label for="contact_phone"><?php gb_e( 'Contact Phone' ); ?></label><br />
	<input type="text" name="contact_phone" id="contact_phone" value="<?php echo esc_attr( $contact_phone ); ?>" class="large-text" />
</p>
<p>
	<label for="website"><?php gb_e( 'Website' ); ?></label><br />
	<input type="text" name="website" id="website" value="<?php echo esc_attr( $website ); ?>" class="large-text" />
</p>
<p>
	<label for="facebook"><?php gb_e( 'Facebook' ); ?></label><br />
	<input type="text" name="facebook" id="facebook" value="<?php echo esc_attr( $facebook ); ?>" class="large-text" />
</p>
<p>
	<label for="twitter"><?php gb_e( 'Twitter' ); ?></label><br />
	<input type="text" name="twitter" id="twitter" value="<?php echo esc_attr( $twitter ); ?>" class="large-text" />
</p>
