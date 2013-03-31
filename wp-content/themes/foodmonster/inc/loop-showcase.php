<li>
<div id="post_content <?php the_ID() ?>" <?php post_class('post loop_deal background_alt deal_status-'.gb_get_status().' clearfix'); ?>>

	<?php if ( has_post_thumbnail() ): ?>
		<a href="<?php the_permalink() ?>" title="<?php gb_e('Read'); ?> <?php the_title() ?>"><div class="loop_thumb showcase-loop contrast"><?php the_post_thumbnail(array(450,450)) ?>ß</div></a>
	<?php else : ?>
		<a href="<?php the_permalink() ?>" title="<?php gb_e('Read'); ?> <?php the_title() ?>"><div class="loop_thumb no_thumb contrast"><span class="logo_thumbnail" style="background: #FFF url(<?php gb_header_logo(); ?>) no-repeat center;">&nbsp;</span></div></a>
	<?php endif; ?>
	
	<div class="excerpt_content showcase-loop clearfix">
		
		<h2 class="entry_title contrast gb_ff"><a href="<?php the_permalink() ?>" title="Read <?php the_title() ?>" class="clearfix"><span class="title_text"><?php the_title() ?></span><span class="deals_loop_price"><?php gb_price(); ?></span></a></h2>
		
				<div class="meta_value">
					<span class="deal_meta_title"><?php gb_e('Value: '); ?></span>
					<span class="deal_meta_value deal_worth">
						<?php gb_formatted_money(gb_get_deal_worth()) ?>
							<?php 
		if ( gb_has_merchant() ) {
			?>
				<h3 class="deal_merchant_title alt_text font_small gb_ff clearfix"><a href="<?php gb_merchant_url(gb_get_merchant_id()) ?>" title="<?php the_title(gb_get_merchant_id()) ?>" class="alt_text"><?php gb_merchant_name(gb_get_merchant_id()); ?></a></h3>
			<?php
		} ?>
					</span>
				</div>
				<div class="meta_value">
					<span class="deal_meta_title"><?php gb_e('Savings: '); ?></span>
					<span class="deal_meta_value deal_savings">
						<?php gb_amount_saved() ?>
					</span>
				</div>
				<?php if (gb_has_expiration()): ?>
					<div class="meta_value">
						<span class="the_time"><?php gb_deal_countdown( true ) ?></span>
					</div>
				<?php endif ?>
				<div class="deals-loop-buy">
					<?php if ( gb_can_purchase() || ( function_exists('gb_is_deal_aggregated') && gb_is_deal_aggregated() ) ) { ?>
						<a href="<?php gb_add_to_cart_url() ?>" class="button form-submit"><?php gb_e('Buy it!') ?> <span class="button_price"><?php gb_price(); ?></span></a>
					<?php  }  else { ?>
						<a href="<?php gb_add_to_cart_url() ?>" class="button form-submit"><?php gb_e('Unavailable') ?></a>
					<?php } ?>
				</div>	

	
	</div>
<div class="excerpt_background clearfix">	</div>

</div></li>
