<!-- connect.php -->
<HTML>
<head><title>FDGG Connect Sample for PHP</title></head> 
<script type="text/javascript">
function forward(){

/* For Merchant Test Environment (CTE) */
document.redirectForm.action="https://connect.merchanttest.firstdataglobalgateway.com/IPGConnect/gateway/processing";
/* For Production Environment (PROD) */
//document.redirectForm.action="https://connect.firstdataglobalgateway.com/IPGConnect/gateway/processing";
document.redirectForm.submit();

}
</script>
<BODY>

	<FORM method="post" id="redirectForm" name="redirectForm">
	<?php
	$mode = $_REQUEST["mode"];
	$chargetotal = $_REQUEST["chargetotal"];
	$subtotal = $_REQUEST["subtotal"];
	?>
	<?php

var_dump($_POST);

?>
	

	<input type="hidden" name="timezone" value="<?php echo 
	gettimezone() ?>" />
	<input type="hidden" name="authenticateTransaction" 
	value="false" />
	<input type="hidden" name="responseSuccessURL" 
	value="http://vps-1083582-7290.manage.myhosting.com/foodmonster/" />
	<input type="hidden" name="responseFailURL" 
	value="http://vps-1083582-7290.manage.myhosting.com/foodmonster/" />
	<input size="50" type="hidden" name="paymentMethod" value="<?php 
	echo $_REQUEST["paymentMethod"] ?>"/>
	<input size="50" type="hidden" name="txntype" value="<?php echo 
	$_REQUEST["txntype"] ?>"/>
	<input size="50" type="hidden" name="txndatetime" value="<?php 
	echo getdatetime() ?>" />
	<input size="50" type="hidden" name="hash" value="<?php echo 
	createhash($chargetotal) ?>" />
	<input size="50" type="hidden" name="mode" value="<?php echo 
	$mode ?>"/>
	<input size="50" type="hidden" name="storename" value="<?php 
	echo getstore() ?>"/>
	<input size="50" type="hidden" name="chargetotal" value="<?php 
	echo $chargetotal ?>"/>
	<input size="50" type="hidden" name="subtotal" value="<?php echo 
	$subtotal ?>"/>
	<input size="50" type="hidden" name="trxOrigin" value="<?php 
	echo $_REQUEST["trxOrigin"] ?>"/>
	<input size="50" type="hidden" name="oid" value="<?php echo 
	$_REQUEST["oid"] ?>"/>
	<input size="50" type="hidden" name="tdate" value="<?php echo 
	$_REQUEST["tdate"] ?>"/>
	</FORM>
	
	
	<?php header("Location: http://www.tripwiremagazine.com/"); ?>
	</BODY>
	</HTML>