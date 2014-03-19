<?php
chdir('..');
include '../cfg/cfg.php';

$user = User::$info['id'];

$sql = "
SELECT transactions.*, UNIX_TIMESTAMP(transactions.date) AS datestamp, currencies.currency AS currency, (currencies.usd * transactions.fiat) AS usd_amount, transactions.btc_price AS fiat_price, currencies.fa_symbol AS fa_symbol ".(($user > 0) ? ",IF(transactions.site_user = $user,transaction_types.name_{$CFG->language},transaction_types1.name_{$CFG->language}) AS type, IF(transactions.site_user = $user,transactions.fee,transactions.fee1) AS fee, IF(transactions.site_user = $user,transactions.btc_net,transactions.btc_net1) AS btc_net" : "")."
FROM transactions
LEFT JOIN transaction_types ON (transaction_types.id = transactions.transaction_type)
LEFT JOIN transaction_types transaction_types1 ON (transaction_types1.id = transactions.transaction_type1)
LEFT JOIN currencies ON (currencies.id = transactions.currency)
WHERE 1
AND transactions.site_user = $user
ORDER BY transactions.date DESC LIMIT 0,1 ";

$result = db_query_array($sql);
$return = $result[0];
echo json_encode($return);