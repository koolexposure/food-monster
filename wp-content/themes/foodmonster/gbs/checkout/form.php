<form id="gb_checkout_<?php echo $current_page; ?>" action="<?php gb_checkout_url(); ?>" method="post">
	<input type="hidden" name="gb_checkout_action" value="<?php echo $current_page; ?>" />
	<?php foreach ( $panes as $pane ) {
		echo $pane['body'];
	} ?>
	<?php do_action('gb_checkout_form_'.$current_page); ?>
	<?php do_action('gb_checkout_form'); ?>
	 <?php
$checkout_chart = Group_Buying_Checkouts::get_instance( $checkout);
$cart = $checkout_chart->get_cart();
$chargetotal = $cart->get_total();
$subtotal = $cart->get_subtotal();

?> 
		<input size="50" type="hidden" name="paymentMethod" value="V"/>
	<input type="hidden" name="authenticateTransaction" value="false" />
	<input size="50" type="hidden" name="txntype" value="sale"/>
<<<<<<< HEAD
	<input type="hidden" name="responseSuccessURL" value="http://vps-1083582-7290.manage.myhosting.com/foodmonster/checkout" />
	<input type="hidden" name="responseFailURL" value="http://vps-1083582-7290.manage.myhosting.com/foodmonster/checkout" />
=======
	<input type="hidden" name="responseSuccessURL" value="http://vps-1083582-7290.manage.myhosting.com/foodmonster/pgw/success.php" />
	<input type="hidden" name="responseFailURL" value="http://vps-1083582-7290.manage.myhosting.com/foodmonster/checkout" />
	<input size="50" type="hidden" name="txndatetime" value="<?php echo getdatetime() ?>" />
	<input size="50" type="hidden" name="hash" value="<?php echo createhash($chargetotal) ?>" />
	<input size="50" type="hidden" name="storename" value="<?php echo getstore() ?>"/>
>>>>>>> 1d1087373a6051439382e20aea22d8313bdc0827
	<input size="50" type="hidden" name="chargetotal" value="<?php  echo $chargetotal ?>"/>
	<input size="50" type="hidden" name="subtotal" value="<?php  echo $subtotal ?>"/>
		<input size="50" type="hidden" name="trxOrigin" value="ECI"/>
			<input size="50" type="hidden" name="mode" value="payonly"/>

</form>