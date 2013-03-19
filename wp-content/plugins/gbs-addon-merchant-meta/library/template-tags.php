<?php

function gb_get_is_featured_merchant( $merchant_id ) {
	return apply_filters('gb_get_is_featured_merchant',Group_Buying_Merchant_Meta::is_featured($merchant_id));
}
function gb_get_featured_merchant_url() {
	return apply_filters('gb_get_is_featured_merchant',Group_Buying_Merchant_Meta::get_url());
}
	function gb_featured_merchant_url() {
		$url = gb_get_featured_merchant_url();
		if ( !is_a($url,'WP_Error')) {
			echo apply_filters('gb_get_is_featured_merchant',$url);
		}
	}

function gb_get_merchant_meta1( $merchant_id ) {
	return apply_filters('gb_get_merchant_meta1',Group_Buying_Merchant_Meta::get_meta1($merchant_id));
}
	function gb_merchant_meta1( $merchant_id ) {
		echo apply_filters('gb_merchant_meta1',gb_get_merchant_meta1($merchant_id));
	}

function gb_get_merchant_meta2( $merchant_id ) {
	return apply_filters('gb_get_merchant_meta2',Group_Buying_Merchant_Meta::get_meta2($merchant_id));
}
	function gb_merchant_meta2( $merchant_id ) {
		echo apply_filters('gb_merchant_meta2',gb_get_merchant_meta2($merchant_id));
	}

function gb_get_merchant_meta3( $merchant_id ) {
	return apply_filters('gb_get_merchant_meta3',Group_Buying_Merchant_Meta::get_meta3($merchant_id));
}
	function gb_merchant_meta3( $merchant_id ) {
		echo apply_filters('gb_merchant_meta3',gb_get_merchant_meta3($merchant_id));
	}
