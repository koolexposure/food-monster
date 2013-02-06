<?php
	get_header();
	?>

	<div id="report_page" class="container prime main clearfix">
	
		<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
			
			<div id="content" class="full clearfix">
			
				<div class="page_title clearfix">
					<h1 class="main_heading gb_ff"><span class="title_highlight"><?php the_title(); ?></span></h1>
					<span class="csv_download_link"><a class="report_button alt_button" href="<?php gb_current_report_csv_download_url(); ?>"><?php gb_e('csv download') ?></a></span>
				</div>

				<?php the_content(); ?>
			</div><!-- #content -->
		
		<?php endwhile; // end of the loop. ?>	
		
	</div><!-- #report_page -->
	
<?php get_footer(); ?>
