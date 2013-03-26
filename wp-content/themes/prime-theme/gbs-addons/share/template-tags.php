<?php 

function gb_get_sharethis_api() {
	return get_option( Group_Buying_Sharing::SHARETHISAPI );
}

function gb_sharethis_api() {
	echo gb_get_sharethis_api();
}