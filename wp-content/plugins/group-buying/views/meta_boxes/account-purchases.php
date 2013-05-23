<script type="text/javascript" charset="utf-8">
	jQuery(document).ready(function($){
		jQuery(".gb_activate").click(function(event) {
			event.preventDefault();
				if( confirm( '<?php gb_e("Are you sure? This will make the voucher immediately available for download.") ?>' ) ) {
					var $link = $( this ),
					voucher_id = $link.attr( 'ref' );
					url = $link.attr( 'href' );
					$( "#"+voucher_id+"_activate" ).fadeOut('slow');
					$.post( url, { activate_voucher: voucher_id },
						function( data ) {
								$( "#"+voucher_id+"_activate_result" ).append( '<?php self::_e( 'Activated' ) ?>' ).fadeIn();
							}
						);
				} else {
					// nothing to do.
				}
		});
		jQuery(".gb_deactivate").on('click', function(event) {
			event.preventDefault();
				if( confirm( '<?php gb_e("Are you sure? This will immediately remove the voucher from customer access.") ?>' ) ) {
					var $deactivate_button = $( this ),
					deactivate_voucher_id = $deactivate_button.attr( 'ref' );
					$( "#"+deactivate_voucher_id+"_deactivate" ).fadeOut('slow');
					$.post( ajaxurl, { action: 'gbs_deactivate_voucher', voucher_id: deactivate_voucher_id, deactivate_voucher_nonce: '<?php echo wp_create_nonce( Group_Buying_Destroy::NONCE ) ?>' },
						function( data ) {
								$( "#"+deactivate_voucher_id+"_deactivate_result" ).append( '<?php self::_e( 'Deactivated' ) ?>' ).fadeIn();
							}
						);
				} else {
					// nothing to do.
				}
		});
	});
</script>
<table id="gb_purchases_tables">
	<h2><?php gb_e( 'Purchase History' ) ?></h2>
	<tbody>

<?php

$purchases = Group_Buying_Purchase::get_purchases( array( 'account' => $account->get_ID() ) );

if ( !empty( $purchases ) ) {
	rsort( $purchases );
	// Loop through all the deals a merchant has
	foreach ( $purchases as $purchase_id ) {
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		if ( is_a( $purchase, 'Group_Buying_Purchase' ) ) {
			echo '<tr><thead><th colspan="14" align="left">'.get_the_title( $purchase_id ).'<small>&nbsp;&nbsp;&nbsp;&nbsp;'.gb__( 'Total: ' ).gb_get_formatted_money( $purchase->get_total() ).'</small></th></thead></tr>';
			echo '<tr><th abbr="'.get_the_title( $purchase_id ).'" colspan="10">&nbsp;</th><td class="th">'.gb__( 'Voucher ID' ).'</td><td class="th">'.gb__( 'Status Mngt.' ).'</td><td class="th">'.gb__( 'Voucher Serial/Code' ).'</td></tr>';
			$vouchers = Group_Buying_Voucher::get_vouchers_for_purchase( $purchase_id );
			foreach ( $vouchers as $voucher_id ) {
				$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
				if ( is_a( $voucher, 'Group_Buying_Voucher' ) ) {
					if ( get_post_status( $voucher_id ) != 'publish' ) {
						$activate_path = 'edit.php?post_type=gb_voucher&activate_voucher='.$voucher_id.'&_wpnonce='.wp_create_nonce( 'activate_voucher' );
						$status = '<span id="'.$voucher_id.'_activate_result"></span><a href="'.admin_url( $activate_path ).'" class="gb_activate button" id="'.$voucher_id.'_activate" ref="'.$voucher_id.'">Activate</a>';
					} else {
						$status = gb__( 'Activated' );
						$status =  '<span id="'.$voucher_id.'_deactivate_result"></span><a href="javascript:void(0)" class="gb_deactivate button disabled" id="'.$voucher_id.'_deactivate" ref="'.$voucher_id.'">'.gb__('Deactivate').'</a>';
					}
					echo '<tr><th abbr="'.get_the_title( $purchase_id ).'" colspan="4">&nbsp;</th><th class="voucher_name" colspan="6" align="right">'.str_replace( gb__( 'Voucher for ' ), '', get_the_title( $voucher_id ) ).'</th>';
					echo '<td>'.$voucher_id.'</td>';
					echo '<td>'.$status.'</td>';
					echo '<td>'.$voucher->get_serial_number().'</td>';
					echo '</tr>';
				}

			}
		}

	}
}
?>
	</tbody>
</table>
