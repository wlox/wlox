<?php

include('../phpqrcode/qrlib.php');

QRcode::png('bitcoin:'.$_REQUEST['code'],false,QR_ECLEVEL_L,8);
