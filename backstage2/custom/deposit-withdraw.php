<?php 
$upload = new Form('deposits',false,false,'form1');
$upload->verify();

if (is_array($CFG->temp_files)) {
	$key = key($CFG->temp_files);
	$transactions = 0;
	$cancelled = 0;
	
	if (($handle = fopen($CFG->dirroot.$CFG->temp_file_location.$CFG->temp_files[$key], "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, ";",'"')) !== FALSE) {
			if (!($data[0] > 0) || !strstr($data[4],'INV'))
				continue;
			
			$sql = 'SELECT id FROM requests WHERE crypto_id = '.$data[0];
			$result = db_query_array($sql);
			
			if ($result)
				continue;
			
			$sql = 'SELECT bank_accounts.site_user, bank_accounts.currency AS currency_id, currencies.currency AS currency, site_users.'.strtolower($data[6]).' AS cur_balance FROM bank_accounts LEFT JOIN currencies ON (currencies.id = bank_accounts.currency) LEFT JOIN site_users ON (bank_accounts.site_user = site_users.id) WHERE bank_accounts.account_number = '.$data[2];
			$result = db_query_array($sql);

			if ($result[0]['currency'] == $data[6]) {
				$insert_id = db_insert('requests',array('date'=>date('Y-m-d H:i:s'),'site_user'=>$result[0]['site_user'],'currency'=>$result[0]['currency_id'],'amount'=>$data[8],'description'=>$CFG->deposit_fiat_desc,'request_type'=>$CFG->request_deposit_id,'request_status'=>$CFG->request_completed_id,'account'=>$data[2],'crypto_id'=>$data[0]));
				db_update('site_users',$result[0]['site_user'],array(strtolower($data[6])=>($result[0]['cur_balance'] + $data[8])));
				$transactions++;
			}
			else {
				$currency_info = DB::getRecord('currencies',false,$data[6],false,'currency');
				$insert_id = db_insert('requests',array('date'=>date('Y-m-d H:i:s'),'site_user'=>$result[0]['site_user'],'currency'=>$currency_info['id'],'amount'=>$data[8],'description'=>$CFG->deposit_fiat_desc,'request_type'=>$CFG->request_deposit_id,'request_status'=>$CFG->request_cancelled_id,'account'=>$data[2],'crypto_id'=>$data[0]));
				$cancelled++;
			}
		}
		fclose($handle);
		
		if ($transactions > 0)
			$upload->messages[] = $transactions.' new transactions were credited.';
		if ($cancelled > 0)
			$upload->errors[] = $cancelled.' transactions could not be credited because of an information mismatch.';
	}
	
	unlink($CFG->dirroot.$CFG->temp_file_location.$CFG->temp_files[$key]);
	unset($CFG->temp_files);
}

$upload->show_errors();
$upload->show_messages();
$upload->fileInput('deposits','Deposits Export File',1,array('csv'),false,false,false,1,false,false,false,false,false,1);
$upload->submitButton('Upload','Upload');
$upload->display();


$withdraw = new Form('withdraw',false,false,'form1');
$withdraw->verify();

if ($_REQUEST['withdraw'] && !is_array($withdraw->errors)) {
	if ($withdraw->info['currency'] > 0 && $withdraw->info['amount'] > 0) {
		$currency_info = DB::getRecord('currencies',$withdraw->info['currency'],0,1);
		
		if (!$currency_info) {
			$withdraw->errors[] = 'Invalid currency.';
		}
		else {
			$status = DB::getRecord('status',1,0,1);
			$sql = 'UPDATE status SET '.strtolower($currency_info['currency']).'_escrow = '.strtolower($currency_info['currency']).'_escrow - '.$withdraw->info['amount'].' WHERE id = 1';
			db_query($sql);
			
			$withdraw->messages[] = $withdraw->info['amount'].' subtracted from '.$currency_info['currency'];
		}
	}
}

$withdraw->show_errors();
$withdraw->show_messages();
$withdraw->selectInput('currency','Currency',1,false,false,'currencies',array('currency'));
$withdraw->textInput('amount','Amount',1);
$withdraw->submitButton('Withdraw','Withdraw');
$withdraw->display();




?>