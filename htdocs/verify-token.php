<?php
include '../cfg/cfg.php';

if (!User::isLoggedIn()) {
	Link::redirect('login.php');
	exit;
}

$token1 = ereg_replace("[^0-9]", "",$_REQUEST['token']);
$dont_ask1 = $_REQUEST['dont_ask'];

if ($_REQUEST['step'] == 1) {
	if (!($token1 > 0))
		Errors::add(Lang::string('security-no-token'));
	
	if (!is_array(Errors::$errors)) {
		$authy_id = User::$info['authy_id'];
		$response = shell_exec('curl "https://api.authy.com/protected/json/verify/'.$token1.'/'.$authy_id.'?api_key='.$CFG->authy_api_key.'"');
		$response1 = json_decode($response,true);
	
		if (!$response || !is_array($response1))
			Errors::merge(Lang::string('security-com-error'));
	
		if ($response1['success'] == 'false')
			Errors::merge($response1['errors']);
	
		if (!is_array(Errors::$errors)) {
			if ($dont_ask1 > 0)
				db_update('site_users',User::$info['id'],array('dont_ask_30_days'=>'Y','dont_ask_date'=>date('Y-m-d H:i:s')));
			
			$_SESSION['token_verified'] = 1;
			Link::redirect('account.php');
			exit;
		}
	}
}

$content = Content::getRecord('security-token-login');
$page_title = Lang::string('verify-token');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.html"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="verify-token.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<div class="content_right">
		<div class="testimonials-4">
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Errors::display(); ?>
			<form id="enable_tfa" action="verify-token.php" method="POST">
				<input type="hidden" name="step" value="1" />
				<div class="buyform">
					<div class="content">
		            	<h3 class="section_label">
		                    <span class="left"><i class="fa fa-check fa-2x"></i></span>
		                    <span class="right"><?= Lang::string('security-enter-token') ?></span>
		                </h3>
		                <div class="one_half">
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="param">
								<label for="authy-token"><?= Lang::string('security-token') ?></label>
								<input name="token" id="authy-token" type="text" value="<?= $token1 ?>" />
								<div class="clear"></div>
							</div>
							<div class="param lessbottom">
								<input class="checkbox" name="dont_ask" id="dont_ask" type="checkbox" value="1" <?= ($dont_ask1) ? 'checked="checked"' : '' ?> />
								<label for="dont_ask"><?= Lang::string('security-dont-ask') ?></label>
								<div class="clear"></div>
							</div>
							 <div class="mar_top2"></div>
							 <ul class="list_empty">
								<li><input type="submit" name="submit" value="<?= Lang::string('security-validate') ?>" class="but_user" /></li>
							</ul>
		                </div>
		                <div class="clear"></div>
		            </div>
	            </div>
            </form>
		</div>
	</div>
	<div class="mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>