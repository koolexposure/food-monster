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
<?php 
	foreach ($_POST as $key => $value)
	 echo "Field ".htmlspecialchars($key)." is ".htmlspecialchars($value)."<br>";
	
?>
	</BODY>
	</HTML>