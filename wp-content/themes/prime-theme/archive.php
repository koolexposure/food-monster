<?php
get_header(); ?>

		<div id="archive" class="container prime main clearfix">
			
			<div id="content" class="clearfix">
				<?php get_template_part( 'loop' ); ?>
			</div>
			
			<div id="page_sidebar" class="sidebar clearfix">
				<?php dynamic_sidebar( 'blog-sidebar' ); ?>
			</div>
			
		</div>
		
<?php get_footer(); ?>