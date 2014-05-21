<?php
chdir('..');
include '../cfg/cfg.php';

$currency1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['currency']);
$notrades = $_REQUEST['notrades'];
$limit_7 = $_REQUEST['get10'];
$user = $_REQUEST['user'];
$currency_info = $CFG->currencies[strtoupper($currency1)];


if (!$notrades) {
	API::add('Transactions','getList',array($currency1,$notrades,$limit_7));
	API::add('Stats','getBTCTraded');
}

API::add('Orders','getBidList',array($currency1,$notrades,$limit_7,$user));
API::add('Orders','getAskList',array($currency1,$notrades,$limit_7,$user));
$query = API::send();

$return['asks'][] = $query['Orders']['getAskList']['results'][0];
$return['bids'][] = $query['Orders']['getBidList']['results'][0];

if (!$notrades) {
	$return['transactions'][] = $query['Transactions']['getList']['results'][0];
	$return['btc_traded'] = $query['Stats']['getBTCTraded']['results'][0];
}

echo json_encode($return);