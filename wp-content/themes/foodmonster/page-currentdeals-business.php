<?php
/*
Template Name: Current Deals (based on business)
*/

get_header(); ?>

<?php
if (isset($_GET['resID']) && is_numeric($_GET['resID'])) { // to verify that fileID is passed
      // we now have the post ID in downloads page and can create download link
    $postID = ($_GET['resID']);  
	$merch_deals = gb_get_merchant_deals_query($postID);
}
?>
<div id="side_navigation"><ul>
	<li><a href="<?php gb_merchant_url($postID); ?>">Their Story</a></li>
	<li><a href="currrent-deals/?resID=<?php echo $postID; ?>">Food Monster Special</a></li>
	<li><a href="restaurant-info/?resID=<?php echo $postID; ?>">Restaurant Info</a></li>
	<li><a href="#">What's New</a></li>
	</ul></div>
		<div id="deals_loop" class="container prime main clearfix">
				<div class="page_title business_page"><!-- Begin #page_title -->
					<h2 class="gb_ff"><?php printf(gb__('Deals offered by %s'), get_the_title() ); ?></h2>
				</div><!-- End #page_title -->
			<div id="content" class="clearfix">

				<?php 
					
					if ( $merch_deals && $merch_deals->have_posts() ) :
						while ($merch_deals->have_posts()) : $merch_deals->the_post();
							?>
							<div class="deals_loop">
								<?php get_template_part('inc/loop-item') ?>
							</div>
							<?php
						endwhile;
					else:
						?>
							<p><?php printf(gb__('There are no active deals for %s.'), get_the_title() ); ?></p>
						<?php
					endif;
					wp_reset_query();
					?>

			</div><!-- #content_wrap -->

			<div id="page_sidebar" class="sidebar clearfix">
				<?php do_action('gb_above_default_sidebar') ?>
				<?php dynamic_sidebar( 'deals-sidebar' );?> 
				<?php do_action('gb_below_default_sidebar') ?>
			</div>

		</div><!-- #single_page -->

<?php
get_footer();