<?php

chdir('..');
include '../cfg/cfg.php';
chdir('securimage');
include 'securimage.php';
$img = new Securimage();
$img->show(); // alternate use:  $img->show('/path/to/background.jpg');

?>
