<?php

/**
 * Manages upgrades. Example, the major upgrades from version 2.x to version 3.x
 *
 * @package GBS
 * @subpackage Base
 */
class Group_Buying_Upgrades extends Group_Buying_Controller {

	const FORM_ACTION = 'perform_upgrade';
	const MENU_NAME = 'gb_update';

	static function init() {
		add_action( 'init', array( get_class(), 'check_for_upgrade' ), 100, 0 );
	}

	static function check_for_upgrade() {
		// Check whether a version upgrade needs to be applied
		$old_version = self::get_old_version();
		$new_version = self::get_new_version();

		// If old version is 3.0 or greater, automatically upgrade
		// Otherwise, site owner will have to manually choose to perform upgrade
		if ( version_compare( $new_version, $old_version, '>' ) ) {
			if ( version_compare( $old_version, '3.0', '>=' ) ) {
				self::upgrade( $old_version, $new_version );
			} else {
				add_action( 'admin_menu', array( get_class(), 'register_upgrade_page' ) );
			}
		} else {
			$current_version = get_option( 'gb_version' );
			if ( false == $current_version ) {
				add_option( 'gb_version', Group_Buying::GB_VERSION );
			}
		}
	}

	static function upgrade( $old_version, $new_version ) {
		while ( $old_version != $new_version ) {
			// Give 15 minutes for each upgrade pass
			// This should be more than enough time
			ignore_user_abort( 1 ); // run script in background
			set_time_limit( 0 ); // run script forever
			switch ( $old_version ) {
				case '2.1':
				case '2.3':
					echo '<p>' . self::__( 'Upgrading Accounts...' ) , '</p>';
					flush();
					Group_Buying_Accounts_Upgrade::upgrade_3_0();
					echo '<p>' . self::__( 'Upgrading Deals, Vouchers, and Purchases...' ) , '</p>';
					flush();
					Group_Buying_Deals_Upgrade::upgrade_3_0();
					echo '<p>' . self::__( 'Upgrading Merchants...' ) , '</p>';
					flush();
					Group_Buying_Merchants_Upgrade::upgrade_3_0();
					$old_version = '3.0';
					echo '<p>' . self::__( '...Done' ) . '</p>';
					break;
				default:
					$old_version = $new_version;
			}
		}

		/*Group_Buying_Deals_Upgrade::upgrade( $old_version, $new_version );
		Group_Buying_Accounts_Upgrade::upgrade( $old_version, $new_version );
		Group_Buying_Purchases_Upgrade::upgrade( $old_version, $new_version );
		Group_Buying_Vouchers_Upgrade::upgrade( $old_version, $new_version );
		Group_Buying_Notifications_Upgrade::upgrade( $old_version, $new_version );
		 */

		update_option( 'gb_version', $new_version );
	}

	static function get_old_version() {

		// If there's a stored version, that's the version of the plugin
		$stored_version = get_option( 'gb_version', false );
		if ( $stored_version ) {
			return $stored_version;
		} else {
			global $wpdb;

			// Version 2.1 stored purchases as 'codes' associated with deals
			$codes_count = $wpdb->get_var( "SELECT COUNT(post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_dealsCodesArray'" );
			if ( $codes_count ) {
				return '2.1';
			}

			// Version 2.3 stored purchases as 'purchase records' associated with deals
			$purchase_count = $wpdb->get_var( "SELECT COUNT(post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_purchaseRecords'" );
			if ( $purchase_count ) {
				return '2.3';
			}

			// If there have been no purchases, but there are old deals, we need to upgrade from 2.3
			$old_deals = $wpdb->get_var( "SELECT Count(ID) FROM {$wpdb->posts} WHERE post_type = 'deal'" );
			if ( $old_deals ) {
				return '2.3';
			}

			// If all else fails, this is probably a fresh install, so the 'old' version is 3.0
			return '3.0';
		}
	}

	static function get_new_version() {
		return Group_Buying::GB_VERSION;
	}

	static function register_upgrade_page() {
		add_submenu_page( self::TEXT_DOMAIN, self::__( 'Update Group Buying' ), self::__( 'Update' ), 'manage_options', self::TEXT_DOMAIN . '/' . self::MENU_NAME, array( get_class(), 'display_upgrade_page' ) );
	}

	static function display_upgrade_page() {
		if ( isset( $_GET['action'] ) && self::FORM_ACTION == $_GET['action'] ) {
			self::load_view( 'admin/perform-upgrade', array() );
		} else {
			self::load_view( 'admin/upgrade', array() );
		}
	}

	static function perform_upgrade() {
		$old_version = self::get_old_version();
		$new_version = self::get_new_version();

		if ( $old_version == $new_version ) {
			echo '<p>' . self::__( 'No updates are available' ) . '</p>';
		} else {
			self::upgrade( $old_version, $new_version );
		}
	}
}
