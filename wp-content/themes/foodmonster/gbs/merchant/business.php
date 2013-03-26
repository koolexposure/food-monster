<?php get_header(); ?>

			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
<div id="side_navigation"><ul>
	<li><a href="<?php gb_merchant_url(get_the_ID()); ?>">Their Story</a></li>
	<li><a href="currrent-deals/?resID=<?php echo $post->ID; ?>">Food Monster Special</a></li>
	<li><a href="restaurant-info/?resID=<?php echo $post->ID; ?>">Restaurant Info</a></li>
	<li><a href="#">What's New</a></li>
	</ul></div>
	<div id="business" class="container prime main clearfix">


		<div id="content_wrap" class="clearfix">

				
				<div id="merchant_<?php the_ID(); ?>" class="clearfix">
					
					<div class="page_title business_page"><!-- Begin #page_title -->
						<h1 class="gb_ff"><?php the_title() ?></h1>
					</div><!-- End #page_title -->

					<div id="merchant_meta" class="clearfix">
					
						<div class="merchant_single_logo clearfix"><!-- Begin .merchant-logo -->
							<?php the_post_thumbnail('gbs_300x180', array('title' => get_the_title())); ?>	
						</div><!-- End .merchant-logo -->
						
					</div>
					
					<div class="merchants-entry clearfix"><!-- Begin .merchants-entry -->
						
						<div id="merchant_content" class="header_color clearfix">
							<div class="page_title business_page"><!-- Begin #page_title -->
								<h2 class="gb_ff"><?php printf(gb__('About %s'), get_the_title() ); ?></h2>
							</div><!-- End #page_title -->
							<?php the_content(); ?>
						</div
						
						<?php if ( comments_open() || '0' != get_comments_number() ) : ?>
							<div class="discussion section clearfix">
								<div class="section_title clearfix">
									<h4 class="font_large"><?php gb_e('Discussion') ?><span class="expand font_x_small background_alt"><?php gb_e('Toggle') ?></span></h4>
								</div>
								<div class="section_content">	
									<?php comments_template( '', true ); ?>
								</div>	
							</div>
						<?php endif; ?>
								
						
					</div><!-- End .merchants-entry -->
												
						
				</div><!-- End #page-wrapper -->
			
			<?php endwhile; // end of the loop. ?>			
				
		</div><!-- End #content_wrap -->
	</div><!-- End .wrapper -->

<?php get_footer(); ?>