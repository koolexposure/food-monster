<form action="<?php echo $action; ?>" method="post" id="gb_order_lookup" class="registration_layout clearfix">
	<table class="form-table">
		<tbody>
			<tr>
				<td><?php gb_form_label( $order_option_name, array( 'label' => 'Order #' ), 'order_lookup' ); ?></td>
				<td class="gb-form-field gb-form-field-text">
					<?php gb_form_field( $order_option_name, array( 'type' => 'text' ), 'order_lookup' ); ?>
				</td>
			</tr>
			<tr>
				<td><?php gb_form_label( $city_option_name, array( 'label' => "Order's Billing City" ), 'order_lookup' ); ?></td>
				<td class="gb-form-field gb-form-field-text">
					<?php gb_form_field( $city_option_name, array( 'type' => 'text' ), 'order_lookup' ); ?>
				</td>
			</tr>
			<?php wp_nonce_field( $nonce_id ); ?>
		</tbody>
	</table>
	<input type="submit" name="submit" value="Lookup" class="form-submit">
</form>
<script type="text/javascript">
jQuery(document).ready(function(){
  	jQuery("#gb_order_lookup").validate({
		rules: {
			'<?php echo $order_option_name ?>': "required",
			'<?php echo $city_option_name ?>': "required"
		  }
	});
	jQuery("<?php echo $order_option_name ?>").focus();
});
</script>