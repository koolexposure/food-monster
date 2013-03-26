<?php do_action( 'gb_meta_box_deal_theme_meta_pre' ) ?>
<p>
	<label for="featured_content"><strong><?php self::_e( 'Featured Content' ) ?>:</strong></label>
	<br/><textarea rows="5" cols="40" name="featured_content" style="width:98%"><?php print $featured_content; ?></textarea>
	<br /><small><?php self::_e( 'Replace deal thumbnail area with this featured content. Shortcodes are accepted.' ); ?></small>
</p>
<?php do_action( 'gb_meta_box_deal_theme_meta' ) ?>
