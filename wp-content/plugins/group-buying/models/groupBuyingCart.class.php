<?php

/**
 * GBS Cart Model
 *
 * @package GBS
 * @subpackage Cart
 */
class Group_Buying_Cart extends Group_Buying_Post_Type {
	const POST_TYPE = 'gb_cart';
	const REWRITE_SLUG = 'cart';

	const CACHE_PREFIX = 'gbs_cart';
	const CACHE_GROUP = 'gbs_cart';

	protected $user_id;
	private static $instances = array(); // for multiton pattern

	/**
	 * All the items in the cart
	 *
	 * Each item in the array should include the following keys:
	 *  - deal_id - the ID of the deal
	 *  - quantity - how many of that deal are in the cart
	 *  - data - any additional information we're storing about the cart item
	 *
	 * @var array
	 */
	protected $products = array();

	private static $meta_keys = array(
		'products' => '_products', // array
		'user_id' => '_user_id', // int
		'user_ip' => '_user_ip', // string
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.

	public static function init() {
		$post_type_args = array(
			'public' => FALSE,
			'show_ui' => FALSE,
			'show_in_menu' => FALSE,
			'rewrite' => FALSE,
			'has_archive' => FALSE,
		);
		self::register_post_type( self::POST_TYPE, 'Cart', 'Carts', $post_type_args );
	}

	/**
	 *
	 *
	 * @static
	 * @return bool Whether the current query is for the cart post type
	 */
	public static function is_cart_query() {
		$post_type = get_query_var( 'post_type' );
		if ( $post_type == self::POST_TYPE ) {
			return TRUE;
		}
		return FALSE;
	}

	protected function __construct( $user_id ) {
		$this->user_id = $user_id;
		$cart_id = self::get_cart_id_for_user( $user_id );
		parent::__construct( $cart_id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int|NULL $user_id
	 * @return Group_Buying_Cart The cart for the given user
	 */
	public static function get_instance( $user_id = 0 ) {
		if ( !$cart_id = self::get_cart_id_for_user( $user_id ) ) {
			return NULL;
		}
		if ( !isset( self::$instances[$cart_id] ) || !self::$instances[$cart_id] instanceof self ) {
			self::$instances[$cart_id] = new self( $user_id );
		}
		if ( self::$instances[$cart_id]->post->post_type != self::POST_TYPE ) {
			return NULL;
		}
		return self::$instances[$cart_id];
	}

	/**
	 * Update with fresh data from the database
	 *
	 * @return void
	 */
	protected function refresh() {
		parent::refresh();
		$this->load_products();
	}

	private function load_products() {
		$products = apply_filters( 'gb_cart_load_products_get', $this->get_post_meta( self::$meta_keys['products'] ), $this );
		if ( is_array( $products ) ) {
			$this->products = $products;
		} else {
			$this->products = array();
		}
	}

	public function get_products() {
		return $this->products;
	}

	/**
	 * Get the cart ID for a user's cart on the current site
	 *
	 * @static
	 * @param int     $user_id
	 * @return int|bool|WP_Error
	 */
	public static function get_cart_id_for_user( $user_id = 0 ) {
		if ( !$user_id ) {
			$user_id = get_current_user_id();
		}

		// see if we've cached the value
		if ( $user_id && $cart_id = self::get_id_cache( $user_id ) ) {
			return $cart_id;
		}

		if ( $user_id ) {
			$user_carts = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['user_id'] => $user_id ) );
			if ( !empty( $user_carts ) ) {
				$cart_id = reset( $user_carts );
				self::set_id_cache( $user_id, $cart_id );
				return $cart_id;
			}
		}

		// anonymous users' carts are stored by IP address/cookie combination
		if ( !empty( $_COOKIE['gbs_cart'] ) ) {
			$ip = get_post_meta( $_COOKIE['gbs_cart'], self::$meta_keys['user_ip'], TRUE );
			if ( $ip && $ip == $_SERVER['REMOTE_ADDR'] ) {
				$cart_owner = get_post_meta( $_COOKIE['gbs_cart'], self::$meta_keys['user_id'], TRUE );
				if ( $cart_owner == 0 ) {
					if ( $user_id ) {
						self::claim_anonymous_cart( $_COOKIE['gbs_cart'], $user_id );
						self::set_id_cache( $user_id, $_COOKIE['gbs_cart'] );
					}
					return $_COOKIE['gbs_cart'];
				}
			}
		}

		$cart_id = self::new_cart( $user_id );
		if ( $user_id == get_current_user_id() && !headers_sent() ) {
			$_COOKIE['gbs_cart'] = $cart_id;
			setcookie( 'gbs_cart', $cart_id, time()+( 60*60*24*30 ), COOKIEPATH, COOKIE_DOMAIN );
		}

		if ( $user_id ) {
			self::set_id_cache( $user_id, $cart_id );
		}
		return $cart_id;
	}

	/**
	 * When the _user_id post meta for a cart is updated,
	 * flush the cache for both the old user ID and the new
	 *
	 * @wordpress-action update_post_meta (update_{$meta_type}_meta)
	 * @wordpress-action added_post_meta (added_{$meta_type}_meta)
	 * @wordpress-action delete_post_meta (delete_{$meta_type}_meta)
	 *
	 * @static
	 * @param int|array $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $_meta_value
	 */
	public static function maybe_clear_id_cache( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( $meta_key == self::$meta_keys['user_id'] && get_post_type($object_id) == self::POST_TYPE ) {
			if ( $meta_value ) { // this might be empty on a delete_post_meta
				self::clear_id_cache($meta_value);
			}
			$old_value = get_post_meta($object_id, $meta_key);
			if ( $old_value != $meta_value ) { // this is probably the same on added_post_meta
				self::clear_id_cache($old_value);
			}
		}
	}

	/**
	 * Get the cart ID cache for the user
	 *
	 * @static
	 * @param int $user_id
	 * @return int
	 */
	private static function get_id_cache( $user_id ) {
		return (int)wp_cache_get(self::CACHE_PREFIX.'_id_'.$user_id, self::CACHE_GROUP);
	}

	/**
	 * Set the cart ID cache for the user
	 *
	 * @static
	 * @param int $user_id
	 * @param int $cart_id
	 */
	private static function set_id_cache( $user_id, $cart_id ) {
		wp_cache_set(self::CACHE_PREFIX.'_id_'.$user_id, (int)$cart_id, self::CACHE_GROUP);
	}

	/**
	 * Delete the cart ID cache for the user
	 *
	 * @static
	 * @param int $user_id
	 */
	private static function clear_id_cache( $user_id ) {
		wp_cache_delete(self::CACHE_PREFIX.'_id_'.$user_id, self::CACHE_GROUP);
	}

	public static function get_anonymous_cart_id(  ) {
		if ( empty( $_COOKIE['gbs_cart'] ) ) {
			return 0;
		}
		$ip = get_post_meta( $_COOKIE['gbs_cart'], self::$meta_keys['user_ip'], TRUE );
		if ( $ip && $ip == $_SERVER['REMOTE_ADDR'] ) {
			$cart_owner = get_post_meta( $_COOKIE['gbs_cart'], self::$meta_keys['user_id'], TRUE );

			if ( $cart_owner == 0 ) {
				return (int)$_COOKIE['gbs_cart'];
			}
		}
		return 0;
	}

	public static function claim_anonymous_cart( $cart_id, $user_id = 0, $merge = FALSE ) {
		if ( empty( $cart_id ) ) {
			return;
		}
		$user_id = $user_id?$user_id:get_current_user_id();
		if ( empty( $user_id ) ) {
			return;
		}
		$post = get_post( $cart_id );
		if ( empty( $post ) ) {
			return;
		}

		if ( $merge ) {
			$old_cart = self::get_instance( $user_id );
			$items = $old_cart->get_items();
		}

		self::delete_cart( $user_id ); // get rid of the old cart

		update_post_meta( $cart_id, self::$meta_keys['user_id'], $user_id );
		delete_post_meta( $cart_id, self::$meta_keys['user_ip'] );
		$user = get_userdata( $user_id );
		$post->post_title = $user->user_login;
		$post->post_name = $user->user_login;
		wp_update_post( $post );

		if ( isset( self::$instances[$cart_id] ) ) {
			self::$instances[$cart_id]->user_id = $user_id;
		}

		if ( $merge && $items ) {
			if ( empty( self::$instances[$cart_id] ) ) {
				self::$instances[$cart_id] = new self( $user_id );
				$cart = self::$instances[$cart_id];
			} else {
				$cart = self::$instances[$cart_id];
			}
			foreach ( $items as $item ) {
				$cart->add_item( $item['deal_id'], $item['quantity'], $item['data'] );
			}
		}
	}

	/**
	 * Create a new cart
	 *
	 * @static
	 * @param int     $user_id
	 * @return int|WP_Error
	 */
	private static function new_cart( $user_id ) {
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			$post = array(
				'post_title' => $user->user_login,
				'post_name' => $user->user_login,
				'post_status' => 'publish',
				'post_type' => self::POST_TYPE,
			);
		} else {
			$post = array(
				'post_title' => sprintf( self::__( 'Anonymous %s' ), $_SERVER['REMOTE_ADDR'] ),
				'post_name' => sprintf( self::__( 'anonymous-%s' ), $_SERVER['REMOTE_ADDR'] ),
				'post_status' => 'publish',
				'post_type' => self::POST_TYPE,
			);
		}
		$id = wp_insert_post( $post );
		if ( !is_wp_error( $id ) ) {
			update_post_meta( $id, self::$meta_keys['user_id'], $user_id );
			update_post_meta( $id, self::$meta_keys['user_ip'], $_SERVER['REMOTE_ADDR'] );
		}
		return $id;
	}

	/**
	 * Delete any carts that might be associated with a user ID
	 *
	 * @static
	 * @param int     $user_id
	 */
	private static function delete_cart( $user_id ) {
		$args = array(
			'post_type' => self::POST_TYPE,
			'meta_query' => array(
				array(
					'key' => self::$meta_keys['user_id'],
					'value' => $user_id,
				),
			),
			'posts_per_page' => -1,
		);
		$cart_posts = get_posts( $args );
		foreach ( $cart_posts as $post ) {
			wp_delete_post( $post->ID, TRUE );
		}
	}

	/**
	 * Adds an item to the cart
	 *
	 * @param int     $product_id
	 * @param int     $quantity
	 * @param array|NULL $data
	 * @return bool Whether the item was successfully added
	 */
	public function add_item( $product_id, $quantity = 1, $data = NULL ) {
		do_action( 'gb_cart_add_item', $this, $product_id, $quantity, $data );
		$new_quantity = $this->quantity_allowed( $product_id, $quantity, $data );
		if ( $new_quantity ) {
			$this->set_quantity( $product_id, $new_quantity, $data );
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Set the item to the quantity given
	 *
	 * @param int     $product_id
	 * @param int     $quantity
	 * @param array|null $data
	 * @return void
	 */
	public function set_quantity( $product_id, $quantity, $data = NULL ) {
		$found = FALSE;
		do_action( 'gb_cart_set_quantity', $this, $product_id, $quantity, $data );
		foreach ( $this->products as $index => $product ) {
			if ( $product['deal_id'] == $product_id && $data == $product['data'] ) {
				$found = TRUE;
				$allowed = $this->quantity_allowed( $product_id, $quantity, $data );
				$new_quantity = ( $quantity>$allowed ) ? $allowed : $quantity;
				$this->products[$index]['quantity'] = $new_quantity;
				break;
			}
		}
		if ( !$found ) {
			$this->products[] = array(
				'deal_id' => $product_id,
				'quantity' => $quantity,
				'data' => $data,
			);
		}
		do_action( 'gb_cart_set_quantity_save', $this, $product_id, $quantity, $data );
		$this->save();
	}

	/**
	 * Allowed quantity.
	 *
	 * @param int     $product_id
	 * @param int     $quantity
	 * @param array|null $data
	 * @return int
	 */
	public function quantity_allowed( $product_id, $quantity = 1, $data = NULL ) {
		$account = Group_Buying_Account::get_instance( $this->user_id );
		$allowed = $account->can_purchase( $product_id, $data ); // how many the user is allowed to have
		$total_quantity = $this->get_quantity( $product_id, $data ); // how many are currently in the cart
		if ( $allowed != Group_Buying_Account::NO_MAXIMUM && $allowed < $total_quantity + $quantity ) { // we have too many
			$subtract = $total_quantity+$quantity - $allowed;
			$new_quantity = $quantity + $this->get_quantity( $product_id, $data ) - $subtract; // take out as many as necessary to make this legal
		} else {
			$new_quantity = $quantity + $this->get_quantity( $product_id, $data ); // add in the new quantity
		}
		return apply_filters( 'cart_quantity_allowed', $new_quantity, $product_id, $data );
	}

	/**
	 * Get the current quantity of the given item in the cart
	 *
	 * @param int     $product_id
	 * @param array|NULL $data
	 * @return int
	 */
	public function get_quantity( $product_id, $data, $ignore_data = FALSE ) {
		$qty = 0;
		foreach ( $this->products as $product ) {
			if ( $product['deal_id'] == $product_id && ( $ignore_data || $data == $product['data'] ) ) {
				$qty += $product['quantity'];
			}
		}
		return $qty;
	}

	/**
	 * Removes all instances of a given item from the cart. If $data is not NULL,
	 * only removes items with matching data.
	 *
	 * @param int     $product_id
	 * @param array|NULL $data
	 * @return void
	 */
	public function remove_item( $product_id, $data = NULL ) {
		foreach ( $this->products as $index => $product ) {
			if ( $product['deal_id'] == $product_id && ( is_null( $data ) || $data == $product['data'] ) ) {
				unset( $this->products[$index] );
			}
		}
		$this->save();
	}

	/**
	 * Removes all items from the given cart
	 *
	 * @return void
	 */
	public function empty_cart() {
		$this->products = array();
		$this->save();
	}

	/**
	 *
	 *
	 * @return array The items in the cart
	 */
	public function get_items() {
		return apply_filters( 'gb_cart_get_items', $this->products, $this );
	}

	/**
	 *
	 *
	 * @return array The items in the cart
	 */
	public function item_count() {
		$count = ( $this->is_empty() ) ? 0 : count( $this->products ) ;
		return apply_filters( 'gb_cart_item_count', $count, $this );
	}

	/**
	 * Saves the cart to the database
	 *
	 * @return void
	 */
	private function save() {
		$this->save_post_meta( array(
				self::$meta_keys['products'] => $this->products,
			) );
	}

	/**
	 *
	 *
	 * @return bool Whether the cart is empty of items
	 */
	public function is_empty() {
		if ( count( $this->products ) > 0 ) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 *
	 *
	 * @return bool Whether the cart has items in it
	 */
	public function has_products() {
		return !$this->is_empty();
	}

	/**
	 *
	 *
	 * @return if cart has any extras hooking into the total
	 */
	public function has_extras() {
		$bool = apply_filters( 'gb_cart_extras', $this );
		return $bool;
	}

	/**
	 *
	 *
	 * @since 3.4
	 * @deprecated 3.4
	 * @see has_extras()
	 */
	public function is_shippable() {
		return $this->has_extras();
	}

	/**
	 * Get the subtotal of all the items in the cart
	 *
	 * @return float|int
	 */
	public function get_subtotal() {
		$subtotal = 0.0;
		foreach ( $this->products as $item ) {
			$deal = Group_Buying_Deal::get_instance( $item['deal_id'] );
			if ( is_object( $deal ) ) {
				$price = $deal->get_price( NULL, $item['data'] )*$item['quantity'];
				$subtotal += $price;
			}
		}
		return $subtotal;
	}

	/**
	 * Get the subtotal of all the items in the cart
	 *
	 * @return float|int
	 */
	public function get_shipping_total() {
		return Group_Buying_Core_Shipping::cart_shipping_total( $this );
	}

	/**
	 * Get the subtotal of all the items in the cart
	 *
	 * @return float|int
	 */
	public function get_tax_total() {
		return Group_Buying_Core_Tax::cart_tax_total( $this );
	}

	/**
	 * Get the total of all the items in the cart, plus any line items
	 *
	 * @return float|int
	 */
	public function get_total() {
		$total = $this->get_subtotal();
		return apply_filters( 'gb_cart_get_total', $total, $this );
	}

}
