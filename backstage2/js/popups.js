function errorPop(processed,unprocessed) {
	if (processed) {
		var error = processed;
	}
	else {
		var error = '<span class="bigger">'+unprocessed+'</span>';
	}
	$("#error_box").find('.popup_content').html(error);
	centerPopup('error_box');
	$("#mask").fadeIn("slow");
	$("#error_box").fadeIn("slow");
}

function messagePop(processed,unprocessed) {
	if (processed) {
		message = processed;
	}
	else {
		message = '<span class="bigger">'+unprocessed+'</span>';
	}
	$("#message_box").find('.popup_content').html(message);
	centerPopup('message_box');
	$("#mask").fadeIn("slow");
	$("#message_box").fadeIn("slow");
}

var prompt_is_true;
var prompt_is_open;
function promptPop(message) {
	prompt_is_true = false;
	prompt_is_open = true;
	$("#prompt_box").find('.popup_content').html(message);
	centerPopup('prompt_box');
	$("#mask").fadeIn("slow");
	$("#prompt_box").fadeIn("slow");
	//$("#ok_button").click(function () { ajaxGetPage('index.php?'+query,parent_id); $("#message_box").fadeOut('slow'); });
	//$("#cancel_button").click(function () { $("#message_box").fadeOut('slow'); });
}

function closePopup(elem,confirm_true) {
	$(elem).parents('.popup').fadeOut('slow');
	prompt_is_true = confirm_true;
	prompt_is_open = false;
	if ($('.popup:visible').length > 1)
		return false;
	else
		$('#mask').fadeOut('slow');
}

function centerPopup(elem_id) {
	var elem_height = $('#'+elem_id).height();
	var elem_width = $('#'+elem_id).width();
	var window_height = $(window).height();
	var window_width = $(window).width();
	var scroll_top = $(window).scrollTop();
	var scroll_left = $(window).scrollLeft();
	var top_px = (elem_height > window_height) ? scroll_top : (window_height/2)+scroll_top;
	var margin_top_px = (elem_height > window_height) ? 0 : elem_height/2;
	$('#'+elem_id).css('top',top_px+'px');
	$('#'+elem_id).css('margin-top','-'+margin_top_px+'px');
	$('#'+elem_id).css('left',((window_width/2)+scroll_left)+'px');
	$('#'+elem_id).css('margin-left','-'+(elem_width/2)+'px');
}

$(document).ready(function() {
	$(".popup").draggable({
		cursor: "move",
		handle: ".popup_bar"
	});
});