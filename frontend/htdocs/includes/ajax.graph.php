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
		foreach ($bids as $bid) {
			$min_bid = (!$min_bid || $bid['btc_price'] < $min_bid);
			$max_bid = (!$max_bid || $bid['btc_price'] > $max_bid);
		}
	
	}
	if ($asks) {
		foreach ($asks as $ask) {
			$min_ask = (!$min_ask || $ask['btc_price'] < $min_ask);
			$max_ask = (!$max_ask || $ask['btc_price'] > $max_ask);
		}
	}
	
	$bid_range = $max_bid - $min_bid;
	$ask_range = $max_ask - $min_ask;
	$c_bids = count($bids);
	$c_asks = count($asks);
	$lower_range = ($bid_range < $ask_range) ? $bid_range : $ask_range;
	
	if ($bids) {
		$cum_btc = 0;
		foreach ($bids as $bid) {
			if ($max_bid && $c_asks > 1 && (($max_bid - $bid['btc_price']) >  $lower_range))
				continue;
			
			$cum_btc += $bid['btc'];
			$vars[] = '['.$bid['btc_price'].','.$cum_btc.']';
		}
		
	}
	if ($asks) {
		$cum_btc = 0;
		foreach ($asks as $ask) {
			if ($min_ask && $c_bids > 1 && (($ask['btc_price'] - $min_ask) >  $lower_range))
				continue;
			
			$cum_btc += $ask['btc'];
			$vars1[] = '['.$ask['btc_price'].','.$cum_btc.']';
		}
	}
	echo '{"bids": ['.implode(',', array_reverse($vars)).'],"asks": ['.implode(',', $vars1).']}';
}