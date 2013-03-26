<?php foreach ( $panes as $pane ) {
	echo $pane['body'];
}
do_action( 'gb_checkout_form_'.$current_page );
do_action( 'gb_checkout_form' );
