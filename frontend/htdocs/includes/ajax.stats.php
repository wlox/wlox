<?php
chdir('..');

$ajax = true;
include '../lib/common.php';

$currency1 = (!empty($CFG->currencies[strtoupper($_REQUEST['currency'])])) ? $_REQUEST['currency'] : 'usd';

API::add('Stats','getCurrent',array(false,$currency1));
$query = API::send();

$stats = $query['Stats']['getCurrent']['results'][0];
echo json_encode($stats);