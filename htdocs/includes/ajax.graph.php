<?php
chdir('..');
include '../cfg/cfg.php';

$timeframe1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['timeframe']);
$currency1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['currency']);
$action1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['action']);

if (!$action1) {
	$stats = Stats::getHistorical($timeframe1,$currency1);
	if ($stats) {
		foreach ($stats as $row) {
			$vars[] = '['.$row['date'].','.$row['price'].']';
		}
	}
	echo '['.implode(',', $vars).']';
}
elseif ($action1 == 'orders') {
	$bids = Orders::get(false,false,false,$currency1,false,false,$CFG->order_type_bid,false,false,1);
	$asks = Orders::get(false,false,false,$currency1,false,false,$CFG->order_type_ask,false,1,1);
	if ($bids) {
		$cum_btc = 0;
		foreach ($bids as $bid) {
			$cum_btc += $bid['btc'];
			$vars[] = '['.$bid['btc_price'].','.$cum_btc.']';
		}
		
	}
	if ($asks) {
		$cum_btc = 0;
		foreach ($asks as $ask) {
			$cum_btc += $ask['btc'];
			$vars1[] = '['.$ask['btc_price'].','.$cum_btc.']';
		}
	}
	echo '{"bids": ['.implode(',', array_reverse($vars)).'],"asks": ['.implode(',', $vars1).']}';
}