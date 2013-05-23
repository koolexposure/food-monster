<div class="checkout_block right_form clearfix">

	<div class="paymentform-info">
		<h2 class="section_heading gb_ff"><?php gb_e( 'Your Payment Information' ); ?></h2>
	</div>
	<fieldset id="gb-billing">
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
