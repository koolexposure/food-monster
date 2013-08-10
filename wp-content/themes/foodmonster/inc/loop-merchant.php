<div class="biz_listing clearfix"><!-- Begin .biz_listing -->
	
	<div class="biz_wrapper clearfix">
	
		<div class="merchant_logo"><!-- Begin .merchant-logo -->
			<?php the_post_thumbnail('gbs_150w', array('title' => get_the_title())); ?>
		</div><!-- End .merchant-logo -->
	
		<div class="biz_content contrast">
	
			<h2 class="gb_ff merchant-title"><a href="<?php the_permalink() ?>" title="<?php the_title() ?>"><?php the_title() ?></a></h2>
			<div class="the_excerpt clearfix">
				<p><?php the_excerpt(); ?></p>
			</div><!-- #.the_excerpt -->

			<ul class="inline social_links clearfix merchant_meta">
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

	<p><a href="<?php the_permalink() ?>" class="biz_moreinfo gb_ff alignright"><?php gb_e('More Info') ?></a></p>

</div><!-- End .biz_listing -->