<?php
/*
Template Name: Done Deals (with location filter)
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
							'compare' => 'BETWEEN'
						)),
				);
				// get prefered location if it's set
				if ( gb_has_location_preference() ) {
					$location = gb_get_preferred_location();
					$args = array_merge( array(gb_get_deal_location_tax() => $location), $args);
				}
				$deal_query = new WP_Query($args);
				?>
                
				<?php if ( ! $deal_query->have_posts() ) : ?>
                
					<?php get_template_part( 'deal/no-deals', 'deal/index' ); ?>
                
				<?php endif; ?>
                
				<?php $count; while ( $deal_query->have_posts() ) : $deal_query->the_post(); $count++; $zebra = ($count % 2) ? ' odd' : ' even'; ?>
                
					<?php get_template_part( 'inc/loop-item', 'inc/deal-item' ); ?>
                
				<?php endwhile; ?>
                
				<?php if (  $deal_query->max_num_pages > 1 ) : ?>
					<div id="nav-below" class="navigation clearfix">
						<div class="nav-previous"><?php next_posts_link( gb__( '<span class="meta-nav">&larr;</span> Older deals' ), $deal_query->max_num_pages ); ?></div>
						<div class="nav-next"><?php previous_posts_link( gb__( 'Newer deals <span class="meta-nav">&rarr;</span>' ), $deal_query->max_num_pages ); ?></div>
					</div><!-- #nav-below -->
				<?php endif; ?>
                
				<?php wp_reset_query(); ?>

			</div><!-- #content_wrap -->

			<div id="page_sidebar" class="sidebar clearfix">
				<?php do_action('gb_above_default_sidebar') ?>
				<?php dynamic_sidebar( 'deals-sidebar' );?> 
				<?php do_action('gb_below_default_sidebar') ?>
			</div>

		</div><!-- #single_page -->

<?php
get_footer();