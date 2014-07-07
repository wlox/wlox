<?php
class Orders {
	function get($count=false,$page=false,$per_page=false,$currency=false,$user=false,$start_date=false,$show_bids=false,$order_by1=false,$order_desc=false,$dont_paginate=false) {
		global $CFG;
		
		$page = preg_replace("/[^0-9]/", "",$page);
		$per_page = preg_replace("/[^0-9]/", "",$per_page);
		$page = preg_replace("/[^0-9]/", "",$page);
		$currency = preg_replace("/[^a-zA-Z]/", "",$currency);
		$start_date = preg_replace ("/[^0-9: \-]/","",$start_date);
		
		$page = ($page > 0) ? $page - 1 : 0;
		$r1 = $page * $per_page;
		$order_arr = array('date'=>'orders.date','btc'=>'orders.btc','btcprice'=>'orders.btc_price','fiat'=>'usd_amount');
		$order_by = ($order_by1) ? $order_arr[$order_by1] : 'orders.btc_price';
		$order_desc = ($order_desc && ($order_by1 != 'date' && $order_by1 != 'fiat')) ? 'ASC' : 'DESC';
		$currency_info = $CFG->currencies[strtoupper($currency)];
		$user = ($user) ? User::$info['id'] : false;
		$type = ($show_bids) ? $CFG->order_type_bid : $CFG->order_type_ask;
		$user_id = (User::$info['id'] > 0) ? User::$info['id'] : '0';
		
		if (!$count)
			$sql = "SELECT orders.*, order_types.name_{$CFG->language} AS type, currencies.currency AS currency, (currencies.usd * orders.fiat) AS usd_amount, orders.btc_price AS fiat_price, (UNIX_TIMESTAMP(orders.date) * 1000) AS time_since, currencies.fa_symbol AS fa_symbol, IF(".$user_id." = orders.site_user,1,0) AS mine FROM orders ";
		else
			$sql = "SELECT COUNT(orders.id) AS total FROM orders ";
			
		$sql .= " 
		LEFT JOIN order_types ON (order_types.id = orders.order_type)
		LEFT JOIN currencies ON (currencies.id = orders.currency)
		WHERE 1 ";
			
		if ($user > 0)
			$sql .= " AND orders.site_user = $user ";
		if ($start_date > 0)
			$sql .= " AND orders.date >= '$start_date' ";
		if ($type > 0)
			$sql .= " AND orders.order_type = $type ";
		if ($currency)
			$sql .= " AND orders.currency = {$currency_info['id']} ";
			
		if ($per_page > 0 && !$count && !$dont_paginate)
			$sql .= " ORDER BY $order_by $order_desc LIMIT $r1,$per_page ";
		if (!$count && $dont_paginate)
			$sql .= " ORDER BY $order_by $order_desc ";
	
		$result = db_query_array($sql);
		if (!$count)
			return $result;
		else
			return $result[0]['total'];
	}
	
	function getRecord($order_id) {
		global $CFG;
		
		$order_id = preg_replace("/[^0-9]/", "",$order_id);
		
		if (!($order_id > 0))
			return false;
		
		$sql = "SELECT * FROM orders WHERE id = $order_id ";
		$result = db_query_array($sql);
		
		if ($result[0]) {
			$result[0]['user_id'] = User::$info['id'];
			$result[0]['is_bid'] = ($result[0]['order_type'] ==$CFG->order_type_bid);
		}
		
		return $result[0];
	}
	
	function getCurrentBid($currency,$currency_id=false) {
		global $CFG;
		
		$currency = preg_replace("/[^a-zA-Z]/", "",$currency);
		$currency_id = preg_replace("/[^0-9]/", "",$currency_id);
		
		$currency_info = ($currency_id > 0) ? DB::getRecord('currencies',$currency_id,0,1) : $CFG->currencies[strtoupper($currency)];
		$sql = "SELECT orders.btc_price AS fiat_price FROM orders WHERE currency = {$currency_info['id']} AND order_type = {$CFG->order_type_bid} ORDER BY btc_price DESC LIMIT 0,1";
		$result = db_query_array($sql);
		
		if (!$result) {
			$currency_info1 = $CFG->currencies['USD'];
			$sql = "SELECT ROUND((orders.btc_price/{$currency_info['usd']}),2) AS fiat_price FROM orders WHERE currency = {$currency_info1['id']} AND order_type = {$CFG->order_type_bid} ORDER BY btc_price DESC LIMIT 0,1";
			$result = db_query_array($sql);
			
			if (!$result) {
				$sql = "SELECT btc_price AS fiat_price, '1' AS no_orders FROM transactions WHERE currency = {$currency_info['id']} ORDER BY `date` DESC LIMIT 0,1";
				$result = db_query_array($sql);
				
				if (!$result) {
					$sql = "SELECT ROUND((btc_price/{$currency_info['usd']}),2) AS fiat_price, '1' AS no_orders FROM transactions WHERE currency = {$currency_info1['id']} ORDER BY `date` DESC LIMIT 0,1";
					$result = db_query_array($sql);
				}
			}
		}
		
		return $result[0]['fiat_price'];
	}
	
	function getCurrentAsk($currency,$currency_id=false) {
		global $CFG;
		
		$currency = preg_replace("/[^a-zA-Z]/", "",$currency);
		$currency_id = preg_replace("/[^0-9]/", "",$currency_id);
		
		$currency_info = ($currency_id > 0) ? DB::getRecord('currencies',$currency_id,0,1) : $CFG->currencies[strtoupper($currency)];
		$sql = "SELECT orders.btc_price AS fiat_price FROM orders WHERE currency = {$currency_info['id']} AND order_type = {$CFG->order_type_ask} ORDER BY btc_price ASC LIMIT 0,1";
		$result = db_query_array($sql);
		
		if (!$result) {
			$currency_info1 = $CFG->currencies['USD'];
			$sql = "SELECT ROUND((orders.btc_price/{$currency_info['usd']}),2) AS fiat_price FROM orders WHERE currency = {$currency_info1['id']} AND order_type = {$CFG->order_type_ask} ORDER BY btc_price ASC LIMIT 0,1";
			$result = db_query_array($sql);
			
			if (!$result) {
				$sql = "SELECT btc_price AS fiat_price FROM transactions WHERE currency = {$currency_info['id']} ORDER BY `date` DESC LIMIT 0,1";
				$result = db_query_array($sql);
			
				if (!$result) {
					$sql = "SELECT ROUND((btc_price/{$currency_info['usd']}),2) AS fiat_price FROM transactions WHERE currency = {$currency_info1['id']} ORDER BY `date` DESC LIMIT 0,1";
					$result = db_query_array($sql);
				}
			}
		}
		//echo $sql.'<br>';
		
		return $result[0]['fiat_price'];
	}
	
	function getCompatible($type,$price,$currency,$for_update=false,$market_price=false) {
		global $CFG;
		
		
		if (!$CFG->session_active)
			return false;
		
		$currency = preg_replace("/[^a-zA-Z]/", "",$currency);
		$price = preg_replace("/[^0-9\.]/", "",$price);
		$type = preg_replace("/[^0-9]/", "",$type);
		
		if (!$type || !$price || !$currency)
			return false;
		
		$currency_info = $CFG->currencies[strtoupper($currency)];
		$comparison = ($type == $CFG->order_type_ask) ? '<=' : '>=';
		$order_asc = ($type == $CFG->order_type_ask) ? 'ASC' : 'DESC';
		
		$sql = "SELECT orders.id, orders.btc_price AS fiat_price, orders.btc AS btc_outstanding, orders.site_user AS site_user, fee_schedule.fee AS fee, site_users.{$currency} AS fiat_balance, site_users.btc AS btc_balance, (IFNULL((SUM(IF(orders1.order_type = {$CFG->order_type_bid},orders1.fiat,0)) + (SUM(IF(orders1.order_type = {$CFG->order_type_bid},orders1.fiat,0)) * (fee * 0.01))),0) + IFNULL(requests.fiat_amount,0)) AS fiat_on_hold, (IFNULL((SUM(IF(orders1.order_type = {$CFG->order_type_ask},orders1.btc,0))),0) + IFNULL(requests.btc_amount,0)) AS btc_on_hold, orders.log_id AS log_id
				FROM orders
				LEFT JOIN site_users ON (orders.site_user = site_users.id )
				LEFT JOIN fee_schedule ON (site_users.fee_schedule = fee_schedule.id )
				LEFT JOIN orders orders1 ON (orders.site_user = orders1.site_user)
				LEFT JOIN (SELECT SUM(IF(currency = {$currency_info['id']},amount,0)) AS fiat_amount, SUM(IF(currency = {$CFG->btc_currency_id},amount,0)) AS btc_amount, site_user FROM requests WHERE requests.request_type = {$CFG->request_widthdrawal_id} AND (requests.request_status = {$CFG->request_pending_id} OR requests.request_status = {$CFG->request_awaiting_id}))requests ON (orders.site_user = requests.site_user)
				WHERE orders.order_type = $type
				".((!$market_price) ? " AND orders.btc_price $comparison $price " : false)."
				AND orders.currency = {$currency_info['id']}
				GROUP BY orders.id
				ORDER BY orders.btc_price $order_asc";
	
		if ($for_update)
			$sql .= ' FOR UPDATE';
		
		return db_query_array($sql);
	}
	
	function executeOrder($buy,$price,$amount,$currency1,$fee,$market_price,$edit_id=0,$this_user_id=0,$external_transaction=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$this_user_id = ($this_user_id > 0) ? $this_user_id : User::$info['id'];
		$this_user_id = preg_replace("/[^0-9]/", "",$this_user_id);
		$amount = preg_replace("/[^0-9\.]/", "",$amount);
		$price = preg_replace("/[^0-9\.]/", "",$price);
		$currency1 = preg_replace("/[^a-zA-Z]/", "",$currency1);
		//$fee = preg_replace("/[^0-9\.]/", "",$fee);
		$edit_id = preg_replace("/[^0-9]/", "",$edit_id);
		if ($edit_id > 0) {
			$orig_order = DB::getRecord('orders',$edit_id,0,1,false,false,false,1);
			if ($orig_order['site_user'] != $this_user_id || !$orig_order)
				return false;
			
			$edit_currency = DB::getRecord('currencies',$orig_order['currency'],0,1);
			$currency1 = strtolower($edit_currency['currency']);
		}
		
		if (!$external_transaction)
			db_start_transaction();
		
		$currency_info = $CFG->currencies[strtoupper($currency1)];
		$transactions = 0;
		$new_order = 0;
		$edit_order = 0;
		$status = Status::get(1);
		$user_info = DB::getRecord('site_users',$this_user_id,0,1,false,false,false,1);
		$this_btc_balance = $user_info['btc'];
		$this_fiat_balance = $user_info[strtolower($currency1)];
		$on_hold = User::getOnHold(1,$user_info['id']);
		$this_btc_on_hold = ($edit_id > 0 && !$buy) ? $on_hold['BTC']['total'] - $amount : $on_hold['BTC']['total'];
		$this_fiat_on_hold = ($edit_id > 0 && $buy) ? $on_hold[strtoupper($currency1)]['total'] - (($amount * $orig_order['btc_price']) + (($amount * $orig_order['btc_price']) * ($fee * 0.01))) : $on_hold[strtoupper($currency1)]['total'];
		
		$user_fee = DB::getRecord('fee_schedule',$user_info['fee_schedule'],0,1);
		$fee = $user_fee['fee'];
		
		if (!($edit_id > 0))
			$order_log_id = db_insert('order_log',array('date'=>date('Y-m-d H:i:s'),'order_type'=>(($buy) ? $CFG->order_type_bid : $CFG->order_type_ask),'site_user'=>$user_info['id'],'btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N')));
		else {
			$order_log_id = db_insert('order_log',array('date'=>date('Y-m-d H:i:s'),'order_type'=>(($buy) ? $CFG->order_type_bid : $CFG->order_type_ask),'site_user'=>$user_info['id'],'btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'p_id'=>$orig_order['log_id']));
		}
	
		if ($buy) {			
			$compatible = Orders::getCompatible($CFG->order_type_ask,$price,$currency1,1,$market_price);
			$trans_total = 0;
			$fiat_total = 0;
			//$btc_commision = 0;
			$fiat_commision = 0;
			
			if ($compatible) {
				foreach ($compatible as $comp_order) {
					if (!($amount > 0) || !(($this_fiat_balance - $this_fiat_on_hold) > 0))
						break;
					
					if ($comp_order['site_user'] == $user_info['id'])
						continue;

					++$transactions;
					
					$comp_order['btc_balance'] = (array_key_exists($comp_order['site_user'],$comp_btc_balance)) ? $comp_btc_balance[$comp_order['site_user']] : $comp_order['btc_balance'];
					$comp_order['fiat_balance'] = (array_key_exists($comp_order['site_user'],$comp_fiat_balance)) ? $comp_fiat_balance[$comp_order['site_user']] : $comp_order['fiat_balance'];
					$comp_btc_on_hold_prev[$comp_order['site_user']] = $comp_btc_on_hold[$comp_order['site_user']];
					$comp_btc_on_hold[$comp_order['site_user']] = (array_key_exists($comp_order['site_user'],$comp_btc_on_hold)) ? $comp_btc_on_hold[$comp_order['site_user']] - $comp_order['btc_outstanding'] : $comp_order['btc_on_hold'] - $comp_order['btc_outstanding'];
					
					$max_amount = ((($this_fiat_balance - $this_fiat_on_hold) / $comp_order['fiat_price']) > ($amount + (($fee * 0.01) * $amount))) ? $amount : (($this_fiat_balance - $this_fiat_on_hold) / $comp_order['fiat_price']) - (($fee * 0.01) * (($this_fiat_balance - $this_fiat_on_hold) / $comp_order['fiat_price']));
					$max_comp_amount = (($comp_order['btc_balance'] - $comp_btc_on_hold[$comp_order['site_user']]) > $comp_order['btc_outstanding']) ? $comp_order['btc_outstanding'] : $comp_order['btc_balance'] - $comp_btc_on_hold[$comp_order['site_user']];
					$this_funds_finished = ($max_amount < $amount);
					$comp_funds_finished = ($max_comp_amount < $comp_order['btc_outstanding']);
					
					if (!($max_amount > 0) || !($max_comp_amount > 0)) {
						$comp_btc_on_hold[$comp_order['site_user']] = $comp_btc_on_hold_prev[$comp_order['site_user']];
						continue;
					}
					
					if ($max_comp_amount >= $max_amount) {
						$trans_amount = $max_amount;
						$comp_order_outstanding = $comp_order['btc_outstanding'] - $max_amount;
						$amount = $amount - $max_amount;
					}
					else {
						$trans_amount = $max_comp_amount;
						$amount = $amount - $trans_amount;
						$comp_order_outstanding = $comp_order['btc_outstanding'] - $max_comp_amount;
					}
				
					$this_fee = ($fee * 0.01) * $trans_amount;
					$comp_order_fee = ($comp_order['fee'] * 0.01) * $trans_amount;
					$this_trans_amount_net = $trans_amount + $this_fee;
					$comp_order_trans_amount_net = $trans_amount - $comp_order_fee;
					$comp_btc_balance[$comp_order['site_user']] = $comp_order['btc_balance'] - $trans_amount;
					$comp_fiat_balance[$comp_order['site_user']] = $comp_order['fiat_balance'] + ($comp_order['fiat_price'] * $comp_order_trans_amount_net);
					//$btc_commision += $this_fee;
					$fiat_commision += ($comp_order_fee + $this_fee) * $comp_order['fiat_price'];
					$this_prev_btc = $this_btc_balance;
					$this_prev_fiat = $this_fiat_balance;
					$this_btc_balance += $trans_amount;
					$this_fiat_balance -= $this_trans_amount_net * $comp_order['fiat_price'];
					$trans_total += $trans_amount;
					
					$transaction_id = db_insert('transactions',array('date'=>date('Y-m-d H:i:s'),'site_user'=>$user_info['id'],'transaction_type'=>$CFG->transactions_buy_id,'site_user1'=>$comp_order['site_user'],'transaction_type1'=>$CFG->transactions_sell_id,'btc'=>$trans_amount,'btc_price'=>$comp_order['fiat_price'],'fiat'=>($comp_order['fiat_price'] * $trans_amount),'currency'=>$currency_info['id'],'fee'=>$this_fee,'fee1'=>$comp_order_fee,'btc_net'=>$this_trans_amount_net,'btc_net1'=>$comp_order_trans_amount_net,'btc_before'=>$this_prev_btc,'btc_after'=>$this_btc_balance,'fiat_before'=>$this_prev_fiat,'fiat_after'=>$this_fiat_balance,'btc_before1'=>$comp_order['btc_balance'],'btc_after1'=>$comp_btc_balance[$comp_order['site_user']],'fiat_before1'=>$comp_order['fiat_balance'],'fiat_after1'=>$comp_fiat_balance[$comp_order['site_user']],'log_id'=>$order_log_id,'log_id1'=>$comp_order['log_id'],'fee_level'=>$fee,'fee_level1'=>$comp_order['fee']));
					
					if ($comp_order_outstanding > 0) {
						if (!$comp_funds_finished)
							db_update('orders',$comp_order['id'],array('btc'=>$comp_order_outstanding,'fiat'=>($comp_order['fiat_price'] * $comp_order_outstanding)));
						else
							self::cancelOrder($comp_order['id'],$comp_order_outstanding,$comp_order['site_user']);
					}
					else {
						db_delete('orders',$comp_order['id']);
					}
	
					db_update('site_users',$comp_order['site_user'],array('btc'=>$comp_btc_balance[$comp_order['site_user']],$currency1=>$comp_fiat_balance[$comp_order['site_user']]));
				}
			}
			else {
				$no_compatible = true;
			}
	
			if ($trans_total > 0) {
				db_update('site_users',$user_info['id'],array('btc'=>$this_btc_balance,$currency1=>$this_fiat_balance));
				db_update('status',1,array(strtolower($currency_info['currency']).'_escrow'=>($status[strtolower($currency_info['currency']).'_escrow']+$fiat_commision)));
				//db_update('status',1,array('btc_escrow'=>($status['btc_escrow']+$btc_commision),strtolower($currency_info['currency']).'_escrow'=>($status[strtolower($currency_info['currency']).'_escrow']+$fiat_commision)));
			}

			if ($amount > 0) {
				if ($edit_id > 0) {
					if (!$this_funds_finished) {
						if (!($no_compatible && $CFG->in_cron)) {
							db_update('orders',$edit_id,array('btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id));
							$edit_order = 1;
						}
					}
					else {
						self::cancelOrder($edit_id,$amount,$this_user_id);
					}
				}
				else {
					if (!$this_funds_finished) {
						db_insert('orders',array('date'=>date('Y-m-d H:i:s'),'order_type'=>$CFG->order_type_bid,'site_user'=>$user_info['id'],'btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id));
						db_insert('history',array('date'=>date('Y-m-d H:i:s'),'ip'=>$CFG->client_ip,'history_action'=>$CFG->history_buy_id,'site_user'=>$user_info['id'],'order_id'=>$order_log_id));
						$new_order = 1;
					}
					else {
						self::cancelOrder(false,$amount,$this_user_id);
					}
				}
			}
			elseif ($edit_id > 0) {
				db_delete('orders',$edit_id);
			}
		}
		else {
			$compatible = Orders::getCompatible($CFG->order_type_bid,$price,$currency1,1,$market_price);
			$trans_total = 0;
			$fiat_total = 0;
			//$btc_commision = 0;
			$fiat_commision = 0;
			
			if ($compatible) {
				foreach ($compatible as $comp_order) {
					if (!($amount > 0) || !(($this_btc_balance - $this_btc_on_hold) > 0))
						break;
					
					if ($comp_order['site_user'] == $user_info['id'])
						continue;
	
					++$transactions;

					$comp_order['btc_balance'] = (array_key_exists($comp_order['site_user'],$comp_btc_balance)) ? $comp_btc_balance[$comp_order['site_user']] : $comp_order['btc_balance'];
					$comp_order['fiat_balance'] = (array_key_exists($comp_order['site_user'],$comp_fiat_balance)) ? $comp_fiat_balance[$comp_order['site_user']] : $comp_order['fiat_balance'];
					$comp_fiat_on_hold_prev[$comp_order['site_user']] = $comp_fiat_on_hold[$comp_order['site_user']];
					$comp_fiat_on_hold[$comp_order['site_user']] = (array_key_exists($comp_order['site_user'],$comp_fiat_on_hold)) ? $comp_fiat_on_hold[$comp_order['site_user']] - (($comp_order['btc_outstanding'] * $comp_order['fiat_price']) + (($comp_order['fee'] * 0.01) * ($comp_order['btc_outstanding'] * $comp_order['fiat_price']))) : $comp_order['fiat_on_hold'] - (($comp_order['btc_outstanding'] * $comp_order['fiat_price']) + (($comp_order['fee'] * 0.01) * ($comp_order['btc_outstanding'] * $comp_order['fiat_price'])));
					
					$max_amount = (($this_btc_balance - $this_btc_on_hold) > $amount) ? $amount : $this_btc_balance - $this_btc_on_hold;
					$max_comp_amount = ((($comp_order['fiat_balance'] - $comp_fiat_on_hold[$comp_order['site_user']]) / $comp_order['fiat_price']) > ($comp_order['btc_outstanding'] + (($comp_order['fee'] * 0.01) * $comp_order['btc_outstanding']))) ? $comp_order['btc_outstanding'] : (($comp_order['fiat_balance'] - $comp_fiat_on_hold[$comp_order['site_user']]) / $comp_order['fiat_price']) - (($comp_order['fee'] * 0.01) * (($comp_order['fiat_balance'] - $comp_fiat_on_hold[$comp_order['site_user']]) / $comp_order['fiat_price']));
					$this_funds_finished = ($max_amount < $amount);
					$comp_funds_finished = ($max_comp_amount < $comp_order['btc_outstanding']);

					if (!($max_amount > 0) || !($max_comp_amount > 0)) {
						$comp_fiat_on_hold[$comp_order['site_user']] = $comp_fiat_on_hold_prev[$comp_order['site_user']];
						continue;
					}
					
					if ($max_comp_amount >= $max_amount) {
						$trans_amount = $max_amount;
						$comp_order_outstanding = $comp_order['btc_outstanding'] - $amount;
						$amount = $amount - $max_amount;
					}
					else {
						$trans_amount = $max_comp_amount;
						$amount = $amount - $trans_amount;
						$comp_order_outstanding = $comp_order['btc_outstanding'] - $max_comp_amount;
					}
					
					$this_fee = ($fee * 0.01) * $trans_amount;
					$comp_order_fee = ($comp_order['fee'] * 0.01) * $trans_amount;
					$this_trans_amount_net = $trans_amount - $this_fee;
					$comp_order_trans_amount_net = $trans_amount + $comp_order_fee;
					$comp_btc_balance[$comp_order['site_user']] = $comp_order['btc_balance'] + $trans_amount;
					$comp_fiat_balance[$comp_order['site_user']] = $comp_order['fiat_balance'] - ($comp_order['fiat_price'] * $comp_order_trans_amount_net);
					//$btc_commision += $comp_order_fee;
					$fiat_commision += ($this_fee + $comp_order_fee) * $comp_order['fiat_price'];
					$this_prev_btc = $this_btc_balance;
					$this_prev_fiat = $this_fiat_balance;
					$this_btc_balance -= $trans_amount;
					$this_fiat_balance += $this_trans_amount_net * $comp_order['fiat_price'];
					$trans_total += $trans_amount;
					
					$transaction_id = db_insert('transactions',array('date'=>date('Y-m-d H:i:s'),'site_user'=>$user_info['id'],'transaction_type'=>$CFG->transactions_sell_id,'site_user1'=>$comp_order['site_user'],'transaction_type1'=>$CFG->transactions_buy_id,'btc'=>$trans_amount,'btc_price'=>$comp_order['fiat_price'],'fiat'=>($comp_order['fiat_price'] * $trans_amount),'currency'=>$currency_info['id'],'fee'=>$this_fee,'fee1'=>$comp_order_fee,'btc_net'=>$this_trans_amount_net,'btc_net1'=>$comp_order_trans_amount_net,'btc_before'=>$this_prev_btc,'btc_after'=>$this_btc_balance,'fiat_before'=>$this_prev_fiat,'fiat_after'=>$this_fiat_balance,'btc_before1'=>$comp_order['btc_balance'],'btc_after1'=>$comp_btc_balance[$comp_order['site_user']],'fiat_before1'=>$comp_order['fiat_balance'],'fiat_after1'=>$comp_fiat_balance[$comp_order['site_user']],'log_id'=>$order_log_id,'log_id1'=>$comp_order['log_id'],'fee_level'=>$fee,'fee_level1'=>$comp_order['fee']));
					
					if ($comp_order_outstanding > 0) {
						if (!$comp_funds_finished)
							db_update('orders',$comp_order['id'],array('btc'=>$comp_order_outstanding,'fiat'=>($comp_order['fiat_price'] * $comp_order_outstanding)));
						else
							self::cancelOrder($comp_order['id'],$comp_order_outstanding,$comp_order['site_user']);
					}
					else {
						db_delete('orders',$comp_order['id']);
					}
	
					db_update('site_users',$comp_order['site_user'],array('btc'=>$comp_btc_balance[$comp_order['site_user']],$currency1=>$comp_fiat_balance[$comp_order['site_user']]));
				}
			}
			else {
				$no_compatible = true;
			}
	
			if ($trans_total > 0) {
				db_update('site_users',$user_info['id'],array('btc'=>$this_btc_balance,$currency1=>$this_fiat_balance));
				db_update('status',1,array(strtolower($currency_info['currency']).'_escrow'=>($status[strtolower($currency_info['currency']).'_escrow']+$fiat_commision)));
				//db_update('status',1,array('btc_escrow'=>($status['btc_escrow']+$btc_commision),strtolower($currency_info['currency']).'_escrow'=>($status[strtolower($currency_info['currency']).'_escrow']+$fiat_commision)));
			}
			
			if ($amount > 0) {
				if ($edit_id > 0) {
					if (!$this_funds_finished) {
						if (!($no_compatible && $CFG->in_cron)) {
							db_update('orders',$edit_id,array('btc'=>$amount,'fiat'=>($amount*$price),'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id));
							$edit_order = 1;
						}
					}
					else {
						self::cancelOrder($edit_id,$amount,$this_user_id);
					}
				}
				else {
					if (!$this_funds_finished) {
						$insert_id = db_insert('orders',array('date'=>date('Y-m-d H:i:s'),'order_type'=>$CFG->order_type_ask,'site_user'=>$user_info['id'],'btc'=>$amount,'fiat'=>($amount*$price),'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id));
						db_insert('history',array('date'=>date('Y-m-d H:i:s'),'ip'=>$CFG->client_ip,'history_action'=>$CFG->history_sell_id,'site_user'=>$user_info['id'],'order_id'=>$order_log_id));
						$new_order = 1;
					}
					else {
						self::cancelOrder(false,$amount,$this_user_id);
					}
				}
			}
			elseif ($edit_id > 0) {
				db_delete('orders',$edit_id);
			}
		}
		
		if (!$external_transaction)
			db_commit();
		
		return array('transactions'=>$transactions,'new_order'=>$new_order,'edit_order'=>$edit_order);
	}
	
	private function cancelOrder($order_id=false,$outstanding_btc=false,$site_user=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$user_info = ($site_user > 0) ? DB::getRecord('site_users',$site_user,0,1) : User::$info;
		$user_info['amount'] = $outstanding_btc;
		$CFG->language = $user_info['last_lang'];
		db_delete('orders',$order_id);
		
		$email = SiteEmail::getRecord('order-cancelled');
		Email::send($CFG->form_email,$user_info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$user_info);
	}
	
	function delete($id) {
		global $CFG;
		
		$id = preg_replace("/[^0-9]/", "",$id);
		
		if (!($id > 0))
			return false;
		
		if (!$CFG->session_active)
			return false;
		
		$del_order = DB::getRecord('orders',$id,0,1);
		if ($del_order['site_user'] != User::$info['id'])
			return false;
		
		db_delete('orders',$id);
	}
	
	function getBidList($currency=false,$notrades=false,$limit_7=false,$user=false) {
		global $CFG;
		
		$currency1 = preg_replace("/[^a-zA-Z]/", "",$currency);
		$user = ($user) ? ' AND site_user = '.User::$info['id'].' ' : '';
		
		if ($currency1 != 'All')
			$currency_info = $CFG->currencies[strtoupper($currency1)];
		
		if ($limit_7)
			$limit = " LIMIT 0,10";
		elseif (!$notrades)
			$limit = " LIMIT 0,5 ";
		
		$sql = "
		SELECT orders.id AS id, orders.btc AS btc, orders.btc_price AS btc_price, orders.order_type AS type, currencies.fa_symbol AS fa_symbol
		FROM orders
		LEFT JOIN currencies ON (currencies.id = orders.currency)
		WHERE 1
		".((is_array($currency_info)) ? " AND orders.currency = {$currency_info['id']} " : false). "
		AND orders.order_type = $CFG->order_type_bid
		$user
		ORDER BY orders.btc_price DESC $limit ";
		//return $sql;
		return db_query_array($sql);
	}
	
	function getAskList($currency=false,$notrades=false,$limit_7=false,$user=false) {
		global $CFG;
	
		$currency1 = preg_replace("/[^a-zA-Z]/", "",$currency);
		$user = ($user) ? ' AND site_user = '.User::$info['id'].' ' : '';
		$currency_info = $CFG->currencies[strtoupper($currency1)];
		
		if ($currency1 != 'All')
			$currency_info = $CFG->currencies[strtoupper($currency1)];
		
		if ($limit_7)
			$limit = " LIMIT 0,10";
		elseif (!$notrades)
			$limit = " LIMIT 0,5 ";
	
		$sql = "
		SELECT orders.id AS id, orders.btc AS btc, orders.btc_price AS btc_price, orders.order_type AS type, currencies.fa_symbol AS fa_symbol
		FROM orders
		LEFT JOIN currencies ON (currencies.id = orders.currency)
		WHERE 1
		".((is_array($currency_info)) ? " AND orders.currency = {$currency_info['id']} " : false). "
		AND orders.order_type = $CFG->order_type_ask 
		$user
		ORDER BY orders.btc_price ASC $limit ";
		//return $sql;
		return db_query_array($sql);
	}
}