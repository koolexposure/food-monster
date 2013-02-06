<?php

/**
 * Purchase Controller
 *
 * @package GBS
 * @subpackage Purchase
 */
class Group_Buying_Purchases extends Group_Buying_Controller {
	const ORDER_LU_OPTION = 'gb_order_lookup_path';
	const AUTH_FORM_INPUT = 'order_billing_city';
	const AUTH_FORM_ID_INPUT = 'order_id';
	const NONCE_ID = 'gb_order_lookup_nonce';
	private static $lookup_path = 'order-lookup';

	public static function init() {
		self::$lookup_path = get_option( self::ORDER_LU_OPTION, self::$lookup_path );

		// Wrapper Template
		add_filter( 'template_include', array( get_class(), 'override_template' ) );
		// Modify Content for purchase template
		add_action( 'the_post', array( get_class(), 'purchase_content' ), 10, 1 );
		add_filter( 'the_title', array( get_class(), 'get_title' ), 10, 2 );

		// Order Lookup
		add_action( 'gb_router_generate_routes', array( get_class(), 'register_path_callback' ), 10, 1 );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 50, 1 );

		// Admin
		self::$settings_page = self::register_settings_page( 'purchase_records', self::__( 'Orders' ), self::__( 'Orders' ), 8.1, FALSE, 'records', array( get_class(), 'display_table' ) );
	}

	/**
	 * Override template for the purchase post type
	 * @param  string $template 
	 * @return            
	 */
	public static function override_template( $template ) {
		$post_type = get_query_var( 'post_type' );
		if ( $post_type == Group_Buying_Purchase::POST_TYPE ) {
			if ( is_single() ) {
				$template = self::locate_template( array(
						'account/single-purchase.php',
						'account/single-order.php',
						'order/single.php',
						'orders/single.php',
						'purchase/single.php',
						'purchases/single.php',
						'order.php',
						'purchase.php',
						'account.php'
					), $template );
			}
		}
		return $template;
	}

	/**
	 * Update the global $pages array with the HTML for the current checkout page
	 *
	 * @static
	 * @param object  $post
	 * @return void
	 */
	public function purchase_content( $post ) {
		if ( $post->post_type == Group_Buying_Purchase::POST_TYPE && is_single() ) {
			$purchase_id = $post->ID;
			$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
			// Remove content filter
			remove_filter( 'the_content', 'wpautop' );
			
			if ( self::authorized_user( $purchase_id ) ) {
				$args = array(
					'order_number' => $purchase_id,
					'tax' => $purchase->get_tax_total(),
					'shipping' => $purchase->get_shipping_total(),
					'total' => $purchase->get_total(),
					'products' => $purchase->get_products()
				);
				$view = self::load_view_to_string( 'purchase/order', $args );
			}
			else { // show the authentication form
				$view = self::lookup_page(TRUE);
			}
			// Display content
			global $pages;
			$pages = array( $view );
		}
	}

	/**
	 * Filter 'the_title' to display the title of the current page, purchase or lookup
	 *
	 * @static
	 * @param string  $title
	 * @param int     $post_id
	 * @return string
	 */
	public function get_title( $title, $post_id ) {
		if ( Group_Buying_Purchase::POST_TYPE == get_post_type( $post_id ) ) {
			if ( is_single() ) {
				$filtered = self::__( 'Order Lookup' );
				if ( self::authorized_user( $post_id ) ) {
					$filtered .= ': '.str_replace( 'Order ', '', $title);
				}
				return $filtered;
			}
		}
		return $title;
	}

	/**
	 * Lookup view
	 * @return  
	 */
	public function lookup_page( $return = FALSE ) {
		$args = array( 'action' => self::get_url(), 'nonce_id' => self::NONCE_ID, 'city_option_name' => self::AUTH_FORM_INPUT, 'order_option_name' => self::AUTH_FORM_ID_INPUT );
		remove_filter( 'the_content', 'wpautop' );
		if ( !$return ) {
			self::load_view( 'purchase/order-lookup', $args );
		} else {
			return self::load_view_to_string( 'purchase/order-lookup', $args );
		}
	}

	/**
	 * Check to see if the user has access to view the purchase content.
	 * @param  int  $purchase_id 
	 * @param  int $user_id     
	 * @return bool|string              
	 */
	public static function authorized_user( $purchase_id, $user_id = 0 ) {
		$return = FALSE;
		if ( !$user_id ) {
			$user_id = get_current_user_id();
		}
		if ( Group_Buying_Purchase::POST_TYPE != get_post_type( $purchase_id ) ) {
			return FALSE;
		}
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		$purchase_user_id = $purchase->get_user();
		// If logged in or manually checked
		if ( $user_id ) {
			// Purchaser or admin
			if ( ( $user_id == $purchase_user_id ) || current_user_can( 'manage_options' ) ) {
				$return = TRUE;
			}
		}
		// Submitted form
		if ( // handle both submissions and $_GET variables from a redirect
			( isset( $_POST['gb_order_lookup_'.self::AUTH_FORM_INPUT] ) && $_POST['gb_order_lookup_'.self::AUTH_FORM_INPUT] != '' ) ||
			( isset( $_REQUEST[self::AUTH_FORM_INPUT] ) && $_REQUEST[self::AUTH_FORM_INPUT] != '' )
			) {
			// submitted form and has a matching billing city
			$account = Group_Buying_Account::get_instance( $purchase_user_id );
			$address = $account->get_address();
			$query = ( isset( $_REQUEST[self::AUTH_FORM_INPUT] ) ) ? $_REQUEST[self::AUTH_FORM_INPUT] : $_POST['gb_order_lookup_'.self::AUTH_FORM_INPUT] ;
			if ( strtolower( $address['city'] ) == strtolower( $query ) ) {
				$return = strtolower( $address['city'] );
			}
		}
		return apply_filters( 'gb_purchase_view_authorized_user', $return, $purchase_id, $user_id );
	}

	/**
	 * Register the path callback for the order lookup page
	 *
	 * @static
	 * @param GB_Router $router
	 * @return void
	 */
	public static function register_path_callback( GB_Router $router ) {
		$path = str_replace( '/', '-', self::$lookup_path );
		$args = array(
			'path' => self::$lookup_path,
			'title' => self::__( 'Order Lookup' ),
			'page_callback' => array( get_class(), 'lookup_page' ),
			'access_callback' => array( get_class(), 'process_form' ),
			'template' => array(
				self::get_template_path().'/'.str_replace( '/', '-', self::$lookup_path ).'.php',
				self::get_template_path().'/order.php', // theme override
				GB_PATH.'/views/public/order.php', // default
			),
		);
		$router->add_route( 'gb_show_order_lookup', $args );
	}

	public function process_form() {
		$message = FALSE;
		if ( isset( $_POST['gb_order_lookup_'.self::AUTH_FORM_ID_INPUT] ) && $_POST['gb_order_lookup_'.self::AUTH_FORM_ID_INPUT] ) {
			if ( !wp_verify_nonce( $_POST['_wpnonce'], self::NONCE_ID ) ) {
				$message = self::__( 'Invalid Submission Attempt' );
			}
			else {
				$purchase_id = $_POST['gb_order_lookup_'.self::AUTH_FORM_ID_INPUT];
				if ( Group_Buying_Purchase::POST_TYPE == get_post_type( $purchase_id ) ) {
					if ( $billing_auth = self::authorized_user( $purchase_id ) ) {
						$url = add_query_arg( array( self::AUTH_FORM_INPUT => $billing_auth ), get_permalink( $purchase_id ) );
						wp_redirect( $url );
						exit();
					} else {
						$message = self::__('Invalid Billing City');	
					}
				} else {
					$message = self::__('Order ID not found');
				}
			}
		}
		if ( $message ) {
			self::set_message( $message );
		}
		return TRUE;
	}

	/**
	 *
	 *
	 * @static
	 * @return string The URL to the cart page
	 */
	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$lookup_path );
		} else {
			$router = GB_Router::get_instance();
			return $router->get_url( 'gb_show_order_lookup' );
		}
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_cart_paths';

		// Settings
		register_setting( $page, self::ORDER_LU_OPTION );
		add_settings_field( self::ORDER_LU_OPTION, self::__( 'Order Lookup Path' ), array( get_class(), 'display_path_option' ), $page, $section );
	}

	public static function display_path_option() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::ORDER_LU_OPTION . '" id="' . self::ORDER_LU_OPTION . '" value="' . esc_attr( self::$lookup_path ) . '" size="40"/><br />';
	}


	public static function display_table() {
		//Create an instance of our package class...
		$wp_list_table = new Group_Buying_Purchases_Table();
		//Fetch, prepare, sort, and filter our data...
		$wp_list_table->prepare_items();

?>
		<script type="text/javascript" charset="utf-8">
			jQuery(document).ready(function($){
				jQuery(".gb_destroy").on('click', function(event) {
					event.preventDefault();
						if( confirm( '<?php gb_e("This will permanently trash the purchase and its associated voucher(s) and payment(s) which cannot be reversed (without manually adjusting them in the DB). This will not reverse any payments or provide a credit to the customer, that must be done manually. Are you sure?") ?>' ) ) {
							var $destroy_link = $( this ),
							destroy_purchase_id = $destroy_link.attr( 'ref' );
							$.post( ajaxurl, { action: 'gbs_destroyer', type: 'purchase', id: destroy_purchase_id, destroyer_nonce: '<?php echo wp_create_nonce( Group_Buying_Destroy::NONCE ) ?>' },
								function( data ) {
										$destroy_link.parent().parent().parent().parent().fadeOut();
									}
								);
						} else {
							// nothing to do.
						}
				});
			});
		</script>
		<style type="text/css">
			#payment_deal_id-search-input, #purchase_id-search-input, #payment_account_id-search-input { width:5em; margin-left: 10px;}
		</style>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2 class="nav-tab-wrapper">
				<?php self::display_admin_tabs(); ?>
			</h2>

			 <?php $wp_list_table->views() ?>
			<form id="payments-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $wp_list_table->search_box( self::__( 'Order ID' ), 'purchase_id' ); ?>
				<p class="search-box deal_search">

					<label class="screen-reader-text" for="payment_deal_id-search-input"><?php self::_e('Deal ID:') ?></label>
					<input type="text" id="payment_deal_id-search-input" name="deal_id" value="">
					<input type="submit" name="" id="search-submit" class="button" value="<?php self::_e('Deal ID') ?>">
				</p>
				<p class="search-box account_search">
					<label class="screen-reader-text" for="payment_account_id-search-input"><?php self::_e('Account ID:') ?></label>
					<input type="text" id="payment_account_id-search-input" name="account_id" value="">
					<input type="submit" name="" id="search-submit" class="button" value="<?php self::_e('Account ID') ?>">
				</p>
				<?php $wp_list_table->display() ?>
			</form>
		</div>
		<?php
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

	private function __construct() {}
}


if ( !class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class Group_Buying_Purchases_Table extends WP_List_Table {
	protected static $post_type = Group_Buying_Purchase::POST_TYPE;

	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
				'singular' => 'order',     // singular name of the listed records
				'plural' => 'orders', // plural name of the listed records
				'ajax' => false     // does this table support ajax?
			) );

	}

	function get_views() {

		$status_links = array();
		$num_posts = wp_count_posts( self::$post_type, 'readable' );
		$class = '';
		$allposts = '';

		$total_posts = array_sum( (array) $num_posts );

		// Subtract post types that are not included in the admin all list.
		foreach ( get_post_stati( array( 'show_in_admin_all_list' => false ) ) as $state )
			$total_posts -= $num_posts->$state;

		$class = empty( $_REQUEST['post_status'] ) ? ' class="current"' : '';
		$status_links['all'] = "<a href='admin.php?page=group-buying/purchase_records{$allposts}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' ) as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( empty( $num_posts->$status_name ) )
				continue;

			if ( isset( $_REQUEST['post_status'] ) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			// replace "Published" with "Complete".
			$label = str_replace( 'Published', 'Complete', translate_nooped_plural( $status->label_count, $num_posts->$status_name ) );
			$status_links[$status_name] = "<a href='admin.php?page=group-buying/purchase_records&post_status=$status_name'$class>" . sprintf( $label, number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return $status_links;
	}

	function extra_tablenav( $which ) {
?>
		<div class="alignleft actions">
<?php
		if ( 'top' == $which && !is_singular() ) {

			$this->months_dropdown( self::$post_type );

			do_action( 'gb_mngt_purchases_extra_tablenav' );

			submit_button( __( 'Filter' ), 'secondary', false, false, array( 'id' => 'post-query-submit' ) );
		}
?>
		</div>
<?php
	}


	/**
	 *
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array   $item        A singular item (one full row's worth of data)
	 * @param array   $column_name The name/slug of the column to be processed
	 * @return string Text or HTML to be placed inside the column <td>
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
		default:
			return apply_filters( 'gb_mngt_purchases_column_'.$column_name, $item ); // do action for those columns that are filtered in
		}
	}


	/**
	 *
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @param array   $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 */
	function column_title( $item ) {
		$purchase = Group_Buying_Purchase::get_instance( $item->ID );
		$user_id = $purchase->get_original_user();
		$account_id = Group_Buying_Account::get_account_id_for_user( $user_id );

		//Build row actions
		$actions = array(
			'payment'    => sprintf( '<a href="admin.php?page=group-buying/payment_records&purchase_id=%s">Payments</a>', $item->ID ),
			'purchaser'    => sprintf( '<a href="post.php?post=%s&action=edit">'.gb__( 'Purchaser' ).'</a>', $account_id ),
		);

		//Return the title contents
		return sprintf( '%1$s <span style="color:silver">(order&nbsp;id:%2$s)</span>%3$s',
			$item->post_title,
			$item->ID,
			$this->row_actions( $actions )
		);
	}

	function column_total( $item ) {
		$purchase = Group_Buying_Purchase::get_instance( $item->ID );
		gb_formatted_money( $purchase->get_total() );
	}

	function column_deals( $item ) {
		$purchase = Group_Buying_Purchase::get_instance( $item->ID );
		$products = $purchase->get_products();

		$i = 0;
		foreach ( $products as $product => $item ) {
			$i++;
			// Display deal name and link
			echo '<p><strong><a href="'.get_edit_post_link( $item['deal_id'] ).'">'.get_the_title( $item['deal_id'] ).'</a></strong>&nbsp;<span style="color:silver">(id:'.$item['deal_id'].')</span>';
			// Build details
			$details = array(
				'Quantity' => $item['quantity'],
				'Unit Price' => gb_get_formatted_money( $item['unit_price'] ),
				'Total' => gb_get_formatted_money( $item['price'] )
			);
			// Filter to add attributes, etc.
			$details = apply_filters( 'gb_purchase_deal_column_details', $details, $item, $products );
			// display details
			foreach ( $details as $label => $value ) {
				echo '<br />&nbsp;&nbsp;'.$label.': '.$value;
			}
			// Build Payment methods
			$payment_methods = array();
			foreach ( $item['payment_method'] as $method => $payment ) {
				$payment_methods[] .= $method.' &mdash; '.gb_get_formatted_money( $payment );
			}
			echo '</p>';
			if ( count( $products ) > $i ) {
				echo '<span class="meta_box_block_divider"></span>';
			}
		}

	}

	function column_payments( $item ) {
		$payments = Group_Buying_Payment::get_payments_for_purchase( $item->ID );
		foreach ( $payments as $payment_id ) {
			$payment = Group_Buying_Payment::get_instance( $payment_id );
			$method = $payment->get_payment_method();
			//Return the title contents
			return sprintf( '<a href="admin.php?page=group-buying/payment_records&s=%2$s">%1$s</a> <span style="color:silver">(payment&nbsp;id:%2$s)</span>',
				$method,
				$payment_id
			);
		}
	}

	function column_status( $item ) {
		$purchase_id = $item->ID;
		$purchase = Group_Buying_Purchase::get_instance( $item->ID );

		$actions = array(
			'trash'    => '<span id="'.$purchase_id.'_destroy_result"></span><a href="javascript:void(0)" class="gb_destroy" id="'.$purchase_id.'_destroy" ref="'.$purchase_id.'">'.gb__('Delete Purchase').'</a>',
		);

		$status = ucfirst( str_replace( 'publish', 'complete', $item->post_status ) );
		$status .= '<br/><span style="color:silver">';
		$status .= mysql2date( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), $item->post_date );
		$status .= '</span>';
		$status .= $this->row_actions( $actions );
		return $status;
	}


	/**
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value
	 * is the column's title text.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 * */
	function get_columns() {
		$columns = array(
			'status'  => gb__('Status'),
			'title'  => gb__('Order'),
			'total'  => gb__('Total'),
			'deals'  => gb__('Deals'),
			'payments'  => gb__('Payments')
		);
		return apply_filters( 'gb_mngt_purchases_columns', $columns );
	}

	/**
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 * */
	function get_sortable_columns() {
		$sortable_columns = array(
			'title'  => array( 'title', true ),     // true means its already sorted
			'total'    => array( 'total', false ),
			'status'  => array( 'status', false )
		);
		return apply_filters( 'gb_mngt_purchases_sortable_columns', $sortable_columns );
	}


	/**
	 * Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 * */
	function get_bulk_actions() {
		$actions = array();
		return apply_filters( 'gb_mngt_purchases_bulk_actions', $actions );
	}


	/**
	 * Prep data.
	 *
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 * */
	function prepare_items() {

		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 25;


		/**
		 * Define our column headers.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();


		/**
		 * REQUIRED. Build an array to be used by the class for column
		 * headers.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$filter = ( isset( $_REQUEST['post_status'] ) ) ? $_REQUEST['post_status'] : 'all';
		$args=array(
			'post_type' => Group_Buying_Purchase::POST_TYPE,
			'post_status' => $filter,
			'posts_per_page' => $per_page,
			'paged' => $this->get_pagenum()
		);
		// Check purchases based on Deal ID
		if ( isset( $_GET['deal_id'] ) && $_GET['deal_id'] != '' ) {

			if ( Group_Buying_Deal::POST_TYPE != get_post_type( $_GET['deal_id'] ) )
				return; // not a valid search

			$purchase_ids = Group_Buying_Purchase::get_purchases( array( 'deal' => $_GET['deal_id'] ) );

			$posts_in = array(
				'post__in' => $purchase_ids
				);
			$args = array_merge( $args, $posts_in );
		}
		// Check payments based on Account ID
		if ( isset( $_GET['account_id'] ) && $_GET['account_id'] != '' ) {

			if ( Group_Buying_Account::POST_TYPE != get_post_type( $_GET['account_id'] ) )
				return; // not a valid search

			$purchase_ids = Group_Buying_Purchase::get_purchases( array( 'account' => $_GET['account_id'] ) );
			$meta_query = array(
				'post__in' => $purchase_ids,
				);
			$args = array_merge( $args, $meta_query );
		}
		// Search
		if ( isset( $_GET['s'] ) && $_GET['s'] != '' ) {
			$args = array_merge( $args, array( 's' => $_GET['s'] ) );
		}
		// Filter by date
		if ( isset( $_GET['m'] ) && $_GET['m'] != '' ) {
			$args = array_merge( $args, array( 'm' => $_GET['m'] ) );
		}
		$purchases = new WP_Query( $args );

		/**
		 * REQUIRED. *Sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = apply_filters( 'gb_mngt_purchases_items', $purchases->posts );

		/**
		 * REQUIRED. Register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
				'total_items' => $purchases->found_posts,                //WE have to calculate the total number of items
				'per_page'  => $per_page,                    //WE have to determine how many items to show on a page
				'total_pages' => $purchases->max_num_pages   //WE have to calculate the total number of pages
			) );
	}

}
