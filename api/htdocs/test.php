<?php 
include '../cfg/cfg.php';

$sql = 'SELECT id, pass FROM site_users';
$result = db_query_array($sql);

if ($result) {
	foreach ($result as $row) {
		$pass = Encryption::hash($row['pass']);
		db_update('site_users',$row['id'],array('pass'=>$pass));
	}
}

?>