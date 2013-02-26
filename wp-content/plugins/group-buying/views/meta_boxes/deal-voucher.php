<?php do_action( 'gb_meta_box_deal_voucher_pre' ) ?>
<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery(".tinymce").addClass("mceEditor");
    if ( typeof( tinyMCE ) == "object" &&
         typeof( tinyMCE.execCommand ) == "function" ) {
        tinyMCE.execCommand("mceAddControl", false, "voucher_how_to_use");
    }
});
</script>
<p id="voucher_howto_edit">
	<label for="voucher_how_to_use"><strong><?php gb_e( 'Voucher&rsquo;s "How to use this":' ); ?></strong></label><br/>
	<textarea rows="3" cols="40" name="voucher_how_to_use" tabindex="508" id="voucher_how_to_use" class="tinymce" style="width:98%"><?php echo esc_textarea( $voucher_how_to_use ) ?></textarea>
</p>
<p id="voucher_logo_edit">
	<label for="voucher_logo"><strong><?php gb_e( 'Voucher&rsquo;s Logo:' ); ?></strong></label><br/>
	<input type="text" name="voucher_logo" id="voucher_logo" class="large-text" tabindex="509" value="<?php echo $voucher_logo; ?>">
<br/><?php gb_e( 'Defaults to sitename and description.' ); ?></p>
</p>
<p id="voucher_prefix_edit">
	<label for="voucher_id_prefix"><strong><?php gb_e( 'Voucher&rsquo;s ID Prefix:' ); ?></strong></label><br/>
	<input type="text" name="voucher_id_prefix" id="voucher_id_prefix" class="small-text" tabindex="510" value="<?php echo $voucher_id_prefix; ?>">
<br/><?php gb_e( 'Leave this blank if you prefer to use your global settings.' ); ?></p>
</p>
<p id="voucher_serial_edit">
	<label for="voucher_serial_numbers"><strong><?php gb_e( 'Voucher&rsquo;s Serials:' ); ?></strong></label><br/>
	<textarea rows="3" cols="40" name="voucher_serial_numbers" tabindex="510" id="voucher_serial_numbers" style="width:98%"><?php echo $voucher_serial_numbers; ?></textarea>
	<br/><?php gb_e( 'Comma separated list.' ); ?>
</p>
<p id="voucher_expiration_edit">
	<label for="voucher_expiration_date"><strong><?php gb_e( 'Voucher&rsquo;s Expiration Date:' ); ?></strong></label><br/>
	<input type="text" name="voucher_expiration_date" id="voucher_expiration_date" class="medium-text" value="<?php if ( !empty( $voucher_expiration_date ) ) echo date( 'm/d/Y', $voucher_expiration_date ) ?>"  tabindex="511">
</p>
<p id="voucher_locations_edit">
	<label for="voucher_location_1"><strong><?php gb_e( 'Voucher&rsquo;s Locations:' ); ?></strong></label><br/>
	<?php $line_format = gb__( 'Line %d:' ); ?>
	<?php foreach ( $voucher_locations as $index => $location ) { ?>
		<input type="text" name="voucher_locations[]" id="voucher_location_<?php echo $index+1; ?>" class="large-text" tabindex="<?php echo 512+$index; ?>" value="<?php esc_attr_e( $location ); ?>"><br />
	<?php } ?>
	<?php gb_e( 'Multiple lines available for multiple locations.' ); ?></p>
</p>
<p id="voucher_map_edit">
	<label for="voucher_map"><strong><?php gb_e( 'Map iframe code:' ); ?></strong></label><br/>
	<input type="text" name="voucher_map" id="voucher_map" class="large-text" tabindex="<?php echo 512+count( $voucher_locations ); ?>" value="<?php echo esc_attr_e( $voucher_map ); ?>">
<br/><?php gb_e( 'Go to <a href="http://www.mapquest.com/">MapQuest</a> or <a href="http://www.google.com/maps" title="Google Maps">Google Maps</a> and create a map with multiple or single locations. Click on "Link/Embed" at the the top right of your map (MapQuest) or the link icon to the left of your map (Google Maps), copy the code from "Paste HTML to embed in website" here.' ); ?></p>
	<?php echo $voucher_map; ?>
</p>
<?php do_action( 'gb_meta_box_deal_voucher' ) ?>
