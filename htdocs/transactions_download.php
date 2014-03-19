<?php
include '../cfg/cfg.php';

if (User::isLoggedIn()) {
	if (User::$info['verified_authy'] == 'Y' && !($_SESSION['token_verified'] > 0))
		Link::redirect('verify-token.php');
	elseif (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
		Link::redirect('settings.php');
}
else {
	Link::redirect('login.php');
	exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=transactions_'.date('Y-m-d').'.csv');

$transactions = Transactions::get(false,false,false,false,User::$info['id'],false,false,false,false,1);
if ($transactions) {
	$output = fopen('php://output', 'w');
	fputcsv($output, array(' '.Lang::string('transactions-type').' ',' '.Lang::string('transactions-time').' ',' '.Lang::string('transactions-btc').' ',' '.Lang::string('transactions-fiat').' ',' '.Lang::string('transactions-price').' ',' '.Lang::string('transactions-fee').' '));
	foreach ($transactions as $transaction) {
		fputcsv($output,array(
			' '.$transaction['type'].' ',
			' '.date('M j, Y, H:i:a',strtotime($transaction['date']) + $CFG->timezone_offset).' ',
			' '.number_format($transaction['btc_net'],8).' ',
			' '.$transaction['fa_symbol'].number_format($transaction['btc_net'] * $transaction['fiat_price'],2).' ',
			' '.$transaction['fa_symbol'].number_format($transaction['fiat_price'],2).' ',
			' '.$transaction['fa_symbol'].number_format($transaction['fee'] * $transaction['fiat_price'],2).' ',
		));
	}
}

