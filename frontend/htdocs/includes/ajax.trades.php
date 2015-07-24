<?php
chdir('..');

$ajax = true;
include '../lib/common.php';

$currency1 = (array_key_exists(strtoupper($_REQUEST['currency']),$CFG->currencies)) ? strtolower($_REQUEST['currency']) : false;
$notrades = (!empty($_REQUEST['notrades']));
$limit = (!empty($_REQUEST['get10'])) ? 10 : 5;
$user = (!empty($_REQUEST['user']));
$currency_info = ($currency1) ? $CFG->currencies[strtoupper($currency1)] : $CFG->currencies['USD'];
$usd_field = 'usd_ask';


if (!$notrades) {
	API::add('Transactions','get',array(false,false,5,$currency1));
	API::add('Stats','getBTCTraded');
}
elseif (empty($_REQUEST['get10'])) {
	$limit = (!$user) ? 30 : false;
}

if (!empty($_REQUEST['last_price']) && $notrades) {
	API::add('Transactions','get',array(false,false,1,$currency1));
	
	if ($currency1)
		API::add('User','getAvailable');
}

API::add('Orders','get',array(false,false,$limit,$currency1,$user,false,1,false,false,$user));
API::add('Orders','get',array(false,false,$limit,$currency1,$user,false,false,false,1,$user));
$query = API::send();

$return['asks'][] = $query['Orders']['get']['results'][1];
$return['bids'][] = $query['Orders']['get']['results'][0];

if (!$notrades) {
	$return['transactions'][] = $query['Transactions']['get']['results'][0];
	$return['btc_traded'] = $query['Stats']['getBTCTraded']['results'][0][0]['total_btc_traded'];
}

if (!empty($_REQUEST['last_price'])) {
	$return['last_price'] = $query['Transactions']['get']['results'][0][0]['btc_price'];
	$return['last_price_curr'] = ($query['Transactions']['get']['results'][0][0]['currency'] == $currency_info['id']) ? '' : (($query['Transactions']['get']['results'][0][0]['currency1'] == $currency_info['id']) ? '' : ' ('.$CFG->currencies[$query['Transactions']['get']['results'][0][0]['currency1']]['currency'].')');
	$return['fa_symbol'] = $currency_info['fa_symbol'];
	$return['last_trans_color'] = ($query['Transactions']['get']['results'][0][0]['maker_type'] == 'sell') ? 'price-green' : 'price-red';
	
	if ($currency1) {
		$return['available_fiat'] = (!empty($query['User']['getAvailable']['results'][0][strtoupper($currency1)])) ? number_format($query['User']['getAvailable']['results'][0][strtoupper($currency1)],2) : '0';
		$return['available_btc'] = (!empty($query['User']['getAvailable']['results'][0]['BTC'])) ? number_format($query['User']['getAvailable']['results'][0]['BTC'],8) : '0';
	}
	
	if (!$notrades) {
		if ($CFG->currencies) {
			foreach ($CFG->currencies as $key => $currency) {
				if (is_numeric($key) || $currency['currency'] == 'BTC')
					continue;
		
				$last_price = number_format($return['last_price'] * ((empty($currency_info) || $currency_info['currency'] == 'USD') ? 1/$currency[$usd_field] : $currency_info[$usd_field] / $currency[$usd_field]),2);
				$return['last_price_cnv'][$currency['currency']] = $last_price;
			}
		}
	}
}

echo json_encode($return);