
function peCycle(elem) {
	var id = $(elem).attr('id');
	var i = parseInt(id.replace('pe_',''));
	var j = (i == 2) ? 0 : i + 1;
	
	$(elem).attr('class','pe_icon');
	$(elem).siblings('#pe_'+j).attr('class','pe_icon_visible');
	$(elem).siblings('input').attr('value',j);
	
	$(elem).parent().next('ul').children('li').children('input').each(function () {
		if (parseInt($(this).attr('value')) > j) {
			$(this).siblings('.pe_icon_visible').attr('class','pe_icon');
			$(this).siblings('#pe_'+j).attr('class','pe_icon_visible');
			$(this).attr('value',j);
		}
	});;
}