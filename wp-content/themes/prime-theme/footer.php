	</div><!-- #wrapper -->

	<div id="footer_wrap" class="main_wrap prime boxed_prime clearfix">

		<div id="footer" class="container clearfix">
			<div class="footer_widget_wrap">
				<?php dynamic_sidebar( 'deal_footer_one' ); ?>
			</div>
			<div class="footer_widget_wrap">
				<?php dynamic_sidebar( 'deal_footer_two' ); ?>
			</div>
			<div class="footer_widget_wrap">
				<?php dynamic_sidebar( 'deal_footer_three' ); ?>
			</div>
			<div class="legal">
				<span class="font_small"><?php printf( gb__( 'Copyright %s.  All rights reserved.' ), date( 'Y' ) );?></span>
			</div>
		</div>

	</div>

<?php wp_footer(); ?>

</body>
</html>
