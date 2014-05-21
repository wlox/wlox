<?php
chdir('..');
include '../cfg/cfg.php';

API::add('Transactions','getHistory');
$query = API::send();
$return = $query['Transactions']['getHistory']['results'][0];
echo json_encode($return);