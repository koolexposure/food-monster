<div id="post_content <?php the_ID() ?>" <?php post_class('post loop_deal background_alt deal_status-'.gb_get_status().' clearfix'); ?>>
	<?php 
		if ( gb_has_merchant() ) {
			?>
				<h3 class="deal_merchant_title alt_text font_small gb_ff clearfix"><a href="<?php gb_merchant_url(gb_get_merchant_id()) ?>" title="<?php the_title(gb_get_merchant_id()) ?>" class="alt_text"><?php gb_merchant_name(gb_get_merchant_id()); ?></a></h3>
			<?php
		} ?>
	<?php if ( has_post_thumbnail() ): ?>
		<div class="loop_thumb contrast"><?php the_post_thumbnail('gbs_208x120') ?></div>
	<?php else : ?>
		<div class="loop_thumb no_thumb contrast"><span class="logo_thumbnail" style="background: #FFF url(<?php gb_header_logo(); ?>) no-repeat center;">&nbsp;</span></div>
	<?php endif; ?>
			
	<div class="excerpt_content clearfix">
		
		<h2 class="entry_title contrast gb_ff"><span class="title_text"><?php the_title() ?></span><span class="deals_loop_price"><?php gb_price(); ?></span></h2>
		
				<div class="meta_value">
					<span class="deal_meta_title"><?php gb_e('Value: '); ?></span>
					<span class="deal_meta_value deal_worth">
						<?php gb_formatted_money(gb_get_deal_worth()) ?>
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

	</div>

</div>
