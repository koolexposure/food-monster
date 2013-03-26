<?php
$csv = '';
$labels_array = array();
$records_array = array();

// CSV Headers
foreach ( $columns as $key => $label ) {
	$labels_array[] = $label;
}
$csv .= implode( ",", $labels_array )."\n";

// Records
foreach ( $records as $record ) { // Loop through each record
	foreach ( $columns as $key => $label ) { // order the records based on the columns
		$val = str_replace( '"', '""', $record[$key] );
		$records_array[] = '"'.$val.'"';
	}
	$csv .= implode( ",", $records_array )."\n";
	$records_array = null; // reset
}
// set headers
header( "Pragma: public" );
header( "Expires: 0" );
header( "Cache-Control: private" );
header( "Content-type: application/octet-stream" );
header( "Content-Disposition: attachment; filename=$filename" );
header( "Accept-Ranges: bytes" );

print $csv;
