<?php
/*
Template Name: Restaurant Info
*/

get_header(); ?>
<?php
if (isset($_GET['resID']) && is_numeric($_GET['resID'])) { // to verify that fileID is passed
      // we now have the post ID in downloads page and can create download link
    $postID = ($_GET['resID']);
	$res_content = gb_get_merchant_meta1($_GET['resID']);
}
?>

<div id="side_navigation"><ul>
	<li><a href="<?php gb_merchant_url($postID); ?>">Their Story</a></li>
	<li><a href="currrent-deals/?resID=<?php echo $postID; ?>">Food Monster Special</a></li>
	<li><a href="restaurant-info/?resID=<?php echo $postID; ?>">Restaurant Info</a></li>
	<li><a href="#">What's New</a></li>
	</ul></div>
<div id="page_template" class="container prime main clearfix">

	<div id="content" class="clearfix">
<div class="page_title business_page"><!-- Begin #page_title -->
					<h2 class="gb_ff"><?php printf(gb__('Restaurant Info'), get_the_title() ); ?></h2>
				</div><!-- End #page_title -->

		<?php echo $res_content ?>
		
		<div class="section_content">
			<?php echo get_post_meta($post->ID, 'restaurant_info', true); ?>
		</div>
		<div class="section_content">
			<ul class="clearfix">
				<?php if (gb_has_merchant_website($postID)): ?>
					<li class="social_icon website"><a href="<?php gb_merchant_website($postID) ?>"><?php gb_e('Website') ?></a></li>
				<?php endif ?>
				<?php if (gb_has_merchant_facebook($postID)): ?>
					<li class="social_icon facebook"><a href="<?php gb_merchant_facebook($postID) ?>"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/facebookc.png"></a></a></li>
				<?php endif ?>
				<?php if (gb_has_merchant_twitter($postID)): ?>
					<li class="social_icon twitter"><a href="<?php gb_merchant_twitter($postID) ?>"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/twitterc.png"></a></li>
				<?php endif ?>
			</ul>
		</div>
	</div>
	<div id="page_sidebar" class="sidebar clearfix">
		<?php dynamic_sidebar('page-sidebar'); ?>
	</div>
	
</div>


<?php get_footer();