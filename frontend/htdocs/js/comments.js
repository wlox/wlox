function showComments(i,elem) {
	$(elem).siblings('a').show();
	$(elem).hide();
	$('#comments_'+i).show('normal');
}

function hideComments(i,elem) {
	$(elem).siblings('a').show();
	$(elem).hide();
	$('#comments_'+i).hide('normal');
}

function showReplyBox(id,i) {
	var html = $('#movable_form').html();
	$('.c_form').hide();
	$('#comment_'+id+' .c_form').show();
	$('#comment_'+id+' .c_form').html(html+'<div style="clear:both;height:0;"></div>');
	$('#comment_'+id+' #comments_'+i+'_p_id').attr('value',id);
}

function showDetails(elem) {
	$(elem).css('display','none');
	$(elem).siblings('a').css('display','');
	$(elem).parent().siblings('.details').show('normal');
}

function hideDetails(elem) {
	$(elem).css('display','none');
	$(elem).siblings('a').css('display','');
	$(elem).parent().siblings('.details').hide('normal');
}