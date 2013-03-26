<div class="checkout_block right_form clearfix">

	<div class="paymentform_info">
		<h2 class="section_heading section_heading background_alt gb_ff"><?php gb_e('Your Payment Information'); ?></h2>
	</div>
	<fieldset id="gb_billing">
		<table>
			<tbody>
				<?php foreach ( $fields as $key => $data ): ?>
					<tr>
						<th scope="row"><?php echo $data['label']; ?></th>
						<td><?php echo $data['value']; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>

</div>