<?php 
/**
* Template Name: Home page (landing page)
**/
get_header(); ?>

	<div id="home_page" class="container clearfix">
		
		<div id="content" class="home prime clearfix">
			
			<div class="mini_header home">
				
				<img src="<?php gb_header_logo(); ?>" />
			
				<h2><?php bloginfo('description') ?></h2>
			
				<?php gb_e('Edit this text by using a child theme or by using the translator.') ?>
			
			</div><!-- // .mini_header -->
		
			<div id="subscription_form" class="split_right clearfix">

				<h2><?php gb_e('Start Here!'); ?></h2>

				<?php gb_subscription_form() ?>

			</div>
			
		</div>
		
	</div>		

<?php // get_footer(); Manually added footer code below ?>

	</div><!-- #wrapper -->	
	
<?php wp_footer(); ?>
</body>
</html>