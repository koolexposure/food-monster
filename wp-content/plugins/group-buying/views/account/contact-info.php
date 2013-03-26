<?php 
/**
 * Contact information view
 *
 * @package GBS
 * @subpackage Account
 * @used-by Group_Buying_Accounts::get_panes()
 */ ?>

<div class="contact-info">
	<h3><?php gb_e( 'Contact Information' ); ?></h3>
	<p><?php echo $name; ?><br />
		<?php echo gb_format_address( $address, 'string', '<br />' ); ?></p>
</div>