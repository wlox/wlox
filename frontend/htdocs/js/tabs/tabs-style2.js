$(document).ready(function() {

	//When page loads...
	$(".tab-content-two").hide(); //Hide all content
	$("ul.tabs-two li:first").addClass("active").show(); //Activate first tab
	$(".tab-content-two:first").show(); //Show first tab content

	//On Click Event
	$("ul.tabs-two li").click(function() {

		$("ul.tabs-two li").removeClass("active"); //Remove any "active" class
		$(this).addClass("active"); //Add "active" class to selected tab
		$(".tab-content-two").hide(); //Hide all tab content

		var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to identify the active tab + content
		$(activeTab).fadeIn(); //Fade in the active ID content
		return false;
	});

});