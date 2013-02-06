<?php do_action( 'gb_meta_box_merchant' ) ?>
<p>
	<label for="deal_merchant"><strong><?php gb_e( 'Deal Merchant:' ); ?></strong></label><br />
	<select name="deal_merchant" id="deal_merchant" class="select2" style="width:300px;">
		<option></option>
		<?php
foreach ( $merchants as $merchant ) {
	echo '<option value="' . $merchant->ID . '" ' . selected( $merchant->ID, $merchant_id ) . '>' . esc_html( $merchant->post_title ) .  "</option>\n";
} ?>
	</select>
</p>
<?php do_action( 'gb_meta_box_merchant' ) ?>
