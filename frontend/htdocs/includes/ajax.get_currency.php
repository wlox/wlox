<?php
chdir('..');
include '../cfg/cfg.php';

$currency1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['currency']);

API::add('Orders','getCurrentBid',array($currency1));
API::add('Orders','getCurrentAsk',array($currency1));
API::add('User','getAvailable');
$query = API::send();

$current_bid = $query['Orders']['getCurrentBid']['results'][0];
$current_ask = $query['Orders']['getCurrentAsk']['results'][0];
$user_available = $query['User']['getAvailable']['results'][0];

$return['currency_info'] = $CFG->currencies[strtoupper($currency1)];
$return['current_bid'] = $current_bid;
$return['current_ask'] = $current_ask;
$return['available_btc'] = number_format($user_available['BTC'],8);
$return['available_fiat'] = number_format($user_available[strtoupper($currency1)],2);

echo json_encode($return);
