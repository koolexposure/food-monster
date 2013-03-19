<?php get_header(); ?>

	<div id="business" class="container prime main clearfix">
	
		<div id="content_wrap" class="clearfix">

			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
				
				<div id="merchant_<?php the_ID(); ?>" class="clearfix">
					
					<div class="page_title business_page"><!-- Begin #page_title -->
						<h1 class="gb_ff"><?php the_title() ?></h1>
					</div><!-- End #page_title -->

					<div id="merchant_meta" class="clearfix">
					
						<div class="merchant_single_logo clearfix"><!-- Begin .merchant-logo -->
							<?php the_post_thumbnail('gbs_300x180', array('title' => get_the_title())); ?>	
						</div><!-- End .merchant-logo -->
						
						<div class="description section clearfix">
							<div class="section_title clearfix">
								<h4 class="font_large gb_ff"><?php gb_e('Category:'); ?><span class="expand font_x_small background_alt"><?php gb_e('Toggle') ?></span></h4>
							</div>
							<div class="section_content">
								<?php gb_get_merchants_types_list(get_the_ID()) ?>
							</div>
						</div>
				
						<div class="description section clearfix">
							<div class="section_title clearfix">
								<h4 class="font_large gb_ff"><?php gb_e('Links:'); ?><span class="expand font_x_small background_alt"><?php gb_e('Toggle') ?></span></h4>
							</div>
							<div class="section_content">
								<ul class="clearfix">
									<?php if (gb_has_merchant_website()): ?>
										<li class="social_icon website"><a href="<?php gb_merchant_website() ?>"><?php gb_e('Website') ?></a></li>
									<?php endif ?>
									<?php if (gb_has_merchant_facebook()): ?>
										<li class="social_icon facebook"><a href="<?php gb_merchant_facebook() ?>"><?php gb_e('Facebook') ?></a></li>
									<?php endif ?>
									<?php if (gb_has_merchant_twitter()): ?>
										<li class="social_icon twitter"><a href="<?php gb_merchant_twitter() ?>"><?php gb_e('Twitter') ?></a></li>
									<?php endif ?>
								</ul>
							</div>
						</div>
						
					</div>
					
					<div class="merchants-entry clearfix"><!-- Begin .merchants-entry -->
						
						<div id="merchant_content" class="header_color clearfix">
							<div class="page_title business_page"><!-- Begin #page_title -->
								<h2 class="gb_ff"><?php printf(gb__('About %s'), get_the_title() ); ?></h2>
							</div><!-- End #page_title -->
							<?php the_content(); ?>
						</div>

						<div id="deals_loop" class="merchant_deals clearfix">
							<div class="page_title business_page"><!-- Begin #page_title -->
								<h2 class="gb_ff"><?php printf(gb__('Deals offered by %s'), get_the_title() ); ?></h2>
							</div><!-- End #page_title -->

							<?php 
								$merch_deals = gb_get_merchant_deals_query();
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
							
						</div>
						
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