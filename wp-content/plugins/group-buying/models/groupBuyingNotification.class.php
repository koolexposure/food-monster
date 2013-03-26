<?php

/**
 * GBS Notification Model
 *
 * @package GBS
 * @subpackage Notification
 */
class Group_Buying_Notification extends Group_Buying_Post_Type {
	const POST_TYPE = 'gb_notification';
	const REWRITE_SLUG = 'notifications';
	private static $instances = array();
	private static $meta_keys = array(
		'disabled' => '_disabled', // bool
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.


	public static function init() {
		// register Notification post type
		$post_type_args = array(
			'public' => FALSE,
			'has_archive' => FALSE,
			'show_ui' => FALSE,
			'show_in_menu' => 'group-buying',
			'supports' => array( 'title', 'editor', 'revisions' ),
			'rewrite' => array(
				'slug' => self::REWRITE_SLUG,
				'with_front' => FALSE,
			),
		);
		self::register_post_type( self::POST_TYPE, 'Notification', 'Notifications', $post_type_args );
	}

	protected function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Group_Buying_Notification
	 */
	public static function get_instance( $id = 0 ) {
		if ( !$id ) {
			return NULL;
		}
		if ( !isset( self::$instances[$id] ) || !self::$instances[$id] instanceof self ) {
			self::$instances[$id] = new self( $id );
		}
		if ( self::$instances[$id]->post->post_type != self::POST_TYPE ) {
			return NULL;
		}
		return self::$instances[$id];
	}

	public function is_disabled() {
		$disabled = $this->get_post_meta( self::$meta_keys['disabled'] );
		if ( 'TRUE' == $disabled ) {
			return TRUE;
		}
		return;
	}

	public function get_disabled() {
		$disabled = $this->get_post_meta( self::$meta_keys['disabled'] );
		return $disabled;
	}

	public function set_disabled( $disabled ) {
		$this->save_post_meta( array(
				self::$meta_keys['disabled'] => $disabled
			) );
		return $disabled;
	}

	// A pretty basic post type. Not much else to do here.
}
