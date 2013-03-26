<?php
/*
Template Name: Restaurant Showcase
*/

get_header(); ?>
<?php
$args = array( 'post_type' => 'gb_merchant', 'orderby' => 'date', 'posts_per_page=7' );
$loop = new WP_Query( $args );



?>
<div id="page_template" class="container prime main clearfix">

	<div id="content" class="clearfix">
<?php
		while ( $loop->have_posts() ) : $loop->the_post();
			the_title();
			echo '<div class="entry-content">';
			the_content();
			echo '</div>';
		endwhile;
		wp_reset_postdata();
?>	
	</div>
	<div id="page_sidebar" class="sidebar clearfix">
		<?php dynamic_sidebar('page-sidebar'); ?>
	</div>
	
</div>


<?php get_footer(); ?>