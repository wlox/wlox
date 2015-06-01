<?php
chdir('..');

$ajax = true;
include '../lib/common.php';

$currency1 = (!empty($CFG->currencies[strtoupper($_REQUEST['currency'])])) ? $_REQUEST['currency'] : 'usd';

API::add('Orders','getBidAsk',array($currency1));
API::add('User','getAvailable');
$query = API::send();

$current_bid = $query['Orders']['getBidAsk']['results'][0]['bid'];
$current_ask = $query['Orders']['getBidAsk']['results'][0]['ask'];
$user_available = $query['User']['getAvailable']['results'][0];

$return['currency_info'] = $CFG->currencies[strtoupper($currency1)];
$return['current_bid'] = $current_bid;
$return['current_ask'] = $current_ask;
$return['available_btc'] = (!empty($user_available['BTC'])) ? number_format($user_available['BTC'],8) : 0;
$return['available_fiat'] = (!empty($user_available[strtoupper($currency1)])) ? number_format($user_available[strtoupper($currency1)],2) : 0;

echo json_encode($return);
