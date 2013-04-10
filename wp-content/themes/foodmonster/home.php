<?php 
/**
* Template Name: Home page (landing page)
**/
get_header(); ?>

<div id="home_page" class="container clearfix">
	<div id="content" class="home prime clearfix">
		<div id="slide_btn">
			<div id="slide_btnbng" class="slide_open"></div>
		</div>
		<div id="top_footer_wrap" class="clearfix">
			<div id="widget_wrapper">
			<div class="footer_widget_wrap_one">
				<?php dynamic_sidebar( 'deal_footer_one' ); ?>
			</div>
			<div class="footer_widget_wrap_two">
				<?php dynamic_sidebar( 'deal_footer_two' ); ?>
			</div>
			<div class="footer_widget_wrap_three">
				<?php dynamic_sidebar( 'deal_footer_three' ); ?>
			</div>
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>

