<?php

/**
 * Gifts controller
 *
 * @package GBS
 * @subpackage Gift
 */
class Group_Buying_Gifts extends Group_Buying_Controller {
	const REDEMPTION_PATH_OPTION = 'gb_gift_redemption';
	const REDEMPTION_QUERY_VAR = 'gb_gift_redemption';
	const FORM_ACTION = 'gb_gift_redemption';
	private static $redemption_path = 'gifts';
	protected static $settings_page;
	private static $instance;

	public static function init() {
		self::register_payment_pane();
		self::register_review_pane();
		//self::register_confirmation_pane();
		self::$redemption_path = get_option( self::REDEMPTION_PATH_OPTION, self::$redemption_path );
		add_action( 'completing_checkout', array( get_class(), 'save_recipient_for_purchase' ), 10, 1 );
		add_action( 'purchase_completed', array( get_class(), 'activate_gifts_for_purchase' ), 10, 1 );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 0 );
		self::register_path_callback( self::$redemption_path, array( get_class(), 'on_redemption_page' ), self::REDEMPTION_QUERY_VAR, 'gifts' );

		// Admin
		self::$settings_page = self::register_settings_page( 'gift_records', self::__( 'Gift Records' ), self::__( 'Gifts' ), 9.1, FALSE, 'records', array( get_class(), 'display_table' ) );
		add_action( 'parse_request', array( get_class(), 'manually_resend_gift' ), 1, 0 );	
	}

	public static function manually_resend_gift( $gift_id = null ) {
		if ( !current_user_can( 'edit_posts' ) ) {
			return; // security check
		}
		if ( isset( $_REQUEST['resend_gift'] ) && $_REQUEST['resend_gift'] != '' ) {
			if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'resend_gift' ) ) {
				$gift_id = $_REQUEST['resend_gift'];
				$recipient = $_REQUEST['recipient'];
			}
		}
		if ( is_numeric( $gift_id ) ) {
			$gift = Group_Buying_Gift::get_instance( $gift_id );
			if ( is_a( $gift, 'Group_Buying_Gift' ) ) {
				if ( $recipient != '' ) {
					$gift->set_recipient( $recipient );
				}
				do_action( 'gb_gift_notification', array( 'gift' => $gift ) );
				return;
			}
		}
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_cart_paths';

		// Settings
		register_setting( $page, self::REDEMPTION_PATH_OPTION );
		add_settings_field( self::REDEMPTION_PATH_OPTION, self::__( 'Gift Redemption Path' ), array( get_class(), 'display_path' ), $page, $section );
	}

	public static function display_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="'.self::REDEMPTION_PATH_OPTION.'" id="'.self::REDEMPTION_PATH_OPTION.'" value="' . esc_attr( self::$redemption_path ) . '"  size="40" /><br />';
	}


	/**
	 * Register action hooks for displaying and processing the payment page
	 *
	 * @return void
	 */
	private static function register_payment_pane() {
		add_filter( 'gb_checkout_panes_'.Group_Buying_Checkouts::PAYMENT_PAGE, array( get_class(), 'display_payment_page' ), 10, 2 );
		add_action( 'gb_checkout_action_'.Group_Buying_Checkouts::PAYMENT_PAGE, array( get_class(), 'process_payment_page' ), 15, 1 ); // higher priority than an offsite redirect.
	}

	private static function register_review_pane() {
		add_filter( 'gb_checkout_panes_'.Group_Buying_Checkouts::REVIEW_PAGE, array( get_class(), 'display_review_page' ), 10, 2 );
	}

	private static function register_confirmation_pane() {
		add_filter( 'gb_checkout_panes_'.Group_Buying_Checkouts::CONFIRMATION_PAGE, array( get_class(), 'display_confirmation_page' ), 10, 2 );
	}

	public static function display_payment_page( $panes, $checkout ) {
		$fields = array(
			'is_gift' => array(
				'type' => 'checkbox',
				'label' => self::__( 'Is this purchase a gift for someone?' ),
				'weight' => 0,
				'default' => ( ( isset( $checkout->cache['gift_recipient'] )&&$checkout->cache['gift_recipient'] )||isset( $_GET['gifter'] )&&$_GET['gifter'] )?TRUE:FALSE,
				'value' => 'is_gift',
			),
			'recipient' => array(
				'type' => 'text',
				'label' => self::__( "Recipient's Email Address" ),
				'weight' => 10,
				'default' => isset( $checkout->cache['gift_recipient'] )?$checkout->cache['gift_recipient']:'',
			),
			'message' => array(
				'type' => 'textarea',
				'label' => self::__( "Your Message" ),
				'weight' => 10,
				'default' => isset( $checkout->cache['gift_message'] )?$checkout->cache['gift_message']:'',
			),
		);
		$fields = apply_filters( 'gb_checkout_fields_gifting', $fields );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		$panes['gifting'] = array(
			'weight' => 5,
			'body' => self::load_view_to_string( 'checkout/gifting', array( 'fields' => $fields ) ),
		);
		return $panes;
	}

	public static function process_payment_page( Group_Buying_Checkouts $checkout ) {
		$valid = TRUE;
		if ( isset( $_POST['gb_gifting_is_gift'] ) && $_POST['gb_gifting_is_gift'] == 'is_gift' ) {

			// Get current user email
			$user = get_userdata( get_current_user_id() );
			$user_email = $user->user_email;

			// Confirm an email was added
			if ( !isset( $_POST['gb_gifting_recipient'] ) || !$_POST['gb_gifting_recipient'] ) {
				self::set_message( "Recipient's Email Address is required for gift purchases", self::MESSAGE_STATUS_ERROR );
				$valid = FALSE;
			} 
			//Check email validity
			elseif ( !sanitize_email( $_POST['gb_gifting_recipient'] ) ) {
				self::set_message( "A valid email address is required for the gift recipient", self::MESSAGE_STATUS_ERROR );
				$valid = FALSE;
			}
			//Check to see if they gave the same email that's tied to their account.
			elseif ( $user_email == sanitize_email( $_POST['gb_gifting_recipient'] ) ) {
				self::set_message( self::__( 'You deserve it but you may not gift yourself this purchase.' ), self::MESSAGE_STATUS_ERROR );
				$valid = FALSE;
			}
		}
		if ( !$valid ) {
			$checkout->mark_page_incomplete( Group_Buying_Checkouts::PAYMENT_PAGE );
		} elseif ( isset( $_POST['gb_gifting_is_gift'] ) && $_POST['gb_gifting_is_gift'] == 'is_gift' ) {
			$checkout->cache['gift_recipient'] = $_POST['gb_gifting_recipient'];
			$checkout->cache['gift_message'] = $_POST['gb_gifting_message'];
		}
	}


	/**
	 * Display the final review pane
	 *
	 * @param array   $panes
	 * @param Group_Buying_Checkout $checkout
	 * @return array
	 */
	public static function display_review_page( $panes, $checkout ) {
		if ( !empty($checkout->cache['gift_recipient']) ) {
			$panes['gifting'] = array(
				'weight' => 5,
				'body' => self::load_view_to_string( 'checkout/gifting-review', array( 'recipient' => $checkout->cache['gift_recipient'], 'message' => $checkout->cache['gift_message'] ) ),
			);
		}
		return $panes;
	}

	/**
	 * Display the confirmation page
	 * Don't depend on anything being in the cache except the purchase ID
	 *
	 * @return array
	 */
	public static function display_confirmation_page( $panes, $checkout ) {
		// TODO
		return $panes;
	}

	public static function save_recipient_for_purchase( $checkout ) {
		if ( !empty($checkout->cache['gift_recipient']) && !empty($checkout->cache['purchase_id']) ) {
			$purchase = Group_Buying_Purchase::get_instance( $checkout->cache['purchase_id'] );
			$gift_id = Group_Buying_Gift::new_gift( $purchase->get_id(), $checkout->cache['gift_recipient'], $checkout->cache['gift_message'] );
		}
	}

	public static function activate_gifts_for_purchase( Group_Buying_Purchase $purchase ) {
		$gift_id = Group_Buying_Gift::get_gift_for_purchase( $purchase->get_id() );
		if ( $gift_id ) {
			$gift = Group_Buying_Gift::get_instance( $gift_id );
			$gift->activate();
		}
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$redemption_path );
		} else {
			return add_query_arg( self::REDEMPTION_QUERY_VAR, 1, home_url() );
		}
	}

	public static function on_redemption_page() {
		self::login_required();
		self::get_instance(); // make sure the class is instantiated
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
		self::do_not_cache(); // never cache the redemption page
		if ( isset( $_POST['gb_gift_action'] ) && $_POST['gb_gift_action'] == self::FORM_ACTION ) {
			$this->process_form_submission();
		}
		add_action( 'pre_get_posts', array( $this, 'edit_query' ), 10, 1 );
		add_action( 'the_post', array( $this, 'view_redemption_form' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'get_title' ), 10, 2 );
	}

	private function process_form_submission() {
		$valid = TRUE;
		$email = $_POST['gb_gift_redemption_email'];
		$code = $_POST['gb_gift_redemption_code'];

		if ( !sanitize_email( $email ) ) {
			self::set_message( self::__( 'A valid email address is required.' ), self::MESSAGE_STATUS_ERROR );
			$valid = FALSE;
		}
		if ( !$code ) {
			self::set_message( self::__( 'A valid coupon code is required.' ), self::MESSAGE_STATUS_ERROR );
			$valid = FALSE;
		}
		if ( !$valid ) {
			return;
		}

		$gift_id = Group_Buying_Gift::validate_gift( $email, $code );
		if ( !$gift_id ) {
			self::set_message( self::__( 'Invalid code: please confirm the email address and coupon code.' ), self::MESSAGE_STATUS_ERROR );
			return;
		}
		$gift = Group_Buying_Gift::get_instance( $gift_id );
		$purchase = $gift->get_purchase();
		if ( $purchase->get_user() != Group_Buying_Purchase::NO_USER ) {
			self::set_message( self::__( 'Error: this coupon code has already been used.' ), self::MESSAGE_STATUS_ERROR );
			return;
		}
		$purchase->set_user( get_current_user_id() );
		self::set_message( sprintf( self::__( 'Success! Visit <a href="%s">your purchases page</a> to print your vouchers.' ), gb_get_vouchers_url() ) );
		wp_redirect( apply_filters( 'gb_gifting_process_gift_redirection', gb_get_vouchers_url(), $purchase ) );
		exit();
	}


	/**
	 * Edit the query on the redemption page to select the user's account.
	 *
	 * @param WP_Query $query
	 * @return void
	 */
	public function edit_query( WP_Query $query ) {
		// we only care if this is the query to show a the edit profile form
		if ( isset( $query->query_vars[self::REDEMPTION_QUERY_VAR] ) && $query->query_vars[self::REDEMPTION_QUERY_VAR] ) {
			// use the user's account as something benign and guaranteed to exist
			$query->query_vars['post_type'] = Group_Buying_Account::POST_TYPE;
			$query->query_vars['p'] = Group_Buying_Account::get_account_id_for_user();
		}
	}

	/**
	 * Update the global $pages array with the HTML for the page.
	 *
	 * @param object  $post
	 * @return void
	 */
	public function view_redemption_form( $post ) {
		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			remove_filter( 'the_content', 'wpautop' );
			$user = wp_get_current_user();
			$view = self::load_view_to_string( 'gift/redemption', array( 'email' => $user->user_email ) );
			global $pages;
			$pages = array( $view );
		}
	}


	/**
	 * Filter 'the_title' to display the title of the page rather than the user name
	 *
	 * @static
	 * @param string  $title
	 * @param int     $post_id
	 * @return string
	 */
	public function get_title(  $title, $post_id  ) {
		$post = &get_post( $post_id );
		if ( $post->post_type == Group_Buying_Account::POST_TYPE ) {
			return self::__( "Redeem a Gift Certificate" );
		}
		return $title;
	}

	public static function display_table() {
		//Create an instance of our package class...
		$wp_list_table = new Group_Buying_Gifts_Table();
		//Fetch, prepare, sort, and filter our data...
		$wp_list_table->prepare_items();

?>
		<script type="text/javascript" charset="utf-8">
			jQuery(document).ready(function($){
				jQuery(".gb_resend_gift").click(function(event) {
					event.preventDefault();
						if(confirm("Are you sure?")){
							var $link = $( this ),
							gift_id = $link.attr( 'ref' ),
							recipient = $( "#"+gift_id+"_recipient_input" ).val(),
							url = $link.attr( 'href' );
							$( "#"+gift_id+"_activate" ).fadeOut('slow');
							$.post( url, { resend_gift: gift_id, recipient: recipient },
								function( data ) {
										$( "#"+gift_id+"_activate_result" ).append( '<?php self::_e( 'Resent' ) ?>' ).fadeIn();
									}
								);
						} else {
							// nothing to do.
						}
				});
				jQuery(".editable_string").click(function(event) {
					event.preventDefault();
					var gift_id = $( this ).attr( 'ref' );
					$( '#' + gift_id + '_recipient_input').show();
					$(this).hide();
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
				<?php $wp_list_table->search_box( self::__( 'Purchase ID' ), 'purchase_id' ); ?>
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

}



if ( !class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class Group_Buying_Gifts_Table extends WP_List_Table {
	protected static $post_type = Group_Buying_Gift::POST_TYPE;

	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
				'singular' => 'gift',     // singular name of the listed records
				'plural' => 'gifts', // plural name of the listed records
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
		$status_links['all'] = "<a href='admin.php?page=group-buying/payment_records{$allposts}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' ) as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( empty( $num_posts->$status_name ) )
				continue;

			if ( isset( $_REQUEST['post_status'] ) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			// replace "Published" with "Complete".
			$label = gb__( 'Payment&nbsp;' ).str_replace( 'Published', 'Complete', translate_nooped_plural( $status->label_count, $num_posts->$status_name ) );
			$status_links[$status_name] = "<a href='admin.php?page=group-buying/gift_records&post_status=$status_name'$class>" . sprintf( $label, number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return $status_links;
	}

	function extra_tablenav( $which ) {
?>
		<div class="alignleft actions">
<?php
		if ( 'top' == $which && !is_singular() ) {

			$this->months_dropdown( self::$post_type );

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
			return apply_filters( 'gb_mngt_gifts_column_'.$column_name, $item ); // do action for those columns that are filtered in
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
		$gift = Group_Buying_Gift::get_instance( $item->ID );
		$purchase_id = $gift->get_purchase_id();
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		if ( $purchase ) {
			$user_id = $purchase->get_original_user();
		} else {
			$user_id = 0;
		}
		$account_id = Group_Buying_Account::get_account_id_for_user( $user_id );

		//Build row actions
		$actions = array(
			'order'    => sprintf( '<a href="admin.php?page=group-buying/purchase_records&s=%s">Order</a>', $purchase_id ),
			'purchaser'    => sprintf( '<a href="post.php?post=%s&action=edit">'.gb__( 'Purchaser' ).'</a>', $account_id ),
		);

		//Return the title contents
		return sprintf( '%1$s <span style="color:silver">(order&nbsp;id:%2$s)</span>%3$s',
			$item->post_title,
			$purchase_id,
			$this->row_actions( $actions )
		);
	}

	function column_total( $item ) {
		$gift = Group_Buying_Gift::get_instance( $item->ID );
		$purchase_id = $gift->get_purchase_id();
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );

		if ( $purchase ) {
			gb_formatted_money( $purchase->get_total() );
		} else {
			gb_formatted_money(0);
		}
	}

	function column_deals( $item ) {
		$gift = Group_Buying_Gift::get_instance( $item->ID );
		$purchase_id = $gift->get_purchase_id();
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		$products = $purchase?$purchase->get_products():array();

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

	function column_gift( $item ) {
		$gift_id = $item->ID;
		$gift = Group_Buying_Gift::get_instance( $gift_id );
		$purchase_id = $gift->get_purchase_id();
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		$account_id = $purchase?$purchase->get_user():-1;
		if ( $account_id == -1 ) {
			echo '<p>';
			echo '<strong>'.gb__( 'Recipient:' ).'</strong> <span class="editable_string gb_highlight" ref="'.$gift_id.'">'.$gift->get_recipient().'</span><input type="text" id="'.$gift_id.'_recipient_input" class="option_recipient cloak" value="'.$gift->get_recipient().'" />';
			echo '<br/><span style="color:silver">'.gb__( 'code' ).': '.$gift->get_coupon_code().'</span>';
			echo '</p>';
			echo '<p><span id="'.$gift_id.'_activate_result"></span><a href="/wp-admin/edit.php?post_type=gb_purchase&resend_gift='.$gift_id.'&_wpnonce='.wp_create_nonce( 'resend_gift' ).'" class="gb_resend_gift button" id="'.$gift_id.'_activate" ref="'.$gift_id.'">Resend</a></p>';
			return;
		} else {
			$user_id = $purchase->get_user();
			$account_id = Group_Buying_Account::get_account_id_for_user( $user_id );
			$account = Group_Buying_Account::get_instance( $account_id );
			$account_name = ( is_a( $account, 'Group_Buying_Account' ) ) ? $account->get_name() : '' ;
			printf( '<a href="%1$s">%2$s<span style="color:silver">(account&nbsp;id:%3$s)</span></a>', get_edit_post_link( $account_id ), $account_name, $account_id );
		}

	}

	function column_status( $item ) {
		$gift = Group_Buying_Gift::get_instance( $item->ID );
		$purchase_id = $gift->get_purchase_id();
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		if ( $purchase ) {
			$account_id = $purchase->get_user();
		} else {
			$account_id = -1;
		}

		if ( $account_id == -1 ) {
			$status = '<strong>'.gb__( 'Pending Claim' ).'</strong><br/>';
		} else {
			$status = '<strong>'.gb__( 'Complete' ).'</strong><br/>';
		}
		$status .= '<span style="color:silver">';
		$status .= mysql2date( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), $item->post_date );
		$status .= '<br/>';
		$status .= gb__( 'Payment: ' ).ucfirst( str_replace( 'publish', 'complete', $item->post_status ) );
		$status .= '</span>';

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
			'total'  => gb__('Totals'),
			'deals'  => gb__('Deals'),
			'gift'  => gb__('Manage')
		);
		return apply_filters( 'gb_mngt_gifts_columns', $columns );
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
		return apply_filters( 'gb_mngt_gifts_sortable_columns', $sortable_columns );
	}


	/**
	 * Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 * */
	function get_bulk_actions() {
		$actions = array();
		return apply_filters( 'gb_mngt_gifts_bulk_actions', $actions );
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
			'post_type' => Group_Buying_Gift::POST_TYPE,
			'post_status' => $filter,
			'posts_per_page' => $per_page,
			'paged' => $this->get_pagenum()
		);
		// Check purchases based on Deal ID
		if ( isset( $_GET['deal_id'] ) && $_GET['deal_id'] != '' ) {

			if ( Group_Buying_Deal::POST_TYPE != get_post_type( $_GET['deal_id'] ) )
				return; // not a valid search

			$purchase_ids = Group_Buying_Purchase::get_purchases( array( 'deal' => $_GET['deal_id'] ) );

			$meta_query = array(
				'meta_query' => array(
					array(
						'key' => '_purchase',
						'value' => $purchase_ids,
						'type' => 'numeric',
						'compare' => 'IN'
					)
				) );
			$args = array_merge( $args, $meta_query );
		}
		// Check payments based on Account ID
		if ( isset( $_GET['account_id'] ) && $_GET['account_id'] != '' ) {

			if ( Group_Buying_Account::POST_TYPE != get_post_type( $_GET['account_id'] ) )
				return; // not a valid search

			$purchase_ids = Group_Buying_Purchase::get_purchases( array( 'account' => $_GET['account_id'] ) );
			$meta_query = array(
				'meta_query' => array(
					array(
						'key' => '_purchase',
						'value' => $purchase_ids,
						'type' => 'numeric',
						'compare' => 'IN'
					)
				) );
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
		$gifts = new WP_Query( $args );

		/**
		 * REQUIRED. *Sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = apply_filters( 'gb_mngt_gifts_items', $gifts->posts );

		/**
		 * REQUIRED. Register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
				'total_items' => $gifts->found_posts,                //WE have to calculate the total number of items
				'per_page'  => $per_page,                    //WE have to determine how many items to show on a page
				'total_pages' => $gifts->max_num_pages   //WE have to calculate the total number of pages
			) );
	}

}