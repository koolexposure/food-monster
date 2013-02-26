<?php

/**
 * GBS Utility Template Functions
 *
 * @package GBS
 * @subpackage Utility
 * @category Template Tags
 */

/**
 * Print form field
 * @see gb_get_form_field()
 * @param string $key      Form field key
 * @param array $data      Array of data to build form field
 * @param string $category group the form field belongs to
 * @return string           form field input, select, radio, etc.
 */
function gb_form_field( $key, $data, $category ) {
	echo apply_filters( 'gb_form_field', gb_get_form_field( $key, $data, $category ), $key, $data, $category );
}

/**
 * Build and return form field
 * @param string $key      Form field key
 * @param array $data      Array of data to build form field
 * @param string $category group the form field belongs to
 * @return string           form field input, select, radio, etc.
 */
function gb_get_form_field( $key, $data, $category ) {
	if ( empty($data['default']) && isset( $_REQUEST['gb_'.$category.'_'.$key] ) && $_REQUEST['gb_'.$category.'_'.$key] != '' ) {
		$data['default'] = $_REQUEST['gb_'.$category.'_'.$key];
	}
	if ( !isset( $data['attributes'] ) || !is_array( $data['attributes'] ) ) {
		$data['attributes'] = array();
	}
	foreach ( array_keys( $data['attributes'] ) as $attr ) {
		if ( in_array( $attr, array( 'name', 'type', 'id', 'rows', 'cols', 'value', 'placeholder', 'size', 'checked' ) ) ) {
			unset( $data['attributes'][$attr] ); // certain attributes are dealt with in other ways
		}
	}
	ob_start();
?>
	<span class="<?php gb_form_field_classes( $data ); ?>">
	<?php if ( $data['type'] == 'textarea' ): ?>
		<textarea name="gb_<?php echo $category; ?>_<?php echo $key; ?>" id="gb_<?php echo $category; ?>_<?php echo $key; ?>" rows="<?php echo isset( $data['rows'] )?$data['rows']:4; ?>" cols="<?php echo isset( $data['cols'] )?$data['cols']:40; ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo $attr.'="'.$attr_value.'" '; } ?> <?php if ( isset( $data['required'] ) && $data['required'] ) echo 'required'; ?>><?php echo $data['default']; ?></textarea>
	<?php elseif ( $data['type'] == 'select-state' ):  // TODO AJAX based on country selection  ?>
		<select name="gb_<?php echo $category; ?>_<?php echo $key; ?>" id="gb_<?php echo $category; ?>_<?php echo $key; ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo $attr.'="'.$attr_value.'" '; } ?> <?php if ( isset( $data['required'] ) && $data['required'] ) echo 'required'; ?>>
			<?php foreach ( $data['options'] as $group => $states ) : ?>
				<optgroup label="<?php echo $group ?>">
					<?php foreach ( $states as $option_key => $option_label ): ?>
						<option value="<?php echo $option_key; ?>" <?php selected( $option_key, $data['default'] ) ?>><?php echo $option_label; ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	<?php elseif ( $data['type'] == 'select' ): ?>
		<select name="gb_<?php echo $category; ?>_<?php echo $key; ?>" id="gb_<?php echo $category; ?>_<?php echo $key; ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo $attr.'="'.$attr_value.'" '; } ?> <?php if ( isset( $data['required'] ) && $data['required'] ) echo 'required'; ?>>
			<?php foreach ( $data['options'] as $option_key => $option_label ): ?>
			<option value="<?php echo $option_key; ?>" <?php selected( $option_key, $data['default'] ) ?>><?php echo $option_label; ?></option>
			<?php endforeach; ?>
		</select>
	<?php elseif ( $data['type'] == 'multiselect' ): ?>
		<select name="gb_<?php echo $category; ?>_<?php echo $key; ?>[]" id="gb_<?php echo $category; ?>_<?php echo $key; ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo $attr.'="'.$attr_value.'" '; } ?> multiple="multiple" <?php if ( isset( $data['required'] ) && $data['required'] ) echo 'required'; ?>>
			<?php foreach ( $data['options'] as $option_key => $option_label ): ?>
				<option value="<?php echo $option_key; ?>" <?php if ( in_array( $option_key, $data['default'] ) ) echo 'selected="selected"' ?>><?php echo $option_label; ?></option>
			<?php endforeach; ?>
		</select>
	<?php elseif ( $data['type'] == 'radios' ): ?>
		<?php foreach ( $data['options'] as $option_key => $option_label ): ?>
			<span class="gb-form-field-radio">
				<label for="gb_<?php echo $category; ?>_<?php echo $key; ?>"><input type="radio" name="gb_<?php echo $category; ?>_<?php echo $key; ?>" id="gb_<?php echo $category; ?>_<?php echo $key; ?>_<?php esc_attr_e( $option_key ); ?>" value="<?php esc_attr_e( $option_key ); ?>" <?php checked( $option_key, $data['default'] ) ?> />&nbsp;<?php _e( $option_label ); ?></label>
			</span>
		<?php endforeach; ?>
	<?php elseif ( $data['type'] == 'checkbox' ): ?>
		<input type="checkbox" name="gb_<?php echo $category; ?>_<?php echo $key; ?>" id="gb_<?php echo $category; ?>_<?php echo $key; ?>" <?php checked( TRUE, $data['default'] ); ?> value="<?php echo isset( $data['value'] )?$data['value']:'On'; ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo $attr.'="'.$attr_value.'" '; } ?> <?php if ( isset( $data['required'] ) && $data['required'] ) echo 'required'; ?>/>
	<?php elseif ( $data['type'] == 'hidden' ): ?>
		<input type="hidden" name="gb_<?php echo $category; ?>_<?php echo $key; ?>" id="gb_<?php echo $category; ?>_<?php echo $key; ?>" value="<?php echo $data['value']; ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo $attr.'="'.$attr_value.'" '; } ?> />
	<?php elseif ( $data['type'] == 'file' ): ?>
		<input type="file" name="gb_<?php echo $category; ?>_<?php echo $key; ?>" id="gb_<?php echo $category; ?>_<?php echo $key; ?>" <?php if ( isset( $data['required'] ) && $data['required'] ) echo 'required'; ?>/>
	<?php elseif ( $data['type'] == 'bypass' ): ?>
		<?php echo $data['output']; ?>
	<?php else: ?>
		<input type="<?php echo $data['type']; ?>" name="gb_<?php echo $category; ?>_<?php echo $key; ?>" id="gb_<?php echo $category; ?>_<?php echo $key; ?>" class="text-input" value="<?php echo $data['default']; ?>" placeholder="<?php echo isset( $data['placeholder'] )?$data['placeholder']:''; ?>" size="<?php echo isset( $data['size'] )?$data['size']:40; ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo $attr.'="'.$attr_value.'" '; } ?> <?php if ( isset( $data['required'] ) && $data['required'] ) echo 'required'; ?>/>
	<?php endif; ?>
	<?php if ( !empty( $data['description'] ) ): ?>
		<p class="description help_block"><?php echo $data['description'] ?></p>
	<?php endif; ?>
	</span>
	<?php
	return apply_filters( 'gb_get_form_field', ob_get_clean(), $key, $data, $category );
}

/**
 * Utility to print form field classes
 * @see gb_get_form_field_classes()
 * @param array $data  array of data that builds the form field
 * @return string       space separated set of classes
 */
function gb_form_field_classes( $data ) {
	$classes = implode( ' ', gb_get_form_field_classes( $data ) );
	echo apply_filters( 'gb_form_field_classes', $classes, $data );
}

/**
 * Utility to build an array of a form fields classes
 * @param array $data  array of data that builds the form field
 * @return array
 */
function gb_get_form_field_classes( $data ) {
	$classes = array(
		'gb-form-field',
		'gb-form-field-'.$data['type'],
	);
	if ( isset( $data['required'] ) && $data['required'] ) {
		$classes[] = 'gb-form-field-required';
	}
	return apply_filters( 'gb_get_form_field_classes', $classes, $data );
}

/**
 * Print form field label
 * @see gb_get_form_label()
 * @param string $key      Form field key
 * @param array $data      Array of data to build form field
 * @param string $category group the form field belongs to
 * @return string           <label>
 */
function gb_form_label( $key, $data, $category ) {
	echo apply_filters( 'gb_form_label', gb_get_form_label( $key, $data, $category ), $key, $data, $category );
}

/**
 * Build and return a form field label
 * @param string $key      Form field key
 * @param array $data      Array of data to build form field
 * @param string $category group the form field belongs to
 * @return string           <label>
 */
function gb_get_form_label( $key, $data, $category ) {
	$out = '<label for="gb_'.$category.'_'.$key.'">'.$data['label'].'</label>';
	if ( isset( $data['required'] ) && $data['required'] ) {
		$out .= ' <span class="required">*</span>';
	}
	return apply_filters( 'gb_get_form_label', $out, $key, $data, $category );
}

/**
 * Return a quantity select option
 * @param integer $start    where to start
 * @param integer $end      when to end
 * @param integer $selected default option
 * @param string  $name     option name
 * @return string            
 */
function gb_get_quantity_select( $start = 1, $end = 10, $selected = 1, $name = 'quantity_select' ) {
	if ( ( $end - $start ) > 100 ) {
		$input = '<input type="number" name="'.$name.'" value="'.$selected.'" min="'.$start.'" max="'.$end.'">';
		return $input;
	}
	$select = '<select name="'.$name.'">';
	for ( $i=$start; $i < $end+1; $i++ ) {
		$select .= '<option value="'.$i.'" '.selected( $selected, $i, FALSE ).'>'.$i.'</option>';
	}
	$select .= "<select>";
	return $select;
}
