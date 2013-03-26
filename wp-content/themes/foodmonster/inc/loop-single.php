<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="page_title"><!-- Begin #page_title -->
		<h1 class="gb_ff"><?php the_title(); ?></h1>
	</div><!-- End #page_title -->
	
	<div class="main_block">
		<?php the_content(); ?>
		
		<div id="comments_wrap"  class="border_top clearfix">
			<?php comments_template( '', true ); ?>
		</div>
		
	</div>
	
</div>