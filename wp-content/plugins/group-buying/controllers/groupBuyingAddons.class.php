<?php

/**
 * GBS Addons
 *
 * @package GBS
 * @subpackage Base
 */
class Group_Buying_Addons extends Group_Buying_Controller {
	const ADDONS_SETTING = 'gb_enabled_addons';
	protected static $settings_page;
	protected static $marketplace_settings_page;
	protected static $api_url = 'http://groupbuyingsite.com/api/json/addon/';
	// protected static $api_url = 'http://staging.groupbuyingsite.com/api/json/addon/';
	protected static $cart_url = 'http://groupbuyingsite.com/marketplace/checkout/';
	// protected static $cart_url = 'http://staging.groupbuyingsite.com/checkout/';
	private static $addons = array();

	/**
	 * Declare all of the addons available
	 *
	 * @static
	 * @return array
	 */
	private static function addon_definitions() {
		if ( self::$addons ) {
			return self::$addons;
		}
		self::$addons['attributes'] = array(
			'label' => self::__( 'Deal Attributes' ),
			'description' => self::__( 'Add attributes (e.g. color, size) that customers can choose when buying deals. Categories are used to filter the selection and additional categories can be added via a basic filter (see forum for details).' ),
			'files' => array(
				GB_PATH.'/models/groupBuyingAttribute.class.php',
				GB_PATH.'/controllers/groupBuyingAttributes.class.php',
				GB_PATH.'/template_tags/attributes.php',
			),
			'callbacks' => array(
				array( 'Group_Buying_Attribute', 'init' ),
				array( 'Group_Buying_Attributes', 'init' ),
			),
		);
		self::$addons['api'] = array(
			'label' => self::__( 'GBS API' ),
			'description' => self::__( 'JSON API for GBS. Temp API Doc. found <a href="http://dl.dropbox.com/u/403305/api-doc.html">here</a>.' ),
			'files' => array(
				GB_PATH.'/controllers/groupBuyingAPI.class.php'
			),
			'callbacks' => array(
				array( 'Group_Buying_API', 'init' )
			),
		);
		self::$addons['fulfillment'] = array(
			'label' => self::__( 'Order Fulfillment and Inventory Mngt.' ),
			'description' => self::__( 'Basic method to manage order status and low inventory notifications.' ),
			'files' => array(
				GB_PATH.'/controllers/groupBuyingFulfillment.class.php',
				GB_PATH.'/template_tags/fulfillment.php',
			),
			'callbacks' => array(
				array( 'Group_Buying_Fulfillment', 'init' ),
			),
		);
		self::$addons['query_optimization'] = array(
			'label' => self::__( 'Advanced: Query Optimization' ),
			'description' => self::__( 'This optimization should be used with caution and should only be used if advised. It will make database queries more efficient by adding additional tables.' ),
			'files' => array(
				GB_PATH.'/controllers/groupBuyingQueryOptimization.class.php',
			),
			'callbacks' => array(
				array( 'Group_Buying_Query_Optimization', 'init' ),
			),
		);
		self::$addons['dynamic_attribute_selection'] = array(
			'label' => self::__( 'Dynamic Attribute Selection' ),
			'description' => self::__( 'Instead of sifting through a long list of "labels" (e.g.  "Medium – Orange", "Small – Orange", "Large – Orange", "XL – Orange"...etc.) the customer can select from separate category dropdowns (e.g.  "size" and "color") and a dynamically generated add-to-cart button will show. ' ),
			'files' => array(),
			'callbacks' => array(
				array( 'Group_Buying_Attributes', 'activate_dynamic_category_selection' ),
			),
		);
		self::$addons = apply_filters( 'gb_addons', self::$addons );
		return self::$addons;
	}

	/**
	 * Init actions and settings page
	 * @return void
	 */
	public static function init() {
		self::$settings_page = self::register_settings_page( 'gb_addons', self::__( 'GBS Add-ons' ), self::__( 'Add-ons' ), 100000, FALSE, 'addons' );
		self::$marketplace_settings_page = self::register_settings_page( 'gb_addon_marketplace', self::__( 'GBS Marketplace' ), self::__( 'Marketplace' ), 100001, FALSE, 'addons', array( get_class(), 'shop_view') );
		add_action( 'init', array( get_class(), 'load_enabled_addons' ), -1, 0 );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 20, 0 );
		add_action( 'gb_options_shop', array( get_class(), 'shop_view' ), 20 );

		// Plugin upgrade hooks
		add_filter( 'pre_set_site_transient_update_plugins', array( get_class(), 'site_transient_update_plugins' ) );
		add_action( 'install_plugins_pre_plugin-information', array( get_class(), 'upgrade_popup' ) ); // thickbox info
		
		// Plugin API for purchase
		add_filter( 'plugins_api_result', array( get_class(), 'plugins_api_result' ), 10, 3 );

		// Theme Info for purchase
		add_filter( 'themes_api_result', array( get_class(), 'plugins_api_result' ), 10, 3 );
		
	}

	/**
	 * Load enabled Addons
	 * @return void
	 */
	public static function load_enabled_addons() {
		$addons = self::addon_definitions();
		$enabled = get_option( self::ADDONS_SETTING, array() );
		if ( !is_array( $enabled ) ) {
			return;
		}
		foreach ( $enabled as $key => $enabled ) {
			if ( $enabled && isset( $addons[$key] ) ) {
				self::load_addon( $key );
			}
		}
	}

	/**
	 * Is Addon enabled
	 * @return void
	 */
	public static function is_addon_anabled( $addon ) {
		$addons = self::addon_definitions();
		$enabled = get_option( self::ADDONS_SETTING, array() );
		if ( !is_array( $enabled ) ) {
			return FALSE;
		}
		foreach ( $enabled as $key => $enabled ) {
			if ( $key == $addon && $enabled && isset( $addons[$key] ) ) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Load an addon
	 * @param string $key Addon slug/key
	 * @return void
	 */
	private static function load_addon( $key ) {
		$addons = self::addon_definitions();
		if ( !isset( $addons[$key] ) || !is_array( $addons[$key]['files'] ) ) {
			return;
		}
		foreach ( $addons[$key]['files'] as $file_path ) {
			require_once $file_path;
		}
		foreach ( (array)$addons[$key]['callbacks'] as $callback ) {
			if ( is_callable( $callback ) ) {
				call_user_func( $callback );
			}
		}
	}


	/**
	 * Inject plugin update information
	 * @param  array $trans plugin information transient
	 * @return array $trans
	 */
	public static function site_transient_update_plugins( $trans ) {
		
		if ( !is_admin() )
			return $trans;
		
		if ( empty( $trans->checked ) )
			return $trans;

		foreach ( $trans->checked as $plugin => $version ) {

			// get addon data
			$token = basename($plugin, ".php");
			$data = self::get_addon_data( $token );

			if ( $data ) {

				// Add addon upgrade data
				if ( version_compare( $version, $data['new_version'], '<' ) ) {
					$trans->response[$plugin]->url = $data['url'];
					$trans->response[$plugin]->slug = $data['slug'];
					$trans->response[$plugin]->package = $data['download_url'];
					$trans->response[$plugin]->new_version = $data['new_version'];
					$trans->response[$plugin]->id = '0';
				}

			}

		}

		return $trans;
	}

	public function plugins_api_result( $response, $action, $api_args ) {
		if ( is_wp_error( $response ) ) {
			
			$refresh = ( 
				( isset( $_GET['action'] ) && $_GET['action'] == 'install-plugin' ) ||
				( isset( $_GET['action'] ) && $_GET['action'] == 'install-theme' )
				) ? TRUE : FALSE;

			$api_data = self::get_addon_data( $api_args->slug, array(), $refresh );
			
			// Is this an addon that GBS has data for?
			if ( $api_data ) {
				$response = new stdClass();
				// set the correct variables
				$response->name = $api_data['post_title'];
				$response->version = $api_data['version'];
				$response->download_link = $api_data['download_url'];
				$response->tested = $api_data['wp_version_tested'];

				// If there's no download url and we're trying to download the addon than something is wrong. Probably it wasn't purchased yet.
				if ( isset( $_GET['action'] ) && $_GET['action'] == 'install-plugin' && $response->download_link == '' ) {
					$response = new WP_Error('plugins_api_failed', __( 'An unexpected error occurred. Something may be wrong with GroupBuyingSite.com, this server&#8217;s configuration or your purchase has not been recorded yet. If you continue to have problems, please try the <a href="http://groupbuyingsite.com/forum/">support forums</a> or download your add-on from your <a href="http://groupbuyingsite.com/account/">account page</a>.' ), 4200 );
				}
			}
		}
		return $response;
	}
	
	public static function get_addon_data( $token = 'pull', $query_args = array(), $fresh = FALSE ) {

		$transient_key = 'gbs_' . substr( md5( serialize( $query_args ) ), -60 );
		// delete_site_transient( $transient_key );
		$addon_data = get_site_transient( $transient_key ); // Look for transient cache
		if ( empty( $addon_data ) || $fresh ) {
			if ( GBS_DEV ) error_log( "get_addon_data not cached: " . print_r( TRUE, true ) );
			$addon_data = self::api_get( 'pull', $query_args, $fresh );
			if ( !$addon_data ) {
				return NULL;
			}
			// Set a transient to cache
			if ( GBS_DEV ) error_log( "set transient: " . print_r( $fresh, true ) );
			set_site_transient( $transient_key, $addon_data, 60*60*24 ); // 60*60*24
		}
		if ( $token != 'pull' ) {
			if ( isset( $addon_data[$token] ) ) {
				return $addon_data[$token];
			}
			return FALSE;
		}
		return $addon_data;
	}
	
	public static function get_addon_info( $addon = '', $fresh = FALSE ) {

		$transient_key = 'gbs_' . substr( md5( serialize( $addon ) ), -60 );
		// delete_site_transient( $transient_key );
		$addon_data = get_site_transient( $transient_key ); // Look for transient cache

		if ( !$addon_info || $fresh ) {
			if ( GBS_DEV ) error_log( "get_addon_info not cached: " . print_r( TRUE, true ) );
			$addon_data = self::api_get( 'info', null, $fresh );
			if ( !$addon_data ) {
				return NULL;
			}
			// Set a transient to cache
			set_site_transient( $transient_key, $addon_info, 60*60*24 );
		}

		if ( $addon != '' ) {
			if ( isset( $addon_data[$addon] ) ) {
				return $addon_data[$addon];
			}
			return FALSE;
		}
		return $addon_data;
	}
	
	public static function get_category_data() {
		$transient_key = 'gbs_addon_categories';
		$categories = get_site_transient( $transient_key ); // Look for transient cache
		if ( !$categories ) {
			if ( GBS_DEV ) error_log( "cats not cached: " . print_r( TRUE, true ) );
			$categories = array();
			$category_data = self::api_get( 'categories' );

			// build a useful
			foreach ( $category_data as $tax_key => $tax_value ) {
				if ( !isset( $categories[$tax_key] ) && $tax_value['count'] > 3 ) {
					$categories[$tax_key] = $tax_value;
					$categories[$tax_key]['weight'] = 1000-$tax_value['count']; // set the weight for sorting
				}
			}
			uasort( $categories, array( get_class(), 'sort_by_weight' ) );

			if ( !$categories ) {
				return NULL;
			}
			// Set a transient to cache
			set_site_transient( $transient_key, $categories, 60*60*24 ); // 60*60*24 // TEST
		}

		return $categories;
	}

	/////////////
	// Add-ons //
	/////////////

	function get_installed_version( $addon_type, $token ) {
		if ( !is_admin() ) return FALSE;
		
		// Get array of plugins or themes
		$items = ( 'theme' == $addon_type ) ? self::get_themes() : self::get_plugins() ;
		
		// Return the Version based on token
		if ( isset( $items[$token]['Version'] ) ) {
			return $items[$token]['Version'];
		}
		
		return FALSE;
	}

	///////////////////////
	// Utility Functions //
	///////////////////////

	public static function get_themes() {
		// Themes are keyed by theme name instead of their directory name
		$themes = wp_get_themes();
		$wp_themes = array();
		foreach ( $themes as $theme ) {
			$key = $theme->get_stylesheet();
			$wp_themes[ $key ] = $theme;
		}
		return $wp_themes;
	}

	/**
	 * get_themes deprecated function
	 * @return  
	 */
	public static function get_themes_dep() {
		$themes = wp_get_themes();
		$wp_themes = array();

		foreach ( $themes as $theme ) {
			$name = $theme->get('Name');
			if ( isset( $wp_themes[ $name ] ) )
				$wp_themes[ $name . '/' . $theme->get_stylesheet() ] = $theme;
			else
				$wp_themes[ $name ] = $theme;
		}

		return $wp_themes;
	}
	
	public static function get_plugins() {
		return get_plugins();
	}
	
	private static function api_args() {
		$args['sslverify'] = false;
		$args['timeout'] = 30;

		$args['headers'] = array(
			'X_GBS_API_KEY' => Group_Buying_Update_Check::get_api_key(),
			'X_GBS_SITE_URL' => home_url(),
			'Referer' => self::current_url(),
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . 'GBSVERSION/' .Group_Buying::GB_VERSION
		);
		
		return $args;
	}
	
	public static function api_post( $action, $body ) {
		$args = self::api_args();
		$args['body'] = $body;
		$url = self::$api_url . trailingslashit( $action );

		$response = wp_remote_post( $url, $args );

		if ( !$response || is_wp_error( $response ) ) {
			return FALSE;
		}
		return json_decode( wp_remote_retrieve_body( $response ), true );
	}
	
	public static function api_get( $action, $url_args = array(), $fresh = FALSE ) {

		// Delay API calls so that if the server is down a function calling api_get doesn't keep requesting data that's not available.
		$delay_key = $action . '_last_api_pull';
		$delay = get_transient( $delay_key );
		if ( $delay ) {
			if ( $delay-time() > 0 ) {
				return FALSE;
			}
		}

		// Build url
		$url = self::$api_url . trailingslashit( $action );
		if ( !empty( $url_args ) ) { // Add query args
			$url = add_query_arg( $url_args, $url );
		}
		
		// remote get
		$response = wp_remote_get( $url, self::api_args() );
		if ( !$response || is_wp_error( $response ) ) {
			// Set transient to delay future calls by 30 minutes, unless explicitly asking for fresh data.
			if ( !$fresh ) {
				set_transient( $delay_key, time()+1800, 1800 );
			}
			return FALSE;
		}
		// API data
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Set transient to delay future calls by 30 minutes, unless explicitly asking for fresh data.
		if ( !$data && !$fresh ) {
			set_transient( $delay_key, time()+1800, 1800 );
		}

		return $data;
	}
	
	public static function current_url() {
		$port = ( $_SERVER['SERVER_PORT'] != '80' ) ? ':' . $_SERVER['SERVER_PORT'] : '';
		return sprintf( 'http%s://%s%s%s', is_ssl(), $_SERVER['SERVER_NAME'], $port, $_SERVER['REQUEST_URI'] );
	}

	/**
	 * Get Settings page
	 *
	 * @static
	 * @return string The ID of the payment settings page
	 */
	public static function get_settings_page() {
		return self::$settings_page;
	}

	/**
	 * Register options
	 * @return void
	 */
	public static function register_settings_fields() {
		if ( !self::addon_definitions() ) {
			return; // nothing to register
		}
		$page = self::$settings_page;
		$section = 'gb__addons_settings';
		add_settings_section( $section, self::__( 'Add-ons' ), array( get_class(), 'display_settings_section' ), $page );
		// Settings
		register_setting( $page, self::ADDONS_SETTING );

		add_settings_field( self::ADDONS_SETTING, self::__( 'Enable Addons' ), array( get_class(), 'display_addons_options' ), $page, $section );
	}

	/**
	 * Display all the addons for selection
	 * @return string inputs of all the addon options
	 */
	public static function display_addons_options() {
		$addons= self::addon_definitions();
		foreach ( $addons as $key => $details ) {
			printf( '<label><input type="checkbox" name="%s[%s]" value="%s" %s /> %s</label><br /><small>%s</small><br/>', self::ADDONS_SETTING, $key, $key, checked( TRUE, self::is_enabled( $key ), FALSE ), $details['label'], $details['description'] );
		}
	}

	/**
	 * Is addon enabled
	 * @param string  $addon key/slug of addon to check
	 * @return boolean		
	 */
	public static function is_enabled( $addon ) {
		$enabled = get_option( self::ADDONS_SETTING, array() );
		if ( isset( $enabled[$addon] ) && $enabled[$addon] ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Show addons marketplace iframe
	 * @return string
	 */
	public static function shop_view() {
		?>
			<div class="wrap">
				<?php screen_icon(); ?>
				<h2 class="nav-tab-wrapper">
					<?php self::display_admin_tabs(); ?>
				</h2>

				<?php 
					if ( isset( $_GET['addon_detail'] ) && $_GET['addon_detail'] ) {
						self::addon_detail( $_GET['addon_detail'] );
					}
					else {
						self::marketplace_list();
					}
				 ?>

			</div>
		<?php
		prp($addon_data);
	}	

	public function marketplace_list() {
		$query_args = array();
		if ( isset( $_GET['category_filter'] ) ) {
			$query_args['category'] = $_GET['category_filter'];
		}
		if ( isset( $_GET['order_by'] ) ) {
			$query_args['order_by'] = $_GET['order_by'];
		}
		$addon_data = self::get_addon_data( 'pull', $query_args );

		$categories = self::get_category_data();
		$i = 0;
		$total_cateogries = count( $categories );

		if ( $addon_data ) {
			?>
				<?php if ( $total_cateogries ): ?>
					<ul class="subsubsub clearfix">
						<li><strong><?php self::_e('Categories:') ?></strong></li>
						<li class="all"><a href="<?php echo remove_query_arg( array('category_filter', 'order_by') ) ?>"><?php self::_e('All') ?> (60+)</a> |</li>
						<li class="all"><a href="<?php echo add_query_arg( 'order_by', 'popularity', remove_query_arg( 'category_filter') ) ?>"><?php self::_e('Most Popular') ?></a> |</li>
						<?php foreach ( $categories as $key => $value ) : ?>
							<li class="<?php echo $value['slug'] ?>"><a href="<?php echo add_query_arg( 'category_filter', $value['term_id'], remove_query_arg( 'order_by') ) ?>"><?php echo $value['name'] ?> (<?php echo $value['count'] ?>)</a> <?php $i++; if ( $i < $total_cateogries ) echo "|"; ?></li>
						<?php endforeach ?>
					</ul>
					
					<ul class="subsubsub clearfix">
						<li><strong><?php self::_e('Filter:') ?></strong></li>
						<li class="all"><a href="<?php echo add_query_arg( 'order_by', 'popularity' ) ?>"><?php self::_e('Popularity') ?></a> |</li>
						<li class="all"><a href="<?php echo add_query_arg( 'order_by', 'price' ) ?>"><?php self::_e('Price') ?></a></li>
					</ul>
				<?php endif ?>

				<div id="addon_marketplace" class="item_columns clearfix">
					<?php foreach ( $addon_data as $addon => $keys ): ?>
						<div id="add_on-<?php echo $addon ?>" class="item <?php if ( self::has_purchased( $addon ) ) echo 'purchased ' ?>clearfix">
							<div class="item_thumb clearfix">
								<a href="<?php echo add_query_arg( 'addon_detail', $addon ) ?>"><img src="<?php echo $keys['post_thumbnail'][0] ?>"></a>
								<?php if ( $keys['alt_price'] ): ?>
									<span class="price">$<?php echo $keys['alt_price'] ?></span>
								<?php else: ?>
									<span class="price">$<?php echo $keys['price'] ?></span>
								<?php endif ?>
							</div><!--  .item_thumb -->
							<div class="item_info clearfix">
								<h3><a href="<?php echo add_query_arg( 'addon_detail', $addon ) ?>"><?php echo gb_get_html_truncation( 40, $keys['post_title'] ) ?></a></h3>
								<span class="information"><?php echo $keys['amount_saved'] ?></span>
							</div><!--  .item_info -->
							<div class="item_links clearfix">
								<span class="details"><a href="<?php echo add_query_arg( 'addon_detail', $addon ) ?>"><?php self::_e('Details') ?></a></span>
								<?php if ( self::has_purchased( $addon ) ) : ?>
									<span class="activate_addon addon_mp_button"><a href="<?php echo self::addon_install_url( $addon ) ?>"><?php self::_e('Install') ?></a></span>
								<?php else: ?>
									<span class="purchase_addon addon_mp_button"><a href="<?php echo self::mp_purchase_url( $addon, $keys['id'] ) ?>" class="mp_purchase"><?php self::_e('Purchase') ?></a></span>
								<?php endif ?>
							</div><!--  .item_links -->
						</div><!-- #add_on-php.item -->
					<?php endforeach ?>
				</div><!-- #addon_marketplace.item_columns -->
			<?php
		}
		else {
			echo '<div class="error fade"><p>Our apologies an unexpected error occurred. Try back later.</p></div>';
		}
		
	}

	public function addon_detail( $addon ) {
		$data = self::get_addon_data( $addon );

		if ( $data ) {
			wp_enqueue_script('thickbox');
			wp_enqueue_style('thickbox');
			// prp($data);
			?>
				<div id="addon_marketplace" class="single clearfix">
					<h2><?php echo $data['post_title'] ?></h2>
					<div class="item_wrap clearfix">
						<div class="item_data clearfix">
							<div class="item_thumb clearfix">
								<img src="<?php echo $data['post_thumbnail'][0] ?>">
								<?php if ( $keys['alt_price'] ): ?>
									<span class="price">$<?php echo $data['alt_price'] ?></span>
								<?php else: ?>
									<span class="price">$<?php echo $data['price'] ?></span>
								<?php endif ?>
							</div><!--  .item_thumb -->

							<div class="add_info clearfix">
								<span class="support"><a href="<?php echo $data['support_link'] ?>" target="_blank"><?php self::_e('Support') ?></a></span> | 
								<span class="url"><a href="<?php echo $data['url'] ?>" target="_blank"><?php self::_e('Marketplace') ?></a></span>
								<?php if ( self::has_purchased( $addon ) ) : ?>
									<span class="activate_addon addon_mp_button"><a href="<?php echo self::addon_install_url( $addon ) ?>"><?php self::_e('Activate') ?></a></span>
								<?php else: ?>
									<span class="purchase_addon addon_mp_button"><a href="<?php echo self::mp_purchase_url( $addon, $data['id'] ) ?>" class="mp_purchase"><?php self::_e('Purchase') ?></a></span>
								<?php endif ?>
							</div><!--  .add_info -->

							<div class="item_attachements clearfix">
								<h3><?php self::_e('Attachments:') ?></h3>
								<?php foreach ( $data['attachments'] as $key => $value): ?>
									<?php 
										$id = $value['ID'];
										$thumb = $data['thumbs'][$id]['guid'][0];
										$thickbox_class = ( FALSE === strpos( $value['post_mime_type'], 'image' ) ) ? 'no_tb' : 'thickbox' ;
										?>
									<a href="<?php echo $value['guid'] ?>" class="<?php echo $thickbox_class ?>"><img src="<?php echo $thumb ?>"></a>
								<?php endforeach ?>
							</div><!--  .item_attachements -->

							<div class="item_categories clearfix">
								<h3><?php self::_e('Categories:') ?></h3>
								<ul class="item_categories_list">
									<?php foreach ( $data['categories'] as $key => $value ): ?>
										<li class="<?php echo $value['slug'] ?>"><a href="<?php echo remove_query_arg( array( 'addon_detail' ), add_query_arg( 'category_filter', $value['term_id'] ) ) ?>"><?php echo $value['name'] ?></a></li>
									<?php endforeach ?>
								</ul>
							</div><!--  .item_categories -->

						</div><!--  .item_data -->

						<div class="item_content clearfix">
							<?php
								// WP_Embed::shortcode will not proceed without a postID
								global $post;
								$post->ID = PHP_INT_MAX - $data['id']; // Make sure the id is unique so that it can be cached without conflict
								echo apply_filters('the_content', $data['content'] ); 
								?>
						</div><!--  .item_content -->
					</div><!--  .item_wrap -->
				</div><!-- #addon_marketplace_single.-->
			<?php
		}
		else {
			echo '<div class="error fade"><p>Our apologies an unexpected error occurred. Try back later.</p></div>';
		}
	}

	public function has_purchased( $addon ) {
		$data = self::get_addon_data( $addon );
		return isset( $data['package'] ) ;
	}

	public function mp_purchase_url( $addon, $id ) {
		$purchase_url = add_query_arg( array( 'add_to_cart' => $id, 'mini_cart' => 1, 'install_url' => urlencode( self::addon_install_url( $addon ) ) ), self::$cart_url );
		return $purchase_url;
	}

	public function addon_install_url( $addon ) {
		$data = self::get_addon_data( $addon );
		$context = ( isset( $data['is_theme'] ) && $data['is_theme'] != '' ) ? 'theme' : 'plugin';
		$install_url = add_query_arg( array(
			'action' => 'install-'.$context,
			$context  => $addon,
		), self_admin_url( 'update.php' ) );

		return esc_url( wp_nonce_url( $install_url, 'install-'.$context.'_' . $addon ) );
	}

	function upgrade_popup() {
		if ( FALSE === strpos( $_GET['plugin'], 'gbs' ) )
			return;
		
		$plugin = basename( $_GET['plugin'], ".php" );
		$data = self::get_addon_info( $plugin );
		if ( !empty( $data['update_info'] ) ) {
			print $data['update_info'];
			exit;
		}
	}
}
