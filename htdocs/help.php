<?php
include '../cfg/cfg.php';

if (User::isLoggedIn()) {
	if (User::$info['verified_authy'] == 'Y' && !($_SESSION['token_verified'] > 0))
		Link::redirect('verify-token.php');
}
else {
	Link::redirect('login.php');
}

// Redirect to help portal here.
// Link::redirect('http://example.freshdesk.com/login/sso?name='.urlencode(User::$info['first_name'].' '.User::$info['last_name']).'&email='.urlencode(User::$info['email']).'&amp;timestamp='.(time()).'&hash='.hash_hmac('md5',(User::$info['first_name'].' '.User::$info['last_name'].User::$info['email'].(time())),$CFG->helpdesk_key));