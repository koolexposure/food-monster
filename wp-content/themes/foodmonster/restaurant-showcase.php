<?php
/*
Template Name: Restaurant Showcase
*/

get_header(); ?>
<?php
if (isset($_GET['resID']) && is_numeric($_GET['resID'])) { // to verify that fileID is passed
      // we now have the post ID in downloads page and can create download link
      $res_content = gb_get_merchant_meta2($_GET['resID']);
}
?>
<div id="page_template" class="container prime main clearfix">

	<div id="content" class="clearfix">

		<?php echo $res_content ?>
	
	</div>
	<div id="page_sidebar" class="sidebar clearfix">
		<?php dynamic_sidebar('page-sidebar'); ?>
	</div>
	
</div>


<?php get_footer(); ?>