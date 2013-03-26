<?php
	get_header();

	switch (gb_get_current_checkout_page()) {
		case 'confirmation':
			$title = 'Your Purchase Confirmation';
			break;
		case 'review':
			$title = 'Your Purchase Review';
			break;
		case 'payment':
		default:
			$title = 'Purchase';
			break;
	}
	?>

	<div id="checkout_page" class="container prime main clearfix">
	
		<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
			
			
			<div id="content" class="full clearfix">
			
				<div class="page_title clearfix">
					<h1 class="main_heading gb_ff"><span class="title_highlight"><?php gb_e($title); ?></span></h1>
				</div>

				<?php the_content(); ?>
				
			</div><!-- #content -->
		
		<?php endwhile; // end of the loop. ?>	
		
	</div><!-- #single_deal -->
	
<?php get_footer(); ?>

