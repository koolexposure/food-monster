<?php
/*
Template Name: Current Deals
*/

get_header(); ?>



		<div id="deals_loop" class="container prime main clearfix">
			
			<div id="content" class="clearfix">

				<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

					<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<div class="page_title"><!-- Begin #page_title -->
							<h1 class="entry_title gb_ff"><?php the_title(); ?></h1>
						</div><!-- End #page_title -->

						<div class="entry_content">
							<?php the_content(); ?>
							<?php wp_link_pages( array( 'before' => '<div class="page-link">' . gb__( 'Pages:' ), 'after' => '</div>' ) ); ?>
						</div><!-- .entry_content -->
					</div><!-- #post-## -->
				<?php endwhile; 
     	
				$deal_query= null;
				$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
				$args=array(
					'post_type' => gb_get_deal_post_type(),
					'post_status' => 'publish',
					'paged' => $paged,
					'meta_query' => array(
						array(
							'key' => '_expiration_date',
							'value' => array(0, current_time('timestamp')),
							'compare' => 'NOT BETWEEN'
						)),
					
				);
				$deal_query = new WP_Query($args);
				?>
                
				<?php if ( ! $deal_query->have_posts() ) : ?>
                
					<?php get_template_part( 'deals/no-deals', 'deals/index' ); ?>
                
				<?php endif; ?>
                <div class="flexslider">
				    <ul class="slides">
				<?php $count; while ( $deal_query->have_posts() ) : $deal_query->the_post(); $count++; $zebra = ($count % 2) ? ' odd' : ' even'; ?>
                			 
						
					<?php get_template_part( 'inc/loop-item', 'inc/deal-item' ); ?>
            
	
				<?php endwhile; ?>
                </ul></div>
				<?php if (  $deal_query->max_num_pages > 1 ) : ?>
					<div id="nav-below" class="navigation clearfix">
						<div class="nav-previous"><?php next_posts_link( gb__( '<span class="meta-nav">&larr;</span> Older deals' ), $deal_query->max_num_pages ); ?></div>
						<div class="nav-next"><?php previous_posts_link( gb__( 'Newer deals <span class="meta-nav">&rarr;</span>' ), $deal_query->max_num_pages ); ?></div>
					</div><!-- #nav-below -->
				<?php endif; ?>
                
				<?php wp_reset_query(); ?>

			</div><!-- #content_wrap -->
			

		</div><!-- #single_page -->

<?php
get_footer();
