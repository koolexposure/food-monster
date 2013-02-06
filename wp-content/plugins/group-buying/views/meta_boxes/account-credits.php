<table class="form-table">
	<tbody>
		<?php foreach ( $credit_types as $key => $data ): ?>
			<tr>
				<th scope="row"><strong><?php echo $data['label']; ?></strong>:</th>
				<td>
					<?php esc_attr_e( $data['balance'] ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php gb_e( 'Action' ) ?>:</th>
				<td>
					<input type="radio" name="account_credit_action[<?php esc_attr_e( $key ); ?>]" value="add" /> <?php gb_e( 'Add' ) ?>&nbsp;&nbsp;&nbsp;<input type="radio" name="account_credit_action[<?php esc_attr_e( $key ); ?>]" value="deduct" /> <?php gb_e( 'Deduct' ) ?>&nbsp;&nbsp;&nbsp;<input type="radio" name="account_credit_action[<?php esc_attr_e( $key ); ?>]" value="change" /> <?php gb_e( 'Change to' ) ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php gb_e( 'Amount' ) ?>:</th>
				<td>
					<input type="text" name="account_credit_balance[<?php esc_attr_e( $key ); ?>]" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php gb_e( 'Comment' ) ?>:</th>
				<td>
					<textarea name="account_credit_notes[<?php esc_attr_e( $key ); ?>]" rows="4" style="width:99%;"></textarea>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<?php foreach ($credit_types as $key => $data): ?>
	<table id="gb_purchases_tables">
		<h2><?php echo $data['label']; ?> <?php gb_e( 'Logs' ) ?></h2>
		<tbody>
		<thead><th><?php gb_e( 'Date' ) ?></th><th><?php gb_e( 'Recorded by' ) ?></th><th><?php gb_e( 'Notes' ) ?></th><th><?php gb_e( 'Amount' ) ?></th><th><?php gb_e( 'Total' ) ?></th></thead>
	<?php

	$records = Group_Buying_Record::get_records_by_type_and_association( $account->get_ID(), Group_Buying_Accounts::$record_type . '_' . $key );
	
	if ( apply_filters( 'gb_include_purchases_in_creditlog', '__return_true' ) ) {
		$purchases = Group_Buying_Purchase::get_purchases( array( 'account' => $account->get_ID() ) );
	} else {
		$purchases = array();
	}
	
	if ( !empty( $purchases ) || !empty( $records ) ) {
		$items = array();

		// Loop through all the records
		foreach ( $records as $record_id ) {
			foreach ( $credit_types as $credit_key => $credit_data ) {
				
				$record = Group_Buying_Record::get_instance( $record_id );
				$record_data = $record->get_data();
				
				$record_post = $record->get_post();
				$author = get_userdata( $record_post->post_author );
				
				$balance = (int)$record_data['current_total'];
				$prior = (int)$record_data['prior_total'];
				$adjustment = ( $balance == (int)$record_data['adjustment_value'] ) ? (int)$record_data['adjustment_value'] - $prior : $balance - $prior ;
				$plusminus = ( $adjustment > 0 ) ? '+' : '';

				$items[get_the_time( 'U', $record_id )] = array(
					'date' => get_the_time( 'U', $record_id ),
					'recorded' => $author->user_login,
					'note' => $record_post->post_content,
					'amount' => $plusminus . $adjustment,
					'total' => $balance,
				);
			}
		}

		// Loop through all the purchases
		foreach ( $purchases as $purchase_id ) {
			$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
			$amount = $purchase->get_total( Group_Buying_Affiliate_Credit_Payments::PAYMENT_METHOD );
			if ( $amount > 0.1 ) {
				$items[get_the_time( 'U', $purchase_id )] = array(
					'date' => get_the_time( 'U', $purchase_id ),
					'recorded' => gb__( 'User' ),
					'note' => get_the_title( $purchase_id ),
					'amount' => number_format( floatval( $amount ), 2 ),
					'total' => gb__( 'N/A' ),
				);
			}
		}
		uasort( $items, array( 'Group_Buying_Records', 'sort_callback' ) );
		foreach ( $items as $key => $value ) {
			echo '<tr><td>'.date( get_option( 'date_format' ).', '.get_option( 'time_format' ), $value['date'] ).'</td>';
			echo '<td>'.$value['recorded'].'</td>';
			echo '<td>'.$value['note'].'</td>';
			echo '<td>'.$value['amount'].'</td>';
			echo '<td>'.$value['total'].'</td>';
			echo '</tr>';
		}
	}

	?>
		</tbody>
	</table>
<?php endforeach ?>
