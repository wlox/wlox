
function ajaxAnimate(elem_id,html,custom_animation) {
	switch(custom_animation) {
		case 'scroll_left':
			ajaxScrollLeft(elem_id,html);
		break;
		case 'scroll_right':
			ajaxScrollRight(elem_id,html);
		break;
		case 'stretch':
			ajaxStretch(elem_id,html);
		break;
		case 'move_heading':
			move_heading(elem_id,html);
		break;
		
	}
}

function ajaxScrollLeft(elem_id,html) {
	var elem = $('#'+elem_id);
	var width = $(elem).width();
	var clone = $(elem).clone();
	$(elem).parent().attr('class','anim_container');
	
	$(elem).attr('id','orig').css('position','absolute').css('top',0).css('left',0);
	$(clone).insertAfter(elem).html(html).css('position','absolute').css('top',0).css('left',width);
	
	$('.anim').animate({ left: '-='+width+'px' },1000,'swing',function() { 
		$(elem).parent().attr('class','');
		$(clone).css('position','');
		$(elem).remove();
	});
}

function ajaxScrollRight(elem_id,html) {
	var elem = $('#'+elem_id);
	var width = $(elem).width();
	var clone = $(elem).clone();
	$(elem).parent().attr('class','anim_container');
	
	$(elem).attr('id','orig').css('position','absolute').css('top',0).css('left',0);
	$(clone).insertBefore(elem).html(html).css('position','absolute').css('top',0).css('left',width);
	
	$('.anim').animate({ right: '-='+width+'px' },1000,'swing',function() { 
		$(elem).parent().attr('class','');
		$(clone).css('position','');
		$(elem).remove();
	});
}

// surround string to compare in <compare></compare> tags
function ajaxStretch(elem_id,html) {
	html = html;
	//alert(html);
	var c1 = html.split('<compare>');
	if (c1[1]) {
		var c2 = c1[1].split('</compare>');
		var comp1 = c2[0];
	}
	else {
		var c1 = html.split('<COMPARE>');
		var c2 = c1[1].split('</COMPARE>');
		var comp1 = c2[0];
	}
	
	var html1 = $('#'+elem_id).html();
	var c3 = html1.split('<compare>');
	if (c3[1]) {
		var c4 = c3[1].split('</compare>');
		var comp2 = c4[0];
	}
	else {
		var c3 = html1.split('<COMPARE>');
		var c4 = c3[1].split('</COMPARE>');
		var comp2 = c4[0];
	}
	
	if (comp1 != comp2) {		
		$('#'+elem_id).animate({ fontSize: '+=10px',opacity: '-=0.4' },600,'swing',function () {
			$('#'+elem_id).html(html);
			$('#'+elem_id).animate({ fontSize: '-=10px',opacity: '+=0.4' },600,'swing');
		});
	}
	else {
		$('#'+elem_id).html(html);
	}
}

function move_heading(elem_id,html) {
	$('#'+elem_id).html(html);
	document.title = $('.doc_title:last').attr('value') + ' | Organic Technologies';
	resizeBanner();
}
