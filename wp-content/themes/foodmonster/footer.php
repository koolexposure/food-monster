	</div><!-- #wrapper -->	

	
	<div id="footer_wrap" class="main_wrap prime boxed_prime clearfix">

		<div id="footer" class="container clearfix">
	
			<div class="legal">
				<span class="font_small"><?php printf(gb__('Copyright %s.  All rights reserved.'),date('Y'));?></span>
			</div>
			<div class="footer_menu">
				<ul>
					<li><a href="">login</a></li>
					<li><a>advertise your restaurant</a></li>
					<li><a>contact us</a></li>
				</ul>
			</div>
			<div class="social_footer">
			<a href="<?php echo site_url() ?>"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/facebook.png"></a>
			<a href="<?php echo site_url() ?>"><img src="<?php bloginfo('stylesheet_directory'); ?>/img/twitter.png"></a>
			</div>
		</div>
		
	</div>

<?php wp_footer(); ?>
</body>
</html>