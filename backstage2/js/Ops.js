var Ops = new Object();
Ops = {
	buttonDown:false,
	buttonDrop: function(elem,event) {
		Ops.menuDropOut();
		if (Ops.buttonDown) {
			Ops.buttonDropOut();
			Ops.buttonDown = false;
			return false;
		}
		event.stopPropagation();
		$(elem).find('.options').css('display','block');
		Ops.buttonDown = true;
	},
	buttonDropOut: function() {
		$('.nav_button .options').css('display','none');
	},
	menuTimeout: false,
	menuDrop: function(elem) {
		Ops.buttonDropOut();
		$('.menu_item .options').css('display','none');
		$('.menu_item > a').removeClass('high');
		$(elem).children('a').addClass('high');
		
		var drop = $(elem).find('.options');
		if (drop.length < 1)
			return false;
		
		$(drop).css('display','block');
		var offset_right = drop.offset().left + $(drop).children('.contain').width();
		if(offset_right > $(window).width()) {
			$(drop).css('right','0');
			$(drop).css('left','auto');
			$(drop).children('.contain').css('float','right');
		}
	},
	menuDropOut: function() {
		$('.menu_item .options').css('display','none');
		$('.menu_item > a').removeClass('high');
	},
	buttonOver: function(elem) {
		$(elem).addClass('high');
	},
	buttonOut: function(elem) {
		$(elem).removeClass('high');
	}
};

$(document).ready(function() {
	// menu functions
	$('body').click(function() {
		Ops.buttonDropOut();
		Ops.menuDropOut();
	});
	$('body').mouseover(function(event) {
		event.stopPropagation();
		clearTimeout(Ops.menuTimeout);
		if ($('.menu_item .options:visible').length > 0) {
			Ops.menuTimeout = setTimeout(function() {
				Ops.menuDropOut(1);
			},400);
		}
	});
	$('.nav_button').click(function(event) {
		Ops.buttonDrop(this,event);
	});
	$('.nav_button').mouseover(function() {
		$(this).addClass('high');
	});
	$('.nav_button').mouseout(function() {
		$(this).removeClass('high');
	});
	$('.menu_item').mouseover(function(event) {
		event.stopPropagation();
		clearTimeout(Ops.menuTimeout);
		Ops.menuDrop(this);
	});
});