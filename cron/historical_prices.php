<?php
include 'cfg.php';

echo "Beginning Historical Data processing...".PHP_EOL;

// QUANDL HISTORICAL DATA
$currency = 'USD';
$exchange = 'BITSTAMP';

$data = file_get_contents('http://www.quandl.com/api/v1/datasets/BITCOIN/'.$exchange.$currency.'.csv?trim_start=2011-01-01');
$data1 = explode("\n",$data);
if ($data1) {
	$i = 1;
	$c = count($data1);
	foreach ($data1 as $row) {
		if ($i == 1) {
			$i++;
			continue;
		}
		
		$row1 = explode(',',$row);
		
		if ($i == 2) {
			db_update('currencies',$CFG->btc_currency_id,array('usd_ask'=>$row1[4],'usd_bid'=>$row1[4]));
		}
		
		$sql = "SELECT * FROM historical_data WHERE `date` = '{$row1[0]}'";
		$result = db_query_array($sql);
		
		if (!$result) {
			db_insert('historical_data',array('date'=>$row1[0],strtolower($currency)=>$row1[4]));
		}
		else {
			db_update('historical_data',$result[0]['id'],array(strtolower($currency)=>$row1[4]));
		}
		
		$i++;
	}
}
echo 'done';
?>