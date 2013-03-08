jQuery(document).ready(function($) {


	// Get a reference to the container.
	var content = $("#content");


	// Bind the link to toggle the slide.
	$("#slide_btnbng").click(

	function() {

		$("#top_footer_wrap").slideToggle(2000);
	}, function() {
		//hide its submenu
		$("#top_footer_wrap").slideToggle(2000);

	});

});
