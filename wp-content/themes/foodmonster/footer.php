	

	</div><!-- #wrapper -->	

	
	<div id="footer_wrap" class="main_wrap prime boxed_prime clearfix">

		<div id="footer" class="container clearfix">
	
			<div class="legal">
				<span class="font_small"><?php printf(gb__('Copyright %s.  All rights reserved.'),date('Y'));?></span>
			</div>
			<div class="footer_menu">
				<ul>
					<li><a href="<?php gb_account_register_url() ?>">login</a></li>
					<li><a href="advertise-your-restaurant">advertise your restaurant</a></li>
					<li><a href="contact-us">contact us</a></li>
				</ul>
			</div>
			<div class="social_footer">
			<a href="https://www.facebook.com/FoodMonsterDeals"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/facebook.png"></a>
			<a href="https://twitter.com/FoodMonsterDeal"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/twitter.png"></a>
			</div>
		</div>
		
	</div>

<?php wp_footer(); ?>
</body>
</html>