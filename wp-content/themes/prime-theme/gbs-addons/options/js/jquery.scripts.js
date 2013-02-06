jQuery(document).ready(function($) {
	$('.color_picker').each(function(i) {
		var $this = $(this);
		var $input = $('#'+$this.attr('id')+'-color');
		$this.css('backgroundColor', '#' + $this.val());
		$this.ColorPicker({
			color : $this.val(),
			onShow : function(picker) {
				$(picker).fadeIn(500);
				return false;
			},
			onHide : function(picker) {
				$(picker).fadeOut(500);
				return false;
			},
			onChange : function(hsb, hex, rgb) {
				$input.val(hex);
				$this.css('backgroundColor', '#' + hex);
				jQuery($this).val(hex);
			}
		});
	});
});