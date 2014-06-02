<?php
chdir('..');
include '../cfg/cfg.php';

$currency1 = preg_replace("/[^a-zA-Z]/", "",$_REQUEST['currency']);
$type1 = preg_replace("/[^0-9]/", "",$_REQUEST['type']);

API::add('Transactions','getHistory',array($currency1,$type1));
$query = API::send();
$return = $query['Transactions']['getHistory']['results'][0];
echo json_encode($return);