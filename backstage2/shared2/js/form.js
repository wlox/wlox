
function fileInputReproduce(elem,max_iterations,from_url,tooltip_desc) {
	if (from_url && ($(elem).attr('value').length == 0))
		return false;

	if ($(elem).parents('.file_input_list').find('li').length < max_iterations) {
		var cur_id = $(elem).attr('id');
		var id_parts = cur_id.split('__');
		var field_name = id_parts[0];
		var clone = $(elem).parent().parent().clone(1);
		
		var rand = Math.floor(Math.random()*99+1);
		while ($(elem).parents('.file_input_list').find(field_name+'__'+rand).length > 0)
			rand = Math.floor(Math.random()*99+1);
		
		var clone_html = $(clone).html();
		var clone_html = clone_html.replace(/(_){1}([0-9]){1,2}/g,'_'+rand);
		$(clone).html(clone_html);
		$(clone).find('.fdesk').attr('value','');
		$(clone).find('.fdesk_img').attr('title',tooltip_desc);
		$(clone).find('.fdesk_img').fadeTo(0,0.6);
		$(clone).find('.file_input').replaceWith($(clone).find('.file_input').clone(true));
		$(clone).find('.input_cover').attr('value','');
		$(elem).parents('.file_input_list').append(clone);
	}
	fileInputText(elem);
}

function checkEmpty(elem,multiple,hidden_elem_id,run,is_tokenizer) {
	if (!run) {
		var hidden_elem_id = elem.id.replace('_dummy','');
		var elem_id = $(elem).attr('id');
		setTimeout("checkEmpty('"+elem_id+"','"+multiple+"','"+hidden_elem_id+"',1)",300);
	}
	else {
		var hidden_elem = $('#'+hidden_elem_id);
		var elem = $('#'+elem);
		var elem_value = $(elem).attr('value');
		
		if (multiple.length == 0) {
			if (isNaN(parseInt($(hidden_elem).attr('value')))) {
				$(hidden_elem).attr('value',elem_value);
			}
			else if ($(hidden_elem).attr('value').length == 0) {
				$(hidden_elem).attr('value','');
			}
		}
		else {
			if (is_tokenizer) {
				var values = new Array();
				$(elem).find('.token').each(function(i) {
					var j = $(this).children('#d0').attr('value');
					values[j] = $(this).children('span').html();
				});
			}
			else {
				var values = $(elem).attr('value').split(',');
			}

			if (!is_tokenizer) {
				var hidden_values = $(hidden_elem).attr('value').replace("array:","");
				var h_values = hidden_values.split('|||');
				var new_values = new Array();
			
				for (i in h_values) {
					var h_parts = h_values[i].split('|');
					var h_id = h_parts[0];
					var h_value = h_parts[1];
					
					for (j in values) {
						var v = jQuery.trim(values[j]);
						if (v == h_value && h_value.length > 0) {
							new_values[j] = h_id + '|' + h_value;
						}
					}
				}
			}
			else {
				var new_values = new Array();
				var i = 0;
				for (j in values) {
					new_values[i] = j + '|' + values[j];
					i++;
				}
			}
			console.log($(hidden_elem).attr('id'));
			if (new_values.length > 0) {
				var new_string = 'array:' + new_values.join('|||');
				$(hidden_elem).attr('value',new_string);
			}
			else {
				$(hidden_elem).attr('value','');
			}
		}
	}
}

function detectBackspace(e,elem) {
	var key;
	
	if(window.event)
		key = e.keyCode
	else if(e.which)
		key = e.which

	if (key == 8 && $(elem).attr('value') == '') {
		var hidden_elem_id = elem.id.replace('_dummy','');
		var parent = $(elem).parent().attr('id');
		$(elem).prev('.token').remove();
		checkEmpty(parent,1,hidden_elem_id,1,1);
	}
}

function removeThis(elem) {
	$(elem).parent().hide('fast',function(){
		var parent = $(elem).parents('.tokenizer').attr('id');
		var hidden_elem_id = $(elem).parents('.tokenizer').siblings('input').attr('id');
		$(elem).parent().remove();
		checkEmpty(parent,1,hidden_elem_id,1,1);
	});
}

function saveForm(elem,refresh_elem,refresh_query) {
	var query = '';
	closePopup(elem);
	$(elem).parents('form').eq(0).find(':input').each(function (i,input) {
		if ($(input).attr('type') == 'checkbox') {
			if ($(input).is(':checked')) {
				query += $(input).attr('name') + '=' + urlencode($(input).attr('value')) + '&';
			}
		}
		else {
			query += $(input).attr('name') + '=' + urlencode($(input).attr('value')) + '&';
		}
	});
	ajaxSave(query,'ajax.form.php');
	setTimeout("ajaxGetPage('"+refresh_query+"','"+refresh_elem+"')",400);
}

function formCancel(url,elem) {
	if ($(elem).parents('.popup').length > 0) {
		$(elem).parents('.popup').fadeOut('slow');
		$('#mask').fadeOut('slow');
	}
	else {
		ajaxGetPage('index.php?bypass=1&current_url='+url,'content');
	}
}

function formDeleteTemp(elem,filename) {
	ajaxGetVar('ajax_confirm_delete');
	promptPop($('#ajax_save_response').html());
	var await_confirm = setInterval(function() {
		if (!prompt_is_open) {
			if (prompt_is_true) {
				$(elem).parents('li').eq(0).remove();
				
				if (filename) {
					formShowFileInput(elem);
					ajaxDeleteFile(filename);
				}
			}
			clearInterval(await_confirm);
		}
	},1000);
}

function formDeleteUrl(elem,table,id) {
	ajaxGetVar('ajax_confirm_delete');
	promptPop($('#ajax_save_response').html());
	var await_confirm = setInterval(function() {
		if (!prompt_is_open) {
			if (prompt_is_true) {
				formShowFileInput(elem);
				$(elem).parents('li').eq(0).remove();
				ajaxDelete(table,id);
			}
			clearInterval(await_confirm);
		}
	},1000);
}

function formDeleteFile(elem,table,id,filename,dir) {
	ajaxGetVar('ajax_confirm_delete');
	promptPop($('#ajax_save_response').html());
	var await_confirm = setInterval(function() {
		if (!prompt_is_open) {
			if (prompt_is_true) {
				formShowFileInput(elem);
				$(elem).parents('li').eq(0).remove();
				ajaxDelete(table,id,false,false,filename,dir);
			}
			clearInterval(await_confirm);
		}
	},1000);
}

function formShowFileInput(elem) {
	var amount = $(elem).parents('.file_input_list').find('.amount').attr('value');
	var attached = $(elem).parents('.file_input_list').find('li').length - 1;
	if (attached <= amount)
		$(elem).parents('.file_input_list').find('li').removeClass('hidden');
}

function prepend(elem,text) {
	var value = $(elem).attr('value').replace(text,'');
	$(elem).attr('value',text + value);
}

function defaultText(elem,text) {
	if ($(elem).attr('value') == text) {
		$(elem).attr('value','');
	}
	else if ($(elem).attr('value') == '') {
		$(elem).attr('value',text);
	}
}

var new_multiple_i = 1;
function multipleNewInput(elem,is_colorpicker,elem_id,in_grid) {
	if (!in_grid) {
		var parent = $(elem).parents('ul');
		var add_elem = $(parent).children('.multiple_add');
	}
	else {
		var parent = $(elem).parents('.multiple_add').siblings('table');
		var add_elem = $(parent).find('tr:hidden');
	}
	
	if (is_colorpicker) {
		var html = $(parent).children('li:hidden').html();
		if (html) {
			var numbers = html.match(/[\d\.]+/g);
			if (numbers) {
				var rand = Math.floor(Math.random()*999);
				for (i in numbers) {
					if (html.search(numbers[i])) {
						html = html.replace(numbers[i],rand);
					}
				}
				
				$('<li style="display:none;"></li>').insertBefore(add_elem).html(html).show('slow');
				
				$("#"+elem_id+rand).ColorPicker({
					onChange: function (hsb, hex, rgb) {
						$('#'+elem_id+rand+'_color').css('backgroundColor', '#' + hex);
						$('#'+elem_id+rand).attr("value",'#' + hex);
					}
				});
			}
		}
	}
	else {
		if (!in_grid) {
			var clone = $(parent).children('li:hidden').clone();
			var html = $(clone).html();
			var numbers = html.match(/[\d\.]+/g);
			if (numbers.length > 0) {
				var new_number = parseInt(numbers[0]) + new_multiple_i;
				html = html.replace(new RegExp(numbers[0], 'g'),new_number);
				$(clone).html(html);
			}
			
			$(clone).show('slow');
			$(clone).insertBefore(add_elem);
		}
		else {
			var clone = $(parent).find('tr:hidden').clone();
			var html = $(clone).html();
			var numbers = html.match(/[\d\.]+/g);
			if (numbers.length > 0) {
				var new_number = parseInt(numbers[0]) + new_multiple_i;
				html = html.replace(new RegExp(numbers[0], 'g'),new_number);
				$(clone).html(html);
			}
			
			$(clone).css('display','');
			$(clone).insertBefore(add_elem);
			
			if (grid_totals) {
				for (input_name in grid_totals) {
					clearInterval(intervals['total_'+input_name]);
					formGridTotal(input_name);
				}
			}
			if (operations) {
				for (id in operations) {
					operation(operations[id]['operation'],operations[id]['variable_names'],operations[id]['form_name'],operations[id]['id'],new_number,operations[id]['grid_input']);
				}
			}
			
			new_multiple_i++;
		}
	}
}

function multipleRemoveInput(elem,in_grid) {
	if (!in_grid) {
		if ($(elem).parents('.file_input_list').find('.file_input_container').length > 1) {
			$(elem).parent().hide('slow',function(){
				$(elem).parent().remove();
			});
		}
		else {
			$(elem).parent().find('input:not(:submit,:button)').each(function() {
				$(this).attr('value','');
				$(this).attr('checked','');
			});
			$(elem).parent().find('select').each(function() {
				$(this).children('option:first').attr('selected','selected');	
			});
		}
	}
	else {
		if ($(elem).parents('table').find('tr:visible').length > 0) {
			$(elem).parents('tr').hide('slow',function(){
				$(elem).parents('tr').remove();
			});
		}
		else {
			$(elem).parents('tr').find('input:not(:submit,:button)').each(function() {
				$(this).attr('value','');
				$(this).attr('checked','');
			});
			$(elem).parents('tr').find('select').each(function() {
				$(this).children('option:first').attr('selected','selected');	
			});
		}
	}
}

function fileInputText(elem) {
	var val = $(elem).val();
	$(elem).parent().children('.input_cover').attr('value',val);
}

var grid_totals = new Array();
function formGridTotal(input_name) {
	var inputs = new Array();
	$('#total_'+input_name).parents('table').find('input').each(function(i) {
		var id = $(this).attr('id');
		if (id.search(input_name) > -1) {
			inputs[i] = id;
		}
	});
	
	if (inputs.length > 0) { 
		grid_totals[input_name] = input_name;
		intervals['total_'+input_name] = setInterval(function() {
			var total = 0;
			for (j in inputs) {
				var a = ($('#'+inputs[j]).attr('value') > 0) ? parseFloat($('#'+inputs[j]).attr('value')) : 0;
				total += a;
			}
			$('#total_'+input_name).html(total.toFixed(2));
		},200);
	}
}

var passive_values = new Array();
function getPassiveValue(url,primary_id,get_id) {
	intervals[primary_id] = setInterval(function() {
		if ($('#'+primary_id).length > 0) {
			var value = $('#'+get_id).attr('value');
			if (value > 0 && value != passive_values[primary_id]) {
				ajaxGetPage(url+'&get_id='+value,primary_id,0,0,1);
				passive_values[primary_id] = value;
			}
		}
	},200);
}

// functions that can be used in the $jscript argument
var operations = new Array();
function operation(operation,variable_names,form_name,id,j,grid_input) {
	var primary_id = (j > 0) ? form_name+'_'+id+'_'+j+'_'+grid_input : form_name+'_'+id;
	intervals[primary_id] = setInterval(function() {
		var variables = new Array();
		for (name in variable_names) {
			if (j > 0)
				variables[name] = $('#'+form_name+'_'+id+'_'+j+'_'+name).attr('value');
			else
				variables[name] = $('#'+form_name+'_'+name).attr('value');
		}
		
		var value = eval(operation);
		$('#'+primary_id).attr("value",value.toFixed(2));
		
		operations[primary_id] = new Array();
		operations[primary_id]['operation'] = operation;
		operations[primary_id]['primary_id'] = primary_id;
		operations[primary_id]['variable_names'] = variable_names;
		operations[primary_id]['form_name'] = form_name;
		operations[primary_id]['id'] = id;
		operations[primary_id]['j'] = j;
		operations[primary_id]['grid_input'] = grid_input;
	},200);
}

function getValue(variable_names,primary_id) {
	intervals[primary_id] = setInterval(function() {
		for (name in variable_names) {
			var var_id = name;
		}

		if (var_id) {
			var elem = $('#'+var_id);
			var value = ($('#'+var_id).attr('nodeName') == 'INPUT' || $('#'+var_id).attr('nodeName') == 'SELECT') ? $(elem).attr('value') : $(elem).html();
			$('#'+primary_id).attr('value',value);
		}
	},200);
}
/*
function createRecord(url,is_tab,f_id_field,f_id,target_elem_id,record_id) {
	if (!target_elem_id) target_elem_id = 'content';
	ajaxGetPage('index.php?action=form&is_tab=1&current_url='+url+'&fill_elems['+f_id_field+']='+f_id,target_elem_id);
}
*/
function displayIf(variable_names,primary_id,desired_value,form_name) {
	intervals[primary_id] = setInterval(function() {
		for (name in variable_names) {
			var var_id = name;
		}
		if (var_id) {
			var primary = $('#'+primary_id).parents('li');
			var type = $('#'+form_name+'_'+var_id).attr('type');
			var elem = (type == 'radio') ? $('#'+form_name+'_'+var_id).parent().children('input:checked') : $('#'+form_name+'_'+var_id);
			
			var actual_value = ($(elem).attr('nodeName') == 'INPUT' || $(elem).attr('nodeName') == 'SELECT') ? $(elem).attr('value') : $(elem).html();
			if (actual_value)
				actual_value = actual_value.replace('"','');
				
			if (actual_value != desired_value) {
				if ($(primary).is(':visible'))
					$(primary).css('display','none');
			}
			else {
				if ($(primary).is(':hidden'))
					$(primary).css('display','');
			}
		}
	},200);
}

function normalizeName(name) {
	name.split(' ').join('_');
	return name.split('.').join('_');
}

function fieldZoomIn(elem_id,input_type,label_caption) {
	var val = urlencode($('#'+elem_id).attr('value'));
	ajaxGetPage('ajax.edit_zoom.php?elem_id='+elem_id+'&input_type='+input_type+'&label_caption='+label_caption+'&current_val='+val,'edit_box');
}

function fieldZoomOut(elem_id) {
	var zoom_val = $('#zoom_'+elem_id).attr('value');
	$('#'+elem_id).attr('value',zoom_val);
	
	if ($('#'+elem_id).siblings('img').length > 0) {
		if (zoom_val.length > 0) {
			$('#'+elem_id).siblings('img').fadeTo(0,1);
			$('#'+elem_id).siblings('img').attr('title',zoom_val);
		}
		else {
			$('#'+elem_id).siblings('img').removeClass('opaq');
		}
	}
	
	$('#edit_box').fadeOut('slow');
	$("#mask").fadeOut("slow");
}

function startFileSortable() {
	$(".file_input_list").sortable({
		items: "li",
		handle: ".file_move",
		cursor:"move",
		stop: function (e,ui) {
			var field_name = $(ui.item).siblings('.file_list_name').attr('value');
			var thumbs = $(ui.item).parents('form').find('#gallery_name_'+field_name).siblings("#thumbs");
			if ($(thumbs).length > 0) {
				var this_id = $(ui.item).attr('id');
				if ($(ui.item).prev('li').length > 0) {
					var last_id = $(ui.item).prev('li').attr('id');
					var last_elem = $(thumbs).find('#'+last_id);
					$(thumbs).find('#'+this_id).insertAfter(last_elem);
				}
				else {
					var elem = $(thumbs).find('#'+this_id);
					$(thumbs).children('ul').prepend(elem);
				}
			}
		}
	});
	$("#thumbs ul").sortable({
		items: "li",
		cursor:"move",
		helper:"clone",
		stop: function (e,ui) {
			var field_name = $(ui.item).parents('.image_gallery').children('.gallery_name').attr('value');
			var file_list = $(ui.item).parents('form').find('#file_list_'+field_name).parent(".file_input_list");
			if ($(file_list).length > 0) {
				var this_id = $(ui.item).attr('id');
				if ($(ui.item).prev('li').length > 0) {
					var last_id = $(ui.item).prev('li').attr('id');
					var last_elem = $(file_list).find('#'+last_id);
					$(file_list).find('#'+this_id).insertAfter(last_elem);
				}
				else {
					var elem = $(file_list).find('#'+this_id);
					$(file_list).prepend(elem);
				}
			}
		}
	});
}

function autoCompleteData(data) {
	var loaded = data.split("||");
	var values = new Array();
	for (i in loaded) {
		loaded1 = loaded[i].split("|");
		values[i] = new Array();
		values[i]["value"] = loaded1[0];
		values[i]["label"] = loaded1[1];
	}
	return values;
}

function split(val) {
	return val.split( /,\s*/ );
}
function extractLast(term) {
	return split(term).pop();
}

function openFauxSelect(elem,e) {
	if (e)
		e.stopPropagation();

	var dropdown = $(elem).find('.faux_dropdown');
	
	if ($(dropdown).hasClass('selected')) {
		$(dropdown).removeClass('selected');
		$('body').unbind('click.fauxSelect');
	}
	else {
		$('.faux_dropdown').removeClass('selected');
		$('body').unbind('click.fauxSelect');
		
		$(dropdown).addClass('selected');
		$('body').bind('click.fauxSelect',function() {
			openFauxSelect(elem);
		});
	}
}

function fauxSelect(elem,e,value) {
	if ($(elem).parent().attr('class') == 'paste')
		return false;
	else
		if (e)
			e.stopPropagation();
	
	$(elem).parent().removeClass('selected');
	var copy = $(elem).clone();
	$(elem).parent().siblings('.paste').html('');
	$(elem).parent().siblings('.paste').append(copy);
	$(elem).parent().siblings('.faux_value').attr('value',value);
	$(elem).parent().siblings('.faux_value').trigger("change");
}

function fauxMultiSelect(elem,e) {
	if (e) e.stopPropagation();
	
	var paste = $(elem).parents('.faux_select').find('.paste');
	var selected = new Array();
	console.log($(elem).parents('.cats_ul').find('.faux_check').length);
	$(elem).parents('.cats_ul').find('.faux_check:checked').each(function(i) {
		selected[i] = $(this).siblings('label').html();
	});
	$(paste).html(selected.join(','));
}

function formReset(elem) {
	$(elem).parents('form').find(':input')
	.not(':button, :submit, :reset, :hidden')
	.attr('value','')
	.removeAttr('checked')
	.removeAttr('selected');
	$(elem).parents('form').find('option').removeAttr('selected').first().attr('selected','selected');
}

function formCatselPop(elem,e) {
	if (e) e.stopPropagation();
	
	$('.cats_contain').css('display','none');
	$(elem).siblings('.cats_contain').show(200);
	$(elem).parents('li').css('overflow','visible');
}

function formCatSelect(elem,e,in_popup,yes_label,no_label) {
	if (e) e.stopPropagation();
	
	if (!in_popup)
		return false;
	
	var cats_selected = $(elem).parents('.cats_ul').find('.checkbox_input:checked').length;
	
	if (cats_selected > 0)
		$(elem).parents('.cats_contain').siblings('.cats_selected').html(cats_selected+' '+yes_label);
	else
		$(elem).parents('.cats_contain').siblings('.cats_selected').html(no_label);
}

function formSelectTab(id,elem) {
	$(elem).parents('.area').find('.tab_area_container').css('display','none');
	$('#tab_area_'+id).css('display','block');
	$(elem).siblings('a').removeClass('visible');
	$(elem).addClass('visible');
}

function tabsSelectTab(url,target_elem_id,elem) {
	ajaxGetPage(url,target_elem_id);
	$(elem).siblings('a').removeClass('visible');
	$(elem).addClass('visible');
}
