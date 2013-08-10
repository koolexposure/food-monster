<?php
/*
 Template Name: Restaurant Info
 */

get_header();
 ?>
<?php
if (isset($_GET['resID']) && is_numeric($_GET['resID'])) {// to verify that fileID is passed
	// we now have the post ID in downloads page and can create download link
	$postID = ($_GET['resID']);
	$res_content = gb_get_merchant_meta1($_GET['resID']);
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
		<div id="content_wrap" class="clearfix">
			<div id="merchant_<?php the_ID(); ?>" class="clearfix">
				<div class="page_title business_page">
					<!-- Begin #page_title -->
					<h1 class="gb_ff"><?php gb_merchant_name($postID) ?></h1>
				</div><!-- End #page_title -->
				<div id="merchant_meta" class="clearfix">
					<div class ="location">
						<h2>Location</h3>
						<?php	gb_merchant_street($postID); ?>
						<?php	gb_merchant_city($postID); ?> ,<?php gb_merchant_state($postID); ?>, <?php	gb_merchant_zip($postID); ?>
					</div>
					<div class ="hours">
						<h2>Hours</h2>
						<?php gb_merchant_meta1($postID); ?>
					</div>
					<div class="contact">
						<h2>Contact</h2>
						<?php	gb_merchant_phone($postID); ?>
					</div>
					<div class="online">
						<h2>Online</h2>
						<ul class="clearfix">
							<?php if (gb_has_merchant_website($postID)): ?>
							<li class="social_icon website">
								<a href="<?php gb_merchant_website($postID) ?>"><?php gb_e('Website') ?></a>
							</li>
							<?php endif ?>
							<?php if (gb_has_merchant_facebook($postID)): ?>
							<li class="social_icon facebook">
								<a href="<?php gb_merchant_facebook($postID) ?>"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/facebookc.png"></a></a>
							</li>
							<?php endif ?>
							<?php if (gb_has_merchant_twitter($postID)): ?>
							<li class="social_icon twitter">
								<a href="<?php gb_merchant_twitter($postID) ?>"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/twitterc.png"></a>
							</li>
							<?php endif ?>
						</ul>
					</div>
				</div>
				<div class="merchants-entry clearfix">
					<div id="merchant_content" class="header_color clearfix">
						<div class="page_title business_page">
							<!-- Begin #page_title -->
							<h2 class="gb_ff"><?php printf(gb__('Restaurant Info'), get_the_title()); ?></h2>
						</div>
						<div class="map">
							<?php	gb_merchant_meta2($postID); ?>
						</div>
					</div>
				</div><!-- End .merchants-entry -->
			</div><!-- End #page-wrapper -->
		</div><!-- End #content_wrap -->
	</div><!-- End .wrapper -->
</div>

<?php get_footer();