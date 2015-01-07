<?php

chdir('..');
include '../lib/common.php';
chdir('securimage');
include 'securimage.php';
$img = new Securimage();
$img->show(); // alternate use:  $img->show('/path/to/background.jpg');

?>
