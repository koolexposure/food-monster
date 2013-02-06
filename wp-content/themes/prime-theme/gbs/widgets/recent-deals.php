<div class="deal_block prime prime_alt clearfix <?php if ( has_post_thumbnail() ) echo ' has_thumbnail' ?>">
	<?php if ( has_post_thumbnail() ):?>
	<div class="deal_thumbnail clearfix">
		<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>" class="post_thumbnail"><?php the_post_thumbnail( 'gbs_60x60' ); ?></a>
	</div><!-- // #.deal_thumbnail -->
	<?php endif ?>
	<div class="widget_info clearfix">
		<span class="meta">
			<?php 
				if ( gb_can_purchase() || ( function_exists('gb_is_deal_aggregated') && gb_is_deal_aggregated() ) ) {
					?><a href="<?php gb_add_to_cart_url(); ?>" class="small_buynow buynow button"><?php echo $buynow ?><span class="button_price"><?php gb_price(); ?></span></a><?php
				} else {
					echo '<span class="small_buynow buynow button"><span class="button_price">'.gb__( 'Purchase Unavailable' ).'</span></span>';
				} ?>
		</span>
	</div>
	<h3><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></h3>
</div>
