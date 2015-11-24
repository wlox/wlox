
function showPanel(id) {
	$('.panel_container').hide();
	$('#'+id).show();
}

function pmControl(elem,order) {
	var class1 = $(elem).attr('id');
	var page_id = $('#pm_page_id').attr('value');
	var action = $('#pm_action').attr('value');
	var is_tab = $('#pm_is_tab').attr('value');
	var field_name = (is_tab == '1') ? 'tab_id' : 'page_id';
	
	ajaxGetPage('index.php?tab_bypass=1&current_url=edit_page&action=add_class&class='+class1+'&form[class]='+class1+'&form['+field_name+']='+page_id+'&form[action]='+action+'&is_tab='+is_tab+'&pm_page_id='+page_id+'&pm_action='+action,'edit_box');
}

function pmMethod(elem,drop_elem,order) {
	var drop_elem_id = $(drop_elem).attr("id");
	var c_id = $('#'+drop_elem_id+'_id').attr('value');
	var class1 = $('#'+drop_elem_id+'_class').attr('value');
	var table = $('#'+drop_elem_id+'_table').attr('value');
	var method = $(elem).attr('id');
	var page_id = $('#pm_page_id').attr('value');
	var action = $('#pm_action').attr('value');
	var is_tab = $('#pm_is_tab').attr('value');
	var field_name = (is_tab == '1') ? 'tab_id' : 'page_id';
	var added_tables = '';
	order = (!isNaN(order)) ? order : '';
	if ($('.added_table').length > 0) {
		$('.added_table').each(function(i) {
			added_tables += '&added_tables[]='+$(this).attr('value');
		});
	}
	
	ajaxGetPage('index.php?tab_bypass=1&current_url=edit_page&action=add_class&class='+class1+'&method='+method+'&form[control_id]='+c_id+'&form[method]='+method+'&order='+order+'&pm_page_id='+page_id+'&pm_action='+action+'&control_id='+c_id+'&is_tab='+is_tab+'&c_table='+table+added_tables,'edit_box');
}

function pmSubMethod(elem,drop_elem) {
	var parent_method_id = $(drop_elem).find('#id').attr('value');
	var method = $(elem).attr('id');
	var page_id = $('#pm_page_id').attr('value');
	var action = $('#pm_action').attr('value');
	var is_tab = $('#pm_is_tab').attr('value');
	var field_name = (is_tab == '1') ? 'tab_id' : 'page_id';
	
	ajaxGetPage('index.php?tab_bypass=1&current_url=edit_page&action=add_class&method='+method+'&form[method]='+method+'&parent_method_id='+parent_method_id+'&pm_page_id='+page_id+'&pm_action='+action+'&is_tab='+is_tab,'edit_box');
}

function pmControlDelete(elem_id) {
	ajaxGetVar('ajax_confirm_delete');
	promptPop($('#ajax_save_response').html());
	var await_confirm = setInterval(function() {
		if (!prompt_is_open) {
			if (prompt_is_true) {
				var drop_elem_id = elem_id;
				var c_id = $('#'+drop_elem_id+'_id').attr('value');
				ajaxDelete('admin_controls',c_id,'admin_controls_methods','control_id');
				$('#'+drop_elem_id).hide(400);
			}
			clearInterval(await_confirm);
		}
	},1000);
}

function pmMethodDelete(elem,method_id,e) {
	if (!e) var e = window.event;
	e.cancelBubble = true;
	if (e.stopPropagation) e.stopPropagation();
	
	ajaxGetVar('ajax_confirm_delete');
	promptPop($('#ajax_save_response').html());
	var await_confirm = setInterval(function() {
		if (!prompt_is_open) {
			if (prompt_is_true) {
				ajaxDelete('admin_controls_methods',method_id);
				$(elem).parents('li').hide(200);
			}
			clearInterval(await_confirm);
		}
	},1000);
}

function pmControlEdit(elem_id) {
	var drop_elem_id = elem_id;
	var page_id = $('#pm_page_id').attr('value');
	var action = $('#pm_action').attr('value');
	var c_id = $('#'+drop_elem_id+'_id').attr('value');
	var class1 = $('#'+drop_elem_id+'_class').attr('value');
	var is_tab = $('#pm_is_tab').attr('value');
	var field_name = (is_tab == '1') ? 'tab_id' : 'page_id';
	
	ajaxGetPage('index.php?tab_bypass=1&current_url=edit_page&action=add_class&class='+class1+'&form[class]='+class1+'&form['+field_name+']='+page_id+'&form[action]='+action+'&id='+c_id+'&is_tab='+is_tab+'&pm_page_id='+page_id+'&pm_action='+action,'edit_box');
}

function pmMethodEdit(elem,e) {
	if (!e) var e = window.event;
	e.cancelBubble = true;
	if (e.stopPropagation) e.stopPropagation();

	var container_id = $(elem).parents(".pm_class_container").attr('id');
	var c_id = $('#'+container_id).children('#'+container_id+'_id').attr('value');
	var class1 = $('#'+container_id).children('#'+container_id+'_class').attr('value');
	var table = $('#'+container_id).children('#'+container_id+'_table').attr('value');
	var method = $(elem).siblings('#method').attr('value');
	var page_id = $('#pm_page_id').attr('value');
	var action = $('#pm_action').attr('value');
	var m_id = $(elem).siblings('#id').attr('value');
	var is_tab = $('#pm_is_tab').attr('value');
	var field_name = (is_tab == '1') ? 'tab_id' : 'page_id';

	if ($(elem).parents('.multiple_input').length > 0) {
		var parent_method_id = $(elem).parents('.multiple_input').children('.caption').children('#id').attr('value');
	}
	else if ($(elem).parents('.col').length > 0) {
		var parent_method_id = $(elem).parents('.col').attr('id');
		class1 = 'Form';
	}
	if (parent_method_id == undefined)
		parent_method_id = 0;
	
	ajaxGetPage('index.php?tab_bypass=1&current_url=edit_page&action=add_class&class='+class1+'&method='+method+'&pm_page_id='+page_id+'&pm_action='+action+'&control_id='+c_id+'&id='+m_id+'&form[control_id]='+c_id+'&form[method]='+method+'&is_tab='+is_tab+'&c_table='+table+'&parent_method_id='+parent_method_id,'edit_box');
}

function pmPageEdit(id,e) {
	if (!e) var e = window.event;
	e.cancelBubble = true;
	if (e.stopPropagation) e.stopPropagation();
	var page_id = $('#pm_page_id').attr('value');
	var action = $('#pm_action').attr('value');
	
	ajaxGetPage('index.php?current_url=edit_tabs&action=form&table=admin_pages&id='+id+'&pm_page_id='+page_id+'&pm_action='+action+'&from_editor='+'1','edit_box');
}

function pmSetTables(url,target_elem,elem) {
	setTimeout("ajaxGetPage('"+url+"','"+target_elem+"','"+$(elem).attr('id')+"')",300);
}

var ctrlDown = false;
function listenCopyPaste() {
    var ctrlKey = 17, vKey = 86, cKey = 67, zKey = 90;
    var clipboard;
    var clipboard_class;
    var last_cp;

    $(document).keydown(function(e){
        if (e.keyCode == ctrlKey) ctrlDown = true;
        if (ctrlDown && (e.keyCode == cKey)) {
        	if ($('#pm_editor').length > 0) {
        		if ($('.ui-selected.pm_class_container').length > 0) {
        			clipboard = $('.ui-selected.pm_class_container').clone(1);
        			clipboard_class = false;
        		}
        		else {
        			clipboard = $('.ui-selected').clone(1);
        			clipboard_class = $('.ui-selected:last').parents('.pm_class_container').find('.this_class').attr('value');
        		}
        	}
        }
        else if (ctrlDown && (e.keyCode == vKey)) {
        	if ($('#pm_editor').length > 0 && ($('.popup:visible').length == 0)) {
        		var class_cp = $(clipboard).attr('class');
        		var copy_cp = $(clipboard).clone(1);
        		if (copy_cp.length > 0) {
        			last_cp = copy_cp;
	        		$(copy_cp).each(function(i) {
		        		if ($('.pm_class_container.ui-selected').length > 0) {
		        			if (class_cp.search('pm_class_container') > -1) {
		        				reLabelControl(this);
		    					$('.pm_class_container.ui-selected:last').after(this);
		        			}
		        			else {
		        				var parent = $('.pm_class_container.ui-selected:last');
		        				if ($(parent).find('.this_class').attr('value') == clipboard_class) {
			        				reLabelMethod(this,$(parent).find('.this_id').attr('value'));
			    					$(parent).append(this);
		        				}
		        				else {
		        					ajaxGetVar('pm_copy_wrong_class');
		        					errorPop('<ul class="errors"><li>'+$('#ajax_save_response').html()+'</li></ul>');
		        				}
		        			}
		        		}
		        		else if ($('.ui-selected').length > 0) {
		        			if (class_cp.search('pm_class_container') > -1) {
		        				reLabelControl(this);
		    					$('.ui-selected:last').parents('.pm_class_container').after(this);
		        			}
		        			else {
		        				var parent = $('.ui-selected:last').parents('.pm_class_container');
		        				if ($(parent).find('.this_class').attr('value') == clipboard_class) {
			        				reLabelMethod(this,$(parent).find('.this_id').attr('value'));
			        				$('.ui-selected:last').after(this);
		        				}
		        				else {
		        					ajaxGetVar('pm_copy_wrong_class');
		        					errorPop('<ul class="errors"><li>'+$('#ajax_save_response').html()+'</li></ul>');
		        				}
		        			}
		        		}
		        		else {
		        			if (class_cp.search('pm_class_container') > -1) {
		        				reLabelControl(this);
		    					$('#pm_editor').append(this);
		        			}
		        		}
	        		});
        		}
        	}
        }
        else if (ctrlDown && (e.keyCode == zKey)) {
        	$(last_cp).remove();
        }
    }).keyup(function(e) {
        if (e.keyCode == ctrlKey) ctrlDown = false;
    });
}

function reLabelControl(elem) {
	var id = $(elem).find('.this_id').attr('value');
	var pm_page_id = $('#pm_editor').find('#pm_page_id').attr('value');
	var pm_action = $('#pm_editor').find('#pm_action').attr('value');
	var pm_is_tab = $('#pm_editor').find('#pm_is_tab').attr('value');

	ajaxSave('action=control&id='+id+'&pm_page_id='+pm_page_id+'&pm_action='+pm_action+'&pm_is_tab='+pm_is_tab,'ajax.copy_control.php');
	var return_vars = $('#ajax_save_response').html();
	if (return_vars.length == 0)
		return false;

	var return_vars1 = return_vars.split('|');
	for (i in return_vars1) {
		if (return_vars1[i].length > 0) {
			var n_vars = return_vars1[i].split('=');
			if (n_vars[0] == 'control_id') {
				var html = $(elem).html();
				$(elem).html(html.replace(new RegExp(id, 'g'),n_vars[1]));
				var id_attr = $(elem).attr('id');
				$(elem).attr('id',id_attr.replace(new RegExp(id, 'g'),n_vars[1]));
			}
			else {
				var label = n_vars[0].split('_');
				var method_id = $(elem).find('.method_id[value="'+label[1]+'"]');
				var html = $(method_id).parent().html();
				$(method_id).parent().html(html.replace(new RegExp(label[1], 'g'),n_vars[1]));
			}
		}
	}
}

function reLabelMethod(elem,control_id) {
	var id = $(elem).find('.method_id').attr('value');
	ajaxSave('action=method&id='+id+'&control_id='+control_id,'ajax.copy_control.php');
	var return_vars = $('#ajax_save_response').html();
	if (return_vars.length == 0)
		return false;

	var return_vars1 = return_vars.split('|');
	for (i in return_vars1) {
		if (return_vars1[i].length > 0) {
			var n_vars = return_vars1[i].split('=');
			var label = n_vars[0].split('_');
			var method_id = $(elem).find('.method_id[value="'+label[1]+'"]');
			var html = $(method_id).parent().html();
			$(method_id).parent().html(html.replace(new RegExp(label[1], 'g'),n_vars[1]));
		}
	}
}

function editorSynch() {
	ajaxGetVar('ajax_synch');
	promptPop($('#ajax_save_response').html());
	var await_confirm = setInterval(function() {
		if (!prompt_is_open) {
			if (prompt_is_true) {
				var pm_page_id = $('#pm_editor').find('#pm_page_id').attr('value');
				var pm_action = $('#pm_editor').find('#pm_action').attr('value');
				var pm_is_tab = $('#pm_editor').find('#pm_is_tab').attr('value');
				
				ajaxSave('action='+pm_action+'&pm_page_id='+pm_page_id+'&pm_action='+pm_action+'&pm_is_tab='+pm_is_tab,'ajax.synch_editor.php');
			}
			clearInterval(await_confirm);
		}
	},1000);
	
}

function openFauxSelect1(identifier,e) {
	if (e)
		e.stopPropagation();

	if ($('#faux_'+identifier).hasClass('selected')) {
		$('#faux_'+identifier).removeClass('selected');
		$('body').unbind('click.fauxSelect1');
	}
	else {
		$('#faux_'+identifier).addClass('selected');
		$('body').bind('click.fauxSelect1',function() {
			openFauxSelect(identifier);
		});
	}
}

function fauxSelect1(elem,e,identifier,id,is_tab) {
	if ($(elem).parent().attr('class') == 'paste')
		return false;
	else
		if (e)
			e.stopPropagation();
	
	$('#fauxs_'+identifier).removeClass('selected');
	var copy = $(elem).clone();
	$('#fauxs_'+identifier).find('.paste').html('');
	$('#fauxs_'+identifier).find('.paste').append(copy);
	is_tab = (is_tab == 1) ? 1 : '';
	ajaxGetPage('index.php?tab_bypass=1&current_url=edit_page&is_tab='+is_tab+'&id='+id,'pm_wrap');
	
	return false;
}

function pmExitEditor() {
	window.location = 'index.php?action=&is_tab='+$('#pm_is_tab').attr('value')+'&current_url=edit_tabs';
}

function pmOpenPage() {
	var page_id = $('#page_url').attr('value');
	var page_is_tab = $('#page_is_tab').attr('value');
	var page_action = $('#page_action').attr('value');
	var table = (page_is_tab > 0) ? 'admin_tabs' : 'admin_pages';
	
	if (page_id > 0)
		ajaxGetPage('index.php?table='+table+'&id='+page_id+'&action='+page_action+'&current_url=edit_page'+'&is_tab='+page_is_tab,'body');
}

function startEditor() {
	$(".pm_icon").draggable({
		revert: true,
		cursor: "move",
		opacity: 1,
		helper: 'clone',
		appendTo: 'body',
		connectToSortable: '.ui-sortable',
		start: function () { already = false; }
	});
	$("#pm_editor").droppable({
		accept: ".pm_control",
		hoverClass: "over",
		drop: function (e,ui) {
			pmControl(ui.draggable);
			$(ui.draggable).remove();
		}
	});
	$("#pm_editor").sortable({
		revert: false,
		cursor: "move",
		opacity: 1,
		handle: ".move_handle",
		stop: function(event,ui) {
			$("#pm_editor").selectable( "option", "disabled",false);
			$("#pm_editor").removeClass('over');
			$(".pm_class_container").removeClass('over');
			$(ui.item).remove();
		},
		containment: '#pm_editor'
	});
	$("#pm_editor").selectable({
		 filter: "li:not(.m_items),.pm_class_container,.grid_header th",
		 cancel: ".move_handle",
		 distance:5
	});
	$("#pm_editor").click(function(e) {
		e.stopPropagation();
		$("#pm_editor").find("*").removeClass("ui-selected");
	});
	$("#pm_editor .form ul li:not(.m_items),#pm_editor .record li:not(.m_items)").click(function(e) {
		e.stopPropagation();
		if (!ctrlDown) $("#pm_editor").find(".ui-selected").removeClass("ui-selected");
		$(this).addClass("ui-selected");
	});
	$(".pm_class_container").click(function(e) {
		e.stopPropagation();
		if (!ctrlDown) $("#pm_editor").find(".ui-selected").removeClass("ui-selected");
		$("#pm_editor").find(".ui-selected:not(.pm_class_container)").removeClass("ui-selected");
		$(this).addClass("ui-selected");
	});
	$(".grid_header th").click(function(e) {
		e.stopPropagation();
		if (!ctrlDown) $("#pm_editor").find("*").removeClass("ui-selected");
		$(this).addClass("ui-selected");
	});
	
	var already = false;
	$(".pm_class_container").droppable({
		accept: ".pm_icon",
		drop: function (e,ui) {
			var class_parts = $(ui.draggable).attr('class').split(' ');
			var class_name = class_parts[0].replace('pm_','');
			var order = false;
			if(($(this).find('.this_class').attr('value')) == class_name) {
				$(ui.draggable).parents('.pm_class_container').find('.method_id,.pm_icon').each(function(i) {
					if ($(this).hasClass('pm_icon') && !order) {
						order = i;
						return false;
					}
				});
				if (!already) {
					pmMethod(ui.draggable,$(this),order);
					already = true;
				}
			}
			$(ui.draggable).remove();
		},
		over: function (e,ui) {
			var class_parts = $(ui.draggable).attr('class').split(' ');
			var class_name = class_parts[0].replace('pm_','');
			
			if(($(this).find('.this_class').attr('value')) == class_name)
				$(this).addClass('over');
		},
		out: function (e,ui) {
			$(this).removeClass('over');
		}
	});
	$(".pm_class_container a").attr("href","#");
	$(".pm_class_container a:not(.dont_disable)").attr("onclick","");
	$(".pm_class_container input").attr("disabled",true);
	var fellows_before;
	var fellows_after;
	$(".pm_class_container ul:not(.cats_ul)").sortable({
		revert: true,
		cursor: "move",
		opacity: 1,
		handle: ".move_handle",
		start: function (e,ui) {
			fellows_before = $(ui.item).prevAll('.ui-selected');
			fellows_after = $(ui.item).nextAll('.ui-selected');
		},	
		stop: function (e,ui) {
			$(ui.item).before(fellows_before).addClass('ui-selected');
			$(ui.item).after(fellows_after).addClass('ui-selected');
			$('.ui-sortable-placeholder').remove();
		}
	});
	
	$(".page_map").sortable({
		revert: true,
		cursor: "move",
		opacity: 1,
		handle: ".move_handle"
	});
	$(".pm_class_container .m_items").sortable({
			revert: true,
			cursor: "move",
			opacity: 1,
			handle: ".move_handle",
			start: function (e,ui) {
				fellows_before = $(ui.item).prevAll('.ui-selected');
				fellows_after = $(ui.item).nextAll('.ui-selected');
			},	
			stop: function (e,ui) {
				$(ui.item).before(fellows_before).addClass('ui-selected');
				$(ui.item).after(fellows_after).addClass('ui-selected');
				$('.ui-sortable-placeholder').remove();
			}
	});
	$(".pm_class_container .grid_header").sortable({
			revert: true,
			cursor: "move",
			opacity: 1,
			handle: ".move_handle",
			start: function (e,ui) {
				fellows_before = $(ui.item).prevAll('.ui-selected');
				fellows_after = $(ui.item).nextAll('.ui-selected');
			},	
			stop: function (e,ui) {
				$(ui.item).before(fellows_before).addClass('ui-selected');
				$(ui.item).after(fellows_after).addClass('ui-selected');
				$('.ui-sortable-placeholder').remove();
			}
	});
	
	$(".multiple_input").droppable({
		accept: ".mult",
		hoverClass: "over",
		drop: function (e,ui) {
			pmSubMethod(ui.draggable,$(this));
		}
	});
	
	var pm_page_id = $('#pm_page_id').attr('value');
	var pm_is_tab = $('#pm_is_tab').attr('value');
	$('.menu_item > a').each(function(i) {
		$(this).unbind('click');
		
		if (i == 1)
			var action = 'form';
		else if (i == 2)
			var action = 'record';
		else
			var action = '';
			
		$(this).click(function() {
			ajaxGetPage('index.php?id='+pm_page_id+'&tab_bypass=1&is_tab='+pm_is_tab+'&action='+action+'&current_url=edit_page','pm_wrap',false,'');
		});
	});
}

$(document).ready(function(){
    listenCopyPaste();
});