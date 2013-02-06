jQuery(document).ready( function($) {
	$('select.fulfillment-status').change( function() {
		var select = $(this);
		var new_status = select.val();
		var post_id = select.siblings('input.fulfillment-status-purchase-id').val();

		select.attr('disabled', 'disabled');

		$.post(ajaxurl,
			{
				action: 'gbs_fulfillment_status',
				purchase_id: post_id,
				status: new_status
			},
			function( data ) {
				select.val(data.status);
				select.removeAttr('disabled');
			}
		);
	});

	$('#gb_deal_limits').find('.inventory-notifications').each( function() {
		var div = $(this);
		if ( div.find('#inventory-notification-toggle:checked').length < 1 ) {
			div.find('.inventory-notification-options').hide();
		}
		div.find('#inventory-notification-toggle').click( function() {
			if ( this.checked ) {
				div.find('.inventory-notification-options').slideDown();
			} else {
				div.find('.inventory-notification-options').slideUp();
			}
		});
	});
});