<?php
	do_action('gb_deals_view');
	get_header();
	?>

		<div id="deals_loop" class="container prime main clearfix">
			
			<div class="page_title business-page"><!-- Begin #page_title -->
				<h1 class="gb_ff"><?php gb_deals_index_title() ?></h1>
			</div><!-- End #page_title -->
			
			<div id="content" class="clearfix">
				
				<?php if ( ! have_posts() ) : ?>
					
					<?php get_template_part( 'gbs/deals/no-deals', 'gbs/deals/index' ); ?>
					
				<?php endif; ?>

				<?php while ( have_posts() ) : the_post(); ?>
				
					<?php get_template_part( 'inc/loop-item', 'inc/deal-item' ); ?>

				<?php endwhile; ?>

				<?php global $wp_query; if ( $wp_query->max_num_pages > 1 ) : ?>
					<?php get_template_part( 'inc/loop-nav', 'inc/index-nav' ); ?>
				<?php endif; ?>
			</div><!-- #content -->

			<div class="sidebar clearfix">
				<?php do_action('gb_above_default_sidebar') ?>
				<?php dynamic_sidebar( 'deals-sidebar' );?> 
				<?php do_action('gb_below_default_sidebar') ?>
			</div>

		</div>

<?php get_footer(); ?>