
function gridSelectAll(elem) {
	if ($(elem).is(':checked')) {
		$('.grid_select').attr('checked','checked');
	}
	else {
		$('.grid_select').attr('checked','');
	}
}

function gridDeleteSelection(table) {
	ajaxGetVar('grid_delete_multiple');
	promptPop($('#ajax_save_response').html());
	var await_confirm = setInterval(function() {
		if (!prompt_is_open) {
			if (prompt_is_true) {
				var query = 'action=delete';
				$('.grid_select:checked').each(function (i) {
					var id = $(this).attr('value');
					query += '&rows['+table+']['+id+']='+id;
					$(this).parents('tr').remove();
				}); 
				ajaxSave(query);
			}
			clearInterval(await_confirm);
		}
	},1000);
}

function gridSetActive(table,active) {
	ajaxGetVar('grid_change');
	promptPop($('#ajax_save_response').html());
	var await_confirm = setInterval(function() {
		if (!prompt_is_open) {
			if (prompt_is_true) {
				var query = 'action=set_active&active='+active;
				$('.grid_select:checked').each(function (i) {
					var id = $(this).attr('value');
					query += '&rows['+table+']['+id+']='+id;
				}); 
				ajaxSave(query);
			}
			clearInterval(await_confirm);
		}
	},1000);
}

function gridDelete(id,table,elem) {
	ajaxGetVar('ajax_confirm_delete');
	promptPop($('#ajax_save_response').html());
	var await_confirm = setInterval(function() {
		if (!prompt_is_open) {
			if (prompt_is_true) {
				var query = 'action=delete';
				query += '&rows['+table+']['+id+']='+id;
				ajaxSave(query);
				$(elem).parents('tr').remove();
			}
			clearInterval(await_confirm);
		}
	},1000);
}

function gridSubmitForm(form_name) {
	$('#grid_form_'+form_name).submit();
}

function gridHighlightTH(elem) {
	$(elem).addClass('high');
	var num_td = $(elem).prevAll().length;
	$(elem).parents('table').find('tr').each(function(i){
		$(this).children('td').eq(num_td).addClass('high');
	});
}

function gridUnHighlightTH(elem) {
	$(elem).removeClass('high');
	$(elem).parents('table').find('td').removeClass('high');
}

function gridHighlightTD(elem) {
	$(elem).addClass('high1');
	$(elem).parents('tr').addClass('high');
	var num_td = $(elem).prevAll().length;
	$(elem).parents('table').find('th').eq(num_td).addClass('high');
	$(elem).parents('table').find('tr').each(function(i){
		$(this).children('td').eq(num_td).addClass('high');
	});
}

function gridUnHighlightTD(elem) {
	$(elem).removeClass('high1');
	$(elem).parents('tr').removeClass('high');
	$(elem).parents('table').find('td').removeClass('high');
	$(elem).parents('table').find('th').removeClass('high');
}