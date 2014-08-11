<?php
class Stats {
	function getHistorical($timeframe='1year',$currency='usd') {
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
		
		$sql = "SELECT usd_ask FROM currencies WHERE currency = '$currency'";
		$result = db_query_array($sql);
		
		if (!$result)
			return false;
		
		$sql = "SELECT (UNIX_TIMESTAMP(DATE(`date`)) * 1000) AS `date`,ROUND((usd/{$result[0]['usd_ask']}),2) AS price FROM historical_data WHERE `date` >= '$start' ORDER BY `date` ASC";
		return db_query_array($sql);
	}
	
	function getCurrent($currency_id,$currency_abbr=false) {
		global $CFG;
		
		$usd_info = $CFG->currencies['USD'];
		$currency_id = ($currency_id > 0) ? preg_replace("/[^0-9]/", "",$currency_id) : $usd_info['id'];
		$currency_abbr = preg_replace("/[^a-zA-Z]/", "",$currency_abbr);
		
		if ($currency_abbr) {
			$c_info = DB::getRecord('currencies',false,$currency_abbr,0,'currency');
			$currency_id = $c_info['id'];
		}
		elseif ($currency_id > 0) {
			$c_info = DB::getRecord('currencies',$currency_id,0,1);
		}
		
		$conversion = ($usd_info['id'] == $currency_id) ? ' currencies.usd_ask' : ' (1 / IF(transactions.currency = '.$usd_info['id'].','.$c_info['usd_ask'].', '.$c_info['usd_ask'].' / currencies.usd_ask))';
		
		$sql = "SELECT * FROM current_stats WHERE id = 1";
		$result1 = db_query_array($sql);

		$sql = "SELECT ".(($CFG->cross_currency_trades) ? "ROUND(IF(transactions.currency = $currency_id,transactions.btc_price,transactions.btc_price * $conversion),2)" : 'transactions.btc_price')." AS btc_price FROM transactions LEFT JOIN currencies ON (transactions.currency = currencies.id) WHERE 1 ".((!$CFG->cross_currency_trades) ? "AND transactions.currency = $currency_id" : '')." ORDER BY transactions.date DESC LIMIT 0,1";
		$result2 = db_query_array($sql);

		$sql = "SELECT ".(($CFG->cross_currency_trades) ? "ROUND(IF(transactions.currency = $currency_id,transactions.btc_price,transactions.btc_price * $conversion),2)" : 'transactions.btc_price')." AS btc_price FROM transactions LEFT JOIN currencies ON (transactions.currency = currencies.id) WHERE transactions.date < CURDATE() ".((!$CFG->cross_currency_trades) ? "AND transactions.currency = $currency_id" : '')." ORDER BY transactions.date DESC LIMIT 0,1";
		$result3 = db_query_array($sql);
		
		$sql = "SELECT SUM(btc) AS total_btc_traded FROM transactions WHERE `date` >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY `date` ASC LIMIT 0,1";
		$result4 = db_query_array($sql);
		
		$sql = "SELECT btc_price AS max FROM transactions WHERE `date` >= CURDATE() AND currency = $currency_id ORDER BY btc_price DESC LIMIT 0,1";
		$result5 = db_query_array($sql);
		
		$sql = "SELECT btc_price AS min FROM transactions WHERE `date` >= CURDATE() AND currency = $currency_id ORDER BY btc_price ASC LIMIT 0,1";
		$result6 = db_query_array($sql);

		$stats['last_price'] = $result2[0]['btc_price'];
		$stats['daily_change'] = ($result3[0]['btc_price'] > 0 && $result2[0]['btc_price'] > 0) ? $result2[0]['btc_price'] - $result3[0]['btc_price'] : '0';
		$stats['daily_change_percent'] = ($stats['last_price'] > 0) ? ($stats['daily_change']/$stats['last_price']) * 100 : 0;
		$stats['max'] = ($result5[0]['max'] > 0) ? $result5[0]['max'] : $result2[0]['btc_price'];
		$stats['min'] = ($result6[0]['min'] > 0) ? $result6[0]['min'] : $result2[0]['btc_price'];
		$stats['open'] = ($result3[0]['btc_price'] > 0) ? $result3[0]['btc_price'] : $result2[0]['btc_price'];
		$stats['total_btc_traded'] = $result4[0]['total_btc_traded'];
		$stats['total_btc'] = $result1[0]['total_btc'];
		$stats['market_cap'] = $result1[0]['market_cap'];
		$stats['trade_volume'] = $result1[0]['trade_volume'];
		return $stats;
	}
	
	function getBTCTraded() {
		global $CFG;
		
		$sql = "SELECT SUM(btc) AS total_btc_traded FROM transactions WHERE `date` >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY `date` ASC LIMIT 0,1";
		return db_query_array($sql);
	}
}