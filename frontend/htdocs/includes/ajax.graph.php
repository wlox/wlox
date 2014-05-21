<?php
chdir('..');
include '../cfg/cfg.php';

$timeframe1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['timeframe']);
$currency1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['currency']);
$action1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['action']);

if (!$action1) {
	API::add('Stats','getHistorical',array($timeframe1,$currency1));
	$query = API::send();
	$stats = $query['Stats']['getHistorical']['results'][0];
	if ($stats) {
		foreach ($stats as $row) {
			$vars[] = '['.$row['date'].','.$row['price'].']';
		}
	}
	echo '['.implode(',', $vars).']';
}
elseif ($action1 == 'orders') {
	API::add('Orders','get',array(false,false,false,$currency1,false,false,2,false,false,1));
	API::add('Orders','get',array(false,false,false,$currency1,false,false,false,false,1,1));
	$query = API::send();
	
	$bids = $query['Orders']['get']['results'][0];
	$asks = $query['Orders']['get']['results'][1];
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