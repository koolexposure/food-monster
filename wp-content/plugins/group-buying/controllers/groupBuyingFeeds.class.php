<?php

/**
 * RSS Feed controller
 *
 * @package GBS
 * @subpackage Base
 * @todo  move to the deals controller
 */
class Group_Buying_Feeds extends Group_Buying_Controller {
	// TODO This needs to be completely rewritten and use the WP feed API.
	const FEED_PATH_OPTION = 'gb_feed_path';
	const FEED_QUERY_VAR = 'gb_show_feed';
	const ADD_TO_FEED_QUERY_VAR = 'add_to_feed';
	const AFFILIATE_XML_QUERY_VAR = 'affiliate_xml';
	private static $feed_path = 'gb_feed';
	private static $instance;
	private $feed = NULL;

	public static function init() {
		self::$feed_path = get_option( self::FEED_PATH_OPTION, self::$feed_path );
		self::register_query_var( self::ADD_TO_FEED_QUERY_VAR, array( get_class(), 'add_to_feed' ) );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 50, 1 );
		self::register_path_callback( self::$feed_path, array( get_class(), 'on_feed_page' ), self::ADD_TO_FEED_QUERY_VAR );

		// TODO
		// add_feed('Some Title', array( get_class(), 'added_feed_example' ) );

		// Add the deal to the RSS feed
		add_filter( 'the_excerpt_rss', array( get_class(), 'deal_custom_rss' ) );
		add_filter( 'the_content_feed', array( get_class(), 'deal_custom_rss' ) );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_feed_paths';
		add_settings_section( $section, null, array( get_class(), 'display_feed_paths_section' ), $page );

		// Settings
		register_setting( $page, self::FEED_PATH_OPTION );
		add_settings_field( self::FEED_PATH_OPTION, self::__( 'Feed Path' ), array( get_class(), 'display_feed_path' ), $page, $section );
	}

	public static function display_feed_paths_section() {
		echo self::__( '<h4>Customize the Feed paths</h4>' );
	}

	public static function display_feed_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::FEED_PATH_OPTION . '" id="' . self::FEED_PATH_OPTION . '" value="' . esc_attr( self::$feed_path ) . '" size="40"/><br />';
	}


	/**
	 *
	 *
	 * @static
	 * @return string The URL to the feed page
	 */
	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$feed_path );
		} else {
			$router = GB_Router::get_instance();
			return $router->get_url( 'gb_show_feed' );
		}
	}

	/**
	 * We're on the feed page, so handle any form submissions from that page,
	 * and make sure we display the correct information (i.e., the feed)
	 *
	 * @static
	 * @return void
	 */
	public static function on_feed_page() {
		// by instantiating, we process any submitted values
		$feed = self::get_instance();
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
	/**
	 *
	 *
	 * @static
	 * @return Group_Buying_Feeds
	 */
	public static function get_instance() {
		if ( !( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// $this->feed = Group_Buying_Feed::get_instance(); // TODO DAN optimize
		if ( isset( $_GET[self::AFFILIATE_XML_QUERY_VAR] ) && !empty( $_GET[self::AFFILIATE_XML_QUERY_VAR] ) ) {
			$this->affiliate_xml();
		} else {
			$this->deal_feed();
		}
	}


	public static function added_feed_example() {
		// TODO a bunch of these need to be created, each with their own WP_Query args
		self::deal_feed( $query_args );
	}

	/**
	 * Display Deal RSS Feed
	 * @param  array  $query_args default query args
	 * @return string             fully formatted RSS feed
	 */
	public function deal_feed( $query_args = array() ) {
		// get Deals
		$query_args = self::query_args( $query_args );
		$deals = new WP_Query( $query_args );

		$items = array();
		while ( $deals->have_posts() ) : $deals->the_post();
			// Set ID
			$deal_id = get_the_ID();

			// get thumbnail
			$post_thumbnail = ( has_post_thumbnail( $deal_id ) ) ? get_the_post_thumbnail( $deal_id, 'deal-post-thumbnail-rss' ) : false;

			// get the content
			$the_content = ( gb_get_rss_excerpt() ) ? gb_get_rss_excerpt() : get_the_content();

			// Build content
			$description = ( has_post_thumbnail( $deal_id ) ) ? get_the_post_thumbnail( $deal_id, 'gbs_voucher_thumb' ) : '';
			$description .= '<p><strong>'.self::__( 'Price:' ).' '.gb_get_formatted_money( gb_get_price() ).'</strong></p>';
			$description .= '<p><strong>'.self::__( 'Value:' ).' '.gb_get_formatted_money( gb_get_deal_worth() ).'</strong></p>';
			$description .= '<p>';
			if ( gb_has_expiration() ) {
				$description .= self::__( 'Expires On:' ).' '.gb_get_deal_end_date().'<br/>';
			}
			$description .= sprintf( self::__( '<span>%s</span> buyers!' ), gb_get_number_of_purchases() ).'<br/>'.self::__( 'Savings:' ).' '.gb_get_amount_saved().'</p>';
			$description .= ( gb_has_merchant() ) ? '<p>'.self::__( 'Business:' ).' <a href="'.gb_get_merchant_url( gb_get_merchant_id() ).'" title="'.get_the_title( gb_get_merchant_id() ).'">'.gb_get_merchant_name( gb_get_merchant_id() ).'</a></p>' : '';
			$description .= $the_content;

			$description = apply_filters( 'gb_deal_feed_content', $description, $deal_id );

			$items[$deal_id] = array(
				'title' => get_the_title(),
				'link' => get_permalink(),
				'dc:creator' => get_the_author(),
				'description' => $description,
				'content:encoded' => $description,
				'guid' => get_permalink(),
				'pubDate' => get_the_date( 'r' )
			);
		endwhile;
		print self::get_feed( apply_filters( 'gb_deal_feed_items', $items, $query_args ) );
		exit();
	}

	/**
	 * Display Deal XML Feed
	 * @param  array  $query_args default query args
	 * @return string             fully formatted XML feed
	 */
	public function affiliate_xml( $query_args = array() ) {
		// Get deals
		$query_args = self::query_args( $query_args );
		$deals = new WP_Query( $query_args );

		$items = array();
		while ( $deals->have_posts() ) : $deals->the_post();
			// Set ID
			$deal_id = get_the_ID();

			// Locations
			$markets = array();
			$market_names = array();
			$market_array = array();
			$market_name_array = array();
			$locations = gb_get_deal_locations( $deal_id );
			foreach ( $locations as $location ) {
				$market_array[] = $location->slug;
				$market_name_array[] = $location->name;
			}
			$markets = implode( ',', $market_array );
			$market_names = implode( ',', $market_name_array );

			// Categories
			$categories = array();
			$category_names = array();
			$category_array = array();
			$category_name_array = array();
			$cats = gb_get_deal_categories( $deal_id );
			foreach ( $cats as $cat ) {
				$category_array[] = $cat->slug;
				$category_name_array[] = $cat->name;
			}
			$categories = implode( ',', $category_array );
			$category_names = implode( ',', $category_name_array );

			// thumbnails
			if ( has_post_thumbnail() ) {
				$post_thumbnail_id = get_post_thumbnail_id( $deal_id );
				if ( $post_thumbnail_id ) {
					$image_array = wp_get_attachment_image_src( $post_thumbnail_id, 'post-thumbnail', false );
					$image_url = $image_array[0];
				}
			}
			// Content
			$the_content = ( gb_get_rss_excerpt() ) ? gb_get_rss_excerpt() : get_the_content();

			// Build Array
			$items[$deal_id] = array(
				'id' => $deal_id,
				'market' => $markets,
				'url' => get_permalink(),
				'image_url' => $image_url,
				'title' => get_the_title(),
				'highlights' => gb_get_highlights(),
				'restrictions' => gb_get_fine_print(),
				'description' => $the_content,
				'value' => gb_get_formatted_money( gb_get_deal_worth() ),
				'price' => gb_get_formatted_money( gb_get_price() ),
				'required_qty' => gb_get_min_purchases(),
				'purchased_qty' => gb_get_number_of_purchases(),
				'category' => $categories,
				'purchase_link' => gb_get_add_to_cart_url(),
				'savings' => gb_get_amount_saved()
			);
			// If has an expiration
			if ( gb_has_expiration( $deal_id ) ) {
				$items[$deal_id] += array(
					'ending_time' => gb_get_deal_end_date( DATE_ATOM ),
				);
			}
			// If item has an associated merchant
			if ( gb_has_merchant( $deal_id ) ) {
				$items[$deal_id] += array(
					'merchant' => gb_get_merchant_name( gb_get_merchant_id() ),
					'address' => gb_get_merchant_street( gb_get_merchant_id() ),
					'city' => gb_get_merchant_city( gb_get_merchant_id() ),
					'state' => gb_get_merchant_state( gb_get_merchant_id() ),
					'zip' => gb_get_merchant_zip( gb_get_merchant_id() ),
					'country' => gb_get_merchant_country( gb_get_merchant_id() ),
					'phone' => gb_get_merchant_phone( gb_get_merchant_id() ),
				);
			}
		endwhile;
		// filter items
		$items = apply_filters( 'gb_affiliate_xml_items', $items, $query_args );
		$items = apply_filters( 'gb_affiliate_xml_items-' . $_GET[self::AFFILIATE_XML_QUERY_VAR], $items, $query_args );

		// Print a XML feed
		print self::get_xml_feed( $items );
		
		exit();
	}

	/**
	 * Build query args for WP_Query based on URL query variables
	 * @param  array $query_args default set of query_args
	 * @return array             
	 */
	public static function query_args( $query_args = null ) {  // TODO DAN optimize

		$post_type = ( isset( $_GET['post_type'] ) || $query_args['post_type'] != '' ) ? $_GET['post_type'] : Group_Buying_Deal::POST_TYPE ; // this will break in almost every case.

		$meta = array();
		if ( isset( $_GET['expired'] ) ) {
			if ( $_GET['expired'] != 'any' || $_GET['expired'] != 'all' ) { // unless it's set to everything we need to only show expired
				$meta[] = array(
					'key' => '_expiration_date',
					'value' => current_time( 'timestamp' ),
					'compare' => '<' );
			}
		} else { // default to current deals.
			$meta[] = array(
				'key' => '_expiration_date',
				'value' => array( 0, current_time( 'timestamp' ) ),
				'compare' => 'NOT BETWEEN' );
		}
		$query_args = array(
			'post_type' => $post_type,
			'post_status' => 'publish',
			'meta_query' => $meta,
		);
		if ( isset( $_GET['location'] ) && $_GET['location'] != '' ) {
			$query_args[gb_get_location_tax_slug()] = $_GET['location'];
		}

		// Filter the Query Args
		$query_args = apply_filters( 'gb_feed_query_args', $query_args );
		if ( isset( $_GET[self::AFFILIATE_XML_QUERY_VAR] ) ) {
			$query_args = apply_filters( 'gb_affiliate_feed_query_args-' . $_GET[self::AFFILIATE_XML_QUERY_VAR], $query_args );
		}
		return $query_args;
	}

	/**
	 * Build feed for deals, differs from affiliate_xml
	 * @param  array  $items array of items to build nodes from
	 * @return string        RSS formatted XML feed
	 */
	public static function get_feed( $items = array() ) {

		if ( empty( $items ) ) return; // nothing to do.

		$shift = $items;
		$first_item = array_shift( $shift );
		ob_start();
		header( "Content-Type:text/xml" );
		?>
			<rss version="2.0"
				xmlns:content="http://purl.org/rss/1.0/modules/content/"
				xmlns:dc="http://purl.org/dc/elements/1.1/"
				xmlns:atom="http://www.w3.org/2005/Atom"
				xmlns:sy="http://purl.org/rss/1.0/modules/syndication/">
				<channel>
					<title><?php bloginfo_rss( 'name' ); wp_title_rss(); ?></title>
					<link><?php bloginfo_rss( 'url' ) ?></link>
					<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
					<description><?php bloginfo_rss( "description" ) ?></description>
					<language><?php bloginfo_rss( 'language' ); ?></language>
					<sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
					<sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
		<?php 
					if ( !empty( $first_item['pubDate'] ) ) {
						echo '<pubDate>'.$first_item['pubDate'].'</pubDate>';
					}

					foreach ( $items as $item ) {
						echo "<item>\n";
						foreach ( $item as $node => $content ) {
							if ( $node == "content:encoded" || $node == "description" ) {
								echo "<".$node."><![CDATA[".$content."]]></".$node.">\n";
							} else {
								echo "<".$node.">".$content."</".$node.">\n";
							}
						}
						echo "</item>\n\n";
					} 
		?>
				</channel>
			</rss>
		<?php
		$feed = ob_get_clean();
		return apply_filters( 'gb_get_feed', $feed, $items );
	}

	/**
	 * Build feed for deals, differs from get_feed since it's not RSS formatted
	 * @param  array  $items array of items to build nodes from
	 * @return string        an XML feed
	 */
	public static function get_xml_feed( $items = array() ) {

		if ( empty( $items ) ) return; // nothing to do.

		ob_start();
		header( "Content-Type:text/xml" );
			echo "<itemset>\n";
				foreach ( $items as $item ) {
					echo "<item>\n";
					foreach ( $item as $node => $content ) {
						if ( in_array( $node, array( "highlights", "restrictions", "description", "merchant", "address", "city", "state" , "zip" , "country", "phone", "excerpt" ) ) ) {
							echo "<".$node."><![CDATA[".$content."]]></".$node.">\n";
						} else {
							echo "<".$node.">".$content."</".$node.">\n";
						}
					}
					echo "</item>\n\n";
				}
			echo "</itemset>\n";
		$feed = ob_get_clean();
		$filter_feed = apply_filters( 'gb_get_xml_feed', $feed, $items );
		if ( isset( $_GET[self::AFFILIATE_XML_QUERY_VAR] ) ) {
			$filter_feed = apply_filters( 'gb_get_xml_feed-' . $_GET[self::AFFILIATE_XML_QUERY_VAR], $filter_feed, $items );
		}
		return $filter_feed;
	}

	/**
	 * Filter the default WP Feed for all deals and include some meta info
	 * @param  string $content full content of post
	 * @return string          content
	 */
	public function deal_custom_rss( $content ) {
		global $post;
		if ( has_post_thumbnail( $post->ID ) ) {
			$content = '<p>' . get_the_post_thumbnail( $post->ID, 'gbs_voucher_thumb' ) . '</p>';
		}
		if ( get_post_type( $post->ID ) == Group_Buying_Deal::POST_TYPE ) {
			$content = ( has_post_thumbnail( get_the_ID() ) ) ? get_the_post_thumbnail( get_the_ID(), 'gbs_voucher_thumb' ) : '';
			$content .= '<p><strong>'.gb_get_formatted_money( gb_get_deal_worth() ).'</strong></p>';
			$content .= '<p>'.self::__( 'Expires On:' ).' '.gb_get_deal_end_date().'<br/>'.sprintf( self::__( '<span>%s</span> buyers!' ), gb_get_number_of_purchases() ).'<br/>'.self::__( 'Savings:' ).' '.gb_get_amount_saved().'</p>';
			if ( gb_get_rss_excerpt() != '' ) {
				$content .= gb_get_rss_excerpt();
			} else {
				$content .= get_the_content();
			}
			$content = apply_filters( 'deal_custom_rss_content', $content, $post->ID );
		} else {
			$content .= get_the_content();
		}
		return apply_filters( 'gb_deal_custom_rss', $content );
	}
}
