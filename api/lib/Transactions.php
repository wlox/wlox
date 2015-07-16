<?php
class Transactions {
	public static function get($count=false,$page=false,$per_page=false,$currency=false,$user=false,$start_date=false,$type=false,$order_by=false,$order_desc=false,$public_api_all=false,$dont_paginate=false) {
		global $CFG;
		
		if ($user && !(User::$info['id'] > 0))
			return false;
		
		$page = preg_replace("/[^0-9]/", "",$page);
		$per_page = preg_replace("/[^0-9]/", "",$per_page);
		$per_page1 = ($per_page == 1 || $per_page == 5) ? 5 : $per_page;
		$page = preg_replace("/[^0-9]/", "",$page);
		$currency = preg_replace("/[^a-zA-Z]/", "",$currency);
		$start_date = preg_replace ("/[^0-9: \-]/","",$start_date);
		
		$page = ($page > 0) ? $page - 1 : 0;
		$r1 = $page * $per_page;
		$order_arr = array('date'=>'transactions.id','btc'=>'transactions.btc','btcprice'=>'usd_price','fiat'=>'usd_amount','fee'=>'usd_fee');
		$order_by = ($order_by) ? $order_arr[$order_by] : 'transactions.id';
		$order_desc = ($order_desc) ? 'ASC' : 'DESC';
		$user = ($user) ? User::$info['id'] : false;
		$usd_info = $CFG->currencies['USD'];
		$usd_field = 'usd_ask';
		$currency_info = (!empty($CFG->currencies[strtoupper($currency)])) ? $CFG->currencies[strtoupper($currency)] : false;

		if ($type == 'buy')
			$type = $CFG->transactions_buy_id;
		elseif ($type == 'sell')
			$type = $CFG->transactions_sell_id;
		else
			$type = preg_replace("/[^0-9]/", "",$type);

		if ($CFG->memcached) {
			$cached = null;
			if ($per_page == 5 && !$count && !$public_api_all)
				$cached = $CFG->m->get('trans_l5_'.$currency_info['currency']);
			elseif ($per_page == 1 && !$count && !$public_api_all)
				$cached = $CFG->m->get('trans_l1_'.$currency_info['currency']);
			elseif ($public_api_all)
				$cached = $CFG->m->get('trans_api'.(($per_page) ? '_l'.$per_page : '').(($user) ? '_u'.$user : '').(($currency) ? '_c'.$currency_info['currency'] : '').(($type) ? '_t'.$type : ''));
			
			if (is_array($cached)) {
				if (count($cached) == 0)
					return false;
				
				return $cached;
			}
		}
		
		$price_str = '(CASE WHEN transactions.currency = '.$currency_info['id'].' THEN transactions.btc_price WHEN transactions.currency1 = '.$currency_info['id'].' THEN transactions.orig_btc_price ELSE (CASE transactions.currency1 ';
		$amount_str = '(CASE WHEN transactions.currency = '.$currency_info['id'].' THEN (transactions.btc_price * transactions.btc) WHEN transactions.currency1 = '.$currency_info['id'].' THEN (transactions.orig_btc_price * transactions.btc) ELSE (CASE transactions.currency1 ';
		$usd_str = '(CASE transactions.currency ';
		$currency_abbr = '(CASE IF(transactions.site_user = '.$user.',transactions.currency,transactions.currency1) ';
		$currency_abbr1 = '(CASE transactions.currency ';
		$currency_abbr2 = '(CASE transactions.currency1 ';
		
		foreach ($CFG->currencies as $curr_id => $currency1) {
			if (is_numeric($curr_id) || $currency1['currency'] == 'BTC')
				continue;
	
			if (!empty($currency_info) && $currency1['id'] == $currency_info['id'])
				continue;
	
			$conversion = (empty($currency_info) || $currency_info['currency'] == 'USD') ? $currency1[$usd_field] : $currency1[$usd_field] / $currency_info[$usd_field];
			$price_str .= ' WHEN '.$currency1['id'].' THEN (transactions.orig_btc_price * '.$conversion.')';
			$amount_str .= ' WHEN '.$currency1['id'].' THEN ((transactions.orig_btc_price * transactions.btc) * '.$conversion.')';
			$usd_str .= ' WHEN '.$currency1['id'].' THEN '.$currency1[$usd_field].' ';
			$currency_abbr .= ' WHEN '.$currency1['id'].' THEN "'.$currency1['currency'].'" ';
			$currency_abbr1 .= ' WHEN '.$currency1['id'].' THEN "'.$currency1['currency'].'" ';
			$currency_abbr2 .= ' WHEN '.$currency1['id'].' THEN "'.$currency1['currency'].'" ';
		}
		$price_str .= ' END) END)';
		$amount_str .= ' END) END)';
		$usd_str .= ' END)';
		$currency_abbr .= ' END)';
		$currency_abbr1 .= ' END)';
		$currency_abbr2 .= ' END)';
		
		if (!$count && !$public_api_all)
			$sql = "SELECT transactions.id,transactions.date,transactions.site_user,transactions.site_user1,transactions.btc,transactions.currency,transactions.currency1,transactions.btc_price,transactions.orig_btc_price,transactions.fiat, (UNIX_TIMESTAMP(transactions.date) - ({$CFG->timezone_offset})) AS time_since ".(($user > 0) ? ",IF(transactions.site_user = $user,transaction_types.name_{$CFG->language},transaction_types1.name_{$CFG->language}) AS type, IF(transactions.site_user = $user,transactions.fee,transactions.fee1) AS fee, IF(transactions.site_user = $user,transactions.btc_net,transactions.btc_net1) AS btc_net, IF(transactions.site_user1 = $user,transactions.orig_btc_price,transactions.btc_price) AS fiat_price, IF(transactions.site_user = $user,transactions.currency,transactions.currency1) AS currency" : ", ROUND($price_str,2) AS btc_price, LOWER(transaction_types1.name_en) AS maker_type").", UNIX_TIMESTAMP(transactions.date) AS datestamp ".(($order_by == 'usd_price') ? ', ROUND(('.$usd_str.' * transactions.btc_price),2) AS usd_price' : '').(($order_by == 'usd_amount') ? ', ROUND(('.$usd_str.' * transactions.fiat),2) AS usd_amount' : '');
		elseif ($public_api_all && $user)
			$sql = "SELECT transactions.id AS id, transactions.date AS date, UNIX_TIMESTAMP(transactions.date) AS `timestamp`, transactions.btc AS btc, LOWER(IF(transactions.site_user = $user,transaction_types.name_{$CFG->language},transaction_types1.name_{$CFG->language})) AS side, IF(transactions.site_user1 = $user,transactions.orig_btc_price,transactions.btc_price) AS price, ROUND((IF(transactions.site_user1 = $user,transactions.orig_btc_price,transactions.btc_price) * IF(transactions.site_user = $user,transactions.btc_net,transactions.btc_net1)),2) AS amount, ROUND((IF(transactions.site_user1 = $user,transactions.orig_btc_price,transactions.btc_price) * IF(transactions.site_user = $user,transactions.fee,transactions.fee1)),2) AS fee, $currency_abbr AS currency ";
		elseif ($public_api_all && !$user && $currency)
			$sql = "SELECT transactions.id AS id, transactions.date AS date, UNIX_TIMESTAMP(transactions.date) AS `timestamp`, transactions.btc AS btc, LOWER(transaction_types1.name_{$CFG->language}) AS maker_type, ROUND($price_str,2) AS price, ROUND($amount_str,2) AS amount, IF(transactions.currency != {$currency_info['id']} AND transactions.currency1 != {$currency_info['id']},$currency_abbr2,'{$currency_info['currency']}') AS currency ";
		elseif ($public_api_all && !$user)
			$sql = "SELECT transactions.id AS id, transactions.date AS date, UNIX_TIMESTAMP(transactions.date) AS `timestamp`, transactions.btc AS btc, transactions.btc_price AS price, transactions.orig_btc_price AS price1, ROUND((transactions.btc_price * transactions.btc),2) AS amount, ROUND((transactions.orig_btc_price * transactions.btc),2) AS amount1, $currency_abbr1 AS currency, $currency_abbr2 AS currency1 ";
		else
			$sql = "SELECT COUNT(transactions.id) AS total ";
			
		$sql .= " 
		FROM transactions
		LEFT JOIN transaction_types ON (transaction_types.id = transactions.transaction_type)
		LEFT JOIN transaction_types transaction_types1 ON (transaction_types1.id = transactions.transaction_type1)
		WHERE 1 ";
			
		if ($user > 0)
			$sql .= " AND (transactions.site_user = $user OR transactions.site_user1 = $user) ";
		if ($start_date > 0)
			$sql .= " AND transactions.date >= '$start_date' ";
		if ($type > 0 && !$user)
			$sql .= " AND (transactions.transaction_type = $type OR transactions.transaction_type1 = $type) ";
		elseif ($type > 0 && $user)
			$sql .= " AND IF(transactions.site_user = $user,transactions.transaction_type,transactions.transaction_type1) = $type ";
		if ($currency && $user)
			$sql .= " AND transactions.currency = {$currency_info['id']} ";

		if ($per_page > 0 && !$count && !$dont_paginate)
			$sql .= " ORDER BY $order_by $order_desc LIMIT $r1,$per_page1 ";
		if (!$count && $dont_paginate)
			$sql .= " ORDER BY transactions.id DESC ";

		$result = db_query_array($sql);
		if ($CFG->memcached) {
			if (!$result)
				$result = array();
			
			if (($per_page == 5 || $per_page == 1) && !$count && !$public_api_all) {
				$CFG->m->set('trans_l5_'.$currency_info['currency'],$result,300);
				$result1 = array_slice($result,0,1);
				$CFG->m->set('trans_l1_'.$currency_info['currency'],$result1,300);
			}
			elseif ($public_api_all)
				$CFG->m->set('trans_api'.(($per_page) ? '_l'.$per_page : '').(($user) ? '_u'.$user : '').(($currency) ? '_c'.$currency_info['currency'] : '').(($type) ? '_t'.$type : ''),$result,300);
			
			if ($public_api_all) {
				$cached = $CFG->m->get('trans_cache');
				if (!$cached)
					$cached = array();
				
				$key = (($per_page) ? '_l'.$per_page : '').(($user) ? '_u'.$user : '').(($currency) ? '_c'.$currency_info['currency'] : '').(($type) ? '_t'.$type : '');
				$cached[$key] = true;
				$CFG->m->set('trans_cache',$cached,300);
			}
		}
		
		if ($result && count($result) == 0)
			return false;
		
		if (!$count)
			return $result;
		else
			return $result[0]['total'];
	}
	
	public static function getTypes() {
		global $CFG;
		
		if ($CFG->memcached) {
			$cached = $CFG->m->get('transaction_types');
			if ($cached) {
				return $cached;
			}
		}
		
		$sql = "SELECT * FROM transaction_types ORDER BY id ASC ";
		$result = db_query_array($sql);
		
		if ($CFG->memcached)
			$CFG->m->set('transaction_types',$result,300);
		
		return $result;
	}
}