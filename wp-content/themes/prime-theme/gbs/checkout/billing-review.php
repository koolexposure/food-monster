<div class="checkout_block left_form clearfix">

	<div class="paymentform_info">
		<h2 class="section_heading section_heading background_alt gb_ff"><?php gb_e('Your Billing Information'); ?></h2>
	</div>
	<fieldset id="gb_billing">
		<table>
			<tbody>
				<tr>
					<th scope="row"><?php gb_e('Name'); ?></th>
					<td><?php esc_html_e($data['first_name']); ?> <?php esc_html_e($data['last_name']); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php gb_e('Address'); ?></th>
					<td>
						<?php if ( $data['street'] ) { echo $data['street'].'<br />'; } ?>
						<?php if ( $data['city'] ) { echo $data['city'].','; } ?>
						<?php echo $data['zone'].' '; ?>
						<?php echo $data['postal_code'].' '; ?>
						<?php if ( $data['country'] ) { echo '<br />'.$data['country']; } ?>
					</td>
				</tr>
			</tbody>
		</table>
	</fieldset>

</div>