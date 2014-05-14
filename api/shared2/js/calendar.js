function synchCalDates(y,m,d) {
	$('#cal_y').attr('value',y);
	$('#cal_m').attr('value',m);
	$('#cal_d').attr('value',d);
}

function saveCalOrder() {
	var query = '';
	$('.event').each(function(i){
		var id = $(this).children('#id').attr('value');
		var f_id = $(this).children('#f_id').attr('value');
		var f_table_id = $(this).children('#f_table_id').attr('value');
		var total_y = $(this).children('#total_y').attr('value');
		var table = $(this).children('#table').attr('value');
		var f_id_field = $(this).children('#f_id_field').attr('value');
		var edate_field = $(this).children('#edate_field').attr('value');
		var sdate_field = $(this).children('#sdate_field').attr('value');
		
		query += 'rows['+i+'][id]='+id+'&rows['+i+'][f_id]='+f_id+'&rows['+i+'][f_table_id]='+f_table_id+'&rows['+i+'][total_y]='+total_y+'&rows['+i+'][table]='+table+'&rows['+i+'][f_id_field]='+f_id_field+'&rows['+i+'][edate_field]='+edate_field+'&rows['+i+'][sdate_field]='+sdate_field+'&';
	});
	ajaxSave(query,'ajax.cal.php');
	$('.total_y').attr('value',0);
}

function printCal(cal_id) {
	var url = $('.cal_print_link').attr('href');
	window.open(url);
}

function calCellHover(elem){
	$(elem).addClass('alt');
}

function calCellOut(elem){
	$(elem).removeClass('alt');
}

function calZoomDay(this_i,url,is_tab,y,m,d) {
	ajaxGetPage('index.php?is_tab='+is_tab+'&current_url='+url+'&cal_'+this_i+'_y='+y+'&cal_'+this_i+'_m='+m+'&cal_'+this_i+'_d='+d+'&cal_bypass=1&bypass=1&cal_'+this_i+'_mode=day','cal_'+this_i);
}

function initializeCalFloats() {
	var cur_pos_l;
	var cur_pos_t;
	var start_y;
	var siblings = new Array();
	var dragged = false;
	$(".floating").draggable({
		opacity:0.70,
		grid:[153, 50],
		scroll:true,
		containment:".all_floats",
		start:function(event, ui) {
			var new_pos = Math.round(parseInt($(this).css("top"))/50);
			var id = $(this).children("#id").attr("value");
			var table = $(this).children("#table").attr("value");
			start_y = $(this).css("top");
			siblings = new Array();
			
			$(this).css("zIndex","2");
			$(this).css("top",(new_pos*50)+"px");
			$(".calendar .floating").each(function(i) {
				if ($(this).children("#id").attr("value") == id && $(this).children("#table").attr("value") == table) {
					siblings[i] = this;
				}
			});
		},
		drag:function(event, ui) {
			cur_pos_t = $(this).css("top");
			cur_pos_l = $(this).css("left");
			for (i in siblings) {
				$(siblings[i]).css("top",cur_pos_t);
			}
		},
		stop:function(event, ui) {
			var new_pos = Math.round(parseInt($(this).css("top"))/50);
			$(this).css("top",(new_pos*50)+"px");
			$(this).css("zIndex","1");
			
			var end_y = $(this).css("top");
			var distance_y = parseInt(end_y.replace("px","")) - parseInt(start_y.replace("px",""));
			var accum_y = parseInt($(this).children("#total_y").attr("value").replace("px","")) + distance_y;
			$(this).children("#total_y").attr("value",accum_y);
			
			for (i in siblings){
				$(siblings[i]).css("top",(new_pos*50)+"px");
				$(siblings[i]).children("#total_y").attr("value",accum_y);
			}
		}
	});
	
	$(".float_contain").droppable({
		accept:".event",
		hoverClass: "cal_hover",
		tolerance:"touch",
		drop: function(event,ui) {
			$(ui.draggable).children("#f_id").attr("value",$(this).children("#record_id").attr("value"));
		}
	});
	// is intersecting?
	$(".floating").droppable({
		accept:".event",
		tolerance:"touch",
		drop: function(event,ui) {
			console.log($(this).css("top"));
		}
	});
}