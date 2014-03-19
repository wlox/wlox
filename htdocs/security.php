<?php
include '../cfg/cfg.php';

if (User::isLoggedIn()) {
	if (User::$info['verified_authy'] == 'Y' && !($_SESSION['token_verified'] > 0))
		Link::redirect('verify-token.php');
	elseif (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
		Link::redirect('settings.php');
}
else {
	Link::redirect('login.php');
	exit;
}

$cell1 = ($_REQUEST['cell']) ? ereg_replace("[^0-9]", "",$_REQUEST['cell']) : User::$info['tel'];
$country_code1 = ($_REQUEST['country_code']) ? ereg_replace("[^0-9]", "",$_REQUEST['country_code']) : User::$info['country_code'];
$token1 = ereg_replace("[^0-9]", "",$_REQUEST['token']);

if ($_REQUEST['step'] == 1) {
	if (!($cell1 > 0))
		Errors::add(Lang::string('security-no-cell'));
	if (!($country_code1 > 0))
		Errors::add(Lang::string('security-no-cc'));
	
	if (!is_array(Errors::$errors)) {
		$response = shell_exec("
		curl https://api.authy.com/protected/json/users/new?api_key=$CFG->authy_api_key \
		-d user[email]='".User::$info['email']."' \
		-d user[cellphone]='$cell1' \
		-d user[country_code]='$country_code1'");
		$response1 = json_decode($response,true);
		$authy_id = $response1['user']['id'];
		
		if (!$response || !is_array($response1))
			Errors::merge(Lang::string('security-com-error'));
		
		if ($response1['success'] == 'false')
			Errors::merge($response1['errors']);
		
		if (!is_array(Errors::$errors)) {
			if ($_REQUEST['send_sms']) {
				$response = shell_exec('curl https://api.authy.com/protected/json/sms/'.$response1['user']['id'].'?force=true&api_key='.$CFG->authy_api_key);
				$response1 = json_decode($response,true);
				
				if (!$response || !is_array($response1))
					Errors::merge(Lang::string('security-com-error'));
				elseif ($response1['success'] == 'false')
					Errors::merge($response1['errors']);
				else
					$using_sms = 'Y';
			}
			else
				$using_sms = 'N';
			
			if (!is_array(Errors::$errors)) {
				db_update('site_users',User::$info['id'],array('tel'=>$cell1,'country_code'=>$country_code1,'authy_requested'=>'Y','verified_authy'=>'N','authy_id'=>$authy_id,'using_sms'=>$using_sms));
				$step1 = true;
			}
		}
	}
}
elseif ($_REQUEST['step'] == 2) {
	if (!($token1 > 0))
		Errors::add(Lang::string('security-no-token'));
	
	if (!is_array(Errors::$errors)) {
		$authy_id = ($response1['user']['id'] > 0) ? $response1['user']['id'] : User::$info['authy_id'];
		$response = shell_exec('curl "https://api.authy.com/protected/json/verify/'.$token1.'/'.$authy_id.'?api_key='.$CFG->authy_api_key.'"');
		$response1 = json_decode($response,true);
	
		if (!$response || !is_array($response1))
			Errors::merge(Lang::string('security-com-error'));
	
		if ($response1['success'] == 'false')
			Errors::merge($response1['errors']);
	
		if (!is_array(Errors::$errors)) {
			Messages::add(Lang::string('security-success-message'));
			db_update('site_users',User::$info['id'],array('verified_authy'=>'Y'));
			$step2 = true;
		}
	}
}

if ((User::$info['verified_authy'] == 'Y' || $step2) && !$_REQUEST['change'])
	$content = Content::getRecord('security-setup');
elseif ((User::$info['authy_requested'] == 'Y' || $step1) && !$_REQUEST['change'])
	$content = Content::getRecord('security-token');
else
	$content = Content::getRecord('security-explain');

$page_title = Lang::string('security');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.html"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="security.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<div class="testimonials-4">
		<? if ((User::$info['verified_authy'] == 'Y' || $step2) && !$_REQUEST['change'] && !$step1) { ?>
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Messages::display(); ?>
			<ul class="list_empty">
				<li><div class="number">+<?= User::$info['country_code']?> <?= User::$info['tel']?></div></li>
				<li><a class="item_label" href="javascript:return false;"><?= Lang::string('security-verified') ?></a></li>
			</ul>
			<a class="but_user" href="security.php?change=1"><?= Lang::string('security-change-number') ?></a>
		<? } elseif ((User::$info['authy_requested'] == 'Y' || $step1) && !$_REQUEST['change']) { ?>
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Errors::display(); ?>
			<form id="enable_tfa" action="security.php" method="POST">
				<input type="hidden" name="step" value="2" />
				<div class="buyform">
					<div class="content">
		            	<h3 class="section_label">
		                    <span class="left"><i class="fa fa-mobile fa-2x"></i></span>
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
							 <div class="mar_top2"></div>
							 <ul class="list_empty">
								<li><input type="submit" name="submit" value="<?= Lang::string('security-validate') ?>" class="but_user" /></li>
							</ul>
		                </div>
		                <div class="clear"></div>
		            </div>
	            </div>
            </form>
		<? } else { ?>
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Errors::display(); ?>
			<form name="start_auth" id="enable_tfa" action="security.php" method="POST">
				<input type="hidden" name="step" value="1" />
				<input type="hidden" id="send_sms" name="send_sms" value="" />
				<div class="buyform">
					<div class="content">
		            	<h3 class="section_label">
		                    <span class="left"><i class="fa fa-mobile fa-2x"></i></span>
		                    <span class="right"><?= Lang::string('security-enable-two-factor') ?></span>
		                </h3>
		                <div class="one_half">
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="param">
								<label for="authy-countries"><?= Lang::string('security-country') ?></label>
								<select name="country_code" id="authy-countries">
								<? 
								if ($country_code1 > 0) {
									echo '<option value="'.$country_code1.'" selected="selected"></option>';
								}
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="authy-cellphone"><?= Lang::string('security-cell') ?></label>
								<input name="cell" id="authy-cellphone" type="text" value="<?= $cell1 ?>" />
								<div class="clear"></div>
							</div>
							 <div class="mar_top2"></div>
							 <ul class="list_empty">
								<li><input type="submit" name="submit" value="<?= Lang::string('security-enable') ?>" class="but_user" /></li>
								<li><input type="submit" name="sms" value="<?= Lang::string('security-send-sms') ?>" class="but_user" /></li>
							</ul>
		                </div>
		                <div class="clear"></div>
		            </div>
	            </div>
            </form>
		<? } ?>
		</div>
		<div class="mar_top8"></div>
	</div>
</div>
<? include 'includes/foot.php'; ?>