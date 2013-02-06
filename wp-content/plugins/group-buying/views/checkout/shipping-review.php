<div class="checkout_block left_form clearfix">

	<div class="paymentform-info">
		<h2 class="gb_ff"><?php gb_e( 'Your Shipping Information' ); ?></h2>
	</div>
	<fieldset id="gb-shipping">
		<table>
			<tbody>
				<tr>
					<th scope="row"><?php gb_e( 'Name' ); ?></th>
					<td><?php esc_html_e( $data['first_name'] ); ?> <?php esc_html_e( $data['last_name'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php gb_e( 'Address' ); ?></th>
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
