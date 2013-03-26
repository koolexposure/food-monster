<?php

class Group_Buying_Fulfillment extends Group_Buying_Controller {
	const FULFILLMENT_STATUS_META_KEY = '_purchase_fulfillment_status';
	const NOTIFICATION_STATUS_META_KEY = '_deal_send_inventory_notification';
	const NOTIFICATION_LEVEL_META_KEY = '_deal_inventory_notification_level';
	const NOTIFICATION_RECIPIENT_META_KEY = '_deal_inventory_notification_recipient';
	const NOTIFICATION_SENT_META_KEY = '_deal_inventory_notification_sent';

	public static function init() {
		if ( is_admin() ) {
			add_action( 'wp_ajax_gbs_fulfillment_status', array( __CLASS__, 'handle_ajax_update_request' ), 10, 0 );
			add_action( 'load-group-buying_page_group-buying/purchase_records', array( __CLASS__, 'add_admin_page_hooks' ), 0, 0 );
			add_action( 'gb_meta_box_deal_limits', array( __CLASS__, 'display_notification_settings_field' ), 10, 0 );
			add_action( 'save_gb_meta_box_deal_limits', array( __CLASS__, 'save_notification_settings' ), 10, 3 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 10, 0 );
		}

		add_filter( 'gb_notification_types', array( __CLASS__, 'register_notification_type' ), 10, 1 );
		add_filter( 'gb_notification_shortcodes', array( __CLASS__, 'register_notification_shortcode' ), 10, 1 );
		add_action( 'purchase_completed', array( __CLASS__, 'maybe_send_inventory_notifications' ), 10, 1 );
		add_action( 'gb_fulfullment_status_updated', array( __CLASS__, 'send_order_status_notification' ), 10, 2 );
	}

	public static function add_admin_page_hooks() {
		add_filter( 'gb_mngt_purchases_columns', array( __CLASS__, 'register_list_table_column' ), 10, 1 );
		add_action( 'gb_mngt_purchases_column_fulfillment', array( __CLASS__, 'display_list_table_column' ), 10, 1 );
		add_action( 'gb_mngt_purchases_extra_tablenav', array( __CLASS__, 'display_list_table_filters' ), 10, 0 );
		add_filter( 'pre_get_posts', array( __CLASS__, 'filter_list_table_query' ), 10, 2 );
	}

	/**
	 * Register our column to display in the orders list table
	 *
	 * @param array $columns
	 * @return array
	 */
	public static function register_list_table_column( $columns ) {
		$columns['fulfillment'] = self::__('Fulfillment');
		return $columns;
	}

	/**
	 * @param object $item The purchase post object for the list table row
	 * @return string
	 */
	public static function display_list_table_column( $item ) {
		$status = self::get_status($item->ID);
		$options = array();
		foreach ( self::fulfillment_statuses() as $key => $label ) {
			$options[] = sprintf('<option value="%s" %s>%s</option>', esc_attr($key), selected($key, $status, FALSE), esc_html($label));
		}
		// no name, because we don't need all these submitted when the list table is filtered/paginated
		$form = sprintf('<select class="fulfillment-status">%s</select>', implode('', $options));
		$form .= sprintf('<input type="hidden" class="fulfillment-status-purchase-id" value="%d" />', $item->ID);
		return $form;
	}

	public static function display_list_table_filters() {
		$statuses = self::fulfillment_statuses();
		$selected = empty($_REQUEST['fulfillment-status'])?array():$_REQUEST['fulfillment-status'];
		$options = array();
		$option = '<option value="%s" %s>%s</option>';
		$options[] = sprintf($option, '', '', self::__('Show all fulfillment statuses'));
		foreach ( $statuses as $key => $label ) {
			$options[] = sprintf( $option, $key, selected($key, $selected, FALSE), esc_html($label));
		}
		printf(' <select name="%s">%s</select>', 'fulfillment-status', implode("\n", $options));
	}

	/**
	 * Filter the query for the purchases list table
	 * @param WP_Query $query
	 */
	public static function filter_list_table_query( $query ) {
		if ( !empty($_REQUEST['fulfillment-status']) ) {
			if ( $_REQUEST['fulfillment-status'] == 'pending' ) {
				// pending may also be NULL
				$query->query_vars['meta_query']['relation'] = 'OR';
				$query->query_vars['meta_query'][] = array(
					'key' => self::FULFILLMENT_STATUS_META_KEY,
					'value' => $_REQUEST['fulfillment-status'],
				);
				// this only works with WP 3.5+
				$query->query_vars['meta_query'][] = array(
					'key' => self::FULFILLMENT_STATUS_META_KEY,
					'value' => $_REQUEST['fulfillment-status'],
					'compare' => 'NOT EXISTS',
				);
			} else {
				$query->query_vars['meta_query'][] = array(
					'key' => self::FULFILLMENT_STATUS_META_KEY,
					'value' => $_REQUEST['fulfillment-status'],
				);

			}
		}
	}

	public static function filter_null_status( $where, $query ) {
		global $wpdb;
		$matches = array();
		preg_match( "#(\w+)\.meta_key = '".self::FULFILLMENT_STATUS_META_KEY."'#", $where, $matches );
		if ( $matches ) {
			$where = str_replace("'pending'", "'pending' OR ".$matches[1].".meta_value IS NULL", $where);
		}
		return $where;
	}

	/**
	 * Get the fulfillment status for the given purchase
	 *
	 * @param int $purchase_id
	 * @return string
	 */
	public static function get_status( $purchase_id ) {
		$status = get_post_meta($purchase_id, self::FULFILLMENT_STATUS_META_KEY, TRUE);
		if ( !$status ) {
			$status = apply_filters('gbs_default_fulfillment_status', 'pending', $purchase_id);
		}
		return $status;
	}

	/**
	 * Get a list of possible fulfillment statuses
	 * @return array
	 */
	public static function fulfillment_statuses() {
		static $statuses = NULL;
		if ( !isset($statuses) ) {
			$statuses = array(
				'pending' => self::__('Pending'),
				'approved' => self::__('Approved'),
				'cancelled' => self::__('Canceled'),
				'processing' => self::__('Processing'),
				'dispatched' => self::__('Dispatched'),
				'complete' => self::__('Complete'),
			);
			$statuses = (array)apply_filters('gbs_fulfillment_statuses', $statuses);
		}
		return $statuses;
	}

	public static function handle_ajax_update_request() {
		header('Content-Type: application/json');
		$purchase_id = empty($_POST['purchase_id'])?0:$_POST['purchase_id'];
		$status = empty($_POST['status'])?0:$_POST['status'];
		$response = array(
			'purchase_id' => $purchase_id,
			'status' => $status,
		);
		if ( !$purchase_id || !$status ) {
			status_header(400);
			echo json_encode($response);
			exit();
		}

		update_post_meta($purchase_id, self::FULFILLMENT_STATUS_META_KEY, $status);
		do_action( 'gb_fulfullment_status_updated', $purchase_id, $status );
		echo json_encode($response);
		exit();
	}

	/**
	 * Register the inventory notification
	 * Register the order status change notification
	 * @param array $notifications
	 *
	 * @return array
	 */
	public function register_notification_type( $notifications ) {
		$notifications['inventory_notification'] = array(
			'name' => self::__( 'Low Inventory Notification' ),
			'description' => self::__( "Customize the notification email that is sent when a deal's inventory reaches the notification threshold." ),
			'shortcodes' => array( 'date', 'site_title', 'site_url', 'deal_url', 'deal_title' ),
			'default_title' => self::__( 'Low Inventory Notification' ),
			'default_content' => self::load_view_to_string( 'notifications/low-inventory', NULL )
		);
		$notifications['order_status_updated'] = array(
			'name' => self::__( 'Order Status Updated' ),
			'description' => self::__( "Customize the notification email that is sent to s customer when the order status has been updated." ),
			'shortcodes' => array( 'date', 'name', 'username', 'purchase_details', 'transid', 'site_title', 'site_url', 'total_paid', 'credits_used', 'rewards_used', 'total', 'billing_address', 'shipping_address', 'order_status' ),
			'default_title' => self::__( 'Order Status Updated' ),
			'default_content' => self::load_view_to_string( 'notifications/order-status-updated', NULL ),
			'default_disabled' => TRUE
		);
		return $notifications;
	}

	public function register_notification_shortcode( $default_shortcodes ) {
		$default_shortcodes['order_status'] = array(
			'description' => self::__( 'Used to display the order status.' ),
			'callback' => array( get_class(), 'order_status_shortcode' )
		);
		return $default_shortcodes;
	}

	public static function order_status_shortcode( $atts, $content, $code, $data ) {
		$purchase = $data['purchase'];
		return self::get_status( $purchase->get_id() );
	}

	/**
	 * @param Group_Buying_Purchase $purchase
	 */
	public static function maybe_send_inventory_notifications( $purchase ) {
		$deals = $purchase->get_products();
		foreach ( $deals as $item ) {
			$deal_id = $item['deal_id'];
			if ( !get_post_meta($deal_id, self::NOTIFICATION_STATUS_META_KEY, TRUE) ) {
				continue; // don't send notifications for this deal
			}
			if ( get_post_meta($deal_id, self::NOTIFICATION_SENT_META_KEY, TRUE) ) {
				continue; // we already sent it
			}
			$threshold = get_post_meta($deal_id, self::NOTIFICATION_LEVEL_META_KEY, TRUE);
			$deal = Group_Buying_Deal::get_instance($deal_id);
			$left = $deal->get_remaining_allowed_purchases();
			if ( $left == Group_Buying_Deal::NO_MAXIMUM ) {
				continue; // no limit for this deal
			}
			if ( $left > $threshold ) {
				continue; // not time yet
			}
			self::send_inventory_notification($deal_id);
		}
	}

	/**
	 * @param int $deal_id
	 */
	public static function send_order_status_notification( $purchase_id, $status = 0 ) {
		if ( $status == get_post_meta($purchase_id, self::NOTIFICATION_SENT_META_KEY, TRUE) ) {
			return; // we already sent it
		}
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		$user_id = $purchase->get_user();
		if ( $user_id == -1 ) { // purchase will be set to -1 if it's a gift.
			$user_id = $purchase->get_original_user();
		}
		$to = Group_Buying_Notifications::get_user_email( $user_id );
		$data = array(
			'user_id' => $user_id,
			'purchase' => $purchase
		);
		Group_Buying_Notifications::send_notification('order_status_updated', $data, $to);
		update_post_meta($purchase_id, self::NOTIFICATION_SENT_META_KEY, $status);
	}

	/**
	 * @param int $deal_id
	 */
	public static function send_inventory_notification( $deal_id ) {
		if ( !get_post_meta($deal_id, self::NOTIFICATION_STATUS_META_KEY, TRUE) ) {
			return;
		}
		$data = array(
			'deal' => Group_Buying_Deal::get_instance($deal_id),
		);
		$to = get_post_meta($deal_id, self::NOTIFICATION_RECIPIENT_META_KEY, TRUE);
		if ( empty($to) ) {
			$to = get_option('admin_email');
		}
		Group_Buying_Notifications::send_notification('inventory_notification', $data, $to);
		update_post_meta($deal_id, self::NOTIFICATION_SENT_META_KEY, time());
	}

	/**
	 * Display the notification settings fields in the Purchase
	 * Limits meta box
	 */
	public static function display_notification_settings_field() {
		global $post;
		$notify = get_post_meta($post->ID, self::NOTIFICATION_STATUS_META_KEY, TRUE);
		$recipient = get_post_meta($post->ID, self::NOTIFICATION_RECIPIENT_META_KEY, TRUE);
		$level = (int)get_post_meta($post->ID, self::NOTIFICATION_LEVEL_META_KEY, TRUE);
		self::load_view( 'meta_boxes/inventory-notifications', array(
			'notify' => $notify,
			'recipient' => $recipient,
			'level' => $level,
		));
	}

	/**
	 * Save the notification settings when the deal is saved
	 *
	 * @param Group_Buying_Deal $deal
	 * @param int $post_id
	 * @param object $post
	 */
	public static function save_notification_settings( Group_Buying_Deal $deal, $post_id, $post ) {
		if ( empty($_POST[self::NOTIFICATION_STATUS_META_KEY]) ) {
			delete_post_meta($post_id, self::NOTIFICATION_STATUS_META_KEY);
			delete_post_meta($post_id, self::NOTIFICATION_LEVEL_META_KEY);
			delete_post_meta($post_id, self::NOTIFICATION_RECIPIENT_META_KEY);
			return;
		}
		update_post_meta($post_id, self::NOTIFICATION_STATUS_META_KEY, 1);
		update_post_meta($post_id, self::NOTIFICATION_LEVEL_META_KEY, (int)$_POST[self::NOTIFICATION_LEVEL_META_KEY]);
		$recipient = $_POST[self::NOTIFICATION_RECIPIENT_META_KEY];
		if ( $recipient && !is_email($recipient) ) {
			$recipient = '';
		}
		update_post_meta($post_id, self::NOTIFICATION_RECIPIENT_META_KEY, $recipient);
	}

	public static function enqueue_scripts() {
		static $done = FALSE;
		if ( $done ) {
			return;
		}
		$done = TRUE;
		wp_enqueue_script('gbs-fulfillment', GB_URL.'/resources/js/fulfillment.admin.gbs.js', array('jquery'), Group_Buying::GB_VERSION, TRUE);
	}
}
