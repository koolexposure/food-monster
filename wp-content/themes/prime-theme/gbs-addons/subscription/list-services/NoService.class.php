<?php
/**
 * This class provides a model for a subscription processor. To implement a
 * different list service, create a new class that extends
 * Group_Buying_List_Services. The new class should implement
 * the following methods (at a minimum):
 *  - get_instance()
 *  - process_subscription()
 *  - process_registration_subscription()
 *  - register()
 *  - get_subscription_method()
 *
 * You may also want to register some settings for the Payment Options page
 */

class Group_Buying_None extends Group_Buying_List_Services {
	protected static $instance;


	protected static function get_instance() {
		if ( !( isset( self::$instance ) && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_subscription_method() {
		return self::SUBSCRIPTION_SERVICE;
	}

	protected function __construct() {
		parent::__construct();
		add_action( 'gb_subscription_form', array( get_class(), 'gb_subscription_form' ), 10, 4 );
	}

	public static function register() {
		self::add_list_service( __CLASS__, self::__( 'None' ) );
	}

	public static function gb_subscription_form( $view, $show_locations, $select_location_text, $button_text ) {
?>
		<form action="" id="gb_subscription_form" method="post" class="clearfix">
			<?php
		$locations = gb_get_locations( false );
		$no_city_text = get_option( Group_Buying_List_Services::SIGNUP_CITYNAME_OPTION );
		if ( ( !empty( $locations ) || !empty( $no_city_text ) ) && $show_locations ) {
?>
						<span class="option location_options_wrap clearfix">
							<label for="locations"><?php gb_e( $select_location_text ); ?></label>
							<?php
			global $wp_query;
			$query_slug = $wp_query->get_queried_object()->slug;
			$current_location = ( !empty( $query_slug ) ) ? $query_slug : $_COOKIE[ 'gb_location_preference' ] ;
			echo '<select name="deal_location" id="deal_location" size="1">';
			foreach ( $locations as $location ) {
				echo '<option value="'.$location->slug.'" '.selected( $current_location, $location->slug ).'>'.$location->name.'</option>';
			}
			if ( !empty( $no_city_text ) ) {
				echo '<option value="notfound">'.esc_attr( $no_city_text ).'</option>';
			}
			echo '</select>';
?>
						</span>
					<?php
		} ?>
			<?php wp_nonce_field( 'gb_subscription' );?>
			<span class="submit clearfix"><input type="submit" class="button-primary" name="gb_subscription" id="gb_subscription" value="<?php gb_e( $button_text ); ?>"></span>
		</form>
		<?php
	}

	public function process_subscription() {
		parent::success( $_POST['deal_location'], null );
	}

	public function process_registration_subscription( $userId = null, $user_login = null, $user_email = null, $password = null, $post = null ) {
		return;
	}
}
Group_Buying_None::register();
