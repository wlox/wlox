<?php
	include('lib/common.php');
	String::magicQuotesOff();
	
	$rows = $_REQUEST['rows'];
	if ($rows) {
		$done_tables = array();
		foreach ($rows as $row) {
			$seconds = ($row['total_y']/50) * 1800;
			$edate_field = $row['edate_field'];
			$sdate_field = $row['sdate_field'];
			$original = DB::getRecord($row['table'],$row['id'],0,1);
			$sdate = date('Y-m-d H:i:s',strtotime($original[$sdate_field]) + $seconds);
			$edate = date('Y-m-d H:i:s',strtotime($original[$edate_field]) + $seconds);
			
			if ($row['f_table_id'] > 0) {
				$f_id_parts = explode(',',$row['f_id_field']);
				$f_parts = explode('.',$f_id_parts[(count($f_id_parts)-1)]);
				
				if (!in_array($row['table'],$done_tables) && !array_key_exists($row['id'],$done_tables)) {
					$a = DB::update($row['table'],array($row['edate_field']=>$edate,$row['sdate_field']=>$sdate),$row['id']);
					if ($a)
						$done_tables[($row['id'])] = $row['table'];
				}
				
				DB::update($f_parts[0],array($f_parts[1]=>$row['f_id']),$row['f_table_id']);
			}
			else {
				if (!in_array($row['table'],$done_tables) && !array_key_exists($row['id'],$done_tables)) {
					if ($row['f_id_field'])
						$a = DB::update($row['table'],array($row['edate_field']=>$edate,$row['sdate_field']=>$sdate,$row['f_id_field']=>$row['f_id']),$row['id']);
					else
						$a = DB::update($row['table'],array($row['edate_field']=>$edate,$row['sdate_field']=>$sdate),$row['id']);
						
					if ($a)
						$done_tables[($row['id'])] = $row['table'];
				}
			}
		}
	}
	
	if (!$errors) {
		Messages::add('Save successfull.');
		Messages::display();
	}
	else {
		Errors::merge($errors);
		Errors::display();
	}

?>