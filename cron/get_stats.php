#!/usr/bin/php
<?php
echo "Beginning Get Status processing...".PHP_EOL;

include 'common.php';


// GET BITCOIN GLOBAL STATS
$data = file_get_contents('http://blockchain.info/charts/total-bitcoins?format=csv');
$data1 = explode("\n",$data);
$c = count($data1) - 1;
$data2 = explode(',',$data1[$c]);
$total_btc = $data2[1];

$data = file_get_contents('http://blockchain.info/charts/market-cap?format=csv');
$data1 = explode("\n",$data);
$c = count($data1) - 1;
$data2 = explode(',',$data1[$c]);
$market_cap = $data2[1];

$data = file_get_contents('http://blockchain.info/charts/trade-volume?format=csv');
$data1 = explode("\n",$data);
$c = count($data1) - 1;
$data2 = explode(',',$data1[$c]);
$trade_volume = $data2[1];
db_update('current_stats',1,array('trade_volume'=>$data2[1],'total_btc'=>$total_btc,'market_cap'=>$market_cap));

// GET EXCHANGE RATES
if ($CFG->currencies) {
	foreach ($CFG->currencies as $currency) {
		if ($currency['currency'] == 'BTC' || $currency == 'USD')
			continue;
		
		$currencies[] = $currency['currency'].'USD';
	}
	$currency_string = urlencode(implode(',',$currencies));
	$data = json_decode(file_get_contents('http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20yahoo.finance.xchange%20where%20pair%3D%22'.$currency_string.'%22&format=json&diagnostics=true&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys'),TRUE);
	
	if ($data['query']['results']['rate']) {
		$bid_str = '(CASE currency ';
		$ask_str = '(CASE currency ';
		$currency_ids = array();
		$last = false;
		
		foreach ($data['query']['results']['rate'] as $row) {
			$key = str_replace('USD','',$row['id']);
			if ($key == $last)
				continue;
			
			$ask = $row['Ask'];
			$bid = $row['Bid'];
			
			if (strlen($key) < 3 || strstr($key,'='))
				continue;
			
			if ($bid == $CFG->currencies[$key]['usd_bid'] || $ask == $CFG->currencies[$key]['usd_ask'])
				continue;
			
			$bid_str .= ' WHEN "'.$key.'" THEN '.$bid.' ';
			$ask_str .= ' WHEN "'.$key.'" THEN '.$ask.' ';
			$currency_ids[] = $CFG->currencies[$key]['id'];
			$last = $key;
		}
		
		$bid_str .= ' END)';
		$ask_str .= ' END)';
		
		$sql = 'UPDATE currencies SET usd_bid = '.$bid_str.', usd_ask = '.$ask_str.' WHERE id IN ('.implode(',',$currency_ids).')';
		$result = db_query($sql);
	}
}

db_update('status',1,array('cron_get_stats'=>date('Y-m-d H:i:s')));
echo 'done'.PHP_EOL;


