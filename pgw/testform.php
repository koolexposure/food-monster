<!-- connect.php -->
<?php include("fdgg-util_sha2.php"); ?>
<HTML>
<head><title>FDGG Connect Sample for PHP</title></head> 


<BODY>	
	
<?php if ($_REQUEST["identifier"]== NULL ) { ?> 
<P>
<H1>Order Form </H1>
<!--<FORM action="/connect_p.php" method=post name="mainform"><BR>-->
<FORM action="http://localhost:8888/foodmonster/pgw/success.php" method="post" 
name="mainform"><BR>
<TABLE border=0>
<TBODY>
<TR>
<TD>Transaction Type</TD>
<TD>
<INPUT type=radio CHECKED value=sale name=txntype>Sale<BR>
<INPUT type=radio value=preauth name=txntype>Authorize 
Only<BR>
<INPUT type=radio value=postauth name=txntype>Ticket 
Only<BR>
<INPUT type=radio value=void name=txntype>Void<BR>
</TD>
</TR>
<TR>
<TD>* Credit Card Type</TD>
<TD><SELECT size=1 name=paymentMethod> <OPTION value=V 
selected>Visa</OPTION>
<OPTION value=M>MasterCard</OPTION> <OPTION 
value=A>American
Express</OPTION> <OPTION value=D>Discover</OPTION> <OPTION 
value=J>JCB</OPTION> <OPTION value=9>Check</OPTION>
<OPTION value="">Other</OPTION>
</SELECT></TD> 
<TR>
<TR>
<TD>* Payment Mode:</TD>
<TD><SELECT name=mode> <OPTION value=payonly 
selected>PayOnly</OPTION> 
<OPTION value=payplus>PayPlus</OPTION> <OPTION 
value=fullpay>FullPay</OPTION> <OPTION 
value=""></OPTION></SELECT> </TD></TR>
<TR>
<TD>Transaction Origin</TD>
<TD>
<INPUT type=radio value=RETAIL name=trxOrigin>RETAIL<BR>
<INPUT type=radio value=MOTO name=trxOrigin>MOTO<BR>
<INPUT type=radio CHECKED value=ECI name=trxOrigin>ECI<BR>
</TD>
</TR>
<TR>
<TD>OrderId</TD>
<td>
<input type="text" name="oid" value=""/>
</td>
</TR>
<tr>
<td>Transaction Date</td>
<td>
<input type="text" name="tdate" value=""/>
</td>
</tr>
<TR>
<TD>* Charge Total:</TD>
<TD><INPUT value=11.00 name=chargetotal> </TD></TR>
<TR>
<TD>* Sub Total:</TD>
<TD><INPUT value=11.00 name=subtotal> </TD></TR>
<TR>
<TD></TD></TR>
<TR>
<TD></TD></TR>
<TR>
<TD align=middle colSpan=2><INPUT type=submit value="Submit 
This Form" name=submitBtn></TD></TR></TBODY></TABLE>
<input type="hidden" name="identifier" value="true" />
</FORM>
<?php } else {?>
	
	<FORM method="post" id="redirectForm" name="redirectForm">
	<?php
	$mode = $_REQUEST["mode"];
	$chargetotal = $_REQUEST["chargetotal"];
	$subtotal = $_REQUEST["subtotal"];
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
	<?php } ?> 
	</BODY>
	</HTML>