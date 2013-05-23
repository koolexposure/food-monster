<?php

/**
 * GBS Record Model
 *
 * @package GBS
 * @subpackage Record
 */
class Group_Buying_Record extends Group_Buying_Post_Type {

	const POST_TYPE = 'gb_record';
	const TAXONOMY = 'gb_record_type'; // TODO

	private static $instances = array();

	private static $meta_keys = array(
		'associate_id' => '_associate', // int
		'data' => '_data', // string
		'type' => '_type', // string
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.

	public static function init() {
		$post_type_args = array(
			'has_archive' => FALSE,
			'show_in_menu' => FALSE,
			'rewrite' => FALSE,
		);
		self::register_post_type( self::POST_TYPE, 'Record', 'Records', $post_type_args );
	}

	protected function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Group_Buying_Gift
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

	public static function new_record( $message, $type = 'mixed', $title = '', $author = 1, $associate_id = -1, $data = array() ) {
		$post = array(
			'post_title' => $title,
			'post_content' => $message,
			'post_author' => $author,
			'post_status' => 'pending',
			'post_type' => self::POST_TYPE,
		);
		$id = wp_insert_post( $post );
		if ( !is_wp_error( $id ) ) {
			$record = self::get_instance( $id );
			$record->set_associate_id( $associate_id );
			$record->set_data( $data );
			$record->set_type( $type );
		}
		return $id;
	}

	public function activate() {
		$this->post->post_status = 'publish';
		$this->save_post();
		do_action( 'record_activated', $this );
	}

	/**
	 *
	 *
	 * @return int The ID of the content associated with this record
	 */
	public function get_associate_id() {
		$associate_id = $this->get_post_meta( self::$meta_keys['associate_id'] );
		return $associate_id;
	}

	/**
	 * Associate this record with content
	 *
	 * @param int     $id The new value
	 * @return int The ID of the content associated with this record
	 */
	public function set_associate_id( $associate_id ) {
		$this->save_post_meta( array(
				self::$meta_keys['associate_id'] => $associate_id
			) );
		return $associate_id;
	}

	/**
	 *
	 *
	 * @return array The data
	 */
	public function get_data() {
		$data = $this->get_post_meta( self::$meta_keys['data'] );
		return $data;
	}

	/**
	 * Set data
	 *
	 * @param array   The data
	 * @return array The data
	 */
	public function set_data( $data ) {
		$this->save_post_meta( array(
				self::$meta_keys['data'] => $data
			) );
		return $data;
	}


	/**
	 *
	 *
	 * @return array The type
	 */
	public function get_type() {
		$type = $this->get_post_meta( self::$meta_keys['type'] );
		return $type;
	}

	/**
	 * Set type
	 *
	 * @param array   The type
	 * @return array The type
	 */
	public function set_type( $type ) {
		$this->save_post_meta( array(
				self::$meta_keys['type'] => $type
			) );
		return $type;
	}


	/**
	 *
	 *
	 * @param int     $type the associate content id
	 * @return array List of IDs for records of this type
	 */
	public static function get_records_by_type_and_association( $associate_id, $type ) {
		$records = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['associate_id'] => $associate_id, self::$meta_keys['type'] => $type ) );
		return $records;
	}


	/**
	 *
	 *
	 * @param int     $type the associate content id
	 * @return array List of IDs for records of this type
	 */
	public static function get_records_by_type( $type ) {
		$records = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['type'] => $type ) );
		return $records;
	}

	/**
	 *
	 *
	 * @param int     $associate_id the associate content id
	 * @return array List of IDs for records with this association
	 */
	public static function get_records_by_association( $associate_id ) {
		$records = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['associate_id'] => $associate_id ) );
		return $records;
	}
}
