<?php

include('../phpqrcode/qrlib.php');
QRcode::png($_REQUEST['code'],false,QR_ECLEVEL_L,8);
