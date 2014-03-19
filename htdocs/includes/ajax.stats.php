<?php
chdir('..');
include '../cfg/cfg.php';

$currency1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['currency']);
$currency_info = DB::getRecord('currencies',false,$currency1,0,'currency');

$stats = Stats::getCurrent($currency_info['id']);
echo json_encode($stats);