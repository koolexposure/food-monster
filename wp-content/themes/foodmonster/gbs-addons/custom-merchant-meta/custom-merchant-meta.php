<?php


if ( class_exists( 'Group_Buying_Controller' ) ) {

	include 'template-tags.php';

	class Group_Buying_Merchant_Content extends Group_Buying_Controller {


		private static $meta_keys = array(
			'merchant_content' => '_merchant_content', // string
		); // A list of meta keys this class cares about. Try to keep them in alphabetical order.

		public static function init() {
			add_action( 'add_meta_boxes', array( get_class(), 'add_meta_boxes' ) );
			add_action( 'save_post', array( get_class(), 'save_meta_boxes' ), 10, 2 );
		}

		public static function add_meta_boxes() {
			add_meta_box( 'gb_custom_merchant_meta', self::__( 'Restaurant Info' ), array( get_class(), 'show_meta_box' ), Group_Buying_Merchant::POST_TYPE, 'advanced', 'high' );
		}

		public static function show_meta_box( $post, $metabox ) {
			switch ( $metabox['id'] ) {
			case 'gb_custom_merchant_meta':
				self::show_meta_box_gb_theme_meta($post, $metabox );
				break;
			default:
				self::unknown_meta_box( $metabox['id'] );
				break;
			}
		}
		private static function show_meta_box_gb_theme_meta(  $post, $metabox ) {
			$merchant_content = self::get_merchant_content( $post->ID );	
			?>
			<p>
				<label for="merchant_content"><strong><?php self::_e( 'Merchant Content' ) ?>:</strong></label>
				<br/><textarea rows="5" cols="40" name="merchant_content" style="width:98%"><?php print $merchant_content; ?></textarea>
				<br /><small><?php self::_e( 'Replace deal thumbnail area with this featured content. Shortcodes are accepted.' ); ?></small>
			</p>
			<?php
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
			self::save_meta_box_gb_theme_meta( $post_id, $post );
		}

		private static function save_meta_box_gb_theme_meta( $post_id, $post ) {
			if ( isset( $_POST['merchant_content'] ) ) {
				$merchant_content = $_POST['merchant_content'];
				self::set_merchant_content( $post_id, $merchant_content );
			}
		}

		public function set_merchant_content( $post_id, $merchant_content ) {
			update_post_meta( $post_id, self::$meta_keys['merchant_content'], $merchant_content );
			return $merchant_content;
		}

		public static function get_merchant_content( $post_id ) {
			$merchant_content = get_post_meta( $post_id, self::$meta_keys['merchant_content'], true );
			
			if ( empty( $merchant_content ) )
				return;
			
			// Strip out height and width of any media so that the theme can manage responsiveness
			if ( ( apply_filters( 'gb_merchant_content_response_sanitization', TRUE ) ) && // allow for this to be override easily.
				strpos( $merchant_content, 'iframe' )
				|| strpos( $merchant_content, 'embed' )
				|| strpos( $merchant_content, 'object' ) 
				) {
				$merchant_content = preg_replace( '/(width|height)=\"\d*\"\s/', "", $merchant_content ); 
			}
			
			return $merchant_content;
		}
	}
}
add_action( 'init', array( 'Group_Buying_Merchant_Content', 'init' )  );
