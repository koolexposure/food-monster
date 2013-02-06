<?php
$categories = array();
foreach ( $options as $term ) {
	$term_ob = get_term_by( 'slug', $term, gb_get_deal_location_tax() );
	$categories[] = $term_ob->name;
}
$option = implode( ", ", $categories ); ?>
<div id="mc_subscriptions" class="user_info clearfix">
	<p>
		<p><span class="contact_title"><?php gb_e( 'Daily E-Mails: ' ); ?></span>
		<?php echo $option; ?>
	</p>
</div>
