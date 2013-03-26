<?php
	get_header();
	?>

	<div id="merchant_page" class="container prime main clearfix">
	
		<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
			
			
			<div id="content" class="merchant clearfix">
			
				<div class="page_title clearfix">
					<?php if (gb_on_merchant_dashboard_page()): ?>
						<h1 class="main_heading gb_ff"><span class="title_highlight"><?php gb_e('Merchant Dashboard') ?></span></h1>
					<?php else: ?>
						<h1 class="main_heading gb_ff"><span class="title_highlight"><?php the_title(); ?></span></h1>
					<?php endif ?>
				</div>
				<?php remove_filter ('the_content', 'wpautop'); ?>
				<?php the_content(); ?>
			</div><!-- #content -->
		
		<?php endwhile; // end of the loop. ?>

		<div class="sidebar">

			<?php get_template_part( 'inc/account-sidebar' ); ?>
			<?php dynamic_sidebar( 'merchant-sidebar' ); ?>

		</div>
		
	</div><!-- #single_deal -->
	
<?php get_footer(); ?>