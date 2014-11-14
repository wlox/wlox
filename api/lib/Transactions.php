<?php
class Transactions {
	function get($count=false,$page=false,$per_page=false,$currency=false,$user=false,$start_date=false,$type=false,$order_by=false,$order_desc=false,$public_api_all=false) {
		global $CFG;
		
		if ($user && !(User::$info['id'] > 0))
			return false;
		
		$page = preg_replace("/[^0-9]/", "",$page);
		$per_page = preg_replace("/[^0-9]/", "",$per_page);
		$page = preg_replace("/[^0-9]/", "",$page);
		$currency = preg_replace("/[^a-zA-Z]/", "",$currency);
		$start_date = preg_replace ("/[^0-9: \-]/","",$start_date);
		
		$page = ($page > 0) ? $page - 1 : 0;
		$r1 = $page * $per_page;
		$order_arr = array('date'=>'transactions.date','btc'=>'transactions.btc','btcprice'=>'usd_price','fiat'=>'usd_amount','fee'=>'usd_fee');
		$order_by = ($order_by) ? $order_arr[$order_by] : 'transactions.date';
		$order_desc = ($order_desc) ? 'ASC' : 'DESC';
		$user = ($user) ? User::$info['id'] : false;
		$usd_info = $CFG->currencies['USD'];
		$currency_info = $CFG->currencies[strtoupper($currency)];
		$conversion = ($usd_info['id'] == $currency_info['id']) ? ' currencies.usd_ask' : ' (1 / IF(transactions.currency = '.$usd_info['id'].','.$currency_info['usd_ask'].', '.$currency_info['usd_ask'].' / currencies.usd_ask))';
		$conversion1 = ($usd_info['id'] == $currency_info['id']) ? ' currencies1.usd_ask' : ' (1 / IF(transactions.currency1 = '.$usd_info['id'].','.$currency_info['usd_ask'].', '.$currency_info['usd_ask'].' / currencies1.usd_ask))';
		
		if ($type == 'buy')
			$type = $CFG->transactions_buy_id;
		elseif ($type == 'sell')
			$type = $CFG->transactions_sell_id;
		else
			$type = preg_replace("/[^0-9]/", "",$type);
		
		//$currency = (!$currency) ? 'usd' : $currency;
		
		if (!$count && !$public_api_all)
			$sql = "SELECT transactions.*, (currencies.usd_ask * transactions.fiat) AS usd_amount, (currencies.usd_ask * transactions.btc_price) AS usd_price, (UNIX_TIMESTAMP(transactions.date) - ({$CFG->timezone_offset})) AS time_since ".(($user > 0) ? ",IF(transactions.site_user = $user,transaction_types.name_{$CFG->language},transaction_types1.name_{$CFG->language}) AS type, IF(transactions.site_user = $user,transactions.fee,transactions.fee1) AS fee, IF(transactions.site_user = $user,transactions.btc_net,transactions.btc_net1) AS btc_net, IF(transactions.site_user1 = $user,transactions.orig_btc_price,transactions.btc_price) AS fiat_price, IF(transactions.site_user = $user,currencies.currency,currencies1.currency) AS currency, IF(transactions.site_user = $user,currencies.fa_symbol,currencies1.fa_symbol) AS fa_symbol" : ", ".(($CFG->cross_currency_trades) ? "ROUND((CASE WHEN transactions.currency = {$currency_info['id']} THEN transactions.btc_price WHEN transactions.currency1 = {$currency_info['id']} THEN transactions.orig_btc_price ELSE (transactions.orig_btc_price * $conversion1) END),2)" : 'transactions.btc_price')." AS btc_price, currencies.currency AS currency, currencies1.currency AS currency1, LOWER(transaction_types1.name_{$CFG->language}) AS maker_type, ".(($currency && !$user && $CFG->cross_currency_trades) ? "'".$currency_info['fa_symbol']."'" : 'currencies.fa_symbol')." AS fa_symbol ").", UNIX_TIMESTAMP(transactions.date) AS datestamp ";
		elseif ($public_api_all && $user)
			$sql = "SELECT transactions.id AS id, transactions.date AS date, transactions.btc AS btc, LOWER(IF(transactions.site_user = $user,transaction_types.name_{$CFG->language},transaction_types1.name_{$CFG->language})) AS type, IF(transactions.site_user1 = $user,transactions.orig_btc_price,transactions.btc_price) AS price, ROUND((IF(transactions.site_user1 = $user,transactions.orig_btc_price,transactions.btc_price) * IF(transactions.site_user = $user,transactions.btc_net,transactions.btc_net1)),2) AS amount, ROUND((IF(transactions.site_user1 = $user,transactions.orig_btc_price,transactions.btc_price) * IF(transactions.site_user = $user,transactions.fee,transactions.fee1)),2) AS fee, currencies.currency AS currency ";
		elseif ($public_api_all && !$user && $currency)
			$sql = "SELECT transactions.id AS id, transactions.date AS date, transactions.btc AS btc, LOWER(transaction_types1.name_{$CFG->language}) AS maker_type, ROUND((CASE WHEN transactions.currency = {$currency_info['id']} THEN transactions.btc_price WHEN transactions.currency1 = {$currency_info['id']} THEN transactions.orig_btc_price ELSE (transactions.orig_btc_price * $conversion1) END),2) AS price, ROUND((CASE WHEN transactions.currency = {$currency_info['id']} THEN (transactions.btc_price * transactions.btc) WHEN transactions.currency1 = {$currency_info['id']} THEN (transactions.orig_btc_price * transactions.btc) ELSE ((transactions.orig_btc_price * transactions.btc) * $conversion1) END),2) AS amount, IF(transactions.currency != {$currency_info['id']} AND transactions.currency1 != {$currency_info['id']},currencies1.currency,'{$currency_info['currency']}') AS currency ";
		elseif ($public_api_all && !$user)
			$sql = "SELECT transactions.id AS id, transactions.date AS date, transactions.btc AS btc, transactions.btc_price AS price, transactions.orig_btc_price AS price1, ROUND((transactions.btc_price * transactions.btc),2) AS amount, ROUND((transactions.orig_btc_price * transactions.btc),2) AS amount1, currencies.currency AS currency, currencies1.currency AS currency1 ";
		else
			$sql = "SELECT COUNT(transactions.id) AS total FROM transactions ";
			
		$sql .= " 
		FROM transactions
		LEFT JOIN transaction_types ON (transaction_types.id = transactions.transaction_type)
		LEFT JOIN transaction_types transaction_types1 ON (transaction_types1.id = transactions.transaction_type1)
		LEFT JOIN currencies currencies ON (currencies.id = transactions.currency)
		LEFT JOIN currencies currencies1 ON (currencies1.id = transactions.currency1)
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
			$sql .= " ORDER BY $order_by $order_desc LIMIT $r1,$per_page ";
		if (!$count && $dont_paginate)
			$sql .= " ORDER BY transactions.date DESC ";
		
		$result = db_query_array($sql);
		if (!$count)
			return $result;
		else
			return $result[0]['total'];
	}
	
	function getTypes() {
		$sql = "SELECT * FROM transaction_types ORDER BY id ASC ";
		return db_query_array($sql);
	}
	
	function pagination($link_url,$page,$total_rows,$rows_per_page=0,$max_pages=0,$pagination_label=false,$target_elem=false) {
		global $CFG;
	
		$link_url = preg_replace("/[^a-zA-Z\.]/", "",$link_url);
		$page = preg_replace("/[^0-9]/", "",$page);
		$total_rows = preg_replace("/[^0-9]/", "",$total_rows);
		$rows_per_page = preg_replace("/[^0-9]/", "",$rows_per_page);
		$max_pages = preg_replace("/[^0-9]/", "",$max_pages);
		$pagination_label = preg_replace("/[^0-9a-zA-Z!@#$%&*?\.\-_]/", "",$pagination_label);
		$target_elem = preg_replace("/[^0-9a-zA-Z!@#$%&*?\.\-_]/", "",$target_elem);
		
		$page = ($page > 0) ? $page : 1;
		if (!($rows_per_page > 0))
			return false;
	
		if ($total_rows > $rows_per_page) {
			$num_pages = ceil($total_rows / $rows_per_page);
			$page_array = range(1,$num_pages);
				
			if ($max_pages > 0) {
				$p_deviation = ($max_pages - 1) / 2;
				$alpha = $page - 1;
				$alpha = ($alpha < $p_deviation) ? $alpha : $p_deviation;
				$beta = $num_pages - $page;
				$beta = ($beta < $p_deviation) ? $beta : $p_deviation;
				if ($alpha < $p_deviation) $beta = $beta + ($p_deviation - $alpha);
				if ($beta < $p_deviation) $alpha = $alpha + ($p_deviation - $beta);
			}
			if ($page != 1)
				$first_page = '<a href="'.$link_url.'?'.(http_build_query(array('page'=>1))).'">'.$CFG->first_page_text.'</a>';
			if ($page != $num_pages)
				$last_page = ' &nbsp;<a href="'.$link_url.'?'.(http_build_query(array('page'=>$num_pages))).'">'.$CFG->last_page_text.'</a>';
	
			$pagination = '<div class="pagination"><div style="float:left;">'.$first_page;
			foreach ($page_array as $p) {
				if (($p >= ($page - $alpha) && $p <= ($page + $beta)) || $max_pages == 0) {
					if ($p == $page) {
						$pagination .= ' <span>'.$p.'</span> ';
					}
					else {
						$pagination .= ' <a href="'.$link_url.'?'.(http_build_query(array('page'=>$p))).'">'.$p.'</a> ';
					}
				}
			}
			$pagination .= '</div>';
				
			if ($pagination_label) {
				$label = str_ireplace('[results]','<b>'.$total_rows.'</b>',$CFG->pagination_label);
				$label = str_ireplace('[num_pages]','<b>'.$num_pages.'</b>',$label);
				$pagination .= '<div style="float:right" class="pagination_label">'.$label.'</div>';
			}
			$pagination .= $last_page.'<div style="clear:both;height:0;">&nbsp;</div></div>';
			return $pagination;
		}
	}
	
	function getList($currency=false,$notrades=false,$limit_7=false) {
		global $CFG;
		
		$currency1 = preg_replace("/[^a-zA-Z]/", "",$currency);
		$currency_info = $CFG->currencies[strtoupper($currency1)];
		
		if ($limit_7)
			$limit = " LIMIT 0,10";
		elseif (!$notrades)
			$limit = " LIMIT 0,5 ";
		
		$sql = "
		SELECT transactions.id AS id, transactions.btc AS btc, transactions.btc_price AS btc_price, currencies.fa_symbol AS fa_symbol, (UNIX_TIMESTAMP(transactions.date) - ({$CFG->timezone_offset})) AS time_since
		FROM transactions
		LEFT JOIN currencies ON (currencies.id = transactions.currency)
		WHERE 1
		AND transactions.currency = {$currency_info['id']}
		AND (transactions.transaction_type = $CFG->transactions_buy_id OR transactions.transaction_type1 = $CFG->transactions_buy_id)
		ORDER BY transactions.date DESC $limit ";
		//return $sql;
		return db_query_array($sql);
	}

	function getHistory($currency=false,$type=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$type = preg_replace("/[^0-9]/", "",$type);
		$currency = preg_replace("/[^a-zA-Z]/", "",$currency);
		
		$user = User::$info['id'];
		$sql = "
		SELECT transactions.*, UNIX_TIMESTAMP(transactions.date) AS datestamp, currencies.currency AS currency, (currencies.usd_ask * transactions.fiat) AS usd_amount, transactions.btc_price AS fiat_price, currencies.fa_symbol AS fa_symbol ".(($user > 0) ? ",IF(transactions.site_user = $user,transaction_types.name_{$CFG->language},transaction_types1.name_{$CFG->language}) AS type, IF(transactions.site_user = $user,transactions.fee,transactions.fee1) AS fee, IF(transactions.site_user = $user,transactions.btc_net,transactions.btc_net1) AS btc_net" : "")."
		FROM transactions
		LEFT JOIN transaction_types ON (transaction_types.id = transactions.transaction_type)
		LEFT JOIN transaction_types transaction_types1 ON (transaction_types1.id = transactions.transaction_type1)
		LEFT JOIN currencies ON (currencies.id = transactions.currency)
		WHERE 1";
		
		if ($type > 0)
			$sql .= " AND IF(transactions.site_user = $user,transactions.transaction_type,transactions.transaction_type1) = $type ";
		if ($currency)
			$sql .= " AND currencies.currency = '$currency' ";
		
		$sql .= "
		AND transactions.site_user = $user
		ORDER BY transactions.date DESC LIMIT 0,1 ";
		
		$result = db_query_array($sql);
		return $result[0];
	}
}