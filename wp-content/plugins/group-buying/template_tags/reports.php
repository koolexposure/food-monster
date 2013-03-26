<?php

/**
 * GBS Reports Template Functions
 *
 * @package GBS
 * @subpackage Report
 * @category Template Tags
 */

/**
 * Get a deals purchase report url.
 * @param integer $post_id Deal ID
 * @param boolean $csv     link to the CSV
 * @return string           
 */
function gb_get_deal_purchase_report_url( $post_id = 0, $csv = FALSE ) {
	if ( isset( $_GET['id'] ) ) {
		$post_id = $_GET['id'];
	}

	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$report = Group_Buying_Reports::get_instance( 'deal_purchase' );
	if ( $csv ) {
		return apply_filters( 'gb_get_deal_purchase_report_url', add_query_arg( array( 'report' => 'deal_purchase', 'id' => $post_id ), $report->get_csv_url() ) );
	}
	return apply_filters( 'gb_get_deal_purchase_report_url', add_query_arg( array( 'report' => 'deal_purchase', 'id' => $post_id ), $report->get_url() ) );
}

/**
 * Print deals purchase report url
 * @see gb_get_deal_purchase_report_url()
 * @param integer $post_id Deal ID
 * @return string           url
 */
function gb_deal_purchase_report_url( $post_id = 0 ) {
	echo apply_filters( 'gb_deal_purchase_report_url', gb_get_deal_purchase_report_url( $post_id ) );
}

/**
 * Print deals purchase report link <a>
 * @see gb_get_deal_purchase_report_url()
 * @param integer $post_id Deal ID
 * @return string           link
 */
function gb_deal_purchase_report_link( $post_id = 0 ) {
	$link = '<a href="'.gb_get_deal_purchase_report_url( $post_id ).'" class="report_button">'.gb__( 'Purchases' ).'</a>';
	echo apply_filters( 'gb_deal_purchase_report_url', $link );
}

/**
 * Print the deals purchase CSV url
 * @see gb_get_deal_purchase_report_url()
 * @param integer $post_id Deal ID
 * @return string           url
 */
function gb_deal_purchase_report_csv_url( $post_id = 0 ) {
	echo apply_filters( 'gb_deal_purchase_report_url', gb_get_deal_purchase_report_url( $post_id, true ) );
}

/**
 * Get a deals merchants report url.
 * @param integer $post_id Merchant ID
 * @param boolean $csv     link to the CSV
 * @return string           
 */
function gb_get_merchant_purchase_report_url( $post_id = 0, $csv = FALSE ) {
	if ( isset( $_GET['id'] ) ) {
		$post_id = $_GET['id'];
	}

	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$report = Group_Buying_Reports::get_instance( 'merchant_purchase' );
	if ( $csv ) {
		return apply_filters( 'gb_get_merchant_purchase_report_url', add_query_arg( array( 'report' => 'merchant_purchase', 'id' => $post_id ), $report->get_csv_url() ) );
	}
	return apply_filters( 'gb_get_merchant_purchase_report_url', add_query_arg( array( 'report' => 'merchant_purchase', 'id' => $post_id ), $report->get_url() ) );
}

/**
 * Print deals merchants report url
 * @see gb_get_merchant_purchase_report_url()
 * @param integer $post_id Merchant ID
 * @return string           url
 */
function gb_merchant_purchase_report_url( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_purchase_report_url', gb_get_merchant_purchase_report_url( $post_id ) );
}

/**
 * Print deals merchants report link <a>
 * @see gb_get_merchant_purchase_report_url()
 * @param integer $post_id Merchant ID
 * @return string           link
 */
function gb_merchant_purchase_report_link( $post_id = 0 ) {
	$link = '<a href="'.gb_get_merchant_purchase_report_url( $post_id ).'" class="report_button">'.gb__( 'Purchases' ).'</a>';
	echo apply_filters( 'gb_merchant_purchase_report_url', $link );
}

/**
 * Print the merchants purchase CSV url
 * @see gb_get_merchant_purchase_report_url()
 * @param integer $post_id Merchant ID
 * @return string           url
 */
function gb_merchant_purchase_report_csv_url( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_purchase_report_url', gb_get_merchant_purchase_report_url( $post_id, true ) );
}

/**
 * Get a deals voucher report url.
 * @param integer $post_id Deal ID
 * @param boolean $csv     link to the CSV
 * @return string           
 */
function gb_get_deal_voucher_report_url( $post_id = 0, $csv = FALSE ) {
	if ( isset( $_GET['id'] ) ) {
		$post_id = $_GET['id'];
	}

	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$report = Group_Buying_Reports::get_instance( 'deal_voucher' );
	if ( $csv ) {
		return apply_filters( 'gb_get_deal_voucher_report_url', add_query_arg( array( 'report' => 'deal_voucher', 'id' => $post_id ), $report->get_csv_url() ) );
	}
	return apply_filters( 'gb_get_deal_voucher_report_url', add_query_arg( array( 'report' => 'deal_voucher', 'id' => $post_id ), $report->get_url() ) );
}

/**
 * Print deals voucher report url
 * @see gb_get_deal_voucher_report_url()
 * @param integer $post_id Deal ID
 * @return string           url
 */
function gb_deal_voucher_report_url( $post_id = 0 ) {
	echo apply_filters( 'gb_deal_voucher_report_url', gb_get_deal_voucher_report_url( $post_id ) );
}

/**
 * Print deals voucher report link <a>
 * @see gb_get_deal_voucher_report_url()
 * @param integer $post_id Deal ID
 * @return string           link
 */
function gb_deal_voucher_report_link( $post_id = 0 ) {
	$link = '<a href="'.gb_get_deal_voucher_report_url( $post_id ).'" class="report_button">'.gb__( 'Voucher Report' ).'</a>';
	echo apply_filters( 'gb_deal_voucher_report_url', $link );
}

/**
 * Print the deals voucher CSV url
 * @see gb_get_deal_voucher_report_url()
 * @param integer $post_id Deal ID
 * @return string           url
 */
function gb_deal_voucher_report_csv_url( $post_id = 0 ) {
	echo apply_filters( 'gb_deal_voucher_report_url', gb_get_deal_voucher_report_url( $post_id, true ) );
}

/**
 * Get a merchant voucher report url.
 * @param integer $post_id Deal ID
 * @param boolean $csv     link to the CSV
 * @return string           
 */
function gb_get_merchant_voucher_report_url( $post_id = 0, $csv = FALSE ) {
	if ( isset( $_GET['id'] ) ) {
		$post_id = $_GET['id'];
	}

	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$report = Group_Buying_Reports::get_instance( 'merchant_voucher' );
	if ( $csv ) {
		return apply_filters( 'gb_get_merchant_voucher_report_url', add_query_arg( array( 'report' => 'merchant_voucher', 'id' => $post_id ), $report->get_csv_url() ) );
	}
	return apply_filters( 'gb_get_merchant_voucher_report_url', add_query_arg( array( 'report' => 'merchant_voucher', 'id' => $post_id ), $report->get_url() ) );
}

/**
 * Print merchant voucher report url
 * @see gb_get_merchant_voucher_report_url()
 * @param integer $post_id Deal ID
 * @return string           url
 */
function gb_merchant_voucher_report_url( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_voucher_report_url', gb_get_merchant_voucher_report_url( $post_id ) );
}

/**
 * Print merchant voucher report link <a>
 * @see gb_get_merchant_voucher_report_url()
 * @param integer $post_id Deal ID
 * @return string           link
 */
function gb_merchant_voucher_report_link( $post_id = 0 ) {
	$link = '<a href="'.gb_get_merchant_voucher_report_url( $post_id ).'" class="report_button">'.gb__( 'Voucher Report' ).'</a>';
	echo apply_filters( 'gb_merchant_voucher_report_url', $link );
}

/**
 * Print the merchant voucher CSV url
 * @see gb_get_merchant_voucher_report_url()
 * @param integer $post_id Deal ID
 * @return string           url
 */
function gb_merchant_voucher_report_csv_url( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_voucher_report_url', gb_get_merchant_voucher_report_url( $post_id, true ) );
}

/**
 * Get a deals purchases report url.
 * @param integer $post_id Deal ID
 * @param boolean $csv     link to the CSV
 * @return string           
 */
function gb_get_purchases_report_url( $post_id = 0, $csv = FALSE ) {
	if ( isset( $_GET['id'] ) ) {
		$post_id = $_GET['id'];
	}

	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$report = Group_Buying_Reports::get_instance( 'purchases' );
	if ( $csv ) {
		return apply_filters( 'gb_get_purchases_report_url', add_query_arg( array( 'report' => 'purchases', 'id' => $post_id ), $report->get_csv_url() ) );
	}
	return apply_filters( 'gb_get_purchases_report_url', add_query_arg( array( 'report' => 'purchases', 'id' => $post_id ), $report->get_url() ) );
}

/**
 * Print deals purchases report url
 * @see gb_get_purchases_report_url()
 * @param integer $post_id Deal ID
 * @return string           url
 */
function gb_purchases_report_url( $post_id = 0 ) {
	echo apply_filters( 'gb_purchases_report_url', gb_get_purchases_report_url( $post_id ) );
}

/**
 * Print deals purchases report link <a>
 * @see gb_get_purchases_report_url()
 * @param integer $post_id Deal ID
 * @return string           link
 */
function gb_purchases_report_link( $post_id = 0 ) {
	$link = '<a href="'.gb_get_purchases_report_url( $post_id ).'" class="report_button">'.gb__( 'Purchase History' ).'</a>';
	echo apply_filters( 'gb_purchases_report_url', $link );
}

/**
 * Print the deals purchases CSV url
 * @see gb_get_purchases_report_url()
 * @param integer $post_id Deal ID
 * @return string           url
 */
function gb_purchases_report_csv_url( $post_id = 0 ) {
	echo apply_filters( 'gb_purchases_report_url', gb_get_purchases_report_url( $post_id, true ) );
}

/**
 * Get a accounts report url.
 * @param boolean $csv     link to the CSV
 * @return string           
 */
function gb_get_accounts_report_url( $csv = FALSE ) {
	if ( isset( $_GET['id'] ) ) {
		$post_id = $_GET['id'];
	}

	$report = Group_Buying_Reports::get_instance( 'accounts' );
	if ( $csv ) {
		return apply_filters( 'gb_get_accounts_report_url', add_query_arg( array( 'report' => 'accounts'), $report->get_csv_url() ) );
	}
	return apply_filters( 'gb_get_accounts_report_url', add_query_arg( array( 'report' => 'accounts', 'id' => $post_id ), $report->get_url() ) );
}

/**
 * Print accounts report url
 * @see gb_get_accounts_report_url()
 * @return string           url
 */
function gb_accounts_report_url() {
	echo apply_filters( 'gb_accounts_report_url', gb_get_accounts_report_url() );
}

/**
 * Print accounts report link <a>
 * @see gb_get_accounts_report_url()
 * @return string           link
 */
function gb_accounts_report_link() {
	$link = '<a href="'.gb_get_accounts_report_url().'" class="report_button">'.gb__( 'Accounts' ).'</a>';
	echo apply_filters( 'gb_accounts_report_url', $link );
}

/**
 * Print the accounts CSV url
 * @see gb_get_accounts_report_url()
 * @param integer $post_id Deal ID
 * @return string           url
 */
function gb_accounts_report_csv_url( $post_id = 0 ) {
	echo apply_filters( 'gb_accounts_report_url', gb_get_accounts_report_url( $post_id, true ) );
}

/**
 * Get a merchant purchases report url.
 * @param integer $post_id Merchant ID
 * @param boolean $csv     link to the CSV
 * @return string           
 */
function gb_get_merchant_purchases_report_url( $post_id = 0, $csv = FALSE ) {
	if ( isset( $_GET['id'] ) ) {
		$post_id = $_GET['id'];
	}

	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$report = Group_Buying_Reports::get_instance( 'merchant_purchases' );
	if ( $csv ) {
		return apply_filters( 'gb_get_merchant_purchases_report_url', add_query_arg( array( 'report' => 'merchant_purchases', 'id' => $post_id ), $report->get_csv_url() ) );
	}
	return apply_filters( 'gb_get_merchant_purchases_report_url', add_query_arg( array( 'report' => 'merchant_purchases', 'id' => $post_id ), $report->get_url() ) );
}

/**
 * Print merchant purchases report url
 * @see gb_get_merchant_purchases_report_url()
 * @param integer $post_id Merchant ID
 * @return string           url
 */
function gb_merchant_purchases_report_url( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_purchases_report_url', gb_get_merchant_purchases_report_url( $post_id ) );
}

/**
 * Print merchant purchases report link <a>
 * @see gb_get_merchant_purchases_report_url()
 * @param integer $post_id Merchant ID
 * @return string           link
 */
function gb_merchant_purchases_report_link( $post_id = 0 ) {
	$link = '<a href="'.gb_get_merchant_purchases_report_url( $post_id ).'" class="report_button">'.gb__( 'Purchase History' ).'</a>';
	echo apply_filters( 'gb_merchant_purchases_report_url', $link );
}

/**
 * Print the merchant purchases CSV url
 * @see gb_get_merchant_purchases_report_url()
 * @param integer $post_id Merchant ID
 * @return string           url
 */
function gb_merchant_purchases_report_csv_url( $post_id = 0 ) {
	echo apply_filters( 'gb_merchant_purchases_report_url', gb_get_merchant_purchases_report_url( $post_id, true ) );
}

/**
 * Get the currently viewed report's CSV download URL
 * @return string
 */
function gb_get_current_report_csv_download_url() {

	if ( !isset( $_GET['report'] ) || $_GET['report'] == '' || !isset( $_GET['id'] ) || $_GET['id'] == '' )
		return; // nothing to do

	$report = Group_Buying_Reports::get_instance( $_GET['report'] );
	$url = add_query_arg(
		array(
			'report' => $_GET['report'],
			'id' => $_GET['id'],
			'showpage' => $_GET['showpage']
		),
		$report->get_csv_url() );

	if ( isset( $_GET['filter'] ) && $_GET['filter'] != '' ) {
		$url = add_query_arg(
			array(
				'filter' => $_GET['filter'] ),
			$url );
	}

	return apply_filters( 'gb_get_current_report_csv_download_url', $url );

}

/**
 * Print the currently viewed report's CSV download URL
 * @return string
 */
function gb_current_report_csv_download_url() {
	echo apply_filters( 'gb_current_report_csv_download_url', gb_get_current_report_csv_download_url() );
}
