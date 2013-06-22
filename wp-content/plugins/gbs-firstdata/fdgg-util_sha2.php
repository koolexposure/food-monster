<?php

$storename="1909379632";
$sharedsecret="30393731383836393938303437333732313231363736303731383030333131303336313331323837313133303630313438383539383836373732393137383030";

$timezone="EST";



function getdatetime() {

  global $datetime;
  return $datetime;
}

function gettimezone() {
  global $timezone;
  return $timezone;
}

function getstore() {
  global $storename;
  return $storename;
}

function createhash($chargetotal) {
  global $storename, $sharedsecret;
  $str = $storename . getdatetime() . $chargetotal . $sharedsecret;
  for ($i=0;$i<strlen($str);$i++) {
    $hexstr .= dechex(ord($str[$i]));
  }
  return hash('sha256', $hexstr);
}

?>