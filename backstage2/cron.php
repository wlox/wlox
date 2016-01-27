

<?php
#!/usr/bin/php

error_reporting(E_ERROR);
class object {
}
$CFG = new object ( );
$CFG->backstage_mode = true;
$CFG->fck_baseurl = "http://backstage.tecnorganica.com/shared/";
$CFG->libdir = "lib";
$CFG->img_dir = "images";
$CFG->self = basename($_SERVER['SCRIPT_FILENAME']);
$CFG->in_cron = true;
$DB_DEBUG = true;

$CFG->dbhost = "localhost";
$CFG->dbuser = "root";
$CFG->dbpass = "";
$dbh = mysql_connect ($CFG->dbhost,$CFG->dbuser,$CFG->dbpass);

require_once ("../shared2/autoload.php");


$sql = "SHOW DATABASES";
$result = db_query_array($sql);

$ignore = array('information_schema','cdcol','mysql','performance_schema','phpmyadmin');

if ($result) {
	foreach ($result as $database) {
		if (in_array($database['Database'],$ignore))
			continue;
		
		mysql_select_db ($database['Database']);
		
		$sql = "DELETE FROM sessions WHERE session_start < '".date('Y-m-d 00:00:00',strtotime('-7 days'))."' ";
		mysql_query ($sql);
		
		if (!DB::tableExists('admin_cron'))
			continue;
		
		Settings::assign ( $CFG );
			
		$sql = "SELECT * FROM admin_cron ";
		$result = db_query_array($sql);
		
		if ($result) {
			foreach ($result as $row) {
				$control = DB::getRecord('admin_controls',$row['control_id'],0,1);
				$control_args = unserialize($control['arguments']);
				$method = DB::getRecord('admin_controls_methods',$row['method_id'],0,1);
				$method_args = Control::parseArguments($method['arguments'],$control['class'],$method['method']);
				
				if ($method['method'] == 'emailNotify') {
					$email_field = $method_args['email_field'];
					$message = DB::getRecord($method_args['email_table'],$method_args['email_record'],0,1);
				}
				
				$sql = "SELECT * FROM {$control_args['table']} WHERE 1 ";
				$result1 = db_query_array($sql);

				if ($result1) {
					foreach ($result1 as $row1) {
						if (!$method_args['run_in_cron']) {
							foreach ($row1 as $key => $val) {
								$day = str_replace('[','',str_replace(']','',str_ireplace($key,$val,$row['day'])));
								$month = str_replace('[','',str_replace(']','',str_ireplace($key,$val,$row['month'])));
								$year = str_replace('[','',str_replace(']','',str_ireplace($key,$val,$row['year'])));
							}	
	
							if ($row['day']) {
								if ($day != date('j') && $day != date('d'))
									continue;
							}
							if ($row['month']) {
								if ($month != date('n') && $month != date('m'))
									continue;
							}
							if ($row['year']) {
								if ($year != date('Y') && $year != date('y'))
									continue;
							}
						}
						
						if ($method['method'] == 'emailNotify') {
							if (Email::send($CFG->form_email,$row1[$email_field],$message['title'],$CFG->form_email_from,false,$message['content'],$row1))
								Messages::add($CFG->email_sent_message);
							else
								Errors::add($CFG->email_send_error);
						}
						elseif ($method['method'] == 'createRecord') {
							$CFG->save_called = 1;
							$form = new Form('cron',false,false,false,$control_args['table'],false,1);
							$form->get($row1['id']);
							$form->createRecord($method_args['table'],$method_args['insert_array'],$method_args['trigger_field'],$method_args['trigger_value'],$method_args['day'],$method_args['month'],$method_args['year'],$method_args['send_condition'],$method_args['any_modification'],$method_args['register_changes'],$method_args['on_new_record_only'],$method_args['store_row'],$method_args['if_not_exists'],$method_args['run_in_cron']);
							unset($form);
						}
						elseif ($method['method'] == 'editRecord') {
							$CFG->save_called = 1;
							$form = new Form('cron',false,false,false,$control_args['table'],false,1);
							$form->get($row1['id']);
							$form->editRecord($method_args['table'],$method_args['insert_array'],$method_args['trigger_field'],$method_args['trigger_value'],$method_args['day'],$method_args['month'],$method_args['year'],$method_args['send_condition'],$method_args['any_modification'],$method_args['register_changes'],$method_args['on_new_record_only'],$method_args['store_row'],$method_args['edit_record_field_id'],$method_args['run_in_cron']);
							unset($form);
						}
					}
				}
			}
		}
	}
}
Messages::display();
Errors::display();
?>