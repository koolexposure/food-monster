<?php

/**
 * Attributes controller, GBS add-on
 *
 * @package GBS
 * @subpackage Attribute
 */
class Group_Buying_Attributes extends Group_Buying_Controller {
	const ATTRIBUTE_QUERY_VAR = 'attribute_id';
	const VOUCHER_ATTRIBUTE_META = '_attribute_id';
	const SAVE_POST_PRIORITY = 15;

	public static function init() {
		add_action( 'add_meta_boxes', array( get_class(), 'add_meta_boxes' ) );
		add_action( 'save_post', array( get_class(), 'save_meta_boxes' ), self::SAVE_POST_PRIORITY, 2 );
		add_filter( 'gb_add_to_cart_form_fields', array( get_class(), 'filter_add_to_cart_form_fields' ), 10, 2 );
		add_action( 'purchase_completed', array( get_class(), 'purchase_completed' ), 6, 1 );
		add_filter( 'gb_deal_title', array( get_class(), 'filter_deal_title' ), 10, 2 );

		add_filter( 'gb_get_deal_price_meta', array( get_class(), 'filter_deal_price' ), 10, 4 );
		add_filter( 'gb_deal_price', array( get_class(), 'filter_deal_price' ), 10, 4 );

		add_filter( 'add_to_cart_data', array( get_class(), 'filter_add_to_cart_data' ), 10, 3 );
		add_filter( 'account_can_purchase', array( get_class(), 'filter_account_can_purchase' ), 10, 3 );
		add_filter( 'cart_quantity_allowed', array( get_class(), 'filter_account_can_purchase' ), 10, 3 );


		add_filter( 'gb_purchase_deal_column_details', array( get_class(), 'show_purchase_details' ), 10, 2 );

		add_filter( 'gb_get_add_to_cart_url', array( get_class(), 'filter_add_to_cart_url' ), 10, 2 );

		add_action( 'create_voucher_for_purchase', array( get_class(), 'set_vouchers_attribute_id' ), 10, 3 );
		add_filter( 'add_to_cart_redirect_url', array( get_class(), 'filter_add_to_cart_redirect_url' ) );

		/// Purchase Report
		add_filter( 'set_deal_purchase_report_data_column', array( get_class(), 'set_deal_purchase_report_data_column' ), 10, 1 );
		add_filter( 'set_deal_purchase_report_data_records', array( get_class(), 'set_deal_purchase_report_data_records' ), 10, 1 );
		// Merchant Report
		add_filter( 'set_merchant_purchase_report_column', array( get_class(), 'set_deal_purchase_report_data_column' ), 10, 1 );
		add_filter( 'set_merchant_purchase_report_records', array( get_class(), 'set_deal_purchase_report_data_records' ), 10, 1 );
		/// Vouchers
		add_filter( 'set_deal_voucher_report_data_column', array( get_class(), 'set_deal_purchase_report_data_column' ), 10, 1 );
		add_filter( 'set_deal_voucher_report_data_records', array( get_class(), 'set_deal_purchase_report_data_records' ), 10, 1 );
		// Merchant Report
		add_filter( 'set_merchant_voucher_report_data_column', array( get_class(), 'set_deal_purchase_report_data_column' ), 10, 1 );
		add_filter( 'set_merchant_voucher_report_data_records', array( get_class(), 'set_deal_purchase_report_data_records' ), 10, 1 );

		// Filter redirect_to link on login and registration page
		add_filter( 'load_view_args_account/login.php', array( get_class(), 'filter_redirect_to' ), 10, 1 );
		add_filter( 'load_view_args_account/register.php', array( get_class(), 'filter_redirect_to' ), 10, 1 );
		add_filter( 'gb_get_account_register_url', array( get_class(), 'filter_register' ), 10, 1 );

		// Filter voucher titles
		add_filter( 'the_title', array( get_class(), 'filter_voucher_titles' ), 10, 2 );

		// add attributes to the admin purchase form
		add_action( 'gb_account_purchases_meta_box_top', array( get_class(), 'edit_admin_purchases_form' ), 11, 0 );
		add_action( 'wp_ajax_nopriv_gbs_ajax_get_attributes',  array( get_class(), 'ajax_get_attributes' ), 10, 0 );
		add_action( 'wp_ajax_gbs_ajax_get_attributes',  array( get_class(), 'ajax_get_attributes' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_gbs_ajax_query_attributes',  array( get_class(), 'ajax_query_attributes' ), 10, 0 );
		add_action( 'wp_ajax_gbs_ajax_query_attributes',  array( get_class(), 'ajax_query_attributes' ), 10, 0 );
		add_filter( 'gbs_admin_purchase_data', array( get_class(), 'filter_admin_purchase_data' ), 10, 2 );

		// Delayed init so that themes and other plugins could filter
		add_action( 'init', array( get_class(), 'delayed_init' ), 100 );
	}

	public function delayed_init() {
		// Add attribute selections to the deal submission form.
		if ( apply_filters( 'gb_deal_submission_attributes', __return_false() ) ) {
			add_filter( 'gb_deal_submission_fields', array( get_class(), 'filter_deal_submission_fields'), 10, 1 );
			add_filter( 'gb_get_form_field', array( get_class(), 'attribute_form_field'), 10, 4 );
			add_action( 'submit_deal',  array( get_class(), 'submit_deal' ), 10, 1 );
		}
	}

	public function activate_dynamic_category_selection() {
		add_filter( 'gb_add_to_cart_form_fields', array( get_class(), 'filter_add_to_cart_add_category_selection' ), 10, 2 );
	}

	public static function filter_voucher_titles( $title, $id = 0 ) {
		if ( get_post_type( $id ) == Group_Buying_Voucher::POST_TYPE ) {
			$attributes = gb_get_attribute_title_by_voucher_id( $id );
			if ( !empty( $attributes ) ) {
				$title .= ' ('.$attributes.')';
			}
		}
		return $title;
	}

	public static function set_deal_purchase_report_data_column( $columns ) {
		$columns['label'] = self::__( 'Label' );
		$columns['price'] = self::__( 'Price(s)' );
		return $columns;
	}
	public static function set_deal_purchase_report_data_records( $array ) {
		if ( !is_array( $array ) ) {
			return; // nothing to do.
		}
		$new_array = array();
		foreach ( $array as $records ) {
			// Add labels
			$items = array();
			$label = array();
			$prices = array();
			if ( !empty( $records['voucher_id'] ) ) {
				$attribute_id = self::get_vouchers_attribute_id( $records['voucher_id'] );
				$purchase = Group_Buying_Purchase::get_instance( $records['id'] );
				if ( !is_array( $attribute_id ) ) {
					$label = array( 'label' => get_the_title( $attribute_id ) );
					// Set Correct Price
					foreach ( $purchase->get_products() as $product => $value ) {
						if ( !empty( $value['data']['attribute_id'] ) && $value['data']['attribute_id'] == $attribute_id ) {
							$records['price'] = gb_get_formatted_money( $value['unit_price'] );
						}
					}
				} else {
					$attribute_title = gb_get_attribute_title_by_voucher_id( $records['voucher_id'], $attribute_id );
					if ( !empty( $attribute_title ) ) {
						$label = array( 'label' => $attribute_title );
					}
				}
			} else {
				$purchase = Group_Buying_Purchase::get_instance( $records['id'] );
				foreach ( $purchase->get_products() as $product => $value ) {
					if ( !empty( $value['data']['attribute_id'] ) && $value['deal_id'] == $_GET['id'] ) {
						for ( $i=0; $i < $value['quantity']; $i++ ) {
							$items[] = get_the_title( $value['data']['attribute_id'] );
							$prices[] = gb_get_formatted_money( $value['unit_price'] );
						}
					}
				}
				if ( !empty( $prices ) ) {
					$records['price'] = implode( ', ', $prices );
				}
				$label = array( 'label' => implode( ', ', $items ) );
			}

			$new_array[] = array_merge( $records, $label );
		}
		return $new_array;
	}

	public static function filter_add_to_cart_url( $url, $post_id ) {
		if ( get_post_type( $post_id ) == Group_Buying_Attribute::POST_TYPE ) {
			$attribute = Group_Buying_Attribute::get_instance( $post_id );
			$deal_id = $attribute->get_deal_id();
			return apply_filters( 'gb_add_to_cart_url_att', add_query_arg( array( 'attribute_id' => $post_id ), Group_Buying_Carts::add_to_cart_url( $deal_id ) ) );
		}
		if ( gb_deal_has_attributes( $post_id ) ) {
			return apply_filters( 'gb_add_to_cart_url_att', add_query_arg( array( 'option' => $post_id ), get_permalink( $post_id ) ) );
		}
		return $url;
	}

	public static function filter_add_to_cart_redirect_url( $url ) {
		return remove_query_arg( array( self::ATTRIBUTE_QUERY_VAR ), $url );
	}

	/**
	 * If a deal has children, show them in a drop-down next to the add-to-cart button
	 *
	 * @static
	 * @param array   $fields
	 * @param int     $deal_id
	 * @return array
	 */
	public static function filter_add_to_cart_form_fields( $fields, $deal_id ) {
		$attributes = Group_Buying_Attribute::get_attributes( $deal_id, 'object' );
		if ( !$attributes ) {
			return $fields;
		}
		$options = array();
		foreach ( $attributes as $attribute ) {
			/* @var Group_Buying_Attribute $attribute */
			if ( $attribute->get_max_purchases() == Group_Buying_Attribute::NO_MAXIMUM || $attribute->remaining_purchases() > 0 ) {
				$title = $attribute->get_title();
				if ( $attribute->get_price() != Group_Buying_Attribute::DEFAULT_PRICE ) {
					$title .= ' - '.gb_get_formatted_money( $attribute->the_price() );
				}
				$options[] = '<option value="'.$attribute->get_id().'">'.$title.'</option>';
			}
		}
		if ( count( $options ) < 1 ) {
			$options[0] = '<option value="">'.self::__( 'Sold Out' ).'</option>';
		}
		if ( $options ) {
			$select = '<select name="'.self::ATTRIBUTE_QUERY_VAR.'">';
			$select .= implode( "\n", $options );
			$select .= '</select>';
			$fields[] = $select;
		}
		return $fields;
	}

	public function filter_add_to_cart_add_category_selection( $unfiltered_fields, $deal_id ) {
		$attributes = Group_Buying_Attribute::get_attributes( $deal_id, 'object' );
		if ( !$attributes ) {
			return $unfiltered_fields; 
		}
		$attribute_taxonomies = Group_Buying_Attribute::get_attribute_taxonomies();
		if ( !empty( $attribute_taxonomies ) ) {

			// Build pricing spans and collect which taxonomies this attribute uses.
			$prices = array();
			$categories = array();
			foreach ( $attributes as $attribute ) {
				/* @var Group_Buying_Attribute $attribute */
				$sold_out = ( $attribute->get_max_purchases() == Group_Buying_Attribute::NO_MAXIMUM || $attribute->remaining_purchases() > 0 ) ? '' : ' sold_out' ;
				$prices[] = '<span id="att_price_'.$attribute->get_id().'" class="attribute_price cloak'.$sold_out.'"><span class="price_label">Price:</span> '.gb_get_formatted_money( $attribute->the_price() ).'</span>';
				foreach ( $attribute->get_categories() as $category_name => $value) {
					if ( !in_array( $category_name, $categories ) ) {
						$categories[] = $category_name;
					}
				}
			}

			// If the attributes are not using categories don't attempt to use the dynamic selection
			if ( empty( $categories ) ) {
				return $unfiltered_fields; 
			}

			// Create a "field" with jQuery AJAX bits
			ob_start();
			?>
				<script type="text/javascript">
					jQuery(document).ready( function($) {
						
						var $add_to_cart_class = '<?php echo apply_filters( 'filter_add_to_cart_add_category_selection_add_form_selector', '.add-to-cart' ) ?>';
						var $dropdowns = $($add_to_cart_class + ' .gb-attribute-category-selections');

						// Create an array of the selected taxonomy term_ids
						var check_availability = function() {
							disable_submit();
							$.ajax({
								type: 'POST',
								dataType: 'json',
								url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
								data: {
									action: 'gbs_ajax_query_attributes',
									deal_id: <?php echo $deal_id ?>,
									selections: $dropdowns.serialize() // serialize all of the selection dropdowns for quering
								},
								success: function(attribute) {
									$att_id = attribute['0'];
									if ( $att_id ) {
										enable_submit($att_id);
									}
									else {
										$($add_to_cart_class + ' #ajax_gif').fadeOut().remove();
										$($add_to_cart_class + " input[type='submit']").fadeTo('slow', .5);
									};
								}
							});
						};
						var enable_submit = function( att_id ) {
							$($add_to_cart_class + ' #att_price_' + att_id).removeClass('cloak').fadeTo('slow', 1);
							$($add_to_cart_class + ' #ajax_gif').fadeOut().remove();
							$($add_to_cart_class + " select[name='attribute_id']").val(att_id);
							$($add_to_cart_class + " input[type='submit']").removeAttr('disabled');
							$($add_to_cart_class + " input[type='submit']").fadeTo('slow', 1);
						};
						var disable_submit = function() {
							// Disable and style the add to cart button right away
							$($add_to_cart_class + " input[type='submit']").attr('disabled','disabled');
							$($add_to_cart_class + " input[type='submit']").fadeTo('slow', .7);
							$($add_to_cart_class + " input[type='submit']").after(gb_ajax_gif);

							$($add_to_cart_class + ' .attribute_price').fadeOut();
						};
						// hide the selection
						$($add_to_cart_class + " select[name='attribute_id']").hide();
						// check the availability whenever the dropdowns are changed
						$dropdowns.change(check_availability);
						// check on load in case the first selections do not have an available attribute
						check_availability();
					});
				</script>
			<?php
			$fields[] = ob_get_clean();

			// Add the category selections
			foreach ( $attribute_taxonomies as $taxonomy ) {
				if ( in_array( $taxonomy->name, $categories ) ) {
					$drop_down = wp_dropdown_categories( array(
								'taxonomy' => $taxonomy->name,
								'name' => $taxonomy->name,
								'class' => 'gb-attribute-category-selections',
								'hide_empty' => FALSE,
								'echo' => 0
							) );
					$fields[] = '<span class="attribute_category_selection clearfix"><label for="'.$taxonomy->name.'">'.$taxonomy->labels->singular_name.': </label>' . $drop_down . '</span>';
				}
			}
			// Add the pricing spans
			if ( $prices ) {
				$price_html = implode( "\n", $prices );
				$fields[] = $price_html;
			}

			if ( !empty( $fields ) ) {
				return array_merge( $unfiltered_fields, $fields );
			}
		}
		return $fields;
	}

	public static function filter_add_to_cart_data( $data, $item_id, $quantity ) {
		if ( isset( $_REQUEST[self::ATTRIBUTE_QUERY_VAR] ) ) {
			$attribute = Group_Buying_Attribute::get_instance( $_REQUEST[self::ATTRIBUTE_QUERY_VAR] );
			if ( $attribute ) {
				if ( $attribute->get_deal_id() == $item_id ) { // make sure we have a valid attribute
					$data[Group_Buying_Attribute::ATTRIBUTE_DATA_KEY] = $_REQUEST[self::ATTRIBUTE_QUERY_VAR];
				}
			} else {
				$data = new WP_Error( 'invalid_selection', self::__( 'Invalid selection' ) );
			}
		}
		return $data;
	}

	public static function filter_account_can_purchase( $qty, $deal_id, $data = array() ) {
		if ( $qty < 1 ) {
			return $qty; // can't purchase any anyway
		}
		if ( !isset( $data[Group_Buying_Attribute::ATTRIBUTE_DATA_KEY] ) ) {
			return $qty; // isn't an attribute
		}
		$attribute = Group_Buying_Attribute::get_instance( $data[Group_Buying_Attribute::ATTRIBUTE_DATA_KEY] );
		if ( !is_a( $attribute, 'Group_Buying_Attribute' ) ) {
			return $qty;
		}
		if ( $attribute->get_deal_id() != $deal_id ) {
			return 0; // invalid child ID, so can't purchase any
		}

		$remaining = $attribute->remaining_purchases();
		if ( $remaining == Group_Buying_Attribute::NO_MAXIMUM ) {
			return $qty;
		}

		if ( $remaining < $qty ) {
			return $remaining;
		}

		return $qty;
	}

	public static function filter_deal_title( $title, $data ) {
		if ( !isset( $data[Group_Buying_Attribute::ATTRIBUTE_DATA_KEY] ) ) {
			return $title; // isn't an attribute
		}
		$attribute = Group_Buying_Attribute::get_instance( $data[Group_Buying_Attribute::ATTRIBUTE_DATA_KEY] );
		if ( is_a( $attribute, 'Group_Buying_Attribute' ) ) {
			$title .= ' ('.$attribute->get_title().')';
		}

		return $title;
	}

	public static function filter_deal_price( $price, Group_Buying_Deal $deal, $qty, $data ) {
		if ( !isset( $data[Group_Buying_Attribute::ATTRIBUTE_DATA_KEY] ) ) {
			return $price; // isn't an attribute
		}
		$attribute = Group_Buying_Attribute::get_instance( $data[Group_Buying_Attribute::ATTRIBUTE_DATA_KEY] );
		if ( is_a( $attribute, 'Group_Buying_Attribute' ) ) {
			$price = $attribute->the_price();
		}
		return $price;
	}

	public static function filter_redirect_to( $args ) {
		if ( isset( $_GET['amp;attribute_id'] ) && $_GET['amp;attribute_id'] != '' ) {
			$_GET['attribute_id'] = $_GET['amp;attribute_id'];
		}
		if ( isset( $_GET['attribute_id'] ) && $_GET['attribute_id'] != '' ) {
			$redirect = add_query_arg( array( 'attribute_id' => $_GET['attribute_id'] ), str_replace( home_url(), '', $_GET['redirect_to'] ) );
			$args['redirect'] = $redirect;
		}
		return $args;
	}


	public static function filter_register( $url ) {
		if ( isset( $_GET['amp;attribute_id'] ) && $_GET['amp;attribute_id'] != '' ) {
			$_GET['attribute_id'] = $_GET['amp;attribute_id'];
		}
		if ( isset( $_GET['attribute_id'] ) && $_GET['attribute_id'] != '' ) {
			$url = $url . '&attribute_id=' . $_GET['attribute_id'];
		}
		return $url;
	}
	/**
	 * If a child post was purchased, update its parent's purchase count, too
	 *
	 * @static
	 * @param Group_Buying_Purchase $purchase
	 * @return void
	 */
	public static function purchase_completed( Group_Buying_Purchase $purchase ) {
		$products = $purchase->get_products();
		foreach ( $products as $product ) {
			$post = get_post( $product['deal_id'] );
			if ( $post->post_parent ) {
				$deal = Group_Buying_Deal::get_instance( $post->post_parent );
				$deal->get_number_of_purchases( TRUE );
			}
		}
	}

	public static function get_attribute_name_by_purchase( $deal_id, $purchase_id ) {
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );

		foreach ( $purchase->get_products() as $product => $value ) {
			if ( !empty( $value['data']['attribute_id'] ) && $value['deal_id'] == $deal_id ) {
				$items[] = get_the_title( $value['data']['attribute_id'] );
			}
		}
		return array( 'title' => implode( ', ', $items ) );
	}

	public static function get_attribute_id_by_purchase( $deal_id, $purchase_id ) {
		$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
		$ids = array();
		foreach ( $purchase->get_products() as $product => $value ) {
			if ( !empty( $value['data']['attribute_id'] ) && $value['deal_id'] == $deal_id ) {
				$ids[] = $value['data']['attribute_id'];
			}
		}
		return $ids;
	}

	public function set_vouchers_attribute_id( $voucher_id, $purchase, $product ) {
		if ( !empty( $product['data']['attribute_id'] ) ) {
			update_post_meta( $voucher_id, self::VOUCHER_ATTRIBUTE_META, $product['data']['attribute_id'] );
		}
	}

	public function get_vouchers_attribute_id( $voucher_id ) {
		$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
		if ( !is_a( $voucher, 'Group_Buying_Voucher' ) ) {
			return;
		}
		return $voucher->get_post_meta( self::VOUCHER_ATTRIBUTE_META );

	}


	/**
	 * Add the selected attribute to the Deals column in the purchases list
	 *
	 * @static
	 * @param array   $details
	 * @param array   $item
	 * @return array
	 */
	public static function show_purchase_details( $details, $item ) {
		if ( isset( $item['data'] ) && $item['data'] && isset( $item['data'][Group_Buying_Attribute::ATTRIBUTE_DATA_KEY] ) && $item['data'][Group_Buying_Attribute::ATTRIBUTE_DATA_KEY] ) {
			$attribute = Group_Buying_Attribute::get_instance( $item['data'][Group_Buying_Attribute::ATTRIBUTE_DATA_KEY] );
			if ( is_a( $attribute, 'Group_Buying_Attribute' ) ) {
				$sku = $attribute->get_sku();
				if ( $sku ) {
					$details = array( 'Sku' => $sku, 'Label' => $attribute->get_title() ) + $details;
				} else {
					$details = array( 'Label' => $attribute->get_title() ) + $details;
				}
			}
		}
		return $details;
	}

	public static function add_meta_boxes() {
		add_meta_box( 'gb_deal_attributes', self::__( 'Items' ), array( get_class(), 'show_meta_box' ), Group_Buying_Deal::POST_TYPE, 'advanced', 'high' );
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
		// ensure it's not a child deal
		if ( $post->post_parent ) {
			return;
		}

		// save all the meta boxes
		$deal = Group_Buying_Deal::get_instance( $post_id );
		self::save_meta_box_gb_deal_attributes( $deal, $post_id, $post );
	}

	public static function show_meta_box( $post, $metabox ) {
		$deal = Group_Buying_Deal::get_instance( $post->ID );

		switch ( $metabox['id'] ) {
		case 'gb_deal_attributes':
			self::show_meta_box_gb_deal_attributes( $deal, $post, $metabox );
			break;
		default:
			self::unknown_meta_box( $metabox['id'] );
			break;
		}
	}

	/**
	 * Display the deal attributes meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post
	 * @param array   $metabox
	 * @return void
	 */
	private static function show_meta_box_gb_deal_attributes( Group_Buying_Deal $deal, $post, $metabox ) {
		$objects = Group_Buying_Attribute::get_attributes( $deal->get_id(), 'object' );
		$attributes = array();
		foreach ( $objects as $attribute ) {
			/* @var Group_Buying_Attribute $attribute */
			$attributes[$attribute->get_id()] = array(
				'sku' => $attribute->get_sku(),
				'title' => $attribute->get_title(),
				'price' => $attribute->get_price(),
				'max_purchases' => ( $attribute->get_max_purchases()==Group_Buying_Attribute::NO_MAXIMUM )?'':$attribute->get_max_purchases(),
				'description' => $attribute->get_description(),
				'categories' => $attribute->get_categories(),
			);
		}
		self::load_view( 'meta_boxes/deal-attributes', array(
				'attributes' => $attributes,
			) );
	}

	/**
	 * Save the deal attributes meta box
	 *
	 * @static
	 * @param Group_Buying_Deal $deal
	 * @param int     $post_id
	 * @param object  $post
	 * @return void
	 */
	private static function save_meta_box_gb_deal_attributes( Group_Buying_Deal $deal, $post_id, $post, $internal = TRUE ) {
		$update = array();
		$new = array();
		$delete = array();

		if ( !isset( $_POST['gb-attribute'] ) ) {
			return;
		}
		$taxonomies = Group_Buying_Attribute::get_attribute_taxonomies();
		// Get the data from the $_POST
		$deal_max_purchases = 0;
		$set_max_purchases = TRUE;
		$title_key = 0;
		foreach ( $_POST['gb-attribute']['attribute_id'] as $key => $post_id ) {
			$values = array(
				'sku' => $_POST['gb-attribute']['sku'][$key],
				'price' => $_POST['gb-attribute']['price'][$key]?$_POST['gb-attribute']['price'][$key]:Group_Buying_Attribute::DEFAULT_PRICE,
				'max_purchases' => $_POST['gb-attribute']['max_purchases'][$key],
				'description' => stripcslashes( $_POST['gb-attribute']['description'][$key] ),
				'categories' => array(),
			);
			if ( $post_id ) {
				$attribute = Group_Buying_Attribute::get_instance( $post_id );
				$values['title'] = $attribute->get_title();
			} else {
				$values['title'] = $_POST['gb-attribute']['title'][$title_key];
				$title_key++;
			}
			foreach ( $taxonomies as $taxonomy ) {
				if ( isset( $_POST['gb-attribute']['category'][$taxonomy->name][$key] ) ) {
					$values['categories'][$taxonomy->name] = (int)$_POST['gb-attribute']['category'][$taxonomy->name][$key];
				}
			}
			if ( !is_numeric( $values['max_purchases'] ) ) {
				$values['max_purchases'] = Group_Buying_Attribute::NO_MAXIMUM;
				if ( !empty( $values['title'] ) ) $set_max_purchases = FALSE; // Set so the deal's max isn't updated since this attribute has no max.
			} else {
				if ( !empty( $values['title'] ) ) $deal_max_purchases += $values['max_purchases']; // count up the max purchases and attempt to update the deal's total
			}

			if ( $post_id ) {
				$update[$post_id] = $values;
			} elseif ( $values['title'] ) {
				$new[] = $values;
			}
		}

		// Get the existing children
		$existing_ids = Group_Buying_Attribute::get_attributes( $deal->get_id() );

		// Check that we have legitimate post IDs
		foreach ( $update as $post_id => $data ) {
			if ( !in_array( $post_id, $existing_ids ) ) {
				$new[] = $data;
				unset( $update[$post_id] );
			}
		}

		// Check for post IDs that have been removed
		foreach ( $existing_ids as $id ) {
			if ( !in_array( $id, array_keys( $update ) ) ) {
				$delete[] = $id;
			}
		}
	
		// Create the new posts
		$new = apply_filters( 'gb_attributes_save_meta_box_new', $new );
		foreach ( $new as $data ) {
			Group_Buying_Attribute::new_attribute( $deal->get_id(), $data );
			do_action( 'gb_attribute_publish', $attribute, $data );
		}

		// Update the existing posts
		$update = apply_filters( 'gb_attributes_save_meta_box_update', $update );
		foreach ( $update as $attribute_id => $data ) {
			$attribute = Group_Buying_Attribute::get_instance( $attribute_id );
			$attribute->update( $data );
			do_action( 'gb_attribute_updated', $attribute, $data );
		}

		// Delete
		$delete = apply_filters( 'gb_attributes_save_meta_box_delete', $delete );
		foreach ( $delete as $attribute_id ) {
			$attribute = Group_Buying_Attribute::get_instance( $attribute_id );
			do_action( 'gb_attribute_removed', $attribute );
			$attribute->remove();
		}

		if ( $internal ) {
			/*
			 * The previous lines resulted in one or more calls
			 * to wp_insert_post(), which in turn, did the 'save_post'
			 * action. Since we're already in a save_post action right now,
			 * we need to make sure to restore the actions array pointer.
			 * Otherwise, no lower-priority callbacks will be called,
			 * because each pass through 'save_post' moves the pointer to
			 * the end of the array.
			 */
			global $wp_filter;
			reset( $wp_filter['save_post'] );
			foreach ( array_keys( $wp_filter['save_post'] ) as $key ) {
				if ( $key == self::SAVE_POST_PRIORITY ) {
					break;
				}
				next( $wp_filter['save_post'] );
			}

			if ( $deal_max_purchases && $set_max_purchases && ( !empty( $new ) || !empty( $update ) || !empty( $delete ) ) ) {
				$deal->set_max_purchases( $deal_max_purchases );
			}
		}

	}

	/**
	 * Add the attribute selector to the admin purchase meta box
	 *
	 * @static
	 * @return void
	 */
	public static function edit_admin_purchases_form() {
		self::load_view( 'meta_boxes/account-purchases-add-attributes.php', array(), FALSE );
	}

	/**
	 * Print a JSON object with the attributes for the requested deal
	 *
	 * @static
	 * @return void
	 */
	public static function ajax_get_attributes() {
		header( 'Content-Type: application/json' );
		$response = array(
			'deal_id' => 0,
			'attributes' => array(),
		);
		$deal_id = $_POST['deal_id'];
		if ( $deal_id ) {
			$response['deal_id'] = $deal_id;
			$attributes = Group_Buying_Attribute::get_attributes( $deal_id, 'object' );
			foreach ( $attributes as $att_id => $att ) {
				$response['attributes'][$att_id] = $att->get_title();
			}
		}
		echo json_encode( $response );
		exit();
	}

	/**
	 * Print a JSON object with the attributes for the requested deal
	 *
	 * @static
	 * @return void
	 */
	public static function ajax_query_attributes() {
		$response = array(
			'deal_id' => 0,
		);
		$deal_id = $_POST['deal_id'];
		if ( $deal_id ) {
			$response['deal_id'] = $deal_id;

			$args = array(
					'post_type' => Group_Buying_Attribute::POST_TYPE,
					'order' => 'ASC',
					'orderby' => 'id',
					'numberposts' => -1,
					'fields' => 'ids',
					'meta_query' => array(
						array(
							'key' => '_deal_id', // Group_Buying_Attribute::$meta_keys['deal_id']
							'value' => $deal_id,
							'type' => 'NUMERIC',
						)
					)
				);
			wp_parse_str( $_POST['selections'], $selections );
			foreach ( $selections as $term_name => $term_id ) {
				$args['tax_query']['relation'] = 'AND'; // in case it wasn't set earlier
				$args['tax_query'][] = array(
				    	'taxonomy' => $term_name,
						'field' => 'id',
						'terms' => $term_id,
						'operator' => 'IN'
						);
			}

			$attribute_ids = query_posts($args);
			foreach ( $attribute_ids as $id ) {
				$attribute = Group_Buying_Attribute::get_instance( $id );
				if ( $attribute->get_max_purchases() == Group_Buying_Attribute::NO_MAXIMUM || $attribute->remaining_purchases() > 0 ) {
					$response[] = $id;
				}
			}
		}
		header( 'Content-Type: application/json' );		
		echo json_encode( $response );
		exit();
	}

	/**
	 * Add the selected attribute to the admin-purchased deal's data array
	 *
	 * @param array   $data
	 * @param Group_Buying_Deal $deal
	 * @return array
	 */
	public function filter_admin_purchase_data( $data, $deal ) {
		if ( isset( $_REQUEST[self::ATTRIBUTE_QUERY_VAR] ) && $_REQUEST[self::ATTRIBUTE_QUERY_VAR] ) {
			$attribute = Group_Buying_Attribute::get_instance( $_REQUEST[self::ATTRIBUTE_QUERY_VAR] );
			if ( $attribute ) {
				if ( $attribute->get_deal_id() == $deal->get_id() ) { // make sure we have a valid attribute
					$data[Group_Buying_Attribute::ATTRIBUTE_DATA_KEY] = $_REQUEST[self::ATTRIBUTE_QUERY_VAR];
				}
			}
		}
		return $data;
	}

	public function filter_deal_submission_fields( $fields ) {
		$fields['deal_details'] = array(
			'weight' => 100,
			'label' => self::__( 'Deal Attributes' ),
			'type' => 'heading',
			'required' => FALSE,
		);

		$fields['attributes'] = array(
			'weight' => 101,
			'type' => 'custom',
			'required' => FALSE
		);
		return $fields;
	}

	public function attribute_form_field( $field, $key, $data, $category ) {
		if ( $category == 'deal' ) {
			if ( $key == 'attributes' ) {
				$attributes = array();
				return self::load_view( 'meta_boxes/deal-attributes', array(
						'attributes' => $attributes,
					) );
			}
		}
		return $field;
	}

	public function submit_deal( Group_Buying_Deal $deal ) {
		$post_id = $deal->get_id();
		self::save_meta_box_gb_deal_attributes( $deal, $post_id, NULL, FALSE );
	}
}
