<?php
$storename = '1909379632'; // Replace with your Storenumber here
$sharedSecret = '30353335333430313737363830393733383933383239373333393835303737353938363630343335333633393131343534393535343233393936363837303233'; //Replace with your Shared Secret here
/* If you have below PHP version 5.1 OR Don't want to set the Default
TimeZone, then you have to do the following
cha
nges to set your server timeZone:
Example: If your server is in "PST" timezone, here are the changes:
//date_default_timezone_set("Asia/Calcutta"); // Comment this line
$timezone = "PST" // change to your server timeZone
*/
//date_default_timezone_set("Asia/Calcutta");
//$timezone = "IST";
date_default_timezone_set('America/New_York');
$timezone = 'EST';

/*
----
*/

$chargetotal = 20.00;
$dateTime = date("Y:m:d-H:i:s");
function getDateTime() {
global $dateTime;
return $dateTime;
}
function getTimezone() {
global $timezone;
return $timezone;
}
function getStorename() {
global $storename;
return $storename;
}
function createHash($chargetotal) {
global $storename, $sharedSecret;
$str = $storename . $dateTime . $chargetotal . $sharedSecret;
for ($i = 0; $i < strlen($str); $i++){
$hex_str =dechex(ord($str[$i]));
}
return hash('sha256', $hex_str);
}
?>