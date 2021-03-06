<?php
/*
Template Name: Current Deals (based on preferred location)
*/
get_header(); ?>
<?php
			if ( isset( $_COOKIE[ 'gb_location_preference' ] ) && $_COOKIE[ 'gb_location_preference' ] != '' ) {
		$location = $_COOKIE[ 'gb_location_preference' ];
	}
 		
        // Get the latest post
        $latestpost = get_posts( array(
            'numberposts' => 1, 'post_type' => 'gb_merchant', 'gb_location' => $location,
        ) );

 ?>

 <?php foreach( $latestpost as $post ) :	setup_postdata($post); ?>
<div id="page_wrapper" class="clearfix">
	<div id="side_navigation">
		<ul>
			<li>
				<a href="<?php gb_merchant_url(get_the_ID()); ?>">Their Story</a><div class="nav_item"></div>
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
					<h1 class="gb_ff"><?php the_title() ?></h1>
				</div><!-- End #page_title -->

				<div id="merchant_meta" class="clearfix">

					<div class="merchant_single_logo clearfix">
						<!-- Begin .merchant-logo -->
						<?php the_post_thumbnail('gbs_300x180', array('title' => get_the_title())); ?>
					</div><!-- End .merchant-logo -->

				</div>

				<div class="merchants-entry clearfix">
					<!-- Begin .merchants-entry -->

					<div id="merchant_content" class="header_color clearfix">
						<div class="page_title business_page">
							<!-- Begin #page_title -->
							<h2 class="gb_ff"><?php printf(gb__('About %s'), get_the_title()); ?></h2>
						</div><!-- End #page_title -->
						<?php the_content(); ?>
					</div

				</div><!-- End .merchants-entry -->

			</div><!-- End #page-wrapper -->

		<?php endforeach; ?>
		</div><!-- End #content_wrap -->
	</div><!-- End .wrapper -->
</div>
</div>
<?php get_footer();