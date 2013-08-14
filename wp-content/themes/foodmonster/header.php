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
 <link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_directory'); ?>/css/supersized.css" media="screen" />
 <link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_directory'); ?>/css/flexslider.css" media="screen" />

	<?php wp_head(); ?>

 <link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_directory'); ?>/style-media-queries.css" media="screen" />
<script type="text/javascript" src="<?php bloginfo('stylesheet_directory'); ?>/js/template.js"></script>
<script type="text/javascript" src="<?php bloginfo('stylesheet_directory'); ?>/js/jquery.flexslider-min.js"></script>
<script type="text/javascript" src="<?php bloginfo('stylesheet_directory'); ?>/js/jquery.roundabout.min.js"></script>
<script type="text/javascript" src="<?php bloginfo('stylesheet_directory'); ?>/js/jquery.transit.min.js"></script>


<script type="text/javascript">
jQuery(function ($) {
    $(window).load(function () {
        $('.flexslider').flexslider({
            animation: "slide"
        });
    });
});
</script>

<script type="text/javascript">
jQuery(function ($) {
    $('#list').click(function(){    
        $('.loop_deal_grid, .loop_deal').animate({opacity:0},function(){
            $('.grid').removeClass('grid-active');
            $('.rotate').removeClass('rotate-active');
            $('.list').addClass('list-active');
            $('.loop_deal_grid, .loop_deal').attr('class', 'loop_deal-list shadow');
            $('ul').removeClass('roundabout-holder');
            $('li').removeClass('roundabout-moveable-item');
            $('li').removeClass('roundabout-in-focus');
            $('li').removeAttr("style");
            $('ul').removeAttr("style");
            $('.loop_deal-list').stop().animate({opacity:1});
        });
    });

    $('#grid').click(function(){  
        $('.loop_deal-list, .loop_deal').animate({opacity:0},function(){
            $('.list').removeClass('list-active');
            $('.rotate').removeClass('rotate-active');
            $('.grid').addClass('grid-active');
            $('.loop_deal-list, .loop_deal').attr('class', 'loop_deal_grid shadow');
            $('ul').removeClass('roundabout-holder');
            $('li').removeClass('roundabout-moveable-item');
            $('li').removeClass('roundabout-in-focus');
             $('li').removeAttr("style");
            $('ul').removeAttr("style");
            $('.loop_deal_grid').stop().animate({opacity:1});
        });
    });
    $('#showcase ul').roundabout({ tilt: -11 });
	$('.rotate').addClass('rotate-active');
     $('#rotate').click(function(){  
        $('.loop_deal-list,.loop_deal_grid').animate({opacity:0},function(){
            $('.list').removeClass('list-active');
            $('.grid').removeClass('grid-active');
			$('.rotate').addClass('rotate-active');
               $('.loop_deal-list,.loop_deal_grid').attr('class', 'loop_deal shadow');
             $('#showcase ul').roundabout({ tilt: -11 });
              $('.loop_deal').stop().animate({opacity:1});
             
        });
    });
});
</script>

	 <?php if ( is_home() ) {?>
		<script type="text/javascript" src="<?php bloginfo('stylesheet_directory'); ?>/js/supersized.3.1.3.min.js"></script>
	
	<script type="text/javascript">  

		jQuery(function($){
			$.supersized({

						//Functionality
						slideshow               :   1,		//Slideshow on/off
						autoplay				:	1,		//Slideshow starts playing automatically
						start_slide             :   1,		//Start slide (0 is random)
						random					: 	0,		//Randomize slide order (Ignores start slide)
						slide_interval          :   7000,	//Length between transitions
						transition              :   3, 		//0-None, 1-Fade, 2-Slide Top, 3-Slide Right, 4-Slide Bottom, 5-Slide Left, 6-Carousel Right, 7-Carousel Left
						transition_speed		:	1100,	//Speed of transition
						new_window				:	1,		//Image links open in new window/tab
						pause_hover             :   0,		//Pause slideshow on hover
						keyboard_nav            :   1,		//Keyboard navigation on/off
						performance				:	1,		//0-Normal, 1-Hybrid speed/quality, 2-Optimizes image quality, 3-Optimizes transition speed // (Only works for Firefox/IE, not Webkit)
						image_protect			:	1,		//Disables image dragging and right click with Javascript

						//Size & Position
						min_width		        :   0,		//Min width allowed (in pixels)
						min_height		        :   0,		//Min height allowed (in pixels)
						vertical_center         :   1,		//Vertically center background
						horizontal_center       :   1,		//Horizontally center background
						fit_portrait         	:   1,		//Portrait images will not exceed browser height
						fit_landscape			:   0,		//Landscape images will not exceed browser width

						//Components
						navigation              :   1,		//Slideshow controls on/off
						thumbnail_navigation    :   1,		//Thumbnail navigation
						slide_counter           :   1,		//Display slide numbers
						slide_captions          :   1,		//Slide caption (Pull from "title" in slides array)
						slides 					:  	[		//Slideshow Images

	/* 					{image : '<?php bloginfo('stylesheet_directory'); ?>/slides/1.jpg'},
						{image : '<?php bloginfo('stylesheet_directory'); ?>/slides/2.jpg'},							
						{image : '<?php bloginfo('stylesheet_directory'); ?>/slides/3.jpg'},	
						{image : '<?php bloginfo('stylesheet_directory'); ?>/slides/4.jpg'},
						{image : '<?php bloginfo('stylesheet_directory'); ?>/slides/5.jpg'},							
						{image : '<?php bloginfo('stylesheet_directory'); ?>/slides/6.jpg'},	
						{image : '<?php bloginfo('stylesheet_directory'); ?>/slides/7.jpg'},
						{image : '<?php bloginfo('stylesheet_directory'); ?>/slides/8.jpg'},							
						{image : '<?php bloginfo('stylesheet_directory'); ?>/slides/9.jpg'} 

*/	

	<?php 
	// The Query
	 query_posts( 'post_type=slides&posts_per_page=-1&orderby=' );
	$i=0; 
	while ( have_posts() ) : the_post();
	$simg=get_post_meta($post->ID, 'wtf_slide', true);
	if ($i > 0) : echo ','; else: echo ''; endif; //For IE sake add a coma BEFORE every image offsetting the first one.
	echo "{image : '".$simg."'}"; 
	$i++; 
	endwhile;
	wp_reset_query();
	 ?>	
	
	]

	}); 
	});
	</script>
<?php } ?>

	
</head>

<body <?php body_class(); ?>>
	
	

	<?php
		if ( is_home() || is_front_page() ) {
			get_template_part( 'inc/navigation', 'header' );
		} else {
			get_template_part( 'inc/navigation', 'header' );
		} ?>

	<div id="message_banner" class="container background_alt cloak">
		<?php gb_display_messages(); ?>
	</div>

	<div id="wrapper" class="clearfix">