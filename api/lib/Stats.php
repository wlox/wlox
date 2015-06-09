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
		$currency_id = ($currency_id > 0) ? preg_replace("/[^0-9]/", "",$currency_id) : $usd_info['id'];
		$currency_abbr = preg_replace("/[^a-zA-Z]/", "",$currency_abbr);

		if ($currency_abbr) {
			$c_info = $CFG->currencies[strtoupper($currency_abbr)];
			$currency_id = $c_info['id'];
		}
		elseif ($currency_id > 0) {
			$c_info = $CFG->currencies[$currency_id];
		}
		
		if ($CFG->memcached) {
			$cached = $CFG->m->get('stats_'.$c_info['currency']);
			if ($cached) {
				return $cached;
			}
		}
		
		$conversion = ($usd_info['id'] == $currency_id) ? ' currencies.usd_ask' : ' (1 / IF(transactions.currency = '.$usd_info['id'].','.$c_info['usd_ask'].', '.$c_info['usd_ask'].' / currencies.usd_ask))';
		$conversion1 = ($usd_info['id'] == $currency_id) ? ' currencies1.usd_ask' : ' (1 / IF(transactions.currency1 = '.$usd_info['id'].','.$c_info['usd_ask'].', '.$c_info['usd_ask'].' / currencies1.usd_ask))';
		
		$bid_ask = Orders::getBidAsk(false,$currency_id);
		$bid = $bid_ask['bid'];
		$ask = $bid_ask['ask'];

		$sql = "SELECT * FROM current_stats WHERE id = 1";
		$result1 = db_query_array($sql);

		$sql = "SELECT ".(($CFG->cross_currency_trades) ? "ROUND((CASE WHEN transactions.currency = $currency_id THEN transactions.btc_price WHEN transactions.currency1 = $currency_id THEN transactions.orig_btc_price ELSE (transactions.orig_btc_price * $conversion1) END),2)" : 'transactions.btc_price')." AS btc_price, IF(transactions.transaction_type = {$CFG->transactions_buy_id},'BUY','SELL') AS last_transaction_type, IF(transactions.currency != $currency_id AND transactions.currency1 != $currency_id,currencies1.currency,'{$c_info['currency']}') AS last_transaction_currency FROM transactions LEFT JOIN currencies ON (transactions.currency = currencies.id) LEFT JOIN currencies currencies1 ON (currencies1.id = transactions.currency1) WHERE 1 ".((!$CFG->cross_currency_trades) ? "AND transactions.currency = $currency_id" : '')." ORDER BY transactions.id DESC LIMIT 0,1";
		$result2 = db_query_array($sql);

		$sql = "SELECT ".(($CFG->cross_currency_trades) ? "ROUND((CASE WHEN transactions.currency = $currency_id THEN transactions.btc_price WHEN transactions.currency1 = $currency_id THEN transactions.orig_btc_price ELSE (transactions.orig_btc_price * $conversion1) END),2)" : 'transactions.btc_price')." AS btc_price FROM transactions LEFT JOIN currencies ON (transactions.currency = currencies.id) LEFT JOIN currencies currencies1 ON (currencies1.id = transactions.currency1) WHERE transactions.date < CURDATE() ".((!$CFG->cross_currency_trades) ? "AND transactions.currency = $currency_id" : '')." ORDER BY transactions.id DESC LIMIT 0,1";
		$result3 = db_query_array($sql);
		
		$sql = "SELECT ROUND(SUM(btc),8) AS total_btc_traded FROM transactions WHERE `date` >= DATE_SUB(DATE_ADD(NOW(), INTERVAL ".((($CFG->timezone_offset)/60)/60)." HOUR), INTERVAL 1 DAY) ORDER BY transactions.id DESC LIMIT 0,1";
		$result4 = db_query_array($sql);

		$sql = "SELECT MAX(".(($CFG->cross_currency_trades) ? "ROUND((CASE WHEN transactions.currency = $currency_id THEN transactions.btc_price WHEN transactions.currency1 = $currency_id THEN transactions.orig_btc_price ELSE (transactions.orig_btc_price * $conversion1) END),2)" : 'transactions.btc_price').") AS max, MIN(".(($CFG->cross_currency_trades) ? "ROUND((CASE WHEN transactions.currency = $currency_id THEN transactions.btc_price WHEN transactions.currency1 = $currency_id THEN transactions.orig_btc_price ELSE (transactions.orig_btc_price * $conversion1) END),2)" : 'transactions.btc_price').") AS min FROM transactions LEFT JOIN currencies ON (transactions.currency = currencies.id) LEFT JOIN currencies currencies1 ON (currencies1.id = transactions.currency1) WHERE transactions.date >= CURDATE() ".((!$CFG->cross_currency_trades) ? "AND transactions.currency = $currency_id" : '')." LIMIT 0,1";
		$result5 = db_query_array($sql);


		$stats['bid'] = $bid;
		$stats['ask'] = $ask;
		$stats['last_price'] = ($result2[0]['btc_price']) ? $result2[0]['btc_price'] : $ask;
		$stats['last_transaction_type'] = $result2[0]['last_transaction_type'];
		$stats['last_transaction_currency'] = $result2[0]['last_transaction_currency'];
		$stats['daily_change'] = ($result3[0]['btc_price'] > 0 && $result2[0]['btc_price'] > 0) ? $result2[0]['btc_price'] - $result3[0]['btc_price'] : '0';
		$stats['daily_change_percent'] = ($stats['last_price'] > 0) ? ($stats['daily_change']/$stats['last_price']) * 100 : 0;
		$stats['max'] = ($result5[0]['max'] > 0) ? $result5[0]['max'] : $result2[0]['btc_price'];
		$stats['min'] = ($result5[0]['min'] > 0) ? $result5[0]['min'] : $result2[0]['btc_price'];
		$stats['open'] = ($result3[0]['btc_price'] > 0) ? $result3[0]['btc_price'] : $result2[0]['btc_price'];
		$stats['total_btc_traded'] = $result4[0]['total_btc_traded'];
		$stats['total_btc'] = $result1[0]['total_btc'];
		$stats['market_cap'] = $result1[0]['market_cap'];
		$stats['trade_volume'] = $result1[0]['trade_volume'];
		
		if ($CFG->memcached)
			$CFG->m->set('stats_'.$c_info['currency'],$stats,120);
		
		return $stats;
	}
	
	public static function getBTCTraded() {
		global $CFG;

		if ($CFG->memcached) {
			$cached = $CFG->m->get('btc_traded');
			if ($cached) {
				return $cached;
			}
		}
		
		$sql = "SELECT ROUND(SUM(btc),8) AS total_btc_traded FROM transactions WHERE `date` >= DATE_SUB(DATE_ADD(NOW(), INTERVAL ".((($CFG->timezone_offset)/60)/60)." HOUR), INTERVAL 1 DAY) LIMIT 0,1";
		$result = db_query_array($sql);
		
		if ($CFG->memcached)
			$CFG->m->set('btc_traded',$result,120);
		
		return $result;
	}
}