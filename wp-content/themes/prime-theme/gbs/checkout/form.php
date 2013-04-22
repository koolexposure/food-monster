<form id="gb_checkout_<?php echo $current_page; ?>" action="https://connect.merchanttest.firstdataglobalgateway.com/IPGConnect/gateway/processing" method="post">
	<input type="hidden" name="gb_checkout_action" value="<?php echo $current_page; ?>" />
	<?php foreach ( $panes as $pane ) {
		echo $pane['body'];
	} ?>
	<?php do_action('gb_checkout_form_'.$current_page); ?>
	<?php do_action('gb_checkout_form'); ?>
	
		<input type="hidden" name="txntype" value="sale" />
		<input type="hidden" name="trxOrigin" value="ECI" />
	<input type="hidden" name="timezone" value="<?php echo
getTimezone() ?>" />
<input size="50" type="hidden" name="txndatetime" value="<?php
echo getDateTime() ?>" />
<input size="50" type="hidden" name="storename" value="<?php
echo getStorename() ?>"/>
<input size="50" type="hidden" name="hash" value="<?php echo
createHash($chargetotal) ?>" />
<input size="50" type="hidden" name="subtotal" value="20.00" />
<input size="50" type="hidden" name="chargetotal" value="20.00" />



</form>