<?php
class Orders {
	public static function get($count=false,$page=false,$per_page=false,$currency=false,$user=false,$start_date=false,$show_bids=false,$order_by1=false,$order_desc=false,$open_orders=false,$public_api_open_orders=false,$public_api_order_book=false) {
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
		$order_arr = array('date'=>'orders.id','btc'=>'orders.btc','btcprice'=>'usd_price','fiat'=>'orders.btc');
		$order_by = ($order_by1) ? $order_arr[$order_by1] : ((!$currency && $user) ? 'usd_price' : 'btc_price');
		$order_desc = ($order_desc && ($order_by1 != 'date' && $order_by1 != 'fiat' && $order_by1 != 'btc')) ? 'ASC' : 'DESC';
		$currency_info = (!empty($CFG->currencies[strtoupper($currency)])) ? $CFG->currencies[strtoupper($currency)] : false;
		$usd_info = $CFG->currencies['USD'];
		$user = ($user) ? User::$info['id'] : false;
		$type = ($show_bids) ? $CFG->order_type_bid : $CFG->order_type_ask;
		$user_id = (User::$info['id'] > 0) ? User::$info['id'] : '0';
		$usd_field = 'usd_ask';
		$conv_comp = ($show_bids) ? '-' : '+';
		$cached = false;

		if ($CFG->cross_currency_trades) {
			$price_str = '(orders.btc_price * CASE orders.currency WHEN '.$currency_info['id'].' THEN 1';
			$price_str_usd = '(orders.btc_price * CASE orders.currency';
			$currency_abbr = '(CASE orders.currency';
			foreach ($CFG->currencies as $curr_id => $currency1) {
				if (is_numeric($curr_id) || $currency1['currency'] == 'BTC')
					continue;
				
				if (!empty($currency_info) && $currency1['id'] == $currency_info['id'])
					continue;
		
				$conversion = (empty($currency_info) || $currency_info['currency'] == 'USD') ? $currency1[$usd_field] : $currency1[$usd_field] / $currency_info[$usd_field];
				$price_str .= ' WHEN '.$currency1['id'].' THEN '.($conversion + ($conversion * $CFG->currency_conversion_fee * ($show_bids ? -1 : 1))).' ';
				$price_str_usd .= ' WHEN '.$currency1['id'].' THEN '.$conversion.' ';
				$currency_abbr .= ' WHEN '.$currency1['id'].' THEN "'.$currency1['currency'].'" ';
			}
			$price_str .= ' END)';
			$price_str_usd .= ' END)';
			$currency_abbr .= ' END)';
		}
		else
			$price_str = 'orders.btc_price';
		
		if (!$count && !$public_api_open_orders && !$public_api_order_book)
			$sql = "SELECT orders.id, orders.currency, orders.market_price, orders.stop_price, orders.log_id, orders.fiat, UNIX_TIMESTAMP(orders.date) AS `date`, ".(!$open_orders ? 'SUM(orders.btc) AS btc,' : 'orders.btc,')." ".(($open_orders) ? 'ROUND('.$price_str_usd.',2) AS usd_price, orders.btc_price, ' : 'ROUND('.$price_str.',2) AS btc_price,')." order_types.name_{$CFG->language} AS type, orders.btc_price AS fiat_price, (UNIX_TIMESTAMP(orders.date) * 1000) AS time_since, site_users.user AS user_id ".($order_by == 'usd_amount' ? ', (orders.btc * '.$price_str_usd.') AS usd_amount' : '') ;
		elseif (!$count && $public_api_order_book)
			$sql = "SELECT ROUND($price_str,2) AS price, orders.btc AS order_amount, ROUND((orders.btc * $price_str),2) AS order_value, $currency_abbr AS converted_from, UNIX_TIMESTAMP(orders.date) AS `timestamp` ";
		elseif (!$count && $public_api_open_orders)
			$sql = "SELECT order_log.id AS id, IF(order_log.order_type = {$CFG->order_type_bid},'buy','sell') AS side, (IF(order_log.market_price = 'Y','market',IF(order_log.stop_price > 0,'stop','limit'))) AS `type`, order_log.btc AS amount, IF(order_log.status = 'ACTIVE',orders.btc,order_log.btc_remaining) AS amount_remaining, order_log.btc_price AS price, ROUND(SUM(IF(transactions.id IS NOT NULL OR transactions1.id IS NOT NULL,(transactions.btc  / (order_log.btc - IF(order_log.status = 'ACTIVE',orders.btc,order_log.btc_remaining))) * IF(transactions.id IS NOT NULL,transactions.btc_price,transactions1.orig_btc_price),0)),2) AS avg_price_executed, order_log.stop_price AS stop_price, $currency_abbr AS currency, order_log.status AS status, order_log.p_id AS replaced, IF(order_log.status = 'REPLACED',replacing_order.id,0) AS replaced_by, UNIX_TIMESTAMP(orders.date) AS `timestamp`";
		else
			$sql = "SELECT COUNT(orders.id) AS total ";
			
		$sql .= " 
		FROM orders
		LEFT JOIN order_types ON (order_types.id = orders.order_type)";
		
		if ($public_api_open_orders) {
			$sql .= "
		LEFT JOIN order_log ON (order_log.id = orders.log_id)
		LEFT JOIN transactions ON (order_log.id = transactions.log_id)
		LEFT JOIN transactions transactions1 ON (order_log.id = transactions1.log_id1)
		LEFT JOIN order_log replacing_order ON (order_log.id = replacing_order.p_id)";
		}
		else if (!$public_api_order_book) {
			$sql .='
		LEFT JOIN site_users ON (orders.site_user = site_users.id)';
		}
		
		$sql .= "
		WHERE 1 ";
			
		if ($user > 0)
			$sql .= " AND orders.site_user = $user ";
		else
			$sql .= ' AND orders.btc_price > 0 AND orders.market_price != "Y" ';
		
		if ($start_date > 0)
			$sql .= " AND orders.date >= '$start_date' ";
		if ($type > 0)
			$sql .= " AND orders.order_type = $type ";
		
		if ($currency && ($user > 0 || !$CFG->cross_currency_trades))
			$sql .= " AND orders.currency = {$currency_info['id']} ";
		
		if (!$user && !$public_api_order_book)
			$sql .= ' GROUP BY orders.btc_price,orders.currency ';
		
		if ($public_api_open_orders)
			$sql .= ' GROUP BY order_log.id ';
		
		if ($public_api_order_book)
			$sql .= ' GROUP BY orders.btc_price,orders.currency ';
			
		if (!$count && !$open_orders && !$public_api_open_orders && !$public_api_order_book)
			$sql .= " ORDER BY $order_by $order_desc ".($per_page ? "LIMIT $r1,$per_page" : '');
		if (!$count && $open_orders && !$public_api_open_orders && !$public_api_order_book)
			$sql .= " ORDER BY $order_by $order_desc ";
		if ($public_api_open_orders)
			$sql .= " ORDER BY price $order_desc";
		if ($public_api_order_book)
			$sql .= " ORDER BY price $order_desc LIMIT $r1,$per_page";
		
		$result = db_query_array($sql);
		if ($result && count($result) == 0)
			$result = false;
		
		if (!$count)
			return $result;
		else
			return $result[0]['total'];
		
	}
	
	public static function getRecord($order_id,$order_log_id=false,$user_id=false,$for_update=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$order_id = preg_replace("/[^0-9]/", "",$order_id);
		$order_log_id = preg_replace("/[^0-9]/", "",$order_log_id);
		$user_id = ($user_id > 0) ? $user_id : User::$info['id'];
		
		if (!($order_id > 0 || $order_log_id > 0))
			return false;
		
		if ($order_id > 0) {
			$sql = "SELECT * FROM orders WHERE id = $order_id";
			if ($user_id)
				$sql .= ' AND site_user = '.$user_id;
		}
		else {
			$sql = "SELECT orders.* FROM orders LEFT JOIN order_log ON (order_log.id = orders.log_id) WHERE order_log.id = $order_log_id ";
			if ($user_id)
				$sql .= ' AND order_log.site_user = '.$user_id;
		}
		
		$sql .= ' LIMIT 0,1 ';
		if ($for_update)
			$sql .= ' FOR UPDATE';
		
		$result = db_query_array($sql);
		
		if ($result[0]['id'] > 0) {
			$result[0]['user_id'] = User::$info['id'];
			$result[0]['is_bid'] = ($result[0]['order_type'] ==$CFG->order_type_bid);
			$result[0]['currency_abbr'] = $CFG->currencies[$result[0]['currency']]['currency'];
		}
		
		return $result[0];
	}
	
	public static function getBidAsk($currency=false,$currency_id=false,$absolute=false) {
		global $CFG;

		if (empty($currency) && empty($currency_id))
			return false;
		
		$currency = preg_replace("/[^a-zA-Z]/", "",$currency);
		$currency_id = preg_replace("/[^0-9]/", "",$currency_id);
		$currency_info = ($currency_id > 0) ? $CFG->currencies[$currency_id] : $CFG->currencies[strtoupper($currency)];
		$usd_field = 'usd_ask';
		
		if ($CFG->cross_currency_trades) {
			$price_str_bid = '(orders.btc_price * CASE orders.currency WHEN '.$currency_info['id'].' THEN 1';
			$price_str_ask = '(orders.btc_price * CASE orders.currency WHEN '.$currency_info['id'].' THEN 1';
			foreach ($CFG->currencies as $curr_id => $currency1) {
				if (is_numeric($curr_id) || $currency1['currency'] == 'BTC')
					continue;
		
				if (!empty($currency_info) && $currency1['id'] == $currency_info['id'])
					continue;
		
				$conversion = (empty($currency_info) || $currency_info['currency'] == 'USD') ? $currency1[$usd_field] : $currency1[$usd_field] / $currency_info[$usd_field];
				$price_str_bid .= ' WHEN '.$currency1['id'].' THEN '.($conversion - ((!$absolute) ? $conversion * $CFG->currency_conversion_fee : 0)).' ';
				$price_str_ask .= ' WHEN '.$currency1['id'].' THEN '.($conversion + ((!$absolute) ? $conversion * $CFG->currency_conversion_fee : 0)).' ';
			}
			$price_str_bid .= ' END)';
			$price_str_ask .= ' END)';
		}
		else {
			$price_str_bid = 'orders.btc_price';
			$price_str_ask = 'orders.btc_price';
		}
		
		$sql = "SELECT ROUND(MAX(IF(orders.order_type = {$CFG->order_type_bid},$price_str_bid,NULL)),2) AS bid, ROUND(MIN(IF(orders.order_type = {$CFG->order_type_ask},$price_str_ask,NULL)),2) AS ask FROM orders WHERE 1 ".((!$CFG->cross_currency_trades) ? "AND orders.currency = {$currency_info['id']}" : false)." AND orders.btc_price > 0 LIMIT 0,1";
		$result = db_query_array($sql);
		$res = ($result[0]) ? $result[0] : array('bid'=>0,'ask'=>0);
		
		if ($res['bid'] > 0 && !$res['ask'])
			$res['ask'] = $res['bid'];
		
		if ($res['ask'] > 0 && !$res['bid'])
			$res['bid'] = $res['ask'];

		if (!$res['ask'] && !$res['bid']) {
			$sql = "SELECT currency, currency1, btc_price, orig_btc_price FROM transactions WHERE 1 ".((!$CFG->cross_currency_trades) ? "AND transactions.currency = {$currency_info['id']}" : '')." ORDER BY id DESC LIMIT 0,1";
			$result = db_query_array($sql);
			
			if ($result) {
				if ($CFG->cross_currency_trades) {
					if ($result[0]['currency'] == $currency_info['id'])
						$result[0]['fiat_price'] = $result[0]['btc_price'];
					else 
						$result[0]['fiat_price'] = round($result[0]['orig_btc_price'] * ((empty($currency_info) || $currency_info['currency'] == 'USD') ? $CFG->currencies[$result[0]['currency1']][$usd_field] : $CFG->currencies[$result[0]['currency1']][$usd_field] / $currency_info[$usd_field]),2,PHP_ROUND_HALF_UP);
				}
			}
			else {
				$currency_info1 = $CFG->currencies['USD'];
				$sql = "SELECT ROUND((btc_price/{$currency_info['usd_ask']}),2) AS fiat_price FROM transactions WHERE currency = {$currency_info1['id']} ORDER BY id DESC LIMIT 0,1";
				$result = db_query_array($sql);
				
				if (!$result) {
					$sql = 'SELECT ROUND((usd /'.$currency_info['usd_ask'].'),2) AS fiat_price FROM historical_data ORDER BY id DESC LIMIT 0,1';
					$result = db_query_array($sql);
				}
			}
			
			if ($result)
				$res = array('bid'=>$result[0]['fiat_price'],'ask'=>$result[0]['fiat_price']);
		}
		
		return $res;
	}
	
	private static function triggerStops($max_price,$min_price,$currency,$maker_is_sell=false,$abs_bid=false,$abs_ask=false,$currency_max=false,$currency_min=false) {
		global $CFG;
		
		if (!($max_price && $min_price) || !$currency)
			return false;
		
		$usd_field = 'usd_ask';
		$currency_info = $CFG->currencies[strtoupper($currency)];
		
		if ($CFG->cross_currency_trades) {
			$price_str = '(CASE orders.currency WHEN '.$currency_info['id'].' THEN '.$min_price;
			$price_str1 = '(CASE orders.currency WHEN '.$currency_info['id'].' THEN '.$max_price;
			foreach ($CFG->currencies as $curr_id => $currency1) {
				if (is_numeric($curr_id) || $currency1['currency'] == 'BTC' || $currency1['id'] == $currency_info['id'])
					continue;
		
				$c_min = (!empty($currency_min) && !empty($currency_min[$currency1['id']])) ? $currency_min[$currency1['id']] : +INF;
				$c_max = (!empty($currency_max) && !empty($currency_max[$currency1['id']])) ? $currency_max[$currency1['id']] : 0; 
				$conversion1 = ($currency_info['currency'] == 'USD') ? 1 / $currency1[$usd_field] : $currency_info[$usd_field] / $currency1[$usd_field];
				
				$price_str .= ' WHEN '.$currency1['id'].' THEN '.($c_min < round($min_price * $conversion1,2,PHP_ROUND_HALF_UP) ? $c_min : round($min_price * $conversion1,2,PHP_ROUND_HALF_UP)).' ';
				$price_str1 .= ' WHEN '.$currency1['id'].' THEN '.($c_max > round($max_price * $conversion1,2,PHP_ROUND_HALF_UP) ? $c_max : round($max_price * $conversion1,2,PHP_ROUND_HALF_UP)).' ';
			}
			$price_str .= ' END)';
			$price_str1 .= ' END)';
		}
		else {
			$price_str = $price;
			$price_str1 = $price;
		}
		
		$sql = "UPDATE orders 
				SET orders.market_price = 'Y', orders.btc_price = IF(orders.btc_price > 0,orders.btc_price,orders.stop_price)
				WHERE ((orders.stop_price >= $price_str AND orders.order_type = {$CFG->order_type_ask}) OR (orders.stop_price <= $price_str1 AND orders.order_type = {$CFG->order_type_bid}))
				AND orders.stop_price > 0
				".((!$CFG->cross_currency_trades) ? "AND orders.currency = {$currency_info['id']}" : false);

		return db_query($sql);
	}
	
	public static function getMarketOrders() {
		global $CFG;
		
		$sql = 'SELECT orders.order_type, orders.btc_price AS orig_btc_price, orders.btc AS btc_outstanding, orders.currency, orders.market_price AS is_market, orders.id, orders.site_user, orders.stop_price FROM orders WHERE orders.market_price = "Y"';
		return db_query_array($sql);
	}

	public static function getCompatible($type,$price,$currency,$amount,$for_update=false,$market_price=false,$executed_orders=false,$compare_with_conv_fees=false,$site_user=false,$get_all_market=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;

		$site_user = ($site_user) ? $site_user : User::$info['id'];
		
		if (!$type || !$price || !$currency)
			return false;
		
		$currency_info = $CFG->currencies[strtoupper($currency)];
		$comparison = ($type == $CFG->order_type_ask) ? '<=' : '>=';
		$usd_field = 'usd_ask';
		$order_asc = ($type == $CFG->order_type_ask) ? 'ASC' : 'DESC';
		$usd_info = $CFG->currencies['USD'];
		$asc = ($type == $CFG->order_type_ask);
		$order_asc = ($asc) ? 'ASC' : 'DESC';
		
		if ($CFG->cross_currency_trades) {
			$price_str = 'IF(orders.market_price = "Y",'.$price.',(orders.btc_price * CASE orders.currency WHEN '.$currency_info['id'].' THEN 1';
			$price_str1 = '(CASE orders.currency WHEN '.$currency_info['id'].' THEN '.$price;
			foreach ($CFG->currencies as $curr_id => $currency1) {
				if (is_numeric($curr_id) || $currency1['currency'] == 'BTC' || $currency1['id'] == $currency_info['id'])
					continue;
		
				$conversion = ($currency_info['currency'] == 'USD') ? $currency1[$usd_field] : $currency1[$usd_field] / $currency_info[$usd_field];
				$conversion1 = ($currency_info['currency'] == 'USD') ? 1 / $currency1[$usd_field] : $currency_info[$usd_field] / $currency1[$usd_field];
				$price_str .= ' WHEN '.$currency1['id'].' THEN '.($conversion + (($conversion * $CFG->currency_conversion_fee) * ($type == $CFG->order_type_ask ? 1 : -1))).' ';
				$price_str1 .= ' WHEN '.$currency1['id'].' THEN '.round((($price * $conversion1) + ($compare_with_conv_fees ? (($price * $conversion1) * $CFG->currency_conversion_fee * (($type == $CFG->order_type_ask) ? -1 : 1)) : 0)),2,PHP_ROUND_HALF_UP);
			}
			$price_str .= ' END))';
			$price_str1 .= ' END)';
		}
		else {
			$price_str = 'IF(orders.market_price = "Y",'.$price.',orders.btc_price)';
			$price_str1 = $price;
		}
		
		
		$sql = "SELECT orders.id, orders.market_price AS is_market, orders.order_type AS order_type, orders.btc_price,
				orders.btc AS btc_outstanding, 
				orders.site_user AS site_user, 
				orders.log_id AS log_id, 
				orders.currency AS currency_id,
				orders.stop_price AS stop_price,
				ROUND($price_str,2) AS fiat_price
				".(($market_price && !$get_all_market) ? ',@running_total := @running_total + orders.btc AS cumulative_sum' : '')."
				FROM orders
				".(($market_price && !$get_all_market) ? 'JOIN (SELECT @running_total := 0) r' : '')."
				WHERE 1
				".((!$get_all_market) ? "AND orders.order_type = $type " : false)."
				".((!$market_price) ? " AND (orders.btc_price $comparison $price_str1 OR orders.market_price = 'Y') " : false)."
				".((!$CFG->cross_currency_trades) ? "AND orders.currency = {$currency_info['id']}" : false)."
				".(($get_all_market) ? " AND orders.market_price = 'Y' " : false)."
				AND orders.btc_price > 0
				".(($market_price && !$get_all_market) ? " AND @running_total <= $amount " : false)."
				".((!$get_all_market) ? " AND orders.site_user != ".$site_user : false).' ORDER BY '.(($market_price && !$get_all_market) ? 'fiat_price '.$order_asc : 'NULL');

		//if ($for_update)
			//$sql .= ' FOR UPDATE';
		
		$result = db_query_array($sql);
		if ($result){
			foreach ($result as $key => $row) {
				$conversion = ($currency_info['currency'] == 'USD') ? $CFG->currencies[$row['currency_id']][$usd_field] : $CFG->currencies[$row['currency_id']][$usd_field] / $currency_info[$usd_field];
				$result[$key]['real_market_price'] = $row['btc_price'] * $conversion;
				$result[$key]['currency_abbr'] = $CFG->currencies[$row['currency_id']]['currency'];
				
				if ($row['is_market'] == 'Y') {
					$conversion1 = ($currency_info['currency'] == 'USD') ? 1 / $CFG->currencies[$row['currency_id']][$usd_field] : $currency_info[$usd_field] / $CFG->currencies[$row['currency_id']][$usd_field];
					$result[$key]['orig_btc_price'] = ($row['currency_id'] == $currency_info['id'] || !$CFG->cross_currency_trades) ? $price : round((($price * $conversion1) + (($price * $conversion1) * $CFG->currency_conversion_fee * (($type == $CFG->order_type_ask) ? -1 : 1))),2,PHP_ROUND_HALF_UP);
				}
				else {
					$result[$key]['orig_btc_price'] = $row['btc_price'];
				}
				
				if ($CFG->cross_currency_trades) {
					$result[$key]['conversion_factor'] = ($row['currency_id'] == $currency_info['id']) ? 0 : 1 * ($conversion + ($conversion * $CFG->currency_conversion_fee * (($type == $CFG->order_type_ask) ? 1 : -1)));
					$result[$key]['orig_conversion_factor'] = ($row['currency_id'] == $currency_info['id']) ? 0 : 1 * $conversion;
				}
			}
			
			if (!$market_price) {
				usort($result, function($a,$b) use ($asc) {
					if ($asc)
						$cmp = $a['fiat_price'] - $b['fiat_price'];
					else
						$cmp = $b['fiat_price'] - $a['fiat_price'];
					
					if ($cmp === 0) {
						return $b['id'] - $a['id'];
					}
					return $cmp;
				});
			}
		}
		return $result;
	}
	
	public static function lockOrder($user_id,$order_id,$currency_id) {
		global $CFG;
		
		$user_fee = FeeSchedule::getUserFees($user_id);
		$orig_order = DB::getRecord('orders',$order_id,0,1,false,false,false,1);
		$user_balances = User::getBalances($user_id,array($currency_id,$CFG->btc_currency_id),true);
		$on_hold = User::getOnHold(1,$user_id,$user_fee,array($currency_id,$CFG->btc_currency_id));
		
		$fiat_on_hold = (!empty($on_hold[$CFG->currencies[$currency_id]['currency']]['total'])) ? $on_hold[$CFG->currencies[$currency_id]['currency']]['total'] : 0;
		$btc_on_hold = $fiat_on_hold = (!empty($on_hold['BTC']['total'])) ? $on_hold['BTC']['total'] : 0;
		$btc_balance = (!empty($user_balances['btc'])) ? $user_balances['btc'] : 0;
		$fiat_balance = (!empty($user_balances[strtolower($CFG->currencies[$currency_id]['currency'])])) ? $user_balances[strtolower($CFG->currencies[$currency_id]['currency'])] : 0;
		
		return array('fiat_on_hold'=>$fiat_on_hold,'btc_on_hold'=>$btc_on_hold,'btc_balance'=>$btc_balance,'fiat_balance'=>$fiat_balance,'fee'=>$user_fee['fee'],'fee1'=>$user_fee['fee1']);
	}
	
	public static function checkUserOrders($buy,$currency_info,$user_id=false,$price,$stop_price,$fee,$is_stop=false) {
		global $CFG;

		$price = preg_replace("/[^0-9\.]/", "",$price);
		$stop_price = preg_replace("/[^0-9\.]/", "",$stop_price);
		$type = ($buy) ? $CFG->order_type_ask : $CFG->order_type_bid;
		$usd_field = 'usd_ask';
		$comparison = ($buy) ? '<=' : '>=';
		$asc = ($buy) ? 'ASC' : 'DESC';
		$user_id = (!$user_id) ? User::$info['id'] : $user_id;
		
		if ($price == $stop_price)
			$price = 0;
		
		if ($is_stop && $stop_price == 0)
			return array('error'=>array('message'=>Lang::string('buy-errors-no-stop'),'code'=>'ORDER_INVALID_STOP_PRICE'));
		
		if ($CFG->cross_currency_trades) {
			$price_str = '(CASE orders.currency WHEN '.$currency_info['id'].' THEN '.$price;
			$price_str1 = '(orders.btc_price * CASE orders.currency WHEN '.$currency_info['id'].' THEN 1';
			$stops_str = '(CASE orders.currency WHEN '.$currency_info['id'].' THEN '.$stop_price;
			$stops_str1 = '(orders.stop_price * CASE orders.currency WHEN '.$currency_info['id'].' THEN 1';
			
			foreach ($CFG->currencies as $curr_id => $currency1) {
				if (is_numeric($curr_id) || $currency1['currency'] == 'BTC' || $currency1['id'] == $currency_info['id'])
					continue;
		
				$conversion = ($currency_info['currency'] == 'USD') ? $currency1[$usd_field] : $currency1[$usd_field] / $currency_info[$usd_field];
				$conversion1 = ($currency_info['currency'] == 'USD') ? 1 / $currency1[$usd_field] : $currency_info[$usd_field] / $currency1[$usd_field];
				$price_str .= ' WHEN '.$currency1['id'].' THEN '.round($price * $conversion1,2,PHP_ROUND_HALF_UP);
				$price_str1 .= ' WHEN '.$currency1['id'].' THEN '.$conversion.' ';
				$stops_str .= ' WHEN '.$currency1['id'].' THEN '.round($stop_price * $conversion1,2,PHP_ROUND_HALF_UP);
				$stops_str1 .= ' WHEN '.$currency1['id'].' THEN '.$conversion.' ';
			}
			$price_str .= ' END)';
			$price_str1 .= ' END)';
			$stops_str .= ' END)';
			$stops_str1 .= ' END)';
		}
		else {
			$price_str = $price;
			$stops_str = $stop_price;
		}
		
		$sql = 'SELECT orders.currency,';
		$sql .= (($CFG->cross_currency_trades) ? "ROUND($price_str1,2)" : 'orders.btc_price')." AS price, ";
		
		if ($buy && $price > 0)
			$sql .= (($CFG->cross_currency_trades) ? "ROUND($stops_str1,2)" : 'orders.stop_price')." AS stop_price, ";
		
		$sql .= " 1 FROM orders
				WHERE orders.order_type = $type AND (";
		
		$conditions = array();
		if ($price > 0)
			$conditions[] = " orders.btc_price $comparison $price_str AND orders.btc_price > 0 ";
		if ($buy && $price > 0)
			$conditions[] =	" orders.stop_price >= $price_str AND orders.stop_price > 0 ";
		elseif ($stop_price > 0)
			$conditions[] = " orders.btc_price <= $stops_str AND orders.btc_price > 0 ";
		
		$sql .= implode(' OR ',$conditions).") ".((!$CFG->cross_currency_trades) ? "AND orders.currency = {$currency_info['id']}" : false)." AND orders.site_user = $user_id ORDER BY ".(($buy && $price > 0) ? 'price' : 'stop_price').' '.$asc;
		$result = db_query_array($sql);

		if ($result) {
			if ($result[0]['price'] > 0 && (!$stop_price || $result[0]['price'] > $stop_price) && !($buy && $result[0]['price'] > $price))
				return array('error'=>array('message'=>Lang::string('buy-errors-outbid-self').(($currency_info['id'] != $result[0]['currency']) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($result[0]['price'],2),' '.Lang::string('limit-max-price')) : ''),'code'=>'ORDER_OUTBID_SELF'));
			elseif ($buy && !empty($result[0]['stop_price']) && $result[0]['stop_price'] > 0)
				return array('error'=>array('message'=>Lang::string('buy-limit-under-stops').(($currency_info['id'] != $result[0]['currency']) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($result[0]['stop_price'],2),' '.Lang::string('limit-min-price')) : ''),'code'=>'ORDER_BUY_LIMIT_UNDER_STOPS'));
			elseif (!$buy && $result[0]['price'] > 0 && $stop_price && $result[0]['price'] <= $stop_price)
				return array('error'=>array('message'=>Lang::string('sell-limit-under-stops').(($currency_info['id'] != $result[0]['currency']) ? str_replace('[price]',$currency_info['fa_symbol'].number_format($result[0]['price'],2),' '.Lang::string('limit-max-price')) : ''),'code'=>'ORDER_BUY_LIMIT_UNDER_STOPS'));
		}
		
		return false;
	}
	
	public static function checkPreconditions($buy,$currency_info,$amount,$price,$stop_price,$fee,$user_available,$current_bid,$current_ask,$market_price,$user_id=false,$orig_order=false) {
		global $CFG;
		
		$subtotal = $amount * (($stop_price > 0 && !($price) > 0) ? $stop_price : $price);
		$fee_amount = ($fee * 0.01) * $subtotal;
		$total = ($buy) ? $subtotal + $fee_amount : $subtotal - $fee_amount;
		$edit_old_fiat = ($orig_order) ? ($orig_order['btc'] * $orig_order['btc_price']) + (($orig_order['btc'] * $orig_order['btc_price']) * ($fee * 0.01)) : 0; 
		$edit_old_btc = ($orig_order) ? $orig_order['btc'] : 0;
		$user_id = (!$user_id) ? User::$info['id'] : $user_id;
		
		if ($price == $stop_price)
			$price = 0;
		
		if (($buy && ($total - $edit_old_fiat) > $user_available) || (!$buy && ($amount - $edit_old_btc) > $user_available))
			return array('error'=>array('message'=>Lang::string('buy-errors-balance-too-low'),'code'=>'ORDER_BALANCE_TOO_LOW'));

		if (($subtotal * $currency_info['usd_ask']) < $CFG->orders_min_usd)
			return array('error'=>array('message'=>str_replace('[amount]',number_format(($CFG->orders_min_usd/$currency_info['usd_ask']),2),str_replace('[fa_symbol]',$currency_info['fa_symbol'],Lang::string('buy-errors-too-little'))),'code'=>'ORDER_UNDER_MINIMUM'));
		
		if ((($buy && $stop_price > 0 && $stop_price <= $current_ask) || (!$buy && $stop_price >= $current_bid)) && $stop_price > 0)
			return array('error'=>array('message'=>($buy) ? Lang::string('buy-stop-lower-ask') : Lang::string('sell-stop-higher-bid'),'code'=>'ORDER_STOP_IN_MARKET'));

		if ((($buy && $stop_price <= $price) || (!$buy && $stop_price >= $price)) && $stop_price > 0 && $price > 0)
			return array('error'=>array('message'=>($buy) ? Lang::string('buy-stop-lower-price') : Lang::string('sell-stop-lower-price'),'code'=>'ORDER_STOP_OVER_LIMIT'));

		if ($buy && !$stop_price && $price < ($current_ask - ($current_ask * (0.01 * $CFG->orders_under_market_percent))))
			return array('error'=>array('message'=>str_replace('[percent]',$CFG->orders_under_market_percent,Lang::string('buy-errors-under-market')),'code'=>'ORDER_TOO_FAR_UNDER_MARKET'));
		
		if ($market_price) {
			$type = (!$buy) ? $CFG->order_type_bid : $CFG->order_type_ask;
			$sql = 'SELECT id FROM orders WHERE order_type = '.$type.' AND site_user != '.$user_id.' LIMIT 0,1';
			$result = db_query_array($sql);
			if (!$result)
				return array('error'=>array('message'=>Lang::string('buy-errors-no-compatible'),'code'=>'ORDER_MARKET_NO_COMPATIBLE'));
		}
		return false;
	}
	
	public static function executeOrder($buy,$price,$amount,$currency1,$fee,$market_price,$edit_id=0,$this_user_id=0,$external_transaction=false,$stop_price=false,$use_maker_fee=false,$verbose=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		if ($CFG->trading_status == 'suspended') {
			db_commit();
			return array('error'=>array('message'=>Lang::string('buy-trading-disabled'),'code'=>'TRADING_SUSPENDED'));
		}
		
		$this_user_id = preg_replace("/[^0-9]/", "",$this_user_id);
		$this_user_id = ($this_user_id > 0) ? $this_user_id : User::$info['id'];
		if (!($this_user_id > 0)) {
			db_commit();
			return array('error'=>array('message'=>'Invalid authentication.','code'=>'AUTH_ERROR'));
		}
		
		$amount = preg_replace("/[^0-9\.]/", "",$amount);
		$orig_amount = $amount;
		$price = preg_replace("/[^0-9\.]/", "",$price);
		$stop_price = preg_replace("/[^0-9\.]/", "",$stop_price);
		$currency1 = strtolower(preg_replace("/[^a-zA-Z]/", "",$currency1));
		$edit_id = preg_replace("/[^0-9]/", "",$edit_id);
		
		db_start_transaction();
		
		$orig_order = false;
		if ($edit_id > 0) {
			if (empty($CFG->session_api) || $external_transaction)
				$orig_order = DB::getRecord('orders',$edit_id,0,1,false,false,false,1);
			else
				$orig_order = self::getRecord(false,$edit_id,$this_user_id,true);
			
			if ($orig_order['site_user'] != $this_user_id || !$orig_order) {
				db_commit();
				return array('error'=>array('message'=>'Order not found.','code'=>'ORDER_NOT_FOUND'));
			}
			
			$buy = ($orig_order['order_type'] == $CFG->order_type_bid);
			$currency_info = $CFG->currencies[$orig_order['currency']];
			$currency1 = strtolower($currency_info['currency']);
			$edit_id = $orig_order['id'];
			$use_maker_fee = ($use_maker_fee && $orig_order['market_price'] != 'Y');
			
			if ($external_transaction) {
				$amount = $orig_order['btc'];
				$orig_amount = $amount;
			}
		}
		else 
			$currency_info = $CFG->currencies[strtoupper($currency1)];
		
		$bid_ask = self::getBidAsk($currency1);
		$bid = $bid_ask['bid'];
		$ask = $bid_ask['ask'];
		$bid = ($bid > $ask) ? $ask : $bid;
		$price = ($market_price) ? (($buy) ? $ask : $bid) : $price;
		$usd_info = $CFG->currencies['USD'];
		$user_balances = User::getBalances($this_user_id,array($currency_info['id'],$CFG->btc_currency_id),true);
		$user_fee = FeeSchedule::getUserFees($this_user_id);
		$on_hold = User::getOnHold(1,$this_user_id,$user_fee,array($currency_info['id'],$CFG->btc_currency_id));
		$this_btc_balance = (!empty($user_balances['btc'])) ? $user_balances['btc'] : 0;
		$this_fiat_balance = (!empty($user_balances[$currency1])) ? $user_balances[$currency1] : 0;
		$this_triggered_stop = ($stop_price > 0 && $market_price);
		$stop_price = ($stop_price > 0 && $market_price) ? false : $stop_price;
		$fee = (!$use_maker_fee) ? $user_fee['fee'] : $user_fee['fee1'];
		
		$insert_id = 0;
		$transactions = 0;
		$new_order = 0;
		$edit_order = 0;
		$comp_btc_balance = array();
		$comp_btc_on_hold = array();
		$comp_fiat_balance = array();
		$comp_fiat_on_hold = array();
		$currency_max = false;
		$currency_max_str = false;
		$currency_min = false;
		$currency_min_str = false;
		$compatible = false;
		$trans_total = 0;
		$this_funds_finished = false;
		$hidden_executions = array();
		$max_price = 0;
		$min_price = 0;
		$executed_orders = array();
		$executed_prices = array();
		$executed_orig_prices = false;
		$no_compatible = false;
		$triggered_rows = false;
		
		if (!empty($on_hold['BTC']['total']))
			$this_btc_on_hold = ($edit_id > 0 && !$buy) ? $on_hold['BTC']['total'] - $amount : $on_hold['BTC']['total'];
		else
			$this_btc_on_hold = 0;
		
		if (!empty($on_hold[strtoupper($currency1)]['total']))
			$this_fiat_on_hold = ($edit_id > 0 && $buy) ? $on_hold[strtoupper($currency1)]['total'] - (($amount * $orig_order['btc_price']) + (($amount * $orig_order['btc_price']) * ($fee * 0.01))) : $on_hold[strtoupper($currency1)]['total'];
		else 
			$this_fiat_on_hold = 0;
			
		
		$error = self::checkPreconditions($buy,$currency_info,$amount,$price,$stop_price,$fee,($buy ? $this_fiat_balance - $this_fiat_on_hold : $this_btc_balance - $this_btc_on_hold),$bid,$ask,$market_price,$this_user_id,$orig_order);
		if ($error) {
			db_commit();
			return $error;
		}
		
		if (!$market_price) {
			$error = self::checkUserOrders($buy,$currency_info,$this_user_id,$price,$stop_price,$fee);
			if ($error) {
				db_commit();
				return $error;
			}
		}
		
		if (!($edit_id > 0))
			$order_log_id = db_insert('order_log',array('date'=>date('Y-m-d H:i:s'),'order_type'=>(($buy) ? $CFG->order_type_bid : $CFG->order_type_ask),'site_user'=>$this_user_id,'btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'stop_price'=>$stop_price,'status'=>'ACTIVE'));
		else {
			if (!$external_transaction || $this_triggered_stop) {
				$order_log_id = db_insert('order_log',array('date'=>date('Y-m-d H:i:s'),'order_type'=>(($buy) ? $CFG->order_type_bid : $CFG->order_type_ask),'site_user'=>$this_user_id,'btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>$price,'market_price'=>(($market_price) ? 'Y' : 'N'),'p_id'=>$orig_order['log_id'],'stop_price'=>$stop_price,'status'=>'ACTIVE'));
				db_update('order_log',$orig_order['log_id'],array('status'=>'REPLACED','btc_remaining'=>$orig_order['btc']));
			}
			else
				$order_log_id = $orig_order['log_id'];
		}
	
		if ($buy) {			
			if ($price != $stop_price) {
				$compatible = self::getCompatible($CFG->order_type_ask,$price,$currency1,$amount,1,$market_price,false,$use_maker_fee,$this_user_id);
				$no_compatible = (!$compatible);
				$compatible = (is_array($compatible)) ? new ArrayIterator($compatible) : false;
				$compatible[] = array('continue'=>1);
				//$btc_commision = 0;
				$fiat_commision = false;
				$c = count($compatible);
				$i = 1;
			}
			
			if ($compatible) {
				foreach ($compatible as $comp_order) {
					if (!empty($comp_order['is_market']) && $comp_order['is_market'] == 'Y' && $price < $bid) {
						$hidden_executions[] = $comp_order;
						continue;
					}
					
					if (!empty($comp_order['real_market_price']) && round($comp_order['real_market_price'],2,PHP_ROUND_HALF_UP) <= $price && round($comp_order['fiat_price'],2,PHP_ROUND_HALF_UP) > $price && !$market_price) {
						$hidden_executions[] = $comp_order;
						continue;
					}
					
					if (!empty($comp_order['order_type']) && $comp_order['order_type'] == $CFG->order_type_bid) {
						if ($comp_order['is_market'] == 'Y')
							$hidden_executions[] = $comp_order;
						
						continue;
					}
					
					if (!($amount > 0) || !(($this_fiat_balance - $this_fiat_on_hold) > 0)) {
						$triggered = self::triggerStops($max_price,$min_price,$currency1,1,$bid,$ask,$currency_max,$currency_min);
						$triggered_rows = self::getMarketOrders();
						if ($triggered_rows)
							$hidden_executions = array_merge($triggered_rows,$hidden_executions);
						
						break;
					}
					elseif ($i == $c && $max_price > 0) {
						$triggered = self::triggerStops($max_price,$min_price,$currency1,1,$bid,$ask,$currency_max,$currency_min);
						if ($triggered > 0) {
							$triggered_rows = self::getCompatible($CFG->order_type_ask,$max_price,$currency1,$amount,1,$market_price,$executed_orders,false,false,true);
							if ($triggered_rows) {
								foreach ($triggered_rows as $triggered_row) {
									$compatible->append($triggered_row);
								}
							}
						}
					}
					
					if (!empty($comp_order['continue']) || $comp_order['site_user'] == $this_user_id) {
						$i++;
						continue;
					}
					
					$comp_user_info = self::lockOrder($comp_order['site_user'],$comp_order['id'],$comp_order['currency_id']);
					if (!$comp_user_info)
						continue;
					
					$comp_order = array_merge($comp_order,$comp_user_info);
					$comp_order['btc_balance'] = (array_key_exists($comp_order['site_user'],$comp_btc_balance)) ? $comp_btc_balance[$comp_order['site_user']] : $comp_order['btc_balance'];
					$comp_order['fiat_balance'] = (array_key_exists($comp_order['site_user'],$comp_fiat_balance)) ? $comp_fiat_balance[$comp_order['site_user']] : $comp_order['fiat_balance'];
					$comp_btc_on_hold[$comp_order['site_user']] = (array_key_exists($comp_order['site_user'],$comp_btc_on_hold)) ? $comp_btc_on_hold[$comp_order['site_user']] : $comp_order['btc_on_hold'];
					$max_amount = ((($this_fiat_balance - $this_fiat_on_hold) / $comp_order['fiat_price']) > ($amount + (($fee * 0.01) * $amount))) ? $amount : (($this_fiat_balance - $this_fiat_on_hold) / $comp_order['fiat_price']) - (($fee * 0.01) * (($this_fiat_balance - $this_fiat_on_hold) / $comp_order['fiat_price']));
					$max_comp_amount = (($comp_order['btc_balance'] - ($comp_btc_on_hold[$comp_order['site_user']] - $comp_order['btc_outstanding'])) > $comp_order['btc_outstanding']) ? $comp_order['btc_outstanding'] : $comp_order['btc_balance'] - ($comp_btc_on_hold[$comp_order['site_user']] - $comp_order['btc_outstanding']);
					$this_funds_finished = ($max_amount < $amount);
					$comp_funds_finished = ($max_comp_amount < $comp_order['btc_outstanding']);
					
					if (!($max_amount > 0) || !($max_comp_amount > 0)) {
						if ($comp_funds_finished)
							self::cancelOrder($comp_order['id'],0,$comp_order['site_user']);
						
						$i++;
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
					$comp_order_fee = ($comp_order['fee1'] * 0.01) * $trans_amount;
					$this_conversion_fee = ($currency_info['id'] != $comp_order['currency_id']) ? ($comp_order['fiat_price'] * $trans_amount) - ($comp_order['orig_btc_price'] * $comp_order['orig_conversion_factor'] * $trans_amount) :  0;
					$this_trans_amount_net = $trans_amount + $this_fee;
					$comp_order_trans_amount_net = $trans_amount - $comp_order_fee;
					$comp_btc_balance[$comp_order['site_user']] = $comp_order['btc_balance'] - $trans_amount;
					$comp_fiat_balance[$comp_order['site_user']] = round($comp_order['fiat_balance'] + ($comp_order['orig_btc_price'] * $comp_order_trans_amount_net),2,PHP_ROUND_HALF_UP);
					$comp_btc_on_hold[$comp_order['site_user']] = $comp_btc_on_hold[$comp_order['site_user']] - $trans_amount;
					//$btc_commision += $this_fee;
					
					if (!empty($fiat_commision[strtolower($currency_info['currency'])]))
						$fiat_commision[strtolower($currency_info['currency'])] += $this_fee * $comp_order['fiat_price'];
					else
						$fiat_commision[strtolower($currency_info['currency'])] = $this_fee * $comp_order['fiat_price'];
					
					if (!empty($fiat_commision[strtolower($comp_order['currency_abbr'])]))
						$fiat_commision[strtolower($comp_order['currency_abbr'])] += $comp_order_fee * $comp_order['orig_btc_price'];
					else
						$fiat_commision[strtolower($comp_order['currency_abbr'])] = $comp_order_fee * $comp_order['orig_btc_price'];
					
					$this_prev_btc = $this_btc_balance;
					$this_prev_fiat = $this_fiat_balance;
					$this_btc_balance += $trans_amount;
					$this_fiat_balance -= round($this_trans_amount_net * $comp_order['fiat_price'],2,PHP_ROUND_HALF_UP);
					$trans_total += $trans_amount;
					$max_price = ($comp_order['fiat_price'] > $max_price) ? $comp_order['fiat_price'] : $max_price;
					$min_price = ($comp_order['fiat_price'] < $min_price || !($min_price > 0)) ? $comp_order['fiat_price'] : $min_price;
					
					if ($currency_info['id'] != $comp_order['currency_id']) {
						$currency_max[$comp_order['currency_id']] = ($comp_order['orig_btc_price'] > $currency_max[$comp_order['currency_id']]) ? $comp_order['orig_btc_price'] : $currency_max[$comp_order['currency_id']];
						$currency_min[$comp_order['currency_id']] = ($comp_order['orig_btc_price'] < $currency_min[$comp_order['currency_id']] || !($currency_min[$comp_order['currency_id']] > 0)) ? $comp_order['orig_btc_price'] : $currency_min[$comp_order['currency_id']];
					}
					
					$trans_info = array('date'=>date('Y-m-d H:i:s'),'site_user'=>$this_user_id,'transaction_type'=>$CFG->transactions_buy_id,'site_user1'=>$comp_order['site_user'],'transaction_type1'=>$CFG->transactions_sell_id,'btc'=>$trans_amount,'btc_price'=>$comp_order['fiat_price'],'fiat'=>($comp_order['fiat_price'] * $trans_amount),'currency'=>$currency_info['id'],'currency1'=>$comp_order['currency_id'],'fee'=>$this_fee,'fee1'=>$comp_order_fee,'btc_net'=>$this_trans_amount_net,'btc_net1'=>$comp_order_trans_amount_net,'btc_before'=>$this_prev_btc,'btc_after'=>$this_btc_balance,'fiat_before'=>$this_prev_fiat,'fiat_after'=>$this_fiat_balance,'btc_before1'=>$comp_order['btc_balance'],'btc_after1'=>$comp_btc_balance[$comp_order['site_user']],'fiat_before1'=>$comp_order['fiat_balance'],'fiat_after1'=>$comp_fiat_balance[$comp_order['site_user']],'log_id'=>$order_log_id,'log_id1'=>$comp_order['log_id'],'fee_level'=>$fee,'fee_level1'=>$comp_order['fee'],'conversion_fee'=>$this_conversion_fee,'orig_btc_price'=>$comp_order['orig_btc_price'],'bid_at_transaction'=>$bid,'ask_at_transaction'=>$ask);
					if ($currency_info['id'] != $comp_order['currency_id'])
						$trans_info = array_merge($trans_info,array('conversion'=>'Y','convert_amount'=>($comp_order['fiat_price'] * $trans_amount),'convert_rate_given'=>$comp_order['conversion_factor'],'convert_system_rate'=>$comp_order['orig_conversion_factor'],'convert_from_currency'=>$currency_info['id'],'convert_to_currency'=>$comp_order['currency_id']));
					
					$transaction_id = db_insert('transactions',$trans_info);
					$executed_orders[] = $comp_order['id'];
					$executed_prices[] = array('price'=>$comp_order['fiat_price'],'amount'=>$trans_amount);
					$executed_orig_prices[$comp_order['id']] = array('price'=>$comp_order['orig_btc_price'],'amount'=>$trans_amount);
					++$transactions;
					
					if (round($comp_order_outstanding,8,PHP_ROUND_HALF_UP) > 0) {
						if (!$comp_funds_finished) {
							db_update('orders',$comp_order['id'],array('btc_price'=>$comp_order['orig_btc_price'],'btc'=>$comp_order_outstanding,'fiat'=>($comp_order['orig_btc_price'] * $comp_order_outstanding)));
							
							if ($comp_order['is_market'] == 'Y')
								$hidden_executions[] = $comp_order;
						}
						else
							self::cancelOrder($comp_order['id'],$comp_order_outstanding,$comp_order['site_user']);
					}
					else {
						self::setStatus($comp_order['id'],'FILLED');
						db_delete('orders',$comp_order['id']);
					}
					
					User::updateBalances($comp_order['site_user'],array('btc'=>$comp_btc_balance[$comp_order['site_user']],$comp_order['currency_abbr']=>$comp_fiat_balance[$comp_order['site_user']]));
					$i++;
				}
			}
	
			if ($trans_total > 0) {
				User::updateBalances($this_user_id,array('btc'=>$this_btc_balance,$currency1=>$this_fiat_balance));
				if ($fiat_commision)
					Status::updateEscrows($fiat_commision);
				//db_update('status',1,array('btc_escrow'=>($status['btc_escrow']+$btc_commision),strtolower($currency_info['currency']).'_escrow'=>($status[strtolower($currency_info['currency']).'_escrow']+$fiat_commision)));
			}

			if (round($amount,8,PHP_ROUND_HALF_UP) > 0) {
				if ($edit_id > 0) {
					if (!$this_funds_finished) {
						if (!($no_compatible && $external_transaction)) {
							db_update('orders',$edit_id,array('btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>(($price != $stop_price) ? $price : 0),'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id,'stop_price'=>$stop_price));
							$edit_order = 1;
						}
						$order_status = 'ACTIVE';
					}
					else {
						self::cancelOrder($edit_id,$amount,$this_user_id);
						$order_status = 'OUT_OF_FUNDS';
					}
				}
				else {
					if (!$this_funds_finished) {
						db_insert('orders',array('date'=>date('Y-m-d H:i:s'),'order_type'=>$CFG->order_type_bid,'site_user'=>$this_user_id,'btc'=>$amount,'fiat'=>$amount*$price,'currency'=>$currency_info['id'],'btc_price'=>(($price != $stop_price) ? (($market_price && $max_price > 0) ? $max_price : $price) : 0),'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id,'stop_price'=>$stop_price));
						$new_order = ($stop_price != $price && $stop_price > 0) ? 2 : 1;
						$order_status = 'ACTIVE';
					}
					else {
						self::cancelOrder(false,$amount,$this_user_id);
						$order_status = 'OUT_OF_FUNDS';
					}
				}
			}
			elseif ($edit_id > 0) {
				self::setStatus($edit_id,'FILLED');
				db_delete('orders',$edit_id);
				$order_status = 'FILLED';
			}
			else {
				self::setStatus(false,'FILLED',$order_log_id);
				$order_status = 'FILLED';
			}
			
			db_insert('history',array('date'=>date('Y-m-d H:i:s'),'ip'=>(!empty($CFG->client_ip) ? $CFG->client_ip : ''),'history_action'=>$CFG->history_buy_id,'site_user'=>$this_user_id,'order_id'=>$order_log_id));
		}
		else {
			if ($price != $stop_price) {
				$compatible = self::getCompatible($CFG->order_type_bid,$price,$currency1,$amount,1,$market_price,false,$use_maker_fee,$this_user_id);
				$no_compatible = (!$compatible);
				$compatible = (is_array($compatible)) ? new ArrayIterator($compatible) : false;
				$compatible[] = array('continue'=>1);
				//$btc_commision = 0;
				$fiat_commision = false;
				$c = count($compatible);
				$i = 1;
			}
			
			if ($compatible) {
				foreach ($compatible as $comp_order) {
					if (!empty($comp_order['is_market']) && $comp_order['is_market'] == 'Y' && $price > $ask) {
						$hidden_executions[] = $comp_order;
						continue;
					}
										
					if (!empty($comp_order['real_market_price']) && round($comp_order['real_market_price'],2,PHP_ROUND_HALF_UP) >= $price && round($comp_order['fiat_price'],2,PHP_ROUND_HALF_UP) < $price && !$market_price) {
						$hidden_executions[] = $comp_order;
						continue;
					}
					
					if (!empty($comp_order['order_type']) && $comp_order['order_type'] == $CFG->order_type_ask) {
						if ($comp_order['is_market'] == 'Y')
							$hidden_executions[] = $comp_order;
							
						continue;
					}
					
					if (!($amount > 0) || !(($this_btc_balance - $this_btc_on_hold) > 0)) {
						$triggered = self::triggerStops($max_price,$min_price,$currency1,false,$bid,$ask,$currency_max,$currency_min);
						$triggered_rows = self::getMarketOrders();
						if ($triggered_rows)
							$hidden_executions = array_merge($triggered_rows,$hidden_executions);
						
						break;
					}
					elseif ($i == $c && $min_price > 0) {
						$triggered = self::triggerStops($max_price,$min_price,$currency1,false,$bid,$ask,$currency_max,$currency_min);
						if ($triggered > 0) {
							$triggered_rows = self::getCompatible($CFG->order_type_bid,$min_price,$currency1,$amount,1,$market_price,$executed_orders,false,false,true);
							if ($triggered_rows) {
								foreach ($triggered_rows as $triggered_row) {
									$compatible->append($triggered_row);
								}
							}
						}
					}
					
					if (!empty($comp_order['continue']) || $comp_order['site_user'] == $this_user_id) {
						$i++;
						continue;
					}
					
					$comp_user_info = self::lockOrder($comp_order['site_user'],$comp_order['id'],$comp_order['currency_id']);
					if (!$comp_user_info)
						continue;
						
					$comp_order = array_merge($comp_order,$comp_user_info);
					$comp_order['btc_balance'] = (array_key_exists($comp_order['site_user'],$comp_btc_balance)) ? $comp_btc_balance[$comp_order['site_user']] : $comp_order['btc_balance'];
					$comp_order['fiat_balance'] = (array_key_exists($comp_order['site_user'],$comp_fiat_balance)) ? $comp_fiat_balance[$comp_order['site_user']] : $comp_order['fiat_balance'];
					$comp_fiat_on_hold[$comp_order['site_user']] = (array_key_exists($comp_order['site_user'],$comp_fiat_on_hold)) ? $comp_fiat_on_hold[$comp_order['site_user']] : round($comp_order['fiat_on_hold'],2,PHP_ROUND_HALF_UP);											
					$comp_fiat_this_on_hold = $comp_fiat_on_hold[$comp_order['site_user']] - (($comp_order['btc_outstanding'] * $comp_order['orig_btc_price']) + (($comp_order['fee1'] * 0.01) * ($comp_order['btc_outstanding'] * $comp_order['orig_btc_price'])));
					$max_amount = (($this_btc_balance - $this_btc_on_hold) > $amount) ? $amount : $this_btc_balance - $this_btc_on_hold;
					$max_comp_amount = ((($comp_order['fiat_balance'] - $comp_fiat_this_on_hold) / $comp_order['orig_btc_price']) > ($comp_order['btc_outstanding'] + (($comp_order['fee1'] * 0.01) * $comp_order['btc_outstanding']))) ? $comp_order['btc_outstanding'] : (($comp_order['fiat_balance'] - $comp_fiat_this_on_hold) / $comp_order['orig_btc_price']) - (($comp_order['fee1'] * 0.01) * (($comp_order['fiat_balance'] - $comp_fiat_this_on_hold) / $comp_order['orig_btc_price']));
					$this_funds_finished = ($max_amount < $amount);
					$comp_funds_finished = ($max_comp_amount < $comp_order['btc_outstanding']);
					
					if (!($max_amount > 0) || !($max_comp_amount > 0)) {
						if ($comp_funds_finished)
							self::cancelOrder($comp_order['id'],0,$comp_order['site_user']);
						
						$i++;
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
					$comp_order_fee = ($comp_order['fee1'] * 0.01) * $trans_amount;
					$this_trans_amount_net = $trans_amount - $this_fee;
					$this_conversion_fee = ($currency_info['id'] != $comp_order['currency_id']) ? ($comp_order['orig_btc_price'] * $comp_order['orig_conversion_factor'] * $trans_amount) - ($comp_order['fiat_price'] * $trans_amount) :  0;
					$comp_order_trans_amount_net = $trans_amount + $comp_order_fee;
					$comp_btc_balance[$comp_order['site_user']] = $comp_order['btc_balance'] + $trans_amount;
					$comp_fiat_balance[$comp_order['site_user']] = $comp_order['fiat_balance'] - round(($comp_order['orig_btc_price'] * $comp_order_trans_amount_net),2,PHP_ROUND_HALF_UP);
					$comp_fiat_on_hold[$comp_order['site_user']] = $comp_fiat_on_hold[$comp_order['site_user']]  - round(($comp_order['orig_btc_price'] * $comp_order_trans_amount_net),2,PHP_ROUND_HALF_UP);
					//$btc_commision += $comp_order_fee;
					
					if (!empty($fiat_commision[strtolower($currency_info['currency'])]))
						$fiat_commision[strtolower($currency_info['currency'])] += $this_fee * $comp_order['fiat_price'];
					else
						$fiat_commision[strtolower($currency_info['currency'])] = $this_fee * $comp_order['fiat_price'];
						
					if (!empty($fiat_commision[strtolower($comp_order['currency_abbr'])]))
						$fiat_commision[strtolower($comp_order['currency_abbr'])] += $comp_order_fee * $comp_order['orig_btc_price'];
					else
						$fiat_commision[strtolower($comp_order['currency_abbr'])] = $comp_order_fee * $comp_order['orig_btc_price'];
						
					
					$this_prev_btc = $this_btc_balance;
					$this_prev_fiat = $this_fiat_balance;
					$this_btc_balance -= $trans_amount;
					$this_fiat_balance += round($this_trans_amount_net * $comp_order['fiat_price'],2,PHP_ROUND_HALF_UP);
					$trans_total += $trans_amount;
					$max_price = ($comp_order['fiat_price'] > $max_price) ? $comp_order['fiat_price'] : $max_price;
					$min_price = ($comp_order['fiat_price'] < $min_price || !($min_price > 0)) ? $comp_order['fiat_price'] : $min_price;
					
					if ($currency_info['id'] != $comp_order['currency_id']) {
						$currency_max[$comp_order['currency_id']] = ($comp_order['orig_btc_price'] > $currency_max[$comp_order['currency_id']]) ? $comp_order['orig_btc_price'] : $currency_max[$comp_order['currency_id']];
						$currency_min[$comp_order['currency_id']] = ($comp_order['orig_btc_price'] < $currency_min[$comp_order['currency_id']] || !($currency_min[$comp_order['currency_id']] > 0)) ? $comp_order['orig_btc_price'] : $currency_min[$comp_order['currency_id']];
					}
					
					$trans_info = array('date'=>date('Y-m-d H:i:s'),'site_user'=>$this_user_id,'transaction_type'=>$CFG->transactions_sell_id,'site_user1'=>$comp_order['site_user'],'transaction_type1'=>$CFG->transactions_buy_id,'btc'=>$trans_amount,'btc_price'=>$comp_order['fiat_price'],'fiat'=>($comp_order['fiat_price'] * $trans_amount),'currency'=>$currency_info['id'],'currency1'=>$comp_order['currency_id'],'fee'=>$this_fee,'fee1'=>$comp_order_fee,'btc_net'=>$this_trans_amount_net,'btc_net1'=>$comp_order_trans_amount_net,'btc_before'=>$this_prev_btc,'btc_after'=>$this_btc_balance,'fiat_before'=>$this_prev_fiat,'fiat_after'=>$this_fiat_balance,'btc_before1'=>$comp_order['btc_balance'],'btc_after1'=>$comp_btc_balance[$comp_order['site_user']],'fiat_before1'=>$comp_order['fiat_balance'],'fiat_after1'=>$comp_fiat_balance[$comp_order['site_user']],'log_id'=>$order_log_id,'log_id1'=>$comp_order['log_id'],'fee_level'=>$fee,'fee_level1'=>$comp_order['fee'],'conversion_fee'=>$this_conversion_fee,'orig_btc_price'=>$comp_order['orig_btc_price'],'bid_at_transaction'=>$bid,'ask_at_transaction'=>$ask);
					if ($currency_info['id'] != $comp_order['currency_id'])
						$trans_info = array_merge($trans_info,array('conversion'=>'Y','convert_amount'=>($comp_order['orig_btc_price'] * $trans_amount),'convert_rate_given'=>$comp_order['conversion_factor'],'convert_system_rate'=>$comp_order['orig_conversion_factor'],'convert_from_currency'=>$comp_order['currency_id'],'convert_to_currency'=>$currency_info['id']));
						
					$transaction_id = db_insert('transactions',$trans_info);
					$executed_orders[] = $comp_order['id'];
					$executed_prices[] = array('price'=>$comp_order['fiat_price'],'amount'=>$trans_amount);
					$executed_orig_prices[$comp_order['id']] = array('price'=>$comp_order['orig_btc_price'],'amount'=>$trans_amount);
					++$transactions;
					
					if ($currency_info['id'] != $comp_order['currency_id'])
						db_update('transactions',$transaction_id,array('conversion'=>'Y','convert_amount'=>($comp_order['orig_btc_price'] * $trans_amount),'convert_rate_given'=>$comp_order['conversion_factor'],'convert_system_rate'=>$comp_order['orig_conversion_factor'],'convert_from_currency'=>$comp_order['currency_id'],'convert_to_currency'=>$currency_info['id']));
						
					if (round($comp_order_outstanding,8,PHP_ROUND_HALF_UP) > 0) {
						if (!$comp_funds_finished) {
							db_update('orders',$comp_order['id'],array('btc_price'=>$comp_order['orig_btc_price'],'btc'=>$comp_order_outstanding,'fiat'=>($comp_order['orig_btc_price'] * $comp_order_outstanding)));
							
							if ($comp_order['is_market'] == 'Y')
								$hidden_executions[] = $comp_order;
						}
						else
							self::cancelOrder($comp_order['id'],$comp_order_outstanding,$comp_order['site_user']);
					}
					else {
						self::setStatus($comp_order['id'],'FILLED');
						db_delete('orders',$comp_order['id']);
					}
	
					User::updateBalances($comp_order['site_user'],array('btc'=>$comp_btc_balance[$comp_order['site_user']],$comp_order['currency_abbr']=>$comp_fiat_balance[$comp_order['site_user']]));
					$i++;
				}
			}
	
			if ($trans_total > 0) {
				User::updateBalances($this_user_id,array('btc'=>$this_btc_balance,$currency1=>$this_fiat_balance));
				if ($fiat_commision)
					Status::updateEscrows($fiat_commision);
			}
			
			if (round($amount,8,PHP_ROUND_HALF_UP) > 0) {
				if ($edit_id > 0) {
					if (!$this_funds_finished) {
						if (!($no_compatible && $external_transaction)) {
							db_update('orders',$edit_id,array('btc'=>$amount,'fiat'=>($amount*$price),'btc_price'=>(($price != $stop_price) ? $price : 0),'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id,'stop_price'=>$stop_price));
							$edit_order = 1;
						}
						$order_status = 'ACTIVE';
					}
					else {
						self::cancelOrder($edit_id,$amount,$this_user_id);
						$order_status = 'OUT_OF_FUNDS';
					}
				}
				else {
					if (!$this_funds_finished) {
						$insert_id = db_insert('orders',array('date'=>date('Y-m-d H:i:s'),'order_type'=>$CFG->order_type_ask,'site_user'=>$this_user_id,'btc'=>$amount,'fiat'=>($amount*$price),'currency'=>$currency_info['id'],'btc_price'=>(($price != $stop_price) ? (($market_price  && $min_price > 0) ? $min_price : $price) : 0),'market_price'=>(($market_price) ? 'Y' : 'N'),'log_id'=>$order_log_id,'stop_price'=>$stop_price));
						$new_order = ($stop_price != $price && $stop_price > 0) ? 2 : 1;
						$order_status = 'ACTIVE';
					}
					else {
						self::cancelOrder(false,$amount,$this_user_id);
						$order_status = 'OUT_OF_FUNDS';
					}
				}
			}
			elseif ($edit_id > 0) {
				self::setStatus($edit_id,'FILLED');
				db_delete('orders',$edit_id);
				$order_status = 'FILLED';
			}
			else {
				self::setStatus(false,'FILLED',$order_log_id);
				$order_status = 'FILLED';
			}
			
			db_insert('history',array('date'=>date('Y-m-d H:i:s'),'ip'=>(!empty($CFG->client_ip) ? $CFG->client_ip : ''),'history_action'=>$CFG->history_sell_id,'site_user'=>$this_user_id,'order_id'=>$order_log_id));
		}
		
		db_commit();
		
		if ($max_price > 0 && $currency1 == 'usd')
			db_update('currencies',$CFG->btc_currency_id,array('usd_ask'=>$max_price));
		if ($min_price > 0 && $currency1 == 'usd')
			db_update('currencies',$CFG->btc_currency_id,array('usd_bid'=>$min_price));
		
		if ($hidden_executions && !$external_transaction) {
			foreach ($hidden_executions as $comp_order) {
				if ($triggered_rows && $comp_order['is_market'] != 'Y')
					continue; 
				
				$return = self::executeOrder(($comp_order['order_type'] == $CFG->order_type_bid),$comp_order['orig_btc_price'],$comp_order['btc_outstanding'],strtolower($comp_order['currency_abbr']),false,($comp_order['is_market'] == 'Y'),$comp_order['id'],$comp_order['site_user'],true,$comp_order['stop_price'],true,true);
				if (!empty($return['order_info']['comp_orig_prices'][($edit_id ? $edit_id : $insert_id)])) {
					$executed_prices[] = $return['order_info']['comp_orig_prices'][($edit_id ? $edit_id : $insert_id)];
					++$transactions;
				}
			}
			
			if ($verbose) {
				$reevaluated_order = DB::getRecord('orders',($edit_id ? $edit_id : $insert_id),0,1);
				if (!$reevaluated_order)
					$order_status = 'FILLED';
				else 
					$amount = $reevaluated_order['btc'];
			}
		}
		
		$order_info = false;
		if ($verbose) {
			if ($executed_prices) {
				foreach ($executed_prices as $exec) {
					$exec_amount[] = $exec['amount'];
				}
				$exec_amount_sum = array_sum($exec_amount);
				foreach ($executed_prices as $exec) {
					$avg_exec[] = ($exec['amount'] / $exec_amount_sum) * $exec['price'];
				}
			}

			$order_info = array('id'=>$order_log_id,'side'=>($buy ? 'buy' : 'sell'),'type'=>(($market_price) ? 'market' : (($stop_price > 0) ? 'stop' : 'limit')),'amount'=>$orig_amount,'amount_remaining'=>$amount,'price'=>round($price,8,PHP_ROUND_HALF_UP),'avg_price_executed'=>((count($executed_prices) > 0) ? round(array_sum($avg_exec),2,PHP_ROUND_HALF_UP) : 0),'stop_price'=>$stop_price,'currency'=>strtoupper($currency1),'status'=>$order_status,'replaced'=>($edit_id ? $orig_order['log_id'] : 0),'comp_orig_prices'=>$executed_orig_prices);
		}
		
		return array('transactions'=>$transactions,'new_order'=>$new_order,'edit_order'=>$edit_order,'executed'=>$executed_orders,'order_info'=>$order_info);
	}
	
	public static function unsetCache() {
		global $CFG;
		
		$cached = $CFG->m->getMulti(array('trans_cache','orders_cache'));
		$delete_keys = array();
		
		if ($CFG->currencies) {
			foreach ($CFG->currencies as $key => $currency) {
				if (is_numeric($key) || $currency['currency'] == 'BTC')
					continue;
				
				$delete_keys[] = 'bid_ask_'.$key;
				$delete_keys[] = 'stats_'.$key;
				$delete_keys[] = 'trans_l5_'.$key;
				$delete_keys[] = 'trans_l1_'.$key;
			}
		}
		
		if (array_key_exists('trans_cache',$cached)) {
			$delete_keys[] = 'trans_cache';
			foreach ($cached['trans_cache'] as $key => $n) {
				$delete_keys[] = 'trans_api'.$key;
			}
		}
		
		if (array_key_exists('orders_cache',$cached)) {
			$delete_keys[] = 'orders_cache';
			foreach ($cached['orders_cache'] as $key => $n) {
				$delete_keys[] = 'orders'.$key;
			}
		}
		
		$CFG->m->deleteMulti($delete_keys);
	}
	
	private static function cancelOrder($order_id=false,$outstanding_btc=false,$site_user=false) {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		$user_info = ($site_user > 0) ? DB::getRecord('site_users',$site_user,0,1) : User::$info;
		$user_info['amount'] = number_format($outstanding_btc,8);
		$user_info['exchange_name'] = $CFG->exchange_name;
		$CFG->language = $user_info['last_lang'];
		self::setStatus($order_id,'OUT_OF_FUNDS',false,$user_info['amount']);
		db_delete('orders',$order_id);
		
		$email = SiteEmail::getRecord('order-cancelled');
		Email::send($CFG->form_email,$user_info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$user_info);
	}
	
	private static function setStatus($order_id,$status,$order_log_id=false,$btc_remaining=false) {
		global $CFG;
		
		if (!($order_id > 0) && !($order_log_id > 0))
			return false;
		
		$sql = "UPDATE order_log LEFT JOIN orders ON (order_log.id = orders.log_id) SET order_log.status = '$status' ".(($btc_remaining > 0) ? ", order_log.btc_remaining = $btc_remaining" : '')." WHERE ".(($order_log_id > 0) ? "order_log.id = $order_log_id " : "orders.id = $order_id ");
		db_query($sql);
	}
	
	public static function getStatus($order_log_id=false,$user=false) {
		global $CFG;
		
		if ($user && !$CFG->session_active)
			return false;
		
		$user_id = false;
		if ($user)
			$user_id = User::$info['id'];
		
		if (!($order_log_id > 0) && !($user_id > 0))
			return false;
		
		$currency_abbr = '(CASE order_log.currency';
		foreach ($CFG->currencies as $curr_id => $currency1) {
			if (is_numeric($curr_id) || $currency1['currency'] == 'BTC')
				continue;
		
			$currency_abbr .= ' WHEN '.$currency1['id'].' THEN "'.$currency1['currency'].'" ';
		}
		$currency_abbr .= ' END)';
		
		$sql = "SELECT order_log.id AS id, IF(order_log.order_type = {$CFG->order_type_bid},'buy','sell') AS side, IF(order_log.market_price = 'Y','market',IF(order_log.stop_price > 0,'stop','limit')) AS `type`, order_log.btc AS amount, IF(order_log.status = 'ACTIVE',orders.btc,order_log.btc_remaining) AS amount_remaining, order_log.btc_price AS price, ROUND(SUM(IF(transactions.id IS NOT NULL OR transactions1.id IS NOT NULL,(IF(transactions.id IS NOT NULL,transactions.btc,transactions1.btc)  / (order_log.btc - IF(order_log.status = 'ACTIVE',orders.btc,order_log.btc_remaining))) * IF(transactions.id IS NOT NULL,transactions.btc_price,transactions1.orig_btc_price),0)),2) AS avg_price_executed, order_log.stop_price AS stop_price, $currency_abbr AS currency, order_log.status AS status, order_log.p_id AS replaced, IF(order_log.status = 'REPLACED',replacing_order.id,0) AS replaced_by
		FROM order_log 
		LEFT JOIN orders ON (order_log.id = orders.log_id) 
		LEFT JOIN transactions ON (order_log.id = transactions.log_id)
		LEFT JOIN transactions transactions1 ON (order_log.id = transactions1.log_id1)
		LEFT JOIN order_log replacing_order ON (order_log.id = replacing_order.p_id)
		WHERE 1 ";
		
		if ($order_log_id > 0)
			$sql .= " AND order_log.id = $order_log_id ";
		
		if ($user_id > 0)
			$sql .= " AND orders.site_user = $user_id ";
		else
			$sql .= " AND order_log.site_user = ".User::$info['id'].' ';
		
		$sql .= "GROUP BY order_log.id";
		$result = db_query_array($sql);
		
		if ($order_log_id)
			return $result[0];
		else
			return $result;
	}
	
	public static function delete($id=false,$order_log_id=false) {
		global $CFG;
		
		$id = preg_replace("/[^0-9]/", "",$id);
		$order_log_id = preg_replace("/[^0-9]/", "",$order_log_id);
		
		if (!($id > 0))
			$id = $order_log_id;
		
		if (!($id > 0))
			return false;
		
		if (!$CFG->session_active)
			return false;
		
		if (!$order_log_id)
			$del_order = DB::getRecord('orders',$id,0,1);
		else
			$del_order = self::getRecord(false,$order_log_id);
		
		if (!$del_order)
			return array('error'=>array('message'=>'Order not found.','code'=>'ORDER_NOT_FOUND'));
		
		if ($del_order['site_user'] != User::$info['id'])
			return array('error'=>array('message'=>'User mismatch.','code'=>'AUTH_NOT_AUTHORIZED'));
		
		self::setStatus(false,'CANCELLED_USER',$del_order['log_id'],$del_order['btc']);
		db_delete('orders',$del_order['id']);
		
		return self::getStatus($del_order['log_id']);
	}
	
	public static function deleteAll() {
		global $CFG;
		
		if (!$CFG->session_active)
			return false;
		
		db_start_transaction();
		$sql = 'SELECT log_id FROM orders WHERE site_user = '.User::$info['id'].' FOR UPDATE';
		$result = db_query_array($sql);
		
		if (!$result) {
			db_commit();
			return false;
		}
		
		$orders_info = self::getStatus(false,1);
		if (!$orders_info) {
			db_commit();
			return false;
		}
		
		$sql = 'UPDATE order_log LEFT JOIN orders ON (order_log.id = orders.log_id) SET status = "CANCELLED_USER" WHERE orders.site_user = '.User::$info['id'];
		$updated = db_query($sql);
		
		if ($updated) {
			$sql = 'DELETE FROM orders WHERE site_user = '.User::$info['id'];
			db_query($sql);
			
			foreach ($orders_info as $i => $order) {
				$orders_info[$i]['status'] = 'CANCELLED_USER';
			}
		}
		db_commit();
		return $orders_info;
	}
}
