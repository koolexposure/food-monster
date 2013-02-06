<?php

/**
 * GBS Purchase Model
 *
 * @package GBS
 * @subpackage Purchase
 */
class Group_Buying_Purchase extends Group_Buying_Post_Type {
	const POST_TYPE = 'gb_purchase';
	const REWRITE_SLUG = 'purchase';
	const NO_USER = -1;
	private static $instances = array();


	protected static $meta_keys = array(
		'account_id' => '_account_id', // int
		'original_owner' => '_original_owner', // int
		'deal_id' => '_deal_id', // int, multiple
		'products' => '_products', // array
		'shipping' => '_shipping', // int|float
		'shipping_local' => '_shipping_address', // string
		'subtotal' => '_subtotal', // int|float
		'tax' => '_tax', // int|float
		'total' => '_total', // int|float
		'user_id' => '_user_id', // int
		'auth_key' => '_auth_key', // string
		'gateway_data' => '_gateway_data', // multiple
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.

	/**
	 * All the items that were purchased
	 *
	 * Each item in the array should include the following keys:
	 *  - deal_id - the ID of the deal
	 *  - quantity - how many of that deal were purchased
	 *  - price - how much was paid per item
	 *  - data - any additional information we're storing about the purchased item
	 *  - payment_method - which payment_processor(s) pay(s) for all or part of this deal
	 *    - key = name of the payment method
	 *    - value = amount this payment method is handling
	 *
	 * @var array
	 */
	protected $products = array();

	public static function init() {
		$post_type_args = array(
			'show_ui' => FALSE,
			'show_in_menu' => 'group-buying', // in case we want a ui
			'rewrite' => array(
				'slug' => self::REWRITE_SLUG,
				'with_front' => FALSE,
			),
			'has_archive' => FALSE,
			'supports' => array( 'title' ),
		);
		self::register_post_type( self::POST_TYPE, 'Purchase', 'Purchases', $post_type_args );
	}

	protected function __construct( $id ) {
		parent::__construct( $id );
	}


	/**
	 * Update with fresh data from the database
	 *
	 * @return void
	 */
	protected function refresh() {
		parent::refresh();
		$this->products = (array)$this->get_post_meta( self::$meta_keys['products'] );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Group_Buying_Purchase
	 */
	public static function get_instance( $id = 0 ) {
		if ( !$id ) {
			$id = self::new_purchase();
		}
		if ( !isset( self::$instances[$id] ) || !self::$instances[$id] instanceof self ) {
			self::$instances[$id] = new self( $id );
		}
		if ( !self::$instances[$id]->post || self::$instances[$id]->post->post_type != self::POST_TYPE ) {
			return NULL;
		}
		return self::$instances[$id];
	}


	public static function new_purchase( $args = array() ) {
		$default = array(
			'post_title' => self::__( 'Order' ),
			'post_status' => 'pending',
			'post_type' => self::POST_TYPE,
		);
		$id = wp_insert_post( $default );
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		$purchase = self::get_instance( $id );
		$purchase->set_title( sprintf( self::__( 'Order #%d' ), $id ) );
		if ( isset( $args['user'] ) && is_numeric( $args['user'] ) ) {
			$purchase->set_account_id( Group_Buying_Account::get_account_id_for_user( (int)$args['user'] ) );
			$purchase->set_user( (int)$args['user'] );
			$purchase->set_original_user( (int)$args['user'] );
		}
		if ( isset( $args['cart'] ) && is_a( $args['cart'], 'Group_Buying_Cart' ) ) {
			$items = $args['cart']->get_items();
			foreach ( $items as $key => $item ) {
				$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
				if ( is_object( $deal ) ) {
					$price = $deal->get_price( NULL, $item['data'] )*$item['quantity'];
					$items[$key]['price'] = $price;
					$items[$key]['unit_price'] = $deal->get_price( NULL, $item['data'] );
					$items[$key]['payment_method'] = array();
				}
			}
			$purchase->set_products( $items );
			$purchase->set_subtotal( $args['cart']->get_subtotal() );
			$purchase->set_tax_total( $args['cart']->get_tax_total() );
			$purchase->set_shipping_total( $args['cart']->get_shipping_total() );
			$purchase->set_total( $args['cart']->get_total() );
		} elseif ( isset( $args['items'] ) && is_array( $args['items'] ) ) {
			$purchase->set_products( $args['items'] );
			$price = 0;
			foreach ( $args['items'] as $item ) {
				$price += $item['price'];
			}
			$purchase->set_shipping_total( 0 );
			$purchase->set_tax_total( 0 );
			$purchase->set_subtotal( $price );
			$purchase->set_total( $price );
		} else {
			$purchase->set_products( array() );
		}
		if ( self::DEBUG ) error_log( "new purchase: " . print_r( $purchase, true ) );
		do_action( 'gb_new_purchase', $purchase, $args );
		if ( self::DEBUG ) error_log( "new purchase after action: " . print_r( $purchase, true ) );
		return $id;
	}

	public static function delete_purchase( $id ) {
		$post_type = get_post_type( $id );
		if ( $post_type == self::POST_TYPE ) {
			do_action( 'deleting_purchase', $id );
			wp_delete_post( $id, TRUE );
			if ( isset( self::$instances[$id] ) ) {
				unset( self::$instances[$id] );
			}
			do_action( 'deleted_purchase', $id );
		}
	}

	public function complete() {
		if ( $this->is_settled() ) {
			do_action( 'completing_purchase', $this );
			$this->post->post_status = 'publish';
			$this->save_post();
			$this->clear_caches();
			do_action( 'purchase_completed', $this );
		}
	}

	public function get_products() {
		return $this->products;
	}

	public function set_products( $products ) {
		$this->products = $products;
		$this->save_post_meta( array(
				self::$meta_keys['products'] => $products,
			) );
		delete_post_meta( $this->ID, self::$meta_keys['deal_id'] );
		foreach ( $this->products as $product ) {
			add_post_meta( $this->ID, self::$meta_keys['deal_id'], $product['deal_id'] );
		}
	}

	/**
	 * Set the user ID for this purchase
	 *
	 * @param int     $user_id
	 * @return void
	 */
	public function set_user( $user_id ) {
		$this->save_post_meta( array(
				self::$meta_keys['user_id'] => $user_id,
			) );
	}

	/**
	 *
	 *
	 * @return int The ID of the user who made this purchase
	 */
	public function get_user() {
		return (int)$this->get_post_meta( self::$meta_keys['user_id'] );
	}

	public function set_account_id( $account_id ) {
		$this->save_post_meta( array( 
				self::$meta_keys['account_id'] => $account_id 
			) );
	}

	public function get_account_id( $reset = FALSE ) {
		$account_id = $this->get_post_meta( self::$meta_keys['account_id'] );
		if ( $reset || empty( $account_id ) || !Group_Buying_Account::is_account( $account_id ) ) {
			$user_id = $this->get_user();
			$account_id = Group_Buying_Account::get_account_id_for_user( $user_id );
			$this->set_account_id( $account_id ); // save the found id to meta
		}
		return $account_id;
	}

	public function get_subtotal( $payment_method = NULL ) {
		if ( $payment_method === NULL ) {
			return $this->get_post_meta( self::$meta_keys['subtotal'] );
		} else {
			$total = 0;
			foreach ( $this->products as $item ) {
				if ( isset( $item['payment_method'][$payment_method] ) ) {
					$total += $item['payment_method'][$payment_method];
				}
			}
			return apply_filters( 'gbs_get_subtotal_purchase', $total, $this, $payment_method );
		}
	}

	public function set_subtotal( $subtotal ) {
		$this->save_post_meta( array(
				self::$meta_keys['subtotal'] => $subtotal
			) );
	}

	public function get_item_tax( $item ) {
		return Group_Buying_Core_Tax::purchase_item_tax( $this, $item );
	}

	public function get_tax_total( $payment_method = NULL, $local = NULL ) {
		if ( $payment_method === NULL ) {
			return $this->get_post_meta( self::$meta_keys['tax'] );
		} else {
			return Group_Buying_Core_Tax::purchase_tax_total( $this, $payment_method, $local );
		}
	}

	public function set_tax_total( $tax ) {
		$this->save_post_meta( array(
				self::$meta_keys['tax'] => $tax
			) );
	}

	/**
	 * Shipping total for the purchase, calculated if items have multiple payment methods.
	 *
	 * @deprecated 3.3.4
	 * @deprecated Use Group_Buying_Core_Shipping::purchase_shipping_total()
	 * @see Group_Buying_Core_Shipping::purchase_shipping_total()
	 */
	public function get_item_shipping( $item, $local = NULL, $distribute = TRUE ) {
		return Group_Buying_Core_Shipping::purchase_item_shipping( $this, $item, $local, $distribute );
	}

	/**
	 * Set the shipping local
	 */
	public function get_shipping_local() {
		return $this->get_post_meta( self::$meta_keys['shipping_local'] );
	}

	public function set_shipping_local( $local ) {
		$this->save_post_meta( array(
				self::$meta_keys['shipping_local'] => $local
			) );
	}

	/**
	 * Shipping total for the purchase, calculated if items have multiple payment methods.
	 *
	 * @deprecated 3.3.4
	 * @deprecated Use Group_Buying_Core_Shipping::purchase_shipping_total()
	 * @see Group_Buying_Core_Shipping::purchase_shipping_total()
	 */
	public function get_shipping_total( $payment_method = NULL, $local = NULL ) {
		if ( $payment_method === NULL ) {
			return $this->get_post_meta( self::$meta_keys['shipping'] );
		} else {
			return Group_Buying_Core_Shipping::purchase_shipping_total( $this, $payment_method, $local );
		}
	}

	public function set_shipping_total( $shipping ) {
		$this->save_post_meta( array(
				self::$meta_keys['shipping'] => $shipping
			) );
	}

	public function set_total( $total ) {
		// TODO Explore the removal  of shipping and tax from the set above
		$this->save_post_meta( array(
				self::$meta_keys['total'] => $total
			) );
	}

	public function get_total( $payment_method = NULL, $local = NULL, $local_billing = NULL ) {
		if ( $payment_method === NULL ) {
			return $this->get_post_meta( self::$meta_keys['total'] );
		} else {
			$total = $this->get_subtotal( $payment_method );
			$total += $this->get_shipping_total( $payment_method, $local );
			$total += $this->get_tax_total( $payment_method, $local_billing );
			return apply_filters( 'gb_purchase_get_total', $total, $this, $payment_method, $local, $local_billing );
		}
	}

	/**
	 *
	 *
	 * @return array The IDs of the payments that paid for this purchase
	 */
	public function get_payments() {
		return Group_Buying_Payment::get_payments_for_purchase( $this->ID );
	}

	/**
	 *
	 *
	 * @return array The IDs of all vouchers associated with this purchase
	 */
	public function get_vouchers() {
		return Group_Buying_Voucher::get_vouchers_for_purchase( $this->ID );
	}

	/**
	 * Get a list of Purchase IDs, filtered by $args
	 *
	 * @static
	 * @param array   $args
	 *  - deal - limit to purchases that include this deal ID
	 *  - user - limit to purchases by this user ID
	 *  - account - limit to purchases by this account ID (ignored if user ID is also given)
	 * @param array   $meta Default and allow for direct query
	 * @return array The IDs of all purchases meeting the criteria
	 */
	public static function get_purchases( $args = array(), $meta = array() ) {
		if ( isset( $args['deal'] ) ) {
			if ( is_array( $args['deal'] ) ) {
				$purchase_ids = array();
				foreach ( $args['deal'] as $deal_id ) {
					$meta[self::$meta_keys['deal_id']] = $deal_id;
					$purchase_ids = array_merge( $purchase_ids, self::find_by_meta( self::POST_TYPE, $meta ) );
				}
				return $purchase_ids; // End early since we're returning an array.
			} else {
				$meta[self::$meta_keys['deal_id']] = $args['deal'];
			}
		}
		if ( isset( $args['account'] ) || isset( $args['user'] ) ) {
			$meta[self::$meta_keys['user_id']] = isset( $args['user'] ) ? (int)$args['user'] : Group_Buying_Account::get_user_id_for_account( (int)$args['account'] );
		}
		$purchase_ids = self::find_by_meta( self::POST_TYPE, $meta );
		return $purchase_ids;
	}

	/**
	 * Get the total quantity of a product in this purchase
	 *
	 * @param int     $product_id The ID of the product to look for
	 * @param mixed   $data       Data to match against
	 * @return int Total quantity of the given product in this purchase
	 */
	public function get_product_quantity( $product_id, $data = NULL ) {
		$quantity = 0;
		if ( !is_array( $this->products ) ) {
			return 0;
		}
		foreach ( $this->products as $product ) {
			if ( $product['deal_id'] == $product_id && ( is_null( $data ) || $product['data'] == $data ) ) {
				if ( is_null( $data ) ) {
					$quantity += $product['quantity'];
				} elseif ( $product['data'] && is_array( $product['data'] ) && is_array( $data ) ) {
					// make sure that each $data point of interest matches the $product's data
					// doesn't have to completely match everything in $product['data'], so long as
					// $data is a subset of $product['data']
					$match = TRUE;
					foreach ( $data as $key => $value ) {
						if ( !( isset( $product['data'][$key] ) && $product['data'][$key] == $value ) ) {
							$match = FALSE;
						}
					}
					if ( $match ) {
						$quantity += $product['quantity'];
					}
				}

			}
		}
		return $quantity;
	}

	/**
	 * Get the total quantity of a product in this purchase
	 *
	 * @param int     $product_id The ID of the product to look for
	 * @param mixed   $data       Data to match against
	 * @return int Total quantity of the given product in this purchase
	 */
	public function get_product_price( $product_id, $data = NULL ) {
		$price = 0;
		if ( !is_array( $this->products ) ) {
			return 0;
		}
		foreach ( $this->products as $product ) {
			if ( $product['deal_id'] == $product_id && ( is_null( $data ) || $product['data'] == $data ) ) {
				$price += $product['price'];
			}
		}
		return $price;
	}

	/**
	 * Get the total quantity of a product in this purchase
	 *
	 * @param int     $product_id The ID of the product to look for
	 * @param mixed   $data       Data to match against
	 * @return int Total quantity of the given product in this purchase
	 */
	public function get_product_unit_price( $product_id, $data = NULL ) {
		$price = 0;
		if ( !is_array( $this->products ) ) {
			return 0;
		}
		foreach ( $this->products as $product ) {
			if ( $product['deal_id'] == $product_id && ( is_null( $data ) || $product['data'] == $data ) ) {
				$price = $product['unit_price'];
			}
		}
		return $price;
	} 

	public function set_original_user( $user_id ) {
		$this->save_post_meta( array(
				self::$meta_keys['original_owner'] => $user_id,
			) );
	}

	public function get_original_user() {
		return (int)$this->get_post_meta( self::$meta_keys['original_owner'] );
	}

	public function set_auth_key( $auth_key ) {
		$this->save_post_meta( array(
				self::$meta_keys['auth_key'] => $auth_key,
			) );
	}

	public function get_auth_key() {
		return $this->get_post_meta( self::$meta_keys['auth_key'] );
	}

	public function set_gateway_data( $gateway_data ) {
		$this->save_post_meta( array(
				self::$meta_keys['gateway_data'] => $gateway_data,
			) );
	}

	public function get_gateway_data() {
		return $this->get_post_meta( self::$meta_keys['gateway_data'] );
	}

	public function is_complete() {
		return $this->post->post_status == 'publish';
	}

	public function is_pending() {
		return $this->post->post_status == 'pending';
	}

	public function is_settled() {
		return $this->post->post_status != 'unsettled';
	}

	public function set_unsettled_status() {
		$this->post->post_status = 'unsettled';
		$this->save_post();
	}

	public function set_pending() {
		$this->post->post_status = 'pending';
		$this->save_post();
	}

	public static function get_purchase_by_key( $key = null ) {
		if ( null == $key ) return; // nothing more to to
		$purchase_ids = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['auth_key'] => $key ) );
		return $purchase_ids[0];
	}

	/**
	 * When a purchase is completed, make sure the front-end cache
	 * for each deal included in the purchase is flushed
	 */
	private function clear_caches() {
		$products = $this->get_products();
		foreach ( $products as $product ) {
			Group_Buying_Controller::clear_post_cache($product['deal_id']);
		}
	}
}
