<?php
	get_header();
	?>

		<div id="loop" class="container prime main clearfix">
			
			<div id="content" class="clearfix">

				<?php get_template_part( 'loop', 'index' ); ?>

				<?php global $wp_query; if ( $wp_query->max_num_pages > 1 ) : ?>
					<?php get_template_part( 'inc/loop-nav', 'inc/index-nav' ); ?>
				<?php endif; ?>
			</div><!-- #content -->

			<div class="sidebar clearfix">
				<?php dynamic_sidebar( 'blog-sidebar' );?> 
			</div>

		</div>
		
<?php get_footer(); ?>