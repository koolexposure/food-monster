<?php


add_action( 'widgets_init', 'groupbuying_crowdfunding_sidebar_init' );
function groupbuying_crowdfunding_sidebar_init() {
	register_sidebar(
		array(
			'name' => 'Footer Widget Area ( column 1)',
			'id'            => 'deal_footer_one',
			'description'   => 'Used for the first column of the footer.',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '<div class="clear"></div></div>',
			'before_title' => '<h2 class="widget-title gb_ff">',
			'after_title' => '</h2>'
		)
	);
	register_sidebar(
		array(
			'name' => 'Footer Widget Area ( column 2 )',
			'id'            => 'deal_footer_two',
			'description'   => 'Used for the second column of the footer.',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '<div class="clear"></div></div>',
			'before_title' => '<h2 class="widget-title gb_ff">',
			'after_title' => '</h2>'
		)
	);
	register_sidebar(
		array(
			'name' => 'Footer Widget Area ( column 3 )',
			'id'            => 'deal_footer_three',
			'description'   => 'Used for the third column of the footer.',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '<div class="clear"></div></div>',
			'before_title' => '<h2 class="widget-title gb_ff">',
			'after_title' => '</h2>'
		)
	);
	register_sidebar(
		array(
			'name' => 'Cart Sidebar',
			'id'            => 'cart-sidebar',
			'description'   => 'Used on the shopping cart page.',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '<div class="clear"></div></div>',
			'before_title' => '<h2 class="widget-title gb_ff">',
			'after_title' => '</h2>'
		)
	);
	register_sidebar(
		array(
			'name' => 'Deal Sidebar',
			'id'            => 'deal-sidebar',
			'description'   => 'Used on the single deal page',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '<div class="clear"></div></div>',
			'before_title' => '<h2 class="widget-title gb_ff">',
			'after_title' => '</h2>'
		)
	);
	register_sidebar(
		array(
			'name' => 'Deals Sidebar',
			'id'            => 'deals-sidebar',
			'description'   => 'Used on the deals index pages',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '<div class="clear"></div></div>',
			'before_title' => '<h2 class="widget-title gb_ff">',
			'after_title' => '</h2>'
		)
	);
	register_sidebar(
		array(
			'name' => 'Blog Sidebar',
			'id'            => 'blog-sidebar',
			'description'   => 'Used on the order/purchase page and order lookup page',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '<div class="clear"></div></div>',
			'before_title' => '<h2 class="widget-title gb_ff">',
			'after_title' => '</h2>'
		)
	);
	register_sidebar(
		array(
			'name' => 'Page Sidebar',
			'id'            => 'page-sidebar',
			'description'   => 'Used on all pages',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '<div class="clear"></div></div>',
			'before_title' => '<h2 class="widget-title gb_ff">',
			'after_title' => '</h2>'
		)
	);
	register_sidebar(
		array(
			'name' => 'Merchants Sidebar',
			'id'            => 'merchant-sidebar',
			'description'   => 'Used on all merchant directory pages',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '<div class="clear"></div></div>',
			'before_title' => '<h2 class="widget-title gb_ff">',
			'after_title' => '</h2>'
		)
	);
	register_sidebar(
		array(
			'name' => 'Account Sidebar',
			'id'            => 'account-sidebar',
			'description'   => 'Used on all user account pages',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '<div class="clear"></div></div>',
			'before_title' => '<h2 class="widget-title gb_ff">',
			'after_title' => '</h2>'
		)
	);
	register_sidebar(
		array(
			'name' => 'Order Sidebar',
			'id'            => 'order-sidebar',
			'description'   => 'Used on all user order pages',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '<div class="clear"></div></div>',
			'before_title' => '<h2 class="widget-title gb_ff">',
			'after_title' => '</h2>'
		)
	);
}
