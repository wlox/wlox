<?php
chdir('..');

$ajax = true;
include '../lib/common.php';

API::add('User','getAvailable');
API::add('BankAccounts','getRecord',array($_REQUEST['account']));
$query = API::send();

$bank_account = $query['BankAccounts']['getRecord']['results'][0];
$user_available = $query['User']['getAvailable']['results'][0];
$bank_account_currency = $CFG->currencies[$bank_account['currency']];

$return['client_account'] = $bank_account['account_number'];
$return['escrow_account'] = $bank_account_currency['account_number'];
$return['escrow_name'] = $bank_account_currency['account_name'];

if (!empty($_REQUEST['avail'])) {
	$return['currency'] = $bank_account_currency['currency'];
	$return['currency_char'] = $bank_account_currency['fa_symbol'];
	$return['available'] = number_format($user_available[$bank_account_currency['currency']],2);
}

echo json_encode($return);