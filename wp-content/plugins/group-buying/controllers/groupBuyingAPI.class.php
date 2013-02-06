<?php

class Group_Buying_API extends Group_Buying_Controller {
	const API_BASE_PATH = 'gbs/api.json';
	const TIMEOUT = 120;
	const MAX_ITEMS_IN_FEED = 100;
	protected static $private_key = '';

	public static function init() {
		add_action( 'gb_router_generate_routes', array( get_class(), 'register_path_callbacks' ), 10, 1 );
	}

	/**
	 * Setup all the API callbacks
	 *
	 * @param GB_Router $router
	 */
	public static function register_path_callbacks( $router ) {
		$routes = array(
			// Authentication
			'gbs-api-token' => array(
				'path' => self::API_BASE_PATH.'/token$',
				'page_callback' => array( __CLASS__, 'api_get_token' ),
				'template' => FALSE,
			),

			// Registration
			'gbs-api-register' => array(
				'path' => self::API_BASE_PATH.'/register$',
				'page_callback' => array( __CLASS__, 'create_user' ),
				'template' => FALSE,
			),

			// Deals
			'gbs-api-deals' => array(
				'path' => self::API_BASE_PATH.'/deals$',
				'page_callback' => array( __CLASS__, 'get_deals' ),
				'title' => self::__( 'Deals' ),
				'template' => FALSE,
			),
			'gbs-api-deal' => array(
				'path' => self::API_BASE_PATH.'/deal/(\d+)\/?$',
				'query_vars' => array(
					'deal_id' => 1,
				),
				'page_arguments' => array('deal_id'),
				'page_callback' => array( __CLASS__, 'display_deal' ),
				'template' => FALSE,
				'title' => self::__( 'Deal' ),
			),

			// Merchants
			'gbs-api-merchants' => array(
				'path' => self::API_BASE_PATH.'/merchants$',
				'page_callback' => array( __CLASS__, 'get_merchants' ),
				'title' => self::__( 'Merchants' ),
				'template' => FALSE,
			),
			'gbs-api-merchant' => array(
				'path' => self::API_BASE_PATH.'/merchant/(\d+)\/?$',
				'query_vars' => array(
					'merchant_id' => 1,
				),
				'page_arguments' => array('merchant_id'),
				'page_callback' => array( __CLASS__, 'display_merchant' ),
				'template' => FALSE,
				'title' => self::__( 'Merchant' ),
			),

			// taxonomies
			'gbs-api-taxonomies' => array(
				'path' => self::API_BASE_PATH.'/taxonomies$',
				'page_callback' => array( __CLASS__, 'display_taxa' ),
				'title' => self::__( 'Taxonomies' ),
				'template' => FALSE,
			),

			// Vouchers
			'gbs-api-vouchers' => array(
				'path' => self::API_BASE_PATH.'/vouchers$',
				'page_callback' => array( __CLASS__, 'get_vouchers' ),
				'title' => self::__( 'Vouchers' ),
				'template' => FALSE,
			),
			'gbs-api-voucher' => array(
				'path' => self::API_BASE_PATH.'/voucher/(\d+)\/?$',
				'query_vars' => array(
					'voucher_id' => 1,
				),
				'page_arguments' => array('voucher_id'),
				'page_callback' => array( __CLASS__, 'display_voucher' ),
				'template' => FALSE,
				'title' => self::__( 'Voucher' ),
			),

			// Carts
			'gbs-api-cart' => array(
				'path' => self::API_BASE_PATH.'/cart$',
				'page_callback' => array( __CLASS__, 'user_cart' ),
				'title' => self::__( 'Cart' ),
				'template' => FALSE,
			),

			// Payment and Purchase
			'gbs-api-payment' => array(
				'path' => self::API_BASE_PATH.'/payment$',
				'page_callback' => array( __CLASS__, 'payment_endpoint' ),
				'title' => self::__( 'Payment' ),
				'template' => FALSE,
			),
			'gbs-api-purchase' => array(
				'path' => self::API_BASE_PATH.'/purchase/(\d+)\/?$',
				'query_vars' => array(
					'purchase_id' => 1,
				),
				'page_arguments' => array('purchase_id'),
				'page_callback' => array( __CLASS__, 'display_purchase' ),
				'template' => FALSE,
				'title' => self::__( 'Purchase' ),
			),



			// TESTS
			'gbs-api-test' => array(
				'path' => self::API_BASE_PATH.'/test$',
				'title' => 'GBS API Test',
				'page_callback' => array( __CLASS__, 'api_do_test' ),
				'template' => FALSE,
			),
			'gbs-api-test2' => array(
				'path' => self::API_BASE_PATH.'/test2$',
				'title' => 'GBS API Test',
				'page_callback' => array( __CLASS__, 'api_authenticate_test' ),
				'template' => FALSE,
			)

		);

		foreach ( $routes as $id => $route ) {
			$router->add_route( $id, $route );
		}

	}

	//////////////////
	// Registration //
	//////////////////

	public function create_user() {

		if ( !empty( $_REQUEST['user'] ) && !empty( $_REQUEST['pwd'] ) && !empty( $_REQUEST['email'] ) ) {

			if ( ! is_email( $_REQUEST['email'] ) ) {
				$errors = new WP_Error();
				$errors->add( 'invalid_email', __( 'The email address isn’t valid.' ) );
				echo json_encode( array( 'error' => 1, 'error_message' => $errors ) );
				exit();
			}

			$user_id = wp_create_user( $_REQUEST['user'], $_REQUEST['pwd'], $_REQUEST['email'] );

			if ( !$user_id || is_wp_error( $user_id ) ) {
				echo json_encode( array( 'error' => 1, 'error_message' => $user_id ) );
				exit();
			}

			// Set contact info for the new account
			$account = Group_Buying_Account::get_instance( $user_id );

			if ( is_a( $account, 'Group_Buying_Account' ) ) {

				$first_name = isset( $_REQUEST['first_name'] ) ? $_REQUEST['first_name'] : '';
				$account->set_name( 'first', $first_name );
				$last_name = isset( $_REQUEST['last_name'] ) ? $_REQUEST['last_name'] : '';
				$account->set_name( 'last', $last_name );
				if ( isset( $_REQUEST['address'] ) ) {
					$address = array(
						'street' => isset( $_REQUEST['address']['street'] ) ? $_REQUEST['address']['street'] : '',
						'city' => isset( $_REQUEST['address']['city'] ) ? $_REQUEST['address']['city'] : '',
						'zone' => isset( $_REQUEST['address']['zone'] ) ? $_REQUEST['address']['zone'] : '',
						'postal_code' => isset( $_REQUEST['address']['postal_code'] ) ? $_REQUEST['address']['postal_code'] : '',
						'country' => isset( $_REQUEST['address']['country'] ) ? $_REQUEST['address']['country'] : ''
					);
					$account->set_address( $address );
				}
			}

			wp_new_user_notification( $user_id );
			do_action( 'gb_account_created', $user_id, $_REQUEST, $account );

			$token = self::get_user_token( $user_id );
			if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
			echo json_encode( $token );
			exit();
		}
	}

	///////////
	// Feeds //
	///////////

	/**
	 * Display the feed of recently updated deals
	 *
	 * @return void
	 */
	public function get_deals() {
		$args = array(
			'post_type' => Group_Buying_Deal::POST_TYPE,
			'post_status' => 'publish', // need to know posts that were deleted
			'suppress_filters' => TRUE, // so we can filter the post modified date later
		);
		if ( isset( $_REQUEST['max'] ) && (int)$_REQUEST['max'] < self::MAX_ITEMS_IN_FEED ) {
			$args['posts_per_page'] = (int)$_REQUEST['max'];
		} else {
			$args['posts_per_page'] = self::MAX_ITEMS_IN_FEED;
		}
		if ( isset( $_REQUEST['location'] ) && $_REQUEST['location'] ) {
			$locations = explode( ',', $_REQUEST['location'] );
			foreach ( $locations as $location_id ) {
				$children = get_term_children( $location_id, Group_Buying_Deal::LOCATION_TAXONOMY );
				$locations = array_merge( $locations, $children );
			}
			$args['tax_query']['relation'] = 'AND';
			$args['tax_query'][] = array(
				'taxonomy' => Group_Buying_Deal::LOCATION_TAXONOMY,
				'field' => 'id',
				'terms' => $locations,
				'operator' => 'IN',
			);
		}
		if ( isset( $_REQUEST['category'] ) && $_REQUEST['category'] ) {
			$categories = explode( ',', $_REQUEST['category'] );
			foreach ( $categories as $category_id ) {
				$children = get_term_children( $category_id, Group_Buying_Deal::CAT_TAXONOMY );
				$categories = array_merge( $categories, $children );
			}
			$args['tax_query']['relation'] = 'AND'; // in case it wasn't set earlier
			$args['tax_query'][] = array(
				'taxonomy' => Group_Buying_Deal::CAT_TAXONOMY,
				'field' => 'id',
				'terms' => $categories,
				'operator' => 'IN',
			);
		}
		if ( isset( $_REQUEST['expired'] ) ) {
			// expired shows expired deals,
			// anything other argument will show non expired.
			$compare = ( $_REQUEST['expired'] == 'expired' ) ? 'BETWEEN' : 'NOT BETWEEN' ;
			$args['meta_query'][] = array(
				'key' => '_expiration_date',
				'value' => array( 0, current_time( 'timestamp' ) ),
				'compare' => $compare
			);
		}

		// Get posts
		$posts = get_posts( $args );
		$output = array();
		foreach ( $posts as $post ) {
			$post_id = $post->ID;
			$output[ $post_id ] = self::build_deal( $post_id );
		}

		// Print and exit
		header( 'Content-type: application/json' );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $output );
		exit();
	}

	/**
	 * Display the feed of recently updated merchants
	 *
	 * @return void
	 */
	public function get_merchants() {
		$args = array(
			'post_type' => Group_Buying_Merchant::POST_TYPE,
			'post_status' => 'publish', // need to know posts that were deleted
			'suppress_filters' => TRUE, // so we can filter the post modified date later
		);
		if ( isset( $_REQUEST['max'] ) && (int)$_REQUEST['max'] < self::MAX_ITEMS_IN_FEED ) {
			$args['posts_per_page'] = (int)$_REQUEST['max'];
		} else {
			$args['posts_per_page'] = self::MAX_ITEMS_IN_FEED;
		}
		if ( isset( $_REQUEST['tags'] ) && $_REQUEST['tags'] ) {
			$tags = explode( ',', $_REQUEST['tags'] );
			foreach ( $tags as $tag_id ) {
				$children = get_term_children( $tag_id, Group_Buying_Merchant::MERCHANT_TAG_TAXONOMY );
				$tags = array_merge( $tags, $children );
			}
			$args['tax_query']['relation'] = 'AND';
			$args['tax_query'][] = array(
				'taxonomy' => Group_Buying_Merchant::MERCHANT_TAG_TAXONOMY,
				'field' => 'id',
				'terms' => $tags,
				'operator' => 'IN',
			);
		}
		if ( isset( $_REQUEST['type'] ) && $_REQUEST['type'] ) {
			$types = explode( ',', $_REQUEST['type'] );
			foreach ( $types as $type_id ) {
				$children = get_term_children( $type_id, Group_Buying_Merchant::MERCHANT_TYPE_TAXONOMY );
				$types = array_merge( $types, $children );
			}
			$args['tax_query']['relation'] = 'AND'; // in case it wasn't set earlier
			$args['tax_query'][] = array(
				'taxonomy' => Group_Buying_Merchant::MERCHANT_TYPE_TAXONOMY,
				'field' => 'id',
				'terms' => $types,
				'operator' => 'IN',
			);
		}

		// Get posts
		$posts = get_posts( $args );
		$output = array();
		foreach ( $posts as $post ) {
			$post_id = $post->ID;
			$output[ $post_id ] = self::build_merchant( $post_id );
		}

		// Print and exit
		header( 'Content-type: application/json' );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $output );
		exit();
	}

	/**
	 * Display the feed of recently updated deals
	 *
	 * @return void
	 */
	public function get_vouchers() {
		// Private data
		self::authenticate_request();

		$args = array(
			'post_type' => Group_Buying_Voucher::POST_TYPE,
			'post_status' => 'publish',
			'suppress_filters' => TRUE,
			'gb_bypass_filter' => TRUE, // so we can filter based on purchases below
		);
		if ( isset( $_REQUEST['max'] ) && (int)$_REQUEST['max'] < self::MAX_ITEMS_IN_FEED ) {
			$args['posts_per_page'] = (int)$_REQUEST['max'];
		} else {
			$args['posts_per_page'] = self::MAX_ITEMS_IN_FEED;
		}

		// get all the user's purchases
		$purchases = Group_Buying_Purchase::get_purchases( array(
				'user' => self::get_user_id(),
			) );
		// no purchases means no vouchers
		if ( empty( $purchases ) ) {
			exit();
		}
		// Add meta query
		$args['meta_query'][] = array(
			'key' => '_purchase_id',
			'value' => $purchases,
			'compare' => 'IN',
			'type' => 'NUMERIC',
		);

		if ( isset( $_REQUEST['filter'] ) ) {
			switch ( $_REQUEST['filter'] ) {

			case 'claimed':
				$args['meta_query'][] = array(
					'key' => '_claimed',
					'value' => 0,
					'compare' => '>'
				);
				break;

			case 'active':
			case 'unclaimed': // WordPress 3.5 only.
				$query->query_vars['meta_query'][] = array(
					'key' => '_claimed',
					'compare' => 'NOT EXISTS',
				);
				break;

			default:
				break;
			}
		}
		// Get posts
		$posts = get_posts( $args );
		$output = array();
		foreach ( $posts as $post ) {
			$post_id = $post->ID;
			$output[ $post_id ] = self::build_voucher( $post_id );
		}

		// Print and exit
		header( 'Content-type: application/json' );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $output );
		exit();
	}

	/**
	 * Display the JSON representation of the given deal
	 *
	 * @param int     $post_id
	 * @return void
	 */
	public function display_deal( $post_id = 0 ) {
		// make sure the deal exists
		$post = get_post( $post_id );
		if ( !$post || $post->post_type != Group_Buying_Deal::POST_TYPE ) {
			status_header( 404 );
			exit();
		}
		if ( $post->post_status != 'publish' ) {
			status_header( 410 ); // Gone
		}

		header( 'Content-type: application/json' );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		print json_encode( self::build_deal( $post->ID ) );
		exit();
	}

	/**
	 * Display the JSON representation of the given merchant
	 *
	 * @param int     $post_id
	 * @return void
	 */
	public function display_merchant( $post_id = 0 ) {
		// make sure the deal exists
		$post = get_post( $post_id );
		if ( !$post || $post->post_type != Group_Buying_Merchant::POST_TYPE ) {
			status_header( 404 );
			exit();
		}
		if ( $post->post_status != 'publish' ) {
			status_header( 410 ); // Gone
		}

		header( 'Content-type: application/json' );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		print json_encode( self::build_merchant( $post->ID ) );
		exit();
	}

	/**
	 * Display the JSON representation of the given deal
	 *
	 * @param int     $post_id
	 * @return void
	 */
	public function display_voucher( $post_id = 0 ) {
		// Private data
		self::authenticate_request();

		// make sure the deal exists
		$post = get_post( $post_id );
		if ( !$post || $post->post_type != Group_Buying_Voucher::POST_TYPE ) {
			status_header( 404 );
			exit();
		}
		if ( $post->post_status != 'publish' ) {
			status_header( 410 ); // Gone
		}

		header( 'Content-type: application/json' );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		print json_encode( self::build_voucher( $post->ID ) );
		exit();
	}

	public function display_taxa( $context = 'deals' ) {
		$context = ( isset( $_REQUEST['context'] ) ) ? $_REQUEST['context'] : $context ;
		$output = array();
		if ( $context == 'merchants' ) {
			$output['merchant_tags'] = get_terms( Group_Buying_Merchant::MERCHANT_TAG_TAXONOMY, array( 'hide_empty' => FALSE ) );
			$output['merchant_types'] = get_terms( Group_Buying_Merchant::MERCHANT_TYPE_TAXONOMY, array( 'hide_empty' => FALSE ) );
		}
		elseif ( $context == 'attributes' ) {
			foreach ( Group_Buying_Attribute::get_attribute_taxonomies() as $taxonomy ) {
				$output['attribute-'.str_replace( 'gb_attribute_tax_', '', $taxonomy->name)] = get_terms( $taxonomy->name, array( 'hide_empty' => FALSE ) );
			}
		}
		else {
			$output['deal_locations'] = get_terms( Group_Buying_Deal::LOCATION_TAXONOMY, array( 'hide_empty' => FALSE ) );
			$output['deal_categories'] = get_terms( Group_Buying_Deal::CAT_TAXONOMY, array( 'hide_empty' => FALSE ) );
		}
		header( 'Content-type: application/json' );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $output );
		exit();
	}

	///////////
	// Carts //
	///////////

	public function user_cart( $user_id = 0 ) {
		// Private data
		self::authenticate_request();

		if ( !$user_id ) {
			$user_id = self::get_user_id();
		}

		$cart = Group_Buying_Cart::get_instance( $user_id );
		
		if ( 
			( isset( $_REQUEST['add'] ) && is_array( $_REQUEST['add'] ) ) || 
			( isset( $_REQUEST['update'] ) && is_array( $_REQUEST['update'] ) )
					) {
			$items = ( isset( $_REQUEST['update'] ) ) ? $_REQUEST['update'] : $_REQUEST['add'] ;
			foreach ( $items as $key => $item ) {
				$deal_id = $item['deal_id'];
				if ( isset( $item['data'] ) && $item['data'] ) {
					$data = maybe_unserialize( $item['data'] );
				} else {
					$data = array();
				}
				if ( ( isset( $item['remove'] ) && $item['remove'] )
					|| !isset( $item['qty'] )
					|| 0 == (int)$item['qty']
				) {
					$cart->remove_item( $deal_id, $data );
				} else {
					$qty = $cart->get_quantity( $deal_id, $data );
					if ( $qty != $item['qty'] ) {
						$cart->set_quantity( $deal_id, $item['qty'], $data );
					}
				}
			}
		}
		elseif ( isset( $_REQUEST['remove'] ) && is_array( $_REQUEST['remove'] ) ) {
			foreach ( $_REQUEST['remove'] as $key => $item ) {
				$deal_id = $item['deal_id'];
				if ( isset( $item['data'] ) && $item['data'] ) {
					$data = maybe_unserialize( $item['data'] );
				} else {
					$data = array();
				}
				$cart->remove_item( $deal_id, $data );
			}

		}

		$cart_output = array();
		//$cart_output['object'] = $cart;
		$cart_output['items'] = $cart->get_products();
		$cart_output['subtotal'] = $cart->get_subtotal();
		$cart_output['total'] = $cart->get_total();
		
		// Get local for shipping and tax
		$local = array( FALSE );
		if ( $_REQUEST['local'] && is_array( $_REQUEST['local'] ) ) {
			$local = array(
				'zone' => $_REQUEST['local']['zone'],
				'country' => $_REQUEST['local']['country'],
			);
		}

		$cart_output['shipping'] = Group_Buying_Core_Shipping::cart_shipping_total( $cart, $local );
		$cart_output['tax'] = Group_Buying_Core_Tax::cart_tax_total( $cart, $local );

		header( 'Content-type: application/json' );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $cart_output );
		exit();
	}

	///////////////////////////
	// Purchase and Payments //
	///////////////////////////

	public function display_purchase( $purchase_id = 0 ) {
		// Private data
		self::authenticate_request();

		if ( !$purchase_id )
			exit();

		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		$post = get_post( $purchase_id );
		$purchcase_output = array(
				'id' => $purchase_id,
				'title' => $post->post_title,
				'post_date' => $post->post_date,
				'post_date_gmt' => $post->post_date_gmt,
				'post_status' => $post->post_status,
				'order_number' => $purchase_id,
				'tax' => $purchase->get_tax_total(),
				'shipping' => $purchase->get_shipping_total(),
				'total' => $purchase->get_total(),
				'products' => $purchase->get_products()
			);

		header( 'Content-type: application/json' );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $purchcase_output );
		exit();
	}

	public function payment_endpoint() {
		// Private data
		self::authenticate_request();

		$purchase = array();

		if ( isset( $_REQUEST['method'] ) && is_array( $_REQUEST['cart'] ) ) { 
			
			if ( $_REQUEST['method'] == 'payment' ) { // completed payment
				$purchase['purchase_id'] = self::process_payment( $_REQUEST['amount'], $_REQUEST['cart'], $_REQUEST['shipping'], $_REQUEST['data'] );
			}
			elseif ( $_REQUEST['method'] == 'authorization' ) { // needs CC authorization.
				$purchase['purchase_id'] = self::authorize_payment( $_REQUEST['cc_data'], $_REQUEST['amount'], $_REQUEST['cart'], $_REQUEST['billing'], $_REQUEST['shipping'], $_REQUEST['data'] );
			}

		}

		header( 'Content-type: application/json' );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $purchase );
		exit();

	}

	private function process_payment( $amount, $cart, $shipping_address = array(), $data = array() ) {
		
		// create a new purchase
		$purchase_id = Group_Buying_Purchase::new_purchase( array(
				'user' => self::get_user_id(),
				'items' => $cart
			) );
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		$purchase->complete();

		// create a payment for the purchase
		$deal_info = array();
		foreach ( $purchase->get_products() as $item ) {
			if ( !isset( $deal_info[$item['deal_id']] ) ) {
				$deal_info[$item['deal_id']] = array();
			}
			$deal_info[$item['deal_id']][] = $item;
		}
		$payment_id = Group_Buying_Payment::new_payment( array(
				'payment_method' => self::__('GBS API Purchase'),
				'purchase' => $purchase->get_id(),
				'amount' => $amount,
				'data' => array(
					'api_data' => $data,
					'uncaptured_deals' => $deal_info,
				),
				'deals' => $deal_info,
				'shipping_address' => $shipping_address,
			), Group_Buying_Payment::STATUS_AUTHORIZED );

		$payment = Group_Buying_Payment::get_instance( $payment_id );
		do_action( 'payment_authorized', $payment );

		// complete payment
		do_action( 'payment_captured', $payment, array() );
		$payment->set_status( Group_Buying_Payment::STATUS_COMPLETE );
		do_action( 'payment_complete', $payment );

		// Empty cart
		$cart = Group_Buying_Cart::get_instance( $user_id );
		$cart->empty_cart();

		return $purchase_id;
	}	

	private function authorize_payment( $cc_data, $amount, $cart, $shipping_address = array(), $shipping_address = array(), $data = array() ) {

		$valid = self::validate_credit_card( $cc_data );
		if ( $valid != '' ) {
			echo json_encode( array( 'error_message' => $valid ) );
			exit();
		}

		$payment_processor = Group_Buying_Payment_Processors::get_payment_processor();
		
		// If hybrid get the CC processor
		if ( is_a( $payment_processor, 'Group_Buying_Hybrid_Payment_Processor' ) ) {
			$selected = get_option( Group_Buying_Hybrid_Payment_Processor::ENABLED_PROCESSORS_OPTION );
			foreach ( $selected as $class ) {
				if ( Group_Buying_Payment_Processors::is_cc_processor( $class ) ) {
					$payment_processor = $class;
				}
			}

		}
		// Make sure the processor is a CC processor
		if ( !Group_Buying_Payment_Processors::is_cc_processor( $payment_processor ) ) {
			echo json_encode( array( 'error_message' => 'Payment Processor Not Compatible (not_cc)' ) );
			exit();
		}

		// Does the payment processor have the ability to process this payment?
		if ( !is_callable( array( $payment_processor, 'process_api_payment' ) ) ) {
			echo json_encode( array( 'error_message' => 'Payment Processor Not Compatible (not_callable)' ) );
			exit();
		}

		// create a new purchase
		$purchase_id = Group_Buying_Purchase::new_purchase( array(
				'user' => self::get_user_id(),
				'items' => $cart
			) );
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		

		// Create payment
		$pp = call_user_func( array( $payment_processor, 'get_instance' ) );
		$payment = $pp->process_api_payment( $purchase, $cc_data, $amount, $cart, $billing_address, $shipping_address, $data );
		
		if ( is_a( $payment, 'Group_Buying_Payment' ) ) {
			// Complete purchase
			$purchase->complete();

			// Empty cart
			$cart = Group_Buying_Cart::get_instance( $user_id );
			$cart->empty_cart();
		}

		$info = array();
		$info['payment'] = $payment;
		$info['purchase'] = $purchase;

		header( 'Content-type: application/json' );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $info );
		exit;
	}

	/////////////
	// Utility //
	/////////////

	public function build_deal( $deal_id ) {
		if ( get_post_type( $deal_id ) != Group_Buying_Deal::POST_TYPE )
			return;

		$deal = Group_Buying_Deal::get_instance( $deal_id );
		$post = get_post( $deal_id );

		$item = array(
			'id' => $deal_id,
			'title' => $deal->get_title(),
			'slug' => $post->post_name,
			'post_content' => apply_filters( 'the_content', $post->post_content ),
			'post_date' => $post->post_date,
			'post_date_gmt' => $post->post_date_gmt,
			'post_status' => $post->post_status,
			'post_modified' => $post->post_modified,
			'post_modified_gmt' => $post->post_modified_gmt,
			'guid' => $post->guid,
			'url' => get_permalink( $deal_id ),
			'thumb_url' => wp_get_attachment_thumb_url( get_post_thumbnail_id( $deal_id ) ),
			'images' => self::get_attachments( $deal_id, 'image' ),
			'attachments' => self::get_attachments( $deal_id, 'file' ),

			'status' => $deal->get_status(),
			'amount_saved' => $deal->get_amount_saved(), // string
			'capture_before_expiration' => $deal->capture_before_expiration(), // bool
			'dynamic_price' => $deal->get_dynamic_price(), // array
			'expiration_date' => $deal->get_expiration_date(), // int
			'fine_print' => $deal->get_fine_print(), // string
			'highlights' => $deal->get_highlights(), // string
			'max_purchases' => $deal->get_max_purchases_per_user(), // int
			'max_purchases_per_user' => $deal->get_max_purchases(), // int
			'merchant_id' => $deal->get_merchant_id(), // int
			'min_purchases' => $deal->get_min_purchases(), // int
			'number_of_purchases' => $deal->get_number_of_purchases(), // int
			'price' => $deal->get_price(), // float
			'remaining_allowed_purchases' => $deal->get_remaining_allowed_purchases(), // int
			'remaining_required_purchases' => $deal->get_remaining_required_purchases(), // int
			'taxable' => $deal->get_taxable(), // bool
			'shippable' => $deal->get_shipping(), // string
			'rss_excerpt' => $deal->get_rss_excerpt(), // string
			'value' => $deal->get_value(), // string

			'voucher_expiration_date' => $deal->get_voucher_expiration_date(), // string
			'voucher_how_to_use' => $deal->get_voucher_how_to_use(), // string
			'voucher_locations' => $deal->get_voucher_locations(), // array
			'voucher_logo' => $deal->get_voucher_logo(), // int
			'voucher_map' => $deal->get_voucher_map(), // string
			'merchant_id' => $deal->get_merchant_id(),

			'locations' => get_the_terms( $deal_id, gb_get_deal_location_tax() ),
			'categories' => get_the_terms( $deal_id, gb_get_deal_cat_slug() ),
			'tags' => get_the_terms( $deal_id, gb_get_deal_tag_slug() ),
		);
		if ( function_exists('gb_deal_has_attributes') && gb_deal_has_attributes( $deal_id ) ) {
			foreach ( Group_Buying_Attribute::get_attributes( $deal_id, 'post' ) as $post ) {
				$attribute_id = $post->ID;
				$attribute = Group_Buying_Attribute::get_instance( $attribute_id );
				$item['attributes'][$attribute_id] = array(
						'title' => $post->post_title,
						'description' => $attribute->get_description(),
						'max_purchases' => $attribute->get_max_purchases(),
						'remaining_purchases' => $attribute->remaining_purchases(),
						'categories' => $attribute->get_categories(),
						'sku' => $attribute->get_sku(),
						'price' => $attribute->get_price(),
						'post_date' => $post->post_date,
						'post_date_gmt' => $post->post_date_gmt,
						'post_status' => $post->post_status,
						'post_modified' => $post->post_modified,
						'post_modified_gmt' => $post->post_modified_gmt

					);
			}
			
		}
		return $item;
	}

	public function build_merchant( $merchant_id ) {
		if ( get_post_type( $merchant_id ) != Group_Buying_Merchant::POST_TYPE )
			return;

		$merchant = Group_Buying_Merchant::get_instance( $merchant_id );
		$post = get_post( $merchant_id );

		$item = array(
			'id' => $merchant_id,
			'title' => $post->post_title,
			'slug' => $post->post_name,
			'post_content' => apply_filters( 'the_content', $post->post_content ),
			'post_date' => $post->post_date,
			'post_date_gmt' => $post->post_date_gmt,
			'post_status' => $post->post_status,
			'post_modified' => $post->post_modified,
			'post_modified_gmt' => $post->post_modified_gmt,
			'guid' => $post->guid,
			'url' => get_permalink( $merchant_id ),
			'images' => self::get_attachments( $merchant_id, 'image' ),
			'attachments' => self::get_attachments( $merchant_id, 'file' ),

			'associated_deals' => $merchant->get_deal_ids(), // array
			'authorized_users' => $merchant->get_authorized_users(), // array

			'contact_title' => $merchant->get_contact_title(), // string
			'contact_name' => $merchant->get_contact_name(), // string
			'contact_street' => $merchant->get_contact_street(), // string
			'contact_city' => $merchant->get_contact_city(), // string
			'contact_state' => $merchant->get_contact_state(), // string
			'contact_postal_code' => $merchant->get_contact_postal_code(), // string
			'contact_country' => $merchant->get_contact_country(), // string
			'contact_phone' => $merchant->get_contact_phone(), // string
			'website' => $merchant->get_website(), // string
			'facebook' => $merchant->get_facebook(), // string
			'twitter' => $merchant->get_twitter(), // string
		);
		return $item;
	}

	public function build_voucher( $voucher_id ) {
		// Private data
		self::authenticate_request();

		if ( get_post_type( $voucher_id ) != Group_Buying_Voucher::POST_TYPE )
			return;

		$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
		$deal = $voucher->get_deal();

		if ( !is_a( $deal, 'Group_Buying_Deal' ) ) {
			return array( 'error' => 1, 'error_message' => 'deal no longer exists' );
		}

		$post = get_post( $voucher_id );

		$item = array(
			'id' => $voucher_id,
			'title' => $post->post_title,
			'slug' => $post->post_name,
			'post_date' => $post->post_date,
			'post_date_gmt' => $post->post_date_gmt,
			'post_status' => $post->post_status,
			'post_modified' => $post->post_modified,
			'post_modified_gmt' => $post->post_modified_gmt,
			'guid' => $post->guid,
			'url' => get_permalink( $voucher_id ),
			'claim_url' => gb_get_voucher_claim_url( $voucher->get_security_code(), FALSE ),

			'deal_id' => $voucher->get_deal_id(), // string
			'purchase_id' => $voucher->get_purchase_id(), // string
			'claimed_date' => $voucher->get_claimed_date(), // string
			'raw_product_data' => $voucher->get_product_data(), // array
			'redemption_data' => $voucher->get_redemption_data(), // array
			'security_code' => $voucher->get_security_code(), // string
			'serial_number' => $voucher->get_serial_number(), // string

			'voucher_expiration_date' => $voucher->get_expiration_date(), // string
			'voucher_how_to_use' => $voucher->get_usage_instructions(), // string
			'fine_print' => $voucher->get_fine_print(), // string
			'voucher_locations' => $voucher->get_locations(), // array
			'voucher_logo' => $voucher->get_logo(), // int
			'voucher_map' => $voucher->get_map(), // string

			'merchant_id' => $deal->get_merchant_id()
		);
		return $item;
	}

	public function get_attachments( $post_id, $type = 'image' ) {
		$images = array();
		$files = array();
		$attachments = get_children( array( 'post_parent' => $post_id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'order' => 'ASC', 'orderby' => 'menu_order ID' ) );
		foreach ( $attachments as $attachment ) {
			if ( substr( $attachment->post_mime_type, 0, 5 ) == 'image'  ) {
				$images['url'] = $attachment->guid;
				$images['thumb_url'] = wp_get_attachment_thumb_url( $attachment->ID );
				$images['id'] = $attachment->ID;
				$images['post_mime_type'] = $attachment->post_mime_type;
				$images['post_modified'] = $attachment->post_modified;
				$images['post_modified_gmt'] = $attachment->post_modified_gmt;
				$images['post_date'] = $attachment->post_date;
				$images['post_date_gmt'] = $attachment->post_date_gmt;
				$images['post_title'] = $attachment->post_title;
				$images['post_excerpt'] = $attachment->post_excerpt;
				$images['post_name'] = $attachment->post_name;
			}
			else {
				$files['url'] = $attachment->guid;
				$files['thumb_url'] = wp_get_attachment_thumb_url( $attachment->ID );
				$files['id'] = $attachment->ID;
				$files['post_mime_type'] = $attachment->post_mime_type;
				$files['post_modified'] = $attachment->post_modified;
				$files['post_modified_gmt'] = $attachment->post_modified_gmt;
				$files['post_date'] = $attachment->post_date;
				$files['post_date_gmt'] = $attachment->post_date_gmt;
				$files['post_title'] = $attachment->post_title;
				$files['post_excerpt'] = $attachment->post_excerpt;
				$files['post_name'] = $attachment->post_name;
			}
		}
		if ( $type == 'image' ) {
			return $images;
		}
		elseif ( $type == 'file' ) {
			return $files;
		}
		return array_merge( $images, $files );
	}

	private function validate_credit_card( $cc_data ) {
		$error = '';
		if ( isset( $cc_data['cc_number'] ) ) {
			if ( !Group_Buying_Credit_Card_Processors::is_valid_credit_card( $cc_data['cc_number'] ) ) {
				$error = self::__( 'Invalid credit card number' );
			}
		}

		if ( isset( $cc_data['cc_cvv'] ) ) {
			if ( !Group_Buying_Credit_Card_Processors::is_valid_cvv( $cc_data['cc_cvv'] ) ) {
				$error = self::__( 'Invalid credit card security code' );
			}
		}

		if ( isset( $cc_data['cc_expiration_year'] ) ) {
			if ( Group_Buying_Credit_Card_Processors::is_expired( $cc_data['cc_expiration_year'], $cc_data['cc_expiration_month'] ) ) {
				$error = self::__( 'Credit card is expired.' );
			}
		}

		return $error;
	}


	////////////////////////////
	// Authentication Methods //
	////////////////////////////

	public static function api_get_token() {
		if ( !isset( $_REQUEST['user'] ) || !isset( $_REQUEST['pwd'] ) ) {
			status_header( 401 );
			exit();
		}
		$user = wp_signon( array(
				'user_login' => $_REQUEST['user'],
				'user_password' => $_REQUEST['pwd'],
				'remember' => FALSE,
			) );
		if ( !$user || is_wp_error( $user ) ) {
			status_header( 401 );
			exit();
		}
		$token = self::get_user_token( $user );
		if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $token );
		exit();
	}

	/**
	 * Verify that the current request is valid and authenticated
	 *
	 * @param bool    $die If TRUE, execution will stop on failure
	 * @return int|bool The authenticated user's ID, or FALSE on failure
	 */
	private static function authenticate_request( $die = TRUE ) {
		$user_id = FALSE;
		if ( !empty( $_REQUEST['user'] ) && !empty( $_REQUEST['signature'] ) && !empty( $_REQUEST['timestamp'] ) ) {
			$user = self::get_user();
			if ( ( time() - $_REQUEST['timestamp'] < self::TIMEOUT ) && $user ) {
				$token = self::get_user_token( $user );

				$hash = $_SERVER['REQUEST_URI'];
				$request = $_REQUEST;
				unset( $request['signature'] );
				ksort( $request );
				if ( $request ) {
					$hash .= '?'.http_build_query( $request, '', '&' );
				}
				$hash .= $token;
				$hash .= self::$private_key;
				$hash = hash( 'sha256', $hash );
				if ( $hash == $_REQUEST['signature'] ) {
					$user_id = $user->ID;
				}
			}
		}
		$user_id = apply_filters( 'gb_api_authenticate_request_user_id', $user_id, $_REQUEST, $user );
		if ( $die && !$user_id ) {
			status_header( 401 );
			if ( GBS_DEV ) header( 'Access-Control-Allow-Origin: *' );
			die( -1 );
		}
		return $user_id;
	}

	/**
	 * Get (and create if necessary) an API token for the user
	 *
	 * @param WP_User|int $user
	 * @return string
	 */
	private static function get_user_token( $user = 0 ) {
		$user = $user ? $user : wp_get_current_user();
		if ( !is_object( $user ) ) {
			$user = new WP_User( $user );
		}
		if ( !$user->ID ) {
			return FALSE;
		}
		$stored = get_user_option( 'gbs_api_token', $user->ID );
		if ( $stored ) {
			return $stored;
		}

		$now = time();
		$token = md5( serialize( $user ).$now );
		update_user_option( $user->ID, 'gbs_api_token', $token );
		update_user_option( $user->ID, 'gbs_api_token_timestamp', $now );
		return $token;
	}

	/**
	 * Delete a user's stored token
	 *
	 * @param int     $user_id
	 */
	private static function revoke_user_token( $user_id = 0 ) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		delete_user_option( $user_id, 'gbs_api_token' );
	}

	public function get_user() {
		if ( !isset( $_REQUEST['user'] ) )
			return;

		return get_user_by( 'login', $_REQUEST['user'] );
	}

	public function get_user_id() {
		$user = self::get_user();
		return $user->ID;
	}

	public function get_account() {
		return Group_Buying_Account::get_instance( self::get_user_id() );
	}


	//////////////////////
	// Testing Methods //
	//////////////////////

	public static function api_do_test() {
		$api_call = self::API_BASE_PATH.'/payment/';

		// Signin
		$token_request = array(
			'user' => 'admin',
			'pwd' => '4242'
		);
		$response = wp_remote_post( home_url( self::API_BASE_PATH.'/token' ), array( 'body' => $token_request ) );
		$token = json_decode( wp_remote_retrieve_body( $response ) );

		// Build package
		$request = array(
			'user' => $token_request['user'], // Required
			'deal_id' => 9955,
			// 'expired' => 'filter', // deal filters
			//'filter' => 'active',
			// 'location' => '17,13',
			'method' => 'authorization',
			'timestamp' => time(),
			'cart' => array(
					array(
						'deal_id' => 9955,
      					'qty' => 10,
						'data' => array(
							'attribute_id' => 9959
						)
					)
				)
		);
		ksort( $request );
		$signature = '/'.$api_call.'?'.http_build_query( $request ).$token.self::$private_key;

		// Create signature
		$request['signature'] = hash( 'sha256', $signature );

		// Print the request
		echo "<h1>Request</h1>";
		prp( $request );

		// Test Callback
		$response = wp_remote_post( home_url( $api_call ), array( 'body' => $request ) );
		$body = wp_remote_retrieve_body( $response );
		echo "<h1>API Response</h1>";
		prp( json_decode( $body ) );
		prp( $body );

	}

	public static function api_authenticate_test() {
		self::authenticate_request();
		prp( $_REQUEST );
		exit();
	}
}
