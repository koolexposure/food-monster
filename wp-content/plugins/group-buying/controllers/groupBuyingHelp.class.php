<?php

/**
 * GBS help sections, pointers, etc..
 *
 * @package GBS
 * @subpackage Base
 */
class Group_Buying_Help extends Group_Buying_Controller {

	public static function init() {

		add_action( 'gb_settings_page_sub_heading_group-buying', array( get_class(), 'display_help_section' ), 10, 0 );

		// Menus and help
		if ( version_compare( get_bloginfo( 'version' ), '3.2.99', '>=' ) ) { // 3.3. only
			add_action( 'admin_bar_menu', array( get_class(), 'wp_admin_bar_options' ), 62 );
			add_action( 'admin_menu', array( get_class(), 'admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( get_class(), 'enqueue_scripts' ) );
			add_action( 'user_register', array( get_class(), 'dismiss_pointers_for_new_users' ) );
		}

	}

	public function display_help_section() {
		if ( $_GET['page'] == self::TEXT_DOMAIN ) {
			print self::load_view( 'admin/docs.php', array() );
		}
	}


	public function admin_menu() {
		// Mixed
		add_action( 'load-edit.php', array( get_class(), 'help_section' ) );
		add_action( 'load-post.php', array( get_class(), 'help_section' ) );
		add_action( 'load-post-new.php', array( get_class(), 'help_section' ) );

		// Edit screen
		add_action( 'load-edit.php', array( get_class(), 'help_section_edit' ) );

		// Edit or add a deal
		add_action( 'load-post.php', array( get_class(), 'help_section_edit_post' ) );
		add_action( 'load-post-new.php', array( get_class(), 'help_section_edit_post' ) );

		// Option Pages
		foreach ( self::get_admin_pages() as $page => $data ) {
			add_action( 'load-'.$data['hook'], array( get_class(), 'options_help_section' ), 50 );
		}
	}

	public static function options_help_section() {
		$screen = get_current_screen();
		$page = str_replace( 'group-buying_page_group-buying/', '', $screen->id ); // get context and make it readable.

		switch ( $page ) {
		case 'payment':
			$screen->add_help_tab( array(
					'id'      => 'payments-help', // This should be unique for the screen.
					'title'   => self::__( 'Payments Settings' ),
					'content' =>
					'<p><strong>' . self::__( 'Configuring Payment Settings.' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( '<a href="%s">A walkthrough video</a> is provided in the support forums.' ), 'http://groupbuyingsite.com/forum/showthread.php?813-Payment-Settings' ) . '</p>'
				) );
			$screen->add_help_tab( array(
					'id'      => 'payments-help-ssl', // This should be unique for the screen.
					'title'   => self::__( 'SSL on Checkout' ),
					'content' =>
					'<p><strong>' . self::__( 'Highly recommended for production sites accepting credit cards.' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( 'When using on-site purchasing options like PayPal Pro and Authorize.net, it&rsquo;s highly recommended that GBS users purchase and integrate an SSL (Secure Sockets Layer) Certificate for their group buying site. Ideally, you would want your hosting provider to install the SSL certificate for you. You will also need to follow the additional steps in our <a href="%s">SSL Integration Documentation</a> to fully secure your checkout pages.' ), 'http://groupbuyingsite.com/forum/showthread.php?810-SSL-Integration' ) . '</p>'
				) );
			$screen->add_help_tab( array(
					'id'      => 'payments-help-addons', // This should be unique for the screen.
					'title'   => self::__( 'Additional Gateways' ),
					'content' =>
					'<p><strong>' . self::__( 'Need a payment gateway not provided below?' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( 'Additional payment gateways are provided in <a href="%s">the GBS marketplace</a>. For payment gateways not yet supported, submit a customization request, and GBS will connect you with an experienced developer.' ), 'http://groupbuyingsite.com/deal-categories/payment-gateway/' ) . '</p>'
				) );
			break;
		case 'gb_tax_settings':

			$screen->add_help_tab( array(
					'id'      => 'tax-help', // This should be unique for the screen.
					'title'   => self::__( 'Enable Tax' ),
					'content' =>
					'<p><strong>' . self::__( 'Enable Tax Calculations.' ) . '</strong></p>' .
					'<p>' . self::__( 'To calculate taxes, first enabled Tax Calculation, then create modes and rates.' ) . '</p>'
				) );

			$screen->add_help_tab( array(
					'id'      => 'tax-locations', // This should be unique for the screen.
					'title'   => self::__( 'Location Based' ),
					'content' =>
					'<p><strong>' . self::__( 'Location based taxation.' ) . '</strong></p>' .
					'<p>' . self::__( 'Enable this feature to set the tax rate based on the customer&rsquo;s address set during checkout. When this option is checked and saved, region and state options will be available in the rates table below.' ) . '</p>'
				) );

			$screen->add_help_tab( array(
					'id'      => 'tax-modes', // This should be unique for the screen.
					'title'   => self::__( 'Tax Modes' ),
					'content' =>
					'<p><strong>' . self::__( 'What are tax modes?' ) . '</strong></p>' .
					'<p>' . self::__( 'Your site is not limited to a single tax rate or tax table.  Modes allow you to create multiple rates and/or rate tables and choose the appropriate "mode" for each individual deal. For example, you could have a "Standard" mode for all of your basic deals, but when a special rate is necessary, you can use a second mode of taxation.  This mode will run simultaneously with the standard mode on other deals.' ) . '</p>'
				) );

			$screen->add_help_tab( array(
					'id'      => 'tax-table', // This should be unique for the screen.
					'title'   => self::__( 'Tax Rates' ),
					'content' =>
					'<p><strong>' . self::__( 'Tax Rate Table.' ) . '</strong></p>' .
					'<p>' . self::__( 'First select the "Mode" the new rate will be assigned to, then create a percentage rate and whether to include shipping in the calculation. If the location based feature is enabled, options for state and region will also show.' ) . '</p>' .
					'<p><strong>' . self::__( 'What is the Rate priority?' ) . '</strong></p>' .
					'<p>' . self::__( 'The Rates priority is set first by list order above (top to bottom), then (if applicable) location matching criteria in this order: Country+State > State > Country.' ) . '</p>'
				) );

			break;
		case 'gb_shipping_settings':

			$screen->add_help_tab( array(
					'id'      => 'shipping-help', // This should be unique for the screen.
					'title'   => self::__( 'Enable Shipping' ),
					'content' =>
					'<p><strong>' . self::__( 'Enable Shipping Calculations.' ) . '</strong></p>' .
					'<p>' . self::__( 'To shipping rates, first Enable Shipping, then create classes and rates.' ) . '</p>'
				) );

			$screen->add_help_tab( array(
					'id'      => 'shipping-locations', // This should be unique for the screen.
					'title'   => self::__( 'Location Based' ),
					'content' =>
					'<p><strong>' . self::__( 'Location based shipping.' ) . '</strong></p>' .
					'<p>' . self::__( 'Enable this feature to set the shipping rate based on the customer&rsquo;s address set during checkout. When this option is checked and saved, region and state options will be available in the rates table below.' ) . '</p>'
				) );

			$screen->add_help_tab( array(
					'id'      => 'shipping-classes', // This should be unique for the screen.
					'title'   => self::__( 'Shipping Classes' ),
					'content' =>
					'<p><strong>' . self::__( 'What are shipping classes?' ) . '</strong></p>' .
					'<p>' . self::__( 'Your site is not limited to a single shipping rate.  Classes allow you to create multiple rates and choose the appropriate "class" for each individual deal. For example, you could have a "Standard" class for all of your basic deals, but when a special rate is necessary, you can use a second class of shipping rate.  This class will run simultaneously with the standard class on other deals.' ) . '</p>'
				) );

			$screen->add_help_tab( array(
					'id'      => 'shipping-table', // This should be unique for the screen.
					'title'   => self::__( 'Shipping Rates' ),
					'content' =>
					'<p><strong>' . self::__( 'Shipping Rate Table.' ) . '</strong></p>' .
					'<p>' . self::__( 'First select the "Class" the new rate will be assigned to, then create a percentage rate and whether to include shipping in the calculation. If the location based feature is enabled, options for state and region will also show.' ) . '</p>' .
					'<p><strong>' . self::__( 'What is the Rate priority?' ) . '</strong></p>' .
					'<p>' . self::__( 'The Rates priority is set first by list order above (top to bottom), then (if applicable) location matching criteria in this order: Country+State > State > Country.' ) . '</p>'
				) );

			break;
		case 'gb_int_settings':
			// First help tab with general stuff (include any other specific tabs here too

			break;
		case 'gb_aggregator_settings':
			// First help tab with general stuff (include any other specific tabs here too

			break;
		case 'gb_addons':
			// First help tab with general stuff (include any other specific tabs here too
			break;
		case 'theme_options':
		case 'theme_color_options':
		case 'translation':
		case 'subscription':
			return;
			break;
		default:
			break;
		}
		$screen->add_help_tab( array(
				'id'      => 'general-options-questions', // This should be unique for the screen.
				'title'   => self::__( 'Question about GBS' ),
				'content' =>
				'<p><strong>' . self::__( 'Do you have a question about GBS?' ) . '</strong></p>' .
				'<p>' . sprintf( self::__( 'Try <a href="%s">searching the forums</a> to find a quick answer.' ), 'http://groupbuyingsite.com/forum/search.php' ) . '</p>'
			) );
		$screen->add_help_tab( array(
				'id'      => 'general-options-problem', // This should be unique for the screen.
				'title'   => self::__( 'Experiencing a problem' ),
				'content' =>
				'<p><strong>' . self::__( 'Are you experiencing trouble with your GBS site?' ) . '</strong></p>' .
				'<p>' . sprintf( self::__( 'Please see these <a href="%s">tips for troubleshooting</a> and search the forums for a solution. If you can\'t find a solution after searching the forums, create a forum post and someone will assist you as soon as possible.' ), 'http://groupbuyingsite.com/forum/forumdisplay.php?39-Troubleshooting-Help' ) . '</p>'
			) );
		$screen->add_help_tab( array(
				'id'      => 'general-options-critical', // This should be unique for the screen.
				'title'   => self::__( 'Critical problem' ),
				'content' =>
				'<p><strong>' . self::__( 'Critical problem with a production/live site after a recent GBS update?' ) . '</strong></p>' .
				'<p>' . sprintf( self::__( '<a href="%s">Submit a helpdesk ticket</a> (making sure to read the helpdesk criteria) after creating a forum thread.' ), 'http://groupbuyingsite.com/forum/support.php?do=newticket' ) . '</p>'.
				'<p>' . self::__( 'Helpdesk support is limited, so please make sure to read the criteria and notes before submitting a new ticket.' ) . '</p>'
			) );
		$screen->add_help_tab( array(
				'id'      => 'general-options-customizations', // This should be unique for the screen.
				'title'   => self::__( 'Customizations' ),
				'content' =>
				'<p><strong>' . self::__( 'In need of a custom feature or custom theme for your site?' ) . '</strong></p>' .
				'<p>' . sprintf( self::__( 'You should never edit GBS plugins or themes directly.  All customizations should be made using a <a href="%s">child theme</a>.' ), 'http://groupbuyingsite.com/forum/showthread.php?3203-Setting-Up-and-Using-a-Child-Theme' ) . '</p>'.
				'<p>' . sprintf( self::__( 'GBS developers provide some custom development services for GBS site owners. Select the &quot;Development Request&quot; option when <a href="%s">submitting a new helpdesk ticket</a> and we will provide assistance.' ), 'http://groupbuyingsite.com/forum/support.php?do=newticket' ) . '</p>'.
				'<p>' . sprintf( self::__( 'GBS has a flourishing developer community, a select few have <a href="%s">profiles on our site</a>.' ), 'http://groupbuyingsite.com/developers/' ) . '</p>'
			) );
		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p>' . self::__( '<a href="http://groupbuyingsite.com/docs/" target="_blank">Documentation on GBS</a>' ) . '</p>' .
			'<p>' . self::__( '<a href="http://groupbuyingsite.com/forum/" target="_blank">Support Forums</a>' ) . '</p>'
		);
	}

	public static function help_section() {
		$screen = get_current_screen();
		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : FALSE;
		if ( $post_id ) {
			$post_type = get_post_type( $post_id );
		} else {
			$post_type = ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) ) ? $_REQUEST['post_type'] : null ;
		}
		/**
		 * ***********************************
		 *                Deals
		 * ***********************************
		 */
		if ( $post_type == Group_Buying_Deal::POST_TYPE ) {
			$screen->add_help_tab( array(
					'id'      => 'deal-help', // This should be unique for the screen.
					'title'   => self::__( 'Deal Management' ),
					'content' =>
					'<p><strong>' . self::__( 'How to add a deal.' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( 'A video walkthrough of <a href="%s">adding a deal</a> is available in the forum.' ), 'http://groupbuyingsite.com/forum/showthread.php?745-Adding-a-Deal' ) . '</p>' .
					'<p><strong>' . self::__( 'Warning:' ) . '</strong></p>' .
					'<p>' . self::__( 'Deleting completed deals can cause front facing error messages on users\' account pages.' ) . '</p>'
				) );

			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
				'<p>' . self::__( '<a href="http://groupbuyingsite.com/forum/showthread.php?745-Adding-a-Deal" target="_blank">Documentation on Adding Deals</a>' ) . '</p>' .
				'<p>' . self::__( '<a href="http://groupbuyingsite.com/forum/" target="_blank">Support Forums</a>' ) . '</p>'
			);
		}
		/**
		 * ***********************************
		 *                Merchants
		 * ***********************************
		 */
		elseif ( $post_type == Group_Buying_Merchant::POST_TYPE ) {
			$screen->add_help_tab( array(
					'id'      => 'merchant-help', // This should be unique for the screen.
					'title'   => self::__( 'Merchant Management' ),
					'content' =>
					'<p><strong>' . self::__( 'What is a Merchant?' ) . '</strong></p>' .
					'<p>' . self::__( 'Merchants provide a method to profile the business associated with a deal. Create merchants is simple.' ) . '</p>' .
					'<p>' . sprintf( self::__( 'A video walkthrough of <a href="%s">adding a merchant</a> is available in the forum.' ), 'http://groupbuyingsite.com/forum/showthread.php?808-Adding-a-Merchant' ) . '</p>',
				) );
			$screen->add_help_tab( array(
					'id'      => 'merchant-help-assign', // This should be unique for the screen.
					'title'   => self::__( 'Authorized Users' ),
					'content' =>
					'<p><strong>' . self::__( 'Authorizing Users to Manage Merchants.' ) . '</strong></p>' .
					'<p>' . self::__( 'Authorized users have the ability to manage the business information within their account. Deals associated with a merchant will also show on the "Business Dashboard" (if your theme permits), providing the authorized user with quick access to sales reports and voucher reports. Authorized users may also submit new deals for site administrator approval.' ) . '</p>'
				) );
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
				'<p>' . self::__( '<a href="http://groupbuyingsite.com/forum/showthread.php?808-Adding-a-Merchant" target="_blank">Documentation on Adding Merchants</a>' ) . '</p>' .
				'<p>' . self::__( '<a href="http://groupbuyingsite.com/forum/" target="_blank">Support Forums</a>' ) . '</p>'
			);
		}
		/**
		 * ***********************************
		 *                Accounts
		 * ***********************************
		 */
		elseif ( $post_type == Group_Buying_Account::POST_TYPE ) {
			$screen->add_help_tab( array(
					'id'      => 'deal-help', // This should be unique for the screen.
					'title'   => self::__( 'Account Management' ),
					'content' =>
					'<p><strong>' . self::__( 'Edit/Manage an Account.' ) . '</strong></p>' .
					'<p>' . self::__( 'Select the account to edit below. Manage contact information, purchase history, add deals, and manage credits.' ) .
					'<p>' . self::__( 'The "user" is the WordPress user associated with the GBS account.' ) . '</p>',
				) );
		}
		/**
		 * ***********************************
		 *                Vouchers
		 * ***********************************
		 */
		elseif ( $post_type == Group_Buying_Voucher::POST_TYPE ) {
			$screen->add_help_tab( array(
					'id'      => 'purchase-help', // This should be unique for the screen.
					'title'   => self::__( 'Vouchers' ),
					'content' =>
					'<p><strong>' . self::__( 'Vouchers generated by GBS are shown below.' ) . '</strong></p>' .
					'<p>' . self::__( 'If a voucher is not yet activated an option to manually activate the voucher will be available. Manually activating vouchers should not be common practice, instead allow the GBS system to process payments and activate the vouchers automatically.' ) . '</p>' .
					'<p><strong>' . self::__( 'Warning:' ) . '</strong></p>' .
					'<p>' . self::__( 'Deleting vouchers can cause front facing error messages on users\' account pages.' ) . '</p>'
				) );
		}
		/**
		 * ***********************************
		 *                Purchases
		 * ***********************************
		 */
		elseif ( $post_type == Group_Buying_Purchase::POST_TYPE ) {
			$screen->add_help_tab( array(
					'id'      => 'purchase-help', // This should be unique for the screen.
					'title'   => self::__( 'Purchases' ),
					'content' =>
					'<p><strong>' . self::__( 'Purchase history.' ) . '</strong></p>' .
					'<p>' . self::__( 'Purchases are shown below.' ) .
					'<p>' . self::__( 'Note: An "authorized" payment is a complete status.' ) . '</p>' .
					'<p><strong>' . self::__( 'Warning:' ) . '</strong></p>' .
					'<p>' . self::__( 'Deleting purchases can cause front facing error messages on users\' account pages.' ) . '</p>',
				) );
		}
	}

	public static function help_section_edit() {
		$screen = get_current_screen();
		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : FALSE;
		if ( $post_id ) {
			$post_type = get_post_type( $post_id );
		} else {
			$post_type = ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) ) ? $_REQUEST['post_type'] : null ;
		}

		/**
		 * ***********************************
		 *                Deals
		 * ***********************************
		 */
		if ( $post_type == Group_Buying_Deal::POST_TYPE ) {
			$screen->add_help_tab( array(
					'id'      => 'deal-help-reports', // This should be unique for the screen.
					'title'   => self::__( 'Deal Reports' ),
					'content' =>
					'<p><strong>' . self::__( 'Purchase Report.' ) . '</strong></p>' .
					'<p>' . self::__( 'Generate a purchase summary. CSV export available.' ) . '</p>' .
					'<p><strong>' . self::__( 'Voucher Report.' ) . '</strong></p>' .
					'<p>' . self::__( 'Generate a report of all vouchers from deal purchases. CSV export available.' ) . '</p>' .
					'<p><strong>' . self::__( 'Warning:' ) . '</strong></p>' .
					'<p>' . self::__( 'Deleting completed deals can cause front facing error messages on users\' account pages.' ) . '</p>',
				) );

			// A bunch of help sections stripped from the posts screen
			$screen->add_help_tab( array(
					'id'  => 'screen-content',
					'title'  => __( 'Screen Content' ),
					'content' =>
					'<p>' . __( 'You can customize the display of this screen&#8217;s contents in a number of ways:' ) . '</p>' .
					'<ul>' .
					'<li>' . __( 'You can filter the list of deals by post status using the text links in the upper left to show All, Published, Draft, or Trashed deals. The default view is to show all deals.' ) . '</li>' .
					'<li>' . __( 'You can refine the list to show only deals in a specific month by using the dropdown menus above the deals list. Click the Filter button after making your selection.' ) . '</li>' .
					'</ul>'
				) );
			$screen->add_help_tab( array(
					'id'  => 'action-links',
					'title'  => __( 'Available Actions' ),
					'content' =>
					'<p>' . __( 'Hovering over a row in the deals list will display action links that allow you to manage your post. You can perform the following actions:' ) . '</p>' .
					'<ul>' .
					'<li>' . __( '<strong>Edit</strong> takes you to the editing screen for that post. You can also reach that screen by clicking on the post title.' ) . '</li>' .
					'<li>' . __( '<strong>Quick Edit</strong> provides inline access to the metadata of your post, allowing you to update post details without leaving this screen.' ) . '</li>' .
					'<li>' . __( '<strong>Trash</strong> removes your post from this list and places it in the trash, from which you can permanently delete it.' ) . '</li>' .
					'<li>' . __( '<strong>Preview</strong> will show you what your draft post will look like if you publish it. View will take you to your live site to view the post. Which link is available depends on your post&#8217;s status.' ) . '</li>' .
					'</ul>'
				) );

			$screen->add_help_tab( array(
					'id'  => 'bulk-actions',
					'title'  => __( 'Bulk Actions' ),
					'content' =>
					'<p>' . __( 'You can also edit or move multiple deals to the trash at once. Select the deals you want to act on using the checkboxes, then select the action you want to take from the Bulk Actions menu and click Apply.' ) . '</p>' .
					'<p>' . __( 'When using Bulk Edit, you can change the metadata (categories, author, etc.) for all selected deals at once. To remove a post from the grouping, just click the x next to its name in the Bulk Edit area that appears.' ) . '</p>'
				) );
		}
		/**
		 * ***********************************
		 *                Accounts
		 * ***********************************
		 */
		elseif ( $post_type == Group_Buying_Account::POST_TYPE ) {

		}
	}

	public static function help_section_edit_post() {
		$screen = get_current_screen();
		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : FALSE;
		if ( $post_id ) {
			$post_type = get_post_type( $post_id );
		} else {
			$post_type = ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) ) ? $_REQUEST['post_type'] : null ;
		}

		/**
		 * ***********************************
		 *                Deals
		 * ***********************************
		 */
		if ( $post_type == Group_Buying_Deal::POST_TYPE ) {
			$customize_display = '<p>' . __( 'The title field and the big Editing Area are fixed in place, but you can reposition all the other boxes using drag and drop, and can minimize or expand them by clicking the title bar of each box. Use the Screen Options tab to unhide more boxes (Slug, Custom Fields, Discussion, etc.) or to choose a 1- or 2-column layout for this screen.' ) . '</p>';

			$screen->add_help_tab( array(
					'id'      => 'customize-display',
					'title'   => __( 'Customizing This Display' ),
					'content' => $customize_display,
				) );

			$screen->add_help_tab( array(
					'id'      => 'deal-help-pricing', // This should be unique for the screen.
					'title'   => self::__( 'Pricing' ),
					'content' =>
					'<p><strong>' . self::__( 'Deal Pricing.' ) . '</strong></p>' .
					'<p>' . self::__( 'Options to set the deal pricing.' ) . '</p>' .
					'<p><strong>' . self::__( 'Dynamic Pricing.' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( 'The milestone pricing (a.k.a. dynamic pricing) feature allows you to set lower prices as more sales are generated for a deal or product. Sales milestones must be achieved before a lower price is activated for all buyers and all buyers will pay the same price when the deal ends (<a href="%s">compatible gateway required</a>).' ), 'http://groupbuyingsite.com/forum/showthread.php?1138-Purchase-Limits-Missing' ) . '</p>' .

					'<p><strong>' . self::__( 'Tax Settings.' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( 'Tax rates and options are <a href="%s">are available here</a>.' ), admin_url( 'admin.php?page=group-buying/gb_tax_settings' ) ) . '</p>' .
					'<p>' . sprintf( self::__( 'A video walkthrough of <a href="%s">the tax settings</a> is available in the forum.' ), 'http://groupbuyingsite.com/forum/showthread.php?3456-Tax-Settings' ) . '</p>' .

					'<p><strong>' . self::__( 'Shipping Settings.' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( 'Shipping rates and options are <a href="%s">are available here</a>.' ), admin_url( 'admin.php?page=group-buying/gb_shipping_settings' ) ) . '</p>' .
					'<p>' . sprintf( self::__( 'A video walkthrough of <a href="%s">the shipping settings</a> is available in the forum.' ), 'http://groupbuyingsite.com/forum/showthread.php?3457-Shipping-Settings' ) . '</p>',

				) );
			$screen->add_help_tab( array(
					'id'      => 'deal-help-limits', // This should be unique for the screen.
					'title'   => self::__( 'Purchase Limits' ),
					'content' =>
					'<p><strong>' . self::__( 'Minimum Sales Required.' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( 'Some gateways don\'t allow multiple captures of the same authorization the purchase needs to be captured immediately.  In those cases, there are no tipping points or purchase requirements. <a href="%s">Read more about it here</a>.' ), 'http://groupbuyingsite.com/forum/showthread.php?1138-Purchase-Limits-Missing' ) . '</p>' .
					'<p>' . sprintf( self::__( 'If you require purchase limits and your gateway has this restriction, GBS has an add-on module that limits the cart to only allow for a single deal purchase; with custom payment gateways that allow for authorization and capturing of funds so purchase limits can be used.' ), 'http://groupbuyingsite.com/marketplace/single-deal-purchases-with-purchase-limits/' ) . '</p>'

				) );
			$screen->add_help_tab( array(
					'id'      => 'deal-help-vouchers', // This should be unique for the screen.
					'title'   => self::__( 'Voucher Settings' ),
					'content' =>
					'<p><strong>' . self::__( 'Voucher details.' ) . '</strong></p>' .
					'<p>' . self::__( 'Details entered will be used to build the voucher generated for deal\'s purchasers.' ) . '</p>'

				) );

			$screen->add_help_tab( array(
					'id'      => 'deal-help-merchant', // This should be unique for the screen.
					'title'   => self::__( 'Merchant' ),
					'content' =>
					'<p><strong>' . self::__( 'Associate a Merchant.' ) . '</strong></p>' .
					'<p>' . sprintf( self::__( 'Deals associated with a <a href="%s">merchant</a> showcase the deal\'s business information (if your theme permits). It also allows for the deal to show on the merchant\'s profile page.' ), admin_url( 'edit.php?post_type=gb_merchant' ) ) . '</p>'

				) );

			// A bunch of help sections stripped from the posts screen
			$title_and_editor  = '<p>' . __( '<strong>Title</strong> - Enter a title for your deal. After you enter a title, you&#8217;ll see the permalink below, which you can edit.' ) . '</p>';
			$title_and_editor .= '<p>' . __( '<strong>Post editor</strong> - Enter the text for your deal. There are two modes of editing: Visual and HTML. Choose the mode by clicking on the appropriate tab. Visual mode gives you a WYSIWYG editor. Click the last icon in the row to get a second row of controls. The HTML mode allows you to enter raw HTML along with your post text. You can insert media files by clicking the icons above the post editor and following the directions. You can go to the distraction-free writing screen via the Fullscreen icon in Visual mode (second to last in the top row) or the Fullscreen button in HTML mode (last in the row). Once there, you can make buttons visible by hovering over the top area. Exit Fullscreen back to the regular post editor.' ) . '</p>';

			$screen->add_help_tab( array(
					'id'      => 'title-post-editor',
					'title'   => __( 'Title and Post Editor' ),
					'content' => $title_and_editor,
				) );

			$publish_box = '<p>' . __( '<strong>Publish</strong> - You can set the terms of publishing your post in the Publish box. For Status, Visibility, and Publish (immediately), click on the Edit link to reveal more options. Visibility includes options for password-protecting a post or making it stay at the top of your blog indefinitely (sticky). Publish (immediately) allows you to set a future or past date and time, so you can schedule a deal to be published in the future or backdate a deal.' ) . '</p>';

			if ( current_theme_supports( 'post-thumbnails' ) && post_type_supports( 'post', 'thumbnail' ) ) {
				$publish_box .= '<p>' . __( '<strong>Featured Image</strong> - This allows you to add an image to be displayed at the top of your deal as well as on deals index pages.' ) . '</p>';
			}

			$screen->add_help_tab( array(
					'id'      => 'publish-box',
					'title'   => __( 'Publish Box' ),
					'content' => $publish_box,
				) );

			$discussion_settings  = '<p>' . __( '<strong>Send Trackbacks</strong> - Trackbacks are a way to notify legacy blog systems that you&#8217;ve linked to them. Enter the URL(s) you want to send trackbacks. If you link to other WordPress sites they&#8217;ll be notified automatically using pingbacks, and this field is unnecessary.' ) . '</p>';
			$discussion_settings .= '<p>' . __( '<strong>Discussion</strong> - You can turn comments and pings on or off, and if there are comments on the post, you can see them here and moderate them.' ) . '</p>';

			$screen->add_help_tab( array(
					'id'      => 'discussion-settings',
					'title'   => __( 'Discussion Settings' ),
					'content' => $discussion_settings,
				) );
		}

		/**
		 * ***********************************
		 *                Accounts
		 * ***********************************
		 */
		elseif ( $post_type == Group_Buying_Account::POST_TYPE ) {
			$screen->add_help_tab( array(
					'id'      => 'account-help-info', // This should be unique for the screen.
					'title'   => self::__( 'Contact Info' ),
					'content' =>
					'<p><strong>' . self::__( 'Contact Information Edit.' ) . '</strong></p>' .
					'<p>' . self::__( 'Review and change the account\'s contact information.' ) . '</p>'

				) );
			$screen->add_help_tab( array(
					'id'      => 'account-help-purchase', // This should be unique for the screen.
					'title'   => self::__( 'Purchase Management' ),
					'content' =>
					'<p><strong>' . self::__( 'Purchase Management and History.' ) . '</strong></p>' .
					'<p>' . self::__( 'Manually add a purchase to the account. Review the purchase history of the account.' ) . '</p>'

				) );
			$screen->add_help_tab( array(
					'id'      => 'account-help-credits', // This should be unique for the screen.
					'title'   => self::__( 'Credit Management' ),
					'content' =>
					'<p><strong>' . self::__( 'Credits.' ) . '</strong></p>' .
					'<p>' . self::__( 'Manage the user\'s credits with the ability to add notes for future reference.' ) . '</p>'

				) );
		}
	}

	public static function enqueue_scripts( $hook_suffix ) {

		// New Features - No Hook
		add_action( 'admin_print_footer_scripts', array( get_class(), 'pointer_new_admins' ) );
		add_action( 'admin_print_footer_scripts', array( get_class(), 'footer_scripts' ) );

		// Add pointers script and style to queue
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
		wp_enqueue_script( 'group-buying-admin' );

		$defaults = array(
			'post-new.php' => 'gb_new_deal',
			'post.php' => 'gb_help_tab_post',
			'edit.php' => 'gb_help_tab_edit',
			'group-buying_page_group-buying/gb_settings' => 'gb_settings',
			'group-buying_page_group-buying/payment' => 'gb_help_tab_options',
			'group-buying_page_group-buying/gb_tax_settings' => 'gb_tax_options',
			'group-buying_page_group-buying/gb_shipping_settings' => 'gb_shipping_options',
			'group-buying_page_group-buying/gb_int_settings' => 'gb_help_tab_options',
			'group-buying_page_group-buying/gb_aggregator_settings' => 'gb_help_tab_options',
			'group-buying_page_group-buying/theme_options' => 'gb_help_tab_options',
			'group-buying_page_group-buying/theme_color_options' => 'gb_help_tab_options',
			'group-buying_page_group-buying/translation' => 'gb_help_tab_options',
			'group-buying_page_group-buying/subscription' => 'gb_help_tab_options',
			'group-buying_page_group-buying/gb_addons' => 'gb_addons_options',
			'group-buying_page_group-buying/gb_aggregator_settings' => 'gb_help_tab_options'
		);

		$registered_pointers = apply_filters( 'gb_pointers', $defaults );

		// Check if screen related pointer is registered
		if ( empty( $registered_pointers[ $hook_suffix ] ) )
			return;

		$pointer = $registered_pointers[ $hook_suffix ];

		$caps_required = array();

		if ( isset( $caps_required[ $pointer ] ) ) {
			foreach ( $caps_required[ $pointer ] as $cap ) {
				if ( ! current_user_can( $cap ) )
					return;
			}
		}

		// Bind pointer print function
		add_action( 'admin_print_footer_scripts', array( get_class(), 'pointer_' . $pointer ) );
	}

	public static function footer_scripts() {
?>
		<script type="text/javascript">
		//<![CDATA[
			jQuery(document).ready( function($) {
				var $step = '<button class="button-primary"><?php self::_e( 'Next Step' ) ?></button>';
				var $first_step = '<button class="button-primary"><?php self::_e( 'Start Tour' ) ?></button>';
				var $last_step = '<button class="button-primary"><?php self::_e( 'Finish' ) ?></button>';
				$(".gb_pointer_step .close").html($step);
				$(".gb_pointer_step_1 .close").html($first_step);
				$(".gb_pointer_step_last .close").html($last_step);
			});
		//]]>
		</script>
		<?php
	}
	/**
	 * Print the pointer javascript data.
	 *
	 * @param string  $pointer_id The pointer ID.
	 * @param string  $selector   The HTML elements, on which the pointer should be attached.
	 * @param array   $args       Arguments to be passed to the pointer JS (see wp-pointer.dev.js).
	 */
	private static function print_js( $pointer_id, $selector, $args, $close = null, $steps = null ) {
		if ( empty( $pointer_id ) || empty( $selector ) || empty( $args ) || empty( $args['content'] ) )
			return;

		// Get dismissed pointers
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

		// Pointer has been dismissed
		if ( in_array( $pointer_id, $dismissed ) )
			return;


?>
		<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function($) {
			var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
			var options = <?php echo json_encode( $args ); ?>;

			if ( ! options )
				return;

			options = $.extend( options, {
				close: function() {
					$.post( ajaxurl, {
						pointer: '<?php echo $pointer_id; ?>',
						action: 'dismiss-wp-pointer'
					});
					<?php echo $close; ?>
				}
			});

			$('<?php echo $selector; ?>').pointer( options ).pointer('open');
		});
		//]]>
		</script>
		<?php
	}


	public static function pointer_gb_help_tab_post() {
		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : FALSE;
		if ( $post_id ) {
			$post_type = get_post_type( $post_id );
		} else {
			$post_type = ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) ) ? $_REQUEST['post_type'] : null ;
		}
		// Help Tab
		self::pointer_gb_help_tab( '_post' );
		// Account pointers
		if ( $post_type == Group_Buying_Account::POST_TYPE ) {
			self::pointer_gb_new_deal();
		}
		// Deal pointers
		if ( $post_type == Group_Buying_Deal::POST_TYPE ) {
			self::pointer_gb_edit_deal_new_features();
		}
		// Notifications pointers
		if ( $post_type == Group_Buying_Notification::POST_TYPE ) {
			self::pointer_gb_edit_notifications_new_features();
		}
	}

	public static function pointer_gb_help_tab_edit() {
		self::pointer_gb_help_tab( '_edit' );
	}

	public static function pointer_gb_help_tab_options() {
		self::pointer_gb_help_tab( '_options' );
	}

	/**
	 * Reports New features
	 */
	public static function pointer_new_admins() {
		$content = '';
		// tax
		$content .= '<p>' . esc_js( self::__( 'With GBS 3.7 the Accounts, Purchases, Vouchers administration areas received major updates. New sections for Payments and Gifts have also been added.' ) ). '</p>';
		self::print_js(
			'gb_new_admin_panels',
			'a.toplevel_page_group-buying',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_admin_panel_pointer',
				'position' => array( 'edge' => 'left', 'align' => 'top' ) )
		);
	}


	/**
	 * Deal New Features
	 */
	public static function pointer_gb_edit_deal_new_features() {

		// tax
		$content  = '<h3>' . esc_js( self::__( 'New Feature: Tax' ) ). '</h3>';
		$content .= '<p>' . sprintf( self::__( 'Tax and Shipping received major updates in version 3.4; make sure to review the <a href="%s">new tax options</a>.' ), admin_url( 'admin.php?page=group-buying/gb_shipping_settings' ) ) . '</p>';
		self::print_js(
			'gb_deal_new_tax',
			'#deal_base_tax',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_pointer',
				'position' => array( 'edge' => 'left', 'align' => 'left' ) )
		);

		// shipping
		$content  = '<h3>' . esc_js( self::__( 'New Feature: Shipping' ) ). '</h3>';
		$content .= '<p>' . sprintf( self::__( 'Shipping and Tax received major updates in version 3.4; make sure to review the <a href="%s">new shipping options</a>.' ), admin_url( 'admin.php?page=group-buying/gb_shipping_settings' ) ) . '</p>';
		self::print_js(
			'gb_deal_new_shipping',
			'#deal_base_shippable_mode',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_pointer',
				'position' => array( 'edge' => 'top', 'align' => 'left' ) )
		);
	}

	/**
	 * Notification New features
	 */
	public static function pointer_gb_edit_notifications_new_features() {

		// tax
		$content  = '<h3>' . esc_js( self::__( 'New Feature: Disable Notification' ) ) . '</h3>';
		$content .= '<p>' . esc_js( self::__( 'With GBS 3.4 you can disable notifications easier.' ) ). '</p>';
		self::print_js(
			'gb_deal_disable_notification',
			'#notification_type_disabled',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_pointer',
				'position' => array( 'edge' => 'right', 'align' => 'left' ) )
		);
	}



	public static function pointer_gb_shipping_options() {

		self::pointer_gb_help_tab( '_options', 'gb_pointer' );

		// tax
		$content  = '<h3>' . esc_js( self::__( 'New Feature:  Shipping Rates' ) ) . '</h3>';
		$content .= '<p>' . esc_js( self::__( 'First select the "Class" the new rate will be assigned to, then create a percentage rate and whether to include shipping in the calculation. If the location based feature is enabled, options for state and region will also show.' ) ). '</p>';
		self::print_js(
			'gb_deal_disable_notification',
			'#gb_add_shipping_rate',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_pointer',
				'position' => array( 'edge' => 'left', 'align' => 'left' ) )
		);

		// shipping
		$content  = '<h3>' . esc_js( self::__( 'New Feature: Classes' ) ) . '</h3>';
		$content .= '<p>' . esc_js( self::__( 'Classes allow you to create multiple rates and choose the appropriate "class" for each individual deal. For example, you could have a "Standard" class for all of your basic deals, but when a special rate is necessary, you can use a second class of shipping rate.  This class will run simultaneously with the standard class on other deals.' ) ). '</p>';
		self::print_js(
			'gb_shippingable_modes',
			'textarea[name="gb_shipping_modes"]',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_pointer',
				'position' => array( 'edge' => 'left', 'align' => 'left' ) )
		);

		// shipping
		$content  = '<h3>' . esc_js( self::__( 'New Feature: Location Based' ) ) . '</h3>';
		$content .= '<p>' . esc_js( self::__( 'Enable this feature to set the shipping rate based on the customer&rsquo;s address set during checkout. When this option is checked and saved, region and state options will be available in the rates table below.' ) ). '</p>';
		self::print_js(
			'gb_enable_shippinges_local',
			'input[name="gb_enable_shipping_local"]',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_pointer',
				'position' => array( 'edge' => 'top', 'align' => 'left' ) )
		);

		// shipping
		$content  = '<h3>' . esc_js( self::__( 'New Feature: Shipping Fees' ) ) . '</h3>';
		$content .= '<p>' . esc_js( self::__( 'Before shipping rates can be used the feature needs to be enabled first.' ) ). '</p>';
		self::print_js(
			'gb_enable_taxes',
			'input[name="gb_enable_shipping"]',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_pointer',
				'position' => array( 'edge' => 'top', 'align' => 'left' ) )
		);


	}

	public static function pointer_gb_tax_options() {

		self::pointer_gb_help_tab( '_options', 'gb_pointer' );

		// tax
		$content  = '<h3>' . esc_js( self::__( 'New Feature:  Tax Rates' ) ) . '</h3>';
		$content .= '<p>' . esc_js( self::__( 'First select the "Mode" the new rate will be assigned to, then create a percentage rate and whether to include shipping in the calculation. If the location based feature is enabled, options for state and region will also show.' ) ). '</p>';
		self::print_js(
			'gb_taxable_rates',
			'#gb_add_tax_rate',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_pointer',
				'position' => array( 'edge' => 'left', 'align' => 'left' ) )
		);

		// tax
		$content  = '<h3>' . esc_js( self::__( 'New Feature: Modes' ) ) . '</h3>';
		$content .= '<p>' . esc_js( self::__( 'Modes allow you to create multiple rates and/or rate tables and choose the appropriate "mode" for each individual deal. For example, you could have a "Standard" mode for all of your basic deals, but when a special rate is necessary, you can use a second mode of taxation.  This mode will run simultaneously with the standard mode on other deals.' ) ). '</p>';
		self::print_js(
			'gb_taxable_modes',
			'textarea[name="gb_taxable_modes"]',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_pointer',
				'position' => array( 'edge' => 'left', 'align' => 'left' ) )
		);

		// tax
		$content  = '<h3>' . esc_js( self::__( 'New Feature: Location Based' ) ) . '</h3>';
		$content .= '<p>' . esc_js( self::__( 'Enable this feature to set the tax rate based on the customer&rsquo;s address set during checkout. When this option is checked and saved, region and state options will be available in the rates table below.' ) ). '</p>';
		self::print_js(
			'gb_enable_taxes_local',
			'input[name="gb_enable_taxes_local"]',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_pointer',
				'position' => array( 'edge' => 'top', 'align' => 'left' ) )
		);

		// tax
		$content  = '<h3>' . esc_js( self::__( 'New Feature: Calculate Tax' ) ) . '</h3>';
		$content .= '<p>' . esc_js( self::__( 'Before taxes can be calculated the feature needs to be enabled first.' ) ). '</p>';
		self::print_js(
			'gb_enable_taxes',
			'input[name="gb_enable_taxes"]',
			array(
				'content'  => $content,
				'pointerWidth' => 300,
				'pointerClass' => 'gb_pointer',
				'position' => array( 'edge' => 'top', 'align' => 'left' ) )
		);


	}

	public static function pointer_gb_new_deal() {

		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : FALSE;
		if ( $post_id ) {
			$post_type = get_post_type( $post_id );
		} else {
			$post_type = ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) ) ? $_REQUEST['post_type'] : null ;
		}
		/**
		 * ***********************************
		 *                Deals
		 * ***********************************
		 */
		if ( $post_type == Group_Buying_Deal::POST_TYPE ) {

			// Step 1
			$close_callback = "$('.gb_pointer_step_2').fadeTo('slow', 1);";
			self::pointer_gb_help_tab( '_deal_help', 'gb_pointer_step_1', $close_callback );

			// Step 2
			$content = '<p>' . esc_js( self::__( 'Set deal&#8217;s price. Set dynamic pricing (if gateway permitted), tax, and shipping below. Tax and Shipping received major updates in version 3.4, make sure to review the new options pages for each.' ) ) . '</p>';
			$close_callback = "$('.gb_pointer_step_3').ScrollTo();$('.gb_pointer_step_3').fadeTo('slow', 1)";
			self::print_js(
				'gb_deal_pricing',
				'#deal_base_price',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_2',
					'position' => array( 'edge' => 'top', 'align' => 'left' ) ),
				$close_callback
			);

			// Step 3
			$content = '<p>' . esc_js( self::__( 'Set purchase requirements and tipping points (if gateway permitted).' ) ) . '</p>';
			$close_callback = "$('.gb_pointer_step_4').ScrollTo();$('.gb_pointer_step_4').fadeTo('slow', 1)";
			self::print_js(
				'gb_deal_limits',
				'#deal_max_purchases',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_3',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) ),
				$close_callback
			);

			// Step 4
			$content = '<p>' . esc_js( self::__( 'Deal details used for front-end display, vouchers, and RSS.' ) ) . '</p>';
			$close_callback = "$('.gb_pointer_step_5').ScrollTo();$('.gb_pointer_step_5').fadeTo('slow', 1)";
			self::print_js(
				'gb_deal_details',
				'#deal_value',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_4',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) ),
				$close_callback
			);


			// Step 5
			$content = '<p>' . esc_js( self::__( 'Voucher details entered will be used to build the voucher generated for deal&#8217;s purchasers. Some GBS themes use this information for the front end display too.' ) ) . '</p>';
			$close_callback = "$('.gb_pointer_step_6').ScrollTo();$('.gb_pointer_step_6').fadeTo('slow', 1)";
			self::print_js(
				'gb_deal_voucher_details',
				'#voucher_how_to_use',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_5',
					'position' => array( 'edge' => 'top', 'align' => 'left' ) ),
				$close_callback
			);

			// Step 6
			$content = '<p>' . self::__( 'Associating a deal with a merchant will showcase the deal&#8217;s business information (if your theme permits). It also allows for the deal to show on the merchant&#8217;s profile page.' ) . '</p>';
			$close_callback = "$('.gb_pointer_step_7').ScrollTo();$('.gb_pointer_step_7').fadeTo('slow', 1)";
			self::print_js(
				'gb_deal_assign_merchant',
				'#deal_merchant',
				array(
					'content'  => $content,
					'pointerWidth' => 360,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_6',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) ),
				$close_callback
			);

			// Step 7
			$content = '<p>' . self::__( 'Set the deal&#8217;s featured image for the theme to display.' ) . '</p>';
			$close_callback = "$('.gb_pointer_step_8').ScrollTo();$('.gb_pointer_step_8').fadeTo('slow', 1)";
			self::print_js(
				'gb_deal_featured',
				'#set-post-thumbnail',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_7',
					'position' => array( 'edge' => 'right', 'align' => 'right' ) ),
				$close_callback
			);

			// Step 8
			$content = '<p>' . self::__( 'Assign locations to the deal. This is critical for most GBS themes since location preferences filter and direct visitors to the relavent deal(s).' ) . '</p>';
			$close_callback = "$('.gb_pointer_step_9').ScrollTo();$('.gb_pointer_step_9').fadeTo('slow', 1)";
			self::print_js(
				'gb_deal_locations',
				'#gb_location-add-toggle',
				array(
					'content'  => $content,
					'pointerWidth' => 360,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_8',
					'position' => array( 'edge' => 'right', 'align' => 'right' ) ),
				$close_callback
			);

			// Step 9 - FINAL
			$content = '<p>' . self::__( 'Set the deal expiration or select the option for the deal to not expire.' ) . '</p>';
			self::print_js(
				'gb_settings_exp',
				'#deal_expiration',
				array(
					'content'  => $content,
					'pointerWidth' => 360,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_last gb_pointer_step_9',
					'position' => array( 'edge' => 'right', 'align' => 'right' ) ),
				$close_callback
			);
		}
		/**
		 * ***********************************
		 *         Merchant - No Tour
		 * ***********************************
		 */
		elseif ( $post_type == Group_Buying_Merchant::POST_TYPE ) {

			$close_callback = "$('.gb_pointer_step_2').fadeTo('slow', 1);";
			self::pointer_gb_help_tab( '_options', 'gb_pointer' );

			$content  = '<h3>' . esc_js( self::__( 'Authorized Users' ) ). '</h3>';
			$content .= '<p>' . esc_js( self::__( 'Authorized users have the ability to manage the business information within their account. Deals associated with a merchant will also show on the "Business Dashboard" (if your theme permits), providing the authorized user with quick access to sales reports and voucher reports.  Authorized users can also create new deals for the site administrator to approve.' ) ) . '</p>';
			self::print_js(
				'gb_settings_authorize_user',
				'#authorized_user',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'gb_pointer',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) )
			);

			$content  = '<h3>' . esc_js( self::__( 'Authorized Users' ) ). '</h3>';
			$content .= '<p>' . esc_js( self::__( 'Authorized users have the ability to manage the business information within their account. Deals associated with a merchant will also show on the "Business Dashboard" (if your theme permits), providing the authorized user with quick access to sales reports and voucher reports.  Authorized users can also create new deals for the site administrator to approve.' ) ) . '</p>';
			self::print_js(
				'gb_settings_authorize_user',
				'#authorized_user',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'gb_pointer',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) )
			);
		}
		/**
		 * ***********************************
		 *         Account - No Tour
		 * ***********************************
		 */
		elseif ( $post_type == Group_Buying_Account::POST_TYPE ) {

			$content  = '<p>' . esc_js( self::__( 'Review and change the account&#8217;s contact information.' ) ) . '</p>';
			self::print_js(
				'gb_settings_account_first_name',
				'#account_first_name',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'gb_pointer',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) )
			);

			$content  = '<p>' . esc_js( self::__( 'Manually add a purchase to the account. Review the purchase history of the account.' ) ) . '</p>';
			self::print_js(
				'gb_settings_account_purchases',
				'#gb_added_deal_id',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'gb_pointer',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) )
			);

			$content  = '<p>' . esc_js( self::__( 'Manage the user&#8217;s credits with the ability to add notes for future reference.' ) ) . '</p>';
			self::print_js(
				'gb_settings_account_credits',
				'input[name="account_credit_balance[affiliate]"]',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'gb_pointer',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) )
			);
		}
	}

	public static function pointer_gb_settings() {

		if ( gb_is_authorized() ) {

			// Step 1
			$close_callback = "$('.gb_pointer_step_2').fadeTo('slow', 1);";
			self::pointer_gb_help_tab( '_options', 'gb_pointer_step_1', $close_callback );

			// Step 2
			$content  = '<p>' . esc_js( self::__( 'Force Login options allow for the site to be locked down until your site is available for the public.' ) ) . '</p>';
			$close_callback = "$('.gb_pointer_step_3').ScrollTo();$('.gb_pointer_step_3').fadeTo('slow', 1)";
			self::print_js(
				'gb_settings_force_login',
				'input[name="gb_force_login"][value="false"]',
				array(
					'content'  => $content,
					'pointerWidth' => 360,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_2',
					'position' => array( 'edge' => 'top', 'align' => 'left' ) ),
				$close_callback
			);

			// Step 3
			$content  = '<p>' . esc_js( self::__( 'Select your TOC and PP pages so they can be linked on the registration page. Also set how much information should be required when your users register with full and minimal options.' ) ) . '</p>';
			$close_callback = "$('.gb_pointer_step_4').ScrollTo();$('.gb_pointer_step_4').fadeTo('slow', 1)";
			self::print_js(
				'gb_settings_registration',
				'#gb_pp_page',
				array(
					'content'  => $content,
					'pointerWidth' => 450,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_3',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) ),
				$close_callback
			);

			// Step 4
			$content  = '<p>' . esc_js( self::__( 'Set the from name and from email address for all notifications that GBS sends. HTML notifications require you modify your notifications to include HTML formatting first.' ) ) . '</p>';
			$close_callback = "$('.gb_pointer_step_5').ScrollTo();$('.gb_pointer_step_5').fadeTo('slow', 1)";
			self::print_js(
				'gb_settings_notification_from_name',
				'input[name="gb_notification_from_email"]:first',
				array(
					'content'  => $content,
					'pointerWidth' => 360,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_4',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) ),
				$close_callback
			);


			// Step 5
			$content  = '<p>' . esc_js( self::__( 'Configure the global options that are printed on your vouchers.' ) ) . '</p>';
			$close_callback = "$('.gb_pointer_step_6').ScrollTo();$('.gb_pointer_step_6').fadeTo('slow', 1)";
			self::print_js(
				'gb_settings_voucher_logo',
				'input[name="gb_voucher_logo"]:first',
				array(
					'content'  => $content,
					'pointerWidth' => 360,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_5',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) ),
				$close_callback
			);

			// Step 6
			$content = '<p>' . self::__( 'Customize the URLs of the GBS sections.' ) . '</p>';
			$close_callback = "$('.gb_pointer_step_7').ScrollTo();$('.gb_pointer_step_7').fadeTo('slow', 1)";
			self::print_js(
				'gb_settings_custom_paths',
				'#gb_account_login_path',
				array(
					'content'  => $content,
					'pointerWidth' => 360,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_6',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) ),
				$close_callback
			);

			// Step 7 - FINAL
			$content  = '<p>' . self::__( 'After configuring all of these general settings and options move on to the payments section.' ) . '</p>';
			self::print_js(
				'gb_settings_final',
				'#gb_options_tab_payment',
				array(
					'content'  => $content,
					'pointerWidth' => 360,
					'pointerClass' => 'gb_pointer gb_pointer_step gb_pointer_step_last gb_pointer_step_7',
					'position' => array( 'edge' => 'top', 'align' => 'left' ) ),
				$close_callback
			);


		} else {
			$content  = '<h3>' . esc_js( self::__( 'Warning: API Key' ) ). '</h3>';
			$content .= '<p>' . self::__( 'Enter your API key here before you get started. Your API key can be found on your <a href="http://groupbuyingsite.com/account/" target="_blank">account page at gbs.com</a>.' ) . '</p>';

			self::print_js(
				'gb_settings',
				'#group_buying_site_api_key',
				array(
					'content'  => $content,
					'pointerWidth' => 360,
					'pointerClass' => 'gb_pointer gb_pointer_api_key',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) )
			);
		}
	}

	/**
	 * Help tab function used for posts, edit screens and option pages.
	 */
	public static function pointer_gb_addons_options() {

		if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'gb_shop' ) {
			return;
		}

		$content  = '<h3>' . esc_js( self::__( 'Browse to Extend' ) ). '</h3>';
		$content .= '<p>' . self::__( 'Browse the GBS add-on marketplace and extend your Group Buying Site. New add-ons are added regularly.' ) . '</p>';

		self::print_js(
			'gb_settings_addons_browse',
			'.theme-install-upload',
			array(
				'content'  => $content,
				'pointerWidth' => 240,
				'pointerClass' => 'gb_pointer gb_pointer_addons',
				'position' => array( 'edge' => 'top', 'align' => 'left' ) )
		);
	}

	/**
	 * Help tab function used for posts, edit screens and option pages.
	 */
	public static function pointer_gb_help_tab( $context = null, $class = null, $close_callback = null ) {
		$content  = '<h3>' . esc_js( self::__( 'Help Section' ) ). '</h3>';
		$content .= '<p>' . esc_js( self::__( 'Get familiar with this help tab. You&#8217;ll see it on almost every admin page and the GBS support team works really hard to keep it super helpful and up-to-date.' ) ) . '</p>';

		self::print_js(
			'gb_help_tab'.$context,
			'#contextual-help-link-wrap',
			array(
				'content'  => $content,
				'pointerWidth' => 250,
				'pointerClass' => 'gb_pointer gb_pointer_help_tab '.$class,
				'position' => array( 'edge' => 'top', 'align' => 'right' ) ),
			$close_callback
		);

		// Pimp Addons everywhere
		$content  = '<h3>' . esc_js( self::__( 'Add-on Marketplace' ) ). '</h3>';
		$content .= '<p>' . self::__( 'Extend your Group Buying Site with add-ons sold in the GBS marketplace. New add-ons are added regularly.' ) . '</p>';

		self::print_js(
			'gb_settings_addons',
			'#gb_options_tab_gb_addons',
			array(
				'content'  => $content,
				'pointerWidth' => 240,
				'pointerClass' => 'gb_pointer gb_pointer_addons',
				'position' => array( 'edge' => 'top', 'align' => 'left' ) )
		);
	}

	/**
	 * Prevents new users from seeing existing 'new feature' pointers.
	 */
	public static function dismiss_pointers_for_new_users( $user_id ) {
		add_user_meta( $user_id, 'dismissed_wp_pointers', 'gb_deal_new_tax,gb_deal_new_ship' );
	}



	/**
	 * Creates an Admin Bar Option Item and submenu for any registered sub-menus ( admin submenu )
	 *
	 * @static
	 * @return void
	 */
	public static function wp_admin_bar_options( WP_Admin_Bar $wp_admin_bar ) {

		if ( !current_user_can( 'manage_options' ) )
			return;

		$menu_items = apply_filters( 'gb_admin_bar', array() );
		$sub_menu_items = apply_filters( 'gb_admin_bar_sub_items', array() );

		$wp_admin_bar->add_node( array(
				'id' => self::MENU_ID,
				'parent' => false,
				'title' => '<span class="gb-icon"><img src="' . GB_URL . '/resources/img/gbs-menu.png" /></span>',
				'href' => admin_url( 'edit.php?post_type='.Group_Buying_Deal::POST_TYPE )
			) );

		uasort( $menu_items, array( get_class(), 'sort_by_weight' ) );
		foreach ( $menu_items as $item ) {
			$wp_admin_bar->add_node( array(
					'parent' => self::MENU_ID,
					'id' => $item['id'],
					'title' => self::__($item['title']),
					'href' => $item['href'],
				) );
		}

		$wp_admin_bar->add_group( array(
				'parent' => self::MENU_ID,
				'id'     => self::MENU_ID.'_options',
				'meta'   => array( 'class' => 'ab-sub-secondary' ),
			) );

		$admin_pages = self::get_admin_pages();
		uasort( $admin_pages, array( get_class(), 'sort_by_weight' ) );
		foreach ( $admin_pages as $page => $data ) {
			$sub_menu_items[] = array(
				'parent' => self::MENU_ID.'_options',
				'id' => $page,
				'title' => self::__(str_replace( 'Settings', '', $data['menu_title'] )),
				'href' => admin_url( 'admin.php?page='.$page ),
				'weight' => $data['weight'],
			);
		}

		uasort( $sub_menu_items, array( get_class(), 'sort_by_weight' ) );
		foreach ( $sub_menu_items as $item ) {
			$wp_admin_bar->add_node( array(
					'parent' => self::MENU_ID.'_options',
					'id' => $item['id'],
					'title' => self::__($item['title']),
					'href' => $item['href'],
				) );
		}
	}

	public function display_welcome() {
		echo "Welcome";
	}
}
