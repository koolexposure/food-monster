<?php
	get_header();
	?>

	<div id="cart" class="container prime main clearfix">
	
		<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
			
			
			<div id="content" class="cart clearfix">
			
				<div class="page_title clearfix">
					<h1 class="main_heading gb_ff"><span class="title_highlight"><?php the_title(); ?></span></h1>
				</div>

				<?php the_content(); ?>
			</div><!-- #content -->
		
		<?php endwhile; // end of the loop. ?>

		<div class="sidebar">

			<?php get_template_part( 'inc/account-sidebar' ); ?>
			<?php dynamic_sidebar( 'cart-sidebar' ); ?>

		</div>
			
	</div><!-- .container -->
	
<?php get_footer(); ?>
