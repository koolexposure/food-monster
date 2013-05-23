<p>
	<?php gb_e( 'Select this notification\'s type. If you select a type that has already been selected for another notification, this selection will be used.' ); ?>
</p>
<p>
	<select name="notification_type" id="notification_type">
		<option></option>
	<?php
foreach ( $notification_types as $type_id => $type ) {
	if ( $notification_id == $notifications[$type_id] ) {
		$notification_name = esc_html( $type['name'] );
	}
	echo '<option value="' . esc_attr( $type_id ) . '"' . selected( isset( $notifications[$type_id] ) ? $notifications[$type_id] : '', $notification_id ) . '>'. esc_html( $type['name'] ) . '</option>';
}
?>
	</select>
</p>
<?php foreach ( $notification_types as $type_id => $type ) { ?>
<p id="notification_type_description_<?php echo esc_attr( $type_id ); ?>" class="notification_type_description">
	<?php echo $type['description']; ?>
</p>
<?php } ?>

<p id="notification_type_disabled_wrap">
	<?php $notification_name = ( !empty( $notification_name ) ) ? $notification_name : '' ; ?>
	<input type="checkbox" id="notification_type_disabled" name="notification_type_disabled" value="TRUE" <?php checked( 'TRUE', $disabled ) ?> />&nbsp;<?php gb_e( sprintf( 'Disable %s Notification', $notification_name ) ) ?>
</p>
