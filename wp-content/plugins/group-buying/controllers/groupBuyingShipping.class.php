<?php

/**
 * Shipping Controller
 *
 * @package GBS
 * @subpackage Checkout
 */
class Group_Buying_Core_Shipping extends Group_Buying_Controller {

	const SHIPPING_OPTION = 'gb_enable_shipping';
	const SHIPPING_OPTION_LOCAL = 'gb_enable_shipping_local';
	const SHIPPING_RATES = 'gb_shipping_rate_table';
	const SHIPPING_MODES = 'gb_shipping_modes';
	protected static $settings_page;
	private static $enable;
	private static $enable_local_based;
	private static $rates;
	protected static $mode;

	final public static function init() {

		// Deal
		add_filter( 'gb_deal_get_shipping', array( get_class(), 'filter_shipping' ), 5, 4 );

		// Cart
		add_filter( 'gb_cart_extras', array( get_class(), 'cart_shipping' ) );
		add_filter( 'gb_cart_line_items', array( get_class(), 'line_items' ), 10, 2 );
		add_filter( 'gb_cart_get_total', array( get_class(), 'cart_total' ), 10, 2 );

		// Checkout
		add_filter( 'gb_checkout_panes_'.Group_Buying_Checkouts::PAYMENT_PAGE, array( get_class(), 'payment_checkout_pane' ), 0, 2 );
		add_filter( 'gb_valid_process_payment_page', array( get_class(), 'valid_process_payment_page' ), 10, 2 );
		add_filter( 'gb_checkout_panes_'.Group_Buying_Checkouts::REVIEW_PAGE, array( get_class(), 'review_checkout_pane' ), 0, 2 );

		// Reports
		// Purchase Report
		add_filter( 'set_deal_purchase_report_data_column', array( get_class(), 'set_deal_purchase_report_data_column' ), 10, 1 );
		add_filter( 'set_deal_purchase_report_data_records', array( get_class(), 'set_deal_purchase_report_data_records' ), 10, 1 );
		// Merchant Report
		add_filter( 'set_merchant_purchase_report_column', array( get_class(), 'set_deal_purchase_report_data_column' ), 10, 1 );
		add_filter( 'set_merchant_purchase_report_records', array( get_class(), 'set_deal_purchase_report_data_records' ), 10, 1 );
		// Vouchers
		add_filter( 'set_deal_voucher_report_data_column', array( get_class(), 'set_deal_purchase_report_data_column' ), 10, 1 );
		add_filter( 'set_deal_voucher_report_data_records', array( get_class(), 'set_deal_purchase_report_data_records' ), 10, 1 );
		// Merchant Report
		add_filter( 'set_merchant_voucher_report_data_column', array( get_class(), 'set_deal_purchase_report_data_column' ), 10, 1 );
		add_filter( 'set_merchant_voucher_report_data_records', array( get_class(), 'set_deal_purchase_report_data_records' ), 10, 1 );


		// Options
		self::$settings_page = self::register_settings_page( 'gb_shipping_settings', self::__( 'Group Buying Shipping Settings' ), self::__( 'Shipping Settings' ), 16, FALSE, 'general' );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 40, 0 );
		self::$enable = get_option( self::SHIPPING_OPTION, 'TRUE' );
		self::$enable_local_based = get_option( self::SHIPPING_OPTION_LOCAL, 'FALSE' );
		self::$rates = get_option( self::SHIPPING_RATES );
		self::$mode = get_option( self::SHIPPING_MODES, "Flat Rate \$5\nFlat Rate \$10\nReduced Rate" );
		add_action( 'admin_init', array( get_class(), 'queue_deal_resources' ) );

		if ( self::shipping_enabled() ) {
			add_action( 'gb_meta_box_deal_price', array( get_class(), 'display_shipping_meta' ), 10, 6 );
			// Add shipping to the purchase object
			add_action( 'gb_new_purchase', array( get_class(), 'filter_new_purcase' ), 15, 2 );
		}
	}


	public static function queue_deal_resources() {
		wp_enqueue_style( 'group-buying-admin-deal', GB_URL . '/resources/css/deal.admin.gbs.css' );
		wp_enqueue_style( 'jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

	}

	public static function filter_shipping( $meta = null, Group_Buying_Deal $deal, $qty = 1, $local = null ) {
		$option = $deal->get_shippable();
		$ship = 0;
		switch ( $option ) {
		case 'FLAT':
			$ship = self::shipping_flat( $deal );
			break;
		case 'FLATIND':
			$ship = self::shipping_flat( $deal, $qty, TRUE );
			break;
		case 'QUANTITY':
			$ship = self::shipping_dyn( $deal, $qty );
			break;
		case 'MODE':
			$ship = self::shipping_mode( $deal, $qty, $local );
			break;
		default:
			if ( is_numeric( $meta ) ) {
				$ship = $meta;
			}
			break;
		}

		return apply_filters( 'gb_control_get_shipping', $ship, $option, $meta, $deal, $qty, $local );
	}

	public static function get_shipping( Group_Buying_Deal $deal, $qty = 1, $local = null ) {
		return self::filter_shipping( null, $deal, $qty, $local );
	}

	public static function shipping_flat( Group_Buying_Deal $deal, $qty = 1, $per_item = FALSE ) {
		$rate = $deal->get_shipping_meta();
		if ( $per_item ) {
			$rate = $rate*$qty;
		}
		return $rate;
	}

	public static function shipping_dyn( Group_Buying_Deal $deal, $qty = 1 ) {
		$shipping_dyn = $deal->get_shipping_dyn_price();
		$max_qty_found = 0;
		if ( !empty( $shipping_dyn ) ) {
			sort( $shipping_dyn );
			foreach ( $shipping_dyn as $rate_id => $data ) {
				if ( $qty >= $data['quantity'] && $data['quantity'] > $max_qty_found ) {
					$price = $data['rate'];
					$per_item = ( 'TRUE' == $data['per_item'] ) ? TRUE : FALSE;
					$max_qty_found = $data['quantity'];
				}
			}
		}
		if ( $per_item ) {
			$price = $price*$qty;
		}
		return $price;
	}

	public static function shipping_mode( Group_Buying_Deal $deal, $qty = 1, $local = null ) {
		$mode = $deal->get_shipping_mode();
		return self::get_rate( $mode, $qty, $local );
	}


	/**
	 *
	 *
	 * @return Hook into cart to register if shipping is being charged
	 */
	public static function cart_shipping( Group_Buying_Cart $cart ) {
		$shipping = self::cart_shipping_total( $cart, null, TRUE );
		$bool = ( $shipping ) ? TRUE : FALSE ;
		return apply_filters( 'gb_shipping_cart_shipping', $bool, $shipping, $cart );
	}

	public static function line_items( $line_items, Group_Buying_Cart $cart ) {
		if ( self::cart_shipping( $cart ) ) {
			$asterisk = '';
			$shipping_title = self::__( 'Shipping' );
			if ( self::shipping_local_enabled() ) {
				$account = Group_Buying_Account::get_instance();
				$ship_address = $account->get_ship_address();
				$asterisk = '*';
				$shipping_title = ( isset( $ship_address['zone'] ) && isset( $ship_address['country'] ) ) ? self::__( 'Calculated Shipping' ) : self::__( 'Est. Shipping' );
			}
			$shipping = array(
				'shipping' => array(
					'label' => $shipping_title,
					'data' => gb_get_formatted_money( self::cart_shipping_total( $cart ) ).$asterisk,
					'weight' => 100,
				)
			);
			$line_items = array_merge( $shipping, $line_items );
		}
		return $line_items;
	}

	public static function cart_total( $filtered_total, Group_Buying_Cart $cart ) {
		if ( self::cart_shipping( $cart ) ) {
			$total = $filtered_total + self::cart_shipping_total( $cart );
			return apply_filters( 'gb_shipping_cart_total', $total, $filtered_total, $cart );
		} else {
			return apply_filters( 'gb_shipping_cart_total', $filtered_total, $filtered_total, $cart );
		}
		
	}

	/**
	 * Get the shipping total
	 *
	 * @return float|int
	 */
	public static function cart_shipping_total( Group_Buying_Cart $cart, $local = null, $bool_return = FALSE ) {
		if ( !self::shipping_enabled() ) {
			return 0;
		}

		$shipping_total = 0;
		$total_deals = array();

		if ( self::shipping_local_enabled() && NULL === $local ) {
			$account = Group_Buying_Account::get_instance();
			$address = $account->get_ship_address();
			if ( empty( $address ) ) {
				$address = $account->get_address();
			}
			if ( !empty( $address ) ) {
				$local = array(
					'zone' => $address['zone'],
					'country' => $address['country'],
				);
			}
		}

		$deals_totaled = self::deal_quantity( $cart->get_items() );
		foreach ( $deals_totaled as $deal_id => $qty ) {
			$deal = Group_Buying_Deal::get_instance( $deal_id );
			if ( is_a( $deal, 'Group_Buying_Deal' ) ) {
				$deal_shipping = self::get_shipping( $deal, $qty, $local );
				// $bool_return available for a quick return since
				// any item with a numeric return will have a shipping
				// rate, even if zero.
				if ( $bool_return && is_numeric( $deal_shipping ) && $deal_shipping >= 0.01 ) {
					return apply_filters( 'gb_shipping_cart_shipping_total_bool', TRUE, $cart, $local );
				}

				// increment the shipping total
				$shipping_total += $deal_shipping;
			}
		}

		// $bool_return if not previously returned the
		// $shipping_total is zero (e.g. 0.00)
		if ( $bool_return )
			return apply_filters( 'gb_shipping_cart_shipping_total_bool', FALSE, $cart, $local );

		return gb_get_number_format( apply_filters( 'gb_shipping_cart_shipping_total', $shipping_total, $cart, $local ) );
	}


	/**
	 * Get the shipping total for item (used for tax class)
	 *
	 * @return float|int
	 */
	public static function cart_item_shipping_total( Group_Buying_Cart $cart, $item = array(), $local = NULL ) {
		$shipping_total = 0;
		$qty = self::deal_quantity( $cart->get_items(), $item['deal_id'] );
		$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
		if ( is_a( $deal, 'Group_Buying_Deal' ) ) {
			$shipping_total += self::get_shipping( $deal, $qty, $local );
		}
		return apply_filters( 'gb_shipping_cart_item_shipping_total', $shipping_total, $cart, $item, $local );
	}

	/**
	 * Get the shipping total
	 *
	 * @return float|int
	 */
	public static function purchase_shipping_total( Group_Buying_Purchase $purchase, $payment_method = NULL, $local = NULL, $distribute = TRUE ) {
		$shipping_total = 0;

		if ( self::shipping_local_enabled() && NULL === $local ) {
			$local = $purchase->get_shipping_local();
		}

		$deal_count = self::deal_quantity( $purchase->get_products(), FALSE, 'deal_count' );
		$deals_totaled = self::deal_quantity( $purchase->get_products() );

		foreach ( $purchase->get_products() as $item ) {
			if ( isset( $item['payment_method'][$payment_method] ) ) {
				$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
				$qty = $deals_totaled[$item['deal_id']];
				$shipping = self::get_shipping( $deal, $qty, $local );
				if ( $distribute && $deal_count[$item['deal_id']] ) { // distribute the shipping costs across all items since $shipping is based off the entire purchase.
					$shipping = $shipping/$deal_count[$item['deal_id']];
				}
				// Calculate if the item has multiple payment methods
				$ratio = 1;
				if ( $item['payment_method'][$payment_method] != $item['price'] ) {
					$ratio = @( $item['payment_method'][$payment_method] / $item['price'] );
				}
				$shipping_total += $shipping*$ratio;
			}
		}
		return apply_filters( 'gb_shipping_purchase_shipping_total', $shipping_total, $purchase, $payment_method, $local, $distribute );
	}

	/**
	 * Get item shipping rate based on a purchase.
	 */
	public static function purchase_item_shipping( Group_Buying_Purchase $purchase, $item = array(), $local = NULL, $distribute = TRUE ) {
		$shipping = 0;
		if ( self::shipping_local_enabled() && NULL === $local ) {
			$local = $purchase->get_shipping_local();
		}

		$count = self::deal_quantity( $purchase->get_products(), $item['deal_id'], 'deal_count' );
		$qty = self::deal_quantity( $purchase->get_products(), $item['deal_id'] );

		$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
		$shipping = self::get_shipping( $deal, $qty, $local );

		if ( $distribute && $count ) { // distribute the shipping costs across all items since $shipping is based off the entire purchase.
			$shipping = $shipping/$count;
		}

		return apply_filters( 'gb_shipping_purchase_item_shipping', $shipping, $purchase, $item, $local, $distribute );
	}


	public static function get_rate( $mode = NULL, $qty = 1, $local = NULL ) {
		if ( empty( self::$rates ) ) {
			return;
		}
		$price = 0;
		foreach ( self::$rates as $rate_id => $data ) {
			// local based
			if ( NULL != $local && self::shipping_local_enabled() ) {
				if ( $mode == $data['mode'] ) {
					// Look for the first match of region and zone.
					if ( !empty( $data['zones'] ) && !empty( $data['regions'] )  ) {
						if ( in_array( $local['zone'], $data['zones'] ) && in_array( $local['country'], $data['regions'] ) ) {
							$price = $data['rate'];
							$per_item = ( 'TRUE' == $data['per_item'] ) ? TRUE : FALSE;
							break; // No point of continuing.
						}
					}
					// Try to match a zone, which is higher priority than regions and make sure not to override a previously matched zone price
					elseif ( !empty( $data['zones'] ) && !$matched_zone ) {
						if ( in_array( $local['zone'], $data['zones'] ) ) { // Zone match
							$price = $data['rate'];
							$per_item = ( 'TRUE' == $data['per_item'] ) ? TRUE : FALSE;
							if ( count( $data['zones'] ) == 1 ) {
								break; // Based on priority, if the zone match is specific.
							}
							$matched_zone = TRUE;
						}
					}
					// Match regision and make sure not to override a previously matched zone or region price
					elseif ( !empty( $data['regions'] ) && !$matched_zone && !$matched_region ) {
						if ( in_array( $local['country'], $data['regions'] ) ) {
							$price = $data['rate'];
							$per_item = ( 'TRUE' == $data['per_item'] ) ? TRUE : FALSE;
							$matched_region = TRUE;
						}
					}
				}
			}
			// Default
			else {
				if ( $mode == $data['mode'] ) {
					$price = $data['rate'];
					$per_item = ( 'TRUE' == $data['per_item'] ) ? TRUE : FALSE;
					break;  // no point of continuing since this is the first
				}
			}

		}
		if ( $per_item ) {
			$price = $price*$qty;
		}
		return apply_filters( 'gb_get_shipping_rate', $price, $mode, $qty, $local );
	}

	/**
	 * Display the payment form
	 *
	 * @return array
	 */
	public function payment_checkout_pane( $panes, Group_Buying_Checkouts $checkout ) {
		if ( self::cart_shipping( $checkout->get_cart() ) ) {
			$update = ( self::shipping_local_enabled() ) ? 1 : 0 ;
			$panes['shipping'] = array(
				'weight' => 500, // after the payment form
				'body' => self::load_view_to_string( 'checkout/shipping', array( 'fields' => self::get_shipping_fields(), 'update' => $update ) ),
			);
		}
		return $panes;
	}

	private function get_shipping_fields() {
		$fields = parent::get_standard_address_fields( FALSE, $shipping = TRUE );
		$fields['copy_billing'] = array(
			'weight' => 0,
			'label' => self::__( 'Same As Billing' ),
			'type' => 'checkbox',
			'required' => FALSE
		);
		$fields = apply_filters( 'gb_checkout_fields_shipping', $fields );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}


	/**
	 * Display the final review page
	 *
	 * @param array   $panes
	 * @param Group_Buying_Checkout $checkout
	 * @return array
	 */
	public function review_checkout_pane( $panes, $checkout ) {
		if ( isset( $checkout->cache['shipping'] ) ) {
			$panes['shipping'] = array(
				'weight' => 500,
				'body' => self::load_view_to_string( 'checkout/shipping-review', array( 'data' => $checkout->cache['shipping'] ) ),
			);
		}
		return $panes;
	}


	/**
	 * Process the payment form
	 *
	 * @return void
	 */
	public function valid_process_payment_page( $valid, Group_Buying_Checkouts $checkout ) {
		if ( self::cart_shipping( $checkout->get_cart() ) ) {
			if ( apply_filters( 'gb_valid_process_payment_page_fields', __return_true() ) ) {
				$fields = self::get_shipping_fields();
				foreach ( $fields as $key => $data ) {
					$checkout->cache['shipping'][$key] = isset( $_POST['gb_shipping_'.$key] )?$_POST['gb_shipping_'.$key]:'';
					if ( isset( $data['required'] ) && $data['required'] && !( isset( $checkout->cache['shipping'][$key] ) && $checkout->cache['shipping'][$key] != '' ) ) {
						$valid = FALSE;
						self::set_message( sprintf( self::__( '"%s" field is required.' ), $data['label'] ), self::MESSAGE_STATUS_ERROR );
					}
				}
			}
			// Set the shipping regardless if there's an error or not.
			$account = Group_Buying_Account::get_instance();
			$local = array(
				'zone' => $_POST['gb_shipping_zone'],
				'country' => $_POST['gb_shipping_country'],
				'street' => $_POST['gb_shipping_street'],
				'postal_code' => $_POST['gb_shipping_postal_code'],
				'city' => $_POST['gb_shipping_city'],
			);
			$account->set_ship_address( $local );

			// If local is enabled check the account's shipping and what's submitted.
			if ( self::shipping_local_enabled() ) {
				$address = $account->get_ship_address();
				if ( empty( $address ) ) {
					$address = $account->get_address();
				}
				// If a mismatch: mark the page as invalid and store the new shipping address.
				if ( isset( $_POST['gb_shipping_country'] ) && isset( $_POST['gb_shipping_zone'] ) && ( $_POST['gb_shipping_zone'] != $address['zone'] || $_POST['gb_shipping_country'] != $address['country'] ) ) {
					$valid = FALSE;
					self::set_message( self::__( 'Shipping Costs Updated.' ), self::MESSAGE_STATUS_ERROR );
				}
			}
		}
		return $valid;
	}


	/*
	 * Set the local to the purchase
	 */
	public static function filter_new_purcase( Group_Buying_Purchase $purchase, $args ) {
		if ( isset( $args['checkout'] ) && is_a( $args['checkout'], 'Group_Buying_Checkouts' ) ) {
			$checkout = $args['checkout'];
			if ( empty($checkout->cache['shipping']) ) {
				return; // no shipping cache, nothing to save
			}
			$shipping = $checkout->cache['shipping'];
			$local = array(
				'zone' => $shipping['zone'],
				'country' => $shipping['country'],
				'first_name' => $shipping['first_name'],
				'last_name' => $shipping['last_name'],
				'street' => $shipping['street'],
				'city' => $shipping['city'],
				'postal_code' => $shipping['postal_code'],
			);
			$purchase->set_shipping_local( $local );
		}
	}

	public static function set_deal_purchase_report_data_column( $columns ) {
		$columns['ship_name'] = self::__( 'Shipping Name' );
		$columns['ship'] = self::__( 'Shipping Address' );
		return $columns;
	}
	public static function set_deal_purchase_report_data_records( $array ) {
		if ( !is_array( $array ) ) {
			return; // nothing to do.
		}
		$new_array = array();
		foreach ( $array as $records ) {
			$purchase = Group_Buying_Purchase::get_instance( $records['id'] );
			if ( is_a( $purchase, 'Group_Buying_Purchase' ) ) {
				$address = $purchase->get_shipping_local();
				if ( !empty( $address ) && isset( $address['street'] ) ) {
					$records['ship_name'] = $address['first_name'].' '.$address['last_name'];
					$records['ship'] = $address['street']."\n".$address['city'].', '.$address['zone'].' '.$address['postal_code']."\n".$address['country'];
				}
			}
			$new_array[] = $records;
		}
		return $new_array;
	}

	public static function deal_quantity( $items, $single = FALSE, $return = 'total_deals' ) {
		$deal_count = array();
		$total_deals = array();
		// Need to get the real quantity of deals since attributes end up being individual items
		foreach ( $items as $item ) {

			// Count how many items share this deal_id
			if ( empty($deal_count[$item['deal_id']]) || $deal_count[$item['deal_id']] < 1 ) {
				$deal_count[$item['deal_id']] = 1;
			} else {
				$deal_count[$item['deal_id']]++;
			}

			$quantity = ( isset( $item['quantity'] ) ) ? $item['quantity'] : 1;
			for ( $i=0; $i < $quantity; $i++ ) { // not forgetting quantity purchasing
				$total_deals[] = $item['deal_id'];
			}
		}

		// deal_count returns the quantity of items that share the same deal.
		if ( $return == 'deal_count' ) {
			if ( FALSE !== $single ) {
				$deal_count = $deal_count[$single];
			}
			return apply_filters( 'gb_shipping_deal_count', $deal_count, $items, $single );
		}

		// default: returns the total quantity of all similar items based on deals
		$deals_totaled = array_count_values( $total_deals ); // clean up the array and get the real quantity
		// return total quantity for a single deal, instead of an array.
		if ( FALSE !== $single ) {
			$deals_totaled = $deals_totaled[$single];
		}
		return apply_filters( 'gb_shipping_total_deals', $deals_totaled, $items, $single );
	}

	public static function shipping_enabled() {
		if ( self::$enable !== 'TRUE' ) {
			return FALSE;
		}
		return TRUE;
	}

	public static function shipping_local_enabled() {
		if ( self::$enable_local_based !== 'TRUE' ) {
			return FALSE;
		}
		return TRUE;
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

	protected function __construct() {}


	public static function register_settings_fields() {
		$page = self::$settings_page;
		$section = 'gb_shipping';
		add_settings_section( $section, '', array( get_class(), 'display_settings_section' ), $page );
		// Settings
		register_setting( $page, self::SHIPPING_OPTION );
		register_setting( $page, self::SHIPPING_OPTION_LOCAL, array( get_class(), 'save_local_option' ) );
		register_setting( $page, self::SHIPPING_MODES );
		register_setting( $page, self::SHIPPING_RATES, array( get_class(), 'save_rates' ) );
		// Fields
		add_settings_field( self::SHIPPING_OPTION, self::__( 'Shipping Fees' ), array( get_class(), 'display_enable' ), $page, $section );
		add_settings_field( self::SHIPPING_OPTION_LOCAL, self::__( 'Location Based' ), array( get_class(), 'display_enable_local' ), $page, $section );
		add_settings_field( self::SHIPPING_RATES, self::__( 'Shipping Classes' ), array( get_class(), 'display_modes' ), $page, $section );
		add_settings_field( self::SHIPPING_MODES, self::__( 'Shipping Rates' ), array( get_class(), 'display_rates' ), $page, $section );
	}

	public function display_settings_section() {
		// printf(self::__('Group Buying Site shipping options.'));
	}
	public static function display_enable() {
		echo '<input type="checkbox" name="'.self::SHIPPING_OPTION.'" value="TRUE" '.checked( 'TRUE', self::$enable, FALSE ).'>&nbsp;'.self::__( 'Enable shipping.' );
	}
	public static function display_enable_local() {
		echo '<input type="checkbox" name="'.self::SHIPPING_OPTION_LOCAL.'" value="TRUE" '.checked( 'TRUE', self::$enable_local_based, FALSE ).'>&nbsp;'.self::__( 'Enable shipping based on location.' );
	}

	public static function display_modes() {
		echo '<textarea name="'.self::SHIPPING_MODES.'" rows="5" cols="20">'.self::$mode.'</textarea>';
		echo '<br/><span class="description">'.self::__( 'List 1 per line.' ).'</span>';
	}

	public static function save_local_option( $shipping_local ) {
		if ( !isset( $_POST[self::SHIPPING_OPTION_LOCAL] ) )
			return $shipping_local;
		if ( !isset( $_POST[self::SHIPPING_OPTION] ) || ( isset( $_POST[self::SHIPPING_OPTION_LOCAL] ) && $_POST[self::SHIPPING_OPTION] != 'TRUE' ) ) {
			$shipping_local = 'FALSE';
		}
		return $shipping_local;
	}

	public static function save_rates( $post ) {
		if ( !isset( $post['rate'] ) )
			return $post;
		$values = array();
		foreach ( $post['rate'] as $key => $rate_id ) {
			if ( $post['rate'][$key] == '' ) $post['rate'][$key] = 0;
			$values[] = array(
				'regions' => isset($post['regions'][$key])?$post['regions'][$key]:'',
				'zones' => isset($post['zones'][$key])?$post['zones'][$key]:'',
				'mode' => isset($post['mode'][$key])?$post['mode'][$key]:'',
				'rate' => isset($post['rate'][$key])?$post['rate'][$key]:'',
				'per_item' => isset($post['per_item'][$key])?$post['per_item'][$key]:'',
			);

		}
		return $values;
	}

	public static function display_rates() {
?>
			<script type="text/javascript">

			jQuery(document).ready( function($) {
				var $i = 0;
				$('a.gb-shipping-rate-remove').live( 'click', function() {
					$(this).parents('.gb-shipping-rate-row').remove();
					return false;
				});
				$('a#gb_add_shipping_rate').click( function() {
					var size = jQuery('tbody .gb-shipping-rate-row').size();
					var $row = $('<tr class="gb-shipping-rate-row">\
					<td class="mode">\
						<select name="<?php echo self::SHIPPING_RATES; ?>[mode]['+size+']"><?php $modes = explode( "\n", self::$mode ); foreach ( $modes as $name ) { echo '<option value="'.sanitize_title( $name ).'">'.esc_js( $name ).'</option>'; } ?></select>\
					</td>\
					<?php if ( self::shipping_local_enabled() ): ?><td class="regions">\
						<select name="<?php echo self::SHIPPING_RATES; ?>[regions]['+size+'][]" class="region_selections regions_added'+$i+'" multiple="multiple"><?php foreach ( parent::$countries as $key => $name ) { echo '<option value="'.$key.'">'.esc_js( $name ).'</option>'; } ?></select>\
						<select name="<?php echo self::SHIPPING_RATES; ?>[zones]['+size+'][]" class="zone_selections zones_added'+$i+'" multiple="multiple"><?php foreach ( self::get_state_options() as $group => $states ) { echo '<optgroup label="'.$group.'">'; foreach ($states as $key => $name) { echo '<option value="'.$key.'">&nbsp;'.$name.'</option>'; } echo '</optgroup>'; } ?></select>\
					</td><?php endif; ?>\
					<td class="rate"><?php echo gb_get_currency_symbol(); ?><input class="gb-shipping-rate" type="text" size="2" name="<?php echo self::SHIPPING_RATES ?>[rate]['+size+']" placeholder="0" ></td>\
					<td class="per_item"><input type="checkbox" name="<?php echo self::SHIPPING_RATES; ?>[per_item]['+size+']" value="TRUE" />&nbsp;<?php self::_e( 'Per Item' ) ?>\
					</td>\
					<td class="remove" valign="middle"><a type="button" class="button gb-shipping-rate-remove" href="#" title="<?php _e( 'Remove This Option' ); ?>"><?php self::_e( 'Remove' ); ?></a></td>\
				</tr>');
					$('div#gb-shipping-rate table tbody:first').append($row);
					$(".regions_added"+$i).select2({
						placeholder: '<?php self::_e( 'Select Regions' ) ?>',
						width: 'element'
					});
					$(".zones_added"+$i).select2({
						placeholder: '<?php self::_e( 'Select States' ) ?>',
						width: 'element'
					});
					$i++;
					return false;
				});

				$(".region_selections").select2({
					placeholder: '<?php self::_e( 'Select Regions' ) ?>',
					width: 'element'
				});
				$(".zone_selections").select2({
					placeholder: '<?php self::_e( 'Select States' ) ?>',
					width: 'element'
				});
			});
			</script>

			<div id="gb-shipping-rate">
				<table class="widefat" style="width:800px">
					<tbody>
						<?php if ( !empty( self::$rates ) ): ?>
							<?php foreach ( self::$rates as $rate_id => $data ): ?>
								<tr class="gb-shipping-rate-row">
									<td class="mode">
										<select name="<?php echo self::SHIPPING_RATES; ?>[mode][<?php echo $rate_id  ?>]">
											<?php
												$modes = explode( "\n", self::$mode );
												foreach ( $modes as $name ) {
													$san_name = sanitize_title( $name );
													echo '<option value="'.$san_name.'" '.selected( $san_name, $data['mode'], FALSE ).'>'.$name.'</option>';
												} ?>
										</select>
									</td>
									<?php if ( self::shipping_local_enabled() ): ?>
										<td class="regions"  style="width: 300px;">
											<select name="<?php echo self::SHIPPING_RATES; ?>[regions][<?php echo $rate_id  ?>][]" class="region_selections" multiple="multiple">
												<?php
													foreach ( self::get_country_options() as $key => $name ) {
														$selected = ( in_array( $key, $data['regions'] ) ) ? 'selected="selected"' : null ;
														echo '<option value="'.$key.'" '.$selected.'>'.$name.'</option>';
													} ?>
											</select>
											<select name="<?php echo self::SHIPPING_RATES; ?>[zones][<?php echo $rate_id ?>][]" class="zone_selections" multiple="multiple">
												<?php
													foreach ( self::get_state_options() as $group => $states ) {
														echo '<optgroup label="'.$group.'">';
														foreach ($states as $key => $name) {
															$selected = ( in_array( $key, $data['zones'] ) ) ? 'selected="selected"' : null ;
															echo '<option value="'.$key.'" '.$selected.'>&nbsp;'.$name.'</option>';
														}
														echo '</optgroup>';
													} ?>
											</select>
										</td>
									<?php endif ?>
									<td class="rate"><?php echo gb_get_currency_symbol(); ?><input class="gb-shipping-rate" type="text" size="2" value="<?php echo $data['rate']; ?>" name="<?php echo self::SHIPPING_RATES ?>[rate][<?php echo $rate_id ?>]" placeholder="0" /></td>
									<td class="per_item">
										<input type="checkbox" name="<?php echo self::SHIPPING_RATES; ?>[per_item][<?php echo $rate_id  ?>]" value="TRUE" <?php checked( 'TRUE', $data['per_item'] ) ?>/>&nbsp;<?php self::_e( 'Per Item' ) ?>
									</td>
									<td class="remove" valign="middle"><a type="button" class="button gb-shipping-rate-remove" href="#" title="<?php _e( 'Remove This Option' ); ?>"><?php self::_e( 'Remove' ); ?></a></td>
								</tr>
							<?php endforeach; ?>
						<?php endif ?>

					</tbody>
				</table>
			</div>
			<h4><a class="button" href="#" id="gb_add_shipping_rate"><?php _e( 'Add New Rate' ); ?></a></h4>
		<?php
		echo '<br/><span class="description">'.self::__( 'Rate priority is set by list order above (top to bottom) and matching criteria in this order <big>Country+State</big> > State <small>> Country</small>.' ).'</span>';
	}

	public static function display_shipping_meta( $price, $dynamic_price, $shipping, $shippable, $shipping_dyn, $shipping_mode, $tax, $taxable, $taxrate ) {
?>
		<script type="text/javascript">

			jQuery(document).ready( function($) {
				$('a.gb_delete_deal_shipping_rate').live( 'click', function() {
					$(this).parents('.gb_deal_dyn_shipping').remove();
					return false;
				});
				$('a#gb_add_deal_shipping_rate').click( function() {
					var dyn_shipping_size = jQuery('tbody .gb_deal_dyn_shipping').size();
					var $dyn_shipping_row = $('<tr class="gb_deal_dyn_shipping">\
					<td class="quantity"><input class="gb-shipping-quantity" type="text" size="2" name="deal_dynamic_shipping[quantity]['+dyn_shipping_size+']" placeholder="0" ><?php self::_e( '& up' ) ?></td>\
					<td class="rate"><?php echo gb_get_currency_symbol(); ?><input class="gb-shipping-rate" type="text" size="2" name="deal_dynamic_shipping[rate]['+dyn_shipping_size+']" placeholder="0" ></td>\
					<td class="ship"><input type="checkbox" name="deal_dynamic_shipping[per_item]['+dyn_shipping_size+']" value="TRUE" />&nbsp;<?php self::_e( 'Per Item' ) ?></td>\
					<td class="remove" valign="middle"><a type="button" class="button gb_delete_deal_shipping_rate" href="#" title="<?php _e( 'Remove This Option' ); ?>"><?php self::_e( 'Remove' ); ?></a></td></tr>');
					$('table#dyn_shipping_table tbody:first').append($dyn_shipping_row);
					return false;
				});
				$('.shipping_option_<?php echo $shippable ?>').show();
				$("input[name$='deal_base_shippable']").click(function() {
					var $selected = $(this).val();
					$('.cloak').hide();
					$('.shipping_option_'+$selected).show();
			    });
			});
		</script>
		<span class="meta_box_block_divider"></span>
		<div id="deal_shiping_meta_wrap" class="clearfix">
			<div class="gb_meta_column float_left">
				<p><label for="deal_base_shippable_false"><strong><?php self::_e( 'Shipping Fee' ); ?>:</strong></label></p>
				<p id="gb_deal_shipping_option">
					<input type="radio" name="deal_base_shippable" class="deal_base_shippable" id="deal_base_shippable_false" value="FALSE" <?php checked( $shippable, 'FALSE' ) ?>> <label for="deal_base_shippable_false"><?php gb_e( 'No Shipping Fee' )?></label><br/>
					<input type="radio" name="deal_base_shippable" class="deal_base_shippable" id="deal_base_shippable_flat" value="FLAT" <?php checked( $shippable, 'FLAT' ) ?>> <label for="deal_base_shippable_flat"><?php gb_e( 'Flat Rate' )?></label><br/>
					<input type="radio" name="deal_base_shippable" class="deal_base_shippable" id="deal_base_shippable_flatind" value="FLATIND" <?php checked( $shippable, 'FLATIND' ) ?>> <label for="deal_base_shippable_flatind"><?php gb_e( 'Flat Rate (per item)' )?></label><br/>
					<input type="radio" name="deal_base_shippable" class="deal_base_shippable" id="deal_base_shippable_quantity" value="QUANTITY" <?php checked( $shippable, 'QUANTITY' ) ?>> <label for="deal_base_shippable_quantity"><?php gb_e( 'Dynamic Rate' )?></label><br/>
					<input type="radio" name="deal_base_shippable" class="deal_base_shippable" id="deal_base_shippable_mode" value="MODE" <?php checked( $shippable, 'MODE' ) ?>> <label for="deal_base_shippable_mode"><?php gb_e( 'Shipping Class' )?></label><br/>
				</p>
			</div>
			<div class="gb_meta_column float_right">
				<p id="shippable_default" class="cloak shipping_option_FLAT shipping_option_FLATIND">
					<br/><br/>
					<label for="deal_shipping"><strong><?php self::_e( 'Shipping Rate' ); ?>:</strong></label>
					&nbsp;
					<?php gb_currency_symbol();  ?><input id="deal_shipping" type="text" size="5" value="<?php echo $shipping; ?>" name="deal_shipping" />&nbsp;<span class="cloak shipping_option_FLATIND"><?php gb_e( 'per item' ) ?></span>
				</p>
				<p id="dyn_shipping_table_label" class="cloak shipping_option_QUANTITY">
					<label for="deal_dynamic_shipping"><strong><?php self::_e( 'Rate Table' ); ?>:</strong></label>
					<br/>
				</p>
				<table id="dyn_shipping_table" class="widefat cloak shipping_option_QUANTITY">
					<thead>
						<tr>
							<th class="left"><?php self::_e( 'Purchasing' ); ?></th>
							<th><?php self::_e( 'Rate' ); ?></th>
							<th><?php self::_e( 'Per Item' ); ?></th>
							<th></th>
						</tr>
					</thead>

					<tbody>
						<?php if ( !empty( $shipping_dyn ) ): ?>
							<?php
			sort( $shipping_dyn );
		foreach ( $shipping_dyn as $rate_id => $data ): ?>
								<tr class="gb_deal_dyn_shipping">
									<td class="quantity"><input class="gb-shipping-quantity" type="text" size="2" value="<?php echo $data['quantity']; ?>" name="deal_dynamic_shipping[quantity][<?php echo $rate_id ?>]" placeholder="0" /></td>
									<td class="rate"><?php echo gb_get_currency_symbol(); ?><input class="gb-shipping-rate" type="text" size="2" value="<?php echo $data['rate']; ?>" name="deal_dynamic_shipping[rate][<?php echo $rate_id ?>]" placeholder="0" /></td>
									<td class="per_item">
										<input type="checkbox" name="deal_dynamic_shipping[per_item][<?php echo $rate_id  ?>]" value="TRUE" <?php checked( 'TRUE', $data['per_item'] ) ?>/>&nbsp;<?php self::_e( 'Per Item' ) ?>
									</td>
									<td class="remove" valign="middle"><a type="button" class="button gb_delete_deal_shipping_rate" href="#" title="<?php _e( 'Remove' ); ?>"><?php self::_e( 'Remove' ); ?></a></td>
								</tr>
							<?php endforeach; ?>
						<?php endif ?>

					</tbody>
				</table>
				<h4 class="cloak shipping_option_QUANTITY"><a href="#" id="gb_add_deal_shipping_rate"><?php _e( '+ Add New Rate' ); ?></a></h4>
				<p id="shippable_mode" class="cloak shipping_option_MODE">
					<br/><br/><label for="deal_base_shipping_mode"><strong><?php self::_e( 'Shipping Mode' ); ?>:</strong></label>&nbsp;
					<select name="deal_base_shipping_mode">
						<?php
		$modes = explode( "\n", self::$mode );
		foreach ( $modes as $name ) {
			$san_name = sanitize_title( $name );
			$shipping_rate = ( !self::shipping_local_enabled() ) ? gb_get_currency_symbol().self::get_rate( $san_name ) . ' &mdash; ' : '' ;
			echo '<option value="'.$san_name.'" '.selected( $san_name, $shipping_mode, FALSE ).'>'.$shipping_rate.$name.'</option>';
		}
?>
					</select>
				</p>
			</div>
		</div>

		<?php
	}
}
