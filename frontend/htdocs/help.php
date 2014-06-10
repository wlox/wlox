<?php
include '../cfg/cfg.php';

if (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');


// redirect to your support panel of choice here