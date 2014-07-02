var plot;

var ajax_active = false;
$(document).ajaxStop(function() {
	ajax_active = false;
});

$(document).ajaxStart(function() {
	ajax_active = true;
});

function graphPriceHistory(timeframe,currency) {
	$("#graph_price_history").append('<div class="tp-loader"></div>');
	
	while (!ajax_active) {
		$.getJSON("includes/ajax.graph.php?timeframe="+timeframe+'&currency='+currency,function(json_data) {
			plot = $.plot($("#graph_price_history"),[
	            	{
	             	data: json_data,
	                 lines: { show: true, fill: true },
	                 points: { show: false, fill: false },
	                 color: '#17D6D6'
	            	}
	     	],
	     	{
	     		xaxis: {
	     			mode: "time",
	     			timeformat: ((timeframe == '1mon' || timeframe == '3mon' || timeframe == '6mon') ? "%b %e" : "%b %y"),
	     			minTickSize: [1, "day"],
	     			tickLength: 0
	     		},
	     		yaxis: {
	     		},
	     		grid: { 
	     			backgroundColor: '#FFFFFF',
	     			borderWidth: 1,
	     			borderColor: '#aaaaaa',
	     			hoverable: true
	     		},
	     		crosshair: {
	     			mode:"x",
	     		    color: "#aaaaaa",
	     		    lineWidth: 1
	     		}
	     	});
			
			var date_options = { year: "numeric", month: "short",day: "numeric" };
			axes = plot.getAxes();
			dataset = plot.getData();
			var left_offset = 30;
			var bottom_offset = 50;
			var flip;
			var max_x;
			var currency1 = currency.toUpperCase();
			
			$("#graph_price_history").bind("plothover", function (event, pos, item) {
				plot.unhighlight();
				latestPosition = pos;
				
				if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max || pos.y < axes.yaxis.min || pos.y > axes.yaxis.max) {
					$('#tooltip').css('display','none');
					return false;
				}
				
				if (!updateLegendTimeout) {
					updateLegendTimeout = setTimeout(updateLegend, 50);
				}
				
				date = new Date(parseInt(graph_point[0]));
				$('#tooltip').css('display','block');
				$('#tooltip .date').html($('#javascript_mon_'+date.getMonth()).val()+' '+date.getDate()+', '+date.getFullYear());
				$('#tooltip .price').html(currency1+' '+graph_point[1]);
				
				var x_pix = dataset[graph_i].xaxis.p2c(graph_point[0]);
				var y_pix = dataset[graph_i].yaxis.p2c(graph_point[1]);
				max_x = dataset[graph_i].xaxis.p2c(axes.xaxis.max);
	
				if ((max_x - x_pix) < $('#tooltip').width())
					flip = true;
				else
					flip = false;
				
				if (!flip) {
					$('#tooltip').css('left',(x_pix+left_offset)+'px');
					$('#tooltip').css('top',(y_pix-bottom_offset)+'px');
				}
				else {
					$('#tooltip').css('left',(x_pix-$('#tooltip').width())+'px');
					$('#tooltip').css('top',(y_pix-bottom_offset)+'px');
				}
				
				plot.highlight(graph_i,graph_j);
			}); 
			
			$("#graph_price_history").remove('.tp-loader');
		});
	}
}

function graphOrders() {
	$("#graph_orders").append('<div class="tp-loader"></div>');
	var currency = $('#graph_orders_currency').val();
	
	while (!ajax_active) {
		$.getJSON("includes/ajax.graph.php?action=orders&currency="+currency,function(json_data) {
			plot = $.plot($("#graph_orders"),[
	            	{
	            	 data: json_data.bids,
	                 lines: { show: true, fill: true },
	                 points: { show: false, fill: false },
	                 color: '#17D6D6'
	            	},
	            	{
	             	 data: json_data.asks,
	                 lines: { show: true, fill: true },
	                 points: { show: false, fill: false },
	                 color: '#53DB80'
	            	}
	     	],
	     	{
	     		xaxis: {
	     			tickLength: 0
	     		},
	     		yaxis: {
	     		},
	     		grid: { 
	     			backgroundColor: '#FFFFFF',
	     			borderWidth: 1,
	     			borderColor: '#aaaaaa',
	     			hoverable: true
	     		},
	     		crosshair: {
	     			mode:"x",
	     		    color: "#aaaaaa",
	     		    lineWidth: 1
	     		}
	     	});
			
			var date_options = { year: "numeric", month: "short",day: "numeric" };
			axes = plot.getAxes();
			dataset = plot.getData();
			var left_offset = 30;
			var bottom_offset = 50;
			var flip;
			var max_x;
			var currency1 = currency.toUpperCase();
			$("#graph_orders").bind("plothover", function (event, pos, item) {
				plot.unhighlight();
				latestPosition = pos;
				
				if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max || pos.y < axes.yaxis.min || pos.y > axes.yaxis.max) {
					$('#tooltip').css('display','none');
					return false;
				}
				
				if (!updateLegendTimeout) {
					updateLegendTimeout = setTimeout(updateLegend, 50);
				}
	
				var ask = false;
				if (pos.x >= dataset[graph_i1].data[0][0]) {
					graph_point = graph_point1;
					graph_i = graph_i1;
					graph_j = graph_j1;
					ask = true;
				}
				
				$('#tooltip').css('display','block');
				$('#tooltip .price').html(currency1+' '+graph_point[0]);
		
				if (!ask) {
					$('#tooltip .bid span').html(graph_point[1]);
					$('#tooltip .bid').css('display','block');
					$('#tooltip .ask').css('display','none');
				}
				else {
					$('#tooltip .ask span').html(graph_point[1]);
					$('#tooltip .ask').css('display','block');
					$('#tooltip .bid').css('display','none');
				}
				
				var x_pix = dataset[graph_i].xaxis.p2c(graph_point[0]);
				var y_pix = dataset[graph_i].yaxis.p2c(graph_point[1]);
				max_x = dataset[graph_i].xaxis.p2c(axes.xaxis.max);
	
				if ((max_x - x_pix) < $('#tooltip').width())
					flip = true;
				else
					flip = false;
				
				if (!flip) {
					$('#tooltip').css('left',(x_pix+left_offset)+'px');
					$('#tooltip').css('top',(y_pix-bottom_offset)+'px');
				}
				else {
					$('#tooltip').css('left',(x_pix-$('#tooltip').width())+'px');
					$('#tooltip').css('top',(y_pix-bottom_offset)+'px');
				}
				
				plot.highlight(graph_i,graph_j);
			}); 
			
			$("#graph_price_history").remove('.tp-loader');
		});
	}
}

function graphControls() {
	$('.graph_options a').click(function() {
		$('.graph_options a').removeClass('selected');
		$(this).addClass('selected');
		var currency = $('#graph_price_history_currency').val();
		
		graphPriceHistory($(this).attr('data-option'),currency);
		return false;
	});
}

var updateLegendTimeout = null;
var latestPosition = null;
var graph_point;
var graph_point1;
var axes;
var dataset;
var graph_i;
var graph_j;
var graph_i1;
var graph_j1;
function updateLegend() {
	updateLegendTimeout = null;
	var pos = latestPosition;
	if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max || pos.y < axes.yaxis.min || pos.y > axes.yaxis.max) {
		return;
	}
	var i, j;
	var already = false;
	for (i = 0; i < dataset.length; ++i) {
		var series = dataset[i];
		// Find the nearest points, x-wise
		for (j = 0; j < series.data.length; ++j) {
			if (series.data[j][0] >= pos.x) {
				if (!already) {
					graph_point = series.data[j];
					graph_i = i;
					graph_j = j;
					already = true;
					break;
				}
				else {
					graph_point1 = series.data[j];
					graph_i1 = i;
					graph_j1 = j;
					break;
				}
			}
		}
		already = true;
	}
}

function timeSince(elem) {
	var miliseconds = $(elem).siblings('.time_since_seconds').val();
	var date = new Date(parseInt(miliseconds));
	var time_unit;
	
	$(elem).countdown({ 
	    since: date,
	    significant: 1,
	    layout: '{o<}{on} {ol}{o>}{w<}{wn} {wl}{w>}{d<}{dn} {dl}{d>}{h<}{hn} {hl}{h>}{m<}{mn} {ml}{m>}{s<}{sn} {sl}{s>}'
	});
}

function updateTransactions() {
	var notrades = ($('#graph_orders_currency').length > 0 || $('#open_orders_user').length > 0 || $('#user_fee').length > 0);
	var get_10 = ($('#user_fee').length > 0);
	var open_orders_user = $('#open_orders_user').val();
	var trades_amount = (get_10) ? 10 : 5;
	var update = setInterval(function(){
		while (!ajax_active) {
			var currency = (notrades) ? (($('#user_fee').length > 0) ? $('#buy_currency').val() : $('#graph_orders_currency').val()) : $('#graph_price_history_currency').val();
			$.getJSON("includes/ajax.trades.php?currency="+currency+((notrades) ? '&notrades=1' : '')+((open_orders_user) ? '&user=1' : '')+((get_10) ? '&get10=1' : ''),function(json_data) {
				if (!notrades && json_data.transactions[0] != null) {
					$.each(json_data.transactions[0],function(i) {
						if ($('#order_'+this.id).length > 0)
							return true;
						
						var current_min = parseFloat($('#stats_min').html());
						var current_max = parseFloat($('#stats_max').html());
						if (this.btc_price < current_min)
							$('#stats_min').html(this.btc_price);
						if (this.btc_price > current_max)
							$('#stats_max').html(this.btc_price);
						
						var active_transactions = $('#transactions_list tr').length;
						if (active_transactions >= 6)
							$('#transactions_list tr:last').remove();
						
						var elem = $('<tr id="order_'+this.id+'"><td><span class="time_since"></span><input type="hidden" class="time_since_seconds" value="'+this.time_since+'" /></td><td>'+this.btc+' BTC</td><td>'+this.fa_symbol+this.btc_price+'</td></tr>').insertAfter(('#transactions_list tr:first'));
						timeSince($(elem).find('.time_since'));
						$(elem).children('td').effect("highlight",{color:"#A2EEEE"},2000);
						$('#stats_traded').html((json_data.btc_traded).toFixed(2));
					});
				}
				else {
					$('#no_transactions').css('display','');
				}
				
				$.each($('.bid_tr'),function() {
					var elem = this;
					var elem_id = $(this).attr('id');
					var order_id = elem_id.replace('bid_','');
					var found = false;
					if (json_data.bids[0] != null) {
						$.each(json_data.bids[0],function() {
							if (this.id == order_id) {
								found = true;
								return false;
							}
						});
					}
					if (!found)
						$(elem).remove();
				});
				if (json_data.bids[0] != null) {				
					$.each(json_data.bids[0],function() {
						var this_bid = $('#bid_'+this.id);
						if (this_bid.length > 0) {
							$(this_bid).find('.order_amount').html(this.btc);
							$(this_bid).find('.order_price').html((parseFloat(this.btc_price)).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
							if (notrades)
								$(this_bid).find('.order_value').html((parseFloat(this.btc) * parseFloat(this.btc_price)).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
						}
						else {
							var last_price = 999999999999999999999;
							var json_elem = this;

							if ($('#bids_list .order_price').length > 0) {
								$.each($('#bids_list .order_price'),function(i){
									var price = parseFloat($(this).html());
									var new_price = parseFloat(json_elem.btc_price);
									var active_bids = $('#bids_list .order_price').length;
									
									if ((new_price <= last_price && new_price >= price) || (active_bids < trades_amount && i == (active_bids - 1)) || (notrades && i == (active_bids - 1) && !get_10)) {
										if (notrades) {
											var edit_str = (open_orders_user) ? '<td><a title="'+$('#cfg_orders_edit').val()+'" href="edit-order.php?order_id='+json_elem.id+'"><i class="fa fa-pencil"></i></a> <a title="'+$('#cfg_orders_delete').val()+'" href="open-orders.php?delete_id='+json_elem.id+'"><i class="fa fa-times"></i></a></td>' : false;
											var string = '<tr class="bid_tr" id="bid_'+json_elem.id+'"><td>'+json_elem.fa_symbol+'<span class="order_price">'+json_elem.btc_price+'</span></td><td><span class="order_amount">'+json_elem.btc+'</span></td><td>'+json_elem.fa_symbol+'<span class="order_value">'+(parseFloat(json_elem.btc) * parseFloat(json_elem.btc_price)).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")+'</span></td>'+edit_str+'</tr>';
										}
										else
											var string = '<tr class="bid_tr" id="bid_'+json_elem.id+'"><td><span class="order_amount">'+json_elem.btc+'</span> BTC</td><td>'+json_elem.fa_symbol+'<span class="order_price">'+json_elem.btc_price+'</span></td></tr>';
										
										if (new_price <= last_price && new_price >= price)
											var elem = $(string).insertBefore($(this).parents('tr'));
										else
											var elem = $(string).insertAfter($(this).parents('tr'));
										
										$(elem).children('td').effect("highlight",{color:"#A2EEEE"},2000);
									}
									
									last_price = price;
								});
							}
							else {
								if (notrades) {
									var edit_str = (open_orders_user) ? '<td><a title="'+$('#cfg_orders_edit').val()+'" href="edit-order.php?order_id='+json_elem.id+'"><i class="fa fa-pencil"></i></a> <a title="'+$('#cfg_orders_delete').val()+'" href="open-orders.php?delete_id='+json_elem.id+'"><i class="fa fa-times"></i></a></td>' : false;
									var string = '<tr class="bid_tr" id="bid_'+json_elem.id+'"><td>'+json_elem.fa_symbol+'<span class="order_price">'+json_elem.btc_price+'</span></td><td><span class="order_amount">'+json_elem.btc+'</span></td><td>'+json_elem.fa_symbol+'<span class="order_value">'+(parseFloat(json_elem.btc) * parseFloat(json_elem.btc_price)).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")+'</span></td>'+edit_str+'</tr>';
								}
								else
									var string = '<tr class="bid_tr" id="bid_'+json_elem.id+'"><td><span class="order_amount">'+json_elem.btc+'</span> BTC</td><td>'+json_elem.fa_symbol+'<span class="order_price">'+json_elem.btc_price+'</span></td></tr>';
								
								var elem = $(string).insertAfter($('#no_bids'));
								$(elem).children('td').effect("highlight",{color:"#A2EEEE"},2000);
								$('#no_bids').css('display','none');
							}
						}
					});
					
					sortTable('#bids_list',((notrades) ? 0 : 1),1);
				}
				else {
					$('#no_bids').css('display','');
				}
	
				$.each($('.ask_tr'),function() {
					var elem = this;
					var elem_id = $(this).attr('id');
					var order_id = elem_id.replace('ask_','');
					var found = false;
					if (json_data.asks[0] != null) {
						$.each(json_data.asks[0],function() {
							if (this.id == order_id) {
								found = true;
								return false;
							}
						});
					}
					if (!found)
						$(elem).remove();
				});
				
				if (json_data.asks[0] != null) {
					$.each(json_data.asks[0],function() {
						var this_ask = $('#ask_'+this.id);
						if (this_ask.length > 0) {
							$(this_ask).find('.order_amount').html(this.btc);
							$(this_ask).find('.order_price').html((parseFloat(this.btc_price)).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
							if (notrades)
								$(this_ask).find('.order_value').html((parseFloat(this.btc) * parseFloat(this.btc_price)).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
						}
						else {
							var last_price = 0;
							var json_elem = this;
							
							if ($('#asks_list .order_price').length > 0) {
								$.each($('#asks_list .order_price'),function(i){
									var price = parseFloat($(this).html());
									var new_price = parseFloat(json_elem.btc_price);
									var active_asks = $('#asks_list .order_price').length;
									
									if ((new_price >= last_price && new_price <= price) || (active_asks < trades_amount && i == (active_asks - 1)) || (notrades && i == (active_asks - 1) && !get_10)) {
										if (notrades) {
											var edit_str = (open_orders_user) ? '<td><a title="'+$('#cfg_orders_edit').val()+'" href="edit-order.php?order_id='+json_elem.id+'"><i class="fa fa-pencil"></i></a> <a title="'+$('#cfg_orders_delete').val()+'" href="open-orders.php?delete_id='+json_elem.id+'"><i class="fa fa-times"></i></a></td>' : false;
											var string = '<tr class="ask_tr" id="ask_'+json_elem.id+'"><td>'+json_elem.fa_symbol+'<span class="order_price">'+json_elem.btc_price+'</span></td><td><span class="order_amount">'+json_elem.btc+'</span></td><td>'+json_elem.fa_symbol+'<span class="order_value">'+(parseFloat(json_elem.btc) * parseFloat(json_elem.btc_price)).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")+'</span></td>'+edit_str+'</tr>';
										}
										else
											var string = '<tr class="ask_tr" id="ask_'+json_elem.id+'"><td><span class="order_amount">'+json_elem.btc+'</span> BTC</td><td>'+json_elem.fa_symbol+'<span class="order_price">'+json_elem.btc_price+'</span></td></tr>';
										
										if (new_price >= last_price && new_price <= price)
											var elem = $(string).insertBefore($(this).parents('tr'));
										else
											var elem = $(string).insertAfter($(this).parents('tr'));
										
										$(elem).children('td').effect("highlight",{color:"#A2EEEE"},2000);
									}
									
									last_price = price;
								});
							}
							else {
								if (notrades) {
									var edit_str = (open_orders_user) ? '<td><a title="'+$('#cfg_orders_edit').val()+'" href="edit-order.php?order_id='+json_elem.id+'"><i class="fa fa-pencil"></i></a> <a title="'+$('#cfg_orders_delete').val()+'" href="open-orders.php?delete_id='+json_elem.id+'"><i class="fa fa-times"></i></a></td>' : false;
									var string = '<tr class="ask_tr" id="ask_'+json_elem.id+'"><td>'+json_elem.fa_symbol+'<span class="order_price">'+json_elem.btc_price+'</span></td><td><span class="order_amount">'+json_elem.btc+'</span></td><td>'+json_elem.fa_symbol+'<span class="order_value">'+(parseFloat(json_elem.btc) * parseFloat(json_elem.btc_price)).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")+'</span></td>'+edit_str+'</tr>';
								}
								else
									var string = '<tr class="ask_tr" id="ask_'+json_elem.id+'"><td><span class="order_amount">'+json_elem.btc+'</span> BTC</td><td>'+json_elem.fa_symbol+'<span class="order_price">'+json_elem.btc_price+'</span></td></tr>';
								
								var elem = $(string).insertAfter($('#no_asks'));
								$(elem).children('td').effect("highlight",{color:"#A2EEEE"},2000);
								$('#no_asks').css('display','none');
							}
						}
					});
					
					sortTable('#asks_list',((notrades) ? 0 : 1),0);
				}
				else {
					$('#no_asks').css('display','');
				}
				
				var current_price = ($('#asks_list .order_price').length > 0) ? parseFloat($('#asks_list .order_price:first').html().replace(',','')) : 0;
				var current_bid = ($('#bids_list .order_price').length > 0) ? parseFloat($('#bids_list .order_price:first').html().replace(',','')) : 0;
				
				if ($('#buy_price').length > 0 && $('#buy_price').is('[readonly]') && current_price > 0) {
					$('#buy_price').val((current_price).toFixed(2));
					$("#buy_price").trigger("change");
				}
				if ($('#sell_price').length > 0 && $('#sell_price').is('[readonly]') && current_bid > 0) {
					$('#sell_price').val((current_bid).toFixed(2));
					$("#sell_price").trigger("change");
				}
				
				if (current_price > 0)
					$('#buy_market_price').prop('readonly','');
				else
					$('#buy_market_price').prop('readonly','readonly');
				if (current_bid > 0)
					$('#sell_market_price').prop('readonly','');
				else
					$('#sell_market_price').prop('readonly','readonly');
				
				current_price = (current_price > 0) ? current_price : current_bid;
				if (current_price > 0) {
					var open_price = parseFloat($('#stats_open').html());
					var change_abs = Math.abs((current_price - open_price).toFixed(2));
					$('#stats_last_price').html((current_price).toFixed(2));
					$('#stats_daily_change_abs').html(change_abs);
					$('#stats_daily_change_perc').html(((change_abs/current_price) * 100).toFixed(2));
				}
			});
		}
	},5000);
}

function updateTransactionsList() {
	if (!($('#refresh_transactions').length > 0))
		return false;
	
	var update = setInterval(function(){
		while (!ajax_active) {
			var currency = $('#graph_orders_currency').val();
			var type = $('#type').val();
			
			$.getJSON("includes/ajax.transactions.php?currency="+currency+'&type='+type,function(transaction) {
				var this_transaction = $('#transaction_'+transaction.id);
				if (this_transaction.length > 0) 
					return false;
				
				var string = '<tr id="transaction_'+transaction.id+'">';
				string += '<td>'+transaction.type+'</td>';
				string += '<td><input type="hidden" class="localdate" value="'+(parseInt(transaction.datestamp))+'" /></td>';
				string += '<td>'+((parseFloat(transaction.btc_net)).toPrecision(8))+'</td>';
				string += '<td>'+transaction.fa_symbol+((parseFloat(transaction.btc_net * transaction.fiat_price)).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","))+'</td>';
				string += '<td>'+transaction.fa_symbol+((parseFloat(transaction.fiat_price)).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","))+'</td>';
				string += '<td>'+transaction.fa_symbol+((parseFloat(transaction.fee * transaction.fiat_price)).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","))+'</td>';
				string += '</tr>';
				
				var elem = $(string).insertAfter($('#table_first'));
				$(elem).children('td').effect("highlight",{color:"#A2EEEE"},2000);
				$('#no_transactions').css('display','none');
				
				localDates();
			});
		}
	},5000);
}

function updateStats() {
	var update = setInterval(function(){
		var currency = $('#graph_price_history_currency').val();
		while (!ajax_active) {
			$.getJSON("includes/ajax.stats.php?currency="+currency,function(json_data) {
				$('#stats_open').html(json_data.open);
				$('#stats_market_cap').html(json_data.market_cap.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
				$('#stats_total_btc').html(json_data.total_btc.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
				$('#stats_trade_volume').html(json_data.trade_volume.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
			});
		}
	},3600000);
}

function filtersUpdate() {
	$('#filters select').bind("keyup change", function(){
		$('#filters_area').append('<div class="tp-loader"></div>');
		var url = $('#filters').attr('action');
		var query = $('#filters').serialize();
		
		while (!ajax_active) {
			$('#filters_area').load(url+'?page=1&bypass=1&'+query,function() {
				paginationUpdate();
				localDates();
			});
		}
	});
}

function paginationUpdate() {
	$('.pagination a').click(function(e) {
		$('#filters_area').append('<div class="tp-loader"></div>');
		var url = $(this).attr('href');
		var query = $('#filters').serialize();
		
		while (!ajax_active) {
			$('#filters_area').load(url+'&bypass=1&'+query,function() {
				paginationUpdate();
				localDates();
			});
			e.preventDefault();
			return false;
		}
	});
}

function switchBuyCurrency() {
	$('#buy_currency,#sell_currency').bind("keyup change", function(){
		var currency = $(this).val();
		while (!ajax_active) {
			$.getJSON("includes/ajax.get_currency.php?currency="+currency,function(json_data) {
				$('#filters_area').load('buy-sell.php?bypass=1&currency='+currency);
				$('#buy_currency,#sell_currency').val(currency);
				$('.sell_currency_label,.buy_currency_label').html(currency.toUpperCase());
				$('.sell_currency_char,.buy_currency_char').html(json_data.currency_info.fa_symbol);
				$('#buy_price').val(json_data.current_bid);
				$('#sell_price').val(json_data.current_ask);
				$('#sell_user_available').html(json_data.available_btc);
				$('#buy_user_available').html(json_data.available_fiat);
				calculateBuyPrice();
			});
		}
	});
}

function calculateBuy() {
	$('#buy_amount,#buy_price,#sell_amount,#sell_price').bind("keyup change", function(){
		calculateBuyPrice();
	});
	
	$('#buy_amount,#buy_price,#sell_amount,#sell_price').bind("keypress", function(e){
		var charCode = (e.which) ? e.which : e.keyCode;
        if (charCode != 46 && charCode != 39 && charCode != 37 && charCode > 31 && (charCode < 48 || charCode > 57))
           return false;

        return true;
	});
	
	$('#buy_amount,#buy_price,#sell_amount,#sell_price,#fiat_amount,#btc_amount').focus(function(){
		if (!(parseFloat($(this).val()) > 0))
			$(this).val('');
	});
	
	$('#buy_amount,#buy_price,#sell_amount,#sell_price,#fiat_amount,#btc_amount').blur(function(){
		if (!(parseFloat($(this).val()) > 0))
			$(this).val('0');
	});
	
	$('#buy_market_price,#sell_market_price').click(function(){
		if ($(this).is('[readonly]')) {
			alert($('#buy_errors_no_compatible').val());
			return false;
		}
	});
}

function calculateBuyPrice() {
	var user_fee = parseFloat($('#user_fee').val());
	
	var buy_amount = ($('#buy_amount').val()) ? parseFloat($('#buy_amount').val().replace(',','')) : 0;
	var buy_price = ($('#buy_price').val()) ? parseFloat($('#buy_price').val().replace(',','')) : 0;
	var buy_subtotal = buy_amount * buy_price;
	var buy_commision = (user_fee * 0.01) * buy_subtotal;
	var buy_total = buy_subtotal + buy_commision;
	$('#buy_subtotal').html((buy_subtotal).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
	$('#buy_total').html((buy_total).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
	
	var sell_amount = ($('#sell_amount').val()) ? parseFloat($('#sell_amount').val().replace(',','')) : 0;
	var sell_price = ($('#sell_price').val()) ? parseFloat($('#sell_price').val().replace(',','')) : 0;
	var sell_subtotal = sell_amount * sell_price;
	var sell_commision = (user_fee * 0.01) * sell_subtotal;
	var sell_total = sell_subtotal - sell_commision;
	$('#sell_subtotal').html((sell_subtotal).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
	$('#sell_total').html((sell_total).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
}

function buttonDisable() {
	$('form').submit(function() {
		$('.but_user').addClass('loading');
		$('.but_user').attr('disabled','disabled');
	});
}

function enableLimitOrder() {
	$('#buy_market_price').click(function(e){
		if ($(this).is(':checked')) {
			$('#buy_price').attr('readonly','readonly');
			//$('#buy_total_approx_label').css('display','block');
			//$('#buy_total_label').css('display','none');
		}
		else {
			$('#buy_price').removeAttr('readonly');
			//$('#buy_total_approx_label').css('display','none');
			//$('#buy_total_label').css('display','block');
		}
	});
	$('#sell_market_price').click(function(e){
		if ($(this).is(':checked')) {
			$('#sell_price').attr('readonly','readonly');
			//$('#sell_total_approx_label').css('display','block');
			//$('#sell_total_label').css('display','none');
		}
		else {
			$('#sell_price').removeAttr('readonly');
			//$('#sell_total_approx_label').css('display','none');
			//$('#sell_total_label').css('display','block');
		}
	});
}

function localDates() {
	$('.localdate').each(function(){
		var date = new Date(parseInt($(this).val()*1000));
		var offset = date.getTimezoneOffset() * 60;
		var date1 = new Date(parseInt((parseInt($(this).val()) + parseInt(offset))*1000));
		var hours = date1.getHours();
		var minutes = date1.getMinutes();
		var ampm = hours >= 12 ? 'pm' : 'am';
		hours = hours % 12;
		hours = hours ? hours : 12; // the hour '0' should be '12'
		minutes = minutes < 10 ? '0'+minutes : minutes;
		var strTime = hours + ':' + minutes + ' ' + ampm;
		
		$(this).parent().html($('#javascript_mon_'+date1.getMonth()).val()+' '+date1.getDate()+', '+date1.getFullYear()+', '+strTime);
	});
}

function startFileSortable() {
	
}

function switchAccount() {
	$('#deposit_bank_account').bind("keyup change", function(){
		while (!ajax_active) {
			$.getJSON("includes/ajax.get_bank_account.php?account="+$(this).val(),function(json_data) {
				$('#client_account').html(json_data.client_account);
				$('#escrow_account').html(json_data.escrow_account);
				$('#escrow_name').html(json_data.escrow_name);
			});
		}
	});
}
function switchAccount1() {
	$('#withdraw_account').bind("keyup change", function(){
		while (!ajax_active) {
			$.getJSON("includes/ajax.get_bank_account.php?avail=1&account="+$(this).val(),function(json_data) {
				$('.currency_label').html(json_data.currency);
				$('.currency_char').html(json_data.currency_char);
				$('#user_available').html(json_data.available);
			});
		}
	});
}

function expireSession() {
	if ($('#is_logged_in').val() > 0) {
		var init_time = Math.round(new Date().getTime() / 1000);
		var checker = setInterval(function(){
			var curtime = Math.round(new Date().getTime() / 1000);
			if (curtime - init_time >= 900) {
				clearInterval(checker);
				window.location.href = 'logout.php?log_out=1';
			}
		},5);
	}
}

function sortTable(elem_selector,col_num,desc){
	var rows = $(elem_selector+' tr').get();
	rows.sort(function(a, b) {
		if ($(a).children('th').length > 0)
			return -1;

		var A = parseFloat($(a).children('td').eq(col_num).text().replace('$','').replace(',','').replace('BTC',''));
		var B = parseFloat($(b).children('td').eq(col_num).text().replace('$','').replace(',','').replace('BTC',''));
		A = (isNaN(A)) ? 0 : A;
		B = (isNaN(B)) ? 0 : B;
		
		if(A < B) {
			return (desc) ? 1 : -1;
		}
 
		if(A > B) {
			return (desc) ? -1 : 1;
		}
		return 0;
	});
	
	$.each(rows, function(index, row) {
		$(elem_selector).append(row);
	});
}

$(document).ready(function() {
	if ($("#graph_price_history").length > 0) {
		var currency = $('#graph_price_history_currency').val();
		graphPriceHistory('1year',currency);
	}
	
	if ($("#graph_orders").length > 0) {
		graphOrders();
		var update = setInterval(graphOrders,10000);
		updateTransactions();
	}
	
	if ($('#open_orders_user').length > 0)
		updateTransactions();
	
	if ($('#user_fee').length > 0)
		updateTransactions();
	
	if ($('.graph_options').length > 0) {
		graphControls();
	}
	
	if ($('.time_since').length > 0) {
		$('.time_since').each(function() {
			timeSince(this);
		});
	}
	
	$('#language_selector').bind("keyup change", function(){
		window.location.href = 'index.php?lang='+$(this).val();
	});
	
	$('#currency_selector').bind("keyup change", function(){
		window.location.href = 'index.php?currency='+$(this).val();
	});
	
	$('#language_selector').bind("keyup change", function(){
		window.location.href = 'index.php?lang='+$(this).val();
	});
	
	$('#ob_currency').bind("keyup change", function(){
		window.location.href = 'order-book.php?currency='+$(this).val();
	});
	
	if ($("#transactions_timestamp").length > 0) {
		updateTransactions();
		updateStats();
	}
	
	$('#enable_tfa [name="sms"]').click(function(){
		$('#send_sms').val('1');
		return true;
	});
	
	$('#enable_tfa [name="google"]').click(function(){
		$('#google_2fa').val('1');
		return true;
	});
	
	$('#cancel_transaction').click(function(){
		$('#cancel').val('1');
		return true;
	});
	
	filtersUpdate();
	paginationUpdate();
	switchBuyCurrency();
	calculateBuy();
	buttonDisable();
	enableLimitOrder();
	localDates();
	switchAccount();
	switchAccount1();
	//expireSession();
	updateTransactionsList();
});