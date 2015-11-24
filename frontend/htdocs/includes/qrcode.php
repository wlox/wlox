<?php

include('../phpqrcode/qrlib.php');

if ($_REQUEST['sec'])
	QRcode::png($_REQUEST['code'],false,QR_ECLEVEL_L,8);
else
	QRcode::png('bitcoin:'.$_REQUEST['code'],false,QR_ECLEVEL_L,8);
