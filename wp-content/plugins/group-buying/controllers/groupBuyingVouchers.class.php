<?php

/**
 * Vouchers controller
 *
 * @package GBS
 * @subpackage Voucher
 */
class Group_Buying_Vouchers extends Group_Buying_Controller {

	const FILTER_QUERY_VAR = 'filter_gb_vouchers';
	const FILTER_EXPIRED_QUERY_VAR = 'expired';
	const FILTER_USED_QUERY_VAR = 'used';
	const FILTER_ACTIVE_QUERY_VAR = 'active';
	const VOUCHER_OPTION_EXP_PATH = 'gb_voucher_path_expired';
	const VOUCHER_OPTION_USED_PATH = 'gb_voucher_path_used';
	const VOUCHER_OPTION_ACTIVE_PATH = 'gb_voucher_path_active';
	const VOUCHER_OPTION_LOGO = 'gb_voucher_logo';
	const VOUCHER_OPTION_FINE_PRINT = 'gb_voucher_fine_print';
	const VOUCHER_OPTION_SUPPORT1 = 'gb_voucher_support_1';
	const VOUCHER_OPTION_SUPPORT2 = 'gb_voucher_support_2';
	const VOUCHER_OPTION_LEGAL = 'gb_voucher_legal';
	const VOUCHER_OPTION_PREFIX = 'gb_voucher_prefix';
	const VOUCHER_OPTION_IDS = 'gb_voucher_ids_options';
	private static $expired_path;
	private static $used_path;
	private static $active_path;
	private static $voucher_logo;
	private static $voucher_fine_print;
	private static $voucher_support1;
	private static $voucher_support2;
	private static $voucher_legal;
	private static $voucher_prefix;
	private static $voucher_ids_option;

	protected static $settings_page;

	public static function init() {
		add_action( 'payment_captured', array( get_class(), 'activate_vouchers' ), 10, 2 );
		add_action( 'purchase_completed', array( get_class(), 'create_vouchers_for_purchase' ), 5, 1 );
		add_filter( 'template_include', array( get_class(), 'override_template' ) );

		self::$expired_path = get_option( self::VOUCHER_OPTION_EXP_PATH, self::FILTER_EXPIRED_QUERY_VAR );
		self::$used_path = get_option( self::VOUCHER_OPTION_USED_PATH, self::FILTER_USED_QUERY_VAR );
		self::$active_path = get_option( self::VOUCHER_OPTION_ACTIVE_PATH, self::FILTER_ACTIVE_QUERY_VAR );
		add_filter( 'gb_rewrite_rules', array( get_class(), 'add_voucher_rewrite_rules' ), 10, 1 );
		self::register_query_var( self::FILTER_QUERY_VAR );
		add_action( 'pre_get_posts', array( get_class(), 'filter_voucher_query' ), 50, 1 );
		add_action( 'parse_query', array( get_class(), 'filter_voucher_query' ), 50, 1 );

		// Hook into GB settings page ( after it's created )
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 20, 0 );

		if ( is_admin() ) {
			// Admin
			self::$settings_page = self::register_settings_page( 'voucher_records', self::__( 'Voucher Records' ), self::__( 'Vouchers' ), 6, FALSE, 'records', array( get_class(), 'display_table' ) );
			add_action( 'parse_request', array( get_class(), 'manually_activate_vouchers' ), 1, 0 );
		}

		self::$voucher_logo = get_option( self::VOUCHER_OPTION_LOGO );
		self::$voucher_fine_print = get_option( self::VOUCHER_OPTION_FINE_PRINT );
		self::$voucher_support1 = get_option( self::VOUCHER_OPTION_SUPPORT1 );
		self::$voucher_support2 = get_option( self::VOUCHER_OPTION_SUPPORT2 );
		self::$voucher_legal = get_option( self::VOUCHER_OPTION_LEGAL );
		self::$voucher_prefix = get_option( self::VOUCHER_OPTION_PREFIX );
		self::$voucher_ids_option = get_option( self::VOUCHER_OPTION_IDS, 'random' );

		// AJAX Functions
		add_action( 'wp_ajax_gb_mark_voucher', array( get_class(), 'mark_voucher' ) );
	}

	/**
	 * A purchase has been completed. Create all the necessary vouchers
	 * and tie them to that purchase
	 *
	 * @static
	 * @param Group_Buying_Purchase $purchase
	 * @return void
	 */
	public static function create_vouchers_for_purchase( Group_Buying_Purchase $purchase ) {
		$products = $purchase->get_products();
		$user_id = $purchase->get_user();
		$account = Group_Buying_Account::get_instance( $user_id );
		foreach ( $products as $product ) {
			$deal = Group_Buying_Deal::get_instance( $product['deal_id'] );
			if ( !$deal ) {
				self::set_message( sprintf( self::__( 'We experienced an error creating a voucher for deal ID %d. Please contact a site administrator for asssistance.' ), $product['deal_id'] ) );
				continue; // nothing else we can do
			}
			for ( $i = 0 ; $i < $product['quantity'] ; $i++ ) {
				$voucher_id = Group_Buying_Voucher::new_voucher( $purchase->get_id(), $product['deal_id'] );
				$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
				$voucher->set_product_data( $product );
				$voucher->set_serial_number();
				$voucher->set_security_code();
				do_action( 'create_voucher_for_purchase', $voucher_id, $purchase, $product );
			}
		}
	}

	public static function override_template( $template ) {
		if ( Group_Buying_Voucher::is_voucher_query() ) {
			
			// require login unless it's a validated temp access
			if ( !Group_Buying_Voucher::temp_voucher_access_attempt() ) {
				self::login_required();
			}
			
			if ( is_single() ) {
				$template = self::locate_template( array(
						'account/voucher.php',
						'vouchers/single-voucher.php',
						'vouchers/single.php',
						'vouchers/voucher.php',
						'voucher.php',
					), $template );
			} else {
				$status = get_query_var( self::FILTER_QUERY_VAR );
				$template = self::locate_template( array(
						'account/'.$status.'-vouchers.php',
						'vouchers/'.$status.'-vouchers.php',
						'vouchers/'.$status.'.php',
						'account/vouchers.php',
						'vouchers/vouchers.php',
						'vouchers/index.php',
						'vouchers/archive.php',
						'vouchers.php',
					), $template );
			}
		}
		return $template;
	}

	/**
	 * Add the rewrite rules for filtering vouchers
	 *
	 * @param array   $vars
	 * @return array
	 */
	public function add_voucher_rewrite_rules( $rules ) {
		global $wp_rewrite;
		$rules[trailingslashit( Group_Buying_Voucher::REWRITE_SLUG ).self::$expired_path.'(/page/?([0-9]{1,}))?/?$'] = 'index.php?post_type='.Group_Buying_Voucher::POST_TYPE.'&paged='.$wp_rewrite->preg_index( 2 ).'&'.self::FILTER_QUERY_VAR.'='.self::FILTER_EXPIRED_QUERY_VAR;
		$rules[trailingslashit( Group_Buying_Voucher::REWRITE_SLUG ).self::$used_path.'(/page/?([0-9]{1,}))?/?$'] = 'index.php?post_type='.Group_Buying_Voucher::POST_TYPE.'&paged='.$wp_rewrite->preg_index( 2 ).'&'.self::FILTER_QUERY_VAR.'='.self::FILTER_USED_QUERY_VAR;
		$rules[trailingslashit( Group_Buying_Voucher::REWRITE_SLUG ).self::$active_path.'(/page/?([0-9]{1,}))?/?$'] = 'index.php?post_type='.Group_Buying_Voucher::POST_TYPE.'&paged='.$wp_rewrite->preg_index( 2 ).'&'.self::FILTER_QUERY_VAR.'='.self::FILTER_ACTIVE_QUERY_VAR;
		return $rules;

	}


	/**
	 * Edit the query to remove other users vouchers
	 *
	 * @param WP_Query $query
	 * @return void
	 */
	public static function filter_voucher_query( WP_Query $query ) {

		// we only care if this is the query for vouchers
		if ( Group_Buying_Voucher::is_voucher_query( $query ) && !is_admin() && get_query_var( self::FILTER_QUERY_VAR ) && !isset( $query->query_vars['gb_bypass_filter'] ) ) {

			if ( get_query_var( self::FILTER_QUERY_VAR ) == self::FILTER_USED_QUERY_VAR ) {
				if ( !isset( $query->query_vars['meta_query'] ) || !is_array( $query->query_vars['meta_query'] ) ) {
					$query->query_vars['meta_query'] = array();
				}
				$query->query_vars['meta_query'][] = array(
					'key' => '_claimed',
					'value' => 0,
					'compare' => '>'
				);
			}
			if ( get_query_var(self::FILTER_ACTIVE_QUERY_VAR) == self::FILTER_ACTIVE_QUERY_VAR ) {
				if ( !isset($query->query_vars['meta_query']) || !is_array($query->query_vars['meta_query']) ) {
					$query->query_vars['meta_query'] = array();
				}
				$query->query_vars['meta_query'][] = array(
					'key' => '_claimed',
					'compare' => 'NOT EXISTS',
				);
			}
			if (
				get_query_var( self::FILTER_QUERY_VAR ) == self::FILTER_EXPIRED_QUERY_VAR
				|| get_query_var( self::FILTER_QUERY_VAR ) == self::FILTER_ACTIVE_QUERY_VAR // TODO
			) {
				// TODO needs some SQL love so non expired vouchers are returned.
				// get all the user's purchases
				$purchases = Group_Buying_Purchase::get_purchases( array(
						'user' => get_current_user_id(),
					) );
				if ( $purchases ) {
					$args = array(
						'post_type' => Group_Buying_Voucher::POST_TYPE,
						'post_status' => 'publish',
						'posts_per_page' => -1,
						'fields' => 'ids',
						'gb_bypass_filter' => TRUE,
						'meta_query' => array(
							'key' => '_purchase_id',
							'value' => $purchases,
							'compare' => 'IN',
							'type' => 'NUMERIC',
						)
					);
					$vouchers = new WP_Query( $args );
					$filtered_vouchers = array();
					foreach ( $vouchers->posts as $voucher_id ) {
						$deal_id = get_post_meta( $voucher_id, '_voucher_deal_id', TRUE );
						if ( !in_array( $voucher_id, $filtered_vouchers ) ) {
							// If expired query remove the expired vouchers, plus those without exp
							if ( get_query_var( self::FILTER_QUERY_VAR ) == self::FILTER_EXPIRED_QUERY_VAR ) {
								$exp = get_post_meta( $deal_id, '_voucher_expiration_date', TRUE );
								if ( $exp && current_time( 'timestamp' ) > $exp ) { // expired
									$filtered_vouchers[] = $voucher_id;
								}
							}
							// If active query removed expired vouchers, keep those without exp
							elseif ( get_query_var( self::FILTER_QUERY_VAR ) == self::FILTER_ACTIVE_QUERY_VAR ) {
									$exp = get_post_meta( $deal_id, '_voucher_expiration_date', TRUE );
									if ( !$exp || current_time( 'timestamp' ) < $exp ) { // not expired
										$filtered_vouchers[] = $voucher_id;
									}
							} else {
								$filtered_vouchers[] = $voucher_id;
							}
						}
					}
					$query->query_vars['post__in'] = $filtered_vouchers;
					
				}
			}

		}
	}

	/**
	 * Get the deal IDs from a user's vouchers.
	 *
	 * @param string  $status 'any' all deal ids, 'used' all deals with claimed vouchers, 'active' all deals with unclaimed vouchers
	 * @return string
	 */
	public static function get_deal_ids( $status = NULL ) {
		// This could possibly be done more efficiently in a single SQL query, but that's an
		// optimization for a later date. All the vouchers and deals will likely be loaded on this
		// page, anyway, so it's probably not costing us much extra doing it this way.

		// self::filter_voucher_query() should filter out vouchers that don't belong to the current user
		$args = array(
			'post_type' => Group_Buying_Voucher::POST_TYPE,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'fields' => 'ids'
		);
		$deal_ids = array();
		$status = ( NULL === $status && !get_query_var( Group_Buying_Vouchers::FILTER_QUERY_VAR ) ) ? 'any' : get_query_var( Group_Buying_Vouchers::FILTER_QUERY_VAR );
		foreach ( get_posts($args) as $voucher_id ) {
			$deal_id = get_post_meta( $voucher_id, '_voucher_deal_id', TRUE );
			if ( !in_array( $deal_id, $deal_ids ) ) {
				$claimed = get_post_meta( $voucher_id, '_claimed', TRUE );
				$exp = get_post_meta( $deal_id, '_voucher_expiration_date', TRUE );
				if ( $status == self::FILTER_USED_QUERY_VAR ) {
					if ( current_time( 'timestamp' ) < $exp || $claimed ) { // Returning expired or claimed vouchers
						$deal_ids[] = $deal_id;
					}
				}
				elseif ( $status == self::FILTER_EXPIRED_QUERY_VAR ) {
					if ( !empty( $exp ) && current_time( 'timestamp' ) > $exp ) { // Returning expired vouchers
						$deal_ids[] = $deal_id;
					}
				}
				elseif ( $status == self::FILTER_ACTIVE_QUERY_VAR ) { // return all non-expired deals if active query
					if ( !$claimed ) { // Don't included claimed vouchers
						if ( empty( $exp ) || ( !empty( $exp ) && current_time( 'timestamp' ) < $exp ) ) {
							$deal_ids[] = $deal_id;
						}
					}
				} else {
					$deal_ids[] = $deal_id;
				}
			}
		}
		return $deal_ids;
	}

	/**
	 * Add the filter query var
	 *
	 * @param array   $vars
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		array_push( $vars, self::FILTER_QUERY_VAR );
		return $vars;
	}


	public static function get_url() {
		return get_post_type_archive_link( Group_Buying_Voucher::POST_TYPE );
	}

	public static function get_active_url() {
		return get_post_type_archive_link( Group_Buying_Voucher::POST_TYPE ).self::$active_path;
	}

	public static function get_expired_url() {
		return get_post_type_archive_link( Group_Buying_Voucher::POST_TYPE ).self::$expired_path;
	}

	public static function get_used_url() {
		return get_post_type_archive_link( Group_Buying_Voucher::POST_TYPE ).self::$used_path;
	}

	/**
	 * Activate any pending vouchers if the purchased deal is now successful
	 *
	 * @static
	 * @param Group_Buying_Purchase $purchase
	 * @return void
	 */
	public static function activate_vouchers( Group_Buying_Payment $payment, $items_captured ) {
		$purchase_id = $payment->get_purchase();
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		$products = $purchase->get_products();
		foreach ( $products as $product ) {
			if ( in_array( $product['deal_id'], $items_captured ) ) {
				$deal = Group_Buying_Deal::get_instance( $product['deal_id'] );
				if ( $deal->is_successful() ) {
					$vouchers = Group_Buying_Voucher::get_pending_vouchers( $product['deal_id'], $purchase_id ); // Added purchase id 4.3.x so that only the purchased vouchers are activated.
					foreach ( $vouchers as $voucher_id ) {
						$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
						$voucher->activate();
					}
				}
			}
		}
	}

	public static function manually_activate_vouchers( $voucher_id = null ) {
		if ( !current_user_can( 'edit_posts' ) ) {
			return; // security check
		}
		if ( isset( $_REQUEST['activate_voucher'] ) && $_REQUEST['activate_voucher'] != '' ) {
			if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'activate_voucher' ) ) {
				$voucher_id = $_REQUEST['activate_voucher'];
			}
		}
		if ( is_numeric( $voucher_id ) ) {
			$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
			if ( is_a( $voucher, 'Group_Buying_Voucher' ) && !$voucher->is_active() ) {
				$voucher->activate();
				return;
			}
		}
	}

	public static function mark_voucher() {
			
		if ( isset( $_REQUEST['voucher_id'] ) && $_REQUEST['voucher_id'] ) {
			$voucher = Group_Buying_Voucher::get_instance( $_REQUEST['voucher_id'] );
			// If destroying claim date
			if ( isset( $_REQUEST['unmark_voucher'] ) && $_REQUEST['unmark_voucher'] ) {
				$marked = $voucher->set_claimed_date( TRUE );
				gb_e('Voucher Claim Date Removed.');
				exit();
			}
			
			$marked = $voucher->set_claimed_date();
			$data = array(
				'date' => date( get_option( 'date_format' ), current_time( 'timestamp', 1 ) ),
				'notes' => gb__( 'Customer marked voucher as redeemed.' )
			);
			$voucher->set_redemption_data( $data );
			echo apply_filters( 'gb_ajax_mark_voucher', date( get_option( 'date_format' ), $marked ) );
			exit();
		}
		exit();
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_general_voucher_settings';
		add_settings_section( $section, self::__( 'Voucher Settings' ), array( get_class(), 'display_settings_section' ), $page );
		// Settings
		register_setting( $page, self::VOUCHER_OPTION_LOGO );
		register_setting( $page, self::VOUCHER_OPTION_FINE_PRINT );
		register_setting( $page, self::VOUCHER_OPTION_SUPPORT1 );
		register_setting( $page, self::VOUCHER_OPTION_SUPPORT2 );
		register_setting( $page, self::VOUCHER_OPTION_LEGAL );
		register_setting( $page, self::VOUCHER_OPTION_PREFIX );
		register_setting( $page, self::VOUCHER_OPTION_IDS );

		add_settings_field( self::VOUCHER_OPTION_LOGO, self::__( 'Voucher Logo' ), array( get_class(), 'display_voucher_option_logo' ), $page, $section );
		add_settings_field( self::VOUCHER_OPTION_FINE_PRINT, self::__( 'Voucher Fine Print' ), array( get_class(), 'display_voucher_option_fine_print' ), $page, $section );
		add_settings_field( self::VOUCHER_OPTION_SUPPORT1, self::__( 'Voucher Support Contact' ), array( get_class(), 'display_voucher_option_support1' ), $page, $section );
		add_settings_field( self::VOUCHER_OPTION_SUPPORT2, self::__( 'Voucher Support Contact' ), array( get_class(), 'display_voucher_option_support2' ), $page, $section );
		add_settings_field( self::VOUCHER_OPTION_LEGAL, self::__( 'Voucher Legal Info' ), array( get_class(), 'display_voucher_option_legal' ), $page, $section );
		add_settings_field( self::VOUCHER_OPTION_PREFIX, self::__( 'Voucher Prefix' ), array( get_class(), 'display_voucher_option_prefix' ), $page, $section );
		//add_settings_field(self::VOUCHER_OPTION_IDS, self::__('Voucher IDs'), array(get_class(), 'display_voucher_option_ids'), $page, $section);

	}

	public static function display_voucher_option_logo() {
		echo '<input type="text" name="'.self::VOUCHER_OPTION_LOGO.'" value="'.self::$voucher_logo.'" size="40">';
	}
	public static function display_voucher_option_fine_print() {
		echo '<textarea rows="3" cols="40" name="'.self::VOUCHER_OPTION_FINE_PRINT.'" id="'.self::VOUCHER_OPTION_FINE_PRINT.'" class="tinymce" style="width:400">'.esc_textarea( self::$voucher_fine_print ).'</textarea>';
	}
	public static function display_voucher_option_support1() {
		echo '<input type="text" name="'.self::VOUCHER_OPTION_SUPPORT1.'" value="'.self::$voucher_support1.'" size="40">';
	}
	public static function display_voucher_option_support2() {
		echo '<input type="text" name="'.self::VOUCHER_OPTION_SUPPORT2.'" value="'.self::$voucher_support2.'" size="40">';
	}
	public static function display_voucher_option_legal() {
		echo '<textarea rows="3" cols="40" name="'.self::VOUCHER_OPTION_LEGAL.'" id="'.self::VOUCHER_OPTION_LEGAL.'" class="tinymce" style="width:400">'.esc_textarea( self::$voucher_legal ).'</textarea>';
	}
	public static function display_voucher_option_prefix() {
		echo '<input type="text" name="'.self::VOUCHER_OPTION_PREFIX.'" value="'.self::$voucher_prefix.'">';
	}
	public static function display_voucher_option_ids() {
		echo '<select name="'.self::VOUCHER_OPTION_IDS.'"><option value="random" '.selected( 'random', self::$voucher_ids_option, FALSE ).'>Random</option><option value="sequantial" '.selected( 'sequantial', self::$voucher_ids_option, FALSE ).'>Sequential</option><option value="none" '.selected( 'none', self::$voucher_ids_option, FALSE ).'>None</option>';
	}

	public static function get_voucher_logo() {
		return self::$voucher_logo;
	}
	public static function get_voucher_fine_print() {
		return self::$voucher_fine_print;
	}
	public static function get_voucher_support1() {
		return self::$voucher_support1;
	}
	public static function get_voucher_support2() {
		return self::$voucher_support2;
	}
	public static function get_voucher_legal() {
		return self::$voucher_legal;
	}
	public static function get_voucher_prefix() {
		return self::$voucher_prefix;
	}
	public static function get_voucher_option_ids() {
		return self::$voucher_ids_option;
	}



	public static function display_table() {
		//Create an instance of our package class...
		$wp_list_table = new Group_Buying_Vouchers_Table();
		//Fetch, prepare, sort, and filter our data...
		$wp_list_table->prepare_items();

?>
		<script type="text/javascript" charset="utf-8">
			jQuery(document).ready(function($){
				jQuery(".gb_activate").on('click', function(event) {
					event.preventDefault();
						if( confirm( '<?php gb_e("Are you sure? This will make the voucher immediately available for download.") ?>' ) ){
							var $link = $( this ),
							voucher_id = $link.attr( 'ref' );
							url = $link.attr( 'href' );
							$( "#"+voucher_id+"_activate" ).fadeOut('slow');
							$.post( url, { activate_voucher: voucher_id },
								function( data ) {
										$( "#"+voucher_id+"_activate_result" ).append( '<?php self::_e( 'Activated' ) ?>' ).fadeIn();
									}
								);
						} else {
							// nothing to do.
						}
				});
				jQuery(".gb_deactivate").on('click', function(event) {
					event.preventDefault();
						if( confirm( '<?php gb_e("Are you sure? This will immediately remove the voucher from customer access.") ?>' ) ) {
							var $deactivate_button = $( this ),
							deactivate_voucher_id = $deactivate_button.attr( 'ref' );
							$( "#"+deactivate_voucher_id+"_deactivate" ).fadeOut('slow');
							$.post( ajaxurl, { action: 'gbs_deactivate_voucher', voucher_id: deactivate_voucher_id, deactivate_voucher_nonce: '<?php echo wp_create_nonce( Group_Buying_Destroy::NONCE ) ?>' },
								function( data ) {
										$( "#"+deactivate_voucher_id+"_deactivate_result" ).append( '<?php self::_e( 'Deactivated' ) ?>' ).fadeIn();
									}
								);
						} else {
							// nothing to do.
						}
				});
				jQuery(".gb_destroy").on('click', function(event) {
					event.preventDefault();
						if( confirm( '<?php gb_e("This will permanently destroy the voucher from the database and remove records of it’s existence from the related purchase and payment(s) which cannot be reversed. This will not reverse any payments or provide a credit to the customer, that must be done manually. Are you sure?") ?>' ) ) {
							var $destroy_link = $( this ),
							destroy_voucher_id = $destroy_link.attr( 'ref' );
							$.post( ajaxurl, { action: 'gbs_destroyer', type: 'voucher', id: destroy_voucher_id, destroyer_nonce: '<?php echo wp_create_nonce( Group_Buying_Destroy::NONCE ) ?>' },
								function( data ) {
										$destroy_link.parent().parent().parent().parent().fadeOut();
									}
								);
						} else {
							// nothing to do.
						}
				});
				jQuery(".gb_unclaim").on('click', function(event) {
					event.preventDefault();
						if(confirm("Are you sure?")){
							var $unclaim_button = $( this ),
							unclaim_voucher_id = $unclaim_button.attr( 'ref' );
							$( "#"+unclaim_voucher_id+"_unclaim" ).fadeOut('slow');
							$.post( ajaxurl, { action: 'gb_mark_voucher', voucher_id: unclaim_voucher_id, unmark_voucher: 1 },
								function( data ) {
										$( "#"+unclaim_voucher_id+"_unclaim_result" ).append( '<?php gb_e("Voucher Redemption Removed.") ?>' ).fadeIn();
									}
								);
						} else {
							// nothing to do.
						}
				});
			});
		</script>
		<style type="text/css">
			#voucher_deal_id-search-input, #voucher_purchase_id-search-input, #voucher_account_id-search-input, #voucher_id-search-input { width:5em; margin-left: 10px;}
		</style>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2 class="nav-tab-wrapper">
				<?php self::display_admin_tabs(); ?>
			</h2>

			 <?php $wp_list_table->views() ?>
			<form id="payments-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $wp_list_table->search_box( self::__( 'Voucher ID' ), 'voucher_id' ); ?>
				<p class="search-box deal_search">
					<label class="screen-reader-text" for="voucher_deal_id-search-input"><?php self::_e('Deal ID:') ?></label>
					<input type="text" id="voucher_deal_id-search-input" name="deal_id" value="">
					<input type="submit" name="" id="search-submit" class="button" value="<?php self::_e('Deal ID') ?>">
				</p>
				<p class="search-box purchase_search">
					<label class="screen-reader-text" for="voucher_purchase_id-search-input"><?php self::_e('Order ID:') ?></label>
					<input type="text" id="voucher_purchase_id-search-input" name="purchase_id" value="">
					<input type="submit" name="" id="search-submit" class="button" value="<?php self::_e('Order ID') ?>">
				</p>
				<p class="search-box account_search">
					<label class="screen-reader-text" for="voucher_account_id-search-input"><?php self::_e('Account ID:') ?></label>
					<input type="text" id="voucher_account_id-search-input" name="account_id" value="">
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
class Group_Buying_Vouchers_Table extends WP_List_Table {
	protected static $post_type = Group_Buying_Voucher::POST_TYPE;

	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
				'singular' => gb__('voucher'),     // singular name of the listed records
				'plural' => gb__('vouchers'), // plural name of the listed records
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
		$status_links['all'] = "<a href='admin.php?page=group-buying/voucher_records{$allposts}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' ) as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( empty( $num_posts->$status_name ) )
				continue;

			if ( isset( $_REQUEST['post_status'] ) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			// replace "Published" with "Complete".
			$label = str_replace( array( 'Published', 'Trash' ), array( 'Active', 'Deactived' ), translate_nooped_plural( $status->label_count, $num_posts->$status_name ) );
			$status_links[$status_name] = "<a href='admin.php?page=group-buying/voucher_records&post_status=$status_name'$class>" . sprintf( $label, number_format_i18n( $num_posts->$status_name ) ) . '</a>';
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
			return apply_filters( 'gb_mngt_vouchers_column_'.$column_name, $item ); // do action for those columns that are filtered in
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
		$voucher = Group_Buying_Voucher::get_instance( $item->ID );
		$purchase = $voucher->get_purchase();
		if ( !is_a( $purchase, 'Group_Buying_Purchase' ) ) {
			return '<p style="color:#BC0B0B">' . gb__( 'ERROR: Order not found.' ) . '</span>';
		}
		$user_id = $purchase->get_user();
		$account = Group_Buying_Account::get_instance( $user_id );
		$deal_id = $voucher->get_post_meta( '_voucher_deal_id' );


		//Build row actions
		$actions = array(
			'deal'    => sprintf( '<a href="%s">'.gb__('Deal' ).'</a>', get_edit_post_link( $deal_id ) ),
			'purchase'    => sprintf( '<a href="admin.php?page=group-buying/purchase_records&s=%s">'.gb__('Order' ).'</a>', $purchase->get_id() )
		);
		if ( $user_id == -1 ) { // gifts
			$purchaser = array(
				'purchaser'    => sprintf( '<a href="admin.php?page=group-buying/gift_records&s=%s">'.gb__( 'Gift' ).'</a>', $purchase->get_id() ),
			);
		} else {
			$purchaser = array(
				'purchaser'    => sprintf( '<a href="%s">'.gb__( 'Purchaser' ).'</a>', get_edit_post_link( $account->get_id() ) ),
			);
		}

		$actions = array_merge( $actions, $purchaser );

		//Return the title contents
		return sprintf( gb__(  '%1$s <span style="color:silver">(voucher&nbsp;id:%2$s)</span>%3$s' ),
			get_the_title( $item->ID ),
			$item->ID,
			$this->row_actions( $actions )
		);
	}

	function column_code( $item ) {
		$voucher = Group_Buying_Voucher::get_instance( $item->ID );
		echo $voucher->get_serial_number();
	}

	function column_manage( $item ) {
		$voucher_id = $item->ID;
		if ( get_post_status( $voucher_id ) != 'publish' ) {
			$activate_path = 'edit.php?post_type=gb_deal&activate_voucher='.$voucher_id.'&_wpnonce='.wp_create_nonce( 'activate_voucher' );
			echo '<p><span id="'.$voucher_id.'_activate_result"></span><a href="'.admin_url( $activate_path ).'" class="gb_activate button" id="'.$voucher_id.'_activate" ref="'.$voucher_id.'">'.gb__('Activate').'</a></p>';
		} else {
			echo '<p><span id="'.$voucher_id.'_deactivate_result"></span><a href="javascript:void(0)" class="gb_deactivate button disabled" id="'.$voucher_id.'_deactivate" ref="'.$voucher_id.'">'.gb__('Deactivate').'</a></p>';
		}
	}

	function column_status( $item ) {
		$voucher_id = $item->ID;
		$voucher = Group_Buying_Voucher::get_instance( $voucher_id );

		$actions = array(
			'trash'    => '<span id="'.$voucher_id.'_destroy_result"></span><a href="javascript:void(0)" class="gb_destroy" id="'.$voucher_id.'_destroy" ref="'.$voucher_id.'">'.gb__('Delete Records').'</a>',
		);

		$status = ucfirst( str_replace( 'publish', 'active', $item->post_status ) );
		$status .= '<br/><span style="color:silver">';
		$status .= mysql2date( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), $item->post_date );
		$status .= '</span>';
		$status .= $this->row_actions( $actions );
		return $status;
	}

	function column_claimed( $item ) {
		$voucher = Group_Buying_Voucher::get_instance( $item->ID );
		$claim_date = $voucher->get_claimed_date();
		$status = '';
		if ( $claim_date ) {
			$status = '<p>' . mysql2date( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), $claim_date ) . '</p>';
			$status .= '<p><span id="'.$item->ID.'_unclaim_result"></span><a href="javascript:void(0)" class="gb_unclaim button disabled" id="'.$item->ID.'_unclaim" ref="'.$item->ID.'">'.gb__('Remove Redemption').'</a></p>';
		}
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
			'code'  => gb__('Code'),
			'manage'  => gb__('Manage Status'),
			'claimed'  => gb__('Redeemed')
		);
		return apply_filters( 'gb_mngt_vouchers_columns', $columns );
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
		return apply_filters( 'gb_mngt_vouchers_sortable_columns', $sortable_columns );
	}


	/**
	 * Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 * */
	function get_bulk_actions() {
		$actions = array();
		return apply_filters( 'gb_mngt_vouchers_bulk_actions', $actions );
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

		$filter = ( isset( $_REQUEST['post_status'] ) ) ? $_REQUEST['post_status'] : array( 'publish', 'pending', 'draft', 'future' );
		$args=array(
			'post_type' => Group_Buying_Voucher::POST_TYPE,
			'post_status' => $filter,
			'posts_per_page' => $per_page,
			'paged' => $this->get_pagenum()
		);
		if ( isset( $_GET['purchase_id'] ) && $_GET['purchase_id'] != '' ) {

			if ( Group_Buying_Purchase::POST_TYPE != get_post_type( $_GET['purchase_id'] ) )
				return; // not a valid search

			$vouchers = Group_Buying_Voucher::get_vouchers_for_purchase( $_GET['purchase_id'] );
			if ( empty( $vouchers ) )
				return;

			$args = array_merge( $args, array( 'post__in' => $vouchers ) );
		}
		// Check purchases based on Deal ID
		if ( isset( $_GET['deal_id'] ) && $_GET['deal_id'] != '' ) {

			if ( Group_Buying_Deal::POST_TYPE != get_post_type( $_GET['deal_id'] ) )
				return; // not a valid search

			$vouchers = Group_Buying_Voucher::get_vouchers_for_deal( $_GET['deal_id'] );
			if ( empty( $vouchers ) )
				return;

			$args = array_merge( $args, array( 'post__in' => $vouchers ) );
		}
		// Check payments based on Account ID
		if ( isset( $_GET['account_id'] ) && $_GET['account_id'] != '' ) {

			if ( Group_Buying_Account::POST_TYPE != get_post_type( $_GET['account_id'] ) )
				return; // not a valid search

			$purchase_ids = Group_Buying_Purchase::get_purchases( array( 'account' => $_GET['account_id'] ) );

			$meta_query = array(
				'meta_query' => array(
					array(
						'key' => '_purchase_id',
						'value' => $purchase_ids,
						'type' => 'numeric',
						'compare' => 'IN'
					)
				) );
			$args = array_merge( $args, $meta_query );
		}
		// Search
		if ( isset( $_GET['s'] ) && $_GET['s'] != '' ) {
			$args = array_merge( $args, array( 'p' => $_GET['s'] ) );
		}
		// Filter by date
		if ( isset( $_GET['m'] ) && $_GET['m'] != '' ) {
			$args = array_merge( $args, array( 'm' => $_GET['m'] ) );
		}
		$vouchers = new WP_Query( $args );

		/**
		 * REQUIRED. *Sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = apply_filters( 'gb_mngt_vouchers_items', $vouchers->posts );

		/**
		 * REQUIRED. Register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
				'total_items' => $vouchers->found_posts,                //WE have to calculate the total number of items
				'per_page'  => $per_page,                    //WE have to determine how many items to show on a page
				'total_pages' => $vouchers->max_num_pages   //WE have to calculate the total number of pages
			) );
	}

}
