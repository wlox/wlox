function ml_save(elem) {
	var query = '';
	var i = 1;
	var j = 1;
	var set = $(elem).parents('.multi_list').find('.ml_li').each(function () {
		var table = $(this).siblings('#table').attr('value');
		var id = $(this).attr('id');
		var f_id = $(this).siblings('#f_id').attr('value');
		var p_id = $(this).siblings('#p_id').attr('value')
		
		if (query.length > 0) query += '&';
		
		query += 'rows['+i+'][table]=' + table + '&rows['+i+'][id]=' + id;
		
		if (f_id > 0)
			query += '&rows['+i+'][info][f_id]=' + f_id;
		if (p_id > 0)
			query += '&rows['+i+'][info][p_id]=' + p_id;
		
		query += '&l_order['+table+']['+i+']='+id;
		i++;
	});
console.log(query);
	ajaxSave(query);
}

function ml_expand(elem) {
	$(elem).siblings('ul,.add').show('slow');
	$(elem).siblings('.less').css('display','block');
	$(elem).css('display','none');
}

function ml_collapse(elem) {
	$(elem).siblings('ul,.add').hide('slow');
	$(elem).siblings('.more').css('display','block');
	$(elem).css('display','none');
}

function ml_disable(elem) {
	var items = $('#'+elem.parentNode.id+' > ul > ul').each(
		function() {
			var enabled = $('#'+this.id+' > #enabled');
			if (parseInt(enabled.attr('value')) > 0) {
				$(this).sortable("disable");
				enabled.attr('value',0);
				$('#dd_disabled').show();
				$('#dd_enabled').hide();
			}
			else {
				$(this).sortable("enable");
				enabled.attr('value',1);
				$('#dd_disabled').hide();
				$('#dd_enabled').show();
			}
		}
	);	
}

var delete_items = new Array();
function ml_delete(elem,li,is_sub,delete_controls) {
	ajaxGetVar('ajax_confirm_delete_sub');
	promptPop($('#ajax_save_response').html());
	var await_confirm = setInterval(function() {
		if (!prompt_is_open) {
			if (prompt_is_true) {
				var li = (li) ? $(li) : $(elem).closest('li');
				var ul = $(li).parent();
				var table = $(ul).children("#table").attr('value');
				var id = $(li).attr('id');
			
				if (!delete_items[table]) delete_items[table] = new Array();
				if (id.length > 0) delete_items[table][id] = id;
				
				var sub_ul = $(ul).children('ul').each(function () {
					if ($(this).children("#p_id").attr('value') == id) {
						$(this).children('li').each(function () {
							ml_delete(false,this,true);
						});
					}
				});
			
				$(li).remove();
				if (delete_controls) {
					var del_type = (table == 'admin_tabs') ? 'tab' : 'page';
					$('#del_'+del_type+'_'+id).remove();
				}
				
				if (!is_sub) {
					ajaxDeleteArray(delete_items,delete_controls);
					delete_items = new Array();
				}
				return true;
			}
			clearInterval(await_confirm);
		}
	},1000);
}

function deletePage(elem) {
	ml_delete(elem,false,false,true);
}
