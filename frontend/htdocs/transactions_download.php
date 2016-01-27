<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=transactions_'.date('Y-m-d').'.csv');

API::add('Transactions','get',array(false,false,false,false,1,false,false,false,false,false,1));
$query = API::send();

$transactions = $query['Transactions']['get']['results'][0];
if ($transactions) {
	$output = fopen('php://output', 'w');
	fputcsv($output, array(' '.Lang::string('transactions-type').' ',' '.Lang::string('transactions-time').' ',' '.Lang::string('transactions-btc').' ',' '.Lang::string('currency').' ',' '.Lang::string('transactions-fiat').' ',' '.Lang::string('transactions-price').' ',' '.Lang::string('transactions-fee').' '));
	foreach ($transactions as $transaction) {
		fputcsv($output,array(
			' '.$transaction['type'].' ',
			' '.date('M j, Y, H:i',strtotime($transaction['date']) + $CFG->timezone_offset).' UTC ',
			' '.number_format($transaction['btc'],8).' ',
			' '.$transaction['currency'].' ',
			' '.number_format($transaction['btc_net'] * $transaction['fiat_price'],2).' ',
			' '.number_format($transaction['fiat_price'],2).' ',
			' '.number_format($transaction['fee'] * $transaction['fiat_price'],2).' ',
		));
	}
}

