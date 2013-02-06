<div class="checkout_block left_form clearfix">

	<div class="paymentform-info">
		<h2 class="section_heading gb_ff"><?php gb_e( 'Your Billing Information' ); ?></h2>
	</div>
	<fieldset id="gb-billing">
		<table class="billing">
			<tbody>
				<?php foreach ( $fields as $key => $data ): ?>
					<tr>
						<?php if ( $data['type'] != 'checkbox' ): ?>
							<td><?php gb_form_label( $key, $data, 'billing' ); ?></td>
							<td><?php gb_form_field( $key, $data, 'billing' ); ?></td>
						<?php else: ?>
							<td colspan="2">
								<label for="gb_billing_<?php echo $key; ?>"><?php gb_form_field( $key, $data, 'billing' ); ?> <?php echo $data['label']; ?></label>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>

</div>
