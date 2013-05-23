<p><?php gb_e( 'Your order is complete.' ); ?></p>
<table>
	<tbody>
		<tr>
			<th scope="row"><?php gb_e( 'Order Number:' ); ?></th>
			<td><?php echo $order_number; ?></td>
		</tr>
		<tr>
			<th scope="row"><?php gb_e( 'Total:' ); ?></th>
			<td><?php gb_formatted_money( $total ); ?></td>
		</tr>
	</tbody>
</table>
