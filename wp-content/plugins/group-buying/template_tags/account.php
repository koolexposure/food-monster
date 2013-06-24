<?php

/**
 * GBS Account Template Functions
 *
 * @package GBS
 * @subpackage Account
 * @category Template Tags
 * @category Template Tags
 */

/**
 * Checks deals availability: checks inventory, purchase limits and completion.
 *
 * @see Group_Buying_Account::can_purchase()
 * @param integer  $post_id $post->ID
 * @param integer  $user_id $user-ID
 * @return boolean The quantity the user is allowed to purchase
 */
function gb_can_purchase( $post_id = 0, $user_id = 0 ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$account = Group_Buying_Account::get_instance( $user_id );
	return apply_filters( 'gb_can_purchase', $account->can_purchase( $post_id ) );
}

/**
 * On account page
 *
 * @return boolean
 */
function gb_on_account_page() {
	if ( 1 == get_query_var( Group_Buying_Accounts::ACCOUNT_QUERY_VAR ) ) {
		return true;
	}
	return;
}

/**
 * On account edit page
 *
 * @return boolean
 */
function gb_on_account_edit_page() {
	if ( 1 == get_query_var( Group_Buying_Accounts_Edit_Profile::EDIT_PROFILE_QUERY_VAR ) ) {
		return true;
	}
	return;
}

/**
 * On login page
 *
 * @return boolean
 */
function gb_on_login_page() {
	if ( 1 == get_query_var( Group_Buying_Accounts_Login::LOGIN_QUERY_VAR ) ) {
		return true;
	}
	return;
}

/**
 * On registration page
 *
 * @return boolean
 */
function gb_on_registration_page() {
	if ( 1 == get_query_var( Group_Buying_Accounts_Registration::REGISTER_QUERY_VAR ) ) {
		return true;
	}
	return;
}

/**
 * On reset password page
 *
 * @return boolean
 */
function gb_on_reset_password_page() {
	if ( 1 == get_query_var( Group_Buying_Accounts_Retrieve_Password::RP_QUERY_VAR ) ) {
		return true;
	}
	return;
}

/**
 * Get Account credit balance
 *
 * @param integer $user_id user id
 * @param string  $type    credit type to check
 * @return integer
 */
function gb_get_account_balance( $user_id = 0, $type = Group_Buying_Accounts::CREDIT_TYPE ) {
	$possible_affilite_name = array(
			'rewards',
			'reward',
			'points',
			'point'
		);
	if ( in_array( $type, $possible_affilite_name ) ) {
		$type = Group_Buying_Affiliates::CREDIT_TYPE;
	}
	$account = Group_Buying_Account::get_instance( $user_id );
	$balance = $account->get_credit_balance( $type );
	if ( $balance == '' )
		return apply_filters( 'gb_get_account_balance', '0' );
	return apply_filters( 'gb_get_account_balance', $balance );
}
function gb_account_balance( $user_id = 0 ) {
	echo apply_filters( 'gb_account_balance', gb_get_account_balance( $user_id ) );
}

/**
 * Does a user have a merchant assigned
 * @param  integer  $user_id $user->ID
 * @return boolean           
 */
function gb_account_has_merchant( $user_id = 0 ) {
	$ids = gb_account_merchant_id( $user_id );
	return ( empty( $ids ) ) ? FALSE : TRUE ;
}

/**
 * Get merchant ID assigned to a user
 * @param integer  $user_id $user->ID
 * @param  boolean $all     Return an array of all merchants assigned
 * @return integer|array           return the first ID or all within an array
 */
function gb_account_merchant_id( $user_id = 0, $all = false ) {
	// TODO this shouldn't be based on user_id, it should be based on account id but that means the merchant class needs to be updated too.
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$merchant_ids = gb_get_merchants_by_account( $user_id );
	if ( !$all ) {
		$merchant_ids = $merchant_ids[0];
	}
	return apply_filters( 'gb_account_merchant_id', $merchant_ids );
}

/**
 * Account URL
 * @see gb_get_account_url()
 * @return string
 */
function get_gbs_account_link() {
	return apply_filters( 'get_gbs_account_link', gb_get_account_url() );
}

/**
 * Account URL
 * @see gb_get_account_url()
 * @return string echo
 */
function gbs_account_link() {
	echo apply_filters( 'gbs_account_link', get_gbs_account_link() );
}

/**
 * Account URL
 * @see gb_get_account_url()
 * @return string echo
 */
function gb_account_url() {
	echo apply_filters( 'gb_account_url', gb_get_account_url() );
}

/**
 * Account URL
 * @return string echo
 */
function gb_get_account_url() {
	$url = Group_Buying_Accounts::get_url();
	return apply_filters( 'gb_get_account_url', $url );
}

/**
 * Login URL
 * @see gb_get_account_login_url()
 * @return string echo
 */
function gb_account_login_url() {
	echo apply_filters( 'gb_account_login_url', gb_get_account_login_url() );
}

/**
 * Login URL
 * @return string
 */
function gb_get_account_login_url() {
	$url = Group_Buying_Accounts_Login::get_url();
	return apply_filters( 'gb_get_account_login_url', $url );
}

/**
 * Lost Password URL
 * @see gb_get_account_lost_password_url()
 * @return string echo
 */
function gb_account_lost_password_url() {
	echo apply_filters( 'gb_account_login_url', gb_get_account_lost_password_url() );
}

/**
 * Lost password URL
 * @return string
 */
function gb_get_account_lost_password_url() {
	$url = add_query_arg( array( 'action' => 'lostpassword' ), Group_Buying_Accounts_Login::get_url() );
	return apply_filters( 'gb_get_account_lost_password_url', $url );
}

/**
 * Merchant registration URL
 * @see gb_get_account_register_url() Echo this return method
 * @return string
 */
function gb_account_register_url() {
	echo apply_filters( 'gb_account_register_url', gb_get_account_register_url() );
}

/**
 * Registration URL
 * @return string
 */
function gb_get_account_register_url() {
	$url = Group_Buying_Accounts_Registration::get_url();
	if ( isset( $_REQUEST['redirect_to'] ) ) {
		$url = add_query_arg( array( 'redirect_to' => urlencode( $_REQUEST['redirect_to'] ) ), $url );
	}
	return apply_filters( 'gb_get_account_register_url', $url );
}

/**
 * Account Edit URL
 * @see gb_get_account_edit_url()
 * @return string echo
 */
function gb_account_edit_url() {
	echo apply_filters( 'gb_account_edit_url', gb_get_account_edit_url() );
}


/**
 * Account Edit URL
 * @see Group_Buying_Accounts_Edit_Profile::get_url()
 * @return string
 */
function gb_get_account_edit_url() {
	$url = Group_Buying_Accounts_Edit_Profile::get_url();
	return apply_filters( 'gb_get_account_edit_url', $url );
}


/**
 * Logout URL
 * @see Group_Buying_Accounts_Login::log_out_url()
 * @return string
 */
function gb_get_logout_url() {
	$url = Group_Buying_Accounts_Login::log_out_url();
	return apply_filters( 'gb_get_logout_url', $url );

}

/**
 * Logout URL
 * @return string echo
 */
function gb_logout_url() {
	$link = '<a href="' . gb_get_logout_url() . '" title="'.gb__( 'Logout' ).'" class="logout">'.gb__( 'Logout' ).'</a>';
	echo apply_filters( 'gb_logout_url', $link );

}

/**
 * Get the name of an account, fallback to user login
 * @param  integer $user_id $user->ID
 * @return string
 */
function gb_get_name( $user_id = 0 ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$account = Group_Buying_Account::get_instance( $user_id );
	$gb_name = '';
	if ( is_a( $account, 'Group_Buying_Account' ) ) {
		$gb_name = $account->get_name();
	}
	$user = get_userdata( $user_id );
	$name = ( strlen( $gb_name ) <= 1 ) ? $user->user_login : $gb_name;
	return apply_filters( 'gb_get_name', $name, $user_id );
}

/**
 * Print the name of an account
 * @param  integer $user_id $user->ID
 * @return string           echo
 */
function gb_name( $user_id = 0 ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$gb_name = gb_get_name( $user_id );
	echo apply_filters( 'gb_name', $gb_name, $user_id );
}

/**
 * Return a formatted address
 * @param  array $address   an address array
 * @param  string $return    return an array or a string with separation
 * @param  string $separator if not returning an array what should the fields be separated by
 * @return array|string            return an array by default of a string based on $return
 */
function gb_format_address( $address, $return = 'array', $separator = "\n" ) {
	if ( empty( $address ) ) {
		return '';
	}
	$lines = array();
	if ( !empty($address['first_name']) || !empty($address['last_name']) ) {
		$lines[] = $address['first_name'].' '.$address['last_name'];
	}
	$lines[] = $address['street'];
	$city_line = $address['city'];
	if ( $city_line && ( $address['zone'] || $address['postal_code'] ) ) {
		$city_line .= ', ';
	}
	$city_line .= $address['zone'];
	$city_line = rtrim( $city_line ).' '.$address['postal_code'];
	$city_line = rtrim( $city_line );
	if ( $city_line ) {
		$lines[] = $city_line;
	}
	if ( $address['country'] ) {
		$lines[] = $address['country'];
	}
	switch ( $return ) {
	case 'array':
		return $lines;
	default:
		return apply_filters( 'gb_format_address', implode( $separator, $lines ), $address, $return, $separator );
	}
}

function gb_is_user_guest_purchaser( $user_id = 0 ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$flag = get_user_meta( $user_id, Group_Buying_Accounts_Checkout::GUEST_PURCHASE_USER_FLAG );
	if ( $flag )
		return TRUE;

	return;
}

function gb_get_purchased_deals( $user_id = 0 ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	// Get all the user's purchases
	$purchases = Group_Buying_Purchase::get_purchases( array(
			'user' => $user_id,
		) );

	$deal_ids = array();
	if ( !empty( $purchases ) ) {
		foreach ( $purchases as $purchase_id ) {
			$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
			$products = $purchase->get_products();
			foreach ( $products as $product ) {
				if ( !in_array( $deal_id, $deal_ids ) ) {
					$deal_ids[] = $product['deal_id'];
				}
			}
		}
	}
	return apply_filters( 'gb_get_purchased_deals', $deal_ids, $user_id, $purchases );
}

/**
 * Get the profile url
 * @param  integer $user_id $user->ID
 * @return string
 */
function gb_get_profile_link( $user_id = 0 ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$account = Group_Buying_Account::get_instance( $user_id );
	return apply_filters( 'gb_get_profile_link', get_permalink( $account->get_id() ), $user_id );
}
/**
 * Get the profile url
 * @param  integer $user_id $user->ID
 * @return string
 */
function gb_profile_link( $user_id = 0 ) {
	echo apply_filters( 'gb_profile_link', gb_get_profile_link( $user_id ) );
}