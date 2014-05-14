var loading_mask;
var current_tab = new Array();
var operations = new Array();
var grid_totals = new Array();
var intervals = new Array();

function ajaxGetPage(url,elem_id,get_elem,custom_animation,no_reset,no_bypass) {
	if (!no_reset) {
		if (intervals) {
			for (i in intervals) {
				clearInterval(intervals[i]);	
			}
			intervals = new Array();
		}
		
		if (operations) {
			for (i in operations) {
				clearInterval(operations[i]);
			}
			operations = new Array();
		}
		
		if (grid_totals) {
			for (i in grid_totals) {
				clearInterval(grid_totals[i]);
			}
			grid_totals = new Array();
		}
		passive_values = new Array();
	}
	$.ajax({
  		url: url,
  		beforeSend: function(){
     		ajaxLoading(elem_id);
     		if (!no_reset) {
     			ajaxHistoryAdd(url,elem_id);
     		}
   		},
  		success: function(html){
  			var path = '';
  			if (elem_id == 'edit_box' || elem_id == 'message_box' || elem_id == 'attributes box') {
  				$('#loading_mask').remove();
  				if (html.search('<div class="path">') > -1) {
     				var parts = html.split('<div class="path">');
     				var parts1 = parts[1].split('</div>');
					path = parts1[0];
					html = html.replace('<div class="path">'+path+'</div>','');
				}
				else if (html.search('<span id="edit_title">') > -1) {
     				var parts = html.split('<span id="edit_title">');
     				var parts1 = parts[1].split('</span>');
					path = parts1[0];
					html = html.replace('<span id="edit_title">'+path+'</span>','');
				}
     		}
  		
  			if (custom_animation)
  				ajaxAnimate(elem_id,html,custom_animation);
  			else {
  				if (elem_id == 'edit_box' || elem_id == 'message_box' || elem_id == 'attributes box')
  					$("#"+elem_id).find('.popup_content').html(html).css('display','');
     			else
     				$("#"+elem_id).html(html);
     		}
     		
     		if ($("#"+elem_id).is(':hidden')) {
     			if (elem_id == 'edit_box' || elem_id == 'message_box' || elem_id == 'attributes box') {
     				if (path.length > 0) {
     					$("#"+elem_id).find('.bpath').html(path);
     					$('.bpath').css('display','');	
     				}
     				else
     					$('.bpath').css('display','none');	
     					
     				$('#mask').fadeIn('slow');
     			}
     			
     			if (elem_id.search(/dropdown/i) < 0) {
	     			centerPopup(elem_id);
	     			$("#"+elem_id).fadeIn('slow');
	     			$("#"+elem_id+' form').submit(function() {
	     				$("#"+elem_id).fadeOut('slow');
	     			});
     			}
     		}
     		$('#'+elem_id+' form').submit(function() {
				ajaxLoading('content');
			});
   		},
   		data: ((get_elem) ? {bypass:1,target_elem:elem_id,field_name:$("#"+get_elem).attr('name'),field_value:$("#"+get_elem).attr('value')} : {bypass:((elem_id != 'edit_page' && !no_bypass) ? 1 : 0),target_elem:elem_id})
		
	});
	return false;
}

function ajaxLoading(elem_id) {
	var height = $('#'+elem_id).height();
	var width = $('#'+elem_id).width();
	
	if (!loading_mask) {
		var loading_animation = 'images/loading.gif';
	
		loading_mask = document.createElement("div");
		loading_mask.id = 'loading_mask';
		loading_mask.innerHTML = '<img src="' + loading_animation + '" />';
	}

	$(loading_mask).css('zoom',1);
	$('#'+elem_id).prepend(loading_mask);
	$(loading_mask).height(height);
	$(loading_mask).width(width);
	
	if ($('#pm_editor').is(':visible') && (elem_id == 'pm_wrap' || elem_id == 'content')) {
		ajaxPmUpdate();
	}
}

// to return values, put as [Return values:key1=val1|key2=val2,...]
function ajaxSave(query,url) {
	url = (url) ? url : 'ajax.save.php';
	var return_values = false;
	$.ajax({
  		url: url + '?' + query,
  		async:false,
  		success: function(html){
			if (html.search(/\[Return values:/) > -1) {
				var html1 = html.split('[Return values:');
				var html2 = html1[1].split(']');
				$('#ajax_save_response').html(html2[0]);
				html = html.replace(/\[Return values:/,'');
				html = html.replace(html2[0],'');
				html = html.replace(/\]/,'');
			}
  			messagePop(html);
   		},
   		error: function(status,error) {
   			errorPop(false,'Save failed');
   		}
	});
}

function ajaxGetVar(cfg_var) {
	$.ajax({
  		url: "ajax.get_var.php?cfg_var="+cfg_var,
  		async:false,
  		success: function(html){
			$('#ajax_save_response').html(html);
   		}
	});
}

function ajaxDeleteArray(items,delete_controls) {
	var query = 'action=delete';
	if (delete_controls) query += '&delete_controls=true';
	for (table in items) {
		for (id in items[table]) {
			query += '&rows['+table+']['+id+']='+id;
		}
	}
	ajaxSave(query);
}

function ajaxDelete(table,id,subtable,f_id_field,filename,dir) {
	subtable = (subtable == undefined) ? '' : subtable;
	f_id_field = (f_id_field == undefined) ? '' : f_id_field;
	filename = (filename == undefined) ? '' : filename;
	dir = (dir == undefined) ? '' : dir;
	
	ajaxSave('action=delete&table='+table+'&id='+id+'&subtable='+subtable+'&f_id_field='+f_id_field+'&filename='+filename+'&dir='+dir);
}

function ajaxDeleteFile(filename) {
	ajaxSave('action=delete_file&filename='+filename);
}

function scaleBackstage() {
	
}

var in_grid = 0;
function ajaxPmUpdate() {
	var query = '';

	if ($('.pm_class_container #form_table').length > 0 && !($('#dont_edit_table').length > 0)) {
		var table = $('.pm_class_container #form_table').attr('value');
		
		query += 'action=check_table&table='+table;
		$('.pm_class_container .form_db_field').each(function () {
			query += '&'+$(this).attr('name')+'='+$(this).attr('value');
		});
		$('.pm_class_container .form_radio_inputs').each(function () {
			query += '&'+$(this).attr('name')+'='+$(this).attr('value');
		});
	}
	$('.pm_class_container .method_id').each(function (i) {
		query += '&l_order[admin_controls_methods]['+i+']='+$(this).attr('value');
	});
	$('.pm_class_container').each(function (i) {
		query += '&l_order[admin_controls]['+i+']='+$(this).attr('id').replace('control_','');
	});
	
	if ($('.page_map').length > 0) {
		$('.page_map').find('.o').each(function(i) {
			var page_id = $(this).children('#id');
			if ($(page_id).attr('class') == 'method_id')
				query += '&page_map[methods]['+i+']='+$(page_id).attr('value');
			else
				query += '&page_map[pages]['+i+']='+$(page_id).attr('value');
		});
	}
	
	if (query.length > 0) 
		ajaxSave(query);
}

function ajaxHistoryStart() {
	$('body').append('<div style="display:none;" id="ajax_history"><input type="hidden" id="ajax_current_url" value=""  /></div>');
	$('body').append('<div style="display:none;" id="ajax_save_response"></div>');
}

function ajaxHistoryAdd(url,elem_id) {
	if (elem_id) {
		if (!(elem_id == 'content' || elem_id == 'pm_editor' || elem_id == 'body'))
			return false;
		
		window.location.hash = url+'/aid='+elem_id;
		$('#ajax_current_url').attr('value','#'+url+'/aid='+elem_id);
	}
	else {
		if (!(url.search(/content/i) > -1) && !(url.search(/pm_editor/i) > -1) && !(url.search(/body/i) > -1))
			return false;
		
		$('#ajax_current_url').attr('value',url);
	}
}

function ajaxHashChange() {
	ajaxGetPage(url,'main');
}

function ajaxUpdateHash(url) {
	window.location.hash = url;
}

function urlencode (str) {
    str = (str+'').toString();
    return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+').replace(/~/g, '%7E');
}

// delimiter is optional character
function preg_quote(str,delimiter) {
	return (str + '').replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
}

function footerToBottom(elem_id) {
	elem_id = (!elem_id) ? 'footer' : elem_id;
	var wh = $(window).height();
	var dh = $(document).height();
	if (dh <= wh) {
		$('#'+elem_id).addClass('force_bottom');
	}
	else {
		$('#'+elem_id).removeClass('force_bottom');
	}
}

$(document).ready(function() {
	ajaxHistoryStart();
	var ajax_int = setInterval(function() {
		if (!($('#ajax_current_url').length > 0)) {
			ajaxHistoryStart();
			ajaxHistoryAdd(window.location.hash);
			return false;
		}
		
		if (window.location.hash == $('#ajax_current_url').attr('value'))
			return false;
		
		if ($('#ajax_current_url').attr('value').search('%') > -1)
			return false;
		
		if (window.location.hash.length > 0) {
			var parts = window.location.hash.split('/aid=');
			if (parts[1]) {
				var target_elem = (parts[1] == 'pm_editor') ? 'body' : parts[1];
				var target_url = parts[0].replace('#','').replace('tab_bypass=1&','');
				ajaxGetPage(target_url,target_elem);
				$('#ajax_current_url').attr('value',window.location.hash);
			}
		}
		else {
			var parts = window.location.href.split('/');
			var c = parts.length - 1;
			var parts1 = $('#ajax_current_url').attr('value').split('/aid=');
			return false;
			if (parts[c] != 'index.php#') {
				ajaxGetPage(parts[c],parts1[1]);
				$('#ajax_current_url').attr('value',parts[c]);
			}
			else {
				ajaxGetPage('index.php',parts1[1]);
				$('#ajax_current_url').attr('value','index.php');
			}
		}
	},200);
	$('form').submit(function() {
		ajaxLoading('content');
	});
	$('body').click(function() {
		$('.cats_contain').css('display','none');
	});
	footerToBottom('credits');
});