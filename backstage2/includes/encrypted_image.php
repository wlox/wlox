<?php
header("Content-type: image/png");
chdir('../');
include_once 'lib/common.php';

$url = urldecode($_REQUEST['url']);
$file = file_get_contents($url);
$decrypted = Encryption::decrypt($file);

echo $decrypted;

?>