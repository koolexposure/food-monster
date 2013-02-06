<?php

/**
 * Tax Controller
 *
 * @package GBS
 * @subpackage Checkout
 */
class Group_Buying_Core_Tax extends Group_Buying_Controller {

	const TAX_OPTION = 'gb_enable_taxes';
	const TAX_OPTION_LOCAL = 'gb_enable_taxes_local';
	const TAX_RATES = 'gb_tax_rate_table';
	const TAX_MODES = 'gb_taxable_modes';
	protected static $settings_page;
	private static $enable;
	private static $enable_local_based;
	private static $rates;
	protected static $mode;

	final public static function init() {

		// Deal
		// TODO bring over some of the deal class functions

		// Cart
		add_filter( 'gb_cart_extras', array( get_class(), 'cart_tax' ) );
		add_filter( 'gb_cart_line_items', array( get_class(), 'line_items' ), 10, 2 );
		add_filter( 'gb_cart_get_total', array( get_class(), 'cart_total' ), 10, 2 );

		// Checkout
		add_filter( 'gb_valid_process_payment_page', array( get_class(), 'valid_process_payment_page' ), 10, 2 );

		// Options
		self::$settings_page = self::register_settings_page( 'gb_tax_settings', self::__( 'Group Buying Tax Settings' ), self::__( 'Tax Settings' ), 16, FALSE, 'general' );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 40, 0 );
		self::$enable = get_option( self::TAX_OPTION, 'TRUE' );
		self::$enable_local_based = get_option( self::TAX_OPTION_LOCAL, 'FALSE' );
		self::$rates = get_option( self::TAX_RATES );
		self::$mode = get_option( self::TAX_MODES, "Standard Rate\nReduced Rate\nNon Taxable" );
		add_action( 'admin_init', array( get_class(), 'queue_deal_resources' ) );

		if ( self::tax_enabled() ) {
			add_action( 'gb_meta_box_deal_price_left', array( get_class(), 'display_tax_meta' ), 10, 9 );
		}
	}

	public static function queue_deal_resources() {
		wp_enqueue_style( 'group-buying-admin-deal', GB_URL . '/resources/css/deal.admin.gbs.css' );
		wp_enqueue_style( 'jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

	}

	public static function get_rate( $mode = NULL, $local = NULL, $shipping_check = FALSE ) {
		$rate = 0;
		if ( !empty( self::$rates ) ) {
			foreach ( self::$rates as $rate_id => $data ) {
				// local based
				if ( NULL != $local && self::tax_local_enabled() ) {
					if ( $mode == $data['mode'] ) {
						// Look for the first match of region and zone.
						if ( !empty( $data['zones'] ) && !empty( $data['regions'] )  ) {
							if ( in_array( $local['zone'], $data['zones'] ) && in_array( $local['country'], $data['regions'] ) ) {
								$rate = $data['rate'];
								$include_shipping = ( 'TRUE' == $data['ship'] ) ? TRUE : FALSE;
								break; // No point of continueing.
							}
						}
						// Try to match a zone, which is higher priority than regions and make sure not to override a previously matched zone price
						elseif ( !empty( $data['zones'] ) && !$matched_zone ) {
							if ( in_array( $local['zone'], $data['zones'] ) ) { // Zone match
								$rate = $data['rate'];
								$include_shipping = ( 'TRUE' == $data['ship'] ) ? TRUE : FALSE;
								if ( count( $data['zones'] ) == 1 ) {
									break; // Based on priority, if the zone match is specific.
								}
								$matched_zone = TRUE;
							}
						}
						// Match region and make sure not to override a previously matched zone or region price
						elseif ( !empty( $data['regions'] ) && !$matched_zone && !$matched_region ) {
							if ( in_array( $local['country'], $data['regions'] ) ) {
								$rate = $data['rate'];
								$include_shipping = ( 'TRUE' == $data['ship'] ) ? TRUE : FALSE;
								$matched_region = TRUE;
							}
						}
					}
				}
				// Default
				else {
					if ( $mode == $data['mode'] ) {
						if ( $shipping_check ) {
							$include_shipping = ( 'TRUE' == $data['ship'] ) ? TRUE : FALSE;
						}
						$rate = $data['rate'];
					}
				}

			}
		}
		if ( $shipping_check ) {
			return apply_filters( 'gb_get_tax_rate', $include_shipping, $mode, $local, $shipping_check );
		}
		return apply_filters( 'gb_get_tax_rate', $rate, $mode, $local, $shipping_check );
	}

	public static function deal_tax_rate( Group_Buying_Deal $deal, $local = NULL ) {
		$tax = 0;
		$mode = $deal->get_tax_mode();
		if ( is_int( $mode ) ) {
			return $mode; // returned before the is_taxable check < 3.3.4 tax.
		}
		if ( self::is_taxable( $deal ) ) {
			$tax = self::get_rate( $mode, $local );
		}
		return apply_filters( 'gb_deal_tax_rate', $tax, $deal, $local, $mode );
	}

	public static function include_shipping( Group_Buying_Deal $deal, $local = NULL ) {
		$mode = $deal->get_tax_mode();
		if ( is_int( $mode ) ) {
			return $mode; // returned before the is_taxable check < 3.3.4 tax.
		}
		if ( self::is_taxable( $deal ) ) {
			return self::get_rate( $mode, $local, TRUE );
		}
		return;
	}

	public static function is_taxable( Group_Buying_Deal $deal ) {
		if ( $deal->get_taxable() !== 'TRUE' ) {
			return FALSE;
		}
		return TRUE;
	}

	public static function get_calc_tax( Group_Buying_Deal $deal, $qty = 1, $item_data = NULL, $local = NULL ) {
		$tax = self::deal_tax_rate( $deal, $local );
		if ( $tax >= 1 ) {
			$tax = $tax/100;
		} elseif ( $tax < 0 ) { // we want to safegaurd from people not understanding what a percentage is.
			$tax = $tax*100;
		}
		$calc_tax = ( $tax*( $deal->get_price( $qty, $item_data )*$qty ) );
		return $calc_tax;
	}

	/**
	 *
	 *
	 * @return Hook into cart to register if shipping is being charged
	 */
	public static function cart_tax( Group_Buying_Cart $cart ) {
		$tax = self::cart_tax_total( $cart );
		if ( $tax >= 0.01  ) { // leave a bit of room for floating point arithmetic
			return TRUE;
		}
		return;
	}

	public static function line_items( $line_items, Group_Buying_Cart $cart ) {
		if ( self::cart_tax( $cart ) ) {
			$asterisk = '';
			$tax_title = self::__( 'Tax' );
			if ( self::tax_local_enabled() ) {
				$account = Group_Buying_Account::get_instance();
				$address = $account->get_address();
				$asterisk = '*';
				$tax_title = ( isset( $address['zone'] ) && isset( $address['country'] ) ) ? self::__( 'Calculated Tax' ) : self::__( 'Est. Tax' );
			}
			$tax = array(
				'tax' => array(
					'label' => $tax_title,
					'data' => gb_get_formatted_money( self::cart_tax_total( $cart ) ).$asterisk,
					'weight' => 200,
				),
			);
			$line_items = array_merge( $tax, $line_items );
		}
		return $line_items;
	}

	public static function cart_total( $total, Group_Buying_Cart $cart ) {
		if ( self::cart_tax( $cart ) ) {
			$total += self::cart_tax_total( $cart );
		}
		return $total;
	}

	/**
	 * Get the tax total of all the items in the cart
	 *
	 * @return float|int
	 */
	public static function cart_tax_total( Group_Buying_Cart $cart, $local = NULL ) {
		$tax_total = 0;
		$tallied = array();

		if ( self::tax_local_enabled() && NULL === $local ) {
			$account = Group_Buying_Account::get_instance();
			$address = $account->get_address();
			if ( !empty( $address ) ) {
				$local = array(
					'zone' => $address['zone'],
					'country' => $address['country'],
					'postal_code' => $address['postal_code']
				);
			}
		}

		foreach ( $cart->get_items() as $item ) {
			$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
			if ( is_a( $deal, 'Group_Buying_Deal' ) ) {
				$tax = self::get_calc_tax( $deal, $item['quantity'], $item['data'], $local );
				$tax_total += $tax;
				// Include Tax for shipping costs
				if ( !in_array( $item['deal_id'], $tallied ) && self::include_shipping( $deal, $local ) ) {
					$ship_tax = self::calc_cart_shipping_tax( $cart, $deal, $item, $local );
					$tax_total += $ship_tax;
				}
				$tallied[] = $item['deal_id'];
			}
		}
		return $tax_total;
	}

	public static function purchase_tax_total( Group_Buying_Purchase $purchase, $payment_method = NULL, $local = NULL ) {
		$total = 0;
		$tallied = array();
		if ( self::tax_local_enabled() && empty( $local ) ) {
			$user_id = $purchase->get_original_user();
			$account = Group_Buying_Account::get_instance( $user_id );
			$address = $account->get_address();
			if ( !empty( $address ) ) {
				$local = array(
					'zone' => $address['zone'],
					'country' => $address['country'],
					'postal_code' => $address['postal_code']
				);
			}
		}

		foreach ( $purchase->get_products() as $item ) {
			if ( isset( $item['payment_method'][$payment_method] ) ) {
				$ratio = 1;
				if ( $item['payment_method'][$payment_method] != $item['price'] ) {
					$ratio = @( $item['payment_method'][$payment_method] / $item['price'] );
				}
				$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
				$tax = self::get_calc_tax( $deal, $item['quantity'], $item['data'], $local );
				$total += $tax*$ratio;
				// Include Tax for shipping costs
				if ( !in_array( $item['deal_id'], $tallied ) && self::include_shipping( $deal, $local ) ) {
					$ship_tax = self::calc_purchase_shipping_tax( $purchase, $deal, $item, $local );
					$total += $ship_tax*$ratio;
				}
				$tallied[] = $item['deal_id'];
			}
		}
		return $total;
	}

	public static function purchase_item_tax( Group_Buying_Purchase $purchase, $item = array(), $local = NULL ) {

		$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );

		if ( self::tax_local_enabled() && NULL === $local ) {
			$user_id = $purchase->get_original_user();
			$account = Group_Buying_Account::get_instance( $user_id );
			$address = $account->get_address();
			if ( !empty( $address ) ) {
				$local = array(
					'zone' => $address['zone'],
					'country' => $address['country'],
					'postal_code' => $address['postal_code']
				);
			} else {
				$local = $purchase->get_shipping_local();
			}
		}

		$qty = self::deal_quantity( $purchase->get_products(), $item['deal_id'] );

		$tax = 0;
		// Include Tax for shipping costs
		if ( self::include_shipping( $deal, $local ) ) {
			$tax += self::calc_purchase_shipping_tax( $purchase, $deal, $item, $local );
		}
		$tax += self::get_calc_tax( $deal, $qty, NULL, $local );
		return $tax;
	}



	public static function calc_cart_shipping_tax( Group_Buying_Cart $cart, Group_Buying_Deal $deal, $item = array(), $local = NULL ) {
		$tax = self::deal_tax_rate( $deal, $local );
		if ( $tax >= 1 ) {
			$tax = $tax/100;
		} elseif ( $tax < 0 ) { // we want to safegaurd from people not understanding what a percentage is.
			$tax = $tax*100;
		}
		$shipping = Group_Buying_Core_Shipping::cart_item_shipping_total( $cart, $item, $local );
		$calc_tax = ( $tax*$shipping );
		return $calc_tax;
	}

	public static function calc_purchase_shipping_tax( Group_Buying_Purchase $purchase, Group_Buying_Deal $deal, $item = array(), $local = NULL ) {
		$tax = self::deal_tax_rate( $deal, $local );
		if ( $tax >= 1 ) {
			$tax = $tax/100;
		} elseif ( $tax < 0 ) { // we want to safegaurd from people not understanding what a percentage is.
			$tax = $tax*100;
		}
		$shipping = Group_Buying_Core_Shipping::purchase_item_shipping( $purchase, $item, $local );
		$calc_tax = ( $tax*$shipping );
		return $calc_tax;
	}

	protected static function deal_quantity( $items, $single = FALSE, $return = 'total_deals' ) {
		$deal_count = array();
		$total_deals = array();
		// Need to get the real quantity of deals since attributes end up being individual items
		foreach ( $items as $item ) {

			// Count how many items share this deal_id
			if ( !empty($deal_count[$item['deal_id']]) && $deal_count[$item['deal_id']] >= 1 ) {
				$deal_count[$item['deal_id']]++;
			} else {
				$deal_count[$item['deal_id']] = 1;
			}

			$quantity = ( isset( $item['quantity'] ) ) ? $item['quantity'] : 1;
			for ( $i=0; $i < $quantity; $i++ ) { // not forgetting quantity purchasing
				$total_deals[] = $item['deal_id'];
			}
		}

		// deal_count returns the quantity of items that share the same deal
		if ( $return == 'deal_count' ) {
			if ( FALSE !== $single ) {
				$deal_count = $deal_count[$single];
			}
			return apply_filters( 'gb_tax_deal_count', $deal_count, $items, $single );
		}

		// default: returns the total quantity of all similar items based on deals
		$deals_totaled = array_count_values( $total_deals ); // clean up the array and get the real quantity
		// return total quantity for a single deal, instead of an array.
		if ( FALSE !== $single ) {
			$deals_totaled = $deals_totaled[$single];
		}
		return apply_filters( 'gb_tax_total_deals', $deals_totaled, $items, $single );
	}


	/**
	 * Process the payment form
	 *
	 * @return void
	 */
	public static function valid_process_payment_page( $valid, Group_Buying_Checkouts $checkout ) {
		// If local is enabled check the account's address and what's submitted.
		if ( self::tax_local_enabled() ) {
			$account = Group_Buying_Account::get_instance();
			$address = $account->get_address();
			// If a mismatch: mark the page as invalid
			if ( isset( $_POST['gb_billing_country'] ) && isset( $_POST['gb_billing_zone'] ) && ( $_POST['gb_billing_zone'] != $address['zone'] || $_POST['gb_billing_country'] != $address['country'] || $_POST['gb_billing_postal_code'] != $address['postal_code'] ) ) {
				$valid = FALSE;
				self::set_message( self::__( 'Tax Costs Updated.' ), self::MESSAGE_STATUS_ERROR );
				// Set the shipping regardless if there's an error or not.
				$local = array(
					'zone' => $_POST['gb_billing_zone'],
					'country' => $_POST['gb_billing_country'],
					'street' => $_POST['gb_billing_street'],
					'postal_code' => $_POST['gb_billing_postal_code'],
					'city' => $_POST['gb_billing_city'],
				);
				// Set the new address so it can be used when the cart reloads, there's some convenience there too.
				$new_address = wp_parse_args( $local, $address );
				$account->set_address( $new_address );
			}
		}
		return $valid;
	}


	public static function tax_enabled() {
		if ( self::$enable !== 'TRUE' ) {
			return FALSE;
		}
		return TRUE;
	}

	public static function tax_local_enabled() {
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
		$section = 'gb_tax';
		add_settings_section( $section, '', array( get_class(), 'display_settings_section' ), $page );
		// Settings
		register_setting( $page, self::TAX_OPTION );
		register_setting( $page, self::TAX_OPTION_LOCAL, array( get_class(), 'save_local_option' ) );
		register_setting( $page, self::TAX_MODES );
		register_setting( $page, self::TAX_RATES, array( get_class(), 'save_rates' ) );
		// Fields
		add_settings_field( self::TAX_OPTION, self::__( 'Calculate Tax' ), array( get_class(), 'display_enable' ), $page, $section );
		add_settings_field( self::TAX_OPTION_LOCAL, self::__( 'Location Based' ), array( get_class(), 'display_enable_local' ), $page, $section );
		add_settings_field( self::TAX_RATES, self::__( 'Tax Modes' ), array( get_class(), 'display_modes' ), $page, $section );
		add_settings_field( self::TAX_MODES, self::__( 'Tax Rates' ), array( get_class(), 'display_rates' ), $page, $section );
	}

	public function display_settings_section() {
		// printf(self::__('Group Buying Site tax options.'));
	}
	public static function display_enable() {
		echo '<input type="checkbox" name="'.self::TAX_OPTION.'" value="TRUE" '.checked( 'TRUE', self::$enable, FALSE ).'>&nbsp;'.self::__( 'Enable tax.' );
	}
	public static function display_enable_local() {
		echo '<input type="checkbox" name="'.self::TAX_OPTION_LOCAL.'" value="TRUE" '.checked( 'TRUE', self::$enable_local_based, FALSE ).'>&nbsp;'.self::__( 'Enable tax based on location.' );
	}

	public static function display_modes() {
		echo '<textarea name="'.self::TAX_MODES.'" rows="5" cols="20">'.self::$mode.'</textarea>';
		echo '<br/><span class="description">'.self::__( 'List 1 per line.' ).'</span>';
	}

	public static function save_local_option( $tax_local ) {
		if ( !isset( $_POST[self::TAX_OPTION_LOCAL] ) )
			return $tax_local;
		if ( !isset( $_POST[self::TAX_OPTION] ) || ( isset( $_POST[self::TAX_OPTION_LOCAL] ) && $_POST[self::TAX_OPTION] != 'TRUE' ) ) {
			$tax_local = 'FALSE';
		}
		return $tax_local;
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
				'ship' => isset($post['ship'][$key])?$post['ship'][$key]:'',
			);

		}
		return $values;
	}

	public static function display_rates() {
?>
			<script type="text/javascript">

			jQuery(document).ready( function($) {
				var $i = 0;
				$('a.gb-tax-rate-remove').on( 'click', function() {
					$(this).parents('.gb-tax-rate-row').remove();
					return false;
				});
				$('a#gb_add_tax_rate').on( 'click', function() {
					var size = jQuery('tbody .gb-tax-rate-row').size();
					var $row = $('<tr class="gb-tax-rate-row">\
					<td class="mode">\
						<select name="<?php echo self::TAX_RATES; ?>[mode]['+size+']"><?php $modes = explode( "\n", self::$mode ); foreach ( $modes as $name ) { echo '<option value="'.sanitize_title( $name ).'">'.esc_js( $name ).'</option>'; } ?></select>\
					</td>\
					<?php if ( self::tax_local_enabled() ): ?><td class="regions">\
						<select name="<?php echo self::TAX_RATES; ?>[regions]['+size+'][]" class="region_selections regions_added'+$i+'" multiple="multiple"><?php foreach ( parent::$countries as $key => $name ) { echo '<option value="'.$key.'">'.esc_js( $name ).'</option>'; } ?></select>\
						<select name="<?php echo self::TAX_RATES; ?>[zones]['+size+'][]" class="zone_selections zones_added'+$i+'" multiple="multiple"><?php foreach ( self::get_state_options() as $group => $states ) { echo '<optgroup label="'.$group.'">'; foreach ($states as $key => $name) { echo '<option value="'.$key.'">&nbsp;'.$name.'</option>'; } echo '</optgroup>'; } ?></select>\
					</td><?php endif; ?>\
					<td class="rate"><input class="gb-tax-rate" type="text" size="2" name="<?php echo self::TAX_RATES ?>[rate]['+size+']" placeholder="0" >%</td>\
					<td class="ship"><input type="checkbox" name="<?php echo self::TAX_RATES; ?>[ship]['+size+']" value="TRUE" />&nbsp;<?php self::_e( 'Include Shipping' ) ?>\
					</td>\
					<td class="remove" valign="middle"><a type="button" class="button gb-tax-rate-remove" href="#" title="<?php _e( 'Remove This Option' ); ?>"><?php self::_e( 'Remove' ); ?></a></td>\
				</tr>');
					$('div#gb-tax-rate table tbody:first').append( $row );
					
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

			<div id="gb-tax-rate">
				<table class="widefat" style="width:800px">
					<tbody>
						<?php if ( !empty( self::$rates ) ): ?>
							<?php foreach ( self::$rates as $rate_id => $data ): ?>
								<tr class="gb-tax-rate-row">
									<td class="mode">
										<select name="<?php echo self::TAX_RATES; ?>[mode][<?php echo $rate_id  ?>]">
											<?php
												$modes = explode( "\n", self::$mode );
												foreach ( $modes as $name ) {
													$san_name = sanitize_title( $name );
													echo '<option value="'.$san_name.'" '.selected( $san_name, $data['mode'], FALSE ).'>'.$name.'</option>';
												} ?>
										</select>
									</td>
									<?php if ( self::tax_local_enabled() ): ?>
										<td class="regions"  style="width: 300px;">
											<select name="<?php echo self::TAX_RATES; ?>[regions][<?php echo $rate_id  ?>][]" class="region_selections" multiple="multiple">
												<?php
													foreach ( self::get_country_options() as $key => $name ) {
														$selected = ( in_array( $key, $data['regions'] ) ) ? 'selected="selected"' : null ;
														echo '<option value="'.$key.'" '.$selected.'>'.$name.'</option>';
													} ?>
											</select>
											<select name="<?php echo self::TAX_RATES; ?>[zones][<?php echo $rate_id ?>][]" class="zone_selections" multiple="multiple">
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
									<td class="rate"><input class="gb-tax-rate" type="text" size="2" value="<?php echo $data['rate']; ?>" name="<?php echo self::TAX_RATES ?>[rate][<?php echo $rate_id ?>]" placeholder="0" />%</td>
									<td class="ship">
										<input type="checkbox" name="<?php echo self::TAX_RATES; ?>[ship][<?php echo $rate_id  ?>]" value="TRUE" <?php checked( 'TRUE', $data['ship'] ) ?>/>&nbsp;<?php self::_e( 'Include Shipping' ) ?>
									</td>
									<td class="remove" valign="middle"><a type="button" class="button gb-tax-rate-remove" href="#" title="<?php _e( 'Remove This Option' ); ?>"><?php self::_e( 'Remove' ); ?></a></td>
								</tr>
							<?php endforeach; ?>
						<?php endif ?>

					</tbody>
				</table>
			</div>
			<h4><a class="button" href="#" id="gb_add_tax_rate"><?php _e( 'Add New Rate' ); ?></a></h4>
		<?php
		echo '<br/><span class="description">'.self::__( 'Rate priority is set by list order above (top to bottom) and matching criteria in this order <big>Country+State</big> > State <small>> Country</small>.' ).'</span>';
	}

	public static function display_tax_meta( $price, $dynamic_price, $shipping, $shippable, $shipping_dyn, $shipping_mode, $tax, $taxable, $taxrate ) {
?>
		<p>
			<label for="deal_base_taxable"><input id="deal_base_taxable" type="checkbox" value="TRUE" name="deal_base_taxable" <?php checked( $taxable, 'TRUE' ) ?>/>&nbsp;<?php self::_e( 'Taxable' ); ?></label>
		<br/>
		<?php // TODO remove blank $modes ?>

			<label for="deal_base_tax"><strong><?php self::_e( 'Tax Rate' ); ?>:</strong></label>
			&nbsp;
			<select name="deal_base_tax" id="deal_base_tax">
				<?php

		$modes = explode( "\n", self::$mode );
		foreach ( $modes as $name ) {
			$san_name = sanitize_title( $name );
			$tax_rate = ( !self::tax_local_enabled() ) ? self::get_rate( $san_name ) . '% &mdash; ' : '' ;
			echo '<option value="'.$san_name.'" '.selected( $san_name, $taxrate, FALSE ).'>'.$tax_rate.$name.'</option>';
		}
?>
			</select>
		</p>
		<?php
	}
}
