<?php
phpinfo();
$db = odbc_connect("dbisam", "root", "");
$sql = "SELECT * FROM SClientes";
$result = odbc_exec($db,$sql);

while(odbc_fetch_row($result)){
	$name = odbc_result($result, 1);
	echo $name.',';
}

?>