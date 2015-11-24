<?php
chdir('..');

$ajax = true;
include '../lib/common.php';

$timeframe1 = (!empty($_REQUEST['timeframe'])) ? preg_replace("/[^0-9a-zA-Z]/", "",$_REQUEST['timeframe']) : false;
$currency1 = (!empty($CFG->currencies[strtoupper($_REQUEST['currency'])])) ? $_REQUEST['currency'] : 'usd';
$action1 = (!empty($_REQUEST['action'])) ? preg_replace("/[^0-9a-zA-Z]/", "",$_REQUEST['action']) : false;

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