<form id="gb_checkout_<?php echo $current_page; ?>" action="<?php gb_checkout_url(); ?>" method="post">
	<input type="hidden" name="gb_checkout_action" value="<?php echo $current_page; ?>" />
	<?php foreach ( $panes as $pane ) {
		echo $pane['body'];
	} ?>
	<?php do_action('gb_checkout_form_'.$current_page); ?>
	<?php do_action('gb_checkout_form'); ?>
	


</form>