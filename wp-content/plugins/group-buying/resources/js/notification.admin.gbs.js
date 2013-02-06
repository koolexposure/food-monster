/**
 * UI enhancements for GBS Notification editor
 */

jQuery(document).ready(function($){
	$('#notification_type_disabled_wrap').hide();
	function show_hide_notification_type_descriptions( type ) {
		$('#advanced-sortables .postbox').each(function(){
			var $this = $(this);
			var id = $this.attr('id');
			// Only mess with shortcode meta boxes
			if ( id.match(/gb_notification_shortcodes_/) ) {
				// If it is the selected notification type, show it. Otherwise, hide it.
				if ( id.match( new RegExp('^gb_notification_shortcodes_' + type + '$') ) ) {
					$this.show();
					$('#notification_type_disabled_wrap').show();
				} else {
					$this.hide();
				}
			}
		});

		// Show and hide the appropriate notification type descriptions
		$('.notification_type_description').hide();
		$('#notification_type_description_' + type).show();
	}
	$('#notification_type').change(function(){
		show_hide_notification_type_descriptions( $(this).val() );
	});
	show_hide_notification_type_descriptions($('#notification_type').val());

	// This should really be in a stylesheet, but having a one-liner for that seemed silly
	$('.notification_type_description').css( { 'font-style' : 'italic' })
});
