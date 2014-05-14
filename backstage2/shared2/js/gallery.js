
function galleryNext(elem, i) {
	var items = $(elem).parent().children(".gallery_item");
	var max_index = parseInt(items.length) - 1;
	var next;
	var last_elem;
	var c_width = $(elem).parents('#display').width();
	var c_height = $(elem).parents('#display').height();
	$(elem).parents('#display').width(c_width).height(c_height);
	
	items.each(function() {
		if (this.style.display != 'none') {
			last_elem = this;
			
			if ((parseInt(this.id) + i) < 0)
				next = max_index; 
			else if ((parseInt(this.id) + i) > max_index)
				next = 0;
			else	
				next = parseInt(this.id) + i;
		}
	});
	
	var next_elem = items.parent().find('#'+next);
	var next_elem_id = $(next_elem).attr('id');
	
	if ($(elem).parents('#display').siblings('#thumbs').length > 0) {
		var thumbs = $(elem).parents('#display').siblings('#thumbs');
		$(thumbs).find('#'+next_elem_id).addClass('selected');
		$(thumbs).find('#'+next_elem_id).siblings('li').removeClass('selected');
	}
	
	$(last_elem).css('position','absolute').fadeOut(400);
	$(next_elem).css('position','absolute').fadeIn(400);
}

function galleryThumbsNext(elem, i, amount) {
	var items = $(elem).parent().parent().children(".gallery_thumb");
	var max_index = parseInt(items.length) - 1;
	var next;
	var last_elem;
	i = i * amount;

	items.each(function() {
		if (next == undefined) {
			if (this.style.display != 'none') {
				if ((parseInt(this.id) + i) < 0) {
					next = max_index + 1 - ((max_index + 1) % i); 
				}
				else if ((parseInt(this.id) + i) > max_index) {
					next = 0;
				}
				else {
					next = parseInt(this.id) + i;
				}
			}
		}
	});
	
	$(elem).parent().parent().children(".gallery_thumb:visible").fadeOut(200,function () {
		for (j=next;j<(amount + next);j++) {
			items.parent().find('#'+j).fadeIn(200);
		}
	});
}

function galleryShow(elem) {
	var items = $(elem).parents('#thumbs').siblings('#display').children('.gallery_item');
	var max_index = parseInt(items.length) - 1;
	var next = elem.id;
	var last_elem;
	
	var c_width = $(items).parents('#display').width();
	var c_height = $(items).parents('#display').height();
	$(items).parents('#display').width(c_width).height(c_height);
	
	items.each(function() {
		if (this.style.display != 'none') {
			last_elem = this;
		}
	});
	
	$(elem).addClass('selected');
	$(elem).siblings('li').removeClass('selected');
	$(last_elem).css('position','absolute').fadeOut(400);
	items.parent().find('#'+next).css('position','absolute').fadeIn(400);
}

function galleryShowField(field_name) {
	if ($('.gf_'+field_name).length > 0) {
		$('.gallery_item').fadeOut(200);
		$('.gf_'+field_name+':first').fadeIn(200);
	}
}

function thumbsResize(gal_i) {
	var avail_width =  $('#image_gallery_'+gal_i).width() - $('#image_gallery_'+gal_i).find('#display').outerWidth() - 80;
	if (avail_width > 320)
		$('#image_gallery_'+gal_i).find('#thumbs').width(320);
	else if (avail_width < 132)
		return false;
	else
		$('#image_gallery_'+gal_i).find('#thumbs').width(avail_width);
}
