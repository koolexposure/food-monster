<?php 

class Group_Buying_Merchant_Meta extends Group_Buying_Controller {

	const META_FEATURED = 'gb_merchant_featured';
	const TAX = 'gb_merchant_featured';
	const TERM = 'merchants';
	const REWRITE_SLUG = 'featured';
	const DEBUG = TRUE;
	
	private static $meta_keys = array(
		'meta1' => '_gb_merchant_meta_1', // string
		'meta2' => '_gb_merchant_meta_2', // string
		'meta3' => '_gb_merchant_meta_3', // string
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.
	
	public static function init() {
		
  		add_action( 'init', array(get_class(), 'init_tax'), 0 );
		// Template
		add_filter('template_include', array( get_class(), 'override_template' ) );
		// Meta Boxes
		add_action( 'add_meta_boxes', array(get_class(), 'add_meta_boxes'));
		add_action( 'save_post', array( get_class(), 'save_meta_boxes' ), 10, 2 );
		
	}
	
	public static function init_tax() {
		// register taxonomy
		$taxonomy_args = array(
			'hierarchical' => TRUE,
			'labels' => array('name' => gb__('Featured Merchant')),
			'show_ui' => FALSE,
			'rewrite' => array(
				'slug' => self::REWRITE_SLUG,
				'with_front' => FALSE,
				'hierarchical' => FALSE,
			),
		);
		register_taxonomy( self::TAX, array(Group_Buying_Merchant::POST_TYPE), $taxonomy_args );
	}

	public static function get_term_slug() {
		$term = get_term_by('slug', self::TERM, self::TAX);
		if ( !empty($term->slug) ) {
			return $term->slug;
		} else {
			$return = wp_insert_term(
				self::TERM, // the term 
				self::TAX, // the taxonomy
					array(
						'description'=> 'This merchant is featured.',
						'slug' => self::TERM, )
				);
			return $return['slug'];
		}

	}
	
	public static function get_url() {
		$url = get_term_link( self::TERM, self::TAX);
		if ( $url ) {
			return $url;
		}
	}

	public static function is_merchant_query( WP_Query $query = NULL ) {
		$taxonomy = get_query_var('taxonomy');
		if ( $taxonomy == self::TAX || $taxonomy == self::TAX || $taxonomy == self::TAX ) {
			return TRUE;
		}
		return FALSE;
	}
	
	public static function override_template( $template ) {
		if ( self::is_merchant_query() ) {
			$taxonomy = get_query_var('taxonomy');
			$template = self::locate_template(array(
				'business/business-'.$taxonomy.'.php',
				'business/business-type.php',
				'business/business-types.php',
				'business/businesses.php',
				'business/business-index.php',
				'business/business-archive.php',
				'merchant/business-'.$taxonomy.'.php',
				'merchant/business-type.php',
				'merchant/business-types.php',
				'merchant/businesses.php',
				'merchant/business-index.php',
				'merchant/business-archive.php',
			), $template);
		}
		return $template;
	}
	
	/**
	 * @return int Alternative Price
	 */
	public function is_featured( Group_Buying_Merchant $merchant ) {
		$featured = array_pop(wp_get_object_terms( $merchant->get_id(), self::TAX));
		if ( !empty($featured) && $featured->slug = self::TERM ) {
			return TRUE;
		}
		return FALSE;
	}
	
	public static function add_meta_boxes() {
		add_meta_box('gb_merchant_meta', self::__('Custom Information'), array(get_class(), 'show_meta_boxes'), Group_Buying_Merchant::POST_TYPE, 'advanced', 'high');
	}

	public static function show_meta_boxes( $post, $metabox ) {
		switch ( $metabox['id'] ) {
			case 'gb_merchant_meta':
				self::show_meta_box($post, $metabox);
				break;
			default:
				self::unknown_meta_box($metabox['id']);
				break;
		}
	}

	private static function show_meta_box( $post, $metabox ) {
		$term = array_pop(wp_get_object_terms( $post->ID, self::TAX));
		$merchant = FALSE;
		if ( !empty($term) && $term->slug = self::TERM ) {
			$merchant = TRUE;
		}
		?>
			<table class="form-table">
				<tbody>
					<tr>
						<td>
							<label for="<?php echo self::META_FEATURED ?>"><input type="checkbox" id="<?php echo self::META_FEATURED ?>" name="<?php echo self::META_FEATURED ?>" <?php checked($merchant,TRUE) ?> value="1"/> <?php gb_e('Featured Merchant') ?></label>
						</td>
					</tr>
					<tr>
						<td>
							<label for="<?php echo self::$meta_keys['meta1'] ?>"><?php gb_e('Custom Field One') ?></label><br />
						<?php wp_editor( self::get_meta1($post->ID), self::$meta_keys['meta1'], array( 'textarea_name' => self::$meta_keys['meta1']) ); ?>
							
						</td>
					</tr>
					<tr>
						<td>
							<label for="<?php echo self::$meta_keys['meta2'] ?>"><?php gb_e('Custom Field Two') ?></label><br />
						<?php wp_editor( self::get_meta2($post->ID), self::$meta_keys['meta2'], array( 'textarea_name' => self::$meta_keys['meta2']) ); ?>
                    
						</td>
					</tr>
					<tr>
						<td>
							<label for="<?php echo self::$meta_keys['meta3'] ?>"><?php gb_e('Custom Field Three') ?></label><br />
						<?php wp_editor( self::get_meta3($post->ID), self::$meta_keys['meta3'], array( 'textarea_name' => self::$meta_keys['meta3']) ); ?>
                    
						</td>
					</tr>
				</tbody>
			</table>
		<?php
	}

	public static function save_meta_boxes( $post_id, $post ) {
		// only continue if it's an account post
		if ( $post->post_type != Group_Buying_Merchant::POST_TYPE ) {
			return;
		}
		// don't do anything on autosave, auto-draft, bulk edit, or quick edit
		if ( wp_is_post_autosave( $post_id ) || $post->post_status == 'auto-draft' || defined('DOING_AJAX') || isset($_GET['bulk_edit']) ) {
			return;
		}
		self::save_meta_box($post_id, $post);
	}

	private static function save_meta_box( $post_id, $post ) {
		$merchant = ( isset( $_POST[self::META_FEATURED] ) && $_POST[self::META_FEATURED] == '1' ) ? self::get_term_slug() : null;
		wp_set_object_terms( $post_id, $merchant, self::TAX );

		$meta1 = isset( $_POST[self::$meta_keys['meta1']] ) ? $_POST[self::$meta_keys['meta1']] : '';
		$meta2 = isset( $_POST[self::$meta_keys['meta2']] ) ? $_POST[self::$meta_keys['meta2']] : '';
		$meta3 = isset( $_POST[self::$meta_keys['meta3']] ) ? $_POST[self::$meta_keys['meta3']] : '';
        
		self::set_meta1( $post_id, $meta1 );
		self::set_meta2( $post_id, $meta2 );
		self::set_meta3( $post_id, $meta3 );
	}

	public static function get_meta1( $merchant_id ) {
		$merchant = Group_Buying_Merchant::get_instance($merchant_id);
		$meta1 = $merchant->get_post_meta( self::$meta_keys['meta1'] );
		return $meta1;
	}
    
	public static function set_meta1( $merchant_id, $meta1 ) {
		$merchant = Group_Buying_Merchant::get_instance($merchant_id);
		$merchant->save_post_meta( array(
			self::$meta_keys['meta1'] => $meta1
		));
		return $meta1;
	}
    
	public static function get_meta2( $merchant_id ) {
		$merchant = Group_Buying_Merchant::get_instance($merchant_id);
		$meta2 = $merchant->get_post_meta( self::$meta_keys['meta2'] );
		return $meta2;
	}
    
	public static function set_meta2( $merchant_id, $meta2 ) {
		$merchant = Group_Buying_Merchant::get_instance($merchant_id);
		$merchant->save_post_meta( array(
			self::$meta_keys['meta2'] => $meta2
		));
		return $meta2;
	}
    
	public static function get_meta3( $merchant_id ) {
		$merchant = Group_Buying_Merchant::get_instance($merchant_id);
		$meta3 = $merchant->get_post_meta( self::$meta_keys['meta3'] );
		return $meta3;
	}
    
	public static function set_meta3( $merchant_id, $meta3 ) {
		$merchant = Group_Buying_Merchant::get_instance($merchant_id);
		$merchant->save_post_meta( array(
			self::$meta_keys['meta3'] => $meta3
		));
		return $meta3;
	}
}


// Initiate the add-on
class Group_Buying_Merchant_Meta_Addon extends Group_Buying_Controller {
	
	public static function init() {
		// Hook this plugin into the GBS add-ons controller
		add_filter('gb_addons', array(get_class(),'gb_merchant_meta_addon'), 10, 1);
	}

	public static function gb_merchant_meta_addon( $addons ) {
		$addons['merchant_meta'] = array(
			'label' => self::__('Merchant Meta'),
			'description' => self::__('Add Meta Fields to the merchant post type.'),
			'files' => array(
				__FILE__,
			),
			'callbacks' => array(
				array('Group_Buying_Merchant_Meta', 'init'),
			),
		);
		return $addons;
	}

}

