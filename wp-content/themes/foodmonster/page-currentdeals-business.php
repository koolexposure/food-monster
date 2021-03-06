<?php
/*
 Template Name: Current Deals (based on business)
 */

get_header();
?>

<?php
if (isset($_GET['resID']) && is_numeric($_GET['resID'])) {// to verify that fileID is passed
	// we now have the post ID in downloads page and can create download link
	$postID = ($_GET['resID']);
	$merch_deals = gb_get_merchant_deals_query($postID);
}
?>
<div id="page_wrapper" class="clearfix">
<div id="side_navigation">
	<ul>
		<li>
			<a href="<?php gb_merchant_url($postID); ?>">Their Story</a><div class="nav_item"></div>
		</li>
		<li>
			<a href="currrent-deals/?resID=<?php echo $postID; ?>">Food Monster Special</a><div class="nav_item"></div>
		</li>
		<li>
			<a href="restaurant-info/?resID=<?php echo $postID; ?>">Restaurant Info</a><div class="nav_item"></div>
		</li>
		<li>
			<a href="whats-new/?resID=<?php echo $postID; ?>">What's New</a><div class="nav_item"></div>
		</li>
	</ul>
</div>


	<div id="business" class="container prime main clearfix">
		<div id="deals_loop" class="clearfix">
			<div class="page_title business_page">
				<!-- Begin #page_title -->
				<h2 class="gb_ff"><?php printf(gb__('Food Monster Specials'), get_the_title()); ?></h2>
			</div><!-- End #page_title -->
			<div class="flexslider">
				<ul class="slides">
					<?php  if ( $merch_deals && $merch_deals->have_posts() ) : while ($merch_deals->have_posts()) : $merch_deals->the_post(); ?>

					<?php get_template_part('inc/loop-item') ?>

					<?php endwhile; else: ?>
					<p>
						<?php printf(gb__('There are no active deals for %s.'), get_the_title()); ?>
					</p>
					<?php endif; wp_reset_query(); ?>
				</ul>
			</div>

		</div><!-- #single_page -->
	</div>
</div>
<?php
get_footer();
