<?php
	get_header();
	?>

	<div id="account" class="container prime main clearfix">
	
		<div class="top_sidebar">
			<?php get_template_part( 'inc/account-sidebar' ); ?>
		</div>	
		
		<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
		
		
			<div id="content" class="cart clearfix">
		
				<div class="page_title clearfix">
					<h1 class="main_heading gb_ff"><span class="title_highlight"><?php gb_e('Your Account') ?></span></h1>
				</div>

				<?php the_content(); ?>
			</div><!-- #content -->
		
		<?php endwhile; // end of the loop. ?>

		<div class="sidebar">

			<?php get_template_part( 'inc/account-sidebar' ); ?>
			<?php dynamic_sidebar( 'account-sidebar' ); ?>

		</div>
		
	</div><!-- .container -->
	
<?php get_footer(); ?>
