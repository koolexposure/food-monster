<?php
if ( class_exists( 'Group_Buying_Controller' ) ) {

	include 'template-tags.php';

	class Group_Buying_Featured_Content extends Group_Buying_Controller {

		private static $instance;

		private static $meta_keys = array(
			'featured_content' => '_featured_content', // string
		); // A list of meta keys this class cares about. Try to keep them in alphabetical order.

		public static function init() {
			add_action( 'add_meta_boxes', array( get_class(), 'add_meta_boxes' ) );
			add_action( 'save_post', array( get_class(), 'save_meta_boxes' ), 10, 2 );
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
		}


		public static function add_meta_boxes() {
			add_meta_box( 'gb_deal_theme_meta', self::__( 'Theme Options' ), array( get_class(), 'show_meta_box' ), Group_Buying_Deal::POST_TYPE, 'advanced', 'high' );
		}

		public static function show_meta_box( $post, $metabox ) {
			$deal = Group_Buying_Deal::get_instance( $post->ID );
			switch ( $metabox['id'] ) {
			case 'gb_deal_theme_meta':
				self::show_meta_box_gb_theme_meta( $deal, $post, $metabox );
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
			// save all the meta boxes
			$deal = Group_Buying_Deal::get_instance( $post_id );
			self::save_meta_box_gb_theme_meta( $deal, $post_id, $post );
		}

		private static function show_meta_box_gb_theme_meta( Group_Buying_Deal $deal, $post, $metabox ) {
			$featured_content = self::get_featured_content( $post->ID );
			include 'views/meta_box.php';
		}

		private static function save_meta_box_gb_theme_meta( Group_Buying_Deal $deal, $post_id, $post ) {
			if ( isset( $_POST['featured_content'] ) ) {
				$featured_content = $_POST['featured_content'];
				self::set_featured_content( $post_id, $featured_content );
			}
		}

		public function set_featured_content( $post_id, $featured_content ) {
			update_post_meta( $post_id, self::$meta_keys['featured_content'], $featured_content );
			return $featured_content;
		}

		public static function get_featured_content( $post_id ) {
			$featured_content = get_post_meta( $post_id, self::$meta_keys['featured_content'], true );
			
			if ( empty( $featured_content ) )
				return;
			
			// Strip out height and width of any media so that the theme can manage responsiveness
			if ( ( apply_filters( 'gb_featured_content_response_sanitization', TRUE ) ) && // allow for this to be override easily.
				strpos( $featured_content, 'iframe' )
				|| strpos( $featured_content, 'embed' )
				|| strpos( $featured_content, 'object' ) 
				) {
				$featured_content = preg_replace( '/(width|height)=\"\d*\"\s/', "", $featured_content ); 
			}
			
			return $featured_content;
		}
	}
}
add_action( 'init', array( 'Group_Buying_Featured_Content', 'init' )  );
