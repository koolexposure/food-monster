<?php

/**
 * Affiliates Controller
 *
 * @package GBS
 * @subpackage Affiliate
 */
class Group_Buying_Affiliates extends Group_Buying_Controller {
	const CREDIT_TYPE = 'affiliate';
	const AFFILIATE_CREDIT_OPTION = 'gb_affiliate_credit';
	const AFFILIATE_COOKIE = 'affiliated_with';
	const AFFILIATE_COOKIE_EXP_OPTION = 'gb_affiliate_cookie_exp';
	const AFFILIATE_QUERY_ARG = 'affiliated-member';
	const SHARED_POST_QUERY_ARG = 'shared-post';
	const SHARE_PATH_OPTION = 'gb_share_path';
	const BITLY_API_LOGIN = 'gb_bitly_login';
	const BITLY_API_KEY = 'gb_bitly_api_key';
	const WP_AFFILIATE_POST = 'gb_affiliate_url_option';
	const WP_AFFILIATE_KEY = 'gb_affiliate_key_option';
	const PURCHASE_WPAF_APPLIED_META = '_gb_wpaffiliate_applied';
	protected static $settings_page;
	private static $affiliate_credit;
	private static $affiliate_cookie_exp;
	private static $share_path = 'share';
	private static $bitly_login;
	private static $bitly_api;
	private static $affiliate_payment_processor;
	private static $affiliate_post;
	private static $affiliate_key;

	final public static function init() {
		// Options
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 40, 0 );
		self::$share_path = get_option( self::SHARE_PATH_OPTION, self::$share_path );
		self::$bitly_login = get_option( self::BITLY_API_LOGIN );
		self::$bitly_api = get_option( self::BITLY_API_KEY );
		self::$affiliate_credit = get_option( self::AFFILIATE_CREDIT_OPTION, '0' );
		self::$affiliate_cookie_exp = (int)get_option( self::AFFILIATE_COOKIE_EXP_OPTION, '3600' );
		self::$affiliate_post = get_option( self::WP_AFFILIATE_POST, trailingslashit( WP_PLUGIN_URL ) . 'wp-affiliate-platform/api/post.php' );
		self::$affiliate_key = get_option( self::WP_AFFILIATE_KEY );

		// This shouldn't ever be instantiated through the normal process. We want to add it on.
		self::$affiliate_payment_processor = Group_Buying_Affiliate_Credit_Payments::get_instance();
		//self::register_path_callback(self::$share_path, array(get_class(), 'shared_redirect'), self::AFFILIATE_QUERY_ARG);
		self::register_query_var( self::AFFILIATE_QUERY_ARG, array( get_class(), 'shared_redirect' ) );
		self::register_query_var( self::SHARED_POST_QUERY_ARG );

		add_filter( 'gb_account_credit_types', array( get_class(), 'register_credit_type' ), 10, 1 );
		add_filter( 'gb_rewrite_rules', array( get_class(), 'affiliate_rewrite_rules' ), 10, 1 );

		add_action( 'payment_authorized', array( get_class(), 'set_source' ), 10, 1 ); // for onsite purchases
		add_action( 'payment_pending', array( get_class(), 'set_source' ), 10, 1 ); // for those offsite purchases
		add_action( 'payment_complete', array( get_class(), 'apply_credits' ), 10, 1 ); // Do the dirty work

		// WP Affiliate
		if ( !defined('WP_AFFILIATE_PLATFORM_VERSION') ) {
			define('WP_AFFILIATE_PLATFORM_VERSION', 0);
		}
		if ( WP_AFFILIATE_PLATFORM_VERSION ) {
			add_action( 'payment_authorized', array( get_class(), 'set_ad_id' ), 20, 1 );
			add_action( 'payment_pending', array( get_class(), 'set_ad_id' ), 20, 1 );
			add_action( 'payment_complete', array( get_class(), 'wp_affiliate' ), 5, 1 ); // Come before apply_credits
		}

	}

	public static function register_credit_type( $credit_types = array() ) {
		$credit_types[self::CREDIT_TYPE] = self::__( 'Reward Points' );
		return $credit_types;
	}

	/**
	 * Set the source or affiliate within the payment record.
	 *
	 * @param string
	 * @return void
	 */
	public static function set_source( $payment ) {
		if ( isset( $_COOKIE[self::AFFILIATE_COOKIE] ) && $_COOKIE[self::AFFILIATE_COOKIE] != '' ) { // be careful not to overwrite the source with something blank, since this function gets called after a successful IPN validation too.
			$member_login = $_COOKIE[self::AFFILIATE_COOKIE];
			$payment->set_source( $member_login );
		}
	}

	/**
	 * Give credits to deserving users when a purchase is completed
	 *
	 * @param Group_Buying_Purchase $purchase
	 * @return void
	 */
	public static function apply_credits( Group_Buying_Payment $payment ) {
		// Get the referrers account
		$member_id = self::get_source( $payment );
		if ( !empty( $member_id ) ) {
			$purchaser_account = $payment->get_account();
			$affiliate_account = Group_Buying_Account::get_instance( $member_id );
			if ( !is_a( $affiliate_account, 'Group_Buying_Account' ) || !is_a( $purchaser_account, 'Group_Buying_Account' ) ) {
				return;
			}
			if ( $purchaser_account->get_ID() != $affiliate_account->get_ID() ) {
				$affiliate_account->add_credit( self::$affiliate_credit, self::CREDIT_TYPE );
				do_action( 'gb_apply_credits', $affiliate_account, $payment, self::$affiliate_credit, self::CREDIT_TYPE );
				self::set_cookie( null, TRUE ); // in case we still have access.
				// Loop through all of the payments, including this one and reset the source to something more readable, it also works as a failsafe for double counts.
				$payments = Group_Buying_Payment::get_payments_for_purchase( $payment->get_purchase() );
				foreach ( $payments as $payment_id ) {
					$payment = Group_Buying_Payment::get_instance( $payment_id );
					$account = self::get_source( $payment, TRUE );
					$source = self::__( 'Member: ' ).$account->get_name();
					$payment->set_source( $source );
				}
				self::affiliate_record( $affiliate_account, $purchaser_account, $payment->get_ID(), self::$affiliate_credit, self::CREDIT_TYPE ); // TODO move to controller.
			}
		}

	}

	// TODO move this to records controller
	public static function affiliate_record( $account, $purchaser_account, $payment_id, $credits, $type ) {
		$account_id = $account->get_ID();
		$purchaser_id = $purchaser_account->get_ID();
		$purchaser_name = $purchaser_account->get_name();
		$balance = $account->get_credit_balance( $type );
		$data = array();
		$data['account_id'] = $account_id;
		$data['payment_id'] = $payment_id;
		$data['credits'] = $credits;
		$data['type'] = $type;
		$data['current_total_'.$type] = $balance;
		$data['change_'.$type] = $credits;
		Group_Buying_Records::new_record( sprintf( self::__( '%s Points from %s (#%s)' ), ucfirst( $type ), $purchaser_name, $purchaser_id ), Group_Buying_Accounts::$record_type, sprintf( self::__( '%s Points from %s (#%s)' ), ucfirst( $type ), $purchaser_name, $purchaser_id ), 1, $account_id, $data );
	}


	/**
	 * Get the account ID via the source/membername
	 *
	 * @static
	 * @return int
	 */
	public static function get_source( Group_Buying_Payment $payment, $account = false ) {
		$member_login = $payment->get_source();
		if ( !$member_login ) {
			return FALSE;
		}
		$user = get_userdatabylogin( urldecode( $member_login ) );
		if ( !$user && !is_int( $user->ID ) ) {
			return FALSE;
		}
		if ( $account ) {
			return Group_Buying_Account::get_instance( $user->ID );
		}
		return $user->ID;
	}

	public static function set_cookie( $affiliate_member = null, $destroy = FALSE ) {
		if ( null == $affiliate_member || !$destroy ) {
			setcookie( self::AFFILIATE_COOKIE, $affiliate_member, time()+self::$affiliate_cookie_exp, '/' );
		} else {
			setcookie( self::AFFILIATE_COOKIE, '', current_time( 'timestamp' )-( 60*60 ), '/' );
		}
	}

	public static function wp_affiliate( Group_Buying_Payment $payment ) {
		$source = $payment->get_source();
		if ( $source ) {

			// Get Purchase
			$transaction_id = $payment->get_purchase();
			$purchase = Group_Buying_Purchase::get_instance( $transaction_id );
			
			if ( !$purchase->get_post_meta( self::PURCHASE_WPAF_APPLIED_META ) ) {

				// Hook Latest Versions of WP Affiliate Platform
				if ( version_compare( WP_AFFILIATE_PLATFORM_VERSION, '4.8.9', '>' ) ) {
					do_action( 'wp_affiliate_process_cart_commission', 
						array( 
							'referrer' => apply_filters( 'gb_wp_affiliate_referrer', $source, $purchase), 
							'sale_amt' => apply_filters( 'gb_wp_affiliate_sale_amt', $purchase->get_subtotal(), $purchase), 
							'txn_id' => apply_filters( 'gb_wp_affiliate_txn_id', $transaction_id, $purchase ) 
							) );
			
				} 
				// Older versions of WP Affiliate Platform
				else {
					// Prepare the data
					$data = array();
					$data['secret'] = self::$affiliate_key;
					$data['ap_id'] = apply_filters( 'gb_wp_affiliate_referrer', $source, $purchase);
					$data['sale_amt'] = apply_filters( 'gb_wp_affiliate_sale_amt', $purchase->get_subtotal(), $purchase);
					$data['txn_id'] = apply_filters( 'gb_wp_affiliate_txn_id', $transaction_id, $purchase);
					$data['item_id'] = '';
					// Post data
					$response = wp_remote_post( self::$affiliate_post,
						array(
							'method' => 'POST',
							'body' => $data,
							'timeout' => 15,
							'sslverify' => false )
					);
				}

				self::set_wpap( $purchase );
			}

		}
	}

	public static function set_wpap( Group_Buying_Purchase $purchase ) {
		$purchase->save_post_meta( array(
				self::PURCHASE_WPAF_APPLIED_META => 1
			) );
	}

	public static function set_ad_id( Group_Buying_Payment $payment ) {
		if ( isset( $_COOKIE['ap_id'] ) && $_COOKIE['ap_id'] != '' ) {
			$payment->set_source( $_COOKIE['ap_id'] );
		}
	}

	/**
	 *
	 *
	 * @static
	 * @return string The ID of the payment settings page
	 */
	public static function get_settings_page() {
		return self::$settings_page;
	}

	/**
	 * Provides rewrite rules for affiliate links
	 *
	 * @param array   $rules
	 * @return array
	 */
	public static function affiliate_rewrite_rules( $rules ) {
		global $wp_rewrite;
		$rules[ trailingslashit( self::$share_path ) . '([^/]+)/([\w-]+)/?$' ] = 'index.php?'.self::AFFILIATE_QUERY_ARG.'=' . $wp_rewrite->preg_index( 1 ) . '&'.self::SHARED_POST_QUERY_ARG.'=' . $wp_rewrite->preg_index( 2 );
		return $rules;
	}

	/**
	 * Get the URL for sharing a post
	 *
	 * @static
	 * @param int|null $postID
	 * @param string|null $member_login
	 * @param boolean|false $directlink
	 * @return string
	 */
	public static function get_share_link( $deal_id, $member_login = NULL, $directlink = FALSE ) {
		if ( NULL === $member_login ) {
			$current_user = wp_get_current_user();
			$member_login = ( !empty( $current_user->user_login ) ) ? $current_user->user_login : 'guest' ;
		}
		$permalink = get_permalink( $deal_id );
		if ( $directlink ) {
			return add_query_arg( array( 'socializer' => urlencode( $member_login ) ), $permalink );
		}

		if ( self::using_permalinks() ) {
			$post = get_post( $deal_id );
			$link = home_url( trailingslashit( self::$share_path ) . urlencode( $member_login ) . '/' .$post->post_name.'/' );
		} else {
			$link = add_query_arg( array( self::AFFILIATE_QUERY_ARG => urlencode( $member_login ) ), $permalink );
		}

		$link = self::get_short_share_url( $link, $member_login, $deal_id );
		return $link;
	}

	public static function is_bitly_active() {
		return self::$bitly_api != '' && self::$bitly_login != '';
	}

	public static function get_short_share_url( $url, $member_login, $deal_id, $refresh = FALSE ) {

		if ( self::is_bitly_active() ) {
			// Check transient cache
			$cache_key = 'gb_bitly_share_v2_'.$member_login.'_dealid_'.$deal_id;
			if ( !$refresh ) {
				$cache = get_transient( $cache_key );
				if ( !empty( $cache ) ) {
					return $cache;
				}
			}
			// Get short URL
			$url = self::get_short_url( $url );
			// set transient cache for a week.
			set_transient( $cache_key, $url, 604800 ); // cache for a week.
		}
		return $url;
	}

	public static function get_short_url( $url ) {
		if ( self::is_bitly_active() ) {
			$bitly = 'https://api-ssl.bitly.com/v3/shorten?&longUrl='.urlencode( $url ).'&login='.self::$bitly_login.'&apiKey='.self::$bitly_api.'&format=json';
			$raw_response = wp_remote_get( $bitly );
			if ( !$raw_response || is_wp_error( $raw_response ) ) {
				return $url;
			}
			$response = json_decode( wp_remote_retrieve_body( $raw_response ) );
			if ( $response->status_code == 200 ) {
				$url = $response->data->url;
			}
		}
		return $url;
	}

	public static function get_bitly_short_url_stats( $short_url ) {
		if ( self::is_bitly_active() ) {
			$bitly = 'https://api-ssl.bitly.com/v3/clicks?&shortUrl='.urlencode( $short_url ).'&login='.self::$bitly_login.'&apiKey='.self::$bitly_api.'&format=json';
			$raw_response = wp_remote_get( $bitly );
			if ( !$raw_response || is_wp_error( $raw_response ) ) {
				return FALSE;
			}
			$response = json_decode( wp_remote_retrieve_body( $raw_response ) );
			if ( $response->status_code == 200 ) {
				$data = $response->data;
				return $data;
			}
		}
		return FALSE;

	}

	public static function get_bitly_short_url_clicks( $short_url ) {
		if ( self::is_bitly_active() ) {
			$data = self::get_bitly_short_url_stats( $short_url );
			return $data->clicks[0]->global_clicks;
		}
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	final protected function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}

	final protected function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}


	protected function __construct() {
	}

	/**
	 * Examines the query variables and redirects to a template if appropriate.
	 *
	 */
	public static function shared_redirect( WP $wp ) {
		$affiliate_member = $wp->query_vars[self::AFFILIATE_QUERY_ARG];
		$shared_post = $wp->query_vars[self::SHARED_POST_QUERY_ARG];

		if ( empty( $affiliate_member ) && empty( $shared_post ) ) {
			return;
		}

		// Set an affiliate cookie
		self::set_cookie( $affiliate_member );

		// Redirect
		$post = get_page_by_path( $shared_post, OBJECT, Group_Buying_Deal::POST_TYPE );
		$post_id = ( !is_int( $shared_post ) && is_object( $post ) ) ? $post->ID : $shared_post;
		do_action( 'gb_shared_post_redirection', $affiliate_member, $shared_post, $post_id );
		if ( is_int( $post_id )  ) {
			wp_redirect( add_query_arg( 'socializer', $affiliate_member, get_permalink( $post_id ) ) );
			exit( );
		} else {
			wp_redirect( add_query_arg( 'socializer', $affiliate_member, site_url() ) );
			exit( );
		}
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_affiliate_settings';
		add_settings_section( $section, self::__( 'Affiliate/Share Settings' ), array( get_class(), 'display_settings_section' ), $page );
		// Settings
		register_setting( $page, self::AFFILIATE_CREDIT_OPTION );
		register_setting( $page, self::AFFILIATE_COOKIE_EXP_OPTION );
		register_setting( $page, self::SHARE_PATH_OPTION, array( get_class(), 'validate_share_path_field' ) );
		register_setting( $page, self::BITLY_API_LOGIN );
		register_setting( $page, self::BITLY_API_KEY );
		// Fields
		add_settings_field( self::AFFILIATE_CREDIT_OPTION, self::__( 'Social/Affiliate Credit' ), array( get_class(), 'display_payment_affiliate_credit' ), $page, $section );
		add_settings_field( self::AFFILIATE_COOKIE_EXP_OPTION, self::__( 'Cookie Expiration' ), array( get_class(), 'display_affiliate_expiration' ), $page, $section );
		add_settings_field( self::SHARE_PATH_OPTION, self::__( 'Share Path' ), array( get_class(), 'display_share_path_field' ), $page, $section );
		add_settings_field( self::BITLY_API_LOGIN, self::__( 'Bitly Login' ), array( get_class(), 'display_bitly_field' ), $page, $section );
		add_settings_field( self::BITLY_API_KEY, self::__( 'Bitly API Key' ), array( get_class(), 'display_bitly_api_field' ), $page, $section );
		// WP Affiliate Platform settings for older versions of WPAP
		$wp_section = 'gb_wp_affiliate_settings';
		if ( version_compare( WP_AFFILIATE_PLATFORM_VERSION, '4.8.9', '<' ) ) {
			add_settings_section( $wp_section, self::__( 'WP Affiliate Platform Settings' ), array( get_class(), 'display_wpaffilaite_settings_section' ), $page );
			register_setting( $page, self::WP_AFFILIATE_POST );
			register_setting( $page, self::WP_AFFILIATE_KEY );
			add_settings_field( self::WP_AFFILIATE_KEY, self::__( 'WPAffiliate Key' ), array( get_class(), 'display_wpa_key_field' ), $page, $wp_section );
			add_settings_field( self::WP_AFFILIATE_POST, self::__( 'WPAffiliate Post URL' ), array( get_class(), 'display_wpa_post_field' ), $page, $wp_section );
		} else {
			add_settings_section( $wp_section, self::__( 'WP Affiliate Platform Has Been Automatically Integrated' ), '', $page );
		}
	}

	public function display_wpaffilaite_settings_section() {
		printf( self::__( 'GBS supports basic integration with <a href="%s" target="_blank">WordPress Affiliate Platform</a> an easy to use WordPress plugin for affiliate recruitment, management and tracking that can be used on any WordPress blog/site.' ), 'http://groupbuyingsite.com/goto/WPAffiliatePlatform' );
	}
	public static function display_payment_affiliate_credit() {
		echo '<input type="text" name="'.self::AFFILIATE_CREDIT_OPTION.'" value="'.self::$affiliate_credit.'" size="3" />';
	}

	public static function display_affiliate_expiration() {
		echo '<input type="text" name="'.self::AFFILIATE_COOKIE_EXP_OPTION.'" value="'.self::$affiliate_cookie_exp.'" size="8" /> <small>seconds</small>';
	}

	public static function display_share_path_field() {
		echo home_url().'/<input type="text" name="'.self::SHARE_PATH_OPTION.'" value="'.self::$share_path.'" size="20" />/&lt;'.self::__('member name').'&gt;/&lt;'.self::__('deal slug').'&gt;/';
	}

	public static function display_bitly_field() {
		echo '<input type="text" name="'.self::BITLY_API_LOGIN.'" value="'.self::$bitly_login.'" />';
		echo '<br/><span class="description">'.self::__( 'Use the Bitly API to shorten URLs and enables stat functions (e.g. total shares).  Be aware that shortened URLs can sometimes cause emails to be marked as spam or blocked.' ).'</small>';
	}

	public static function display_bitly_api_field() {
		echo '<input type="text" name="'.self::BITLY_API_KEY.'" value="'.self::$bitly_api.'" size="70" />';
		echo '<br/><span class="description">'.self::__( 'Find this API key on your <a href="http://bitly.com/a/account">account page</a>.' ).'</span>';
	}

	public static function display_wpa_post_field() {
		echo '<input type="text" name="'.self::WP_AFFILIATE_POST.'" value="'.self::$affiliate_post.'" size="70" />';
		echo '<br/><span class="description">'.self::__( 'The url the affiliate record needs to be posted to.' ).'</span>';
	}

	public static function display_wpa_key_field() {
		echo '<input type="text" name="'.self::WP_AFFILIATE_KEY.'" value="'.self::$affiliate_key.'" size="20" />';
	}

	public static function validate_share_path_field( $value ) {
		$value = trim( $value, "/" );
		return $value;
	}

	public static function get_affiliate_credit() {
		return self::$affiliate_credit;
	}

	public static function get_affiliate_cookie_exp() {
		return self::$affiliate_cookie_exp;
	}
}
