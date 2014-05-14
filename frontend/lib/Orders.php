<?php
class Orders {
	function get($count=false,$page=false,$per_page=false,$currency=false,$user=false,$start_date=false,$type=false,$order_by1=false,$order_desc=false,$dont_paginate=false) {
		global $CFG;
		
		$page = mysql_real_escape_string($page);
		$page = ($page > 0) ? $page - 1 : 0;
		$r1 = $page * $per_page;
		$order_arr = array('date'=>'orders.date','btc'=>'orders.btc','btcprice'=>'orders.btc_price','fiat'=>'usd_amount');
		$order_by = ($order_by1) ? $order_arr[$order_by1] : 'orders.btc_price';
		$order_desc = ($order_desc && ($order_by1 != 'date' && $order_by1 != 'fiat')) ? 'ASC' : 'DESC';
		$currency_info = $CFG->currencies[strtoupper($currency)];
		
		if (!$count)
			$sql = "SELECT orders.*, order_types.name_{$CFG->language} AS type, currencies.currency AS currency, (currencies.usd * orders.fiat) AS usd_amount, orders.btc_price AS fiat_price, (UNIX_TIMESTAMP(orders.date) * 1000) AS time_since, currencies.fa_symbol AS fa_symbol FROM orders ";
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
	
		//echo $sql;
		$result = db_query_array($sql);
		if (!$count)
			return $result;
		else
			return $result[0]['total'];
	}
	
	function getCurrentBid($currency,$currency_id=false) {
		global $CFG;
		
		$currency_info = ($currency_id > 0) ? array('id'=>$currency_id) : $CFG->currencies[strtoupper($currency)];
		$sql = "SELECT orders.btc_price AS fiat_price FROM orders WHERE currency = {$currency_info['id']} AND order_type = {$CFG->order_type_bid} ORDER BY btc_price DESC LIMIT 0,1";
		$result = db_query_array($sql);
		
		if (!$result) {
			$currency_info1 = $CFG->currencies['USD'];
			$sql = "SELECT ROUND((orders.btc_price/{$currency_info['usd']}),2) AS fiat_price FROM orders WHERE currency = {$currency_info1['id']} AND order_type = {$CFG->order_type_bid} ORDER BY btc_price DESC LIMIT 0,1";
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
		
		return $result[0]['fiat_price'];
	}
	
	function getCurrentAsk($currency,$currency_id=false) {
		global $CFG;
		
		$currency_info = ($currency_id > 0) ? array('id'=>$currency_id) : $CFG->currencies[strtoupper($currency)];
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
	
	function getCompatible($type,$price,$currency,$for_update=false) {
		global $CFG;
		
		if (!$type || !$price || !$currency)
			return false;
		
		$currency_info = $CFG->currencies[strtoupper($currency)];
		$comparison = ($type == $CFG->order_type_ask) ? '<=' : '>=';
		$order_asc = ($type == $CFG->order_type_ask) ? 'ASC' : 'DESC';
		
		$sql = "SELECT orders.id, orders.btc_price AS fiat_price, orders.btc AS btc_outstanding, orders.site_user AS site_user, fee_schedule.fee AS fee, site_users.{$currency} AS fiat_balance, site_users.btc AS btc_balance, orders.log_id AS log_id FROM orders
				LEFT JOIN site_users ON (orders.site_user = site_users.id)
				LEFT JOIN fee_schedule ON (site_users.fee_schedule = fee_schedule.id)
				WHERE orders.order_type = $type 
				AND orders.btc_price $comparison $price
				AND orders.currency = {$currency_info['id']}
				ORDER BY orders.btc_price $order_asc ";
		
		if ($for_update)
			$sql .= ' FOR UPDATE';
		
		return db_query_array($sql);
	}
	
	function executeOrder($buy,$price,$amount,$currency1,$fee,$market_price,$edit_id=0,$this_user_id=0,$external_transaction=false) {
		global $CFG;
		
		if (!$external_transaction)
			db_start_transaction();
		
		$this_user_id = ($this_user_id > 0) ? $this_user_id : User::$info['id'];
		$currency_info = $CFG->currencies[strtoupper($currency1)];
		$transactions = 0;
		$new_order = 0;
		$edit_order = 0;
		$status = Status::get(1);
		$user_info = DB::getRecord('site_users',$this_user_id,0,1,false,false,false,1);
		$this_btc_balance = $user_info['btc'];
		$this_fiat_balance = $user_info[$currency1];
		
		if (!($edit_id > 0))
			$order_log_id = db_insert('order_log',array('date'=>date('Y-m-d H:i:s'),'order_type'=>(($buy) ? $CFG->order_type_bid : $CFG->order_type_ask),'site_user'=>$user_info['id'],'btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N')));
		else {
			$orig_order = DB::getRecord('orders',$edit_id,0,1,false,false,false,1);
			$order_log_id = db_insert('order_log',array('date'=>date('Y-m-d H:i:s'),'order_type'=>(($buy) ? $CFG->order_type_bid : $CFG->order_type_ask),'site_user'=>$user_info['id'],'btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'p_id'=>$orig_order['log_id']));
		}
		
		if ($buy) {
			$compatible = Orders::getCompatible($CFG->order_type_ask,$price,$currency1,1);
			$trans_total = 0;
			$fiat_total = 0;
			$btc_commision = 0;
			$fiat_commision = 0;
			
			if ($compatible) {
				foreach ($compatible as $comp_order) {
					if (!($amount > 0))
						break;
					
					if ($comp_order['site_user'] == $user_info['id'])
						continue;
	
					++$transactions;
	
					if ($comp_order['btc_outstanding'] >= $amount) {
						$trans_amount = $amount;
						$comp_order_outstanding = $comp_order['btc_outstanding'] - $amount;
						$amount = 0;
					}
					else {
						$trans_amount = $comp_order['btc_outstanding'];
						$amount = $amount - $trans_amount;
						$comp_order_outstanding = 0;
					}
	
					$this_fee = ($fee * 0.01) * $trans_amount;
					$comp_order_fee = ($comp_order['fee'] * 0.01) * $trans_amount;
					$this_trans_amount_net = $trans_amount - $this_fee;
					$comp_order_trans_amount_net = $trans_amount - $comp_order_fee;
					$comp_btc_balance = $comp_order['btc_balance'] - $trans_amount;
					$comp_fiat_balance = $comp_order['fiat_balance'] + ($comp_order['fiat_price'] * $comp_order_trans_amount_net);
					$btc_commision += $this_fee;
					$fiat_commision += $comp_order_fee * $comp_order['fiat_price'];
					$this_prev_btc = $this_btc_balance;
					$this_prev_fiat = $this_fiat_balance;
					$this_btc_balance += $this_trans_amount_net;
					$this_fiat_balance -= $trans_amount * $comp_order['fiat_price'];
					$trans_total += $trans_amount;
					
					$transaction_id = db_insert('transactions',array('date'=>date('Y-m-d H:i:s'),'site_user'=>$user_info['id'],'transaction_type'=>$CFG->transactions_buy_id,'site_user1'=>$comp_order['site_user'],'transaction_type1'=>$CFG->transactions_sell_id,'btc'=>$trans_amount,'btc_price'=>$comp_order['fiat_price'],'fiat'=>($comp_order['fiat_price'] * $trans_amount),'currency'=>$currency_info['id'],'fee'=>$this_fee,'fee1'=>$comp_order_fee,'btc_net'=>$this_trans_amount_net,'btc_net1'=>$comp_order_trans_amount_net,'btc_before'=>$this_prev_btc,'btc_after'=>$this_btc_balance,'fiat_before'=>$this_prev_fiat,'fiat_after'=>$this_fiat_balance,'btc_before1'=>$comp_order['btc_balance'],'btc_after1'=>$comp_btc_balance,'fiat_before1'=>$comp_order['fiat_balance'],'fiat_after1'=>$comp_fiat_balance,'log_id'=>$order_log_id,'log_id1'=>$comp_order['log_id'],'fee_level'=>$fee,'fee_level1'=>$comp_order['fee']));
					
					if ($comp_order_outstanding > 0) {
						db_update('orders',$comp_order['id'],array('btc'=>$comp_order_outstanding,'fiat'=>($comp_order['fiat_price'] * $comp_order_outstanding)));
					}
					else {
						db_delete('orders',$comp_order['id']);
					}
	
					db_update('site_users',$comp_order['site_user'],array('btc'=>$comp_btc_balance,$currency1=>$comp_fiat_balance));
				}
			}
	
			if ($trans_total > 0) {
				db_update('site_users',$user_info['id'],array('btc'=>$this_btc_balance,$currency1=>$this_fiat_balance));
				db_update('status',1,array('btc_escrow'=>($status['btc_escrow']+$btc_commision),strtolower($currency_info['currency']).'_escrow'=>($status[strtolower($currency_info['currency']).'_escrow']+$fiat_commision)));
			}
	
			if ($amount > 0) {
				if ($edit_id > 0) {
					db_update('orders',$edit_id,array('btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id));
					$edit_order = 1;
				}
				else {
					db_insert('orders',array('date'=>date('Y-m-d H:i:s'),'order_type'=>$CFG->order_type_bid,'site_user'=>$user_info['id'],'btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id));
					$new_order = 1;
				}
			}
			elseif ($edit_id > 0) {
				db_delete('orders',$edit_id);
			}
		}
		else {
			$compatible = Orders::getCompatible($CFG->order_type_bid,$price,$currency1,1);
			$trans_total = 0;
			$fiat_total = 0;
			$btc_commision = 0;
			$fiat_commision = 0;
			
			if ($compatible) {
				foreach ($compatible as $comp_order) {
					if (!($amount > 0))
						break;
					
					if ($comp_order['site_user'] == $user_info['id'])
						continue;
	
					++$transactions;
	
					if ($comp_order['btc_outstanding'] >= $amount) {
						$trans_amount = $amount;
						$comp_order_outstanding = $comp_order['btc_outstanding'] - $amount;
						$amount = 0;
					}
					else {
						$trans_amount = $comp_order['btc_outstanding'];
						$amount = $amount - $trans_amount;
						$comp_order_outstanding = 0;
					}
					$this_fee = ($fee * 0.01) * $trans_amount;
					$comp_order_fee = ($comp_order['fee'] * 0.01) * $trans_amount;
					$this_trans_amount_net = $trans_amount - $this_fee;
					$comp_order_trans_amount_net = $trans_amount - $comp_order_fee;
					$comp_btc_balance = $comp_order['btc_balance'] + $comp_order_trans_amount_net;
					$comp_fiat_balance = $comp_order['fiat_balance'] - ($comp_order['fiat_price'] * $trans_amount);
					$btc_commision += $comp_order_fee;
					$fiat_commision += $this_fee * $comp_order['fiat_price'];
					$this_prev_btc = $this_btc_balance;
					$this_prev_fiat = $this_fiat_balance;
					$this_btc_balance -= $trans_amount;
					$this_fiat_balance += $this_trans_amount_net * $comp_order['fiat_price'];
					$trans_total += $trans_amount;
					
					$transaction_id = db_insert('transactions',array('date'=>date('Y-m-d H:i:s'),'site_user'=>$user_info['id'],'transaction_type'=>$CFG->transactions_sell_id,'site_user1'=>$comp_order['site_user'],'transaction_type1'=>$CFG->transactions_buy_id,'btc'=>$trans_amount,'btc_price'=>$comp_order['fiat_price'],'fiat'=>($comp_order['fiat_price'] * $trans_amount),'currency'=>$currency_info['id'],'fee'=>$this_fee,'fee1'=>$comp_order_fee,'btc_net'=>$this_trans_amount_net,'btc_net1'=>$comp_order_trans_amount_net,'btc_before'=>$this_prev_btc,'btc_after'=>$this_btc_balance,'fiat_before'=>$this_prev_fiat,'fiat_after'=>$this_fiat_balance,'btc_before1'=>$comp_order['btc_balance'],'btc_after1'=>$comp_btc_balance,'fiat_before1'=>$comp_order['fiat_balance'],'fiat_after1'=>$comp_fiat_balance,'log_id'=>$order_log_id,'log_id1'=>$comp_order['log_id'],'fee_level'=>$fee,'fee_level1'=>$comp_order['fee']));
					
					if ($comp_order_outstanding > 0) {
						db_update('orders',$comp_order['id'],array('btc'=>$comp_order_outstanding,'fiat'=>($comp_order['fiat_price'] * $comp_order_outstanding)));
					}
					else {
						db_delete('orders',$comp_order['id']);
					}
	
					db_update('site_users',$comp_order['site_user'],array('btc'=>$comp_btc_balance,$currency1=>$comp_fiat_balance));
				}
			}
	
			if ($trans_total > 0) {
				db_update('site_users',$user_info['id'],array('btc'=>$this_btc_balance,$currency1=>$this_fiat_balance));
				db_update('status',1,array('btc_escrow'=>($status['btc_escrow']+$btc_commision),strtolower($currency_info['currency']).'_escrow'=>($status[strtolower($currency_info['currency']).'_escrow']+$fiat_commision)));
			}
			
			if ($amount > 0) {
				if ($edit_id > 0) {
					db_update('orders',$edit_id,array('btc'=>$amount,'fiat'=>($amount*$price),'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id));
					$edit_order = 1;
				}
				else {
					$insert_id = db_insert('orders',array('date'=>date('Y-m-d H:i:s'),'order_type'=>$CFG->order_type_ask,'site_user'=>$user_info['id'],'btc'=>$amount,'fiat'=>($amount*$price),'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id));
					$new_order = 1;
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
}