<?php

/**
 * GBS Attribute Model
 *
 * @package GBS
 * @subpackage Attribute
 */
class Group_Buying_Attribute extends Group_Buying_Post_Type {
	const POST_TYPE = 'gb_attribute';
	const NO_MAXIMUM = -1;
	const DEFAULT_PRICE = -1;
	const TAXONOMY_PREFIX = 'gb_attribute_tax_';
	const REGISTERED_TAXONOMIES_OPTION = 'gb_attribute_registered_taxonomies';
	const ATTRIBUTE_DATA_KEY = 'attribute_id';
	const CACHE_PREFIX = 'gbs_attribute';
	const CACHE_GROUP = 'gbs_attribute';

	private static $instances = array();
	private static $taxonomies = array();

	private static $meta_keys = array(
		'deal_id' => '_deal_id', // int
		'max_purchases' => '_max_purchases', // int
		'sku' => '_sku', // string
		'price' => '_price',
	);


	public static function init() {
		// register attribute post type
		$post_type_args = array(
			'public' => FALSE,
			'has_archive' => FALSE,
			'show_in_menu' => FALSE,
			'supports' => array( 'title', 'editor', 'revisions' ),
			'rewrite' => FALSE,
		);
		self::register_post_type( self::POST_TYPE, 'Attribute', 'Attributes', $post_type_args );
		add_action( 'init', array( get_class(), 'register_attribute_taxonomies' ), 0, 0 );
		add_action( 'init', array( get_class(), 'create_default_terms' ), 100, 0 );
		add_action( 'admin_menu', array( get_class(), 'relocated_attribute_taxonomy_menu' ), 15, 0 );

		add_action( 'update_post_meta', array( get_class(), 'maybe_clear_attribute_cache' ), 10, 4 );
		add_action( 'added_post_meta', array( get_class(), 'maybe_clear_attribute_cache' ), 10, 4 );
		add_action( 'delete_post_meta', array( get_class(), 'maybe_clear_attribute_cache' ), 10, 4 );
	}

	public static function relocated_attribute_taxonomy_menu() {
		foreach ( self::$taxonomies as $key => $tax ) {
			$taxonomy = get_taxonomy( self::TAXONOMY_PREFIX.$key );
			add_submenu_page( 'edit.php?post_type='.Group_Buying_Deal::POST_TYPE, $taxonomy->labels->name, $taxonomy->labels->name, 'edit_posts', 'edit-tags.php?taxonomy='.self::TAXONOMY_PREFIX.$key.'&post_type='.self::POST_TYPE );
		}
	}
	/**
	 * Registers the attribute taxonomies. Plugins can filter the taxonomies and the default terms.
	 *
	 * @static
	 * @return void
	 */
	public static function register_attribute_taxonomies() {
		self::$taxonomies = array(
			'size' => array(
				'singular' => self::__( 'Size' ),
				'plural' => self::__( 'Sizes' ),
				'default_terms' => array(
					'small' => self::__( 'Small' ),
					'medium' => self::__( 'Medium' ),
					'large' => self::__( 'Large' ),
				),
			),
			'color' => array(
				'singular' => self::__( 'Color' ),
				'plural' => self::__( 'Colors' ),
				'default_terms' => array(
					'red' => self::__( 'Red' ),
					'orange' => self::__( 'Orange' ),
					'yellow' => self::__( 'Yellow' ),
					'green' => self::__( 'Green' ),
					'blue' => self::__( 'Blue' ),
					'purple' => self::__( 'Purple' ),
				)
			)
		);
		self::$taxonomies = apply_filters( 'gb_attribute_taxonomies', self::$taxonomies );
		foreach ( self::$taxonomies as $key => $taxonomy ) {
			if ( !is_array( $taxonomy ) ) {
				$singular = $taxonomy;
				$taxonomy = array();
				if ( is_numeric( $key ) ) {
					unset( self::$taxonomies[$key] );
					$key = $singular;
				}
				$taxonomy['singular'] = $singular;
				self::$taxonomies[$key] = $singular;
			}
			if ( !isset( $taxonomy['singular'] ) ) {
				$taxonomy['singular'] = '';
			}
			if ( !isset( $taxonomy['plural'] ) ) {
				$taxonomy['plural'] = '';
			}
			if ( !isset( $taxonomy['args'] ) ) {
				$taxonomy['args'] = array();
			}
			$args = array(
				'rewrite' => array(
					'slug' => 'deal-attribute-'.$key,
					'with_front' => TRUE,
					'hierarchical' => TRUE,
				),
			);
			$args = wp_parse_args( $taxonomy['args'], $args );
			self::register_taxonomy( self::TAXONOMY_PREFIX.$key, self::POST_TYPE, $taxonomy['singular'], $taxonomy['plural'], $args );
		}
	}

	/**
	 * Creates default terms the first time a taxonomy is registered
	 *
	 * @static
	 * @return void
	 */
	public static function create_default_terms() {
		$registered = get_option( self::REGISTERED_TAXONOMIES_OPTION, array() );
		foreach ( self::$taxonomies as $key => $taxonomy ) {
			if ( !is_array( $taxonomy ) || !isset( $taxonomy['default_terms'] ) || !$taxonomy['default_terms'] ) {
				continue; // doesn't have any default terms
			}
			if ( in_array( $key, $registered ) ) {
				continue; // already created the default terms
			}
			foreach ( (array)$taxonomy['default_terms'] as $slug => $term ) {
				$args = array();
				if ( !is_numeric( $slug ) ) {
					$args['slug'] = $slug;
				}
				wp_insert_term( $term, self::TAXONOMY_PREFIX.$key, $args );
			}
			$registered[] = $key;
		}
		update_option( self::REGISTERED_TAXONOMIES_OPTION, $registered );
	}

	public static function get_attribute_taxonomies() {
		$taxa = array();
		foreach ( self::$taxonomies as $key => $taxonomy ) {
			$taxa[] = get_taxonomy( self::TAXONOMY_PREFIX.$key );
		}
		return $taxa;
	}

	/**
	 * @static
	 * @param int $deal_id
	 * @param string $format The format of the return value. 'post', 'object', or 'id'
	 * @return array
	 */
	public static function get_attributes( $deal_id, $format = 'id' ) {
		$cache = self::get_attributes_cache($deal_id);
		switch ( $format ) {
			case 'post':
				if ( is_array($cache) && empty($cache) ) {
					// no posts to find
					return array();
				}
				$args = array(
					'post_type' => self::POST_TYPE,
					'order' => 'ASC',
					'orderby' => 'id',
					'numberposts' => -1
				);
				if ( $cache ) {
					// avoid the join with the meta table by using the ID cache
					$args['post__in'] = $cache;
				} else {
					$args['meta_query'] = array(
						array(
							'key' => self::$meta_keys['deal_id'],
							'value' => $deal_id,
							'type' => 'NUMERIC',
						),
					);
				}
				$posts = get_posts( $args );
				if ( !$cache ) {
					self::set_attributes_cache($deal_id, wp_list_pluck($posts, 'ID'));
				}
				return $posts;
				break;
			case 'object':
				$ids = ( $cache !== FALSE ) ? $cache : self::get_attributes($deal_id, 'id');
				$attributes = array();
				foreach ( $ids as $post_id ) {
					$attributes[$post_id] = self::get_instance( $post_id );
				}
				return $attributes;
			case 'id':
			default:
				if ( $cache !== FALSE ) {
					return $cache;
				}
				$posts = self::get_attributes($deal_id, 'post');
				$ids = wp_list_pluck($posts, 'ID');
				return $ids;
		}
	}

	/**
	 * When the _deal_id post meta for an attribute is updated,
	 * flush the cache for both the old deal ID and the new
	 *
	 * @wordpress-action update_post_meta (update_{$meta_type}_meta)
	 * @wordpress-action added_post_meta (added_{$meta_type}_meta)
	 * @wordpress-action delete_post_meta (delete_{$meta_type}_meta)
	 *
	 * @static
	 * @param int|array $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $_meta_value
	 */
	public static function maybe_clear_attribute_cache( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( $meta_key == self::$meta_keys['deal_id'] && get_post_type($object_id) == self::POST_TYPE ) {
			if ( $meta_value ) { // this might be empty on a delete_post_meta
				self::clear_attributes_cache($meta_value);
			}
			$old_value = get_post_meta($object_id, $meta_key);
			if ( $old_value != $meta_value ) { // this is probably the same on added_post_meta
				self::clear_attributes_cache($old_value);
			}
		}
	}

	/**
	 * Get the cache of IDs of attributes associated with the deal
	 *
	 * @param int $deal_id
	 * @return array
	 */
	private function get_attributes_cache( $deal_id ) {
		$ids = wp_cache_get(self::CACHE_PREFIX.'_id_'.$deal_id, self::CACHE_GROUP);
		if ( !is_array($ids) ) {
			return FALSE;
		}
		return $ids;
	}


	/**
	 * Set the attribute IDs cache for the deal
	 *
	 * @static
	 * @param int $deal_id
	 * @param array $attribute_ids
	 */
	private static function set_attributes_cache( $deal_id, $attribute_ids ) {
		wp_cache_set(self::CACHE_PREFIX.'_id_'.$deal_id, $attribute_ids, self::CACHE_GROUP);
	}

	/**
	 * Delete the attribute IDs cache for the deal
	 *
	 * @static
	 * @param int $deal_id
	 */
	private static function clear_attributes_cache( $deal_id ) {
		wp_cache_delete(self::CACHE_PREFIX.'_id_'.$deal_id, self::CACHE_GROUP);
	}

	protected function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Group_Buying_Attribute
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

	public static function new_attribute( $deal_id, $args ) {
		$default_args = array(
			'title' => self::__( 'New Attribute' ),
			'price' => '',
			'description' => '',
			'max_purchases' => self::NO_MAXIMUM,
			'sku' => '',
		);
		$args = wp_parse_args( $args, $default_args );
		$post = array(
			'post_title' => $args['title'],
			'post_content' => $args['description'],
			'post_type' => self::POST_TYPE,
			'post_status' => 'publish',
		);
		$id = wp_insert_post( $post );
		if ( !is_wp_error( $id ) ) {
			$att = self::get_instance( $id );
			$att->set_deal_id( $deal_id );
			$att->set_price( $args['price'] );
			$att->set_max_purchases( $args['max_purchases'] );
			$att->set_sku( $args['sku'] );
			$att->set_categories( $args['categories'] );
		}
		return $id;
	}


	/**
	 * Destroy this attribute
	 *
	 * @return void
	 */
	public function remove() {
		wp_delete_post( $this->ID, TRUE );
		unset( $this->post );
		$this->ID = 0;
	}

	/**
	 * Update with new information
	 *
	 * @param array   $data
	 * @return void
	 */
	public function update( $data ) {
		if ( isset( $data['title'] ) || isset( $data['description'] ) ) {
			if ( isset( $data['title'] ) ) {
				$this->post->post_title = $data['title'];
			}
			if ( isset( $data['description'] ) ) {
				$this->post->post_content = $data['description'];
			}
			wp_update_post( $this->post );
		}

		if ( isset( $data['categories'] ) ) {
			$this->set_categories( $data['categories'] );
		}

		// use the setter for each meta key to update
		foreach ( array_keys( self::$meta_keys ) as $key ) {
			if ( $key != 'deal_id' && isset( $data[$key] ) ) {
				$function = 'set_'.$key;
				if ( is_callable( array( $this, $function ) ) ) {
					call_user_func( array( $this, $function ), $data[$key] );
				}
			}
		}
	}

	private function set_deal_id( $id ) {
		$deal = Group_Buying_Deal::get_instance( $id );
		if ( $deal ) {
			self::save_post_meta( array( self::$meta_keys['deal_id'] => $id ) );
		}
	}

	public function get_deal_id() {
		return self::get_post_meta( self::$meta_keys['deal_id'] );
	}

	public function get_title() {
		return $this->post->post_title;
	}

	public function set_title( $title ) {
		$this->post->post_title = $title;
		wp_update_post( $this->post );
	}

	public function get_price() {
		$price = self::get_post_meta( self::$meta_keys['price'] );
		if ( $price == self::DEFAULT_PRICE ) {
			return $price;
		}
		return gb_get_number_format( $price );
	}

	public function the_price() {
		$price = $this->get_price();
		if ( $price == self::DEFAULT_PRICE ) {
			$deal = Group_Buying_Deal::get_instance( $this->get_deal_id() );
			$price = $deal->get_price();
		}
		return gb_get_number_format( $price );
	}

	public function set_price( $price ) {
		if ( $price < 0 ) {
			$price = self::DEFAULT_PRICE;
		}
		self::save_post_meta( array( self::$meta_keys['price'] => $price ) );
	}

	public function get_description() {
		return $this->post->post_content;
	}

	public function set_description( $description ) {
		$this->post->post_content = $description;
		wp_update_post( $this->post );
	}

	public function set_max_purchases( $max ) {
		$max = (int)$max;
		if ( $max < 0 ) {
			$max = self::NO_MAXIMUM;
		}
		self::save_post_meta( array( self::$meta_keys['max_purchases'] => $max ) );
	}

	public function get_max_purchases() {
		return self::get_post_meta( self::$meta_keys['max_purchases'] );
	}

	public function set_sku( $sku ) {
		self::save_post_meta( array( self::$meta_keys['sku'] => $sku ) );
	}

	public function get_sku() {
		return self::get_post_meta( self::$meta_keys['sku'] );
	}

	public function set_categories( $categories ) {
		foreach ( $categories as $taxonomy => $term_id ) {
			wp_set_object_terms( $this->ID, $term_id, $taxonomy );
		}
	}

	public function get_categories() {
		$terms = array();
		foreach ( array_keys( self::$taxonomies ) as $key ) {
			$post_terms = wp_get_post_terms( $this->ID, self::TAXONOMY_PREFIX.$key, array( 'fields' => 'ids' ) );
			if ( $post_terms && !is_wp_error( $post_terms ) ) {
				$terms[self::TAXONOMY_PREFIX.$key] = $post_terms[0];
			}
		}
		return $terms;
	}

	public function remaining_purchases() {
		$max = $this->get_max_purchases();
		if ( $max == self::NO_MAXIMUM ) {
			return self::NO_MAXIMUM;
		}

		// How many have been purchased?
		$deal = Group_Buying_Deal::get_instance( $this->get_deal_id() );
		$purchases = $deal->get_purchases();
		$number_of_purchases = 0;
		foreach ( $purchases as $purchase ) {
			/* @var Group_Buying_Purchase $purchase */
			if ( $purchase->is_complete() || $purchase->is_pending() ) {
				$purchase_quantity = $purchase->get_product_quantity( $deal->get_id(), array( self::ATTRIBUTE_DATA_KEY => $this->ID ) );
				$number_of_purchases += $purchase_quantity;
			}
		}

		// How many does that leave?
		if ( $max - $number_of_purchases > 0 ) {
			return $max - $number_of_purchases;
		} else {
			return 0;
		}
	}
}
