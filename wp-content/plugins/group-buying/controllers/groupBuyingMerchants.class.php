<?php

/**
 * Merchant controller
 *
 * @package GBS
 * @subpackage Merchant
 */
class Group_Buying_Merchants extends Group_Buying_Controller {
	const MERCHANT_PATH_OPTION = 'gb_merchant_path';
	const MERCHANT_QUERY_VAR = 'gb_account_merchant';
	private static $merchant_path = 'merchant';
	private static $instance;

	public static function init() {
		self::$merchant_path = get_option( self::MERCHANT_PATH_OPTION, self::$merchant_path );
		self::register_path_callback( self::$merchant_path, array( get_class(), 'on_account_merchant_page' ), self::MERCHANT_QUERY_VAR, 'merchant' );

		add_action( 'add_meta_boxes', array( get_class(), 'add_meta_boxes' ) );
		add_action( 'save_post', array( get_class(), 'save_meta_boxes' ), 10, 2 );
		add_filter( 'template_include', array( get_class(), 'override_template' ) );
		add_filter( 'gb_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 10, 1 );
		add_action( 'pre_get_posts', array( get_class(), 'edit_query' ), 9, 1 );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 50, 0 );

		// Admin columns
		add_filter ( 'manage_edit-'.Group_Buying_Merchant::POST_TYPE.'_columns', array( get_class(), 'register_columns' ) );
		add_filter ( 'manage_'.Group_Buying_Merchant::POST_TYPE.'_posts_custom_column', array( get_class(), 'column_display' ), 10, 2 );
		add_filter( 'manage_edit-'.Group_Buying_Merchant::POST_TYPE.'_sortable_columns', array( get_class(), 'sortable_columns' ) );

		// Init other classes, this allows for the paths to be changed
		add_action( 'init', array( get_class(), 'wp_init' ) );
	}

	public static function wp_init() {
		// Initialize the classes for each of the form pages
		Group_Buying_Merchants_Registration::init();
		Group_Buying_Merchants_Edit::init();
		Group_Buying_Merchants_Dashboard::init();
		Group_Buying_Merchants_Voucher_Claim::init();
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_merchant_paths';
		add_settings_section( $section, null, array( get_class(), 'display_merchant_paths_section' ), $page );

		// Settings
		register_setting( $page, self::MERCHANT_PATH_OPTION );
		add_settings_field( self::MERCHANT_PATH_OPTION, self::__( 'Merchant Path' ), array( get_class(), 'display_merchant_path' ), $page, $section ); // TODO add this back when the router is used.
	}

	public static function display_merchant_paths_section() {
		echo self::__( '<h4>Customize the Merchant paths.</h4>' );
	}

	public static function display_merchant_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="'.self::MERCHANT_PATH_OPTION.'" id="'.self::MERCHANT_PATH_OPTION.'" value="' . esc_attr( self::$merchant_path ) . '"  size="40"/><br />';
	}

	public static function edit_query( $query ) {
		if ( isset( $query->query_vars[self::MERCHANT_QUERY_VAR] ) && $query->query_vars[self::MERCHANT_QUERY_VAR] ) {
			$merchant = Group_Buying_Merchant::get_merchant_id_for_user();
			$query->query_vars['post_type'] = Group_Buying_Merchant::POST_TYPE;
			$query->query_vars['post_status'] = 'draft,publish';
			$query->query_vars['p'] = $merchant;
		}
		// we only care if this is the query to show a the edit profile form
		if ( isset( $query->query_vars['post_type'] ) && Group_Buying_Merchant::POST_TYPE == $query->query_vars['post_type'] ) {
			$blank_merchant = Group_Buying_Merchant::blank_merchant();
			if ( isset( $query->query_vars['post__not_in'] ) && !empty( $query->query_vars['post__not_in'] ) ) {
				$query->query_vars['post__not_in'][] = $blank_merchant;
			} else {
				$query->query_vars['post__not_in'] = array( $blank_merchant );
			}
		}
	}

	public static function on_account_merchant_page() {
		// Unregistered users shouldn't be here
		self::login_required();
		self::get_instance();
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
		self::do_not_cache(); // never cache the merchant account page
		if ( isset( $_POST['gb_merchant_action'] ) && $_POST['gb_merchant_action'] == self::FORM_ACTION ) {
			$this->process_form_submission();
		}
		add_action( 'the_post', array( $this, 'view_account_merchant' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'get_title' ), 10, 2 );
	}

	public function view_account_merchant( $post ) {
		if ( $post->post_type == Group_Buying_Merchant::POST_TYPE ) {
			remove_filter( 'the_content', 'wpautop' );
			$blank_merchant = Group_Buying_Merchant::blank_merchant();
			$merchant = Group_Buying_Merchant::get_instance( $post->ID );
			if ( $post->ID == $blank_merchant ) {
				$view = self::load_view_to_string( 'merchant/info-none', array() );
			} elseif ( 'draft' == $post->post_status ) {
				$view = self::load_view_to_string( 'merchant/info-pending', array() );
			} else {
				$view = self::load_view_to_string( 'merchant/info-published', array( 'fields' => $this->merchant_contact_info_fields( $merchant ) ) );
			}
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
		if ( $post->post_type == Group_Buying_Merchant::POST_TYPE ) {
			return self::__( "Account Merchant" );
		}
		return $title;
	}

	public static function add_meta_boxes() {
		add_meta_box( 'gb_merchant_details', self::__( 'Merchant Details' ), array( get_class(), 'show_meta_box' ), Group_Buying_Merchant::POST_TYPE, 'advanced', 'high' );
		add_meta_box( 'gb_merchant_authorized_users', self::__( 'Authorized Users' ), array( get_class(), 'show_meta_box' ), Group_Buying_Merchant::POST_TYPE, 'advanced', 'high' );
	}

	public static function show_meta_box( $post, $metabox ) {
		$merchant = Group_Buying_Merchant::get_instance( $post->ID );
		switch ( $metabox['id'] ) {
		case 'gb_merchant_details':
			self::show_meta_box_gb_merchant_details( $merchant, $post, $metabox );
			break;
		case 'gb_merchant_authorized_users':
			self::show_meta_box_gb_merchant_authorized_users( $merchant, $post, $metabox );
			break;
		default:
			self::unknown_meta_box( $metabox['id'] );
			break;
		}
	}

	public static function save_meta_boxes( $post_id, $post ) {
		// only continue if it's a deal post
		if ( $post->post_type != Group_Buying_Merchant::POST_TYPE ) {
			return;
		}
		// don't do anything on autosave, auto-draft, bulk edit, or quick edit
		if ( wp_is_post_autosave( $post_id ) || $post->post_status == 'auto-draft' || defined( 'DOING_AJAX' ) || isset( $_GET['bulk_edit'] ) ) {
			return;
		}
		// save all the meta boxes
		$merchant = Group_Buying_Merchant::get_instance( $post_id );
		self::save_meta_box_gb_merchant_details( $merchant, $post_id, $post );
		self::save_meta_box_gb_merchant_authorized_users( $merchant, $post_id, $post );
	}

	/**
	 * Display the deal details meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $merchant
	 * @param int     $post
	 * @param array   $metabox
	 * @return void
	 */
	private static function show_meta_box_gb_merchant_details( Group_Buying_Merchant $merchant, $post, $metabox ) {
		$contact_name = $merchant->get_contact_name();
		$contact_title = $merchant->get_contact_title();
		$contact_street = $merchant->get_contact_street();
		$contact_city = $merchant->get_contact_city();
		$contact_state = $merchant->get_contact_state();
		$contact_postal_code = $merchant->get_contact_postal_code();
		$contact_country = $merchant->get_contact_country();
		$contact_phone = $merchant->get_contact_phone();
		$website = $merchant->get_website();
		$facebook = $merchant->get_facebook();
		$twitter = $merchant->get_twitter();

		self::load_view( 'meta_boxes/merchant-details', array(
				'contact_name' => is_null( $contact_name ) ? '' : $contact_name,
				'contact_title' => is_null( $contact_title ) ? '' : $contact_title,
				'contact_street' => is_null( $contact_street ) ? '' : $contact_street,
				'contact_city' => is_null( $contact_city ) ? '' : $contact_city,
				'contact_state' => is_null( $contact_state ) ? '' : $contact_state,
				'contact_postal_code' => is_null( $contact_postal_code ) ? '' : $contact_postal_code,
				'contact_country' => is_null( $contact_country ) ? '' : $contact_country,
				'contact_phone' => is_null( $contact_phone ) ? '' : $contact_phone,
				'website' => is_null( $website ) ? '' : $website,
				'facebook' => is_null( $facebook ) ? '' : $facebook,
				'twitter' => is_null( $twitter ) ? '' : $twitter
			) );
	}

	private static function show_meta_box_gb_merchant_authorized_users( Group_Buying_Merchant $merchant, $post, $metabox ) {
		$authorized_users = $merchant->get_authorized_users();
		$args = apply_filters( 'gb_get_users_args', null );
		$users = get_users( $args );
		self::load_view( 'meta_boxes/merchant-authorized-users', array(
				'authorized_users' => $authorized_users,
				'users' => $users
			) );
	}

	/**
	 * Save the deal details meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $merchant
	 * @param int     $post_id
	 * @param object  $post
	 * @return void
	 */
	private static function save_meta_box_gb_merchant_details( Group_Buying_Merchant $merchant, $post_id, $post ) {
		$contact_name = isset( $_POST['contact_name'] ) ? $_POST['contact_name'] : '';
		$contact_title = isset( $_POST['contact_title'] ) ? $_POST['contact_title'] : '';
		$contact_street = isset( $_POST['contact_street'] ) ? $_POST['contact_street'] : '';
		$contact_city = isset( $_POST['contact_city'] ) ? $_POST['contact_city'] : '';
		$contact_state = isset( $_POST['contact_state'] ) ? $_POST['contact_state'] : '';
		$contact_postal_code = isset( $_POST['contact_postal_code'] ) ? $_POST['contact_postal_code'] : '';
		$contact_country = isset( $_POST['contact_country'] ) ? $_POST['contact_country'] : '';
		$contact_phone = isset( $_POST['contact_phone'] ) ? $_POST['contact_phone'] : '';
		$website = isset( $_POST['website'] ) ? esc_url( $_POST['website'] ) : '';
		$facebook = isset( $_POST['facebook'] ) ? esc_url( $_POST['facebook'] ) : '';
		$twitter = isset( $_POST['twitter'] ) ? esc_url( $_POST['twitter'] ) : '';

		$merchant->set_contact_name( $contact_name );
		$merchant->set_contact_title( $contact_title );
		$merchant->set_contact_street( $contact_street );
		$merchant->set_contact_city( $contact_city );
		$merchant->set_contact_state( $contact_state );
		$merchant->set_contact_postal_code( $contact_postal_code );
		$merchant->set_contact_country( $contact_country );
		$merchant->set_contact_phone( $contact_phone );
		$merchant->set_website( $website );
		$merchant->set_facebook( $facebook );
		$merchant->set_twitter( $twitter );
	}

	private static function save_meta_box_gb_merchant_authorized_users( Group_Buying_Merchant $merchant, $post_id, $post ) {
		if ( isset( $_POST['authorized_user'] ) && ( $_POST['authorized_user'] != '' ) ) {
			$authorized_user = $_POST['authorized_user'];
			$merchant->authorize_user( $authorized_user );
		}
		if ( isset( $_POST['unauthorized_user'] ) && ( $_POST['unauthorized_user'] != '' ) ) {
			$unauthorized_user = $_POST['unauthorized_user'];
			$merchant->unauthorize_user( $unauthorized_user );
		}
	}

	public static function register_columns( $columns ) {
		unset( $columns['date'] );
		unset( $columns['author'] );
		$columns['authorized'] = __( 'Authorized' );
		$columns['phone'] = __( 'Contact Phone' );
		$columns['website'] = __( 'Website' );
		$columns['date'] = __( 'Published' );
		return $columns;
	}

	public static function column_display( $column_name, $id ) {
		$merchant = Group_Buying_Merchant::get_instance( $id );

		if ( !$merchant )
			return; // return for that temp post

		switch ( $column_name ) {
		case 'authorized':
			$authorized_users = $merchant->get_authorized_users();
			foreach ( $authorized_users as $user_id ) {
				$user = get_userdata( $user_id );
				$display = $user->user_firstname . ' ' . $user->user_lastname;
				if ( ' ' == $display ) {
					$display = $user->user_login;
				}
				if ( !empty( $user->user_email ) ) {
					$display .= " (".$user->user_email.")";
				}
			}
			echo $display;
			break;
		case 'phone':
			echo $merchant->get_contact_phone();
			break;
		case 'website':
			echo '<a href="'. $merchant->get_website().'">'.$merchant->get_website().'</a>';
			break;
		default:
			break;
		}
	}

	public function sortable_columns( $columns ) {
		$columns['id'] = 'id';
		return $columns;
	}

	public static function override_template( $template ) {
		if ( Group_Buying_Merchant::is_merchant_query() ) {
			if ( is_single() ) {
				$template = self::locate_template( array(
						'business/business.php',
						'business/single.php',
						'merchant/business.php',
						'merchant/single.php'
					), $template );
			} elseif ( is_archive() ) {
				$template = self::locate_template( array(
						'business/businesses.php',
						'business/index.php',
						'business/archive.php',
						'business/business-index.php',
						'business/business-archive.php',
						'merchant/businesses.php',
						'merchant/index.php',
						'merchant/archive.php',
						'merchant/business-index.php',
						'merchant/business-archive.php',
					), $template );
			}
		}
		if ( Group_Buying_Merchant::is_merchant_tax_query() ) {
			$taxonomy = get_query_var( 'taxonomy' );
			$template = self::locate_template( array(
					'business/business-'.$taxonomy.'.php',
					'business/business-type.php',
					'business/business-types.php',
					'business/businesses.php',
					'business/business-index.php',
					'business/business-archive.php',
					'business/archive.php',
					'merchant/business-'.$taxonomy.'.php',
					'merchant/business-type.php',
					'merchant/business-types.php',
					'merchant/businesses.php',
					'merchant/business-index.php',
					'merchant/business-archive.php',
					'merchant/archive.php',
				), $template );
		}
		return $template;
	}

	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'edit_merchants',
			'title' => self::__( 'Edit Merchants' ),
			'href' => admin_url( 'edit.php?post_type='.Group_Buying_Merchant::POST_TYPE ),
			'weight' => 10,
		);
		return $items;
	}

	protected function validate_merchant_contact_info_fields( $submitted ) {
		$errors = array();
		$fields = $this->merchant_contact_info_fields();
		foreach ( $fields as $key => $data ) {
			if ( isset( $data['required'] ) && $data['required'] && !( isset( $submitted['gb_contact_'.$key] ) && $submitted['gb_contact_'.$key] != '' ) ) {
				$errors[] = sprintf( self::__( '"%s" field is required.' ), $data['label'] );
			}
		}
		return $errors;
	}

	protected function merchant_contact_info_fields( Group_Buying_Merchant $merchant = null ) {
		$fields = $this->get_standard_address_fields();

		unset( $fields['first_name'] );
		unset( $fields['last_name'] );

		$fields['merchant_title'] = array(
			'weight' => 0,
			'label' => self::__( 'Merchant Name' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => ''
		);

		$fields['merchant_description'] = array(
			'weight' => 5,
			'label' => self::__( 'Merchant Description' ),
			'type' => 'textarea',
			'required' => TRUE,
			'default' => ''
		);

		$fields['merchant_thumbnail'] = array(
			'weight' => 7,
			'label' => self::__( 'Merchant Image' ),
			'type' => 'file',
			'required' => FALSE,
			'default' => '',
			'description' => gb__('<span>Optional:</span> Featured image for the merchant.')
		);

		$fields['name'] = array(
			'weight' => 11,
			'label' => self::__( 'Contact Name' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => '',
		);
		/*/
		$fields['title'] = array(
			'weight' => 5,
			'label' => self::__('Contact Title'),
			'type' => 'text',
			'required' => TRUE,
			'default' => '',
		);
		/**/
		$fields['phone'] = array(
			'weight' => 16,
			'label' => self::__( 'Contact Phone' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => '',
		);

		$fields['website'] = array(
			'weight' => 26,
			'label' => self::__( 'Website' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => '',
		);
		$fields['facebook'] = array(
			'weight' => 27,
			'label' => self::__( 'Facebook' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => '',
		);
		$fields['twitter'] = array(
			'weight' => 28,
			'label' => self::__( 'Twitter' ),
			'type' => 'text',
			'required' => FALSE,
			'default' => '',
		);

		if ( is_a( $merchant, 'Group_Buying_Merchant' ) ) {
			$merchant_post = $merchant->get_post();
			$fields['merchant_title']['default'] = $merchant_post->post_title;
			$fields['merchant_description']['default'] = $merchant_post->post_content;
			$fields['name']['default'] = $merchant->get_contact_name();
			$fields['street']['default'] = $merchant->get_contact_street();
			$fields['city']['default'] = $merchant->get_contact_city();
			$fields['zone']['default'] = $merchant->get_contact_state();
			$fields['postal_code']['default'] = $merchant->get_contact_postal_code();
			$fields['country']['default'] = $merchant->get_contact_country();
			$fields['phone']['default'] = $merchant->get_contact_phone();
			$fields['website']['default'] = $merchant->get_website();
			$fields['facebook']['default'] = $merchant->get_facebook();
			$fields['twitter']['default'] = $merchant->get_twitter();


			$img_array = wp_get_attachment_image_src(get_post_thumbnail_id( $merchant->get_id() ));
			$fields['merchant_thumbnail']['default'] = $img_array[0];
		}

		$fields = apply_filters( 'gb_merchant_register_contact_info_fields', $fields, $merchant );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$merchant_path );
		} else {
			return add_query_arg( self::MERCHANT_QUERY_VAR, 1, home_url() );
		}
	}
}

class Group_Buying_Merchants_Registration extends Group_Buying_Merchants{
	const REGISTER_PATH_OPTION = 'gb_merchant_register_path';
	const REGISTER_QUERY_VAR = 'gb_merchant_register';
	const FORM_ACTION = 'gb_merchant_register';
	private static $register_path = 'merchant/register';
	private static $instance;

	public static function init() {
		self::$register_path = get_option( self::REGISTER_PATH_OPTION, self::$register_path );
		self::register_path_callback( self::$register_path, array( get_class(), 'on_registration_page' ), self::REGISTER_QUERY_VAR, 'merchant/register' );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 1 );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_merchant_paths';

		// Settings
		register_setting( $page, self::REGISTER_PATH_OPTION );
		add_settings_field( self::REGISTER_PATH_OPTION, self::__( 'Merchant Registration Path' ), array( get_class(), 'display_merchant_registration_path' ), $page, $section );
	}

	public static function display_merchant_registration_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::REGISTER_PATH_OPTION . '" id="' . self::REGISTER_PATH_OPTION . '" value="' . esc_attr( self::$register_path ) . '" size="40"/><br />';
	}

	public static function on_registration_page() {
		// Unregistered users shouldn't be here
		self::login_required();
		self::get_instance();
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
		if ( isset( $_POST['gb_merchant_action'] ) && $_POST['gb_merchant_action'] == self::FORM_ACTION ) {
			$this->process_form_submission();
		}
		add_action( 'pre_get_posts', array( get_class(), 'edit_query' ), 10, 1 );
		add_action( 'the_post', array( $this, 'view_registration_form' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'get_title' ), 10, 2 );
	}

	public static function edit_query( $query ) {
		// we only care if this is the query to show a the edit profile form
		if ( isset( $query->query_vars[self::REGISTER_QUERY_VAR] ) && $query->query_vars[self::REGISTER_QUERY_VAR] ) {
			$query->query_vars['post_type'] = Group_Buying_Merchant::POST_TYPE;
			$query->query_vars['post_status'] = 'draft,publish';
			$query->query_vars['p'] = Group_Buying_Merchant::blank_merchant();
		}
	}

	public function view_registration_form( $post ) {
		if ( $post->post_type == Group_Buying_Merchant::POST_TYPE ) {
			remove_filter( 'the_content', 'wpautop' );
			$view = self::load_view_to_string( 'merchant/register', array( 'fields' => $this->merchant_contact_info_fields() ) );
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
		if ( $post->post_type == Group_Buying_Merchant::POST_TYPE ) {
			return self::__( "Register Merchant" );
		}
		return $title;
	}

	private function process_form_submission() {
		$errors = array();
		$title = isset( $_POST['gb_contact_merchant_title'] ) ? esc_html( $_POST['gb_contact_merchant_title'] ) : '';
		$allowed_tags = wp_kses_allowed_html( 'post' );
		$allowed_tags['iframe'] = array(
			'width' => true,
			'height' => true,
			'src' => true,
			'frameborder' => true,
			'webkitAllowFullScreen' => true,
			'mozallowfullscreen' => true,
			'allowfullscreen' => true
		);
		$content = isset( $_POST['gb_contact_merchant_description'] ) ? wp_kses( $_POST['gb_contact_merchant_description'], $allowed_tags ) : '';
		$contact_title = isset( $_POST['gb_contact_title'] ) ? esc_html( $_POST['gb_contact_title'] ) : '';
		$contact_name = isset( $_POST['gb_contact_name'] ) ? esc_html( $_POST['gb_contact_name'] ) : '';
		$contact_street = isset( $_POST['gb_contact_street'] ) ? esc_html( $_POST['gb_contact_street'] ) : '';
		$contact_city = isset( $_POST['gb_contact_city'] ) ? esc_html( $_POST['gb_contact_city'] ) : '';
		$contact_state = isset( $_POST['gb_contact_zone'] ) ? esc_html( $_POST['gb_contact_zone'] ) : '';
		$contact_postal_code = isset( $_POST['gb_contact_postal_code'] ) ? esc_html( $_POST['gb_contact_postal_code'] ) : '';
		$contact_country = isset( $_POST['gb_contact_country'] ) ? esc_html( $_POST['gb_contact_country'] ) : '';
		$contact_phone = isset( $_POST['gb_contact_phone'] ) ? esc_html( $_POST['gb_contact_phone'] ) : '';
		$website = isset( $_POST['gb_contact_website'] ) ? esc_url( $_POST['gb_contact_website'] ) : '';
		$facebook = isset( $_POST['gb_contact_facebook'] ) ? esc_url( $_POST['gb_contact_facebook'] ) : '';
		$twitter = isset( $_POST['gb_contact_twitter'] ) ? esc_url( $_POST['gb_contact_twitter'] ) : '';
		$errors = array_merge( $errors, $this->validate_merchant_contact_info_fields( $_POST ) );
		$errors = apply_filters( 'gb_validate_merchant_registration', $errors, $_POST );
		if ( !empty( $errors ) ) {
			foreach ( $errors as $error ) {
				self::set_message( $error, self::MESSAGE_STATUS_ERROR );
			}
			return FALSE;
		} else {
			$post_id = wp_insert_post( array(
					'post_status' => 'draft',
					'post_type' => Group_Buying_Merchant::POST_TYPE,
					'post_title' => $title,
					'post_content' => $content
				) );
			$merchant = Group_Buying_Merchant::get_instance( $post_id );
			$merchant->set_contact_name( $contact_name );
			$merchant->set_contact_title( $contact_title );
			$merchant->set_contact_street( $contact_street );
			$merchant->set_contact_city( $contact_city );
			$merchant->set_contact_state( $contact_state );
			$merchant->set_contact_postal_code( $contact_postal_code );
			$merchant->set_contact_country( $contact_country );
			$merchant->set_contact_phone( $contact_phone );
			$merchant->set_website( $website );
			$merchant->set_facebook( $facebook );
			$merchant->set_twitter( $twitter );
			$merchant->authorize_user( get_current_user_id() );

			do_action( 'register_merchant', $merchant );

			if ( !empty( $_FILES['gb_contact_merchant_thumbnail'] ) ) {
				// Set the uploaded field as an attachment
				$merchant->set_attachement( $_FILES );
			}

			do_action( 'gb_admin_notification', array( 'subject' => self::__( 'New Merchant Registration' ), 'content' => self::__( 'A user has registered as a merchant and needs your review.' ), $merchant ) );

			$url = Group_Buying_Merchants::get_url();
			$url = add_query_arg( 'message', 'registered', $url );
			self::set_message( __( 'Merchant Registration Submitted for Review.' ), self::MESSAGE_STATUS_INFO );
			wp_redirect( $url, 303 );
			exit();
		}
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$register_path );
		} else {
			return add_query_arg( self::REGISTER_QUERY_VAR, 1, home_url() );
		}
	}
}

class Group_Buying_Merchants_Edit extends Group_Buying_Merchants {
	const EDIT_PATH_OPTION = 'gb_merchant_edit_path';
	const EDIT_QUERY_VAR = 'gb_merchant_edit';
	const FORM_ACTION = 'gb_merchant_edit';
	private static $edit_path = 'merchant/edit';
	private static $instance;

	public static function init() {
		self::$edit_path = get_option( self::EDIT_PATH_OPTION, self::$edit_path );
		self::register_path_callback( self::$edit_path, array( get_class(), 'on_edit_page' ), self::EDIT_QUERY_VAR, 'merchant/edit' );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 1 );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_merchant_paths';

		// Settings
		register_setting( $page, self::EDIT_PATH_OPTION );
		add_settings_field( self::EDIT_PATH_OPTION, self::__( 'Merchant Edit Path' ), array( get_class(), 'display_merchant_edit_path' ), $page, $section );
	}

	public static function display_merchant_edit_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::EDIT_PATH_OPTION . '" id="' . self::EDIT_PATH_OPTION . '" value="' . esc_attr( self::$edit_path ) . '" size="40"/><br />';
	}

	public static function on_edit_page() {
		// Unregistered users shouldn't be here
		self::login_required();
		self::get_instance();
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
		if ( isset( $_POST['gb_merchant_action'] ) && $_POST['gb_merchant_action'] == self::FORM_ACTION ) {
			$this->process_form_submission();
		}
		add_action( 'pre_get_posts', array( get_class(), 'edit_query' ), 10, 1 );
		add_action( 'the_post', array( $this, 'view_edit_form' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'get_title' ), 10, 2 );
	}

	public static function edit_query( $query ) {
		// we only care if this is the query to show a the edit profile form
		if ( isset( $query->query_vars[self::EDIT_QUERY_VAR] ) && $query->query_vars[self::EDIT_QUERY_VAR] ) {
			$query->query_vars['post_type'] = Group_Buying_Merchant::POST_TYPE;
			$query->query_vars['post_status'] = 'draft,publish';
			$query->query_vars['p'] = Group_Buying_Merchant::get_merchant_id_for_user();
		}
	}

	public function view_edit_form( $post ) {
		if ( $post->post_type == Group_Buying_Merchant::POST_TYPE ) {
			$merchant_id = Group_Buying_Merchant::get_merchant_id_for_user();
			$merchant = Group_Buying_Merchant::get_instance( $merchant_id );
			remove_filter( 'the_content', 'wpautop' );
			$view = self::load_view_to_string( 'merchant/edit', array( 'fields' => $this->merchant_contact_info_fields( $merchant ) ) );
			global $pages;
			$pages = array( $view );
		}
	}

	private function process_form_submission() {
		$errors = array();
		$title = isset( $_POST['gb_contact_merchant_title'] ) ? esc_html( $_POST['gb_contact_merchant_title'] ) : '';
		$allowed_tags = wp_kses_allowed_html( 'post' );
		$allowed_tags['iframe'] = array(
			'width' => true,
			'height' => true,
			'src' => true,
			'frameborder' => true,
			'webkitAllowFullScreen' => true,
			'mozallowfullscreen' => true,
			'allowfullscreen' => true
		);
		$content = isset( $_POST['gb_contact_merchant_description'] ) ? wp_kses( $_POST['gb_contact_merchant_description'], $allowed_tags ) : '';
		$contact_title = isset( $_POST['gb_contact_title'] ) ? esc_html( $_POST['gb_contact_title'] ) : '';
		$contact_name = isset( $_POST['gb_contact_name'] ) ? esc_html( $_POST['gb_contact_name'] ) : '';
		$contact_street = isset( $_POST['gb_contact_street'] ) ? esc_html( $_POST['gb_contact_street'] ) : '';
		$contact_city = isset( $_POST['gb_contact_city'] ) ? esc_html( $_POST['gb_contact_city'] ) : '';
		$contact_state = isset( $_POST['gb_contact_zone'] ) ? esc_html( $_POST['gb_contact_zone'] ) : '';
		$contact_postal_code = isset( $_POST['gb_contact_postal_code'] ) ? $_POST['gb_contact_postal_code'] : '';
		$contact_country = isset( $_POST['gb_contact_country'] ) ? esc_html( $_POST['gb_contact_country'] ) : '';
		$contact_phone = isset( $_POST['gb_contact_phone'] ) ? esc_html( $_POST['gb_contact_phone'] ) : '';
		$website = isset( $_POST['gb_contact_website'] ) ? esc_url( $_POST['gb_contact_website'] ) : '';
		$facebook = isset( $_POST['gb_contact_facebook'] ) ? esc_url( $_POST['gb_contact_facebook'] ) : '';
		$twitter = isset( $_POST['gb_contact_twitter'] ) ? esc_url( $_POST['gb_contact_twitter'] ) : '';
		$errors = array_merge( $errors, $this->validate_merchant_contact_info_fields( $_POST ) );
		$errors = apply_filters( 'gb_validate_merchant_registration', $errors, $_POST );
		if ( !empty( $errors ) ) {
			foreach ( $errors as $error ) {
				self::set_message( $error, self::MESSAGE_STATUS_ERROR );
			}
			return FALSE;
		} else {
			$post_id = Group_Buying_Merchant::get_merchant_id_for_user();
			$merchant = Group_Buying_Merchant::get_instance( $post_id );
			wp_update_post( array(
					'ID' => $post_id,
					'post_title' => $title,
					'post_content' => $content
				) );
			$merchant->set_contact_title( $contact_title );
			$merchant->set_contact_name( $contact_name );
			$merchant->set_contact_street( $contact_street );
			$merchant->set_contact_city( $contact_city );
			$merchant->set_contact_state( $contact_state );
			$merchant->set_contact_postal_code( $contact_postal_code );
			$merchant->set_contact_country( $contact_country );
			$merchant->set_contact_phone( $contact_phone );
			$merchant->set_website( $website );
			$merchant->set_facebook( $facebook );
			$merchant->set_twitter( $twitter );
			$merchant->authorize_user( get_current_user_id() );

			if ( !empty( $_FILES['gb_contact_merchant_thumbnail'] ) ) {
				// Set the uploaded field as an attachment
				$merchant->set_attachement( $_FILES );
			}

			do_action( 'edit_merchant', $merchant );

			$url = Group_Buying_Merchants::get_url();
			$url = add_query_arg( 'message', 'updated', $url );
			self::set_message( __( 'Merchant Updated.' ), self::MESSAGE_STATUS_INFO );
			wp_redirect( $url, 303 );
			exit();
		}
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$edit_path );
		} else {
			return add_query_arg( self::EDIT_QUERY_VAR, 1, home_url() );
		}
	}
}

class Group_Buying_Merchants_Voucher_Claim extends Group_Buying_Controller {
	const BIZ_VOUCHER_PATH_OPTION = 'gb_biz_voucher_register_path';
	const BIZ_VOUCHER_QUERY_VAR = 'gb_merchant_biz_voucher';
	const BIZ_VOUCHER_CLAIM_ARG = 'gb_voucher_claim';
	const BIZ_VOUCHER_REDEMPTION_DATA = 'gb_voucher_redemption_data';
	private static $voucher_path = 'merchant/vouchers';
	private static $instance;

	public static function init() {
		self::$voucher_path = get_option( self::BIZ_VOUCHER_PATH_OPTION, self::$voucher_path );
		//self::register_query_var(self::BIZ_VOUCHER_QUERY_VAR, array(get_class(), 'on_biz_voucher_page'));
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 1 );
		add_action( 'gb_router_generate_routes', array( get_class(), 'register_path_callback' ), 10, 1 );
		add_filter( 'set_merchant_voucher_report_data_column', array( get_class(), 'add_columns_merch_report' ), 10, 1 );
		add_filter( 'gb_merch_deal_voucher_record_item', array( get_class(), 'add_item_merch_report' ), 10, 4 );
	}

	public static function add_columns_merch_report( $array ) {
		$redemption_data = array(
			'redeem_name' => self::__( 'Redeemer Name' ),
			'redeem_date' => self::__( 'Redemption Date' ),
			'redeem_total' => self::__( 'Redemption Total' ),
			'redeem_notes' => self::__( 'Redemption Notes' )
		);
		return array_merge( $array, $redemption_data );
	}

	public static function add_item_merch_report( $array, $voucher, $purchase, $account ) {
		$redemption_data = $voucher->get_redemption_data();
		$redemption_data = array(
			'redeem_name' => $redemption_data['name'],
			'redeem_date' => $redemption_data['date'],
			'redeem_total' => $redemption_data['total'],
			'redeem_notes' => $redemption_data['notes']
		);
		return array_merge( $array, $redemption_data );
	}

	/**
	 * Register the path callback for the cart page
	 *
	 * @static
	 * @param GB_Router $router
	 * @return void
	 */
	public static function register_path_callback( GB_Router $router ) {
		$path = str_replace( '/', '-', self::$voucher_path );
		$args = array(
			'path' => self::$voucher_path,
			'title' => self::__( 'Voucher Management' ),
			'title_callback' => array( self::__( 'Voucher Management' ) ),
			'page_callback' => array( get_class(), 'on_biz_voucher_page' ),
			//'access_callback' => array( get_class(), 'login_required' ),
			'template' => array(
				self::get_template_path().'/'.self::$voucher_path.'.php', // non-default merchant path
				self::get_template_path().'/merchant.php', // theme default path
				GB_PATH.'/views/public/merchant.php', // default
			),
		);
		$router->add_route( self::BIZ_VOUCHER_QUERY_VAR, $args );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_merchant_paths';

		// Settings
		register_setting( $page, self::BIZ_VOUCHER_PATH_OPTION );
		add_settings_field( self::BIZ_VOUCHER_PATH_OPTION, self::__( 'Merchant Voucher Management Path' ), array( get_class(), 'display_merchant_voucher_path' ), $page, $section );
	}

	public static function display_merchant_voucher_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::BIZ_VOUCHER_PATH_OPTION . '" id="' . self::BIZ_VOUCHER_PATH_OPTION . '" value="' . esc_attr( self::$voucher_path ) . '" size="40"/><br />';
	}

	public static function on_biz_voucher_page() {
		do_action( 'on_biz_voucher_page' );
		self::get_instance();
		self::view_voucher_mngmt();
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
		if ( isset( $_POST[self::BIZ_VOUCHER_CLAIM_ARG] ) && $_POST[self::BIZ_VOUCHER_CLAIM_ARG] != '' ) {
			self::set_claimed_date( $_POST[self::BIZ_VOUCHER_CLAIM_ARG] );
		}
	}

	public static function set_claimed_date( $security_code ) {
		$voucher_id = Group_Buying_Voucher::get_voucher_by_security_code( $security_code );
		$voucher = Group_Buying_Voucher::get_instance( array_shift( $voucher_id ) );
		$claimed = FALSE;
		if ( is_a( $voucher, 'Group_Buying_Voucher' ) ) {
			if ( FALSE != $voucher->set_claimed_date() ) {
				self::set_message( __( 'Serial claimed.' ), self::MESSAGE_STATUS_INFO );
				if ( isset( $_POST[self::BIZ_VOUCHER_REDEMPTION_DATA] ) && !empty( $_POST[self::BIZ_VOUCHER_REDEMPTION_DATA] ) ) {
					$voucher->set_redemption_data( $_POST[self::BIZ_VOUCHER_REDEMPTION_DATA] );
				}
				do_action( 'gb_voucher_merchant_redeemed', $voucher );
				$claimed = TRUE;
			}
		}
		if ( !$claimed ) {
			self::set_message( __( 'Error: Security code is not valid.' ), self::MESSAGE_STATUS_ERROR );
		}
		if ( isset( $_REQUEST['redirect_to'] ) && $_REQUEST['redirect_to'] != '' ) {
			wp_redirect( urldecode( $_REQUEST['redirect_to'] ) );
			exit();
		}
	}

	public static function view_voucher_mngmt() {
		echo self::load_view_to_string( 'merchant/voucher-claim.php', array(
				'claim_arg' => self::BIZ_VOUCHER_CLAIM_ARG,
				'data' => self::BIZ_VOUCHER_REDEMPTION_DATA,
			) );
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$voucher_path );
		} else {
			return add_query_arg( self::BIZ_VOUCHER_QUERY_VAR, 1, home_url() );
		}
	}
}

class Group_Buying_Merchants_Dashboard extends Group_Buying_Controller {
	const BIZ_DASH_PATH_OPTION = 'gb_biz_dash_register_path';
	const BIZ_DASH_QUERY_VAR = 'gb_merchant_biz_dash';
	private static $dash_path = 'merchant/dashboard';
	private static $instance;

	public static function init() {
		self::$dash_path = get_option( self::BIZ_DASH_PATH_OPTION, self::$dash_path );
		self::register_path_callback( self::$dash_path, array( get_class(), 'on_biz_dash_page' ), self::BIZ_DASH_QUERY_VAR, 'merchant/dashboard' );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 1 );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_merchant_paths';

		// Settings
		register_setting( $page, self::BIZ_DASH_PATH_OPTION );
		add_settings_field( self::BIZ_DASH_PATH_OPTION, self::__( 'Merchant Dashboard Path' ), array( get_class(), 'display_merchant_registration_path' ), $page, $section );
	}

	public static function display_merchant_registration_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::BIZ_DASH_PATH_OPTION . '" id="' . self::BIZ_DASH_PATH_OPTION . '" value="' . esc_attr( self::$dash_path ) . '" size="40"/><br />';
	}

	public static function on_biz_dash_page() {
		// Unregistered users shouldn't be here
		self::login_required();
		self::get_instance();
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
		add_action( 'pre_get_posts', array( $this, 'edit_query' ), 10, 1 );
		add_action( 'the_post', array( $this, 'view_biz_dash' ), 10, 1 );
		//add_filter('the_title', array($this, 'get_title'), 10, 2);
		//add_filter('template_include', array( get_class(), 'override_template' ) );
	}

	public function edit_query( $query ) {
		// we only care if this is the query to show a the edit profile form
		if ( isset( $query->query_vars[self::BIZ_DASH_QUERY_VAR] ) && $query->query_vars[self::BIZ_DASH_QUERY_VAR] ) {
			$query->query_vars['post_type'] = Group_Buying_Merchant::POST_TYPE;
			$query->query_vars['p'] = Group_Buying_Merchant::get_merchant_id_for_user();
		}
	}

	public function view_biz_dash( $post ) {
		if ( $post->post_type == Group_Buying_Merchant::POST_TYPE ) {
			remove_filter( 'the_content', 'wpautop' );
			$view = self::load_view_to_string( 'merchant/dashboard', array() );
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
		if ( get_query_var( self::BIZ_DASH_QUERY_VAR ) && $post->post_type == Group_Buying_Merchant::POST_TYPE  ) {
			return self::__( "Merchant Dashboard" );
		}
		return $title;
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$dash_path );
		} else {
			return add_query_arg( self::BIZ_DASH_QUERY_VAR, 1, home_url() );
		}
	}
}

class Group_Buying_Merchants_Upgrade {
	public static function upgrade_3_0() {
		$merchant_posts = get_posts( array(
				'numberposts' => -1,
				'post_status' => 'any',
				'post_type' => Group_Buying_Deal::POST_TYPE
			) );
		foreach ( $merchant_posts as $merchant_post ) {

			$post_id = $merchant_post->ID;
			$deal = Group_Buying_Deal::get_instance( $post_id );

			$merchant_id = $deal->get_merchant_id();
			if ( empty( $merchant_id ) ) {
				$merchant_name = get_post_meta( $post_id, '_merchant_name', true );
				$merchant_address = get_post_meta( $post_id, '_merchant_address', true );
				$merchant_city = get_post_meta( $post_id, '_merchant_city', true );
				$merchant_state = get_post_meta( $post_id, '_merchant_state', true );
				$merchant_zip = get_post_meta( $post_id, '_merchant_zip', true );
				$merchant_country = get_post_meta( $post_id, '_merchant_country', true );
				$merchant_phone = get_post_meta( $post_id, '_merchant_phone', true );
				$merchant_website = get_post_meta( $post_id, '_merchant_website', true );
				if ( !empty( $merchant_name ) ) {
					$merchant_id = wp_insert_post( array(
							'post_type' => Group_Buying_Merchant::POST_TYPE,
							'post_title' => $merchant_name
						) );
					wp_publish_post( $merchant_id );
					$merchant = Group_Buying_Merchant::get_instance( $merchant_id );
					$merchant->set_contact_name( $merchant_name );
					$merchant->set_contact_street( $merchant_address );
					$merchant->set_contact_city( $merchant_city );
					$merchant->set_contact_state( $merchant_state );
					$merchant->set_contact_postal_code( $merchant_zip );
					$merchant->set_contact_country( $merchant_country );
					$merchant->set_contact_phone( $merchant_phone );
					$merchant->set_website( $merchant_website );
					$deal->set_merchant_id( $merchant_id );
					do_action( 'gb_upgrade_merchant', $merchant );
				}
			}
		}
	}
}
