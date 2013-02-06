<?php
	$dropdown = wp_dropdown_pages( array(
		'echo' => 0,
		'post_type' => Group_Buying_Deal::POST_TYPE,
		'show_option_none' => gb__( ' -- Select a Deal -- ' ),
		'name' => Group_Buying_Admin_Purchases::ADD_DEAL_ID_FIELD,
	) );
?>
<div id="account-add-purchase">
	<h4><?php gb_e( 'Add a new purchase' ); ?></h4>
	<?php if ( $dropdown != '' ): ?>
		<label style="margin-right: 25px;"><?php gb_e( 'Deal' ); ?>: <?php echo $dropdown; ?></label>
	<?php else: ?>
		<script type="text/javascript">
			jQuery(document).ready( function($) {
				var $field = $('#account-add-purchase input');
				var $span = $('#deals_name_ajax');
				
				var show_deal_name = function() {
					$span.addClass('loading_gif').empty();
					var user_id = $field.val();
					if ( !user_id ) {
						$span.removeClass('loading_gif');
						return;
					}
					$.ajax({
						type: 'POST',
						dataType: 'json',
						url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						data: {
							action: 'gbs_ajax_get_deal_info',
							id: user_id
						},
						success: function(data) {
							$span.removeClass('loading_gif');
							$span.empty().append(data.title + ' <span style="color:silver">(deal id:' + data.deal_id + ')</span>');
						}
					});
				};
				if ( $('#account-add-purchase input').length > 0 ) {
					$field.live('keyup',show_deal_name);
				}
			});
		</script>
		<style type="text/css">
			.loading_gif {
				background: url( '<?php echo GB_URL; ?>/resources/img/loader.gif') no-repeat 0 center;
				width: auto;
				height: 16px;
				padding-right: 16px;
				padding-bottom: 2px;
				margin-left: 10px;
				margin-top: 10px;
			}
		</style>
		<label style="margin-right: 25px;"><?php gb_e( 'Deal' ); ?>: <input type="text" size="8" name="<?php echo Group_Buying_Admin_Purchases::ADD_DEAL_ID_FIELD; ?>" placeholder="<?php gb_e('Deal ID') ?>" /></label>
	<?php endif ?>
	<br/><br/>
	<label><?php gb_e( 'Quantity' ); ?>: <input type="text" size="3" name="<?php echo Group_Buying_Admin_Purchases::ADD_DEAL_QUANTITY_FIELD; ?>" placeholder="0" /></label>
	<br/><span id="deals_name_ajax">&nbsp;</span>
</div>
