<p>
	<?php gb_e( 'You can use the following shortcodes to create dynamic notifications' ); ?>
</p>
<?php
foreach ( $type['shortcodes'] as $shortcode ) : if ( isset( $shortcodes[$shortcode] ) ) : ?>
<p>
	<strong>[<?php echo $shortcode; ?>]</strong> &mdash;
	<?php echo $shortcodes[$shortcode]['description']; ?>
</p>
<?php endif; endforeach; ?>
