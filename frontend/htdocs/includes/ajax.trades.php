<?php
chdir('..');
include '../cfg/cfg.php';

$currency1 = ($_REQUEST['currency'] != 'All') ? ereg_replace("/[^\da-z]/i", "",$_REQUEST['currency']) : false;
$notrades = $_REQUEST['notrades'];
$limit = $_REQUEST['get10'] ? 10 : 5;
$user = $_REQUEST['user'];
$currency_info = $CFG->currencies[strtoupper($currency1)];


if (!$notrades) {
	API::add('Transactions','getList',array($currency1,$notrades,$limit_7));
	API::add('Stats','getBTCTraded');
}

API::add('Orders','get',array(false,false,$limit,$currency1,$user,false,1));
API::add('Orders','get',array(false,false,$limit,$currency1,$user,false,false,false,1));
$query = API::send();

$return['asks'][] = $query['Orders']['get']['results'][1];
$return['bids'][] = $query['Orders']['get']['results'][0];

if (!$notrades) {
	$return['transactions'][] = $query['Transactions']['getList']['results'][0];
	$return['btc_traded'] = $query['Stats']['getBTCTraded']['results'][0];
}

echo json_encode($return);