<?php
/*
Template Name: Loop (blog) Template
*/
get_header(); ?>

		<div id="page_template_loop" class="container prime main clearfix">
			
			<div id="content" class="clearfix">

				<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

					<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				
						<div class="page_title"><!-- Begin #page_title -->
							<h1 class="gb_ff"><?php the_title(); ?></h1>
						</div><!-- End #page_title -->

				<?php endwhile; ?>

						<?php
							$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
							$args = array(
								'post_status' => 'publish',
							   //'cat' => 3, // Uncomment and add the category ID if you want to filter this loop.
							   'paged' => $paged,
							   );
							$wp_query = new WP_Query($args);
							get_template_part( 'loop' );
							?>

					</div><!-- #post-## -->
			
			</div>

			<div id="page_sidebar" class="sidebar clearfix">
				<?php dynamic_sidebar( 'blog-sidebar' ); ?>
			</div>
			
		</div>
		
<?php get_footer(); ?>