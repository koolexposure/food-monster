<?php do_action( 'gb_meta_box_deal_details_pre' ) ?>
<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery(".tinymce").addClass("mceEditor");
    if ( typeof( tinyMCE ) == "object" &&
         typeof( tinyMCE.execCommand ) == "function" ) {
        tinyMCE.execCommand("mceAddControl", false, "deal_highlights");
        tinyMCE.execCommand("mceAddControl", false, "deal_fine_print");
        tinyMCE.execCommand("mceAddControl", false, "deal_rss_excerpt");
    }
});
</script>
<p>
	<label for="deal_value"><strong><?php gb_e( 'Deal&rsquo;s value:' ); ?></strong></label><br/>
	<?php gb_currency_symbol(); ?><input type="text" name="deal_value" id="deal_value" class="small-text" tabindex="503" value="<?php echo esc_textarea( $deal_value ); ?>">
	<br/><?php gb_e( 'To advertise worth.' ); ?></p>
</p>
<p>
	<label for="deal_amount_saved"><strong><?php gb_e( 'Deal&rsquo;s savings:' ); ?></strong></label><br/>
	<input type="text" name="deal_amount_saved" id="deal_amount_saved" class="large-text" tabindex="504" value="<?php esc_attr_e( $deal_amount_saved ); ?>">
	<br/><?php gb_e( 'This is the savings that&rsquo;s advertised to the visitors. Examples: "40% off" or "$25 Discount".' ); ?>
</p>
<p>
	<label for="deal_highlights"><strong><?php gb_e( 'Deal&rsquo;s or Merchant&rsquo;s "Highlights":' ); ?></strong></label><br/>
	<textarea rows="3" cols="40" name="deal_highlights" tabindex="506" id="deal_highlights" class="tinymce" style="width:98%"><?php echo esc_textarea( $deal_highlights ); ?></textarea>
</p>
<p>
	<label for="deal_fine_print"><strong><?php gb_e( 'Deal&rsquo;s "Fine Print":' ); ?></strong></label><br/>
	<textarea rows="3" cols="40" name="deal_fine_print" tabindex="507" id="deal_fine_print" class="tinymce" style="width:98%"><?php echo esc_textarea( $deal_fine_print ); ?></textarea>
</p>
<p>
	<label for="deal_rss_excerpt"><strong><?php gb_e( 'Deal&rsquo;s RSS Excerpt:' ); ?></strong></label><br/>
	<textarea rows="3" cols="40" name="deal_rss_excerpt" tabindex="507" id="deal_rss_excerpt" class="tinymce" style="width:98%"><?php echo esc_textarea( $deal_rss_excerpt ); ?></textarea>
</p>
<?php /*/ ?>
<p>
	<label for="deal_rss_excerpt"><strong><?php gb_e('RSS Excerpt:'); ?></strong></label><br/>
	<textarea rows="3" cols="40" name="deal_rss_excerpt" tabindex="507" id="deal_rss_excerpt" class="tinymce" style="width:98%"><?php echo esc_textarea( $deal_rss_excerpt ); ?></textarea>
</p>
<?php /**/ ?>
<?php do_action( 'gb_meta_box_deal_details' ) ?>
