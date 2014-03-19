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
}

$currencies = $CFG->currencies;
$on_hold = SiteUser::getOnHold();
$available = SiteUser::getAvailable();
$volume = SiteUser::getVolume();
$fee_bracket = DB::getRecord('fee_schedule',User::$info['fee_schedule'],0,1);

$page_title = Lang::string('account');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.html"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<div class="testimonials-4">
            <h2><?= Lang::string('account-welcome') ?>, <strong><?= User::$info['first_name'].' '.User::$info['last_name'] ?></strong></h2>
			<? 
			if (User::$info['verified_authy'] != 'Y') {
				echo '<div class="notice"><div class="message-box-wrap">'.Lang::string('account-security-notify').'</div></div>';
			}
			?>
			<div class="mar_top2"></div>
			<ul class="list_empty">
				<li><a href="buy-sell.php" class="but_user"><i class="fa fa-btc fa-lg"></i> <?= Lang::string('buy-sell') ?></a></li>
				<li><a href="deposit.php" class="but_user"><i class="fa fa-download fa-lg"></i> <?= Lang::string('deposit') ?></a></li>
				<li><a href="withdraw.php" class="but_user"><i class="fa fa-upload fa-lg"></i> <?= Lang::string('withdraw') ?></a></li>
			</ul>
			<div class="clear"></div>
            <div class="content">
            	<h3 class="section_label">
                    <span class="left"><i class="fa fa-check fa-2x"></i></span>
                    <span class="right"><?= Lang::string('account-balance') ?></span>
                </h3>
                <div class="clear"></div>
                <div class="balances">
                	<div class="one_half">
                		<div class="label">BTC <?= Lang::string('account-available') ?></div>
                		<div class="amount"><?= number_format($available['BTC'],8) ?></div>
                	</div>
	            	<?
	            	$i = 2;
	            	foreach ($available as $currency => $balance) {
						if ($currency == 'BTC')
							continue;
						
						$last_class = ($i % 2 == 0) ? 'last' : '';
					?>
					<div class="one_half <?= $last_class ?>">
                		<div class="label"><?= $currency.' '.Lang::string('account-available') ?>:</div>
                		<div class="amount"><?= $CFG->currencies[$currency]['fa_symbol'].number_format($balance,2) ?></div>
                	</div>
					<?
						$i++;
					} 
	            	?>
	            	<div class="clear"></div>
            	</div>
            	<div class="clear"></div>
            </div>
            <div class="mar_top3"></div>
            <div class="clear"></div>
            <div class="content">
            	<h3 class="section_label">
                    <span class="left"><i class="fa fa-exclamation fa-2x"></i></span>
                    <span class="right"><?= Lang::string('account-on-hold') ?></span>
                </h3>
                <div class="clear"></div>
                <div class="balances">
	            	<?
	            	if ($on_hold) {
	            		foreach ($on_hold as $currency => $balance) {
					?>
					<div class="one_half">
                		<div class="label"><?= $currency.' '.Lang::string('account-on-order') ?>:</div>
                		<div class="amount"><?= $CFG->currencies[$currency]['fa_symbol'].number_format($balance['order'],2) ?></div>
                	</div>
                	<div class="one_half last">
                		<div class="label"><?= $currency.' '.Lang::string('account-on-widthdrawal') ?>:</div>
                		<div class="amount"><?= $CFG->currencies[$currency]['fa_symbol'].number_format($balance['withdrawal'],2) ?></div>
                	</div>
					<?
						} 
					}
					else {
						echo Lang::string('account-nothing-on-hold');
					}
	            	?>
	            	<div class="clear"></div>
            	</div>
            	<div class="clear"></div>
            </div>
            <div class="mar_top3"></div>
            <div class="content1">
	            <h3 class="section_label">
					<span class="left"><i class="fa fa-info fa-2x"></i></span>
					<span class="right"><?= Lang::string('account-fee-structure') ?></span>
				</h3>
				<div class="clear"></div>
				<div class="balances">
					<div class="one_half">
						<div class="label"><?= Lang::string('account-fee-bracket') ?>:</div>
						<div class="amount"><?= $fee_bracket['fee'] ?>% <a title="<?= Lang::string('account-view-fee-schedule') ?>" href="fee-schedule.php"><i class="fa fa-question-circle"></i></a></div>
	                </div>
	                <div class="one_half last">
	                	<div class="label"><?= Lang::string('account-30-day-vol') ?>:</div>
	                	<div class="amount">$<?= number_format($volume,2) ?></div>
	                </div>
		            <div class="clear"></div>
	            </div>
	            <div class="clear"></div>
            </div>
            <div class="mar_top8"></div>
        </div>
	</div>
</div>
<? include 'includes/foot.php'; ?>