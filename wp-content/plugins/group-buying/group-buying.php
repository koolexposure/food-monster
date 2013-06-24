<?php
/*
Plugin Name: Group Buying Plugin
Version: 4.4
Plugin URI: http://groupbuyingsite.com/feature-tour/
Description: Allows for groupon like functionality. By installing this plugin you agree to the <a href="http://groupbuyingsite.com/tos/" title="I agree">terms and conditions</a> of GroupBuyingSite.
Author: GroupBuyingSite.com
Author URI: http://groupbuyingsite.com/
Plugin Author: Dan Cameron
Plugin Author URI: http://sproutventure.com/
Contributors: Dan Cameron, Jonathan Brinley & Nathan Stryker
Text Domain: group-buying
Domain Path: /lang
*/


/**
 * GBS directory
 */
define( 'GB_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
/**
 * GB URL
 */
define( 'GB_URL', plugins_url( '', __FILE__ ) );
/**
 * URL to resources directory
 */
define( 'GB_RESOURCES', plugins_url( 'resources/', __FILE__ ) );
/**
 * Minimum supported version of WordPress
 */
define( 'GBS_SUPPORTED_WP_VERSION', version_compare( get_bloginfo( 'version' ), '3.4', '>=' ) );
/**
 * Minimum supported version of PHP
 */
define( 'GBS_SUPPORTED_PHP_VERSION', version_compare( phpversion(), '5.2.4', '>=' ) );

/**
 * Compatibility check
 */
if ( GBS_SUPPORTED_WP_VERSION && GBS_SUPPORTED_PHP_VERSION ) {
	group_buying_load();
	do_action( 'group_buying_load' );
} else {
	/**
	 * Disable GBS and add fail notices if compatibility check fails
 	 * @package GBS
 	 * @subpackage Base
	 * @return string inserted within the WP dashboard
	 */
	gb_deactivate_plugin();
	add_action( 'admin_head', 'gbs_fail_notices' );
	function gbs_fail_notices() {
		if ( !GBS_SUPPORTED_WP_VERSION ) {
			echo '<div class="error"><p><strong>Group Buying Plugin</strong> requires WordPress 3.3 or higher. Please upgrade WordPress and activate the Group Buying Plugin again.</p></div>';
		}
		if ( !GBS_SUPPORTED_PHP_VERSION ) {
			echo '<div class="error"><p><strong>Group Buying Plugin</strong> requires PHP 5.2.4 or higher. Talk to your web host about using a secure version of PHP and activate the Group Buying Plugin after they upgrade your server.</p></div>';
		}
	}
}

/**
 * Load the GBS application
 * @package GBS
 * @subpackage Base
 * @return void
 */
function group_buying_load() {
	if ( class_exists( 'Group_Buying' ) ) {
		gb_deactivate_plugin();
		return; // already loaded, or a name collision
	}
	// router plugin dependency
	require_once GB_PATH.'/controllers/router/gb-router.php';

	// base classes
	require_once GB_PATH.'/groupBuying.class.php';
	require_once GB_PATH.'/models/groupBuyingModel.class.php';
	require_once GB_PATH.'/models/groupBuyingPostType.class.php';
	require_once GB_PATH.'/controllers/groupBuyingController.class.php';
	require_once GB_PATH.'/controllers/groupBuyingPaymentProcessors.class.php';
	require_once GB_PATH.'/controllers/groupBuyingOffsiteProcessors.class.php';
	require_once GB_PATH.'/controllers/groupBuyingCreditCardProcessors.class.php';


	// models
	require_once GB_PATH.'/models/groupBuyingDeal.class.php';
	require_once GB_PATH.'/models/groupBuyingAccount.class.php';
	require_once GB_PATH.'/models/groupBuyingCart.class.php';
	require_once GB_PATH.'/models/groupBuyingGift.class.php';
	require_once GB_PATH.'/models/groupBuyingMerchant.class.php';
	require_once GB_PATH.'/models/groupBuyingNotification.class.php';
	require_once GB_PATH.'/models/groupBuyingPayment.class.php';
	require_once GB_PATH.'/models/groupBuyingPurchase.class.php';
	require_once GB_PATH.'/models/groupBuyingReport.class.php';
	require_once GB_PATH.'/models/groupBuyingRecord.class.php';
	require_once GB_PATH.'/models/groupBuyingVoucher.class.php';

	// controllers
	require_once GB_PATH.'/controllers/groupBuyingAccounts.class.php';
	require_once GB_PATH.'/controllers/groupBuyingAffiliates.class.php';
	require_once GB_PATH.'/controllers/groupBuyingCarts.class.php';
	require_once GB_PATH.'/controllers/groupBuyingCheckouts.class.php';
	require_once GB_PATH.'/controllers/groupBuyingDeals.class.php';
	require_once GB_PATH.'/controllers/groupBuyingGifts.class.php';
	require_once GB_PATH.'/controllers/groupBuyingMerchants.class.php';
	require_once GB_PATH.'/controllers/groupBuyingNotifications.class.php';
	require_once GB_PATH.'/controllers/groupBuyingPayments.class.php';
	require_once GB_PATH.'/controllers/groupBuyingPurchases.class.php';
	require_once GB_PATH.'/controllers/groupBuyingReports.class.php';
	require_once GB_PATH.'/controllers/groupBuyingRecords.class.php';
	require_once GB_PATH.'/controllers/groupBuyingShipping.class.php'; // v. 3.4
	require_once GB_PATH.'/controllers/groupBuyingTax.class.php'; // v. 3.4
	require_once GB_PATH.'/controllers/groupBuyingUI.class.php';
	require_once GB_PATH.'/controllers/groupBuyingUpdateCheck.class.php';
	require_once GB_PATH.'/controllers/groupBuyingUpgrades.class.php';
	require_once GB_PATH.'/controllers/groupBuyingVouchers.class.php';
	require_once GB_PATH.'/controllers/groupBuyingAdminPurchases.class.php';
	require_once GB_PATH.'/controllers/groupBuyingAddons.class.php';
	require_once GB_PATH.'/controllers/groupBuyingFeeds.class.php';
	require_once GB_PATH.'/controllers/groupBuyingHelp.class.php'; // v. 3.4
	require_once GB_PATH.'/controllers/groupBuyingDestroyer.class.php'; // v. 3.9
	require_once GB_PATH.'/controllers/groupBuyingAPI.class.php'; // v. 4.1

	// payment processors
	require_once GB_PATH.'/controllers/payment_processors/groupBuyingAccountBalance.class.php';
	require_once GB_PATH.'/controllers/payment_processors/groupBuyingCredits.class.php';
	require_once GB_PATH.'/controllers/payment_processors/groupBuyingHybridPaymentProcessor.class.php';
	require_once GB_PATH.'/controllers/payment_processors/groupBuyingPaypal.class.php';
	//require_once GB_PATH.'/controllers/payment_processors/groupBuyingPaypalHybrid.class.php';
	require_once GB_PATH.'/controllers/payment_processors/groupBuyingPaypalWPP.class.php';
	require_once GB_PATH.'/controllers/payment_processors/groupBuyingPaypalAP.class.php';
	require_once GB_PATH.'/controllers/payment_processors/groupBuyingAuthorize.net.class.php';
	require_once GB_PATH.'/controllers/payment_processors/groupBuyingNMI.net.class.php';
	do_action( 'gb_register_processors' );

	// template tags
	require_once GB_PATH.'/template_tags/account.php';
	require_once GB_PATH.'/template_tags/affiliate.php';
	require_once GB_PATH.'/template_tags/cart.php';
	require_once GB_PATH.'/template_tags/checkout.php';
	require_once GB_PATH.'/template_tags/deals.php';
	require_once GB_PATH.'/template_tags/deprecated.php';
	require_once GB_PATH.'/template_tags/forms.php';
	require_once GB_PATH.'/template_tags/location.php';
	require_once GB_PATH.'/template_tags/merchant.php';
	require_once GB_PATH.'/template_tags/payment.php';
	require_once GB_PATH.'/template_tags/reports.php';
	require_once GB_PATH.'/template_tags/ui.php';
	require_once GB_PATH.'/template_tags/utility.php';
	require_once GB_PATH.'/template_tags/voucher.php';
	require_once GB_PATH.'/template_tags/gifts.php';

	// router plugin dependency
	require_once GB_PATH.'/controllers/syndication-service/group-buying-aggregator.php';

	// initialize objects
	// models
	Group_Buying_Post_Type::init(); // initialize query caching
	Group_Buying_Deal::init();
	Group_Buying_Account::init();
	Group_Buying_Cart::init();
	Group_Buying_Gift::init();
	Group_Buying_Merchant::init();
	Group_Buying_Notification::init();
	Group_Buying_Payment::init();
	Group_Buying_Purchase::init();
	Group_Buying_Record::init();
	//Group_Buying_Report::init();
	Group_Buying_Voucher::init();


	// controllers
	Group_Buying_Update_Check::init();
	Group_Buying_Controller::init();
	Group_Buying_Deals::init();
	Group_Buying_Accounts::init();
	Group_Buying_Carts::init();
	Group_Buying_Checkouts::init();
	Group_Buying_Merchants::init();
	Group_Buying_Notifications::init();
	Group_Buying_Vouchers::init();
	Group_Buying_Gifts::init();
	Group_Buying_Offsite_Processors::init();
	Group_Buying_Payment_Processors::init();
	Group_Buying_Purchases::init();
	Group_Buying_Payments::init();
	Group_Buying_Reports::init();
	Group_Buying_Records::init();
	Group_Buying_Core_Shipping::init();
	Group_Buying_Core_Tax::init();
	Group_Buying_UI::init();
	Group_Buying_Upgrades::init();
	Group_Buying_Affiliates::init();
	Group_Buying_Admin_Purchases::init();
	Group_Buying_Addons::init();
	Group_Buying_Feeds::init();
	Group_Buying_Help::init();
	Group_Buying_Destroy::init();
	Group_Buying_API::init();
}


/**
 * do_action when plugin is activated.
 * @package GBS
 * @subpackage Base
 * @ignore
 */
register_activation_hook( __FILE__, 'gb_plugin_activated' );
function gb_plugin_activated() {
	do_action( 'gb_plugin_activation_hook' );
}
/**
 * do_action when plugin is deactivated.
 * @package GBS
 * @subpackage Base
 * @ignore
 */
register_deactivation_hook( __FILE__, 'gb_plugin_deactivated' );
function gb_plugin_deactivated() {
	do_action( 'gb_plugin_deactivation_hook' );
}

function gb_deactivate_plugin() {
	if ( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {
		require_once ABSPATH.'/wp-admin/includes/plugin.php';
		deactivate_plugins( __FILE__ );
	}
}
