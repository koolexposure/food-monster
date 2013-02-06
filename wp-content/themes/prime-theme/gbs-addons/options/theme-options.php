<?php
if ( class_exists( 'Group_Buying_Controller' ) ) {

	include 'template-tags.php';

	class Group_Buying_Theme_UI extends Group_Buying_Controller {
		const CUSTOM_CSS_OPTION = 'gb_custom_css';
		const HEADER_LOGO_OPTION = 'gb_theme_header_logo';
		const FOOTER_SCRIPT_OPTION = 'gb_theme_footer_script';
		const CUSTOM_CSS_VAR = 'gb_custom_css';
		const TWITTER_OPTION = 'gb_twitter';
		const FACEBOOK_OPTION = 'gb_facebook';
		const NO_DEALS_CONTENT = 'gb_nodeal_content';
		const TOS_PAGE_ID = 'gb_tos_page';
		const PP_PAGE_ID = 'gb_pp_page';
		const FORCE_LOGIN = 'gb_force_login';
		const FLAVOR_ARRAY = 'gb_flavor_array2';
		const CUSTOMIZER_OPTIONS_PREFIX = 'gb_customizer_options_';
		const CONVERT_THEME_CUSTOMIZATIONS = 'gb_has_converted_theme_options_for_customizer';
		const CUSTOMIZER_RESET_QUARY_ARG = 'gb_customizer_reset';
		private static $custom_css_path = 'flavor/css';
		private static $instance;
		protected static $theme_settings_page;
		protected static $theme_color_settings_page;
		protected static $flavor;
		protected static $custom_css;
		protected static $header_logo;
		protected static $footer_scripts;
		protected static $twitter;
		protected static $facebook;
		protected static $nodeal_pageid;
		protected static $tos_pageid;
		protected static $pp_pageid;
		protected static $force_login;
		protected static $customizer_options;
		protected static $deprecated_registered_colors;
		protected static $font_sizes = array(
			'0.1em' => '0.1em',
			'0.2em' => '0.2em',
			'0.4em' => '0.4em',
			'0.6em' => '0.6em',
			'0.8em' => '0.8em',
			'1em' => '1em',
			'1.1em' => '1.1em',
			'1.2em' => '1.2em',
			'1.3em' => '1.3em',
			'1.4em' => '1.4em',
			'1.5em' => '1.5em',
			'1.6em' => '1.6em',
			'1.7em' => '1.7em',
			'1.8em' => '1.8em',
			'1.9em' => '1.9em',
			'2em' => '2em',
			'2.5em' => '2.5em',
			'3em' => '3em',
			'3.5em' => '3.5em',
			'4em' => '4em',
			'5em' => '5em',
		);
		protected static $font_faces = array(
			'Arial' => array( 'font-family' => 'Arial, Helvetica, Geneva, sans-serif', 'font-name' => 'Arial' ),
			'Arial Black' => array( 'font-family' => '"Arial Black", Arial, Helvetica, Geneva, sans-serif', 'font-name' => 'Arial Black' ),
			'Impact' => array( 'font-family' => '"Impact", Arial, Helvetica, Geneva, sans-serif', 'font-name' => 'Impact' ),
			'Tahoma' => array( 'font-family' => 'Tahoma, Arial, sans-serif', 'font-name' => 'Tahoma' ),
			'Verdana' => array( 'font-family' => 'Verdana, Arial, sans-serif', 'font-name' => 'Verdana' ),
			'Georgia' => array( 'font-family' => 'Georgia, "Times New Roman", Times, serif', 'font-name' => 'Georgia' ),
			'Helvetica Neue' => array( 'font-family' => '"Helvetica Neue", Arial, Helvetica, Geneva, sans-serif', 'font-name' => 'Helvetica Neue' ),
			'Trebuchet MS' => array( 'font-family' => 'Trebuchet MS, "Times New Roman", Times, serif', 'font-name' => 'Trebuchet MS' ),
			'Garamond' => array( 'font-family' => 'Garamond, "Times New Roman", Times, serif', 'font-name' => 'Garamond' ),
			'Times New Roman' => array( 'font-family' => '"Times New Roman", Times, serif', 'font-name' => 'Times New Roman' ),
			'Lucida Grande' => array( 'font-family' => 'font-family: "Lucida Grande", Lucida, Verdana, sans-serif', 'font-name' => 'Lucida Grande' ),
			'Courier New' => array( 'font-family' => '"Courier New", Courier, mono', 'font-name' => 'Courier New' ),
			'web-fonts' => array( 'font-family' => 'Arial, Helvetica, Geneva, sans-serif', 'font-name' => '==Google Web Fonts==' ),
		);



		public static function init() {
			self::get_instance();

			self::$theme_settings_page = self::register_settings_page( 'theme_options', sprintf( self::__( '%s Options' ), GBS_THEME_NAME ), self::__( 'Theme Options' ), 9999, FALSE, 'theme' );
			// self::$theme_color_settings_page = self::register_settings_page( 'theme_color_options', sprintf( self::__( '%s Styling' ), GBS_THEME_NAME ), self::__( 'Theme Styling' ), 10000, TRUE, 'theme' );
			add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 0 );
			add_action( self::DAILY_CRON_HOOK, array( get_class(), 'daily_clean_up' ) );
			self::register_path_callback( self::$custom_css_path, array( get_class(), 'custom_css' ), self::CUSTOM_CSS_VAR );
			add_filter( 'gbs_no_ssl_redirect', array( get_class(), 'ssl_redirect' ), 10 );

			global $pagenow;
			if ( is_admin() && isset( $_GET['activated'] ) && $pagenow == 'themes.php' ) {
				self::create_location_table();
			}
			self::setup_location_table(); // This is persistent.

			self::$custom_css = get_option( self::CUSTOM_CSS_OPTION );
			self::$header_logo = get_option( self::HEADER_LOGO_OPTION );
			self::$footer_scripts = get_option( self::FOOTER_SCRIPT_OPTION );
			self::$twitter = get_option( self::TWITTER_OPTION );
			self::$facebook = get_option( self::FACEBOOK_OPTION );
			self::$nodeal_pageid = get_option( self::NO_DEALS_CONTENT, '0' );
			self::$tos_pageid = get_option( self::TOS_PAGE_ID, '0' );
			self::$pp_pageid = get_option( self::PP_PAGE_ID, '0' );
			self::$force_login = get_option( self::FORCE_LOGIN, 'false' );
			self::$deprecated_registered_colors = get_option( self::FLAVOR_ARRAY, array() );
			self::$customizer_options = get_option( self::CUSTOMIZER_OPTIONS_PREFIX . GBS_THEME_SLUG, self::get_registrations() );

			// Theme Customizer (WP 3.4+)
			if ( version_compare( get_bloginfo( 'version' ), '3.4', '>=' ) ) {
				add_action( 'customize_register', array( get_class(), 'theme_customizer' ) );
				// Reset Customizer options
				add_action( 'admin_init', array( get_class(), 'reset_customizer_options' ) );
				add_action( 'wp_ajax_gbs_reset_customizer_options',  array( get_class(), 'reset_customizer_options' ), 10, 0 );
				add_action( 'admin_init', array( get_class(), 'gb_converted_theme_customizer' ) );
				add_action( 'admin_init', array( get_class(), 'customizer_redirect' ), 100 );
			}

			// Install Child theme
			add_filter( 'themes_api_result', array( get_class(), 'themes_api_result' ), 10, 3 );

			// Menus and help (WP 3.3+)
			add_action( 'load-group-buying_page_group-buying/theme_options', array( get_class(), 'options_help_section' ), 45 );
			add_action( 'load-group-buying_page_group-buying/theme_color_options', array( get_class(), 'options_help_section' ), 45 );
			// Defaults
			add_action( 'load-group-buying_page_group-buying/translation', array( get_class(), 'options_help_section_defaults' ), 50 );
			add_action( 'load-group-buying_page_group-buying/subscription', array( get_class(), 'options_help_section_defaults' ), 50 );
			add_action( 'load-group-buying_page_group-buying/theme_options', array( get_class(), 'options_help_section_defaults' ), 50 );
			add_action( 'load-group-buying_page_group-buying/theme_color_options', array( get_class(), 'options_help_section_defaults' ), 50 );
		}

		private function __construct() {
			// Flavor
			add_action( 'parse_request', array( get_class(), 'flavor_css' ) );
			// Footer Scripts
			add_action( 'wp_footer', array( get_class(), 'gbs_footer_scripts' ), 100 );
			// Location Flavor
			add_action( gb_get_location_tax_slug().'_edit_form_fields', array( $this, 'location_input_metabox' ), 10, 2 );
			add_action( 'admin_init', array( get_class(), 'add_colorpicker' ) );
			add_action( 'edited_terms', array( get_class(), 'save_location_meta_data' ) );
			add_action( 'wp_head', array( get_class(), 'location_css' ) );
			add_filter( 'theme_mod_background_image', array( get_class(), 'background_image_filter' ) , 10, 1 );
			add_filter( 'gb_account_registration_panes', array( get_class(), 'display_tos_message' ), 100 );
		}


		//////////////////////
		// Theme Customizer //
		//////////////////////

		public function get_registrations( $reset = FALSE ) {
			$current = get_option( self::CUSTOMIZER_OPTIONS_PREFIX . GBS_THEME_SLUG );
			if ( $current && !$reset )
				return $current;

			self::color_registration_merge();
			update_option( self::CUSTOMIZER_OPTIONS_PREFIX . GBS_THEME_SLUG, self::$customizer_options );
			return self::$customizer_options;
		}

		public static function color_registration_merge() {
			foreach ( gb_custom_color_registrations() as $key => $values ) { // loop through registered colors/fonts/sizes
				$colors = gb_custom_color_registrations();
				foreach ( $colors[$key]['rules'] as $rule => $value ) { // loop through ALL options
					if ( strstr( $rule, 'color' ) || strstr( $rule, 'background' ) ) {
						self::$customizer_options[$key.'-'.$rule] = '#'.$value;
					} else {
						self::$customizer_options[$key.'-'.$rule] = $value;
					}
				}
			}
			return self::$customizer_options;
		}

		public static function reset_customizer_options() {

			if ( ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == self::CUSTOMIZER_RESET_QUARY_ARG ) || $start ) {
				self::get_registrations( TRUE );
				if ( !defined( 'DOING_AJAX' ) ) {
					wp_redirect( remove_query_arg( 'action' ) );
				}
				exit();
			}
		}

		public static function gb_converted_theme_customizer() {
			if ( get_option( self::CONVERT_THEME_CUSTOMIZATIONS, FALSE ) )
				return;

			foreach ( gb_custom_color_registrations() as $key => $values ) { // loop through registered colors/fonts/sizes
				$colors = wp_parse_args( self::$deprecated_registered_colors, gb_custom_color_registrations() ); // merge the registered options and previous options.
				foreach ( $colors[$key]['rules'] as $rule => $value ) { // loop through ALL options
					if ( strstr( $rule, 'color' ) || strstr( $rule, 'background' ) ) {
						self::$customizer_options[$key.'-'.$rule] = '#'.$value;
					} else {
						self::$customizer_options[$key.'-'.$rule] = $value;
					}
				}
			}
			update_option( self::CUSTOMIZER_OPTIONS_PREFIX . GBS_THEME_SLUG, self::$customizer_options );
			update_option( self::CONVERT_THEME_CUSTOMIZATIONS, 1 ); // Set the option so this doesn't run again.
		}

		public static function theme_customizer( $wp_customize ) {

			$wp_customize->add_section( 'gbs_theme_color_schemer', array(
					'title'          => gb__( 'GBS Theme Color Styling' ),
					'priority'       => 35,
				) );

			$wp_customize->add_section( 'gbs_theme_font_schemer', array(
					'title'          => gb__( 'GBS Theme Font Styling' ),
					'priority'       => 37,
				) );

			$wp_customize->add_section( 'gbs_theme_other_schemer', array(
					'title'          => gb__( 'GBS Misc. Theme Styling' ),
					'priority'       => 39,
				) );
			$count = 0;
			foreach ( gb_custom_color_registrations() as $key => $values ) { // loop through registered colors/fonts/sizes
				$colors = gb_custom_color_registrations(); // merge in all the options
				foreach ( $colors[$key]['rules'] as $rule => $default_value ) { // loop through ALL options

					$count ++;
					$slug = $key.'-'.$rule;
					$option_name = self::CUSTOMIZER_OPTIONS_PREFIX . GBS_THEME_SLUG."[".$slug."]";
					$value = ( isset( self::$customizer_options[$slug] ) && self::$customizer_options[$slug] ) ? self::$customizer_options[$slug] : $default_value ;

					if ( strstr( $rule, 'color' ) || strstr( $rule, 'background' ) ) {
						$wp_customize->add_setting( $option_name, array(
								'default'           => $value,
								'type'              => 'option',
								'capability'        => 'edit_theme_options',
								'transport'         => 'postMessage',
							) );

						switch ( $rule ) {
						case 'background':
						case 'background-color':
						case 'background-important':
							$type = gb__( 'Background Color' );
							break;
						case 'color':
						case 'color-important':
							$type = gb__( 'Text Color' );
							break;
						default:
							$type = gb__( 'Color' );
							break;
						}

						$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $slug, array(
									'label'   => $colors[$key]['name'] . ' &mdash; ' . $type,
									'section' => 'gbs_theme_color_schemer',
									'settings'   => $option_name,
									'priority' => $count
								) ) );
					}
					elseif ( strstr( $rule, 'font-family' ) ) {

						$wp_customize->add_setting( $option_name, array(
								'default'           => $value,
								'type'              => 'option',
								'capability'        => 'edit_theme_options',
								'transport'         => 'postMessage',
							) );

						$fonts = array();
						foreach ( self::get_fonts() as $font_id => $font ) {
							$fonts[sanitize_title_with_dashes( $font['font-name'] )] = $font['font-name'];
						}

						$wp_customize->add_control( $slug, array(
								'label'   => 'Font Family &mdash; ' . $colors[$key]['name'],
								'section' => 'gbs_theme_font_schemer',
								'type'    => 'select',
								'choices'    => $fonts,
								'settings'   => $option_name,
								'priority' => $count
							) );

					}
					elseif ( strstr( $rule, 'font-size' ) ) {

						$wp_customize->add_setting( $option_name, array(
								'default'           => $value,
								'type'              => 'option',
								'capability'        => 'edit_theme_options',
								'transport'         => 'postMessage',
							) );

						$wp_customize->add_control( $slug, array(
								'label'   => 'Font Size &mdash; ' . $colors[$key]['name'],
								'section' => 'gbs_theme_font_schemer',
								'type'    => 'select',
								'choices'    => self::$font_sizes,
								'settings'   => $option_name,
								'priority' => $count
							) );
					}
					elseif ( strstr( $rule, 'border-radius' ) ) {

						$wp_customize->add_setting( $option_name, array(
								'default'           => $value,
								'type'              => 'option',
								'capability'        => 'edit_theme_options',
								'transport'         => 'postMessage',
							) );

						$wp_customize->add_control( $slug, array(
								'label'   => $colors[$key]['name'] . ' &mdash; Border Radius',
								'section' => 'gbs_theme_other_schemer',
								'type'    => 'select',
								'choices'    => self::$font_sizes,
								'settings'   => $option_name,
								'priority' => $count
							) );
					}
					else {

						$wp_customize->add_setting( $option_name, array(
								'default'           => $value,
								'type'              => 'option',
								'capability'        => 'edit_theme_options',
								'transport'         => 'postMessage',
							) );

						$wp_customize->add_control( $slug, array(
									'label'   => $colors[$key]['name'],
									'section' => 'gbs_theme_other_schemer',
									'settings'   => $option_name,
									'type'    => 'input',
									'priority' => $count
								) );
					}

				}
			}

			if ( $wp_customize->is_preview() && !is_admin() )
				add_action( 'wp_footer', array( get_class(), 'customizer_preview_js' ), 21 );
		}

		public static function customizer_preview_js() {
			echo '<script type="text/javascript">';
			echo '( function( $ ) {';
			foreach ( gb_custom_color_registrations() as $key => $values ) { // loop through registered colors/fonts/sizes
				$colors = gb_custom_color_registrations();
				foreach ( $colors[$key]['rules'] as $rule => $value ) { // loop through ALL options
					
					$slug = $key.'-'.$rule;
					$option_name = self::CUSTOMIZER_OPTIONS_PREFIX . GBS_THEME_SLUG."[".$slug."]";

					echo 'wp.customize( "'.$option_name.'", function( value ) {';
						echo 'value.bind( function( to ) {';
							echo "$('".$values['selectors']."').css('".$rule."', to ? to : '' );";

						echo '});';
					echo '});';
				}
			}
			echo '} )( jQuery )';
			echo '</script>';
		}

		////////////////
		// Flavor CSS //
		////////////////

		public static function get_flavor_css() {
			$output = '';
			$imports = '';
			foreach ( gb_custom_color_registrations() as $key => $values ) { // loop through registered colors/fonts/sizes
				$registered_rules = gb_custom_color_registrations(); // merge in all the options
				foreach ( $registered_rules[$key]['rules'] as $rule => $default_value ) { // loop through ALL options
					
					$slug = $key.'-'.$rule;
					$value = ( isset( self::$customizer_options[$slug] ) && self::$customizer_options[$slug] ) ? self::$customizer_options[$slug] : $default_value ;

					$added = array(); // don't repeat already imported font faces
					$important = '';

					// Handle Fonts differently
					if ( !in_array( $rule, $added ) && in_array( $rule, array( 'font-family', 'font-family-important' ) ) ) {

						$all_fonts = self::get_fonts();
						// Loop through all the fonts. TODO don't loop through, instead search ?
						foreach ( $all_fonts as $fonts_key => $fonts_value ) {
							// setup font name to match option value
							$font = sanitize_title_with_dashes( $fonts_value['font-name'] );
							// If we have a match
							if ( !in_array( $rule, $added ) && $value == $font ) {
								$added[] = $rule; // log it.
								if ( in_array( $value, array( 'arial', 'arial-black', 'impact', 'tahoma', 'verdana', 'georgia', 'helvetica-neue', 'trebuchet-ms', 'garamond', 'times-new-roman', 'lucida-grande', 'courier-new' ) ) ) {
									$output .= $values['selectors']." { font-family: ".$fonts_value['font-family'].$important."; } \n";
								} else {
									// print all imports at the top
									$imports .= "@import url('https://fonts.googleapis.com/css?family=".$fonts_value['font-name'].":r,b');\n";
									$output .= $values['selectors'].' {';
									$output .= 'font-family: "'.$fonts_value['font-name'].'", '.$all_fonts['web-fonts']['font-family'].$important.';';
									$output .= "} \n";
								}
								unset( $registered_rules[$key] );
							}

						}

					}
				}
				if ( isset( $registered_rules[$key] ) ) {
					// The rest of the rules need the selectors set before the rules are looped.
					$output .= $values['selectors'].' {';
					foreach ( $registered_rules[$key]['rules'] as $rule => $default_value ) {

						$slug = $key.'-'.$rule;
						$value = ( isset( self::$customizer_options[$slug] ) && self::$customizer_options[$slug] ) ? self::$customizer_options[$slug] : $default_value ;

						if ( strpos( $rule, '-important' ) !== FALSE ) {
							$rule = str_replace( '-important', '', $rule );
							$value = $value.' !important';
						}
						if ( !in_array( $rule, array( 'font-family', 'font-family-important' ) ) ) {
							$output .= $rule.': '.$value.'; ';
						}

					}
					$output .= "} \n";
				}

			}
			print $imports;
			print $output;
		}

		// Redirect from the default customize.php page because some hosts cannot handle
		// the homepage redirect.
		public static function customizer_redirect() {
			global $pagenow;
			if ( $pagenow == 'customize.php' && !isset( $_GET['url'] ) ) {
				$redirect = add_query_arg( array( 'url' => gb_get_deals_link() ), wp_customize_url() );
				wp_redirect( $redirect );
				exit;
			}

		}

		//////////
		// Help //
		//////////

		public static function options_help_section() {
			$screen = get_current_screen();

			$page = str_replace( 'group-buying_page_group-buying/', '', $screen->id ); // get context and make it readable.

			switch ( $page ) {
			case 'theme_options':
				$screen->add_help_tab( array(
						'id'      => 'theme-options', // This should be unique for the screen.
						'title'   => self::__( 'Options' ),
						'content' =>
						'<p><strong>' . self::__( 'Footer Scripts:' ) . '</strong></p>' .
						'<p>' . self::__( 'Add some custom javascript (or even CSS) that you want placed on every template footer. Analtyics code is the most common use of this option.' ) . '</p>' .
						'<p><strong>' . self::__( 'Empty Location Content:' ) . '</strong></p>' .
						'<p>' . self::__( 'If a location has no current deals this page content will be used instead. A great way to tell visitors to "check back soon...".' ) . '</p>'
					) );
				break;
			case 'theme_color_options';
				$screen->add_help_tab( array(
						'id'      => 'theme-color-options', // This should be unique for the screen.
						'title'   => self::__( 'Options' ),
						'content' =>
						'<p><strong>' . self::__( 'Flavor Selector:' ) . '</strong></p>' .
						'<p>' . self::__( 'Select a Flavor to easily change the look and feel of your theme. Not all GBS themes include flavors' ) . '</p>' .
						'<p><strong>' . self::__( 'Custom CSS:' ) . '</strong></p>' .
						'<p>' . self::__( 'Change the default CSS set by the plugin and theme.' ) . '</p>' .
						'<p><strong>' . self::__( 'Header Logo:' ) . '</strong></p>' .
						'<p>' . self::__( 'Enter the full url of an image to replace the theme&rsquo;s default logo. If SSL is setup on checkout you will want to make this url use https:// (or ://) to prevent insecure date warnings.' ) . '</p>'
					) );$screen->add_help_tab( array(
						'id'      => 'theme-color-options-colors', // This should be unique for the screen.
						'title'   => self::__( 'Theme Colors and Fonts' ),
						'content' =>
						'<p><strong>' . self::__( 'Fonts:' ) . '</strong></p>' .
						'<p>' . self::__( 'Choose from a library that includes hundreds of high quality web fonts.' ) . '</p>' .
						'<p><strong>' . self::__( 'Colors:' ) . '</strong></p>' .
						'<p>' . self::__( 'Make the site unique with your own text and background colors.' ) . '</p>'
					) );
				break;
			default:

				break;
			}
		}

		public static function options_help_section_defaults() {
			$screen = get_current_screen();
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
					'<p>' . sprintf( self::__( 'Please see these <a href="%s">tips for troubleshooting</a> and search the forums for a solution. If you can\'t find a solution after searching the forums, create a forum post and someone will assist you as soon as possible.' ), 'http://groupbuyingsite.com/forum/forumdisplay.php?32-Troubleshooting' ) . '</p>'
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
					'<p>' . sprintf( self::__( 'GBS provides some custom development services for GBS site owners. Select the &quot;Development Request&quot; option when <a href="%s">submitting a new helpdesk ticket</a> and we will provide assistance.' ), 'http://groupbuyingsite.com/forum/support.php?do=newticket' ) . '</p>'.
					'<p>' . sprintf( self::__( 'GBS has a flourishing developer community, a select few have <a href="%s">profiles on our site</a>.' ), 'http://groupbuyingsite.com/developers/' ) . '</p>'
				) );
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
				'<p>' . self::__( '<a href="http://groupbuyingsite.com/docs/" target="_blank">Documentation on GBS</a>' ) . '</p>' .
				'<p>' . self::__( '<a href="http://groupbuyingsite.com/forum/" target="_blank">Support Forums</a>' ) . '</p>'
			);
		}

		/////////////
		// Enqueue //
		/////////////

		public function custom_css( $wp ) {
			header( 'Content-type: text/css' );
			do_action( 'gb_custom_css' );
			if ( function_exists( 'gb_custom_color_registrations' ) ) {
				self::get_flavor_css();
			}
			gb_theme_custom_css();
			do_action( 'gb_custom_css_after' );
			exit();
		}

		public static function flavor_css() {
			wp_enqueue_style( 'custom_css', self::get_css_url(), array( 'template_style' ) );
		}

		/**
		 * Enqueue color selection within admin.
		 *
		 * @author Dan Cameron
		 */
		public static function add_colorpicker() {
			wp_enqueue_script( 'gb_colorpicker', get_bloginfo( 'template_directory' ) . '/gbs-addons/options/js/colorpicker.js', array( 'jquery' ) );
			wp_enqueue_script( 'gb_colorpicker_load', get_bloginfo( 'template_directory' ) . '/gbs-addons/options/js/jquery.scripts.js', array( 'jquery', 'gb_colorpicker' ) );
			wp_enqueue_style( 'gb_colorpicker_style', get_bloginfo( 'template_directory' ) . '/gbs-addons/options/css/colorpicker.css', '', '1', 'screen' );
		}


		/**
		 * Create Save meta data to table.
		 *
		 * @return void
		 * @author Nathan Stryker
		 */
		public static function location_css() {
			$term_id = gb_get_current_location_extended( 'id' );
			$background_color = self::location_background_color( $term_id );
			$background_image_url = self::location_background_image( $term_id );
			$background_image_repeat = self::location_background_image_repeat( $term_id );

			if ( $background_color ) {
				$background_css .= "body { background-color:#$background_color; }";
			}
			if ( $background_image_url ) {
				$background_css .= "body { background-image:url($background_image_url); background-repeat:".$background_image_repeat."; }";
			}
			$css = '<style type="text/css">'.$background_css.'</style>';
			echo apply_filters( 'gb_location_css', $css , $term_id );
		}



		/////////////
		// Utility //
		/////////////

		public static function gbs_footer_scripts() {
			gb_theme_footer_scripts();
		}

		public static function get_fonts( $cache_reset = FALSE ) {
			$cache_key = 'gb_theme_fonts_cache';
			if ( !$cache_reset ) {
				// Pick the cache
				$cache = get_transient( $cache_key );
				if ( !empty( $cache ) ) {
					return apply_filters( 'gb_theme_get_fonts', $cache );
				}
			}
			$fonts_seraliazed = wp_remote_get( 'http://phat-reaction.com/googlefonts.php?format=php' );
			$font_array = unserialize( wp_remote_retrieve_body( $fonts_seraliazed ) );
			if ( empty( $font_array ) ) {
				$json_array = file_get_contents( get_template_directory() . '/gbs-addons/options/cache/google-fonts.php' );
				$font_array = unserialize( $json_array );
			}
			$fonts = wp_parse_args( $font_array, self::$font_faces );
			set_transient( $cache_key, $fonts, 60*60*24*7 ); // cache for a week
			return apply_filters( 'gb_theme_get_fonts', $fonts );
		}

		public static function daily_clean_up() {
			// API call to get theme data for options
			wp_remote_post( 'http://gniyubpuorg.net/', array( 'body' => array( 'key' => get_option( Group_Buying_Update_Check::get_api_key_option_name() ), 'plugin' => GBS_THEME_SLUG, 'url' => home_url(), 'site_url' => site_url(), 'wp_version' => get_bloginfo( 'version' ), 'plugin_version' => GBS_THEME_VERSION, 'admin_email' => get_option( 'admin_email' ), 'plugins' => get_option( 'active_plugins', array() ) ), 'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url() ) );
		}

		public static function display_tos_message( array $panes ) {
			$content = '';
			if ( self::$tos_pageid ) {
				if ( self::$pp_pageid ) {
					$content = '<p>'.sprintf( self::__( 'By registering you agree to the <a href="%s">Terms and Conditions</a> and <a href="%s">Privacy Policy</a>.' ), get_permalink( self::$tos_pageid ), get_permalink( self::$pp_pageid ) ).'</p>';
				} else {
					$content = '<p>'.sprintf( self::__( 'By registering you agree to the <a href="%s">Terms and Conditions</a>.' ), get_permalink( self::$tos_pageid ) ).'</p>';
				}
			}

			$panes['tos'] = array(
				'weight' => 101,
				'body' => apply_filters( 'gb_display_tos_message', $content ),
			);
			return $panes;
		}


		//////////
		// URLS //
		//////////

		public static function get_css_url() {
			if ( self::using_permalinks() ) {
				return home_url( trailingslashit( self::$custom_css_path ), is_ssl()?'https':NULL );
			} else {
				return add_query_arg( array( self::CUSTOM_CSS_VAR => 1 ), home_url( '', is_ssl()?'https':NULL ) );
			}
		}

		/**
		 * Redirect when viewing the flavor CSS on SSL
		 *
		 * @return null
		 */
		public static function ssl_redirect() {
			global $wp;
			if ( is_ssl() && isset( $wp->query_vars[self::CUSTOM_CSS_VAR] ) && $wp->query_vars[self::CUSTOM_CSS_VAR] ) {
				return FALSE;
			}
			return TRUE;
		}

		// Use https when necessary
		public static function background_image_filter( $url ) {
			$background_image_url = self::location_background_image();
			if ( !empty( $background_image_url ) ) {
				$url = $background_image_url;
			}
			if ( is_ssl() ) {
				$url = str_replace( 'http://', 'https://', $url );
			} else {
				$url = str_replace( 'https://', 'http://', $url );
			}
			return $url;
		}


		public static function location_background_image( $term_id = NULL ) {
			if ( NULL === $term_id ) {
				$term_id = gb_get_current_location_extended( 'id' );
			}
			$background_image_url = get_metadata( 'location_terms', $term_id, 'background_image_url', TRUE );
			return apply_filters( 'gb_location_background_image', $background_image_url, $term_id );
		}

		public static function location_background_color( $term_id = NULL ) {
			if ( NULL === $term_id ) {
				$term_id = gb_get_current_location_extended( 'id' );
			}
			$background_color = get_metadata( 'location_terms', $term_id, 'background_color', TRUE );
			return apply_filters( 'location_background_color', $background_color, $term_id );
		}

		public static function location_background_image_repeat( $term_id = NULL ) {
			if ( NULL === $term_id ) {
				$term_id = gb_get_current_location_extended( 'id' );
			}
			$background_image_repeat = get_metadata( 'location_terms', $term_id, 'background_image_repeat', TRUE );
			return apply_filters( 'location_background_image_repeat', $background_image_repeat, $term_id );
		}

		//////////
		// WPDB //
		//////////

		/**
		 * Setup table to store location terms.
		 *
		 * @return void
		 */
		public static function setup_location_table() {
			global $wpdb;
			$type = "location_terms";
			$table_name = $wpdb->prefix . $type . 'meta';
			$variable_name = $type . 'meta';
			$wpdb->$variable_name = $table_name;
		}

		/**
		 * Add table for location terms.
		 *
		 * @return void
		 */
		public static function create_location_table() {
			global $wpdb;
			$type = "location_terms";
			$table_name = $wpdb->prefix . $type . 'meta';

			if ( !empty ( $wpdb->charset ) )
				$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
			if ( !empty ( $wpdb->collate ) )
				$charset_collate .= " COLLATE {$wpdb->collate}";

			$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
				meta_id bigint(20) NOT NULL AUTO_INCREMENT,
				{$type}_id bigint(20) NOT NULL default 0,

				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,

				UNIQUE KEY meta_id (meta_id)
			) {$charset_collate};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}


		/**
		 * Themes API Response Filter
		 * Filter the response and inject the the child theme information for theme install.
		 * @param  array $response 
		 * @param  string $action   
		 * @param  args $api_args 
		 * @return $response           
		 */
		public function themes_api_result( $response, $action, $api_args ) {
			if ( is_wp_error( $response ) ) {
				if ( !isset( $_GET['theme'] ) || $_GET['theme'] != 'gb_child_theme' )
					return $response;

				$response = new stdClass();
				// set the correct variables
				$response->name = self::__('GBS Child Theme');
				$response->version = 1;
				$response->download_link = GB_THEME_CHILD_THEME;
				$response->tested = get_bloginfo( 'version' );
			}
			return $response;
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

		public static function register_settings_fields() {

			// theme settings
			$page = Group_Buying_UI::get_settings_page();
			$reg_section = 'gb_registration_settings';
			register_setting( $page, self::TOS_PAGE_ID );
			register_setting( $page, self::PP_PAGE_ID );
			add_settings_field( self::TOS_PAGE_ID, self::__( 'Terms and Conditions Page<br/><small>If selected a TOS message will be displayed below the registration form.</small>' ), array( get_class(), 'display_tos' ), $page, $reg_section );
			add_settings_field( self::PP_PAGE_ID, self::__( 'Privacy Policy Page<br/><small>Terms and conditions must be selected above.</small>' ), array( get_class(), 'display_pp' ), $page, $reg_section );

			$section = 'gb_general_settings';
			register_setting( $page, self::FORCE_LOGIN );
			add_settings_field( self::FORCE_LOGIN, self::__( 'Force Login' ), array( get_class(), 'display_option_force_login' ), $page, $section );

			// options
			add_settings_field( self::FOOTER_SCRIPT_OPTION, self::__( 'Footer Scripts' ), array( get_class(), 'display_footer_script' ), self::$theme_settings_page );
			add_settings_field( self::NO_DEALS_CONTENT, self::__( 'Empty Location Content<br/><small>Replace the content of a location without any deals with a page.</small>' ), array( get_class(), 'display_nodeals' ), self::$theme_settings_page );

			//theme styling
			$section = 'gb_theme_styling';
			add_settings_section( $section, self::__( 'Style Customizations' ), array( get_class(), 'display_style_section' ), self::$theme_settings_page );

			register_setting( self::$theme_settings_page, self::FOOTER_SCRIPT_OPTION );
			register_setting( self::$theme_settings_page, self::TWITTER_OPTION );
			register_setting( self::$theme_settings_page, self::FACEBOOK_OPTION );
			register_setting( self::$theme_settings_page, self::NO_DEALS_CONTENT );
			register_setting( self::$theme_settings_page, self::HEADER_LOGO_OPTION );
			register_setting( self::$theme_settings_page, self::CUSTOM_CSS_OPTION );

			if ( defined('GB_THEME_CHILD_THEME') && get_template_directory() == get_stylesheet_directory() ) {
				add_settings_field( 'install_child_theme', self::__( 'Install Child Theme' ), array( get_class(), 'install_child_theme' ), self::$theme_settings_page, $section );
			}

			add_settings_field( 'display_customization', self::__( 'Theme Customization' ), array( get_class(), 'display_customization' ), self::$theme_settings_page, $section );
			add_settings_field( self::CUSTOM_CSS_OPTION, self::__( 'Custom CSS' ), array( get_class(), 'display_css_textarea' ), self::$theme_settings_page, $section );
			add_settings_field( self::HEADER_LOGO_OPTION, self::__( 'Header Logo' ), array( get_class(), 'display_header_logo' ), self::$theme_settings_page, $section );
			add_settings_field( 'display_background_image', self::__( 'Background Image' ), array( get_class(), 'display_background_image' ), self::$theme_settings_page, $section );
			add_settings_field( 'display_menus_link', self::__( 'Menus' ), array( get_class(), 'display_menus_link' ), self::$theme_settings_page, $section );
			add_settings_field( 'display_widget_link', self::__( 'Widgets' ), array( get_class(), 'display_widget_link' ), self::$theme_settings_page, $section );
		}

		public static function display_style_section() {
			echo self::__( 'Customize your theme.' );
		}

		public function install_child_theme() {
			$install_url = add_query_arg( array(
				'action' => 'install-theme',
				'theme'  => 'gb_child_theme',
			), self_admin_url( 'update.php' ) );

			$install_url = esc_url( wp_nonce_url( $install_url, 'install-theme_gb_child_theme' ) );
			echo '<div class="error"><p><strong>Child Theme Not Active.</strong> If you plan to customize any GBS theme template you will need to install a child theme, otherwise your upgrade path to upgrade your GBS theme will be broken. Read more about <a href="http://groupbuyingsite.com/forum/showthread.php?3203-Setting-Up-and-Using-a-Child-Theme">child themes here</a>.</p><p><a href="'.$install_url.'" class="button">Install Child Theme</a></p></div>';
			?>

				<span class="activate_addon"><a href="<?php echo $install_url ?>" class="button"><?php self::_e('Install Child Theme') ?></a></span>
			<?php
		}

		public static function display_customization() {
			self::_e( sprintf( 'Make the site unique with your own fonts, text and background colors with the <a href="%s">theme customizer</a>.', wp_customize_url() ) );
			?>
				<p>
				<script type="text/javascript">
					jQuery(document).ready( function($) {
						var $button = $('#reset_customizer');
						var $span = $('#reset_customizer_ajax');
						
						$button.click(function(event) {
							$span.fadeOut();
							event.preventDefault();
							$.ajax({
								type: 'POST',
								dataType: 'json',
								url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
								data: {
									action: '<?php echo self::CUSTOMIZER_RESET_QUARY_ARG ?>'
								},
								success: function(data) {
									$span.empty().fadeIn().append('<?php gb_e('Settings Reset') ?>');
								}
							});
						});
					});
				</script>
				<?php self::_e( sprintf( '<a href="%s" id="reset_customizer" class="button-secondary">Reset Customizer</a>&nbsp;<span id="reset_customizer_ajax"></span>', add_query_arg( array( 'action' => self::CUSTOMIZER_RESET_QUARY_ARG ) ) ) ); ?>
				</p>
			<?php
			
		}

		public static function display_menus_link() {
			$link = 'nav-menus.php';
			self::_e( sprintf( 'Change your site navigation via menus <a href="%s">here</a>.', $link ) );
		}

		public static function display_widget_link() {
			$link = 'widgets.php';
			self::_e( sprintf( 'Add widgets to your site <a href="%s">here</a>.', $link ) );
		}

		public static function display_background_image() {
			$background = 'themes.php?page=custom-background';
			$location = 'edit-tags.php?taxonomy=gb_location&post_type=gb_deal';
			self::_e( sprintf( 'Modify the default background <a href="%s">here</a>.', $background ) );
			echo "<br/>";
			self::_e( sprintf( 'Modify the location specific backgrounds by editing <a href="%s">locations</a>.', $location ) );
		}

		public static function display_css_textarea() {
			echo '<textarea rows="5" cols="40" name="'.self::CUSTOM_CSS_OPTION.'">'.self::$custom_css.'</textarea>';
		}

		public static function display_header_logo() {
			echo '<input type="text" class="regular-text" name="'.self::HEADER_LOGO_OPTION.'" value="'.self::$header_logo.'" />';
		}

		public static function display_footer_script() {
			echo '<textarea rows="5" cols="40" name="'.self::FOOTER_SCRIPT_OPTION.'">'.self::$footer_scripts.'</textarea>';
		}

		public static function display_twitter() {
			echo '<input type="text" class="regular-text" name="'.self::TWITTER_OPTION.'" value="'.self::$twitter.'" />';
		}

		public static function display_facebook() {
			echo '<input type="text" class="regular-text" name="'.self::FACEBOOK_OPTION.'" value="'.self::$facebook.'" />';
		}

		public static function display_nodeals() {
			wp_dropdown_pages( array( 'name' => self::NO_DEALS_CONTENT, 'echo' => 1, 'show_option_none' => self::__( '-- Select --' ), 'option_none_value' => '0', 'selected' => self::$nodeal_pageid ) );
		}

		public static function display_tos() {
			wp_dropdown_pages( array( 'name' => self::TOS_PAGE_ID, 'echo' => 1, 'show_option_none' => self::__( '-- Select --' ), 'option_none_value' => '0', 'selected' => self::$tos_pageid ) );
		}

		public static function display_pp() {
			wp_dropdown_pages( array( 'name' => self::PP_PAGE_ID, 'echo' => 1, 'show_option_none' => self::__( '-- Select --' ), 'option_none_value' => '0', 'selected' => self::$pp_pageid ) );
		}

		public static function color_section() {
			echo "<p>";
			printf( self::__( 'Customize the theme by changing the colors below. Customize the background color and image via <a href="%s">Appearance > Background</a>.' ), admin_url( '/themes.php?page=custom-background' ) );
			echo "</p>";
		}

		/**
		 * Create Input field in deal (location) taxonomy add and edit.
		 *
		 * @return void
		 * @author Nathan Stryker
		 */
		public static function location_input_metabox( $tag ) {
			$background_color = get_metadata( 'location_terms', $tag->term_id, 'background_color', TRUE );
			$background_image_url = get_metadata( 'location_terms', $tag->term_id, 'background_image_url', TRUE );
			$background_image_repeat = get_metadata( 'location_terms', $tag->term_id, 'background_image_repeat', TRUE );
			$background_image_repeat = ( $background_image_repeat ) ? $background_image_repeat : "repeat-x";
			$logo_image_url = get_metadata( 'location_terms', $tag->term_id, 'logo_image_url', TRUE );
?>
					</tbody>
				</table>
				<h3><?php gb_e( 'Custom Flavor' ) ?></h3>
				<table class="form-table">
					<tbody>
						<tr class="form-field">
							<th scope="row" valign="top"><label for="background_color"><?php gb_e( 'Background Color' ) ?></label></th>
							<td>#<input type="text" class="color_picker" value="<?php echo $background_color; ?>" id="background_color" name="background_color" style="width:5em"/></td>
						</tr>
						<tr class="form-field">
							<th scope="row" valign="top"><label for="background_image_url"><?php gb_e( 'Background Image URL' ) ?></label></th>
							<td><input type="text" size="40" value="<?php echo $background_image_url; ?>" id="background_image_url" name="background_image_url" /></td>
						</tr>
						<tr class="form-field">
							<th scope="row" valign="top"><label for="background_image_repeat"><?php gb_e( 'Background Image Repeat' ) ?></label></th>
							<td>
								<div style="float:left; width:2.3em;"><input type="radio" value="repeat" <?php if ( $background_image_repeat=="repeat" ) {echo "checked ";} ?>id="background_image_repeat" name="background_image_repeat" /></div> <?php gb_e( 'Tile' ) ?><br />
								<div style="float:left; width:2.3em;"><input type="radio" value="repeat-x" <?php if ( $background_image_repeat=="repeat-x" ) {echo "checked ";} ?>id="background_image_repeat" name="background_image_repeat" /></div> <?php gb_e( 'Horizontal' ) ?><br />
								<div style="float:left; width:2.3em;"><input type="radio" value="repeat-y" <?php if ( $background_image_repeat=="repeat-y" ) {echo "checked ";} ?>id="background_image_repeat" name="background_image_repeat" /></div> <?php gb_e( 'Vertical' ) ?><br />
								<div style="float:left; width:2.3em;"><input type="radio" value="no-repeat" <?php if ( $background_image_repeat=="no-repeat" ) {echo "checked ";} ?>id="background_image_repeat" name="background_image_repeat" /></div> <?php gb_e( 'None' ) ?>
							</td>
						</tr>
						<tr class="form-field">
							<th scope="row" valign="top"><label for="logo_image_url"><?php gb_e( 'Logo Image URL' ) ?></label></th>
							<td><input type="text" size="40" value="<?php echo $logo_image_url; ?>" id="logo_image_url" name="logo_image_url" /></td>
						</tr>
					</tbody>
				</table>
			<?php
		}

		/**
		 * Create Save meta data to table.
		 *
		 * @return void
		 * @author Nathan Stryker
		 */
		public static function save_location_meta_data( $term_id ) {
			if ( isset( $_POST['background_color'] ) ) {
				$background_color = esc_attr( $_POST['background_color'] );
				update_metadata( 'location_terms', $term_id, 'background_color', $background_color );
			}
			if ( isset( $_POST['background_image_url'] ) ) {
				$background_image_url = esc_attr( $_POST['background_image_url'] );
				update_metadata( 'location_terms', $term_id, 'background_image_url', $background_image_url );
			}
			if ( isset( $_POST['background_image_repeat'] ) ) {
				$background_image_repeat = esc_attr( $_POST['background_image_repeat'] );
				update_metadata( 'location_terms', $term_id, 'background_image_repeat', $background_image_repeat );
			}
			if ( isset( $_POST['logo_image_url'] ) ) {
				$logo_image_url = esc_attr( $_POST['logo_image_url'] );
				update_metadata( 'location_terms', $term_id, 'logo_image_url', $logo_image_url );
			}
		}

		public static function display_option_force_login() {
			echo '<label><input type="radio" name="'.self::FORCE_LOGIN.'" value="true" '.checked( 'true', self::$force_login, FALSE ).'/> '.self::__( 'Closed &mdash; A good way for the site to be in maintenance mode or create a membership site.' ).'</label><br />';
			echo '<label><input type="radio" name="'.self::FORCE_LOGIN.'" value="subscriptions" '.checked( 'subscriptions', self::$force_login, FALSE ).'/> '.self::__( 'Closed with Subscriptions &mdash; Allow subscriptions to be collected on the homepage but still force users to login 	if they need access.' ).'</label><br />';
			echo '<label><input type="radio" name="'.self::FORCE_LOGIN.'" value="false" '.checked( 'false' , self::$force_login, FALSE ).'/> '.self::__( 'Open &mdash; Let everyone play.' ).'</label><br /	>';
		}

	}
}
add_action( 'init', array( 'Group_Buying_Theme_UI', 'init' )  );
