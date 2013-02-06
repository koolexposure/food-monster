<?php
// This view is displayed on the merchant/ page when the current user hasn't registered a merchant
?>
<p>
	<?php gb_e( 'If you register a merchant with this site, it can be associated with daily deals.' ); ?>
</p>
<p>
	<a href="<?php echo esc_url( Group_Buying_Merchants_Registration::get_url() ); ?>"><?php gb_e( 'Register a merchant' ); ?></a>
</p>
