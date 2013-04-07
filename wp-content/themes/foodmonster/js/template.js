jQuery(document).ready(function($) {
	// Get a reference to the container.
	var content = $("#content");
	
	


	// Bind the link to toggle the slide.
	$("#slide_btnbng").click(function () {
			$("#top_footer_wrap").slideToggle(2000, function() {
				$("#slide_btnbng").removeClass("slide_open").addClass("slide_closed");
			});

	});
});
