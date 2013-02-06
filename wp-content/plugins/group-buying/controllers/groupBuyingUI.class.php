<?php

/**
 * GBS UI related: widgets, resources, etc..
 *
 * @package GBS
 * @subpackage Theme
 */
class Group_Buying_UI extends Group_Buying_Controller {
	const TODAYSDEAL_PATH_OPTION = 'gb_todaysdeal_path';
	const REMOVE_EXPIRED_DEALS = 'gb_remove_expired';
	const COUNTRIES_OPTION = 'gb_countries_filter';
	const STATES_OPTION = 'gb_states_filter';
	public static $todays_deal_path;
	public static $remove_expired;
	protected static $settings_page;
	protected static $int_settings_page;
	protected static $countries;
	protected static $states;
	private static $instance;

	final public static function init() {
		self::$settings_page = self::register_settings_page( 'gb_settings', self::__( 'Welcome to Group Buying' ), self::__( 'General Settings' ), 10, FALSE, 'general' );

		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 0 );
		add_action( 'admin_init', array( get_class(), 'register_int_settings_fields' ), 100, 0 );
		self::$todays_deal_path = trailingslashit( get_option( self::TODAYSDEAL_PATH_OPTION, 'todays-deal' ) );
		self::$remove_expired = get_option( self::REMOVE_EXPIRED_DEALS, FALSE );
		self::$countries = get_option( self::COUNTRIES_OPTION, FALSE );
		self::$states = get_option( self::STATES_OPTION, FALSE );

		// Callback
		self::register_path_callback( self::$todays_deal_path, array( get_class(), 'todays_deal' ), self::TODAYSDEAL_PATH_OPTION );

		self::get_instance();

		add_action( 'init', array( get_class(), 'register_resources' ) );
		add_action( 'wp_enqueue_scripts', array( get_class(), 'frontend_enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( get_class(), 'admin_enqueue' ) );

		// Widgets
		add_action( 'widgets_init', create_function( '', 'return register_widget("GroupBuying_FinePrint");' ) );
		add_action( 'widgets_init', create_function( '', 'return register_widget("GroupBuying_Highlights");' ) );
		add_action( 'widgets_init', create_function( '', 'return register_widget("GroupBuying_Locations");' ) );
		add_action( 'widgets_init', create_function( '', 'return register_widget("GroupBuying_Location");' ) );
		add_action( 'widgets_init', create_function( '', 'return register_widget("GroupBuying_Categories");' ) );
		add_action( 'widgets_init', create_function( '', 'return register_widget("GroupBuying_Tags");' ) );
		add_action( 'widgets_init', create_function( '', 'return register_widget("GroupBuying_RecentDeals");' ) );
		add_action( 'widgets_init', create_function( '', 'return register_widget("GroupBuying_RelatedDeals");' ) );
		add_action( 'widgets_init', create_function( '', 'return register_widget("GroupBuying_Share_and_Earn");' ) );

		add_action( 'admin_head', array( get_class(), 'admin_footer' ) );
		if ( version_compare( get_bloginfo( 'version' ), '3.2.99', '<=' ) ) { // WP 3.3 and below // TODO remove check after 3.4
			add_filter( 'contextual_help', array( get_class(), 'admin_contextual_help' ), 10, 2 );
		}
		// gb controller handles menus for 3.3 and above.

	}

	public static function admin_footer() {
		echo '<style type="text/css">';
		echo '#icon-edit.icon32-posts-gb_deal { background: url('.GB_URL.'/resources/img/deals-big.png) no-repeat 0 0; }';
		echo '#icon-edit.icon32-posts-gb_merchant { background: url('.GB_URL.'/resources/img/merchant-big.png) no-repeat 0 0; }';
		echo '</style>';
	}

	public static function admin_contextual_help( $text, $screen ) {
		$settings_pos = strpos( $screen, 'group-buying_page_group-buying' );
		$deal_pos = strpos( $screen, 'edit-gb_deal' );
		$merchant_pos = strpos( $screen, 'edit-gb_merchant' );
		if ( $settings_pos !== false || $deal_pos !== false || $merchant_pos !== false ) {
			$text = "<p><strong>Do you have a question about GBS?</strong><br/>";
			$text .= "Try <a href='http://groupbuyingsite.com/forum/search.php'>searching the forums</a> to find a quick answer.</p>";
			$text .= "<p><strong>Are you experiencing trouble with your GBS site?</strong><br/>";
			$text .= "Please see these <a href='http://groupbuyingsite.com/forum/forumdisplay.php?32-Troubleshooting'>tips for troubleshooting</a> and search the forums for a solution. If you can't find a solution after searching the forums, create a forum post and someone will assist you as soon as possible.</p>";
			$text .= "<p><strong>Critical problem with a production/live site?</strong><br/>";
			$text .= "<a href='http://groupbuyingsite.com/forum/support.php?do=newticket'>Submit a helpdesk ticket</a> after creating a forum thread.</p>";
			$text .= "<p><strong>In need of a custom feature or custom theme for your site?</strong>";
			$text .= "<br/>Select the &quot;Development Request&quot; option when <a href='http://groupbuyingsite.com/forum/support.php?do=newticket'>submitting a new ticket</a>";
		}
		return $text;
	}

	public static function register_resources() {
		// Datepicker and misc.
		wp_register_script( 'group-buying-admin', GB_URL . '/resources/js/admin.gbs.js', array( 'jquery', 'jquery-ui-draggable' ), Group_Buying::GB_VERSION );
		// Validation plugin and misc
		wp_register_script( 'group-buying-jquery', GB_URL . '/resources/js/jquery.public.gbs.js', array( 'jquery' ), Group_Buying::GB_VERSION );
		// Select2
		wp_register_script( 'select2', GB_URL . '/resources/plugins/admin/select2/select2.js', array( 'jquery' ), Group_Buying::GB_VERSION );
		
		// Marketplace
		wp_register_script( 'group-buying-admin-marketplace', GB_URL . '/resources/js/admin.marketplace.gbs.js', array( 'jquery' ), Group_Buying::GB_VERSION );

		// CSS
		wp_register_style( 'group-buying-admin-css', GB_URL . '/resources/css/admin.gbs.css' );
		wp_register_style( 'select2_css', GB_URL . '/resources/plugins/admin/select2/select2.css' );
	}

	public static function frontend_enqueue() {
		wp_enqueue_script( 'group-buying-jquery' );
	}

	public static function admin_enqueue() {
		wp_enqueue_script( 'group-buying-admin' );
		wp_enqueue_script( 'group-buying-admin-marketplace' );
		wp_enqueue_script( 'select2' );
		wp_enqueue_style( 'group-buying-admin-css' );
		wp_enqueue_style( 'select2_css' );
	}

	/**
	 *
	 *
	 * @static
	 * @return string The ID of the payment settings page
	 */
	public static function get_settings_page() {
		return self::$settings_page;
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	final protected function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}

	final protected function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}
	/**
	 *
	 *
	 * @static
	 * @return Group_Buying_UI
	 */
	public static function get_instance() {
		if ( !( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	protected function __construct() {
		if ( 'FALSE' != self::$remove_expired ) {
			add_filter( 'pre_get_posts', array( get_class(), 'remove_expired_deals' ), 10, 1 );
		}
		if ( 'false' != self::$countries && !empty( self::$countries ) ) {
			add_filter( 'gb_country_options', array( get_class(), 'get_countries_option' ), 10, 2 );
		}
		if ( 'false' != self::$states && !empty( self::$states ) ) {
			add_filter( 'gb_state_options', array( get_class(), 'get_states_option' ), 10, 2 );
		}
	}

	/**
	 * Remove expired deals from loop
	 *
	 * @param string  $query
	 * @return void
	 * @author Dan Cameron
	 */
	public static function remove_expired_deals( &$query ) {
		if ( ( is_tax() || is_archive() ) && !is_page() && !is_single() && !is_admin() && !gb_on_voucher_page() ) {
			if ( isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == gb_get_deal_post_type()  ) {
				$query->set( 'meta_query', array(
						array(
							'key' => '_expiration_date',
							'value' => array( 0, current_time( 'timestamp' ) ),
							'compare' => 'NOT BETWEEN'
						)
					) );
			}
			if ( self::$remove_expired == 'ALL' ) {
				if (
					isset( $query->query[gb_get_deal_location_tax()] ) ||
					isset( $query->query[gb_get_deal_cat_slug()] ) ||
					isset( $query->query[gb_get_deal_tag_slug()] )
				) {
					$query->set( 'meta_query', array(
							array(
								'key' => '_expiration_date',
								'value' => array( 0, current_time( 'timestamp' ) ),
								'compare' => 'NOT BETWEEN'
							)
						) );
				}
			}
		}
	}

	public function todays_deal() {
		wp_redirect( gb_get_latest_deal_link() );
		exit();
	}

	public static function register_settings_fields() {
		$page = self::$settings_page;
		$section = 'gb_general_settings';
		add_settings_section( $section, self::__( 'General Options' ), array( get_class(), 'display_settings_section' ), $page );
		register_setting( self::$settings_page, self::TODAYSDEAL_PATH_OPTION );
		register_setting( self::$settings_page, self::REMOVE_EXPIRED_DEALS );
		add_settings_field( self::REMOVE_EXPIRED_DEALS, self::__( 'Expired Deals' ), array( get_class(), 'display_option_remove_expired' ), $page, $section );
		add_settings_field( self::TODAYSDEAL_PATH_OPTION, self::__( 'Latest Deal URL' ), array( get_class(), 'display_option_todays_deal' ), $page, $section );
	}

	public static function register_int_settings_fields() {
		$page = self::$settings_page;
		$int_section = 'gb_internationalization_settings';
		add_settings_section( $int_section, self::__( 'Form Options' ), array( get_class(), 'display_internationalization_section' ), $page );
		register_setting( self::$settings_page, self::STATES_OPTION, array( get_class(), 'save_states') );
		register_setting( self::$settings_page, self::COUNTRIES_OPTION, array( get_class(), 'save_countries') );
		add_settings_field( self::STATES_OPTION, self::display_option_states_title(), array( get_class(), 'display_option_states' ), $page, $int_section );
		add_settings_field( self::COUNTRIES_OPTION, self::display_option_countries_title(), array( get_class(), 'display_option_countries' ), $page, $int_section );
	}

	public static function display_option_todays_deal() {
		echo site_url().'/<input type="text" name="'.self::TODAYSDEAL_PATH_OPTION.'" value="'.self::$todays_deal_path.'">';
	}

	public static function display_option_remove_expired() {
		echo '<label><input type="radio" name="'.self::REMOVE_EXPIRED_DEALS.'" value="TRUE" '.checked( 'TRUE', self::$remove_expired, FALSE ).'/> '.self::__( 'Remove the expired deals from the main deals loop.' ).'</label><br />';
		echo '<label><input type="radio" name="'.self::REMOVE_EXPIRED_DEALS.'" value="ALL" '.checked( 'ALL', self::$remove_expired, FALSE ).'/> '.self::__( 'Remove the expired deals from location, tags and category loops.' ).'</label><br />';
		echo '<label><input type="radio" name="'.self::REMOVE_EXPIRED_DEALS.'" value="FALSE" '.checked( 'FALSE', self::$remove_expired, FALSE ).'/> '.self::__( 'Show expired deals. ' ).'</label><br />';
	}

	public static function display_internationalization_section() {
		echo '<p>'.self::_e( 'Select the states and countries/proviences you would like in your forms.' ).'</p>';

	}

	public static function display_option_states_title() {
		$title = '<strong>'.self::__( 'States' ).'</strong>';
		$title .= '<p>'.self::__( 'Additional states can be added by hooking into the <code>gb_state_options</code> filter.' ).'</p>';
		return $title;
	}

	public static function display_option_states() {
		echo '<div class="gb_state_options">';
		echo '<select name="'.self::STATES_OPTION.'[]" class="select2" multiple="multiple">';
		foreach ( parent::$grouped_states as $group => $states ) {
			echo '<optgroup label="'.$group.'">';
			foreach ($states as $key => $name) {
				$selected = ( in_array( $name, self::$states[$group] ) || empty( self::$states ) ) ? 'selected="selected"' : null ;
				echo '<option value="'.$key.'" '.$selected.'>&nbsp;'.$name.'</option>';
			}
			echo '</optgroup>';
		}
		echo '</select>';
		echo '</div>';
	}

	public static function display_option_countries_title() {
		$title = '<strong>'.self::__( 'Countries' ).'</strong>';
		$title .= '<p>'.self::__( 'Additional countries can be added by hooking into the <code>gb_country_options</code> filter.' ).'</p>';
		$title .= '<p>'.self::__( 'Note: Some payment processors country support is limited. For example, Paypal Pro only accepts <a href="https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_country_codes">these countries</a>.' ).'</p>';
		return $title;
	}

	public static function display_option_countries() {
		echo '<div class="gb_country_options">';
		echo '<select name="'.self::COUNTRIES_OPTION.'[]" class="select2" multiple="multiple">';
		foreach ( parent::$countries as $key => $name ) {
			$selected = ( in_array( $name, self::$countries ) || empty( self::$countries ) ) ? 'selected="selected"' : null ;
			echo '<option value="'.$name.'" '.$selected.'>&nbsp;'.$name.'</option>';
		}
		echo '</select>';
		echo '</div>';
	}

	public static function save_states( $selected ) {
		$sanitized_options = array();
		foreach ( parent::$grouped_states as $group => $states ) {
			$sanitized_options[$group] = array();
			foreach ($states as $key => $name) {
				if ( in_array( $key, $selected ) ) {
					$sanitized_options[$group][$key] = $name;
				}
			}
			// Unset the empty groups
			if ( empty( $sanitized_options[$group] ) ) {
				unset( $sanitized_options[$group] );
			}
		}
		return $sanitized_options;
	}

	public static function save_countries( $options ) {
		$sanitized_options = array();
		foreach ( parent::$countries  as $key => $name ) {
			if ( in_array( $name, $options ) ) {
				$sanitized_options[$key] = $name;
			}
		}
		return $sanitized_options;
	}

	public static function get_states_option( $states = array(), $args = array() ) {
		if ( isset( $args['include_option_none'] ) && $args['include_option_none'] ) {
			$states = array( '' => $args['include_option_none'] ) + self::$states;
		}
		return $states;
	}

	public static function get_countries_option( $countries = array(), $args = array() ) {
		if ( isset( $args['include_option_none'] ) && $args['include_option_none'] ) {
			$countries = array( '' => $args['include_option_none'] ) + self::$countries;
		}
		return $countries;
	}
}

/**
 * GBS Fineprint Widget
 *
 * @package GBS
 * @subpackage Theme
 */
class GroupBuying_FinePrint extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	function GroupBuying_FinePrint() {
		$widget_ops = array( 'description' => gb__( 'Can only be used on the Deal Page, otherwise we will gracefully hide the widget.' ) );
		parent::WP_Widget( false, $name = gb__( 'Group Buying :: Deal Fine Print' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		do_action( 'pre_fine_print', $args, $instance );
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$post_type = get_query_var( 'post_type' );
		if ( is_single() && $post_type == gb_get_deal_post_type() ) {
			echo $before_widget;
			ob_start();
			if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
?>
				<div class="deal-widget-inner"><?php gb_fine_print(); ?></div>
				<?php

			$view = ob_get_clean();
			print apply_filters( 'gb_fine_print_widget', $view );
			echo $after_widget;
		}
		do_action( 'post_fine_print', $args, $instance );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {
		$title = esc_attr( $instance['title'] );
?>
            <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php
	}

}

/**
 * GBS Highlights Widget
 *
 * @package GBS
 * @subpackage Theme
 */
class GroupBuying_Highlights extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Dan Cameron
	 */
	function GroupBuying_Highlights() {
		$widget_ops = array( 'description' => gb__( 'Can only be used on the Deal Page, otherwise we will gracefully hide the widget.' ) );
		parent::WP_Widget( false, $name = gb__( 'Group Buying :: Deal Highlights' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		do_action( 'pre_highlights', $args, $instance );
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$post_type = get_query_var( 'post_type' );
		if ( is_single() && $post_type == gb_get_deal_post_type() ) {
			echo $before_widget;
			ob_start();
			if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
?>
				<div class="deal-widget-inner"><?php gb_highlights(); ?></div>
				<?php

			$view = ob_get_clean();
			print apply_filters( 'gb_highlights_widget', $view );
			echo $after_widget;
		}
		do_action( 'post_highlights', $args, $instance );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {
		$title = esc_attr( $instance['title'] );
?>
            <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php
	}

}

/**
 * GBS Locations Widget
 *
 * @package GBS
 * @subpackage Theme
 */
class GroupBuying_Locations extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Dan Cameron
	 */
	function GroupBuying_Locations() {
		$widget_ops = array( 'description' => gb__( 'Can only be used on the Deal Page, otherwise we will gracefully hide the widget.' ) );
		parent::WP_Widget( false, $name = gb__( 'Group Buying :: Locations' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		do_action( 'pre_locations', $args, $instance );
		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );
		$post_type = get_query_var( 'post_type' );
		if ( is_single() && $post_type == gb_get_deal_post_type() ) {
			echo $before_widget;
			ob_start();
			if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
?>
				<div class="deal-widget-inner"><?php gb_deal_locations(); ?></div>
				<?php

			$view = ob_get_clean();
			print apply_filters( 'gb_locations_widget', $view );
			echo $after_widget;
		}
		do_action( 'post_locations', $args, $instance );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {
		$title = esc_attr( $instance['title'] );
?>
            <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php
	}

}

/**
 * GBS Categories Widget
 *
 * @package GBS
 * @subpackage Theme
 */
class GroupBuying_Categories extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Dan Cameron
	 */
	function GroupBuying_Categories() {
		$widget_ops = array( 'description' => gb__( 'Can only be used on the Deal Page, otherwise we will gracefully hide the widget.' ) );
		parent::WP_Widget( false, $name = gb__( 'Group Buying :: Categories' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		do_action( 'pre_categories', $args, $instance );
		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );
		$post_type = get_query_var( 'post_type' );
		if ( is_single() && $post_type == gb_get_deal_post_type() ) {
			echo $before_widget;
			ob_start();
			if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
?>
				<div class="deal-widget-inner"><?php gb_deal_categories(); ?></div>
				<?php

			$view = ob_get_clean();
			print apply_filters( 'gb_categories_widget', $view );
			echo $after_widget;
		}
		do_action( 'post_categories', $args, $instance );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {
		$title = esc_attr( $instance['title'] );
?>
            <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php
	}

}

/**
 * GBS Tags Widget
 *
 * @package GBS
 * @subpackage Theme
 */
class GroupBuying_Tags extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Dan Cameron
	 */
	function GroupBuying_Tags() {
		$widget_ops = array( 'description' => gb__( 'Can only be used on the Deal Page, otherwise we will gracefully hide the widget.' ) );
		parent::WP_Widget( false, $name = gb__( 'Group Buying :: Tags' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		do_action( 'pre_tags', $args, $instance );
		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );
		$post_type = get_query_var( 'post_type' );
		if ( is_single() && $post_type == gb_get_deal_post_type() ) {
			echo $before_widget;
			ob_start();
			if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
?>
				<div class="deal-widget-inner"><?php gb_deal_tags( ); ?></div>
				<?php

			$view = ob_get_clean();
			print apply_filters( 'gb_tags_widget', $view );
			echo $after_widget;
		}
		do_action( 'post_tags', $args, $instance );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {
		$title = esc_attr( $instance['title'] );
?>
            <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php
	}

}

/**
 * GBS Location Widget
 *
 * @package GBS
 * @subpackage Theme
 */
class GroupBuying_Location extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Dan Cameron
	 */
	function GroupBuying_Location() {
		$widget_ops = array( 'description' => gb__( 'Can only be used on the Deal Page, otherwise we will gracefully hide the widget.' ) );
		parent::WP_Widget( false, $name = gb__( 'Group Buying :: Map' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		do_action( 'pre_location', $args, $instance );
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		$post_type = get_query_var( 'post_type' );
		if ( is_single() && $post_type == gb_get_deal_post_type() ) {
			echo $before_widget;
			ob_start();
			if ( !empty( $title ) ) { echo $before_title . $title. $after_title; };
?>
				<div class="deal-widget-inner"><?php gb_map(); ?></div>
				<?php

			$view = ob_get_clean();
			print apply_filters( 'gb_location_widget', $view );
			echo $after_widget;
		}
		do_action( 'post_location', $args, $instance );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {
		$title = esc_attr( $instance['title'] );
?>
            <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php
	}

}

/**
 * GBS Share and Earn Widget
 *
 * @package GBS
 * @subpackage Theme
 */
class GroupBuying_Share_and_Earn extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Dan Cameron
	 */
	function GroupBuying_Share_and_Earn() {
		$widget_ops = array( 'description' => gb__( 'Display a "Share and Earn" widget.' ) );
		parent::WP_Widget( false, $name = gb__( 'Group Buying :: Share and Earn' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		do_action( 'pre_share_and_earn', $args, $instance );
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		if ( !empty( $title ) ) { echo $before_title . $title. $after_title; };

		if ( is_single() && get_post_type( get_the_ID() ) == gb_get_deal_post_type() ):
			Group_Buying_Controller::load_view( 'widgets/share-earn.php', array( 'buynow'=>$buynow ) );
		endif;

		echo $after_widget;
		do_action( 'post_share_and_earn', $args, $instance );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {
		$title = esc_attr( $instance['title'] );
?>
            <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php
	}

}

/**
 * GBS Recent Deals Widget
 *
 * @package GBS
 * @subpackage Theme
 */
class GroupBuying_RecentDeals extends WP_Widget {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Dan Cameron
	 */
	function GroupBuying_RecentDeals() {
		$widget_ops = array( 'description' => gb__( 'Creates an attractive display of recent deals.' ) );
		parent::WP_Widget( false, $name = gb__( 'Group Buying :: Recent Deals' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		do_action( 'pre_recent_deals', $args, $instance );
		global $gb, $wp_query;
		$temp = null;
		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );
		$buynow = empty( $instance['buynow'] ) ? 'Buy Now' : $instance['buynow'];
		$deals = apply_filters( 'gb_recent_deals_widget_show', $instance['deals'] );
		if ( is_single() ) {
			$post_not_in = $wp_query->post->ID;
		}
		$count = 1;
		$deal_query= null;
		$args=array(
			'post_type' => gb_get_deal_post_type(),
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => '_expiration_date',
					'value' => array( 0, current_time( 'timestamp' ) ),
					'compare' => 'NOT BETWEEN'
				) ),
			'posts_per_page' => $deals,
			'post__not_in' => array( $post_not_in )
		);

		$deal_query = new WP_Query( $args );
		if ( $deal_query->have_posts() ) {
			echo $before_widget;
			echo $before_title . $title . $after_title;
			while ( $deal_query->have_posts() ) : $deal_query->the_post();

			Group_Buying_Controller::load_view( 'widgets/recent-deals.php', array( 'buynow'=>$buynow ) );

			endwhile;
			echo $after_widget;
		}
		$deal_query = null; $deal_query = $temp;
		wp_reset_query();
		do_action( 'post_recent_deals', $args, $instance );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['buynow'] = strip_tags( $new_instance['buynow'] );
		$instance['deals'] = strip_tags( $new_instance['deals'] );
		$instance['show_expired'] = strip_tags( $new_instance['show_expired'] );
		return $instance;
	}

	function form( $instance ) {
		$title = esc_attr( $instance['title'] );
		$buynow = esc_attr( $instance['buynow'] );
		$deals = esc_attr( $instance['deals'] );
		$show_expired = esc_attr( $instance['show_expired'] );
?>
            <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id( 'buynow' ); ?>"><?php _e( 'Buy now link text:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'buynow' ); ?>" name="<?php echo $this->get_field_name( 'buynow' ); ?>" type="text" value="<?php echo $buynow; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id( 'deals' ); ?>"><?php _e( 'Number of deals to display:' ); ?>
            	<select id="<?php echo $this->get_field_id( 'deals' ); ?>" name="<?php echo $this->get_field_name( 'deals' ); ?>">
					<option value="1">1</option>
					<option value="2"<?php if ( $deals=="2" ) {echo ' selected="selected"';} ?>>2</option>
					<option value="3"<?php if ( $deals=="3" ) {echo ' selected="selected"';} ?>>3</option>
					<option value="4"<?php if ( $deals=="4" ) {echo ' selected="selected"';} ?>>4</option>
					<option value="5"<?php if ( $deals=="5" ) {echo ' selected="selected"';} ?>>5</option>
					<option value="10"<?php if ( $deals=="10" ) {echo ' selected="selected"';} ?>>10</option>
					<option value="15"<?php if ( $deals=="15" ) {echo ' selected="selected"';} ?>>15</option>
					<option value="-1"<?php if ( $deals=="-1" ) {echo ' selected="selected"';} ?>>All</option>
				 </select>
            </label></p>
        <?php
	}
}

/**
 * GBS Related Deals Widget
 *
 * @package GBS
 * @subpackage Theme
 */
class GroupBuying_RelatedDeals extends WP_Widget {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Dan Cameron
	 */
	function GroupBuying_RelatedDeals() {
		$widget_ops = array( 'description' => gb__( 'Creates an attractive display of related deals. Relationships are based on user&rsquo;s preferred location or a single term from the deal shown.' ) );
		parent::WP_Widget( false, $name = gb__( 'Group Buying :: Related Deals' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		do_action( 'pre_related_deals', $args, $instance );
		global $wp_query, $post;
		
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$buynow = empty( $instance['buynow'] ) ? gb__('Buy Now') : $instance['buynow'];
		$qty = $instance['deals'];
		$location = '';

		if ( isset( $_COOKIE[ 'gb_location_preference' ] ) && $_COOKIE[ 'gb_location_preference' ] != '' ) {
			$location = $_COOKIE[ 'gb_location_preference' ];
		} 
		if ( $location == '' ) {
			$locations = array();
			$terms = get_the_terms( $post->ID, gb_get_deal_location_tax() );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$locations[] = $term->slug;
				}
			}
			$location = $locations[0];
		}
		if ( $location != '' ) {
			$args = array(
				'post_type' => gb_get_deal_post_type(),
				'post_status' => 'publish',
				gb_get_deal_location_tax() => apply_filters( 'gb_related_deals_widget_location', $location, $locations ),
				'meta_query' => array(
					array(
						'key' => '_expiration_date',
						'value' => array( 0, current_time( 'timestamp' ) ),
						'compare' => 'NOT BETWEEN'
					) ),
				'posts_per_page' => $qty
			);
			if ( is_single() ) {
				$args = array_merge( $args, array( 'post__not_in' => array( $wp_query->post->ID ) ) );
			}
			
			$related_deal_query = new WP_Query( apply_filters( 'gb_related_deals_widget_args', $args) );
			if ( $related_deal_query->have_posts() ) {
				echo $before_widget;
				echo $before_title . $title . $after_title;
					while ( $related_deal_query->have_posts() ) : $related_deal_query->the_post();

						Group_Buying_Controller::load_view( 'widgets/related-deals.php', array( 'buynow'=>$buynow ) );

					endwhile;
				echo $after_widget;
			}
			wp_reset_query();
			do_action( 'post_related_deals', $args, $instance );
		}
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['buynow'] = strip_tags( $new_instance['buynow'] );
		$instance['deals'] = strip_tags( $new_instance['deals'] );
		return $instance;
	}

	function form( $instance ) {
		$title = esc_attr( $instance['title'] );
		$buynow = esc_attr( $instance['buynow'] );
		$deals = esc_attr( $instance['deals'] );
?>
            <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id( 'buynow' ); ?>"><?php _e( 'Buy now link text:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'buynow' ); ?>" name="<?php echo $this->get_field_name( 'buynow' ); ?>" type="text" value="<?php echo $buynow; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id( 'deals' ); ?>"><?php _e( 'Number of deals to display:' ); ?>
            	<select id="<?php echo $this->get_field_id( 'deals' ); ?>" name="<?php echo $this->get_field_name( 'deals' ); ?>">
					<option value="1">1</option>
					<option value="2"<?php if ( $deals=="2" ) {echo ' selected="selected"';} ?>>2</option>
					<option value="3"<?php if ( $deals=="3" ) {echo ' selected="selected"';} ?>>3</option>
					<option value="4"<?php if ( $deals=="4" ) {echo ' selected="selected"';} ?>>4</option>
					<option value="5"<?php if ( $deals=="5" ) {echo ' selected="selected"';} ?>>5</option>
					<option value="10"<?php if ( $deals=="10" ) {echo ' selected="selected"';} ?>>10</option>
					<option value="15"<?php if ( $deals=="15" ) {echo ' selected="selected"';} ?>>15</option>
					<option value="-1"<?php if ( $deals=="-1" ) {echo ' selected="selected"';} ?>>All</option>
				 </select>
            </label></p>
        <?php
	}

}
