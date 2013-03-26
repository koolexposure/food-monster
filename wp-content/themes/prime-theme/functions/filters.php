<?php

/////////////
// Options //
/////////////
 
/**
 * Custom colors array
 * @return array
 */
function gb_custom_color_registrations() {
	$colors = array(
		'gb_rt_ff' => array(
			'selectors' => '.gb_ff, h1, h2, h3, h4, h5',
			'name' => gb__( 'Headings Font Face' ),
			'rules' => array(
				'font-family' => 'arvo',
			)
		),
		'body_rt_ff' => array(
			'selectors' => 'body, .body',
			'name' => gb__( 'Body/Default Font Face' ),
			'rules' => array(
				'font-family' => 'helvetica-neue',
			)
		),
		'body' => array(
			'selectors' => 'body',
			'name' => gb__( 'Site Body' ),
			'rules' => array(
				'background-color' => '60B6C1',
				'color' => '555555',
			)
		),
		'alt_text' => array(
			'selectors' => '.alt_text, a.alt_text',
			'name' => gb__( 'Alt Text Color' ),
			'rules' => array(
				'color' => '864814',
			)
		),
		'gb_rt' => array(
			'selectors' => 'h1, h2, h3, h4, h5',
			'name' => gb__( 'Headings' ),
			'rules' => array(
				'color' => 'f6892f',
			)
		),
		'header' => array(
			'selectors' => '#header_wrap, #footer_wrap, .header_color, .deal_block, .current_balance, .filter_biz',
			'name' => gb__( 'Header and Footer Areas' ),
			'rules' => array(
				'background-color' => 'f3f3f3',
				'color' => '555555',
			)
		),
		'prime' => array(
			'selectors' => '#container, .prime, tr.cart-line-item td.cart-line-item-total, #content .tabs li.ui-state-active a',
			'name' => gb__( 'Main Areas' ),
			'rules' => array(
				'background-color' => 'ffffff',
				'color' => '555555',
			)
		),
		'contrast' => array(
			'selectors' => '.contrast, #content td.cart-line-item-total, .milestone_pricing li, .project_thumbnail, .no_featured_image, .contrast a, .contrast a:hover, a.contrast, #main_navigation .right_menu li a, #main_navigation .right_menu li a:hover, #locations_header_wrap li a:hover, .contrast h2, #navigation ul.sub-menu',
			'name' => gb__( 'Contrast Areas' ),
			'rules' => array(
				'background-color' => '438088',
				'color' => 'ffffff',
			)
		),
		'contrast_light' => array(
			'selectors' => '.contrast_light, #content .tabs li a',
			'name' => gb__( 'Light Contrast Areas' ),
			'rules' => array(
				'background-color' => 'a6d6dc',
				'color' => '27494e',
			)
		),
		'alt_background' => array(
			'selectors' => '.background_alt, .account_sidebar li.current_page a, .wp-caption, .low_contrast_button, .form-submit.checkout_next_step',
			'name' => gb__( 'Secondary Areas' ),
			'rules' => array(
				'background-color' => 'f6892f',
				'color' => 'ffffff',
			)
		),
		'widgets' => array(
			'selectors' => '.widget',
			'name' => gb__( 'Widgets Areas' ),
			'rules' => array(
				'color' => '777777',
			)
		),
		'navigation' => array(
			'selectors' => '#navigation, #main_navigation li a, #navigation ul.sub-menu li a:hover, .account_sidebar a:hover',
			'name' => gb__( 'Navigation' ),
			'rules' => array(
				'background-color' => 'ff8400',
				'color' => 'ffffff',
			)
		),
		'navigation_hover' => array(
			'selectors' => 'h2.section_heading, legend.section_heading, .section_heading a, .table_heading',
			'name' => gb__( 'Navigation Hover' ),
			'rules' => array(
				'background-color' => 'ff8400',
				'color' => '864814',
			)
		),
		'corners' => array(
			'selectors' => '.prime',
			'name' => gb__( 'Main Rounded Corners' ),
			'rules' => array(
				'border-radius' => '0.8em',
			)
		),
		'btn-corners' => array(
			'selectors' => '.button, .alt_button, input.form-submit, input.button-primary, .cart-claimed a, .contrast a.button, .contrast a.button:hover, .deal_thumbnail img, .deal_meta_wrapper, #deal_countdown, .purchase_info, #milestone_wrap, .loop_thumb, .button_price, .buy_button a span, .current_balance, .account_sidebar a, .current_balance_amount, #locations_header_wrap li a, .biz_content, #deals_loop .post, .dash_section, .merchant_logo',
			'name' => gb__( 'Small Rounded Corners' ),
			'rules' => array(
				'border-radius' => '0.4em',
			)
		),
		'link' => array(
			'selectors' => 'a, .link, .merchant-title a',
			'name' => gb__( 'Link Color' ),
			'rules' => array(
				'color' => 'f6892f',
			)
		),
		'link_hover' => array(
			'selectors' => 'a:hover, .link:hover, .merchant-title a:hover',
			'name' => gb__( 'Link Color Hover' ),
			'rules' => array(
				'color' => 'f6892f',
			)
		),
		'alt_link' => array(
			'selectors' => '.alt_link, .alt_link:hover, .header-locations-drop-link, .header-locations-drop-link a, .header-locations-drop-link a:hover, .deal_block a, .deal_block a:hover, .deal_merchant_title a, .deal_merchant_title a:hover',
			'name' => gb__( 'Alternate Link Color' ),
			'rules' => array(
				'color' => '666666',
			)
		),
		'button' => array(
			'selectors' => '.button, input.form-submit, input.button-primary, .cart-claimed a, .contrast a.button, .contrast a.button:hover, .deal_block a.button',
			'name' => gb__( 'Buttons' ),
			'rules' => array(
				'background-color' => '4d9faa',
				'color' => 'ffffff',
			)
		),
		'button_hover' => array(
			'selectors' => '.button:hover, input.form-submit:hover, input.button-primary:hover, .cart-claimed a:hover, .deal_block a.button:hover',
			'name' => gb__( 'Buttons Hover' ),
			'rules' => array(
				'background-color' => '4d9faa',
				'color' => 'ffffff',
			)
		),
		'alt_button' => array(
			'selectors' => '.alt_button, thead',
			'name' => gb__( 'Alternate Buttons' ),
			'rules' => array(
				'background-color' => 'e4e4e4',
				'color' => '888888',
			)
		),
		'alt_button_hover' => array(
			'selectors' => '.alt_button:hover',
			'name' => gb__( 'Alternate Buttons Hover' ),
			'rules' => array(
				'background-color' => 'e4e4e4',
				'color' => '646464',
			)
		),
		'alt_button_a' => array(
			'selectors' => '.alt_button a',
			'name' => gb__( 'Alternate Button Link Color' ),
			'rules' => array(
				'color' => '888888',
			)
		),
		'alt_button_a_hover' => array(
			'selectors' => '.alt_button a:hover',
			'name' => gb__( 'Alternate Button Link Color Hover' ),
			'rules' => array(
				'color' => '646464',
			)
		),
		'input' => array(
			'selectors' => 'input[type="text"], input[type="password"], textarea',
			'name' => gb__( 'Input Fields' ),
			'rules' => array(
				'background-color' => 'eeeeee',
				'color' => '000000',
			)
		),

		// Font Sizes
		'font_medium' => array(
			'selectors' => '.font_medium, body, .add-to-cart option',
			'name' => gb__( 'Medium/Default Font Size' ),
			'rules' => array(
				'font-size' => '1em',
			)
		),
		'font_xx_small' => array(
			'selectors' => '.font_xx_small',
			'name' => gb__( 'XX Small Font Size' ),
			'rules' => array(
				'font-size' => '0.4em',
			)
		),
		'font_x_small' => array(
			'selectors' => '.font_x_small, .countdown_section',
			'name' => gb__( 'X Small Font Size' ),
			'rules' => array(
				'font-size' => '0.6em',
			)
		),
		'font_small' => array(
			'selectors' => '.font_small, .widget_info',
			'name' => gb__( 'Small Font Size' ),
			'rules' => array(
				'font-size' => '0.8em',
			)
		),
		'font_large' => array(
			'selectors' => '.font_large',
			'name' => gb__( 'Large Font Size' ),
			'rules' => array(
				'font-size' => '1.5em',
			)
		),
		'font_x_large' => array(
			'selectors' => '.font_x_large',
			'name' => gb__( 'X Large Font Size' ),
			'rules' => array(
				'font-size' => '2em',
			)
		),
		'font_xx_large' => array(
			'selectors' => '.font_xx_large, #deal_countdown .countdown_amount',
			'name' => gb__( 'XX Large Font Size' ),
			'rules' => array(
				'font-size' => '3em',
			)
		),
	);
	return apply_filters( 'gb_platinum_flavor_options', $colors );
}

/**
 * Removes the wrapper div from wp_page_menu, since wp_nav_menu can call this if there's no menu
 * @return string
 */
add_filter( 'wp_page_menu', 'remove_container', 10, 2 );
function remove_container( $menu, $args ) {
	$menu = strip_tags( $menu, '<li><a>' );
	return '<ul class="'.$args['menu_class'].'">'.$menu.'</ul>';
}