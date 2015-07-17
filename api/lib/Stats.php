<?php
class Stats {
	public static function getHistorical($timeframe='1year',$currency='usd',$public_api=false) {
		global $CFG;
		
		$currency = preg_replace("/[^a-zA-Z]/", "",$currency);
		
		if ($timeframe == '1mon')
			$start = date('Y-m-d',strtotime('-1 month'));
		elseif ($timeframe == '3mon')
			$start = date('Y-m-d',strtotime('-3 month'));
		elseif ($timeframe == '6mon')
			$start = date('Y-m-d',strtotime('-6 month'));
		elseif ($timeframe == 'ytd')
			$start = date('Y').'-01-01';
		elseif ($timeframe == '1year')
			$start = date('Y-m-d',strtotime('-1 year'));
		
		if ($CFG->memcached) {
			$cached = $CFG->m->get('historical_'.$currency.'_'.$timeframe.(($public_api) ? '_api' : ''));
			if ($cached) {
				return $cached;
			}
		}
		
		$currency_info = $CFG->currencies[strtoupper($currency)];
		$usd_ask = $currency_info['usd_ask'];
		if (!$usd_ask)
			return false;
		
		$sql = "SELECT ".((!$public_api) ? "(UNIX_TIMESTAMP(DATE(`date`)) * 1000) AS" : '')." `date`,ROUND((usd/$usd_ask),2) AS price FROM historical_data WHERE `date` >= '$start' GROUP BY `date` ORDER BY `date` ASC";
		$result = db_query_array($sql);
		if ($CFG->memcached)
			$CFG->m->set('historical_'.$currency.'_'.$timeframe.(($public_api) ? '_api' : ''),$result,3600);
		
		return $result;
	}
	
	public static function getCurrent($currency_id,$currency_abbr=false) {
		global $CFG;
		
		$usd_info = $CFG->currencies['USD'];
		$usd_field = 'usd_ask';
		$currency_id = ($currency_id > 0) ? preg_replace("/[^0-9]/", "",$currency_id) : $usd_info['id'];
		$currency_abbr = preg_replace("/[^a-zA-Z]/", "",$currency_abbr);

		if ($currency_abbr) {
			$currency_info = $CFG->currencies[strtoupper($currency_abbr)];
			$currency_id = $currency_info['id'];
		}
		elseif ($currency_id > 0) {
			$currency_info = $CFG->currencies[$currency_id];
		}
		
		if ($CFG->memcached) {
			$cached = $CFG->m->get('stats_'.$currency_info['currency']);
			if ($cached) {
				return $cached;
			}
		}
		
		$bid_ask = Orders::getBidAsk(false,$currency_id);
		$bid = $bid_ask['bid'];
		$ask = $bid_ask['ask'];
		
		$price_str = '(CASE WHEN transactions.currency = '.$currency_info['id'].' THEN transactions.btc_price WHEN transactions.currency1 = '.$currency_info['id'].' THEN transactions.orig_btc_price ELSE (transactions.orig_btc_price * (CASE transactions.currency1 ';
		foreach ($CFG->currencies as $curr_id => $currency1) {
			if (is_numeric($curr_id) || $currency1['currency'] == 'BTC')
				continue;
		
			if (!empty($currency_info) && $currency1['id'] == $currency_info['id'])
				continue;
		
			$conversion = (empty($currency_info) || $currency_info['currency'] == 'USD') ? $currency1[$usd_field] : $currency1[$usd_field] / $currency_info[$usd_field];
			$price_str .= ' WHEN '.$currency1['id'].' THEN '.$conversion.' ';
		}
		$price_str .= ' END)) END)';
		
		$sql = 'SELECT r2.btc_price AS btc_price2, r3.btc_price AS btc_price3, r2.last_transaction_type AS last_transaction_type2, r2.last_transaction_currency AS last_transaction_currency2, r3.last_transaction_currency AS last_transaction_currency3, r4.total_btc_traded, r5.max, r5.min, current_stats.total_btc, current_stats.market_cap, current_stats.trade_volume FROM current_stats ';

		$sql_arr[] = "LEFT JOIN (SELECT IF(transactions.currency = $currency_id,transactions.btc_price,transactions.orig_btc_price) AS btc_price, IF(transactions.transaction_type = {$CFG->transactions_buy_id},'BUY','SELL') AS last_transaction_type, IF(transactions.currency != $currency_id AND transactions.currency1 != $currency_id,transactions.currency1,$currency_id) AS last_transaction_currency FROM transactions WHERE 1 ".((!$CFG->cross_currency_trades) ? "AND transactions.currency = $currency_id" : '')." ORDER BY transactions.id DESC LIMIT 0,1) AS r2 ON (1)";
		$sql_arr[] = "LEFT JOIN (SELECT IF(transactions.currency = $currency_id,transactions.btc_price,transactions.orig_btc_price) AS btc_price, IF(transactions.currency != $currency_id AND transactions.currency1 != $currency_id,transactions.currency1,$currency_id) AS last_transaction_currency FROM transactions WHERE transactions.date < DATE_SUB(DATE_ADD(NOW(), INTERVAL ".((($CFG->timezone_offset)/60)/60)." HOUR), INTERVAL 1 DAY) ".((!$CFG->cross_currency_trades) ? "AND transactions.currency = $currency_id" : '')." ORDER BY transactions.id DESC LIMIT 0,1) AS r3  ON (1)";
		$sql_arr[] = "LEFT JOIN (SELECT btc_24h AS total_btc_traded FROM status) AS r4 ON (1)";
		$sql_arr[] = "LEFT JOIN (SELECT MAX(".(($CFG->cross_currency_trades) ? "ROUND($price_str,2)" : 'transactions.btc_price').") AS `max`, MIN(".(($CFG->cross_currency_trades) ? "ROUND($price_str,2)" : 'transactions.btc_price').") AS `min` FROM transactions WHERE transactions.date >= CURDATE() ".((!$CFG->cross_currency_trades) ? "AND transactions.currency = $currency_id" : '')." LIMIT 0,1) AS r5 ON (1)";
		
		$sql .= implode(' ',$sql_arr).' WHERE current_stats.id = 1';
		$result = db_query_array($sql);
		
		if ($result[0]['btc_price2'])
			$result[0]['btc_price2'] = $result[0]['btc_price2'] * (($currency_info['currency'] == 'USD') ? $CFG->currencies[$result[0]['last_transaction_currency2']][$usd_field] : $CFG->currencies[$result[0]['last_transaction_currency2']][$usd_field] / $currency_info[$usd_field]);
		if ($result[0]['btc_price3'])
			$result[0]['btc_price3'] = $result[0]['btc_price3'] * (($currency_info['currency'] == 'USD') ? $CFG->currencies[$result[0]['last_transaction_currency3']][$usd_field] : $CFG->currencies[$result[0]['last_transaction_currency3']][$usd_field] / $currency_info[$usd_field]);
		
		$stats['bid'] = $bid;
		$stats['ask'] = $ask;
		$stats['last_price'] = ($result[0]['btc_price2']) ? $result[0]['btc_price2'] : $ask;
		$stats['last_transaction_type'] = $result[0]['last_transaction_type2'];
		$stats['last_transaction_currency'] = $result[0]['last_transaction_currency2'];
		$stats['daily_change'] = ($result[0]['btc_price3'] > 0 && $result[0]['btc_price2'] > 0) ? $result[0]['btc_price2'] - $result[0]['btc_price3'] : '0';
		$stats['daily_change_percent'] = ($stats['last_price'] > 0) ? ($stats['daily_change']/$stats['last_price']) * 100 : 0;
		$stats['max'] = ($result[0]['max'] > 0) ? $result[0]['max'] : $result[0]['btc_price2'];
		$stats['min'] = ($result[0]['min'] > 0) ? $result[0]['min'] : $result[0]['btc_price2'];
		$stats['open'] = ($result[0]['btc_price3'] > 0) ? $result[0]['btc_price3'] : $result[0]['btc_price2'];
		$stats['total_btc_traded'] = $result[0]['total_btc_traded'];
		$stats['total_btc'] = $result[0]['total_btc'];
		$stats['market_cap'] = $result[0]['market_cap'];
		$stats['trade_volume'] = $result[0]['trade_volume'];
		
		if ($CFG->memcached)
			$CFG->m->set('stats_'.$currency_info['currency'],$stats,120);
		
		return $stats;
	}
	
	public static function getBTCTraded() {
		global $CFG;

		if ($CFG->memcached && empty($CFG->m_skip)) {
			$cached = $CFG->m->get('btc_traded');
			if ($cached) {
				return $cached;
			}
		}
		
		$sql = "SELECT btc_24h AS total_btc_traded FROM status LIMIT 0,1";
		$result = db_query_array($sql);
		
		if ($CFG->memcached)
			$CFG->m->set('btc_traded',$result,120);
		
		return $result;
	}
}