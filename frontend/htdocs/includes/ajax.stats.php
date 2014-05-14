<?php
chdir('..');
include '../cfg/cfg.php';

$currency1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['currency']);

API::add('Stats','getCurrent',array(false,$currency1));
$query = API::send();

$stats = $query['Stats']['getCurrent']['results'][0];
echo json_encode($stats);