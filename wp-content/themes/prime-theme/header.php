<?php do_action( 'pre_gbs_head' ) ?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
	<title>
	    <?php
		global $page, $paged;

		wp_title( '>', true, 'right' );

		// Add the blog name.
		bloginfo( 'name' );

		// Add the blog description for the home/front page.
		$site_description = get_bloginfo( 'description', 'display' );
		if ( $site_description && ( is_home() || is_front_page() ) )
			echo " > $site_description";

		// Add a page number if necessary:
		if ( $paged >= 2 || $page >= 2 )
			echo ' ? ' . sprintf( gb__( 'Page %s' ), max( $paged, $page ) ); 

		?>
    </title>

	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<!--[if lt IE 7]>
	<script src="//ie7-js.googlecode.com/svn/version/2.1(beta4)/IE7.js"></script>
	<![endif]-->
	<!--[if lt IE 8]>
	<script src="//ie7-js.googlecode.com/svn/version/2.1(beta4)/IE8.js"></script>
	<![endif]-->

	<?php wp_head(); ?>
	
</head>

<body <?php body_class(); ?>>

	<?php
		if ( is_home() || is_front_page() ) {
			get_template_part( 'inc/home-navigation', 'header' );
		} else {
			get_template_part( 'inc/navigation', 'header' );
		} ?>

	<div id="message_banner" class="container background_alt cloak">
		<?php gb_display_messages(); ?>
	</div>

	<div id="wrapper" class="clearfix">
