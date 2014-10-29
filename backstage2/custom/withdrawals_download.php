<?php 
chdir('..');
include 'cfg.php';

if (!$_SESSION['export_withdrawals'])
	exit;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=fiat_withdrawals_'.$_REQUEST['currency'].'_'.date('Y-m-d').'.csv');

$transactions = $_SESSION['export_withdrawals'];
if ($transactions) {
	$output = fopen('php://output', 'w');
	foreach ($transactions as $transaction) {
		fputcsv($output,array(
		'"'.$transaction[0].'"',
		'"'.$transaction[1].'"',
		'"'.$transaction[2].'"',
		'"'.$transaction[3].'"'
		));
	}
}
?>