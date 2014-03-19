<?php
chdir('..');
include '../cfg/cfg.php';

$currency1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['currency']);
$current_bid = Orders::getCurrentBid($currency1);
$current_ask = Orders::getCurrentAsk($currency1);
$user_available = SiteUser::getAvailable();

$return['currency_info'] = $CFG->currencies[strtoupper($currency1)];
$return['current_bid'] = $current_bid;
$return['current_ask'] = $current_ask;
$return['available_btc'] = number_format($user_available['BTC'],8);
$return['available_fiat'] = number_format($user_available[strtoupper($currency1)],2);

echo json_encode($return);
