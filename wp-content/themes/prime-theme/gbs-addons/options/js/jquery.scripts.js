jQuery(document).ready(function($) {
	// Loop through all color pickers and init the wpColorPicker
	$('.color_picker').each(function() {
		var picker = $(this);

		picker.wpColorPicker({
			change: function(event, ui) {
				// After every change callback to gb_iris_pickColor
				gb_iris_pickColor( picker, picker.wpColorPicker("color") );
				// Refresh the preview and generated css
			},
			clear: function() {
				// clear out the text area
				gb_iris_pickColor( picker, "" );
			}
		});
		// toggle text area when picked
		picker.click(gb_iris_toggle_text(picker));
		gb_iris_toggle_text(picker);
	});

	// set the input value
	function gb_iris_pickColor( e, color ) {
		e.val(color);
	}
	// toggle the text area with the color value
	function gb_iris_toggle_text(e) {
		var default_color = "000000"; // todo, should be value
		if ('' === e.val().replace('#', '')) {
			e.val(default_color);
			gb_iris_pickColor(e, default_color);
		} else {
			gb_iris_pickColor(e, e.val());
		}
	}
});

