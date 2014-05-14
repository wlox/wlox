var file_manager = new Object();
file_manager = {
	history: new Array(),
	current: 0,
	addHistory: function(id) {
		var c = file_manager.history.length;
		if (file_manager.current > 0 && file_manager.history.length > 0 && file_manager.history.length > (c+1)) {
			file_manager.history = file_manager.history.slice(file_manager.current);
			c = file_manager.history.length;
		}
		file_manager.history[c] = id;
		file_manager.current = c;
	},
	openFolder: function(id,show_path) {
		var url = $('#current_url').attr('value');
		var is_tab = $('#is_tab').attr('value');
		ajaxGetPage('index.php?current_url='+url+'&is_tab='+is_tab+'&current_id='+id+'&fm_bypass=1','navigator');
		if (show_path) {
			file_manager.showSubfolders(id,show_path);
		}
	},
	showSubfolders: function(id,elem) {
		var folder_table = $('#folder_table').attr('value');
		var subfolders = $(elem).parent().find('.folder_container');
		if (subfolders.length > 0) {
			$(subfolders).hide(200);
			$(subfolders).remove();
			var triangle = ($(elem).hasClass('triangle1')) ? elem : $(elem).siblings('#triangle');
			$(triangle).removeClass('triangle1');
			$(triangle).addClass('triangle');
		}
		else {
			$(elem).parent().append('<div id="expand_'+id+'" class="folder_container indent">');
			ajaxGetPage('ajax.file_manager.php?&p_id='+id+'&folder_table='+folder_table+'&action=expand','expand_'+id);
			var triangle = ($(elem).hasClass('triangle')) ? elem : $(elem).siblings('#triangle');
			$(triangle).removeClass('triangle');
			$(triangle).addClass('triangle1');
		}
	},
	next: function() {
		var url = $('#current_url').attr('value');
		var is_tab = $('#is_tab').attr('value');
		if (file_manager.history[file_manager.current+1]) {
			file_manager.current += 1;
			ajaxGetPage('index.php?current_url='+url+'&is_tab='+is_tab+'&current_id='+file_manager.history[file_manager.current]+'&fm_bypass=1&b_or_n=1','navigator');
		}
	},
	last: function() {
		var url = $('#current_url').attr('value');
		var is_tab = $('#is_tab').attr('value');
		file_manager.current = (file_manager.current > 0) ? file_manager.current - 1 : 0;
		ajaxGetPage('index.php?current_url='+url+'&is_tab='+is_tab+'&current_id='+file_manager.history[file_manager.current]+'&fm_bypass=1&b_or_n=1','navigator');
	},
	addFolder: function() {
		var folder_table = $('#folder_table').attr('value');
		var url = $("#folder_link").attr('value');
		ajaxGetPage('index.php?current_url='+folder_table+url+'&bypass_save=1','edit_box');
	},
	addFile: function(table) {
		var url = $("#"+table+"_link").attr('value');
		var target = $("#"+table+"_target").attr('value');
		var is_tab = $('#is_tab').attr('value');
		ajaxGetPage('index.php?'+url+'&is_tab='+is_tab+'&bypass_save=1',target);
	},
	setPath: function(path) {
		$('#fm_path').html(path);
	},
	select: function(elem,e) {
		if (!e) var e = window.event;
		e.cancelBubble = true;
		if (e.stopPropagation) e.stopPropagation();

		file_manager.unselect();
		if ($(elem).hasClass('search_container')) {
			$(elem).addClass('ui-selected');
		}
		else {
			$(elem).parent('.file_container,.folder_container').addClass('ui-selected');
		}
		file_manager.showMenu(elem);
	},
	unselect: function() {
		file_manager.hideMenu();
		$('.tree').find('.file_container,.folder_container,.search_container').removeClass('ui-selected');
		if (!($.browser.msie)) {
			$('#navigator').find('.file_container,.folder_container,.search_container').removeClass('ui-selected');
		}
		else {
			if ($('.search_container').length > 0)
				$('.search_container').removeClass('ui-selected');
		}
	},
	showMenu: function(elem) {
		if (!($(elem).parents('.tree') > 0)) {
			file_manager.hideMenu();
			$(elem).siblings('.ops').fadeIn(400);
		}
	},
	hideMenu: function() {
		$('#navigator').find('.ops').fadeOut(400);
	},
	showFile: function(url,id,is_tab,target) {
		ajaxGetPage('index.php?current_url='+url+'&id='+id+'&is_tab='+is_tab+'&action=record',target);
	},
	deleteThis: function (id,table,elem) {
		ajaxGetVar('ajax_confirm_delete');
		promptPop($('#ajax_save_response').html());
		var await_confirm = setInterval(function() {
			if (!prompt_is_open) {
				if (prompt_is_true) {
					var query = 'action=delete&sub_records=1';
					query += '&rows['+table+']['+id+']='+id;
					$(elem).parent().parent().remove();
					ajaxSave(query);
				}
				clearInterval(await_confirm);
			}
		},1000);
	},
	startSelectable: function() {
		var fellows;
		$('#navigator .file_container,#navigator .folder_container').draggable({
			revert: true,
			opacity: 0.7,
			drag: function(event,ui) {
				fellows = $('.ui-draggable-dragging').siblings('.ui-selected');
				$(fellows).css('top',ui.position.top+'px').css('left',ui.position.left+'px');
			},
			stop: function(event,ui) {
				$(fellows).animate({
					"left": "0px",
					"top": "0px"
				},400,'swing',function() { $(fellows).addClass('ui-selected'); });
			}
		});
		$(".folder_container").droppable({
			accept: '.file_container,.folder_container',
			hoverClass: 'hover',
			drop: function(event,ui) {
				var p_id = $(this).find('#id').attr('value');
				
				var is_file = (ui.draggable.hasClass('file_container'));
				var id = ui.draggable.find('#id').attr('value');
				var table = (is_file) ? ui.draggable.find('#table').attr('value') : $('#folder_table').attr('value');
				var folder_field = (is_file) ? ui.draggable.find('#folder_field').attr('value') : 'p_id';
				
				var files = '&rows['+id+'][p_id]='+p_id+'&rows['+id+'][table]='+table+'&rows['+id+'][folder_field]='+folder_field;
				
				if ($(fellows).length > 0) {
					$(fellows).each(function() {
						var id = $(this).find('#id').attr('value');
						var is_file = ($(this).hasClass('file_container'));
						var table = (is_file) ? $(this).find('#table').attr('value') : $('#folder_table').attr('value');
						var folder_field = (is_file) ? $(this).find('#folder_field').attr('value') : 'p_id';
						
						files += '&rows['+id+'][p_id]='+p_id+'&rows['+id+'][table]='+table+'&rows['+id+'][folder_field]='+folder_field;
					});
				}
				ajaxSave(files+'&action=drop','ajax.file_manager.php');
				ui.draggable.remove();
				$(fellows).remove();
			}
		});
		$("#navigator").selectable({
			filter: '.file_container,.folder_container',
			tolerance: 'touch'
		});
	},
	downloadResults: function() {
		var ids = '';
		var current_url = $('#current_url').attr('value');
		var table_fields = $('#table_'+current_url+'_fields').attr('value');
		$('.download_ids').each(function() {
			ids += '&download['+$(this).attr('id').replace('download_','')+']='+$(this).attr('value');
		});
		$('.fm_download_iframe').attr('src','ajax.file_manager.php?action=download&current_url='+current_url+'&table_fields='+table_fields+'&is_tab='+($('#is_tab').attr('value'))+ids);
	}
};
