<script type="text/javascript">
	jQuery(document).ready( function($) {
		if ( $('#account-add-purchase select').length > 0 ) {
			var $deal_selector = $('#account-add-purchase select');
		} else {
			var $deal_selector = $('#account-add-purchase input');
		}
		var $attribute_selector = $('#account-add-purchase-attributes select');
		var $empty_option = $attribute_selector.find('option[value=0]').clone();
		var ajax_get_attributes = function( deal_id ) {

		};
		var load_attributes = function() {
			var current_deal = $deal_selector.val();
			$('#account-add-purchase-attributes-loader').slideDown();
			if ( !current_deal ) {
				$attribute_selector.val(0);
				$('#account-add-purchase-attributes-loader').slideUp();
				$('#account-add-purchase-attributes').slideUp();
				return;
			}
			$attribute_selector.attr('disabled', 'disabled');
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				data: {
					action: 'gbs_ajax_get_attributes',
					deal_id: current_deal
				},
				success: function(data) {
					if ( data.deal_id != $deal_selector.val() ) {
						return; // selection has changed since this request was sent
					}
					$attribute_selector.empty().append($empty_option.clone());
					if ( data.attributes.length < 1 ) {
						$('#account-add-purchase-attributes-loader').slideUp();
						$('#account-add-purchase-attributes').slideUp();
						return;
					}
					$.each(data.attributes, function(index, value) {
						$attribute_selector.append('<option value="'+index+'">'+value+'</option>');
					});
					$attribute_selector.removeAttr('disabled');
					$('#account-add-purchase-attributes-loader').slideUp();
					$('#account-add-purchase-attributes').slideDown();
				}
			});
		};
		if ( $('#account-add-purchase select').length > 0 ) {
			$deal_selector.change(load_attributes);
		} else {
			$deal_selector.live('keyup',load_attributes);
		}
	});
</script>
<style type="text/css">
	#account-add-purchase-attributes {
		padding-top: 1em;
		float: left;
	}
	.cloak {
		display: none;
	}
	#account-add-purchase-attributes-loader.loading_gif {
		background: url( '<?php echo GB_URL; ?>/resources/img/loader.gif') no-repeat 0 0;
		line-height: 32px;
		margin-left: 10px;
		margin-top: 10px;
	}
</style>
<div id="account-add-purchase-attributes-loader" class="loading_gif cloak">&nbsp;</div>
<div id="account-add-purchase-attributes" class="cloak">
	<label><?php self::_e( 'Attributes' ); ?>:
		<select name="<?php echo Group_Buying_Attributes::ATTRIBUTE_QUERY_VAR; ?>">
			<option value="0"> -- <?php self::_e( 'Select an Attribute' ); ?> -- </option>
		</select>
	</label>
</div>
