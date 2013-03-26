<?php 

function gb_get_order_status( $purchase_id = 0 ) {
	if ( !$purchase_id ) {
		global $post;
		$post_id = $post->ID;
		if ( Group_Buying_Purchase::POST_TYPE !== $the_post->post_type ) {
			if ( $the_post->post_type === Group_Buying_Payment::POST_TYPE ) { // global id is a payment
				$payment = Group_Buying_Payment::get_instance( $post_id );
				$purchase_id = $payment->get_purchase();
			}
			elseif ( $the_post->post_type === Group_Buying_Voucher::POST_TYPE ) { // global post id is a voucher
				$voucher = Group_Buying_Voucher::get_instance( $post_id );
				$purchase_id = $voucher->get_purchase_id();
			}
			else {
				return apply_filters( 'gb_get_order_status', NULL, 0 ); // FAIL
			}
		} else { $purchase_id = $post_id; } // the post_id is the purchase id.
	}
	return apply_filters( 'gb_get_order_status', Group_Buying_Fulfillment::get_status( $purchase_id ), $purchase_id );
}