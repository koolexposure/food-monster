<?php

/**
 * Deal controller
 *
 * @package GBS
 * @subpackage Deal
 */
class Group_Buying_Deals extends Group_Buying_Controller {
	const CRON_HOOK = 'gb_deals_cron';

	public static function init() {
		if ( is_admin() ) {
			// deals submitted on the front-end won't have meta boxes
			add_action( 'add_meta_boxes', array( get_class(), 'add_meta_boxes' ) );
			add_action( 'save_post', array( get_class(), 'save_meta_boxes' ), 10, 2 );
		}
		add_filter( 'template_include', array( get_class(), 'override_template' ) );
		add_action( 'init', array( get_class(), 'schedule_cron' ), 10, 0 );
		add_action( self::CRON_HOOK, array( get_class(), 'check_for_expired_deals' ), 10, 0 );
		add_action( 'purchase_completed', array( get_class(), 'purchase_completed' ), 5, 1 ); // run before vouchers are created
		add_action( 'admin_init', array( get_class(), 'queue_deal_resources' ) );
		add_filter( 'gb_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 10, 1 );

		// Admin columns
		add_filter ( 'manage_edit-'.Group_Buying_Deal::POST_TYPE.'_columns', array( get_class(), 'register_columns' ) );
		add_filter ( 'manage_'.Group_Buying_Deal::POST_TYPE.'_posts_custom_column', array( get_class(), 'column_display' ), 10, 2 );
		add_filter( 'manage_edit-'.Group_Buying_Deal::POST_TYPE.'_sortable_columns', array( get_class(), 'sortable_columns' ) );
		add_filter( 'request', array( get_class(), 'column_orderby' ) );

		Group_Buying_Deals_Submit::init();
		Group_Buying_Deals_Preview::init();

		// AJAX Actions
		add_action( 'wp_ajax_nopriv_gbs_ajax_get_deal_info',  array( get_class(), 'ajax_get_deal_info' ), 10, 0 );
		add_action( 'wp_ajax_gbs_ajax_get_deal_info',  array( get_class(), 'ajax_get_deal_info' ), 10, 0 );
	}

	public static function ajax_get_deal_info() {
		$id = $_POST['id'];
		if ( get_post_type( $id ) != Group_Buying_Deal::POST_TYPE ) {
			exit();
		}
		$deal = Group_Buying_Deal::get_instance( $id );
		if ( is_a( $deal, 'Group_Buying_Deal' ) ) {

			header( 'Content-Type: application/json; charset=utf8' );
			$response = array(
				'deal_id' => $deal->get_ID(),
				'title' => $deal->get_title(),
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
				'voucher_id_prefix' => $deal->get_voucher_id_prefix(), //string
				'voucher_locations' => $deal->get_voucher_locations(), // array
				'voucher_logo' => $deal->get_voucher_logo(), // int
				'voucher_map' => $deal->get_voucher_map(), // string
				
			);
			echo json_encode( $response );	
		}
		exit();
	}

	public static function add_meta_boxes() {
		add_meta_box( 'gb_deal_expiration', self::__( 'Expiration Date' ), array( get_class(), 'show_meta_box' ), Group_Buying_Deal::POST_TYPE, 'side', 'high' );
		add_meta_box( 'gb_deal_price', self::__( 'Pricing' ), array( get_class(), 'show_meta_box' ), Group_Buying_Deal::POST_TYPE, 'advanced', 'high' );
		add_meta_box( 'gb_deal_limits', self::__( 'Purchase Limits' ), array( get_class(), 'show_meta_box' ), Group_Buying_Deal::POST_TYPE, 'advanced', 'high' );
		add_meta_box( 'gb_deal_details', self::__( 'Deal Details' ), array( get_class(), 'show_meta_box' ), Group_Buying_Deal::POST_TYPE, 'advanced', 'high' );
		add_meta_box( 'gb_deal_voucher', self::__( 'Voucher' ), array( get_class(), 'show_meta_box' ), Group_Buying_Deal::POST_TYPE, 'advanced', 'high' );
		add_meta_box( 'gb_deal_merchant', self::__( 'Merchant' ), array( get_class(), 'show_meta_box' ), Group_Buying_Deal::POST_TYPE, 'advanced', 'high' );
	}

	public static function show_meta_box( $post, $metabox ) {
		$deal = Group_Buying_Deal::get_instance( $post->ID );
		switch ( $metabox['id'] ) {
		case 'gb_deal_expiration':
			self::show_meta_box_gb_deal_expiration( $deal, $post, $metabox );
			break;
		case 'gb_deal_price':
			self::show_meta_box_gb_deal_price( $deal, $post, $metabox );
			break;
		case 'gb_deal_limits':
			self::show_meta_box_gb_deal_limits( $deal, $post, $metabox );
			break;
		case 'gb_deal_details':
			self::show_meta_box_gb_deal_details( $deal, $post, $metabox );
			break;
		case 'gb_deal_voucher':
			self::show_meta_box_gb_deal_voucher( $deal, $post, $metabox );
			break;
		case 'gb_deal_merchant':
			self::show_meta_box_gb_deal_merchant( $deal, $post, $metabox );
			break;
		default:
			self::unknown_meta_box( $metabox['id'] );
			break;
		}
	}

	public static function save_meta_boxes( $post_id, $post ) {
		// only continue if it's a deal post
		if ( $post->post_type != Group_Buying_Deal::POST_TYPE ) {
			return;
		}
		// don't do anything on autosave, auto-draft, bulk edit, or quick edit
		if ( wp_is_post_autosave( $post_id ) || $post->post_status == 'auto-draft' || defined( 'DOING_AJAX' ) || isset( $_GET['bulk_edit'] ) ) {
			return;
		}
		// Since the save_box_gb_deal_[meta] functions don't check if there's a _POST, a nonce was added to safe guard save_post actions from ... scheduled posts, etc.
		if ( !isset( $_POST['gb_deal_submission'] ) && ( empty( $_POST ) || !check_admin_referer( 'gb_save_metaboxes', 'gb_save_metaboxes_field' ) ) ) {
			return;
		}
		// save all the meta boxes
		$deal = Group_Buying_Deal::get_instance( $post_id );
		self::save_meta_box_gb_deal_price( $deal, $post_id, $post );
		self::save_meta_box_gb_deal_limits( $deal, $post_id, $post );
		self::save_meta_box_gb_deal_details( $deal, $post_id, $post );
		self::save_meta_box_gb_deal_voucher( $deal, $post_id, $post );
		self::save_meta_box_gb_deal_merchant( $deal, $post_id, $post );

		// save expiration last, since it depends on the value of the deal_price meta box
		self::save_meta_box_gb_deal_expiration( $deal, $post_id, $post );
	}

	public static function queue_deal_resources() {
		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : -1;
		if ( ( isset( $_GET['post_type'] ) && Group_Buying_Deal::POST_TYPE == $_GET['post_type'] ) || Group_Buying_Deal::POST_TYPE == get_post_type( $post_id ) || ( is_admin() && ( isset( $_GET['page'] ) && $_GET['page'] == 'group-buying/gb_settings' ) ) ) {
			wp_enqueue_script( 'group-buying-admin-deal', GB_URL . '/resources/js/deal.admin.gbs.js', array( 'jquery', 'jquery-ui-draggable' ), Group_Buying::GB_VERSION );
			wp_enqueue_style( 'group-buying-admin-deal', GB_URL . '/resources/css/deal.admin.gbs.css' );
		}
	}

	/**
	 * Display the deal expiration meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post
	 * @param array   $metabox
	 * @return void
	 */
	private static function show_meta_box_gb_deal_expiration( Group_Buying_Deal $deal, $post, $metabox ) {
		$expiration = $deal->get_expiration_date();
		self::load_view( 'meta_boxes/deal-expiration', array(
				'timestamp' => ( $expiration == Group_Buying_Deal::NO_EXPIRATION_DATE )?( current_time( 'timestamp' )+24*60*60 ):$expiration,
				'never_expires' => ( $expiration == Group_Buying_Deal::NO_EXPIRATION_DATE ),
				'show_vouchers' => $deal->capture_before_expiration(),
			) );
		wp_nonce_field( 'gb_save_metaboxes', 'gb_save_metaboxes_field' );
	}

	/**
	 * Save the deal expiration meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post_id
	 * @param object  $post
	 * @return void
	 */
	private static function save_meta_box_gb_deal_expiration( Group_Buying_Deal $deal, $post_id, $post ) {
		if ( $deal->has_dynamic_price() ) {
			// these options are incompatible with dynamic pricing
			unset( $_POST['deal_expiration_never'] );
			unset( $_POST['deal_capture_before_expiration'] );
		}
		if ( isset( $_POST['deal_expiration_never'] ) && $_POST['deal_expiration_never'] ) {
			$deal->set_expiration_date( Group_Buying_Deal::NO_EXPIRATION_DATE );
			$_POST['deal_capture_before_expiration'] = TRUE; // if it never expires, you have to capture earlier than expiration
		} else {
			$deal->set_expiration_date( strtotime( $_POST['deal_expiration'] ) );
		}
		if ( isset( $_POST['deal_capture_before_expiration'] ) && $_POST['deal_capture_before_expiration'] ) {
			$deal->set_capture_before_expiration( TRUE );
		} else {
			$deal->set_capture_before_expiration( FALSE );
		}
	}

	/**
	 * Display the deal price meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post
	 * @param array   $metabox
	 * @return void
	 */
	private static function show_meta_box_gb_deal_price( Group_Buying_Deal $deal, $post, $metabox ) {
		self::load_view( 'meta_boxes/deal-price', array(
				'price' => $deal->get_price( 0 ),
				'dynamic_price' => $deal->get_dynamic_price(),
				'shipping' => $deal->get_shipping_meta(),
				'shippable' => $deal->get_shippable(),
				'shipping_dyn' => $deal->get_shipping_dyn_price(),
				'shipping_mode' => $deal->get_shipping_mode(),
				'tax' => $deal->get_tax(),
				'taxable' => $deal->get_taxable(),
				'taxrate' => $deal->get_tax_mode()
			) );
	}

	/**
	 * Save the deal price meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post_id
	 * @param object  $post
	 * @return void
	 */
	private static function save_meta_box_gb_deal_price( Group_Buying_Deal $deal, $post_id, $post ) {
		$prices = array( 0=>0 );
		if ( isset( $_POST['deal_base_price'] ) ) {
			if ( is_numeric( $_POST['deal_base_price'] ) ) {
				$prices[0] = $_POST['deal_base_price'];
			}
			$dynamic_prices = isset( $_POST['deal_dynamic_price'] ) ? (array) $_POST['deal_dynamic_price'] : array();
			foreach ( $dynamic_prices as $qty => $price ) {
				if ( is_numeric( $qty ) && is_numeric( $price ) ) {
					$prices[(int)$qty] = $price;
				}
			}
		}
		ksort( $prices );
		$deal->set_prices( $prices );

		$taxable = isset( $_POST['deal_base_taxable'] ) ? $_POST['deal_base_taxable'] : '';
		$deal->set_taxable( $taxable );
		$tax = isset( $_POST['deal_base_tax'] ) ? $_POST['deal_base_tax'] : '';
		$deal->set_tax( $tax );
		$shipping = isset( $_POST['deal_shipping'] ) ? $_POST['deal_shipping'] : '';
		$deal->set_shipping( $shipping );
		$deal_base_shippable = isset( $_POST['deal_base_shippable'] ) ? $_POST['deal_base_shippable'] : '';
		$deal->set_shippable( $deal_base_shippable );
		$shipping_mode = isset( $_POST['deal_base_shipping_mode'] ) ? $_POST['deal_base_shipping_mode'] : '';
		$deal->set_shipping_mode( $shipping_mode );

		$shipping_rates = array();
		if ( isset( $_POST['deal_dynamic_shipping'] ) ) {
			foreach ( $_POST['deal_dynamic_shipping']['quantity'] as $key => $rate_id ) {
				if ( $_POST['deal_dynamic_shipping']['quantity'][$key] > 0 && $_POST['deal_dynamic_shipping']['quantity'][$key] != '' ) {
					if ( $_POST['deal_dynamic_shipping']['rate'][$key] == '' ) $_POST['deal_dynamic_shipping']['rate'][$key] = 0;
					$shipping_rates[] = array(
						'quantity' => $_POST['deal_dynamic_shipping']['quantity'][$key],
						'rate' => $_POST['deal_dynamic_shipping']['rate'][$key],
						'per_item' => $_POST['deal_dynamic_shipping']['per_item'][$key]
					);
				}
			}
		}
		$deal->set_shipping_dyn_price( $shipping_rates );
	}

	/**
	 * Display the deal limits meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post
	 * @param array   $metabox
	 * @return void
	 */
	private static function show_meta_box_gb_deal_limits( Group_Buying_Deal $deal, $post, $metabox ) {
		$deal = Group_Buying_Deal::get_instance( $post->ID );
		$min = $deal->get_min_purchases();
		$max = $deal->get_max_purchases();
		$max_per_user = $deal->get_max_purchases_per_user();
		self::load_view( 'meta_boxes/deal-limits', array(
				'minimum' => ( $min > 0 )?$min:0,
				'maximum' => ( $max == Group_Buying_Deal::NO_MAXIMUM )?'':$max,
				'max_per_user' => ( $max_per_user == Group_Buying_Deal::NO_MAXIMUM )?'':$max_per_user,
			) );
	}

	/**
	 * Save the deal limits meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post_id
	 * @param object  $post
	 * @return void
	 */
	private static function save_meta_box_gb_deal_limits( Group_Buying_Deal $deal, $post_id, $post ) {
		$min = 0;
		if ( isset( $_POST['deal_min_purchases'] ) && (int)$_POST['deal_min_purchases'] > 0 ) {
			$min = (int)$_POST['deal_min_purchases'];
		}
		$deal->set_min_purchases( $min );

		$max = Group_Buying_Deal::NO_MAXIMUM;
		if ( isset( $_POST['deal_max_purchases'] )
			&& $_POST['deal_max_purchases'] != '' // blank means no maximum
			&& (int)$_POST['deal_max_purchases'] >= 0
		) {
			$max = (int)$_POST['deal_max_purchases'];
		}
		$deal->set_max_purchases( $max );

		$max_per_user = Group_Buying_Deal::NO_MAXIMUM;
		if ( isset( $_POST['deal_max_purchases_per_user'] )
			&& $_POST['deal_max_purchases_per_user'] != '' // blank means no maximum
			&& (int)$_POST['deal_max_purchases_per_user'] >= 0
		) {
			$max_per_user = (int)$_POST['deal_max_purchases_per_user'];
		}
		$deal->set_max_purchases_per_user( $max_per_user );
		do_action( 'save_gb_meta_box_deal_limits', $deal, $post_id, $post );
	}

	/**
	 * Display the deal details meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post
	 * @param array   $metabox
	 * @return void
	 */
	private static function show_meta_box_gb_deal_details( Group_Buying_Deal $deal, $post, $metabox ) {
		$value = $deal->get_value();
		$amount_saved = $deal->get_amount_saved();
		$highlights = $deal->get_highlights();
		$fine_print = $deal->get_fine_print();
		$rss_excerpt = $deal->get_rss_excerpt();
		self::load_view( 'meta_boxes/deal-details', array(
				'deal_value' => is_null( $value ) ? '' : $value,
				'deal_amount_saved' => is_null( $amount_saved ) ? '' : $amount_saved,
				'deal_highlights' => is_null( $highlights ) ? '' : $highlights,
				'deal_fine_print' => is_null( $fine_print ) ? '' : $fine_print,
				'deal_rss_excerpt' => is_null( $rss_excerpt ) ? '' : $rss_excerpt,
			) );
	}

	/**
	 * Save the deal details meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post_id
	 * @param object  $post
	 * @return void
	 */
	private static function save_meta_box_gb_deal_details( Group_Buying_Deal $deal, $post_id, $post ) {
		$value = isset( $_POST['deal_value'] ) ? $_POST['deal_value'] : '';
		$deal->set_value( $value );

		$amount_saved = isset( $_POST['deal_amount_saved'] ) ? $_POST['deal_amount_saved'] : '';
		$deal->set_amount_saved( $amount_saved );

		$highlights = isset( $_POST['deal_highlights'] ) ? $_POST['deal_highlights'] : '';
		$deal->set_highlights( $highlights );

		$fine_print = isset( $_POST['deal_fine_print'] ) ? $_POST['deal_fine_print'] : '';
		$deal->set_fine_print( $fine_print );

		$rss_excerpt = isset( $_POST['deal_rss_excerpt'] ) ? $_POST['deal_rss_excerpt'] : '';
		$deal->set_rss_excerpt( $rss_excerpt );
	}

	/**
	 * Display the deal voucher meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post
	 * @param array   $metabox
	 * @return void
	 */
	private static function show_meta_box_gb_deal_voucher( Group_Buying_Deal $deal, $post, $metabox ) {
		$voucher_expiration_date = $deal->get_voucher_expiration_date();
		$voucher_how_to_use = $deal->get_voucher_how_to_use();
		$voucher_id_prefix = $deal->get_voucher_id_prefix();
		$voucher_locations = $deal->get_voucher_locations();
		while ( count( $voucher_locations ) < Group_Buying_Deal::MAX_LOCATIONS ) {
			$voucher_locations[] = '';
		}
		$voucher_logo = $deal->get_voucher_logo();
		$voucher_map = $deal->get_voucher_map();
		$voucher_serial_numbers = implode( ',', $deal->get_voucher_serial_numbers() );

		self::load_view( 'meta_boxes/deal-voucher', array(
				'voucher_expiration_date' => is_null( $voucher_expiration_date ) ? '' : $voucher_expiration_date,
				'voucher_how_to_use' => is_null( $voucher_how_to_use ) ? '' : $voucher_how_to_use,
				'voucher_id_prefix' => is_null( $voucher_id_prefix ) ? '' : $voucher_id_prefix,
				'voucher_locations' => $voucher_locations,
				'voucher_logo' => is_null( $voucher_logo ) ? '' : $voucher_logo,
				'voucher_map' => is_null( $voucher_map ) ? '' : $voucher_map,
				'voucher_serial_numbers' => $voucher_serial_numbers,
			) );
	}

	/**
	 * Save the deal voucher meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post_id
	 * @param object  $post
	 * @return void
	 */
	private static function save_meta_box_gb_deal_voucher( Group_Buying_Deal $deal, $post_id, $post ) {
		$expiration_date = isset( $_POST['voucher_expiration_date'] ) ? $_POST['voucher_expiration_date'] : '';
		$deal->set_voucher_expiration_date( $expiration_date );

		$how_to_use = isset( $_POST['voucher_how_to_use'] ) ? $_POST['voucher_how_to_use'] : '';
		$deal->set_voucher_how_to_use( $how_to_use );

		$id_prefix = isset( $_POST['voucher_id_prefix'] ) ? $_POST['voucher_id_prefix'] : '';
		$deal->set_voucher_id_prefix( $id_prefix );

		$locations = isset( $_POST['voucher_locations'] ) ? $_POST['voucher_locations'] : '';
		if ( !is_array( $locations ) ) {
			$locations = array();
		}
		while ( count( $locations ) < Group_Buying_Deal::MAX_LOCATIONS ) {
			$locations[] = '';
		}
		$deal->set_voucher_locations( $locations );

		$logo = isset( $_POST['voucher_logo'] ) ? $_POST['voucher_logo'] : '';
		$deal->set_voucher_logo( $logo );

		$map = isset( $_POST['voucher_map'] ) ? $_POST['voucher_map'] : '';
		$deal->set_voucher_map( $map );

		$serial_numbers = isset( $_POST['voucher_serial_numbers'] ) ? $_POST['voucher_serial_numbers'] : '';
		$serial_numbers = explode( ',', $serial_numbers );
		$serial_numbers = array_map( 'trim', $serial_numbers );
		$deal->set_voucher_serial_numbers( $serial_numbers );
	}

	/**
	 * Display the deal merchant meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post
	 * @param array   $metabox
	 * @return void
	 */
	private static function show_meta_box_gb_deal_merchant( Group_Buying_Deal $deal, $post, $metabox ) {
		$merchants = get_posts( array( 'numberposts' => -1, 'post_type' => Group_Buying_Merchant::POST_TYPE, 'post_status' => array( 'publish', 'draft' ) ) );
		$merchant_id = $deal->get_merchant_id();
		self::load_view( 'meta_boxes/deal-merchant', array(
				'merchants' => $merchants,
				'merchant_id' => $merchant_id
			) );
	}

	/**
	 * Save the deal merchant meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post_id
	 * @param object  $post
	 * @return void
	 */
	private static function save_meta_box_gb_deal_merchant( Group_Buying_Deal $deal, $post_id, $post ) {
		$merchant_id = isset( $_POST['deal_merchant'] ) ? $_POST['deal_merchant'] : '';
		$deal->set_merchant_id( $merchant_id );
	}

	public static function register_columns( $columns ) {
		unset( $columns['date'] );
		unset( $columns['title'] );
		unset( $columns['comments'] );
		unset( $columns['author'] );
		$columns['title'] = self::__( 'Deal' );
		$columns['status'] = self::__( 'Status' );
		$columns['sold'] = self::__( 'Records' );
		$columns['records'] = self::__( 'Reports' );
		$columns['merchant'] = self::__( 'Merchant' );
		$columns['date'] = self::__( 'Published' );
		$columns['comments'] = '<span><span class="vers"><img alt="Comments" src="images/comment-grey-bubble.png"></span></span>';
		return $columns;
	}


	public static function column_display( $column_name, $id ) {
		global $post;
		$deal = Group_Buying_Deal::get_instance( $id );

		if ( !$deal )
			return; // return for that temp post

		switch ( $column_name ) {
		case 'merchant':
			$merchant = Group_Buying_Merchant::get_merchant_object( $id );
			if ( !is_a( $merchant, 'Group_Buying_Merchant' ) ) return;
			printf( '<a href="%1$s">%2$s</a><br/>', get_edit_post_link( $merchant->get_ID() ), get_the_title( $merchant->get_ID() ) );
			printf( self::__( '<a href="%1$s" style="color:silver">%1$s</a><br/>' ), $merchant->get_website() );
			printf( self::__( '<span style="color:silver">%1$s</span>' ), $merchant->get_contact_phone() );
			printf( '<div class="row-actions"><span class="payment"><a href="%1$s">View</a></span></div>', get_permalink( $merchant->get_ID() ) );
			break;
		case 'status':
			$expiration = ( Group_Buying_Deal::NO_EXPIRATION_DATE == $deal->get_expiration_date() ) ? self::__( 'none' ) : date( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), $deal->get_expiration_date() );
			switch ( $deal->get_status() ) {
				case 'open':
					printf( '<span style="color:green">%1$s</span> <span style="color:silver">(id: %2$s)</span> <br/><span style="color:silver">expires: %3$s</span> ', self::__( 'Active' ), $id, $expiration );
					break;
				case 'closed':
					printf( '<span style="color:#BC0B0B">%1$s</span> <span style="color:silver">(id: %2$s)</span> <br/><span style="color:silver">%3$s</span>', self::__( 'Expired' ), $id, $expiration );
					break;
				case 'closed':
					printf( '<span style="color:orange">%1$s</span> <span style="color:silver">(id: %2$s)</span> <br/><span style="color:silver">expiration: %3$s</span>', self::__( 'Pending' ), $id, $expiration );
					break;
				case 'closed':
				default:
					echo '<span style="color:black">'.gb__( 'Unknown' ).'</span>';
					break;
			}
			break;
		case 'sold':
			printf( self::__( 'Sold: %s' ), $deal->get_number_of_purchases() );
			printf( self::__( '<br/><span style="color:silver">Current Price: %s</span>' ), gb_get_formatted_money( $deal->get_price() ) );
			if ( $deal->get_remaining_allowed_purchases() > 0 ) {
				printf( self::__( '<br/><span style="color:silver">Remaining allowed: %s</span>' ), $deal->get_remaining_allowed_purchases() );
			}
			$remaining = (int) $deal->get_remaining_required_purchases();
			if ( $remaining ) {
				printf( self::__( '<br/><span style="color:silver">Remaining required: %s</span>' ), $remaining );
			}
			printf( '<div class="row-actions"><span class="payment"><a href="admin.php?page=group-buying/voucher_records&amp;deal_id=%1$s">Vouchers</a> | <span class="payments"><a href="admin.php?page=group-buying/payment_records&amp;deal_id=%1$s">Payments</a> | </span><span class="purchases"><a href="admin.php?page=group-buying/purchase_records&amp;deal_id=%1$s">Orders</a> | </span><span class="gifts"><a href="admin.php?page=group-buying/gift_records&amp;deal_id=%1$s">Gifts</a></span></div>', $id );
			
			break;
		case 'records':
			echo '<p><a href="'.gb_get_deal_purchase_report_url( $id ).'" class="button">'.self::__( 'Purchases' ).'</a>&nbsp;&nbsp;<a href="'.gb_get_deal_voucher_report_url( $id ).'" class="button">'.self::__( 'Vouchers' ).'</a></p>';
			break;
		default:
			break;
		}
	}

	public function sortable_columns( $columns ) {
		//$columns['status'] = 'status';
		//$columns['sold'] = 'sold';
		//$columns['expires'] = 'expires';
		$columns['id'] = 'id';
		return $columns;
	}
	public function column_orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && is_admin() ) {
			switch ( $vars['orderby'] ) {
			case 'status':
				$vars = array_merge( $vars, array(
						'orderby' => 'SQL' // TODO SQL
					) );
				break;
			case 'expires':
				$vars = array_merge( $vars, array(
						'orderby' => 'SQL' // TODO SQL
					) );
				break;
			case 'sold':
				$vars = array_merge( $vars, array(
						'orderby' => 'SQL' // TODO SQL
					) );
				break;
			default:
				// code...
				break;
			}
		}

		return $vars;
	}

	public static function override_template( $template ) {
		if ( Group_Buying_Deal::is_deal_query() ) {
			if ( is_single() ) {
				$template = self::locate_template( array(
						'deals/single-deal.php',
						'deals/single.php',
						'deals/deal.php',
						'deal.php',
					), $template );
			} else {
				$template = self::locate_template( array(
						'deals/deals.php',
						'deals/index.php',
						'deals/archive.php',
					), $template );
			}
		}
		if ( Group_Buying_Deal::is_deal_tax_query() ) {
			$taxonomy = get_query_var( 'taxonomy' );
			$template = self::locate_template( array(
					'deals/deal-'.$taxonomy.'.php',
					'deals/deal-type.php',
					'deals/deal-types.php',
					'deals/deals.php',
					'deals/index.php',
					'deals/archive.php',
				), $template );
		}
		return $template;
	}

	public static function schedule_cron() {
		if ( !wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'halfhour', self::CRON_HOOK );
		}
	}

	public static function clear_schedule() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	public static function check_for_expired_deals() {
		// in case two processes are kicked off automatically decrease the chances of them conflicting.
		usleep( rand( 1, 1000000 ) );
		$transient = 'check_for_expired_deals_in_progress';
		$in_progress = (int)get_transient( $transient );
		if ( $in_progress ) {
			return;
		}
		// Set in progress transient
		set_transient( $transient, time(), 1801 );

		$now = current_time( 'timestamp' );
		$last_check = (int)get_option( 'gb_expiration_check', 0 );
		$deals = Group_Buying_Deal::get_expired_deals( $last_check );
		foreach ( $deals as $deal_id ) {
			$deal = Group_Buying_Deal::get_instance( $deal_id );
			if ( is_a( $deal, 'Group_Buying_Deal' ) ) {
				do_action( 'deal_expired', $deal );
				if ( $deal->is_successful() ) {
					do_action( 'deal_success', $deal );
				} else {
					do_action( 'deal_failed', $deal );
				}
			}
		}

		delete_transient( $transient );
		update_option( 'gb_expiration_check', $now );
	}

	public static function purchase_completed( Group_Buying_Purchase $purchase ) {
		$products = $purchase->get_products();
		foreach ( $products as $product ) {
			$deal = Group_Buying_Deal::get_instance( $product['deal_id'] );
			$deal->get_number_of_purchases( TRUE ); // recalculate based on latest purchase
		}
	}

	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'edit_deals',
			'title' => self::__( 'Edit Deals' ),
			'href' => admin_url( 'edit.php?post_type='.Group_Buying_Deal::POST_TYPE ),
			'weight' => 0,
		);
		return $items;
	}
}

class Group_Buying_Deals_Submit extends Group_Buying_Controller {
	const SUBMIT_PATH_OPTION = 'gb_submit_deal_path';
	const SUBMIT_QUERY_VAR = 'gb_submit_deal';
	const FORM_ACTION = 'gb_submit_deal';
	private static $submit_path = 'merchant/submit-deal';
	private static $instance;

	public static function init() {
		self::$submit_path = get_option( self::SUBMIT_PATH_OPTION, self::$submit_path );
		self::register_path_callback( self::$submit_path, array( get_class(), 'on_submit_page' ), self::SUBMIT_QUERY_VAR, 'merchant/submit-deal' );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 1 );

		// AJAX
		add_action( 'wp_ajax_gb_location_add', array( get_class(), 'add_location' ) );
		add_action( 'wp_ajax_gb_deal_publish', array( get_class(), 'ajax_publish' ) );
		add_action( 'wp_ajax_gb_deal_draft', array( get_class(), 'ajax_draft' ) );
	}

	public static function on_submit_page() {
		// Unregistered users shouldn't be here
		self::login_required();
		self::get_instance();
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_merchant_paths';

		// Settings
		register_setting( $page, self::SUBMIT_PATH_OPTION );
		add_settings_field( self::SUBMIT_PATH_OPTION, self::__( 'Merchant Submit Path' ), array( get_class(), 'display_path' ), $page, $section );
	}

	public static function display_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::SUBMIT_PATH_OPTION . '" id="' . self::SUBMIT_PATH_OPTION . '" value="' . esc_attr( self::$submit_path ) . '" size="40"/><br />';
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	private function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}
	private function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}
	public static function get_instance() {
		if ( !( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		self::do_not_cache();
		if ( isset( $_POST['gb_deal_submission'] ) && $_POST['gb_deal_submission'] == self::FORM_ACTION ) {
			$this->process_form_submission();
		}
		wp_enqueue_script( 'group-buying-admin-deal', GB_URL . '/resources/js/deal.admin.gbs.js', array( 'jquery', 'jquery-ui-draggable' ), Group_Buying::GB_VERSION );
		wp_enqueue_style( 'group-buying-admin-deal', GB_URL . '/resources/css/deal.admin.gbs.css' );
		add_action( 'pre_get_posts', array( get_class(), 'edit_query' ), 10, 1 );
		add_action( 'the_post', array( $this, 'view_submit_form' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'get_title' ), 10, 2 );
	}

	public static function edit_query( $query ) {
		// we only care if this is the query to show a the edit profile form
		if ( isset( $query->query_vars[self::SUBMIT_QUERY_VAR] ) && $query->query_vars[self::SUBMIT_QUERY_VAR] ) {
			$query->query_vars['post_type'] = Group_Buying_Merchant::POST_TYPE;
			$query->query_vars['post_status'] = 'draft,publish';
			$query->query_vars['p'] = Group_Buying_Merchant::blank_merchant();
		}
	}

	public function view_submit_form( $post ) {
		if ( $post->post_type == Group_Buying_Merchant::POST_TYPE ) {
			remove_filter( 'the_content', 'wpautop' );

			// Load submitted, in case there is a problem and the merchant needs to resubmit
			$expiration = isset( $_POST['gb_deal_exp'] ) ? $_POST['gb_deal_exp'] : '';
			$capture_before_expiration = isset( $_POST['gb_deal_capture_before_expiration'] );
			$price = isset( $_POST['gb_deal_price'] ) ? $_POST['gb_deal_price'] : '';
			$deal_locations = isset( $_POST['gb_deal_locations'] ) ? $_POST['gb_deal_locations'] : array();
			//$dynamic_price = isset( $_POST['gb_deal_dynamic_price'] ) ? $_POST['gb_deal_dynamic_price'] : array();
			$shipping = isset( $_POST['gb_deal_shipping'] ) ? $_POST['gb_deal_shipping'] : '';
			$thumb = isset( $_POST['gb_deal_thumbnail'] ) ? $_POST['gb_deal_thumbnail'] : '';
			$tax = isset( $_POST['gb_deal_tax'] ) ? $_POST['gb_deal_tax'] : '';
			$min = isset( $_POST['gb_deal_min_purchases'] ) ? (int)$_POST['gb_deal_min_purchases'] : 0;
			$max = isset( $_POST['gb_deal_max_purchases'] ) ? (int)$_POST['gb_deal_max_purchases'] : Group_Buying_Deal::NO_MAXIMUM;
			$max_per_user = isset( $_POST['gb_deal_max_per_user'] ) ? (int)$_POST['gb_deal_max_per_user'] : Group_Buying_Deal::NO_MAXIMUM;
			$value = isset( $_POST['gb_deal_value'] ) ? $_POST['gb_deal_value'] : '';
			$amount_saved = isset( $_POST['gb_deal_amount_saved'] ) ? $_POST['gb_deal_amount_saved'] : '';
			$highlights = isset( $_POST['gb_deal_highlights'] ) ? $_POST['gb_deal_highlights'] : '';
			$fine_print = isset( $_POST['gb_deal_fine_print'] ) ? $_POST['gb_deal_fine_print'] : '';
			$rss_excerpt = isset( $_POST['gb_deal_rss_excerpt'] ) ? $_POST['gb_deal_rss_excerpt'] : '';
			$voucher_expiration_date = isset( $_POST['gb_deal_voucher_expiration'] ) ? $_POST['gb_deal_voucher_expiration'] : '';
			$voucher_how_to_use = isset( $_POST['gb_deal_voucher_how_to_use'] ) ? $_POST['gb_deal_voucher_how_to_use'] : '';
			//$voucher_id_prefix = isset( $_POST['gb_deal_voucher_id_prefix'] ) ? $_POST['gb_deal_voucher_id_prefix'] : '';
			$voucher_locations = isset( $_POST['gb_deal_voucher_locations'] ) ? $_POST['gb_deal_voucher_locations'] : '';
			//$voucher_logo = isset( $_POST['gb_deal_voucher_logo'] ) ? $_POST['gb_deal_voucher_logo'] : '';
			$voucher_map = isset( $_POST['gb_deal_voucher_map'] ) ? $_POST['gb_deal_voucher_map'] : '';
			$voucher_serial_numbers = isset( $_POST['gb_deal_voucher_serial_numbers'] ) ? $_POST['gb_deal_voucher_serial_numbers'] : '';

			$view = self::load_view_to_string( 'merchant/submit-deal', array( 'fields' => $this->deal_submission_fields(), 'form_action' => self::FORM_ACTION ) );
			global $pages;
			$pages = array( $view );
		}
	}

	/**
	 * Filter 'the_title' to display the title of the page rather than the merchant
	 *
	 * @static
	 * @param string  $title
	 * @param int     $post_id
	 * @return string
	 */
	public function get_title(  $title, $post_id  ) {
		$post = &get_post( $post_id );
		if ( $post->post_type == Group_Buying_Merchant::POST_TYPE ) {
			return self::__( 'Submit Deal' );
		}
		return $title;
	}

	protected function deal_submission_fields() {

		$fields['title'] = array(
			'weight' => 1,
			'label' => self::__( 'Deal Name' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => '',
			'description' => gb__('<span>Required:</span> Advertised title of deal.')
		);

		$fields['description'] = array(
			'weight' => 2,
			'label' => self::__( 'Deal Description' ),
			'type' => 'textarea',
			'required' => TRUE,
			'default' => '',
			'description' => gb__('<span>Required:</span> Full description of the deal.')
		);

		$fields['thumbnail'] = array(
			'weight' => 3,
			'label' => self::__( 'Deal Image' ),
			'type' => 'file',
			'required' => FALSE,
			'default' => '',
			'description' => gb__('<span>Optional:</span> Featured image for the deal.')
		);

		$fields['exp'] = array(
			'weight' => 5,
			'label' => self::__( 'Deal Expiration' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => '',
			'description' => gb__('<span>Required:</span> Expiration for the deal; purchases will not be allowed after this time.')
		);

		$fields['price'] = array(
			'weight' => 7,
			'label' => self::__( 'Deal Price' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => '29',
			'description' => gb__('<span>Required:</span> Purchase price.')
		);

		$fields['shipping'] = array(
			'weight' => 10,
			'label' => self::__( 'Deal Shipping Cost' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => '0',
			'description' => gb__('<span>Optional:</span> Shipping for each deal purchased.')
		);

		$site_locations = get_terms( array( Group_Buying_Deal::LOCATION_TAXONOMY ), array( 'hide_empty'=>FALSE, 'fields'=>'all' ) );
		$location_options = array();
		foreach ( $site_locations as $site_local ) {
			$location_options[$site_local->term_id] = $site_local->name;
		}
		$fields['locations'] = array(
			'weight' => 12,
			'label' => self::__( 'Locations' ),
			'type' => 'multiselect',
			'required' => FALSE,
			'options' => $location_options,
			'default' => '',
			'description' => gb__('Locations this deal will be available.')
		);

		// Heading
		$fields['purchase_limits'] = array(
			'weight' => 16,
			'label' => self::__( 'Purchase Limits' ),
			'type' => 'heading',
			'required' => FALSE,
		);

		$fields['min_purchases'] = array(
			'weight' => 20,
			'label' => self::__( 'Minimum Purchases' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => '1',
			'description' => gb__('<span>Required:</span> Number of purchases required before the deal is successfully made.')
		);

		$fields['max_purchases'] = array(
			'weight' => 25,
			'label' => self::__( 'Max Purchases' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => '10000',
			'description' => gb__('<span>Required:</span> Maximum number of purchases allowed for this deal.')
		);

		$fields['max_per_user'] = array(
			'weight' => 30,
			'label' => self::__( 'Max Purchases Per User' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => '10000',
			'description' => gb__('<span>Required:</span> Maximum number of purchases allowed for this deal for one user.')
		);

		// Heading
		$fields['deal_details'] = array(
			'weight' => 31,
			'label' => self::__( 'Deal Details' ),
			'type' => 'heading',
			'required' => FALSE,
		);

		$fields['value'] = array(
			'weight' => 35,
			'label' => self::__( 'Value' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => '',
			'description' => gb__('<span>Required:</span> Advertise worth.')
		);

		$fields['amount_saved'] = array(
			'weight' => 40,
			'label' => self::__( 'Savings' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => '',
			'description' => gb__('<span>Optional:</span> Savings that&rsquo;s advertised to the visitors. Examples: "40% off" or "$25 Discount".')
		);

		$fields['highlights'] = array(
			'weight' => 45,
			'label' => self::__( 'Highlights' ),
			'type' => 'textarea',
			'required' => TRUE,
			'default' => '',
			'description' => gb__('<span>Required:</span> Highlights about the deal.')
		);

		$fields['fine_print'] = array(
			'weight' => 50,
			'label' => self::__( 'Fine Print' ),
			'type' => 'textarea',
			'required' => TRUE,
			'default' => '',
			'description' => gb__('<span>Required:</span> Fine print for this deal and voucher.')
		);

		// Heading

		$fields['voucher_expiration'] = array(
			'weight' => 54,
			'label' => self::__( 'Voucher Expiration' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => '',
			'description' => gb__('<span>Required:</span> Voucher expiration.')
		);

		$fields['voucher_details'] = array(
			'weight' => 54,
			'label' => self::__( 'Voucher' ),
			'type' => 'heading',
			'required' => FALSE,
		);

		$fields['voucher_how_to_use'] = array(
			'weight' => 55,
			'label' => self::__( 'How to use' ),
			'type' => 'textarea',
			'required' => TRUE,
			'default' => '',
			'description' => gb__('<span>Required:</span> How the voucher should be used.')
		);
		
		for ($i=0; $i < Group_Buying_Deal::MAX_LOCATIONS; $i++) { 
			$count = $i+1;
			$fields['voucher_locations['.$i.']'] = array(
				'weight' => 60+$i,
				'label' => self::__( 'Redemption Location' ) .'&nbsp;#'.$count,
				'type' => 'text',
				'required' => FALSE,
				'default' => '',
			);
		}

		$fields['voucher_map'] = array(
			'weight' => 65,
			'label' => self::__( 'Map ( Google Maps iframe )' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => '',
			'description' => gb__('<span>Optional:</span> Go to <a href="http://www.mapquest.com/">MapQuest</a> or <a href="http://www.google.com/maps" title="Google Maps">Google Maps</a> and create a map with multiple or single locations. Click on "Link/Embed" at the the top right of your map (MapQuest) or the link icon to the left of your map (Google Maps), copy the code from "Paste HTML to embed in website" here.' )
		);

		$fields['voucher_serial_numbers'] = array(
			'weight' => 70,
			'label' => self::__( 'Voucher Codes' ),
			'type' => 'textarea',
			'required' => FALSE,
			'description' => gb__('<span>Optional:</span> Enter a comma separated list to use your own custom codes for this deal instead of them being dynamically generated. The amount of codes entered should not be less than that of the maximum purchases set above.')
		);

		$fields = apply_filters( 'gb_deal_submission_fields', $fields );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}

	private function process_form_submission() {
		$errors = array();
		$title = isset( $_POST['gb_deal_title'] ) ? esc_html( $_POST['gb_deal_title'] ) : '';
		$description = isset( $_POST['gb_deal_description'] ) ? wp_kses_post( $_POST['gb_deal_description'] ) : '';
		$locations = isset( $_POST['gb_deal_locations'] ) ? $_POST['gb_deal_locations'] : array();
		$expiration = isset( $_POST['gb_deal_exp'] ) ? $_POST['gb_deal_exp'] : '';
		//$capture_before_expiration = isset( $_POST['gb_deal_capture_before_expiration'] );
		$price = isset( $_POST['gb_deal_price'] ) ? $_POST['gb_deal_price'] : '';
		//$dynamic_price = isset( $_POST['gb_deal_dynamic_price'] ) ? $_POST['gb_deal_dynamic_price'] : array();
		$shipping = isset( $_POST['gb_deal_shipping'] ) ? $_POST['gb_deal_shipping'] : '';
		$min = isset( $_POST['gb_deal_min_purchases'] ) ? (int)$_POST['gb_deal_min_purchases'] : 0;
		$max = isset( $_POST['gb_deal_max_purchases'] ) ? (int)$_POST['gb_deal_max_purchases'] : Group_Buying_Deal::NO_MAXIMUM;
		$max_per_user = isset( $_POST['gb_deal_max_per_user'] ) ? (int)$_POST['gb_deal_max_per_user'] : Group_Buying_Deal::NO_MAXIMUM;
		$value = isset( $_POST['gb_deal_value'] ) ? $_POST['gb_deal_value'] : '';
		$amount_saved = isset( $_POST['gb_deal_amount_saved'] ) ? $_POST['gb_deal_amount_saved'] : '';
		$highlights = isset( $_POST['gb_deal_highlights'] ) ? $_POST['gb_deal_highlights'] : '';
		$fine_print = isset( $_POST['gb_deal_fine_print'] ) ? $_POST['gb_deal_fine_print'] : '';
		$rss_excerpt = isset( $_POST['gb_deal_rss_excerpt'] ) ? $_POST['gb_deal_rss_excerpt'] : '';
		$voucher_expiration_date = isset( $_POST['gb_deal_voucher_expiration'] ) ? $_POST['gb_deal_voucher_expiration'] : '';
		$voucher_how_to_use = isset( $_POST['gb_deal_voucher_how_to_use'] ) ? $_POST['gb_deal_voucher_how_to_use'] : '';
		//$voucher_id_prefix = isset( $_POST['gb_deal_voucher_id_prefix'] ) ? $_POST['gb_deal_voucher_id_prefix'] : '';
		$voucher_locations = isset( $_POST['gb_deal_voucher_locations'] ) ? $_POST['gb_deal_voucher_locations'] : '';
		//$voucher_logo = isset( $_POST['gb_deal_voucher_logo'] ) ? $_POST['gb_deal_voucher_logo'] : '';
		$voucher_map = isset( $_POST['gb_deal_voucher_map'] ) ? $_POST['gb_deal_voucher_map'] : '';
		$voucher_serial_numbers = isset( $_POST['gb_deal_voucher_serial_numbers'] ) ? $_POST['gb_deal_voucher_serial_numbers'] : '';

		$errors = array_merge( $errors, $this->validate_deal_submission_fields( $_POST ) );

		$errors = apply_filters( 'gb_validate_deal_submission', $errors, $_POST );

		if ( !empty( $errors ) ) {
			foreach ( $errors as $error ) {
				self::set_message( $error, self::MESSAGE_STATUS_ERROR );
			}
			return FALSE;
		} else {
			$post_id = wp_insert_post( array(
					'post_status' => 'draft',
					'post_type' => Group_Buying_Deal::POST_TYPE,
					'post_title' => $title,
					'post_content' => $description
				) );

			wp_set_post_terms( $post_id, $locations, Group_Buying_Deal::LOCATION_TAXONOMY );

			$deal = Group_Buying_Deal::get_instance( $post_id );
			$deal->set_expiration_date( empty( $expiration ) ? Group_Buying_Deal::NO_EXPIRATION_DATE : strtotime( $expiration ) );
			$deal->set_prices( array( 0 => $price ) );
			$deal->set_shipping( $shipping );
			$deal->set_min_purchases( $min );
			$deal->set_max_purchases( $max );
			$deal->set_max_purchases_per_user( $max_per_user );
			$deal->set_value( $value );
			$deal->set_amount_saved( $amount_saved );
			$deal->set_highlights( $highlights );
			$deal->set_fine_print( $fine_print );
			$deal->set_voucher_expiration_date( $voucher_expiration_date );
			$deal->set_voucher_how_to_use( $voucher_how_to_use );
			$deal->set_voucher_map( $voucher_map );
			$deal->set_voucher_serial_numbers( explode( ',', $voucher_serial_numbers ) );
			$deal->set_merchant_id( Group_Buying_Merchant::get_merchant_id_for_user() );

			// voucher locations
			if ( !is_array( $voucher_locations ) ) {
				$voucher_locations = array();
			}
			while ( count( $voucher_locations ) < Group_Buying_Deal::MAX_LOCATIONS ) {
				$voucher_locations[] = '';
			}
			$deal->set_voucher_locations( $voucher_locations );

			if ( !empty( $_FILES['gb_deal_thumbnail'] ) ) {
				// Set the uploaded field as an attachment
				$deal->set_attachement( $_FILES );
			}

			do_action( 'gb_admin_notification', array( 'subject' => self::__( 'New Deal Submission' ), 'content' => self::__( 'A user has submitted a new deal for your review.' ), $deal ) );

			do_action( 'submit_deal', $deal );

			$url = Group_Buying_Accounts::get_url();
			$url = add_query_arg( 'message', 'deal-submitted', $url );
			self::set_message( __( 'Deal Submitted for Review.' ), self::MESSAGE_STATUS_INFO );
			wp_redirect( $url, 303 );
			exit();
		}
	}

	protected function validate_deal_submission_fields( $submitted ) {
		$errors = array();
		$fields = $this->deal_submission_fields();
		foreach ( $fields as $key => $data ) {
			if ( isset( $data['required'] ) && $data['required'] && !( isset( $submitted['gb_deal_'.$key] ) && $submitted['gb_deal_'.$key] != '' ) ) {
				$errors[] = sprintf( self::__( '"%s" field is required.' ), $data['label'] );
			}
		}
		return $errors;
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$submit_path );
		} else {
			return add_query_arg( self::SUBMIT_QUERY_VAR, 1, home_url() );
		}
	}

	public function get_form() {
		return self::load_view_to_string( 'merchant/submit-deal', array( 'fields' => $this->deal_submission_fields(), 'form_action' => self::FORM_ACTION, ) );
	}


	public function add_location() {
		wp_insert_term( $_REQUEST['location_name'], Group_Buying_Deal::LOCATION_TAXONOMY );
		echo '<span id="ajax_locations">'.gb_get_list_locations( 'ul', FALSE ).'</span>';
		die();
	}

	public static function ajax_publish() {
		if ( isset( $_REQUEST['deal_id'] ) && $_REQUEST['deal_id'] ) {
			$post = array();
			$post['ID'] = $_REQUEST['deal_id'];
			$post['post_name'] = sanitize_title( get_the_title( $_REQUEST['deal_id'] ) );
			$post['post_date_gmt'] = current_time( 'mysql', 1 );
			$post['post_status'] = 'publish';
			$post_id = wp_update_post( $post );
			echo apply_filters( 'gb_ajax_publish', $post_id );
		}
		die();
	}

	public static function ajax_draft() {
		if ( isset( $_REQUEST['deal_id'] ) && $_REQUEST['deal_id'] ) {
			$post = array();
			$post['ID'] = $_REQUEST['deal_id'];
			$post['post_status'] = 'draft';
			$post_id = wp_update_post( $post );
			echo apply_filters( 'gb_ajax_draft', $post_id );
		}
		die();
	}
}

class Group_Buying_Deals_Edit extends Group_Buying_Deals {
	const EDIT_PATH_OPTION = 'gb_deals_edit_path';
	const EDIT_QUERY_VAR = 'gb_deals_edit';
	const FORM_ACTION = 'gb_deals_edit';
	const EDIT_DEAL_QUERY_VAR = 'gb_edit_deal';
	private static $edit_path = 'merchant/edit-deal';
	private static $deal_id;
	private static $instance;

	public static function init() {
		self::$edit_path = get_option( self::EDIT_PATH_OPTION, self::$edit_path );
		self::register_query_var( self::EDIT_DEAL_QUERY_VAR, array( get_class(), 'edit_deal' ) );
		self::register_query_var( self::EDIT_DEAL_QUERY_VAR );
		add_action( 'gb_router_generate_routes', array( get_class(), 'register_path_callback' ), 100, 1 );

		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 1 );
	}

	/**
	 * Register the path callback for the edit page
	 *
	 * @static
	 * @param GB_Router $router
	 * @return void
	 */
	public static function register_path_callback( GB_Router $router ) {
		$args = array(
			'path' => trailingslashit( self::$edit_path ). '([^/]+)/?$',
			'query_vars' => array(
				self::EDIT_DEAL_QUERY_VAR => 1
			),
			'title' => 'Edit Deal',
			'page_arguments' => array( self::EDIT_DEAL_QUERY_VAR ),
			'title_callback' => array( get_class(), 'get_title' ),
			'page_callback' => array( get_class(), 'on_edit_page' ),
			'template' => array(
				self::get_template_path().'/'.str_replace( '/', '-', self::$edit_path ).'.php', // non-default edit path
				self::get_template_path().'/merchant.php', // theme override
				GB_PATH.'/views/public/merchant.php', // default
			),
		);
		$router->add_route( self::EDIT_QUERY_VAR, $args );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_merchant_paths';

		// Settings
		register_setting( $page, self::EDIT_PATH_OPTION );
		add_settings_field( self::EDIT_PATH_OPTION, self::__( 'Merchant Edit Deal Path' ), array( get_class(), 'display_deals_edit_path' ), $page, $section );
	}

	public static function display_deals_edit_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::EDIT_PATH_OPTION . '" id="' . self::EDIT_PATH_OPTION . '" value="' . esc_attr( self::$edit_path ) . '" size="40"/><br />';
	}

	/**
	 *
	 *
	 * @static
	 * @return string The URL to the edit deal page
	 */
	public static function get_url( $post_id = null ) {
		if ( null === $post_id ) {
			global $post;
			$post_id = $post->ID;
		}
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$edit_path ).trailingslashit( $post_id );
		} else {
			$router = GB_Router::get_instance();
			return $router->get_url( self::EDIT_QUERY_VAR, array( self::EDIT_DEAL_QUERY_VAR => $post_id ) );
		}
	}

	/**
	 * We're on the edit deal page
	 *
	 * @static
	 * @return void
	 */
	public static function on_edit_page( $gb_edit_deal = 0 ) {
		// by instantiating, we process any submitted values
		$edit_page = self::get_instance();

		if ( !$gb_edit_deal ) {
			wp_redirect( gb_account_url() );
			exit();
		}

		self::$deal_id = $gb_edit_deal;

		// display the edit form
		$edit_page->view_edit_form();
	}

	/**
	 *
	 *
	 * @static
	 * @return bool Whether the current query is a edit page
	 */
	public static function is_edit_page() {
		$query_var = get_query_var( GB_Router_Utility::QUERY_VAR );
		if (  $query_var == self::EDIT_QUERY_VAR ) {
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	private function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}
	private function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}
	public static function get_instance() {
		if ( !( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		self::do_not_cache();
		if ( isset( $_POST['gb_deal_action'] ) && $_POST['gb_deal_action'] == self::FORM_ACTION ) {
			$this->process_form_submission();
		}
	}

	/**
	 * View the page
	 *
	 * @return void
	 */
	public function view_edit_form() {
		remove_filter( 'the_content', 'wpautop' );
		wp_enqueue_script( 'group-buying-admin-deal', GB_URL . '/resources/js/deal.admin.gbs.js', array( 'jquery', 'jquery-ui-draggable' ), Group_Buying::GB_VERSION );
		wp_enqueue_style( 'group-buying-admin-deal', GB_URL . '/resources/css/deal.admin.gbs.css' );
		$deal = Group_Buying_Deal::get_instance( self::$deal_id );
		self::load_view( 'merchant/edit-deal', array( 'fields' => self::edit_fields( $deal ), 'form_action' => self::FORM_ACTION, 'edit_deal_id' => self::$deal_id ) );
	}

	protected function edit_fields( $deal = FALSE ) {

		if ( is_a( $deal, 'Group_Buying_Deal' ) ) {
			$post_obj = get_post( $deal->get_ID() );
			$title = $deal->get_title();
			$content = apply_filters( 'the_content', $post_obj->post_content );
			$expiration = date( 'm/d/Y G:i', $deal->get_expiration_date() );
			$deal_locations = wp_get_object_terms( $deal->get_ID(), Group_Buying_Deal::LOCATION_TAXONOMY, array( 'fields' => 'ids' ) );
			$price = $deal->get_price();
			$shipping = $deal->get_shipping_meta();
			$min = $deal->get_min_purchases();
			$max = $deal->get_max_purchases();
			$max_per_user = $deal->get_max_purchases_per_user();
			$value = $deal->get_value();
			$amount_saved = $deal->get_amount_saved();
			$highlights = $deal->get_highlights();
			$fine_print = $deal->get_fine_print();
			$voucher_expiration = ( $deal->get_voucher_expiration_date() ) ? date( 'm/d/Y G:i', $deal->get_voucher_expiration_date() ) : date( 'm/d/Y G:i', time()+60*60*24 ) ;
			$voucher_how_to_use = $deal->get_voucher_how_to_use();
			$voucher_map = $deal->get_voucher_map();

			$voucher_locations = $deal->get_voucher_locations();
			while ( count( $voucher_locations ) < Group_Buying_Deal::MAX_LOCATIONS ) {
				$voucher_locations[] = '';
			}
			$voucher_serial_numbers = implode( ',', $deal->get_voucher_serial_numbers() );

			if ( is_a($deal,'Group_Buying_Deal') ) {
				$post_id = $deal->get_id();
				$img_array = wp_get_attachment_image_src(get_post_thumbnail_id( $post_id ));
				$deal_image = $img_array[0];
			}

		} else {
			$title = '';
			$content = '';
			// Load submitted, in case there is a problem and the merchant needs to resubmit
			$expiration = isset( $_POST['gb_deal_exp'] ) ? $_POST['gb_deal_exp'] : '';
			$capture_before_expiration = isset( $_POST['gb_deal_capture_before_expiration'] );
			$price = isset( $_POST['gb_deal_price'] ) ? $_POST['gb_deal_price'] : '';
			$deal_locations = isset( $_POST['gb_deal_locations'] ) ? $_POST['gb_deal_locations'] : array();
			//$dynamic_price = isset( $_POST['gb_deal_dynamic_price'] ) ? $_POST['gb_deal_dynamic_price'] : array();
			$shipping = isset( $_POST['gb_deal_shipping'] ) ? $_POST['gb_deal_shipping'] : '';
			$thumb = isset( $_POST['gb_deal_thumbnail'] ) ? $_POST['gb_deal_thumbnail'] : '';
			$tax = isset( $_POST['gb_deal_tax'] ) ? $_POST['gb_deal_tax'] : '';
			$min = isset( $_POST['gb_deal_min_purchases'] ) ? (int)$_POST['gb_deal_min_purchases'] : 0;
			$max = isset( $_POST['gb_deal_max_purchases'] ) ? (int)$_POST['gb_deal_max_purchases'] : Group_Buying_Deal::NO_MAXIMUM;
			$max_per_user = isset( $_POST['gb_deal_max_per_user'] ) ? (int)$_POST['gb_deal_max_per_user'] : Group_Buying_Deal::NO_MAXIMUM;
			$value = isset( $_POST['gb_deal_value'] ) ? $_POST['gb_deal_value'] : '';
			$amount_saved = isset( $_POST['gb_deal_amount_saved'] ) ? $_POST['gb_deal_amount_saved'] : '';
			$highlights = isset( $_POST['gb_deal_highlights'] ) ? $_POST['gb_deal_highlights'] : '';
			$fine_print = isset( $_POST['gb_deal_fine_print'] ) ? $_POST['gb_deal_fine_print'] : '';
			$rss_excerpt = isset( $_POST['gb_deal_rss_excerpt'] ) ? $_POST['gb_deal_rss_excerpt'] : '';
			$voucher_expiration = isset( $_POST['gb_deal_voucher_expiration'] ) ? $_POST['gb_deal_voucher_expiration'] : '';
			$voucher_how_to_use = isset( $_POST['gb_deal_voucher_how_to_use'] ) ? $_POST['gb_deal_voucher_how_to_use'] : '';
			//$voucher_id_prefix = isset( $_POST['gb_deal_voucher_id_prefix'] ) ? $_POST['gb_deal_voucher_id_prefix'] : '';
			$voucher_locations = isset( $_POST['gb_deal_voucher_locations'] ) ? $_POST['gb_deal_voucher_locations'] : '';
			//$voucher_logo = isset( $_POST['gb_deal_voucher_logo'] ) ? $_POST['gb_deal_voucher_logo'] : '';
			$voucher_map = isset( $_POST['gb_deal_voucher_map'] ) ? $_POST['gb_deal_voucher_map'] : '';
			$voucher_serial_numbers = isset( $_POST['gb_deal_voucher_serial_numbers'] ) ? $_POST['gb_deal_voucher_serial_numbers'] : '';
			$deal_image = null;
		}

		$fields['title'] = array(
			'weight' => 1,
			'label' => self::__( 'Deal Name' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => $title,
			'description' => gb__('<span>Required:</span> Advertised title of deal.')
		);

		$fields['description'] = array(
			'weight' => 2,
			'label' => self::__( 'Deal Description' ),
			'type' => 'textarea',
			'required' => TRUE,
			'default' => $content,
			'description' => gb__('<span>Required:</span> Full description of the deal.')
		);

		$fields['thumbnail'] = array(
			'weight' => 3,
			'label' => self::__( 'Deal Image' ),
			'type' => 'file',
			'required' => FALSE,
			'default' => $deal_image,
			'description' => gb__('<span>Optional:</span> Featured image for the deal.')
		);

		$fields['exp'] = array(
			'weight' => 5,
			'label' => self::__( 'Deal Expiration' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => $expiration,
			'description' => gb__('<span>Required:</span> Expiration for the deal; purchases will not be allowed after this time.')
		);

		$fields['price'] = array(
			'weight' => 7,
			'label' => self::__( 'Deal Price' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => $price,
			'description' => gb__('<span>Required:</span> Purchase price.')
		);

		$fields['shipping'] = array(
			'weight' => 10,
			'label' => self::__( 'Deal Shipping Cost' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => $shipping,
			'description' => gb__('<span>Optional:</span> Locations this deal will be available.')
		);

		$site_locations = get_terms( array( Group_Buying_Deal::LOCATION_TAXONOMY ), array( 'hide_empty'=>FALSE, 'fields'=>'all' ) );
		$location_options = array();
		foreach ( $site_locations as $site_local ) {
			$location_options[$site_local->term_id] = $site_local->name;
		}
		$fields['locations'] = array(
			'weight' => 12,
			'label' => self::__( 'Locations' ),
			'type' => 'multiselect',
			'required' => FALSE,
			'options' => $location_options,
			'default' => $deal_locations,
			'description' => gb__('<span>Required:</span> Locations this deal will be available.')
		);

		// Heading
		$fields['purchase_limits'] = array(
			'weight' => 16,
			'label' => self::__( 'Purchase Limits' ),
			'type' => 'heading',
			'required' => FALSE,
		);

		$fields['min_purchases'] = array(
			'weight' => 20,
			'label' => self::__( 'Minimum Purchases' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => $min,
			'description' => gb__('<span>Required:</span> Number of purchases required before the deal is successfully made.')
		);

		$fields['max_purchases'] = array(
			'weight' => 25,
			'label' => self::__( 'Max Purchases' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => $max,
			'description' => gb__('<span>Required:</span> Maximum number of purchases allowed for this deal.')
		);

		$fields['max_per_user'] = array(
			'weight' => 30,
			'label' => self::__( 'Max Purchases Per User' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => $max_per_user,
			'description' => gb__('<span>Required:</span> Maximum number of purchases allowed for this deal for one user.')
		);

		// Heading
		$fields['deal_details'] = array(
			'weight' => 31,
			'label' => self::__( 'Deal Details' ),
			'type' => 'heading',
			'required' => FALSE,
		);

		$fields['value'] = array(
			'weight' => 35,
			'label' => self::__( 'Value' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => $value,
			'description' => gb__('<span>Required:</span> Advertise worth.')
		);

		$fields['amount_saved'] = array(
			'weight' => 40,
			'label' => self::__( 'Savings' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => $amount_saved,
			'description' => gb__('<span>Optional:</span> Savings that&rsquo;s advertised to the visitors. Examples: "40% off" or "$25 Discount".')
		);

		$fields['highlights'] = array(
			'weight' => 45,
			'label' => self::__( 'Highlights' ),
			'type' => 'textarea',
			'required' => TRUE,
			'default' => $highlights,
			'description' => gb__('<span>Required:</span> Highlights about the deal.')
		);

		$fields['fine_print'] = array(
			'weight' => 50,
			'label' => self::__( 'Fine Print' ),
			'type' => 'textarea',
			'required' => TRUE,
			'default' => $fine_print,
			'description' => gb__('<span>Required:</span> Fine print for this deal and voucher.')
		);

		// Heading

		$fields['voucher_expiration'] = array(
			'weight' => 54,
			'label' => self::__( 'Voucher Expiration' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => $voucher_expiration,
			'description' => gb__('<span>Required:</span> Voucher expiration.')
		);

		$fields['voucher_details'] = array(
			'weight' => 54,
			'label' => self::__( 'Voucher' ),
			'type' => 'heading',
			'required' => FALSE,
		);

		$fields['voucher_how_to_use'] = array(
			'weight' => 55,
			'label' => self::__( 'How to use' ),
			'type' => 'textarea',
			'required' => TRUE,
			'default' => $voucher_how_to_use,
			'description' => gb__('<span>Required:</span> How the voucher should be used.')
		);
		
		foreach ( $voucher_locations as $index => $location ) {
			$count = (int)$index+1;
			$fields['voucher_locations['.$index.']'] = array(
				'weight' => 60+$index,
				'label' => self::__( 'Redemption Location' ) .'&nbsp;#'.$count,
				'type' => 'text',
				'required' => FALSE,
				'default' => $location,
			);
		}

		$fields['voucher_map'] = array(
			'weight' => 65,
			'label' => self::__( 'Map ( Google Maps iframe )' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => esc_html__( $voucher_map ),
			'description' => gb__('<span>Optional:</span> Go to <a href="http://www.mapquest.com/">MapQuest</a> or <a href="http://www.google.com/maps" title="Google Maps">Google Maps</a> and create a map with multiple or single locations. Click on "Link/Embed" at the the top right of your map (MapQuest) or the link icon to the left of your map (Google Maps), copy the code from "Paste HTML to embed in website" here.' )
		);

		$fields['voucher_serial_numbers'] = array(
			'weight' => 70,
			'label' => self::__( 'Voucher Codes' ),
			'type' => 'textarea',
			'required' => FALSE,
			'default' => $voucher_serial_numbers,
			'description' => gb__('<span>Optional:</span> Enter a comma separated list to use your own custom codes for this deal instead of them being dynamically generated. The amount of codes entered should not be less than that of the maximum purchases set above.')
		);

		$fields = apply_filters( 'gb_deal_submission_fields', $fields );
		$fields = apply_filters( 'gb_edit_deal_submission_fields', $fields );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}

	public function get_title( $title ) {
		$title = get_the_title( self::$deal_id );
		return sprintf( self::__( "Edit: %s" ), $title );
	}

	private function process_form_submission() {
		$errors = array();
		$title = isset( $_POST['gb_deal_title'] ) ? esc_html( $_POST['gb_deal_title'] ) : '';
		$content = isset( $_POST['gb_deal_description'] ) ? wp_kses_post( $_POST['gb_deal_description'] ) : 'Please enter information about your business here.';
		$locations = isset( $_POST['gb_deal_locations'] ) ? $_POST['gb_deal_locations'] : array();
		$expiration = isset( $_POST['gb_deal_exp'] ) ? $_POST['gb_deal_exp'] : '';
		//$capture_before_expiration = isset( $_POST['gb_deal_capture_before_expiration'] );
		$price = isset( $_POST['gb_deal_price'] ) ? $_POST['gb_deal_price'] : '';
		//$dynamic_price = isset( $_POST['gb_deal_dynamic_price'] ) ? $_POST['gb_deal_dynamic_price'] : array();
		$shipping = isset( $_POST['gb_deal_shipping'] ) ? $_POST['gb_deal_shipping'] : '';
		$min = isset( $_POST['gb_deal_min_purchases'] ) ? (int)$_POST['gb_deal_min_purchases'] : 0;
		$max = isset( $_POST['gb_deal_max_purchases'] ) ? (int)$_POST['gb_deal_max_purchases'] : Group_Buying_Deal::NO_MAXIMUM;
		$max_per_user = isset( $_POST['gb_deal_max_per_user'] ) ? (int)$_POST['gb_deal_max_per_user'] : Group_Buying_Deal::NO_MAXIMUM;
		$value = isset( $_POST['gb_deal_value'] ) ? $_POST['gb_deal_value'] : '';
		$amount_saved = isset( $_POST['gb_deal_amount_saved'] ) ? $_POST['gb_deal_amount_saved'] : '';
		$highlights = isset( $_POST['gb_deal_highlights'] ) ? $_POST['gb_deal_highlights'] : '';
		$fine_print = isset( $_POST['gb_deal_fine_print'] ) ? $_POST['gb_deal_fine_print'] : '';
		$rss_excerpt = isset( $_POST['gb_deal_rss_excerpt'] ) ? $_POST['gb_deal_rss_excerpt'] : '';
		$voucher_expiration_date = isset( $_POST['gb_deal_voucher_expiration'] ) ? $_POST['gb_deal_voucher_expiration'] : '';
		$voucher_how_to_use = isset( $_POST['gb_deal_voucher_how_to_use'] ) ? $_POST['gb_deal_voucher_how_to_use'] : '';
		//$voucher_id_prefix = isset( $_POST['gb_deal_voucher_id_prefix'] ) ? $_POST['gb_deal_voucher_id_prefix'] : '';
		$voucher_locations = isset( $_POST['gb_deal_voucher_locations'] ) ? $_POST['gb_deal_voucher_locations'] : '';
		//$voucher_logo = isset( $_POST['gb_deal_voucher_logo'] ) ? $_POST['gb_deal_voucher_logo'] : '';
		$voucher_map = isset( $_POST['gb_deal_voucher_map'] ) ? $_POST['gb_deal_voucher_map'] : '';
		$voucher_serial_numbers = isset( $_POST['gb_deal_voucher_serial_numbers'] ) ? $_POST['gb_deal_voucher_serial_numbers'] : '';

		$errors = array_merge( $errors, $this->validate_deal_submission_fields( $_POST ) );
		$errors = apply_filters( 'gb_validate_deal_submission', $errors, $_POST );
		$errors = apply_filters( 'gb_validate_deal_edit', $errors, $_POST );

		if ( !empty( $errors ) ) {
			foreach ( $errors as $error ) {
				self::set_message( $error, self::MESSAGE_STATUS_ERROR );
			}
			return FALSE;
		} else {
			$post_id = $_POST['gb_deal_edited'];

			// TODO for some reason wp_update_post produces an error.
			global $wpdb;
			$data = stripslashes_deep( array( 'post_title' => $title, 'post_content' => $content ) );
			$wpdb->update( $wpdb->posts, $data, array( 'ID' => $post_id ) );

			wp_set_post_terms( $post_id, $locations, Group_Buying_Deal::LOCATION_TAXONOMY );

			$deal = Group_Buying_Deal::get_instance( $post_id );
			$deal->set_expiration_date( empty( $expiration ) ? Group_Buying_Deal::NO_EXPIRATION_DATE : strtotime( $expiration ) );
			$deal->set_prices( array( 0 => $price ) );
			$deal->set_shipping( $shipping );
			$deal->set_min_purchases( $min );
			$deal->set_max_purchases( $max );
			$deal->set_max_purchases_per_user( $max_per_user );
			$deal->set_value( $value );
			$deal->set_amount_saved( $amount_saved );
			$deal->set_highlights( $highlights );
			$deal->set_fine_print( $fine_print );
			$deal->set_voucher_expiration_date( $voucher_expiration_date );
			$deal->set_voucher_how_to_use( $voucher_how_to_use );
			$deal->set_voucher_map( $voucher_map );
			$deal->set_voucher_serial_numbers( explode( ',', $voucher_serial_numbers ) );
			$deal->set_merchant_id( Group_Buying_Merchant::get_merchant_id_for_user() );

			// voucher locations
			if ( !is_array( $voucher_locations ) ) {
				$voucher_locations = array();
			}
			while ( count( $voucher_locations ) < Group_Buying_Deal::MAX_LOCATIONS ) {
				$voucher_locations[] = '';
			}
			$deal->set_voucher_locations( $voucher_locations );

			if ( !empty( $_FILES['gb_deal_thumbnail'] ) ) {
				// Set the uploaded field as an attachment
				$deal->set_attachement( $_FILES );
			}

			do_action( 'gb_admin_notification', array( 'subject' => self::__( 'Deal Edited' ), 'content' => sprintf( self::__( 'A merchant has updated their deal. Deal ID #%s' ), $deal->get_id() ), $deal ) );

			do_action( 'edit_deal', $deal );

			if ( !empty( $_POST['_wp_http_referer'] ) ) {
				$url = site_url( stripslashes( $_POST['_wp_http_referer'] ) );
			} else {
				$url = Group_Buying_Accounts::get_url();
			}
			$url = add_query_arg( 'message', 'deal-updated', $url );
			self::set_message( __( 'Deal Updated.' ), self::MESSAGE_STATUS_INFO );
			wp_redirect( $url, 303 );
			exit();
		}
	}

	protected function validate_deal_submission_fields( $submitted ) {
		$errors = array();
		$fields = self::edit_fields();
		foreach ( $fields as $key => $data ) {
			if ( isset( $data['required'] ) && $data['required'] && !( isset( $submitted['gb_deal_'.$key] ) && $submitted['gb_deal_'.$key] != '' ) ) {
				$errors[] = sprintf( self::__( '"%s" field is required.' ), $data['label'] );
			}
		}
		return $errors;
	}
}

class Group_Buying_Deals_Preview extends Group_Buying_Controller {

	const NONCE_OPTION = 'gb_deal_preview_option';
	private static $id;
	private static $hookname;

	public static function init() {
		if ( !is_admin() ) {
			add_action( 'init', array( get_class(), 'show_preview' ) );
		} else {
			add_action( 'add_meta_boxes', array( get_class(), 'add_meta_boxes' ) );
			add_action( 'save_post', array( get_class(), 'save_meta_boxes' ), 10, 2 );
		}
	}

	public static function add_meta_boxes() {
		add_meta_box( 'gb_deal_previews', self::__( 'Previews' ), array( get_class(), 'show_meta_box' ), Group_Buying_Deal::POST_TYPE, 'side', 'low' );
	}

	public static function show_meta_box( $post, $metabox ) {
		$deal = Group_Buying_Deal::get_instance( $post->ID );
		switch ( $metabox['id'] ) {
		case 'gb_deal_previews':
			self::show_meta_box_gb_deal_previews( $deal, $post, $metabox );
			break;
		default:
			self::unknown_meta_box( $metabox['id'] );
			break;
		}
	}

	private static function show_meta_box_gb_deal_previews( Group_Buying_Deal $deal, $post, $metabox ) {
		self::load_view( 'meta_boxes/deal-preview', array(
				'post' => $post,
				'deal_preview' => self::has_key( $deal ),
				'voucher_preview_url' => self::get_voucher_preview_link( $deal ),
				'deal_preview_url' => self::get_preview_link( $deal ),
			), FALSE );
	}

	public static function save_meta_boxes( $post_id, $post ) {
		// only continue if it's a deal post
		if ( $post->post_type != Group_Buying_Deal::POST_TYPE ) {
			return;
		}
		// don't do anything on autosave, auto-draft, bulk edit, or quick edit
		if ( wp_is_post_autosave( $post_id ) || $post->post_status == 'auto-draft' || defined( 'DOING_AJAX' ) || isset( $_GET['bulk_edit'] ) ) {
			return;
		}
		// Since the save_box_gb_deal_[meta] functions don't check if there's a _POST, a nonce was added to safe gaurd save_post actions from ... scheduled posts, etc.
		if ( !isset( $_POST['gb_deal_submission'] ) && ( empty( $_POST ) || !check_admin_referer( 'gb_save_metaboxes', 'gb_save_metaboxes_field' ) ) ) {
			return;
		}
		// save meta boxes
		$deal = Group_Buying_Deal::get_instance( $post_id );
		self::save_meta_box_gb_deal_preview( $deal, $post_id, $post );
	}

	public static function save_meta_box_gb_deal_preview( Group_Buying_Deal $deal, $post_id, $post ) {
		if ( isset( $_POST['deal_preview'] ) && 'TRUE' == $_POST['deal_preview'] ) {
			if ( !self::has_key( $deal ) ) {
				$deal->set_preview_key( self::create_key() );
			}
		} else {
			$deal->set_preview_key( null );
		}
		return;
	}

	public static function get_preview_link( Group_Buying_Deal $deal ) {
		$key = $deal->get_preview_key();
		return add_query_arg( array( 'p' => $deal->get_id(), 'post_type' => get_post_type( $deal->get_id() ), 'key' => $key, 'preview' => 'true' ), trailingslashit( get_option( 'home' ) ) );
	}

	public static function get_voucher_preview_link( Group_Buying_Deal $deal ) {
		$key = $deal->get_preview_key();
		return add_query_arg( array( 'deal_id' => $deal->get_id(), 'key' => $key, 'voucher_preview' => 'true' ), trailingslashit( get_option( 'home' ) ) );
	}

	public static function has_key( Group_Buying_Deal $deal ) {
		$private_key = $deal->get_preview_key();
		if ( $private_key != '' ) {
			return TRUE;
		}
		return;
	}

	public static function verify_key( $key = NULL, $deal_id ) {
		$deal = Group_Buying_Deal::get_instance( $deal_id );
		$private_key = $deal->get_preview_key();
		if ( $key == $private_key ) {
			return TRUE;
		}
		return;
	}

	/**
	 * Create Key
	 */
	public static function create_key() {
		return wp_generate_password( 18, FALSE );
	}

	/**
	 * Show the previews
	 */
	public static function show_preview() {
		if ( !is_admin() && isset( $_GET['preview'] ) && $_GET['preview'] && isset( $_GET['key'] ) ) {
			$deal_id = (int)$_GET['p'];
			if ( !self::verify_key( $_GET['key'], $deal_id ) ) {
				wp_die( self::__( 'Sorry but you do not have permission to preview this deal.' ) );
			}
			add_filter( 'posts_results', array( get_class(), 'fake_publish' ) );
		} elseif ( !is_admin() && isset( $_GET['voucher_preview'] ) && $_GET['voucher_preview'] && isset( $_GET['key'] ) ) {
			$deal_id = (int)$_GET['deal_id'];
			if ( !self::verify_key( $_GET['key'], $deal_id ) ) {
				wp_die( self::__( 'Sorry but you do not have permission to preview this voucher.' ) );
			}
			add_filter( 'template_redirect', array( get_class(), 'voucher_preview' ) );
		}
	}

	/**
	 * Fake the post being published so we don't have to do anything *too* hacky to get it to load the preview
	 */
	public static function fake_publish( $posts ) {
		$posts[0]->post_status = 'publish';
		return $posts;
	}

	// TODO move to voucher class
	public static function voucher_preview( $template ) {
		self::login_required();
		$deal_id = (int)$_GET['deal_id'];
		$deal = Group_Buying_Deal::get_instance( $deal_id );
		$template = self::locate_template( array(
				'account/voucher.php',
				'vouchers/single-voucher.php',
				'vouchers/voucher.php',
				'voucher.php',
			), $template );

		$content = '$id = '.$deal_id.'; ?>';
		$content .= file_get_contents( $template );
		// Title
		$content = str_replace( '<?php the_title(); ?>', '<?php echo get_the_title($id); ?>', $content );
		$content = str_replace( 'get_the_title()', 'get_the_title($id)', $content );
		// Logo
		$logo = $deal->get_voucher_logo();
		if ( !empty( $logo ) ) {
			$content = str_replace( 'gb_has_voucher_logo()', '__return_true()', $content );
			$content = str_replace( 'gb_voucher_logo_image();', '?><img src="'.$logo.'" /><?php', $content );
		} else {
			$content = str_replace( 'gb_has_voucher_logo()', '__return_false()', $content );
		}
		// Serial
		$serial = $deal->get_next_serial();
		if ( $serial == '' ) {
			$random = wp_generate_password( 12, FALSE, FALSE );
			$serial = implode( '-', str_split( $random, 4 ) );
		}
		$content = str_replace( '<?php gb_voucher_code(); ?>', $serial, $content );
		// QR Code
		$content = str_replace( '<?php echo urlencode( gb_get_voucher_claim_url( gb_get_voucher_security_code(), FALSE ) ) ?>', home_url(), $content );
		// Exp.
		$format = get_option( "date_format" );
		$expiration = ( $deal->get_voucher_expiration_date() ) ? $deal->get_voucher_expiration_date() : time()+60*60*24*14;
		$content = str_replace( '<?php gb_voucher_expiration_date(); ?>', date( $format, $expiration ), $content );
		// fine print.
		$content = str_replace( '<?php gb_voucher_fine_print() ?>', $deal->get_fine_print(), $content );
		// security code.
		$content = str_replace( '<?php gb_voucher_security_code(); ?>', '<?php echo '.$deal_id.' . "-" . strtoupper(wp_generate_password(5, FALSE, FALSE)); ?>', $content );
		// Locations
		$locals = $deal->get_voucher_locations();
		$locations = '';
		if ( !empty( $locals ) ) {
			$locations .= '<ul class="voucher_locations"><li>';
			$locations .= implode( '</li><li>', $locals );
			$locations .= '</li></ul>';
		}
		$content = str_replace( '<?php gb_voucher_locations() ?>', $locations, $content );
		// How to use.
		$content = str_replace( '<?php gb_voucher_usage_instructions() ?>', $deal->get_voucher_how_to_use(), $content );
		// Map
		$content = str_replace( '<?php gb_voucher_map() ?>', $deal->get_voucher_map(), $content );

		$content = apply_filters( 'gb_voucher_preview_content', $content, $deal_id );
		eval( $content );
		die();
	}

}

class Group_Buying_Deals_Upgrade extends Group_Buying {

	public static function upgrade_3_0() {
		global $wpdb;

		$old_posts = get_posts( array(
				'numberposts' => apply_filters( 'gb_migrate_deals_at_a_time', -1 ),
				'post_status' => 'any',
				'post_type' => 'deal'
			) );

		// Also upgrade trashed posts
		$old_trash_posts = get_posts( array(
				'numberposts' => apply_filters( 'gb_migrate_deals_at_a_time', -1 ),
				'post_status' => 'trash',
				'post_type' => 'deal'
			) );

		$old_posts = array_merge( $old_posts, $old_trash_posts );

		foreach ( $old_posts as $old_post ) {

			// 5 minutes for each deal, in case there are a lot of purchases
			set_time_limit( 5*60 );
			$post_id = $old_post->ID;

			// Pull this value out early so it isn't overwritten
			$voucher_logo = get_post_meta( $post_id, '_voucher_logo', true );

			printf( '<p style="margin-left: 20px">' . self::__( 'Updating Deal "%s"' ) . "</p>\n", $old_post->post_title );
			flush();

			wp_update_post( array(
					'ID' => $post_id,
					'post_type' => Group_Buying_Deal::POST_TYPE
				) );

			$deal = Group_Buying_Deal::get_instance( $post_id );

			// Update Meta Keys
			$amount_saved = get_post_meta( $post_id, '_dealSavings', true );
			$deal->set_amount_saved( $amount_saved );

			$base_price = get_post_meta( $post_id, '_dealCreditCost', true );
			$dynamic_price = get_post_meta( $post_id, '_dealDynCosts', true );
			$dynamic_price[0] = $base_price;
			$deal->set_prices( $dynamic_price );

			$expiration = get_post_meta( $post_id, '_dealExpiration', true );
			$expiration_status = get_post_meta( $post_id, '_meta_deal_complete_status', true );
			$expiration_disable = get_post_meta( $post_id, '_dealExpirationDisable', true );
			if ( 'disable' == $expiration_disable ) {
				$expiration = Group_Buying_Deal::NO_EXPIRATION_DATE;
			} elseif ( empty( $expiration ) ) {
				if ( empty( $expiration_status ) ) {
					$expiration = Group_Buying_Deal::NO_EXPIRATION_DATE;
				} else {
					$expiration = $expiration_status;
				}
			}
			$deal->set_expiration_date( $expiration );

			$fine_print = get_post_meta( $post_id, 'voucher_fine_print', true );
			$deal->set_fine_print( $fine_print );

			$highlights = get_post_meta( $post_id, 'dealHighlights', true );
			$deal->set_highlights( $highlights );

			$max_purchases = get_post_meta( $post_id, '_dealThresholdMax', true );
			if ( !$max_purchases ) {
				$max_purchases = Group_Buying_Deal::NO_MAXIMUM;
			}
			$deal->set_max_purchases( $max_purchases );

			$purchases_per_user = get_post_meta( $post_id, '_allowMultiplePurchases', true );
			$deal->set_max_purchases_per_user( $purchases_per_user );

			$min_purchases = get_post_meta( $post_id, '_dealThreshold', true );
			$deal->set_min_purchases( $min_purchases );

			$rss_excerpt = get_post_meta( $post_id, 'rss_excerpt', true );
			$deal->set_rss_excerpt( $rss_excerpt );

			$deal_value = get_post_meta( $post_id, '_dealWorth', true );
			$deal->set_value( $deal_value );

			$voucher_expiration = get_post_meta( $post_id, 'voucher_expiration', true );
			$deal->set_voucher_expiration_date( $voucher_expiration );

			$voucher_how_to_use = get_post_meta( $post_id, 'how_to_use', true );
			$deal->set_voucher_how_to_use( $voucher_how_to_use );

			$voucher_prefix = get_post_meta( $post_id, '_voucher_prefix', true );
			$deal->set_voucher_id_prefix( $voucher_prefix );

			$voucher_locations = array();
			$voucher_locations[] = get_post_meta( $post_id, 'deal_address_1', true );
			$voucher_locations[] = get_post_meta( $post_id, 'deal_address_2', true );
			$voucher_locations[] = get_post_meta( $post_id, 'deal_address_3', true );
			$voucher_locations[] = get_post_meta( $post_id, 'deal_address_4', true );
			$voucher_locations[] = get_post_meta( $post_id, 'deal_address_5', true );
			$deal->set_voucher_locations( $voucher_locations );

			$deal->set_voucher_logo( $voucher_logo );

			$voucher_map = get_post_meta( $post_id, 'google_maps_iframe', true );
			$deal->set_voucher_map( $voucher_map );

			$voucher_serials = get_post_meta( $post_id, '_voucher_serials', true );
			if ( is_array( $voucher_serials ) ) {
				$deal->set_voucher_serial_numbers( $voucher_serials );
			}

			// Update deal purchases
			$purchases = get_post_meta( $post_id, '_purchaseRecords' );

			if ( count( $purchases ) ) {
				// Set import version
				update_post_meta( $post_id, '_import_version', '2.3' );
			}

			if ( !empty( $purchases ) ) {

				printf( '<p style="margin-left: 20px">' . self::__( 'Updating %d Voucher(s) and Purchase(s) for Deal "%s"' ) . "</p>\n", count( $purchases ), $old_post->post_title );
				flush();

				// Allow one second per purchase, to avoid execution issues
				set_time_limit( 300 + count( $purchases ) );

				foreach ( $purchases as $old_purchase ) {
					$old_purchase = (object) $old_purchase;
					$user_id = $old_purchase->userID;
					$account_id = Group_Buying_Account::get_account_id_for_user( $user_id );
					$voucher_code = $old_purchase->coupon_code;
					$transaction_id = $old_purchase->transID;
					$security_code = $old_purchase->security_code;
					$purchase_date = date( 'Y-m-d H:i:s', $old_purchase->time );
					$item_value = $old_purchase->item_value;

					$purchase_id = wp_insert_post( array(
							'post_title' => sprintf( self::__( 'Order #%d' ), $transaction_id ),
							'post_status' => 'publish',
							'post_type' => Group_Buying_Purchase::POST_TYPE,
							'post_date' => $purchase_date
						) );
					$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
					$purchase->set_title( sprintf( self::__( 'Order #%d' ), $purchase_id ) );
					$purchase->set_user( $user_id );
					$purchase->set_original_user( $user_id );
					$purchase->set_total( $item_value );
					$purchase->set_products( array( array(
								'deal_id' => $post_id,
								'quantity' => 1,
								'unit_price' => $item_value,
								'price' => $item_value
							) ) );

					$voucher_id = Group_Buying_Voucher::new_voucher( $purchase_id, $deal->get_id() );
					wp_publish_post( $voucher_id );

					$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
					$voucher->set_serial_number( $voucher_code );
					$voucher->set_security_code( $security_code );
					$voucher->set_purchase( $purchase_id );
					$voucher->set_deal( $post_id );
					// Set import version
					update_post_meta( $voucher_id, '_import_version', '2.3' );
					$voucher->activate();
				}
			}

			$codes = get_post_meta( $post_id, '_dealsCodesArray', true );

			if ( count( $codes ) ) {
				// Set import version
				update_post_meta( $post_id, '_import_version', '<= 2.1' );
			}

			if ( !empty( $codes ) ) {

				printf( '<p style="margin-left: 20px">' . self::__( 'Updating %d Voucher(s) and Purchase(s) for Deal "%s"' ) . "</p>\n", count( $codes ), $old_post->post_title );
				flush();

				// Allow one second per purchase, to avoid execution issues
				set_time_limit( 300 + count( $codes ) );

				foreach ( $codes as $user_id => $code ) {
					$account_id = Group_Buying_Account::get_account_id_for_user( $user_id );
					$voucher_code = $code;
					$transaction_id = get_user_meta( $user_id, '_' . $deal->get_id() . '_transaction_id', true );

					$purchase_id = wp_insert_post( array(
							'post_title' => sprintf( self::__( 'Order #%d' ), $transaction_id ),
							'post_status' => 'publish',
							'post_type' => Group_Buying_Purchase::POST_TYPE
						) );
					$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
					$purchase->set_title( sprintf( self::__( 'Order #%d' ), $purchase_id ) );
					$purchase->set_user( $user_id );
					$purchase->set_original_user( $user_id );
					$purchase->set_total( $deal->get_price( 0 ) );
					$purchase->set_products( array( array(
								'deal_id' => $post_id,
								'quantity' => 1,
								'unit_price' => $deal->get_price( 0 ),
								'price' => $deal->get_price( 0 )
							) ) );

					$voucher_id = Group_Buying_Voucher::new_voucher( $purchase_id, $deal->get_id() );
					wp_publish_post( $voucher_id );

					$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
					$voucher->set_serial_number( $voucher_code );
					$voucher->set_purchase( $purchase_id );
					$voucher->set_deal( $post_id );

					// Set import version
					update_post_meta( $voucher_id, '_import_version', '<= 2.1' );
				}
			}
		}
	}
}
