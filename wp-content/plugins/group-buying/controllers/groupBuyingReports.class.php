<?php

/**
 * Reports Controller
 *
 * @package GBS
 * @subpackage Report
 */
class Group_Buying_Reports extends Group_Buying_Controller {
	const REPORTS_PATH_OPTION = 'gb_report_path';
	const REPORTS_PATH_CSV_OPTION = 'gb_report_csv_path';
	const REPORTS_PATH = 'gb_report_path';
	const REPORT_QUERY_VAR = 'gb_report';
	const CSV_QUERY_VAR = 'gb_report_csv';
	private static $reports_path = 'gbs_reports';
	private static $reports_csv_path = 'gbs_reports/csv';
	private static $report;
	private static $instance;

	final public static function init() {
		self::$reports_path = get_option( self::REPORTS_PATH_OPTION, self::$reports_path );
		self::$reports_csv_path = get_option( self::REPORTS_PATH_CSV_OPTION, self::$reports_csv_path );
		self::register_path_callback( self::$reports_path, array( get_class(), 'gbs_report' ), self::REPORT_QUERY_VAR, 'gbs_reports' );
		self::register_path_callback( self::$reports_csv_path, array( get_class(), 'download_csv' ), self::CSV_QUERY_VAR, 'gbs_reports/csv' );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 50, 0 );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_reports_paths';
		add_settings_section( $section, null, array( get_class(), 'display_paths_section' ), $page );
		register_setting( $page, self::REPORTS_PATH_OPTION );
		register_setting( $page, self::REPORTS_PATH_CSV_OPTION );
		add_settings_field( self::REPORTS_PATH_OPTION, self::__( 'Report Path' ), array( get_class(), 'display_path' ), $page, $section );
		add_settings_field( self::REPORTS_PATH_CSV_OPTION, self::__( 'Report Path for CSV Downloads' ), array( get_class(), 'display_csv_path' ), $page, $section );
	}

	public static function display_paths_section() {
		echo self::__( '<h4>Customize the Report paths</h4>' );
	}

	public static function display_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="'.self::REPORTS_PATH_OPTION.'" id="'.self::REPORTS_PATH_OPTION.'" value="' . esc_attr( self::$reports_path ) . '"  size="40" /><br />';
	}

	public static function display_csv_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="'.self::REPORTS_PATH_CSV_OPTION.'" id="'.self::REPORTS_PATH_CSV_OPTION.'" value="' . esc_attr( self::$reports_csv_path ) . '"  size="40" /><br />';
	}

	public static function gbs_report() {
		// Unregistered users shouldn't be here
		self::login_required();
		self::get_instance();
	}

	public function download_csv( $post ) {
		// Unregistered users shouldn't be here
		self::login_required();
		self::do_not_cache(); // never cache the report
		$report = Group_Buying_Report::get_instance( $_GET['report'] );
		$columns = $report->columns;
		$records = $report->records;
		$view = self::load_view_to_string( 'reports/csv', array( 'filename' => 'gbs_report.csv', 'columns' => $columns, 'records' => $records ), FALSE );
		print $view;
		exit();
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
	public static function get_instance() {
		if ( !( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		self::do_not_cache(); // never cache the report
		self::$report = isset($_GET['report'])?$_GET['report']:'';
		add_action( 'pre_get_posts', array( get_class(), 'edit_query' ), 10, 1 );
		add_filter( 'template_include', array( get_class(), 'override_template' ) );
		add_action( 'the_post', array( $this, 'view_report' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'get_title' ), 10, 2 );
	}

	public static function edit_query( $query ) {
		if ( isset( $query->query_vars[self::REPORT_QUERY_VAR] ) && $query->query_vars[self::REPORT_QUERY_VAR] ) {
			$query->query_vars['post_type'] = gb_get_merchant_post_type();
			$query->query_vars['post_status'] = 'draft,publish';
			$query->query_vars['p'] = Group_Buying_Merchant::blank_merchant();
		}
	}

	public static function override_template( $template ) {
		$template = self::locate_template( array(
				'reports/report.php',
				'report.php',
			), $template );
		return $template;
	}

	public function view_report( $post ) {
		if ( $post->post_type == Group_Buying_Merchant::POST_TYPE && isset( $_GET['report'] ) ) {
			remove_filter( 'the_content', 'wpautop' );
			$report = Group_Buying_Report::get_instance( self::$report );
			$columns = $report->columns;
			$records = $report->records;
			$view = self::load_view_to_string( 'reports/view', array( 'columns' => $columns, 'records' => $records ) );
			global $pages;
			$pages = array( $view );
		}
	}

	public function get_title(  $title, $post_id  ) {
		$post = &get_post( $post_id );
		if ( ( $post->post_type == gb_get_merchant_post_type() || $post->post_type == gb_get_purchase_post_type() ) && isset( $_GET['report'] ) ) {
			$report_name = str_replace( '_', ' ', self::$report );
			return apply_filters( 'gb_reports_get_title', ucwords( self::__( $report_name . " report" ) ), self::$report );
		}
		return $title;
	}

	public static function get_url() {
		if ( self::using_permalinks() ) {
			return add_query_arg( array( 'report' => self::$report ), home_url( trailingslashit( self::$reports_path ) ) );
		} else {
			return add_query_arg( array( self::REPORT_QUERY_VAR => 1, 'report' => self::$report  ), home_url() );
		}
	}

	public static function get_csv_url() {
		if ( self::using_permalinks() ) {
			return add_query_arg( array( 'report' => self::$report ), home_url( trailingslashit( self::$reports_csv_path ) ) );
		} else {
			return add_query_arg( array( self::CSV_QUERY_VAR => 1, 'report' => self::$report  ), home_url() );
		}
	}

}
