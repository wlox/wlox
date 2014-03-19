<?php
chdir('..');
include '../cfg/cfg.php';

$account1 = ereg_replace("[^0-9]", "",$_REQUEST['account']);

$bank_account = DB::getRecord('bank_accounts',$account1,0,1);
$bank_account_currency = DB::getRecord('currencies',$bank_account['currency'],0,1);

$return['client_account'] = $bank_account['account_number'];
$return['escrow_account'] = $bank_account_currency['account_number'];
$return['escrow_name'] = $bank_account_currency['account_name'];

if ($_REQUEST['avail']) {
	$user_available = SiteUser::getAvailable();
	$return['currency'] = $bank_account_currency['currency'];
	$return['currency_char'] = $bank_account_currency['fa_symbol'];
	$return['available'] = number_format($user_available[$return['currency']],2);
}

echo json_encode($return);