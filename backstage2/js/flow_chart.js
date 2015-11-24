var flow_chart = new Object();
flow_chart = {
	open: function(id) {
		var url = $('#navigator #table').attr('value');
		ajaxGetPage('index.php?current_url='+url+'&id='+id+'&action=record','edit_box');
	},
	addStep: function() {
		var url = $('#table').attr('value');
		ajaxGetPage('index.php?current_url='+url+'&bypass_save=1&action=form','edit_box');
	},
	saveOrder: function() {
		var is_tab = $('#navigator #is_tab').attr('value');
		var url = $('#navigator #current_url').attr('value');
		var table = $('#navigator #table').attr('value');
		var query = 'action=save_order&table='+table;
		var steps = $('#navigator .step_container').each(function(i) {
			query += '&rows['+i+']='+($(this).find('#id').attr('value'));
		});
		ajaxSave(query,'ajax.flow_chart.php');
	},
	select: function(elem,event) {
		event.stopPropagation();
		flow_chart.unselect();
		
		$(elem).parent('.step_container').addClass('ui-selected');
		flow_chart.showMenu(elem);
	},
	unselect: function() {
		flow_chart.hideMenu();
		$('#navigator').find('.step_container').removeClass('ui-selected');
	},
	showMenu: function(elem) {
		flow_chart.hideMenu();
		$(elem).siblings('.ops').fadeIn(400);
	},
	hideMenu: function() {
		$('#navigator').find('.ops').fadeOut(400);
	},
	deleteThis: function (id,table,elem) {
		ajaxGetVar('ajax_confirm_delete');
		promptPop($('#ajax_save_response').html());
		var await_confirm = setInterval(function() {
			if (!prompt_is_open) {
				if (prompt_is_true) {
					var query = 'action=delete';
					query += '&rows['+table+']['+id+']='+id;
					$(elem).parent().parent().remove();
					
					ajaxSave(query);
				}
				clearInterval(await_confirm);
			}
		},1000);
	},
	startSortable: function() {
		$('.step_container').draggable({
			opacity: 0.7,
			helper: 'clone',
			start: function(event,ui) {
				$(this).css('display','none');
			},
			stop:function(event,ui) {
				$(this).css('display','');
			}
		});
		$('.step_container').droppable({
			accept:'.step_container',
			over:function(event,ui) {
				$(this).after('<div id="drop_placeholder"></div>');
				$(ui.draggable).after(this);
			},
			out:function(event,ui) {
				$('#drop_placeholder').before(this);
				$('#drop_placeholder').remove();
			},
			drop:function(event,ui) {
				$('#drop_placeholder').after(ui.draggable);
				$('#drop_placeholder').remove();
			}
		});
	}
}
