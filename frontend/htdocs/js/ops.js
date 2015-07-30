var plot = false;

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
	     			labelWidth:0
	     		},
	     		grid: { 
	     			backgroundColor: '#FFFFFF',
	     			borderWidth: 1,
	     			borderColor: '#dddddd',
	     			hoverable: true
	     		},
	     		crosshair: {
	     			mode:"x",
	     		    color: "#aaaaaa",
	     		    lineWidth: 1
	     		}
	     	});
			
			var date_options = { year: "numeric", month: "short",day: "numeric" };
			var axes = plot.getAxes();
			var dataset = plot.getData();
			var left_offset = 30;
			var bottom_offset = 50;
			var flip;
			var max_x;
			var currency1 = currency.toUpperCase();
			
			$("#graph_price_history").bind("plothover", function (event, pos, item) {
				plot.unhighlight();
				
				if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max || pos.y < axes.yaxis.min || pos.y > axes.yaxis.max) {
					$('#tooltip').css('display','none');
					return false;
				}
				
				updateLegend(pos,axes,dataset,true,function(graph_point,graph_i,graph_j) {
					if (graph_point == undefined)
						return false;
					
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
			}); 
			
			$("#graph_price_history").remove('.tp-loader');
		});
	}
}

var last_data = null;
function graphOrders(json_data) {
	$("#graph_orders").append('<div class="tp-loader"></div>');
	var currency = $('#graph_orders_currency').val();
	
	if (!json_data) {
		try {
			json_data = static_data;
		}
		catch (e) {
			console.log(e);
		}
	}
	
	if (last_data) {
		if (last_data.bids && last_data.bids.length && last_data.bids.length > 30 && json_data.bids.length && json_data.bids.length >= 30) {
			var c = (last_data.bids.length && last_data.bids.length > 0) ? last_data.bids.length : 0;
			c = (c > 30) ? 29 : c - 1;
			
			var diff = (last_data.bids[c][1] && json_data.bids[c][1]) ? parseFloat(parseFloat(json_data.bids[c][1]) - (parseFloat(last_data.bids[c][1])).toFixed(2)) : 0;
			var c_price = (diff) ? parseFloat(json_data.bids[c][0]) : Number.POSITIVE_INFINITY;
			last_data.bids.splice(0,30);
			
			last_data.bids = last_data.bids.map(function(item) {
				item[1] += diff;
				return item;
			}).filter(function(item) {
				return (parseFloat(item[0]) <= c_price);
			});
			
			if (json_data.bids)
				json_data.bids = json_data.bids.concat(last_data.bids);
		}
		if (last_data.asks && last_data.asks.length && last_data.asks.length > 30 && json_data.asks.length && json_data.asks.length >= 30) {
			var c = (last_data.asks.length && last_data.asks.length > 0) ? last_data.asks.length : 0;
			c = (c > 30) ? 29 : c - 1;
			
			var diff = (last_data.asks[c][1] && json_data.asks[c][1]) ? parseFloat((parseFloat(json_data.asks[c][1]) - parseFloat(last_data.asks[c][1])).toFixed(2)) : 0;
			var c_price = (diff) ? parseFloat(json_data.bids[c][0]) : Number.NEGATIVE_INFINITY;
			last_data.asks.splice(0,30);
			
			last_data.asks = last_data.asks.map(function(item) {
				item[1] += diff;
				return item;
			}).filter(function(item) {
				return (parseFloat(item[0]) >= c_price);
			});
			
			if (json_data.asks)
				json_data.asks = json_data.asks.concat(last_data.asks);
		}
	}

	last_data = json_data;
	
	var series = [
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
 	];
	
	if (plot) {
		plot.setData(series);
		plot.setupGrid();
		plot.draw();
		return false;
	}
	 
	plot = $.plot($("#graph_orders"),series,
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
	var axes = plot.getAxes();
	var dataset = plot.getData();
	var left_offset = 30;
	var bottom_offset = 50;
	var flip;
	var max_x;
	var currency1 = currency.toUpperCase();
	var last_type = false;
	$("#graph_orders").bind("plothover", function (event, pos, item) {
		plot.unhighlight();
		
		if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max || pos.y < axes.yaxis.min || pos.y > axes.yaxis.max) {
			$('#tooltip').css('display','none');
			return false;
		}
		
		updateLegend(pos,axes,dataset,false,function(graph_point,graph_i,graph_j) {
			var ask = (graph_i == 1);
			
			if (!graph_point || graph_point == 0)
				return false;
			
			$('#tooltip').css('display','block');
			$('#tooltip .price').html(currency1+' '+formatCurrency(graph_point[0]));

			if (last_type != graph_i) {
				if (graph_i > 0)
					$('#tooltip .price').addClass('alt');
				else
					$('#tooltip .price').removeClass('alt');
			}
	
			if (!ask) {
				$('#tooltip .bid span').html(formatCurrency(graph_point[1]));
				if (last_type != graph_i) {
					$('#tooltip .bid').css('display','block');
					$('#tooltip .ask').css('display','none');
				}
			}
			else {
				$('#tooltip .ask span').html(formatCurrency(graph_point[1]));
				if (last_type != graph_i) {
					$('#tooltip .ask').css('display','block');
					$('#tooltip .bid').css('display','none');
				}
			}
			
			var x_pix = dataset[graph_i].xaxis.p2c(graph_point[0]);
			var y_pix = dataset[graph_i].yaxis.p2c(graph_point[1]);
			max_x = dataset[graph_i].xaxis.p2c(axes.xaxis.max);
			last_type = graph_i;
	
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
	}); 
	
	$("#graph_price_history").remove('.tp-loader');
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

function updateLegend(pos,axes,dataset,single_dataset,callback) {
	if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max || pos.y < axes.yaxis.min || pos.y > axes.yaxis.max) {
		return false;
	}
	
	if (single_dataset) {
		if (!dataset || !dataset[0].data || dataset[0].data.length == 0)
			return false;
		
		var series = dataset[0].data;
		var graph_i = 0;
	}
	else {
		if (dataset[0] && dataset[0].data && pos.x <= dataset[0].data[0][0]) {
			var series = dataset[0].data;
			var graph_i = 0;
		}
		else if (dataset[1] && dataset[1].data && pos.x >= dataset[1].data[0][0]) {
			var series = dataset[1].data;
			var graph_i = 1;
		}
		else
			return false;
	}
	
	var diff = null;
	var last_diff = null;
	var graph_j = null;
	var graph_point = null;

	for (i in series) {
		if (!pos.x || !series[i][0])
			continue;
		
		diff = pos.x - parseFloat(series[i][0]);
		if (last_diff && Math.abs(diff) > Math.abs(last_diff))
			break;
		
		graph_j = i;
		graph_point = series[i];
		last_diff = diff;
	}
	
	callback(graph_point,graph_i,graph_j);
}

function updateTransactions() {
	var notrades = ($('#graph_orders_currency').length > 0 || $('#open_orders_user').length > 0 || $('#user_fee').length > 0);
	var get_10 = ($('#user_fee').length > 0);
	var open_orders_user = $('#open_orders_user').val();
	var trades_amount = (get_10) ? 10 : 5;
	var cfg_user_id = $('#cfg_user_id').val();
	var sort_column = false;
	
	var update = setInterval(function(){
		while (!ajax_active) {
			var currency = (notrades) ? (($('#user_fee').length > 0) ? $('#buy_currency').val() : $('#graph_orders_currency').val()) : $('#graph_price_history_currency').val();
			var currency_id = (currency) ? $('.curr_abbr_'+currency.toUpperCase()).attr('name') : null;
			var order_by = $('#order_by').val();
			
			if ($('#order_by').length > 0) {
				if ($('#order_by').val() == 'btcprice')
					sort_column = '.usd_price';
				else if ($('#order_by').val() == 'date')
					sort_column = '.order_date';
				else if ($('#order_by').val() == 'btc')
					sort_column = '.order_amount';
			}
			
			$.getJSON("includes/ajax.trades.php?currency="+currency+((order_by) ? '&order_by='+order_by : '')+((notrades) ? '&notrades=1' : '')+((open_orders_user) ? '&user=1' : '&last_price=1')+((get_10) ? '&get10=1' : ''),function(json_data) {
				var depth_chart_data = {bids:[],asks: []}; 
				if (!notrades && json_data.transactions[0] != null) {
					var i = 0;
					var insert_elem = ('#transactions_list tr:first');
					$.each(json_data.transactions[0],function(i) {
						if ($('#order_'+this.id).length > 0)
							return true;
						
						var this_currency_abbr = (this.currency == currency_id) ? '' : ((this.currency1 == currency_id) ? '' : ' ('+($('#curr_abbr_'+this.currency1).val())+')');
						var this_currency_abbr1 = $('#curr_abbr_'+currency_id).val();
						var this_fa_symbol = $('#curr_sym_'+currency_id).val();
						
						if (i == 0) {
							current_price = parseFloat(this.btc_price.replace(',',''));
							if (current_price > 0) {
								if (this.maker_type == 'sell') {
									$('#stats_last_price').parents('.stat1').removeClass('price-red').addClass('price-green');
									$('#up_or_down1').replaceWith('<i id="up_or_down1" class="fa fa-caret-up price-green"></i>');
								}
								else {
									$('#stats_last_price').parents('.stat1').removeClass('price-green').addClass('price-red');
									$('#up_or_down1').replaceWith('<i id="up_or_down1" class="fa fa-caret-down price-red"></i>');
								}
								
								var open_price = parseFloat($('#stats_open').html().replace(',',''));
								var change_perc = formatCurrency(current_price - open_price);
								var change_abs = Math.abs(change_perc);
								
								$('#stats_last_price').html(formatCurrency(current_price));
								$('#stats_last_price_curr').html((this.currency == currency_id) ? '' : ((this.currency1 == currency_id) ? '' : ' ('+($('#curr_abbr_'+this.currency1).val())+')'));
								$('#stats_daily_change_abs').html(change_abs);
								$('#stats_daily_change_perc').html(formatCurrency((change_abs/current_price) * 100));
								
								if (change_perc > 0) 
									$('#up_or_down').replaceWith('<i id="up_or_down" class="fa fa-caret-up" style="color:#60FF51;"></i>');
								else if (change_perc < 0)
									$('#up_or_down').replaceWith('<i id="up_or_down" class="fa fa-caret-down" style="color:#FF5151;"></i>');
								else
									$('#up_or_down').replaceWith('<i id="up_or_down" class="fa fa-minus"></i>');
								
								if (typeof json_data.last_price_cnv == 'object') {
									for (key1 in json_data.last_price_cnv) {
										$('.price_'+key1).html(json_data.last_price_cnv[key1]);
										console.log(key1,this_currency_abbr1);
										if (key1 == this_currency_abbr1) {
											if (this.maker_type == 'sell')
												$('.price_'+key1).parent().removeClass('price-red').addClass('price-green');
											else
												$('.price_'+key1).parent().removeClass('price-green').addClass('price-red');
										}
											
									}
								}
							}
						}
						
						var current_min = parseFloat($('#stats_min').html().replace(',',''));
						var current_max = parseFloat($('#stats_max').html().replace(',',''));
						if (this.btc_price < current_min)
							$('#stats_min').html(formatCurrency(this.btc_price));
						if (this.btc_price > current_max)
							$('#stats_max').html(formatCurrency(this.btc_price));
						
						var elem = $('<tr id="order_'+this.id+'"><td><span class="time_since"></span><input type="hidden" class="time_since_seconds" value="'+this.time_since+'" /></td><td>'+this.btc+' BTC</td><td>' + this_fa_symbol + formatCurrency(this.btc_price) + this_currency_abbr + '</td></tr>').insertAfter(insert_elem);
						insert_elem = elem;
						
						timeSince($(elem).find('.time_since'));
						$(elem).children('td').effect("highlight",{color:"#A2EEEE"},2000);
						$('#stats_traded').html(formatCurrency(json_data.btc_traded));
						
						var active_transactions = $('#transactions_list tr:not(#no_transactions)').length;
						if (active_transactions > 5)
							$('#transactions_list tr:not(#no_transactions):last').remove();
						
						i++;
					});
				}
				else {
					$('#no_transactions').css('display','');
				}
				
				$.each($('.bid_tr'),function(index) {
					if (index >= 30)
						return false;
					
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
					var cum_btc = 0;
					$.each(json_data.bids[0],function() {
						if (this.btc && this.btc > 0) {
							cum_btc += parseFloat(this.btc);
							depth_chart_data.bids.push([this.btc_price,cum_btc]);
						}
						
						var this_currency_id = (parseFloat($('#this_currency_id').val()) > 0 ? $('#this_currency_id').val() : this.currency);
						var fa_symbol = $('#curr_sym_'+this_currency_id).val();
						var currency_abbr = $('#curr_abbr_'+this.currency).val();
						
						var this_bid = $('#bid_'+this.id);
						if (this_bid.length > 0) {
							$(this_bid).find('.order_amount').html(this.btc);
							$('#bid_'+this.id+'.double').find('.order_amount').html(this.btc);
							$(this_bid).find('.order_price').html(formatCurrency((this.btc_price > 0) ? this.btc_price : this.stop_price));
							$('#bid_'+this.id+'.double').find('.order_price').html(formatCurrency(this.stop_price));
							if (notrades) {
								$(this_bid).find('.order_value').html(formatCurrency(parseFloat(this.btc) * parseFloat((this.btc_price > 0) ? this.btc_price : this.stop_price)));
								$('#bid_'+this.id+'.double').find('.order_value').html(formatCurrency(parseFloat(this.btc) * parseFloat(this.stop_price)));
								if (open_orders_user) {
									var double = 0;
									if (this.market_price == 'Y')
										var type = '<div class="identify market_order">M</div>';
									else if (this.btc_price > 0 && !(this.stop_price > 0))
										var type = '<div class="identify limit_order">L</div>';
									else if (this.stop_price > 0 && !(this.btc_price > 0))
										var type = '<div class="identify stop_order">S</div>';
									else if (this.stop_price > 0 && this.btc_price > 0) {
										var type = '<div class="identify limit_order">L</div>';
										double = 1;
									}
									$(this_bid).find('.identify').replaceWith(type);
									if (!double)
										$('#bid_'+this.id+'.double').remove();
									
									$(this_bid).find('.usd_price').val(this.usd_price);
								}
							}
						}
						else {
							var last_price = 999999999999999999999;
							var mine = (cfg_user_id > 0 && cfg_user_id == this.user_id && !open_orders_user && this.currency == this_currency_id) ? '<a class="fa fa-user" href="open-orders.php?id='+this.id+'" title="'+($('#your_order').val())+'"></a>' : '';
							var json_elem = this;
							var skip_next = false;
							var insert_elem = false;
							var before = false;
							var j = 1;
							
							if ($('#bids_list .order_price').length > 0) {
								$.each($('#bids_list .order_price'),function(i){
									if (skip_next) {
										skip_next = false;
										j++;
										return;
									}
									
									var price = parseFloat($(this).html());
									var next_price = ($(this).parents('tr').next('tr').find('.order_price').length > 0) ? parseFloat($(this).parents('tr').next('tr').find('.order_price').html()) : 0;
									var new_price = parseFloat(json_elem.btc_price);
									var active_bids = $('#bids_list .order_price').length;
									this_elem = (next_price == price) ? $(this).parents('tr').next('tr').find('.order_price') : this;
									this_elem = ($(this_elem).parents('tr').next('tr').hasClass('double')) ? $(this_elem).parents('tr').next('tr').find('.order_price') : this_elem;
									skip_next = (next_price == price);
									
									if (new_price > price && new_price < last_price) {
										insert_elem = $(this_elem).parents('tr');
										before = 1;
									}
									else if (new_price == price) 
										insert_elem = $(this_elem).parents('tr');
									else if (new_price < price && active_bids == j)
										insert_elem = $(this_elem).parents('tr');
									
									if (insert_elem)
										return false;
										
									last_price = price;
									j++;
								});
							}
							else {
								insert_elem = $('#no_bids');
								$('#no_bids').css('display','none');
							}
							
							if (notrades) {
								var usd_price = '';
								if (open_orders_user) {
									var double = 0;
									if (json_elem.market_price == 'Y')
										var type = '<td><div class="identify market_order">M</div></td>';
									else if (json_elem.btc_price > 0 && !(json_elem.stop_price > 0))
										var type = '<td><div class="identify limit_order">L</div></td>';
									else if (json_elem.stop_price > 0 && !(json_elem.btc_price > 0))
										var type = '<td><div class="identify stop_order">S</div></td>';
									else if (json_elem.stop_price > 0 && json_elem.btc_price > 0) {
										var type = '<td><div class="identify limit_order">L</div></td>';
										double = 1;
									}
									
									usd_price = '<input type="hidden" class="usd_price" value="'+(json_elem.usd_price ? json_elem.usd_price : json_elem.btc_price)+'" /><input type="hidden" class="order_date" value="'+json_elem.date+'" />';
								}
								
								var edit_str = (open_orders_user) ? '<td><a title="'+$('#cfg_orders_edit').val()+'" href="edit-order.php?order_id='+json_elem.id+'"><i class="fa fa-pencil"></i></a> <a title="'+$('#cfg_orders_delete').val()+'" href="open-orders.php?delete_id='+json_elem.id+'&uniq='+$('#uniq').val()+'"><i class="fa fa-times"></i></a></td>' : false;
								var string = '<tr class="bid_tr" id="bid_'+json_elem.id+'">'+usd_price+type+'<td>'+mine+fa_symbol+'<span class="order_price">'+formatCurrency(((json_elem.btc_price > 0) ? json_elem.btc_price : json_elem.stop_price))+'</span> '+((json_elem.btc_price != json_elem.fiat_price) ? '<a title="'+$('#orders_converted_from').val().replace('[currency]',currency_abbr)+'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '')+'</td><td><span class="order_amount">'+json_elem.btc+'</span></td><td>'+fa_symbol+'<span class="order_value">'+formatCurrency(parseFloat(json_elem.btc) * parseFloat(json_elem.btc_price))+'</span></td>'+edit_str+'</tr>';
							
								if (double)
									string += '<tr class="bid_tr double" id="bid_'+json_elem.id+'"><td><div class="identify stop_order">S</div></td><td>'+mine+fa_symbol+'<span class="order_price">'+(formatCurrency(json_elem.stop_price))+'</span></td><td><span class="order_amount">'+json_elem.btc+'</span></td><td>'+fa_symbol+'<span class="order_value">'+formatCurrency(parseFloat(json_elem.btc) * parseFloat(json_elem.btc_price))+'</span></td><td><span class="oco"><i class="fa fa-arrow-up"></i> OCO</span></td></tr>';
							}
							else
								var string = '<tr class="bid_tr" id="bid_'+json_elem.id+'"><td>'+mine+'<span class="order_amount">'+json_elem.btc+'</span> BTC</td><td>'+fa_symbol+'<span class="order_price">'+(formatCurrency(json_elem.btc_price))+'</span> '+((json_elem.btc_price != json_elem.fiat_price) ? '<a title="'+$('#orders_converted_from').val().replace('[currency]',currency_abbr)+'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '')+'</td></tr>';
							
							if (before)
								var elem = $(string).insertBefore(insert_elem);
							else
								var elem = $(string).insertAfter(insert_elem);
						
							$(elem).children('td').effect("highlight",{color:"#A2EEEE"},2000);
						}
					});
					
					sortTable('#bids_list',((notrades) ? 0 : 1),1,sort_column);
				}
				else {
					$('#no_bids').css('display','');
				}
	
				$.each($('.ask_tr'),function(index) {
					if (index >= 30)
						return false;
					
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
					var cum_btc = 0;
					$.each(json_data.asks[0],function() {
						if (this.btc && this.btc > 0) {
							cum_btc += parseFloat(this.btc);
							depth_chart_data.asks.push([this.btc_price,cum_btc]);
						}
						
						var this_currency_id = (parseFloat($('#this_currency_id').val()) > 0 ? $('#this_currency_id').val() : this.currency);
						var fa_symbol = $('#curr_sym_'+this_currency_id).val();
						var currency_abbr = $('#curr_abbr_'+this.currency).val();
						
						var this_ask = $('#ask_'+this.id);
						if (this_ask.length > 0) {
							$(this_ask).find('.order_amount').html(this.btc);
							$('#ask_'+this.id+'.double').find('.order_amount').html(this.btc);
							$(this_ask).find('.order_price').html(formatCurrency((this.btc_price > 0) ? this.btc_price : this.stop_price));
							$('#ask_'+this.id+'.double').find('.order_price').html(formatCurrency(this.stop_price));
							if (notrades) {
								$(this_ask).find('.order_value').html(formatCurrency(parseFloat(this.btc) * parseFloat((this.btc_price > 0) ? this.btc_price : this.stop_price)));
								$('#ask_'+this.id+'.double').find('.order_value').html(formatCurrency(parseFloat(this.btc) * parseFloat(this.stop_price)));
								if (open_orders_user) {
									var double = 0;
									if (this.market_price == 'Y')
										var type = '<div class="identify market_order">M</div>';
									else if (this.btc_price > 0 && !(this.stop_price > 0))
										var type = '<div class="identify limit_order">L</div>';
									else if (this.stop_price > 0 && !(this.btc_price > 0))
										var type = '<div class="identify stop_order">S</div>';
									else if (this.stop_price > 0 && this.btc_price > 0) {
										var type = '<div class="identify limit_order">L</div>';
										double = 1;
									}
									$(this_ask).find('.identify').replaceWith(type);
									if (!double)
										$('#ask_'+this.id+'.double').remove();
									
									$(this_ask).find('.usd_price').val(this.usd_price);
								}
							}
						}
						else {
							var last_price = 0;
							var mine = (cfg_user_id > 0 && cfg_user_id == this.user_id && !open_orders_user && this.currency == this_currency_id) ? '<a class="fa fa-user" href="open-orders.php?id='+this.id+'" title="'+($('#your_order').val())+'"></a>' : '';
							var json_elem = this;
							var skip_next = false;
							var insert_elem = false;
							var before = false;
							
							var j = 1;
							if ($('#asks_list .order_price').length > 0) {
								$.each($('#asks_list .order_price'),function(i){
									if (skip_next) {
										skip_next = false;
										i++;
										return;
									}
									
									var price = parseFloat($(this).html());
									var next_price = ($(this).parents('tr').next('tr').find('.order_price').length > 0) ? parseFloat($(this).parents('tr').next('tr').find('.order_price').html()) : 0;
									var new_price = parseFloat(json_elem.btc_price);
									var active_asks = $('#asks_list .order_price').length;
									this_elem = (next_price == price) ? $(this).parents('tr').next('tr').find('.order_price') : this;
									this_elem = ($(this_elem).parents('tr').next('tr').hasClass('double')) ? $(this_elem).parents('tr').next('tr').find('.order_price') : this_elem;
									skip_next = (next_price == price);
									
									if (new_price < price && new_price > last_price) {
										insert_elem = $(this_elem).parents('tr');
										before = 1;
									}
									else if (new_price == price) 
										insert_elem = $(this_elem).parents('tr');
									else if (new_price > price && active_asks == j)
										insert_elem = $(this_elem).parents('tr');
									
									if (insert_elem)
										return false;
										
									last_price = price;
									j++;
								});
							}
							else {
								insert_elem = $('#no_asks');
								$('#no_asks').css('display','none');
							}
							
							if (notrades) {
								var usd_price = '';
								if (open_orders_user) {
									var double = 0;
									if (json_elem.market_price == 'Y')
										var type = '<td><div class="identify market_order">M</div></td>';
									else if (json_elem.btc_price > 0 && !(json_elem.stop_price > 0))
										var type = '<td><div class="identify limit_order">L</div></td>';
									else if (json_elem.stop_price > 0 && !(json_elem.btc_price > 0))
										var type = '<td><div class="identify stop_order">S</div></td>';
									else if (json_elem.stop_price > 0 && json_elem.btc_price > 0) {
										var type = '<td><div class="identify limit_order">L</div></td>';
										double = 1;
									}
									
									usd_price = '<input type="hidden" class="usd_price" value="'+(json_elem.usd_price ? json_elem.usd_price : json_elem.btc_price)+'" /><input type="hidden" class="order_date" value="'+json_elem.date+'" />';
								}
								
								var edit_str = (open_orders_user) ? '<td><a title="'+$('#cfg_orders_edit').val()+'" href="edit-order.php?order_id='+json_elem.id+'"><i class="fa fa-pencil"></i></a> <a title="'+$('#cfg_orders_delete').val()+'" href="open-orders.php?delete_id='+json_elem.id+'&uniq='+$('#uniq').val()+'"><i class="fa fa-times"></i></a></td>' : false;
								var string = '<tr class="ask_tr" id="ask_'+json_elem.id+'">'+usd_price+type+'<td>'+mine+fa_symbol+'<span class="order_price">'+(formatCurrency((json_elem.btc_price > 0) ? json_elem.btc_price : json_elem.stop_price))+'</span> '+((json_elem.btc_price != json_elem.fiat_price) ? '<a title="'+$('#orders_converted_from').val().replace('[currency]',currency_abbr)+'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '')+'</td><td><span class="order_amount">'+json_elem.btc+'</span></td><td>'+fa_symbol+'<span class="order_value">'+formatCurrency(parseFloat(json_elem.btc) * parseFloat(json_elem.btc_price))+'</span></td>'+edit_str+'</tr>';
								
								if (double)
									string += '<tr class="ask_tr double" id="ask_'+json_elem.id+'"><td><div class="identify stop_order">S</div></td><td>'+mine+fa_symbol+'<span class="order_price">'+(formatCurrency(json_elem.stop_price))+'</span></td><td><span class="order_amount">'+json_elem.btc+'</span></td><td>'+fa_symbol+'<span class="order_value">'+formatCurrency(parseFloat(json_elem.btc) * parseFloat(json_elem.btc_price))+'</span></td><td><span class="oco"><i class="fa fa-arrow-up"></i> OCO</span></td></tr>';
							}
							else
								var string = '<tr class="ask_tr" id="ask_'+json_elem.id+'"><td>'+mine+'<span class="order_amount">'+json_elem.btc+'</span> BTC</td><td>'+fa_symbol+'<span class="order_price">'+(formatCurrency(json_elem.btc_price))+'</span> '+((json_elem.btc_price != json_elem.fiat_price) ? '<a title="'+$('#orders_converted_from').val().replace('[currency]',currency_abbr)+'" class="fa fa-exchange" href="" onclick="return false;"></a>' : '')+'</td></tr>';
							
							if (before)
								var elem = $(string).insertBefore(insert_elem);
							else
								var elem = $(string).insertAfter(insert_elem);
						
							$(elem).children('td').effect("highlight",{color:"#A2EEEE"},2000);
						}
					});
					
					sortTable('#asks_list',((notrades) ? 0 : 1),0,sort_column);
				}
				else {
					$('#no_asks').css('display','');
				}
				
				if ($("#graph_orders").length > 0 && (depth_chart_data.bids.length > 0 || depth_chart_data.asks > 0))
					graphOrders(depth_chart_data);
				
				if (parseFloat(json_data.last_price) && $('#last_price').length > 0) {
					var lp_prev = $('#last_price').val();
					var lp_now = $('<div/>').html(json_data.fa_symbol + formatCurrency(json_data.last_price) + json_data.last_price_curr).text();
					$('#last_price').val(lp_now);
					
					if (json_data.last_trans_color == 'price-green')
						$('#last_price').removeClass('price-red').addClass(json_data.last_trans_color);
					else
						$('#last_price').removeClass('price-green').addClass(json_data.last_trans_color);
					
					if (lp_prev != lp_now) 
						$('#last_price').effect("highlight",{color:"#A2EEEE"},1000);
				}
				
				var current_price = ($('#asks_list .order_price').length > 0) ? parseFloat($('#asks_list .order_price:first').html().replace(',','')) : 0;
				var current_bid = ($('#bids_list .order_price').length > 0) ? parseFloat($('#bids_list .order_price:first').html().replace(',','')) : 0;
				
				if ($('#buy_price').length > 0 && $('#buy_price').is('[readonly]') && current_price > 0) {
					$('#buy_price').val(formatCurrency(current_price));
					$("#buy_price").trigger("change");
				}
				if ($('#sell_price').length > 0 && $('#sell_price').is('[readonly]') && current_bid > 0) {
					$('#sell_price').val(formatCurrency(current_bid));
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
				
				$('#buy_user_available').html(json_data.available_fiat);
				$('#sell_user_available').html(json_data.available_btc);
			});
		}
	},(!notrades ? 2000 : 5000));
}

function formatCurrency(amount,is_btc) {
	if (!is_btc)
		return parseFloat(amount).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	else
		return parseFloat(amount).toFixed(8).toString();
}

function updateTransactionsList() {
	if (!($('#refresh_transactions').length > 0))
		return false;
	
	var gmt_offset = parseInt($('#gmt_offset').val()) * -1;
	var update = setInterval(function(){
		while (!ajax_active) {
			var currency = $('#graph_orders_currency').val();
			var type = $('#type').val();
			var order_by = $('#order_by').val();
			var page = $('#page').val();
			
			$.getJSON("includes/ajax.transactions.php?currency="+currency+'&type='+type+'&order_by='+order_by+'&page='+page,function(transactions) {
				if (transactions != null) {
					var last = false;
					$.each(transactions,function(i) {
						var transaction = transactions[i];
						var this_transaction = $('#transaction_'+transaction.id);

						if (this_transaction.length > 0) {
							last = this_transaction;
							return;
						}
						
						var this_fa_symbol = $('#curr_sym_'+transaction.currency).val();
						var string = '<tr id="transaction_'+transaction.id+'">';
						string += '<td>'+transaction.type+'</td>';
						string += '<td><input type="hidden" class="localdate" value="'+(parseInt(transaction.datestamp) + gmt_offset)+'" /></td>';
						string += '<td>'+((parseFloat(transaction.btc_net)).toFixed(8))+'</td>';
						string += '<td>'+this_fa_symbol+formatCurrency(transaction.btc_net * transaction.fiat_price)+'</td>';
						string += '<td>'+this_fa_symbol+formatCurrency(transaction.fiat_price)+'</td>';
						string += '<td>'+this_fa_symbol+formatCurrency(transaction.fee * transaction.fiat_price)+'</td>';
						string += '</tr>';
						
						var elem = $(string).insertAfter((last) ? $(last) : $('#table_first'));
						$(elem).children('td').effect("highlight",{color:"#A2EEEE"},2000);
						$('#no_transactions').css('display','none');
						
						localDates();
						last = this_transaction;
					});
				}
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
				$('#buy_price').val(json_data.current_ask.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
				$('#sell_price').val(json_data.current_bid.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
				$('#sell_user_available').html(json_data.available_btc);
				$('#buy_user_available').html(json_data.available_fiat);
				$('#this_currency_id').val(json_data.currency_info.id);
				calculateBuyPrice();
			});
		}
	});
}

function calculateBuy() {
	$('#buy_amount,#buy_price,#buy_stop_price,#sell_amount,#sell_price,#sell_stop_price').bind("keyup change", function(){
		calculateBuyPrice();
	});
	
	$('#btc_amount,#fiat_amount').bind("keyup change", function(){
		calculateWithdrawal();
	});
	
	$('#buy_amount,#buy_price,#sell_amount,#sell_price,#fiat_amount,#btc_amount,#buy_stop_price,#sell_stop_price').bind("keypress", function(e){
		var charCode = (e.which) ? e.which : e.keyCode;
        if (charCode != 46 && charCode != 39 && charCode != 37 && charCode > 31 && (charCode < 48 || charCode > 57))
           return false;

        return true;
	});
	
	$('#buy_amount,#buy_price,#sell_amount,#sell_price,#fiat_amount,#btc_amount,#buy_stop_price,#sell_stop_price').focus(function(){
		if (!(parseFloat($(this).val()) > 0))
			$(this).val('');
	});
	
	$('#buy_amount,#buy_price,#sell_amount,#sell_price,#fiat_amount,#btc_amount,#buy_stop_price,#sell_stop_price').blur(function(){
		if (!(parseFloat($(this).val()) > 0))
			$(this).val('0');
	});
	
	$('#buy_market_price,#sell_market_price').click(function(){
		if ($(this).is('[readonly]')) {
			alert($('#buy_errors_no_compatible').val());
			$(this).prop('checked','');
		}
		else {
			$(this).prop('checked','checked');
		}
	});
	
	$('#buy_market_price').click(function(){
		if ($(this).is(':checked') && !$(this).is('[readonly]')) {
			$('#buy_stop').prop('checked','');
			$('#buy_limit').prop('checked','');
			$('#buy_price_market_label').css('display','');
			$('#buy_price_limit_label').css('display','none');
			$('#buy_price').attr('readonly','readonly');
			$('#buy_price_container').css('display','');
			
			if ($('#buy_limit').is(':checked'))
				$('#buy_stop_container').hide(400);
			else
				$('#buy_stop_container').css('display','none');
			
			calculateBuyPrice();
		}
	});
	
	$('#sell_market_price').click(function(){
		if ($(this).is(':checked') && !$(this).is('[readonly]')) {
			$('#sell_stop').prop('checked','');
			$('#sell_limit').prop('checked','');
			$('#sell_price_market_label').css('display','');
			$('#sell_price_limit_label').css('display','none');
			$('#sell_price').attr('readonly','readonly');
			$('#sell_price_container').css('display','');
			
			if ($('#sell_limit').is(':checked'))
				$('#sell_stop_container').hide(400);
			else
				$('#sell_stop_container').css('display','none');
			
			calculateBuyPrice();
		}
	});
	
	$('#buy_stop').click(function(){
		if ($(this).is(':checked')) {
			$('#buy_market_price').prop('checked','');
			$('#buy_price').removeAttr('readonly');
			if ($('#buy_limit').is(':checked')) {
				$('#buy_stop_container').show(400);
			}
			else {
				$('#buy_stop_container').css('display','');
				$('#buy_price_container').css('display','none');
			}
		}
		else {
			if ($('#buy_limit').is(':checked')) {
				$('#buy_stop_container').hide(400);
			}
			else {
				$(this).prop('checked','checked');
			}
		}
		calculateBuyPrice();
	});
	
	$('#sell_stop').click(function(){
		if ($(this).is(':checked')) {
			$('#sell_market_price').prop('checked','');
			$('#sell_price').removeAttr('readonly');
			if ($('#sell_limit').is(':checked')) {
				$('#sell_stop_container').show(400);
			}
			else {
				$('#sell_stop_container').css('display','');
				$('#sell_price_container').css('display','none');
			}
		}
		else {
			if ($('#sell_limit').is(':checked')) {
				$('#sell_stop_container').hide(400);
			}
			else {
				$(this).prop('checked','checked');
			}
		}
		calculateBuyPrice();
	});
	
	$('#buy_limit').click(function(){
		if ($(this).is(':checked')) {
			$('#buy_market_price').prop('checked','');
			$('#buy_price').removeAttr('readonly');
			$('#buy_price_market_label').css('display','none');
			$('#buy_price_limit_label').css('display','');
			
			if ($('#buy_stop').is(':checked')) {
				$('#buy_price_container').show(400);
			}
			else {
				$('#buy_price_container').css('display','');
				$('#buy_stop_container').css('display','none');
			}
		}
		else {
			if ($('#buy_stop').is(':checked')) {
				$('#buy_price_container').hide(400);
			}
			else {
				$(this).prop('checked','checked');
			}
		}
		calculateBuyPrice();
	});
	
	$('#sell_limit').click(function(){
		if ($(this).is(':checked')) {
			$('#sell_market_price').prop('checked','');
			$('#sell_price').removeAttr('readonly');
			$('#sell_price_market_label').css('display','none');
			$('#sell_price_limit_label').css('display','');
			
			if ($('#sell_stop').is(':checked')) {
				$('#sell_price_container').show(400);
			}
			else {
				$('#sell_price_container').css('display','');
				$('#sell_stop_container').css('display','none');
			}
		}
		else {
			if ($('#sell_stop').is(':checked')) {
				$('#sell_price_container').hide(400);
			}
			else {
				$(this).prop('checked','checked');
			}
		}
		calculateBuyPrice();
	});
	
	$('#method').bind("keyup change", function(){
		if ($(this).val() != 'google') {
			$('.method_show').show(400);
		}
		else {
			$('.method_show').hide(400);
		}
	});
}

function calculateBuyPrice() {
	var user_fee = parseFloat($('#user_fee').val());
	var user_fee1 = parseFloat($('#user_fee1').val());
	
	var first_ask = ($('#asks_list .order_price').length > 0) ?  parseFloat($('#asks_list .order_price:first').html().replace(',','')) : 0;
	var buy_amount = ($('#buy_amount').val()) ? parseFloat($('#buy_amount').val().replace(',','')) : 0;
	var buy_price = ($('#buy_price').val()) ? parseFloat($('#buy_price').val().replace(',','')) : 0;
	var buy_stop_price = ($('#buy_stop_price').val()) ? parseFloat($('#buy_stop_price').val().replace(',','')) : 0;
	var buy_fee = (buy_price >= first_ask || $('#buy_market_price').is(':checked')) ? user_fee : user_fee1;
	var buy_subtotal = buy_amount * (($('#buy_stop').is(':checked') && !$('#buy_limit').is(':checked')) ? buy_stop_price : buy_price);
	var buy_commision = (buy_fee * 0.01) * buy_subtotal;
	var buy_total = buy_subtotal + buy_commision;
	$('#buy_subtotal').html(formatCurrency(buy_subtotal));
	$('#buy_total').html(formatCurrency(buy_total));
	$('#buy_user_fee').html((buy_price >= first_ask || $('#buy_market_price').is(':checked')) ? user_fee.toFixed(2) : user_fee1.toFixed(2));
	
	var first_bid = ($('#bids_list .order_price').length > 0) ? parseFloat($('#bids_list .order_price:first').html().replace(',','')) : 0;
	var sell_amount = ($('#sell_amount').val()) ? parseFloat($('#sell_amount').val().replace(',','')) : 0;
	var sell_price = ($('#sell_price').val()) ? parseFloat($('#sell_price').val().replace(',','')) : 0;
	var sell_stop_price = ($('#sell_stop_price').val()) ? parseFloat($('#sell_stop_price').val().replace(',','')) : 0;
	var sell_fee = ((sell_price > 0 && sell_price <= first_bid) || $('#sell_market_price').is(':checked')) ? user_fee : user_fee1;
	var sell_subtotal = sell_amount * (($('#sell_stop').is(':checked') && !$('#sell_limit').is(':checked')) ? sell_stop_price : sell_price);
	var sell_commision = (sell_fee * 0.01) * sell_subtotal;
	var sell_total = sell_subtotal - sell_commision;
	$('#sell_subtotal').html(formatCurrency(sell_subtotal));
	$('#sell_total').html(formatCurrency(sell_total));
	$('#sell_user_fee').html(((sell_price > 0 && sell_price <= first_bid) || $('#sell_market_price').is(':checked')) ? user_fee.toFixed(2) : user_fee1.toFixed(2));
}

function buttonDisable() {
	$('form').submit(function() {
		$('.but_user').addClass('loading');
		$('.but_user').attr('disabled','disabled');
	});
}

function localDates() {
	$('.localdate').each(function(){
		var date = new Date(parseInt($(this).val()*1000));
		//var offset = date.getTimezoneOffset() * 60;
		//var date1 = new Date(parseInt((parseInt($(this).val()) + parseInt(offset))*1000));
		var hours = date.getHours();
		var minutes = date.getMinutes();
		var ampm = hours >= 12 ? 'pm' : 'am';
		hours = hours % 12;
		hours = hours ? hours : 12; // the hour '0' should be '12'
		minutes = minutes < 10 ? '0'+minutes : minutes;
		var strTime = hours + ':' + minutes + ' ' + ampm;
		
		$(this).parent().html($('#javascript_mon_'+date.getMonth()).val()+' '+date.getDate()+', '+date.getFullYear()+', '+strTime);
	});
}

function timeSince(elem) {
	var miliseconds = $(elem).siblings('.time_since_seconds').val();
	var date = new Date(parseInt(miliseconds)*1000);
	//var offset = date.getTimezoneOffset() * 60;
	//var date1 = new Date(parseInt(miliseconds) + (parseInt(offset)*1000));
	var time_unit;
	
	$(elem).countdown({ 
	    since: date,
	    significant: 1,
	    layout: '{o<}{on} {ol}{o>}{w<}{wn} {wl}{w>}{d<}{dn} {dl}{d>}{h<}{hn} {hl}{h>}{m<}{mn} {ml}{m>}{s<}{sn} {sl}{s>}'
	});
}

/*
function timeUntil(elem) {
	var miliseconds = $(elem).siblings('.time_until_seconds').val();
	var date = new Date(parseInt(miliseconds)*1000);
	var lang = $('#language_selector').val();
	lang = (lang = 'zh') ? 'zh-CN' : lang;
	
		
	$(elem).countdown({ 
	    until: date,
	    significant: 1,
	    onExpiry: pageRefresh,
	    layout: '{o<}{on} {ol}{o>}{w<}{wn} {wl}{w>}{d<}{dn} {dl}{d>}{h<}{hn} {hl}{h>}{m<}{mn} {ml}{m>}{s<}{sn} {sl}{s>}'
	});
}
*/

function pageRefresh() {
	//location.reload(); 
	$('.error').remove();
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
				calculateWithdrawal();
			});
		}
	});
}

function calculateWithdrawal() {
	var btc_amount = ($('#btc_amount').val()) ? parseFloat($('#btc_amount').val().replace(',','')) : 0;
	var btc_fee = ($('#withdraw_btc_network_fee').html()) ? parseFloat($('#withdraw_btc_network_fee').html().replace(',','')) : 0;
	var btc_total = (btc_amount > 0) ? btc_amount - btc_fee : 0;
	var fiat_amount = ($('#fiat_amount').val()) ? parseFloat($('#fiat_amount').val().replace(',','')) : 0;
	var fiat_fee = ($('#withdraw_fiat_fee').html()) ? parseFloat($('#withdraw_fiat_fee').html().replace(',','')) : 0;
	var fiat_total = (fiat_amount > 0) ? fiat_amount - fiat_fee : 0;
	
	$('#withdraw_btc_total').html(formatCurrency(btc_total,1));
	$('#withdraw_fiat_total').html(formatCurrency(fiat_total));
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

function sortTable(elem_selector,col_num,desc,col_name){
	var rows = $(elem_selector+' tr:not(:first,.double)').get();
	desc = (col_name == '.order_date' || col_name == '.order_amount') ? true : desc;
	
	if (!col_name) {
		if ($('.usd_price').length > 0)
			col_name = '.usd_price';
		else
			col_name = '.order_price';
	}
	
	rows.sort(function(a, b) {
		if ($(a).children('th').length > 0)
			return -1;

		var A = (col_name != '.order_price') ? parseFloat($(a).find(col_name).val()) : parseFloat($(a).find('.order_price').eq(col_num).text().replace('$','').replace(',','').replace('BTC',''));
		var B = (col_name != '.order_price') ? parseFloat($(b).find(col_name).val()) : parseFloat($(b).find('.order_price').eq(col_num).text().replace('$','').replace(',','').replace('BTC',''));
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
		var id = $(row).attr('id');
		$('#'+id+'.double').insertAfter(row);
	});
}

function blink() {
	if (!($('.blink').length > 0))
		return false;
	
	var i = 0;
	var on = false;
	var elems = $('.blink');
	var blink = setInterval(function(){
		$(elems).toggleClass('blink');

		if (i > 15 && !on) 
			clearInterval(blink);
		i++;
		on = (!on);
	},300);
}

function confirmDeleteAll(uniq,e) {
	e.preventDefault();
	
	if (!uniq)
		return false;
	
    var r = confirm($('#order-cancel-all-conf').val());
    if (r == true) {
    	window.location.href = 'open-orders.php?delete_all=1&uniq='+uniq;
    }
}

function startTicker() {
	var elem = $('.ticker .scroll');
	var elem_f = $('.ticker .scroll');
	var elem_sub_l = $('.ticker .scroll a:last');
	var elem_sub_l_w = elem_sub_l.outerWidth();
	var elem_w = elem.outerWidth();
	var window_w = $(window).width();
	var cloned = false;

	var properties = {duration:400,easing:'linear',complete:function(){
		$('.ticker .scroll').animate({left:'-=50px'},properties);
	},progress:function(){
		offset = elem_sub_l.offset();
		if (elem_sub_l && offset) {
			if ((window_w + 500) - offset.left > 0) {
				elem = $('.ticker .scroll:last').clone().css('left',(offset.left + elem_sub_l_w)+'px').insertAfter('.ticker .scroll:last');
				elem_sub_l = elem.find('a:last');
				cloned = true;
			}
		}
		if (elem_f && elem_f.offset()) {
			if ((elem_f.offset().left * -1) >= elem_f.outerWidth() && cloned) {
				console.log(elem_f.offset().left);
				elem_f.remove();
				elem_f = $('.ticker .scroll:first');
				cloned = false;
			}
		}
			
	}};
	elem.animate({left:'-=50px'},properties);
}

$(document).ready(function() {
	if ($("#graph_price_history").length > 0) {
		var currency = $('#graph_price_history_currency').val();
		graphPriceHistory('1mon',currency);
		startTicker();
	}
	
	if ($("#graph_orders").length > 0) {
		graphOrders();
		//var update = setInterval(graphOrders,10000);
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
		var lang = $(this).val();
		var alternate = $('[hreflang="'+lang+'"][rel="alternate"]').attr('href');
		
		if (typeof alternate == 'undefined') {
			var url = window.location.pathname;
			alternate = url.substring(url.lastIndexOf('/')+1)+'?lang='+lang;
		}

		window.location.href = alternate;
	});
	
	$('#currency_selector').bind("keyup change", function(){
		var lang = $('#language_selector').val();
		var url = $('#url_'+'index_php'+'_'+lang).val();
		window.location.href = url+'?currency='+$(this).val();
	});
	
	$('#fee_currency').bind("keyup change", function(){
		var lang = $('#language_selector').val();
		var url = $('#url_'+'fee-schedule_php'+'_'+lang).val();
		window.location.href = url+'?currency='+$(this).val();
	});
	
	$('#ob_currency').bind("keyup change", function(){
		var lang = $('#language_selector').val();
		var url = $('#url_'+'order-book_php'+'_'+lang).val();
		window.location.href = url+'?currency='+$(this).val();
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
	
	var first_text = $('input:text').first();
	if (first_text.length > 0) {
		if ($(first_text).val() == '0')
			$(first_text).val('').focus();
		else if (!($(first_text).val().length > 0))
			$(first_text).focus();
	}
	
	$(window).scroll(function(){
        if ($(this).scrollTop() > 100) {
            $('.scrollup').fadeIn();
        } else {
            $('.scrollup').fadeOut();
        }
    });

    $('.scrollup').click(function(){
        $("html, body").animate({ scrollTop: 0 }, 500);
        return false;
    });
    
    selectnav('tiny', {
		label: '--- Navigation --- ',
		indent: '-'
	});
	
    ddsmoothmenu.init({
    	mainmenuid: "menu",
    	orientation: 'h',
    	classname: 'menu',
    	contentsource: "markup"
    })
    
	filtersUpdate();
	paginationUpdate();
	switchBuyCurrency();
	calculateBuy();
	buttonDisable();
	localDates();
	switchAccount();
	switchAccount1();
	//expireSession();
	updateTransactionsList();
	//timeUntil();
	blink();
});

// Sticky Menu Core
var Modernizr = function () {}
jQuery(function(t){var n,e,i,o,r,s,a,u,l,h,c,p,d,f,g,m,v,y,$,w,C,x,k,b,T,I,H,M,P,S,z,O,q,A,F,L,B,D,N,V,E,K,_;return K=t(window),z=t(document),window.App={},n=t("body, html"),o=t("#header"),s=t("#headerPush"),i=t("#footer"),O="easeInOutExpo",k="/wp-content/themes/KIND",b=39,C=37,P=38,v=40,y=27,N=navigator.userAgent,q=N.match(/(Android|iPhone|BlackBerry|Opera Mini)/i),A=N.match(/(iPad|Kindle)/i),E=function(){return window.innerHeight||K.height()},_=function(){return window.innerWidth||K.width()},V=function(t){return K.resize(function(){return t()}),K.bind("orientationchange",function(){return t()})},r=o.find(".button"),m=o.find("#trueHeader"),D=m.outerHeight(),L=0===t("#masthead").length&&0===t("#slideshow").length,B=m.offset().top,F=B+5*D,setInterval(function(){var t;return t=K.scrollTop(),o.css({height:o.outerHeight()}),t>=B?(o.addClass("sticky"),0>=B||r.hasClass("inv")&&r.removeClass("transparent inv")):(o.removeClass("sticky"),0>=B||r.hasClass("inv")||r.addClass("transparent inv")),t>=F?o.addClass("condensed"):o.removeClass("condensed")},10),1===(c=t("#slideshow")).length&&(I=function(){function t(){this.$slideshow=c,this.$slides=this.$slideshow.find(".slide"),this.max=this.$slides.length-1}return t.prototype.autoplay=function(){var t=this;if(0!==this.max)return this.interval=setInterval(function(){return t.next()},6e3),this},t.prototype.clear=function(){return clearInterval(this.interval)},t.prototype.goToSlide=function(t){var n,e,i=this;if(t!==this.current)return null!=this.interval&&this.clear(),t>this.max&&(t=0),0>t&&(t=this.max),this.$slides.removeClass("active"),this.$slides.eq(t).addClass("active"),this.$h2=this.$slides.eq(t).find("h2"),setTimeout(function(){return i.typeOutSteps()},1e3),(n=this.$slides.eq(t).find(".product_pledged"))&&(e=Math.round(parseInt(n.text())),n.text((""+e).replace(/(\d)(?=(\d{3})+(?!\d))/g,"$1,")),n.css({opacity:1})),this.current=t,this.autoplay(),this},t.prototype.next=function(){return this.goToSlide(this.current+1)},t.prototype.prev=function(){return this.goToSlide(this.current-1)},t.prototype.typeOutSteps=function(){var t,n,e,i,o,r,s=this;return n=0,i=this.$h2.data().sequences.split(","),t=e=i[n],r=function(r){var a,u;return null==r&&(r=0),n!==i.length?(u=0,a=function(){var l;return r++,l=t.substring(0,r),s.$h2.text(l),s.$h2.addClass("typing"),clearInterval(u),r!==t.length?u=setInterval(a,s.human()):(n++,s.$h2.removeClass("typing"),n!==i.length?(r=0,e=t,t=i[n],setTimeout(o,s.human(10))):void 0)},a()):void 0},r(),o=function(){var n,i,o,a,u,l,h,c,p,d,f,g;for(a=e.split(" "),n=t.split(" "),p="",l=0,c=f=0,g=a.length;g>f;c=++f)d=a[c],d===n[c]&&(l++,l>c&&(p+=d+" "));return u=t.length,h=p.length>0?p.length-1:0,o=0,i=function(){var t;return t=e.substring(0,u),s.$h2.text(t),s.$h2.addClass("typing"),u--,clearInterval(o),u===h?(s.$h2.removeClass("typing"),setTimeout(function(){return r(h)},s.human(5))):o=setInterval(i,s.human())},i()},this},t.prototype.human=function(t){return null==t&&(t=1),Math.round(170*Math.random()+30)*t},t}(),T=new I,setTimeout(function(){return T.goToSlide(0)},100),t("#slideDecor").bind("click tap",function(){return n.stop(1,1).animate({scrollTop:t("#content").offset().top-100},660,O)}),f=t("#theVideo"),t("#slideshow .button.transparent").on("click tap",function(t){return t.preventDefault(),n.stop(1,1).animate({scrollTop:f.offset().top-o.outerHeight()},450,function(){return S(f.find("iframe"))})})),Modernizr.csstransitions&&(l=t("[data-parallax]")).length>=1&&K.bind("load scroll touchmove",function(){var t,n,e;return e=K.scrollTop(),n=parseFloat(.35*e),n-=148-n,t="center "+n+"px",l.css({backgroundPosition:t})}),1===(g=t("#timeline")).length&&(M=function(){function n(){this.$timeline=g,this.$fx=this.$timeline.find("#timelineFx"),this.$items=this.$timeline.find("article")}return n.prototype.checkPosition=function(n){return null==n&&(n=K.scrollTop()),this.$items.each(function(e,i){var o;return i=t(i),o=i.offset().top,n>=o-K.height()?i.css({opacity:1}):void 0}),this},n.prototype.resizeFx=function(){var t;if(null!=this.$timeline)return t=this.$timeline.outerHeight(),t-=this.$timeline.find("article:visible:last-child").outerHeight(),this.$fx.css({height:t}),this},n}(),H=new M,K.load(function(){return H.resizeFx()}),setInterval(function(){return H.checkPosition()},100)),w=function(){function t(){this.zoom=15,this.lat=30.191969,this.long=-98.084782}return t.prototype.init=function(){return this.map=new GMaps({div:"#mapCanvas",zoom:this.zoom,lat:this.lat,lng:this.long,zoomControlOpt:{style:"SMALL",position:"TOP_LEFT"},zoomControl:!0,panControl:!0,streetViewControl:!0,mapTypeControl:!1,scrollwheel:!1}),this.addMarker(),this},t.prototype.addMarker=function(){return this.map.addMarker({lat:this.lat,lng:this.long,icon:k+"/assets/images/icon-marker.png"}),this},t}(),1===(u=t("#mapCanvas")).length&&($=new w,window.onload=function(){return $.init()},K.bind("load resize",function(){var t;return t=K.height()-o.outerHeight()+72,888>t&&(t=888),u.css({height:t}),$.map.setCenter($.lat,$.long)})),1===(e=t("#faqListing")).length&&e.find(".faqs").isotope({itemSelector:".faq"}),(p=t(".specs")).length>=1&&p.each(function(){var n,e,i;return i=t(this),e=i.find("figure"),n=i.find("aside"),e.next().is("aside")&&e.outerHeight()<n.outerHeight()?e.css({height:n.outerHeight()+50}):void 0}),1===(d=t("#thePosts")).length&&d.infinitescroll({navSelector:"#postNav",nextSelector:"#postNav a:first-child",itemSelector:"#thePosts .post"}),1===(h=t("#popup")).length&&(x=function(){function n(){var n=this;this.$popup=h,this.$content=this.$popup.find("#popupContent"),this.$popup.add(t("#close")).bind("click tap",function(){return n.close()}),this.$content.bind("click tap",function(t){return t.stopPropagation()}),K.bind("keydown",function(t){return t.keyCode===y?n.close():void 0}),this.$content.find("a").bind("click tap",function(){return window.location=t(this).attr("href")})}return n.prototype.open=function(n){var e=this;return t("#popupContent-load").empty().append(t(n).html()),this.$popup.stop(1,1).fadeIn(750,function(){var t;return 1===(t=e.$popup.find("iframe")).length?S(t):void 0}),this},n.prototype.close=function(){var t=this;return this.$popup.stop(1,1).fadeOut(750,function(){return t.$popup.find("#popupContent-load").empty()}),this},n}(),window.App.PopupModal=new x),S=function(t){var n,e;return n=t[0],e=n.src,e.match(/autoplay/)?(n.src=e.replace("autoplay=0","autoplay=1"),console.log(n.src)):n.src+=0>e.indexOf("?")?"?autoplay=1":"&autoplay=1"},t('[data-action="revealVideo"]').on("click tap",function(){var n,e,i;return i=t(this),e=i.parents("figure"),n=e.find("iframe"),n.addClass("over"),S(n)}),t("iframe:not(.skip)").each(function(n,e){var i,o;return e=t(this)[0],o="wmode=transparent",i=e.src,e.src+=0>i.indexOf("?")?"?"+o:"&"+o}),1===(a=t(".id-widget-wrap .main-btn")).length?(a.text("Back Us"),a.css({opacity:1})):void 0});

/* SelectNav.js (v. 0.1)
 * Converts your <ul>/<ol> navigation into a dropdown list for small screens */
window.selectnav=function(){"use strict";var a=function(a,b){function l(a){var b;a||(a=window.event),a.target?b=a.target:a.srcElement&&(b=a.srcElement),b.nodeType===3&&(b=b.parentNode),b.value&&(window.location.href=b.value)}function m(a){var b=a.nodeName.toLowerCase();return b==="ul"||b==="ol"}function n(a){for(var b=1;document.getElementById("selectnav"+b);b++);return a?"selectnav"+b:"selectnav"+(b-1)}function o(a){i++;var b=a.children.length,c="",k="",l=i-1;if(!b)return;if(l){while(l--)k+=g;k+=" "}for(var p=0;p<b;p++){var q=a.children[p].children[0],r=q.innerText||q.textContent,s="";d&&(s=q.className.search(d)!==-1||q.parentElement.className.search(d)!==-1?j:""),e&&!s&&(s=q.href===document.URL?j:""),c+='<option value="'+q.href+'" '+s+">"+k+r+"</option>";if(f){var t=a.children[p].children[1];t&&m(t)&&(c+=o(t))}}return i===1&&h&&(c='<option value="">'+h+"</option>"+c),i===1&&(c='<select class="selectnav" id="'+n(!0)+'">'+c+"</select>"),i--,c}a=document.getElementById(a);if(!a)return;if(!m(a))return;document.documentElement.className+=" js";var c=b||{},d=c.activeclass||"active",e=typeof c.autoselect=="boolean"?c.autoselect:!0,f=typeof c.nested=="boolean"?c.nested:!0,g=c.indent||"→",h=c.label||"- Navigation -",i=0,j=" selected ";a.insertAdjacentHTML("afterend",o(a));var k=document.getElementById(n());return k.addEventListener&&k.addEventListener("change",l),k.attachEvent&&k.attachEvent("onchange",l),k};return function(b,c){a(b,c)}}();

//** Smooth Navigational Menu- By Dynamic Drive DHTML code library: http://www.dynamicdrive.com
//** Script Download/ instructions page: http://www.dynamicdrive.com/dynamicindex1/ddlevelsmenu/
var ddsmoothmenu={
arrowimages: {down:[], right:[]},
transition: {overtime:300, outtime:300}, //duration of slide in/ out animation, in milliseconds
shadow: {enable:false, offsetx:5, offsety:5}, //enable shadow?
showhidedelay: {showdelay: 100, hidedelay: 200}, //set delay in milliseconds before sub menus appear and disappear, respectively
// end cfg
detectwebkit: navigator.userAgent.toLowerCase().indexOf("applewebkit")!=-1, //detect WebKit browsers (Safari, Chrome etc)
detectie6: document.all && !window.XMLHttpRequest,

getajaxmenu:function($, setting){ //function to fetch external page containing the panel DIVs
	var $menucontainer=$('#'+setting.contentsource[0]) //reference empty div on page that will hold menu
	$menucontainer.html("Loading Menu...")
	$.ajax({
		url: setting.contentsource[1], //path to external menu file
		async: true,
		error:function(ajaxrequest){
			$menucontainer.html('Error fetching content. Server Response: '+ajaxrequest.responseText)
		},
		success:function(content){
			$menucontainer.html(content)
			ddsmoothmenu.buildmenu($, setting)
		}
	})
},


buildmenu:function($, setting){
	var smoothmenu=ddsmoothmenu
	var $mainmenu=$("#"+setting.mainmenuid+">ul") //reference main menu UL
	$mainmenu.parent().get(0).className=setting.classname || "ddsmoothmenu"
	var $headers=$mainmenu.find("ul").parent()
	$headers.hover(
		function(e){
			$(this).children('a:eq(0)').addClass('selected')
		},
		function(e){
			$(this).children('a:eq(0)').removeClass('selected')
		}
	)
	$headers.each(function(i){ //loop through each LI header
		var $curobj=$(this).css({}) //reference current LI header
		var $subul=$(this).find('ul:eq(0)').css({display:'block'})
		$subul.data('timers', {})
		this._dimensions={w:this.offsetWidth, h:this.offsetHeight, subulw:$subul.outerWidth(), subulh:$subul.outerHeight()}
		this.istopheader=$curobj.parents("ul").length==1? true : false //is top level header?
		$subul.css({top:this.istopheader && setting.orientation!='v'? this._dimensions.h+"px" : 0})
		$curobj.children("a:eq(0)").css(this.istopheader? {paddingRight: smoothmenu.arrowimages.down[2]} : {})
		if (smoothmenu.shadow.enable){
			this._shadowoffset={x:(this.istopheader?$subul.offset().left+smoothmenu.shadow.offsetx : this._dimensions.w), y:(this.istopheader? $subul.offset().top+smoothmenu.shadow.offsety : $curobj.position().top)} //store this shadow's offsets
			if (this.istopheader)
				$parentshadow=$(document.body)
			else{
				var $parentLi=$curobj.parents("li:eq(0)")
				$parentshadow=$parentLi.get(0).$shadow
			}
			this.$shadow=$('<div class="ddshadow'+(this.istopheader? ' toplevelshadow' : '')+'"></div>').prependTo($parentshadow).css({left:this._shadowoffset.x+'px', top:this._shadowoffset.y+'px'})  //insert shadow DIV and set it to parent node for the next shadow div
		}
		$curobj.hover(
			function(e){
				var $targetul=$subul //reference UL to reveal
				var header=$curobj.get(0) //reference header LI as DOM object
				clearTimeout($targetul.data('timers').hidetimer)
				$targetul.data('timers').showtimer=setTimeout(function(){
					header._offsets={left:$curobj.offset().left, top:$curobj.offset().top}
					var menuleft=header.istopheader && setting.orientation!='v'? 0 : header._dimensions.w
					menuleft=(header._offsets.left+menuleft+header._dimensions.subulw>$(window).width())? (header.istopheader && setting.orientation!='v'? -header._dimensions.subulw+header._dimensions.w : -header._dimensions.w) : menuleft //calculate this sub menu's offsets from its parent
					if ($targetul.queue().length<=1){ //if 1 or less queued animations
						$targetul.css({left:menuleft+"px", width:header._dimensions.subulw+'px'}).animate({height:'show',opacity:'show'}, ddsmoothmenu.transition.overtime)
						if (smoothmenu.shadow.enable){
							var shadowleft=header.istopheader? $targetul.offset().left+ddsmoothmenu.shadow.offsetx : menuleft
							var shadowtop=header.istopheader?$targetul.offset().top+smoothmenu.shadow.offsety : header._shadowoffset.y
							if (!header.istopheader && ddsmoothmenu.detectwebkit){ //in WebKit browsers, restore shadow's opacity to full
								header.$shadow.css({opacity:1})
							}
							header.$shadow.css({overflow:'', width:header._dimensions.subulw+'px', left:shadowleft+'px', top:shadowtop+'px'}).animate({height:header._dimensions.subulh+'px'}, ddsmoothmenu.transition.overtime)
						}
					}
				}, ddsmoothmenu.showhidedelay.showdelay)
			},
			function(e){
				var $targetul=$subul
				var header=$curobj.get(0)
				clearTimeout($targetul.data('timers').showtimer)
				$targetul.data('timers').hidetimer=setTimeout(function(){
					$targetul.animate({height:'hide', opacity:'hide'}, ddsmoothmenu.transition.outtime)
					if (smoothmenu.shadow.enable){
						if (ddsmoothmenu.detectwebkit){ //in WebKit browsers, set first child shadow's opacity to 0, as "overflow:hidden" doesn't work in them
							header.$shadow.children('div:eq(0)').css({opacity:0})
						}
						header.$shadow.css({overflow:'hidden'}).animate({height:0}, ddsmoothmenu.transition.outtime)
					}
				}, ddsmoothmenu.showhidedelay.hidedelay)
			}
		) //end hover
	}) //end $headers.each()
	$mainmenu.find("ul").css({display:'none', visibility:'visible'})
},

init:function(setting){
	if (typeof setting.customtheme=="object" && setting.customtheme.length==2){ //override default menu colors (default/hover) with custom set?
		var mainmenuid='#'+setting.mainmenuid
		var mainselector=(setting.orientation=="v")? mainmenuid : mainmenuid+', '+mainmenuid
		document.write('<style type="text/css">\n'
			+mainselector+' ul li a {background:'+setting.customtheme[0]+';}\n'
			+mainmenuid+' ul li a:hover {background:'+setting.customtheme[1]+';}\n'
		+'</style>')
	}
	this.shadow.enable=(document.all && !window.XMLHttpRequest)? false : this.shadow.enable //in IE6, always disable shadow
	jQuery(document).ready(function($){ //ajax menu?
		if (typeof setting.contentsource=="object"){ //if external ajax menu
			ddsmoothmenu.getajaxmenu($, setting)
		}
		else{ //else if markup menu
			ddsmoothmenu.buildmenu($, setting)
		}
	})
}

}
