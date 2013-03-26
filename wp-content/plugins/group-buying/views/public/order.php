<?php
/**
 * The Template for displaying the purchase
 *
 * @package GBS
 */

get_header(); ?>

		<div id="primary" class="site-content">
			<div id="content" role="main">

			<?php
				/* Run the loop to output the post. */
				?>

			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<h1 class="entry-title"><?php the_title(); ?></h1>

					<div class="entry-content gb_checkout_payment">
						<?php the_content(); ?>
					</div><!-- .entry-content -->

				</div><!-- #post-## -->


			<?php endwhile; // end of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
