<?php get_header(); ?>

		<div id="merchant_loop" class="container prime main clearfix">

			<div id="content" class="merchant clearfix">
				<div class="page_title business-page"><!-- Begin #page_title -->
					<h1 class="gb_ff"><?php gb_e('Business Directory'); ?></h1>
				</div><!-- End #page_title -->
				<div class="filter_biz section"><!-- Begin .filter_biz -->
					<a class="biz_toggler font_large bold gb_ff" href="javascript:void(0)" title="<?php gb_e('Browse by Type'); ?>"><?php gb_e('Browse by Type'); ?><span class="expand font_x_small boo"><?php gb_e('Toggle') ?></span></a>
					<?php gb_get_all_merchant_types_list( 'ul', 'All', 'biz_filter_links inline' ) ?>
				</div><!-- End .filter_biz -->
				

				<?php while ( have_posts() ) : the_post(); ?>

					<?php get_template_part( 'inc/loop-merchant' ); ?>

				<?php endwhile; ?>

				<?php if (  $wp_query->max_num_pages > 1 ) : ?>
					<?php get_template_part( 'inc/loop-nav', 'inc/index-nav' ); ?>
				<?php endif; ?>
			</div><!-- #content -->
			<div class="sidebar clearfix">
				<?php dynamic_sidebar( 'merchant-sidebar' ); ?>
			</div>	
			
		</div>
		
<?php get_footer(); ?>