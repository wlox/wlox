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

$account1 = ereg_replace("[^0-9]", "",$_REQUEST['account']);
$currency1 = ereg_replace("[^0-9]", "",$_REQUEST['currency']);
$description1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['description']);
$remove_id1 = ereg_replace("[^0-9]", "",$_REQUEST['remove_id']);
$bank_accounts = BankAccounts::get(User::$info['id']);

if ($account1 > 0) {
	$exists = BankAccounts::find($account1);
	if ($bank_accounts[$account1])
		Errors::add(Lang::string('bank-accounts-already-exists'));
	elseif ($exists)
		Errors::add(Lang::string('bank-accounts-already-associated'));
	if (strlen($account1) < 5)
		Errors::add(Lang::string('bank-accounts-invalid-number'));
	if (!($currency1 > 0))
		Errors::add(Lang::string('bank-accounts-no-currency'));
	
	if (!is_array(Errors::$errors)) {
		if (!$description1)
			$description1 = Lang::string('bank-accounts-crypto-label');
		
		$_REQUEST['action'] = false;
		db_insert('bank_accounts',array('account_number'=>$account1,'currency'=>$currency1,'description'=>$description1,'site_user'=>User::$info['id']));
		Messages::add(Lang::string('bank-accounts-added-message'));
		$bank_accounts = BankAccounts::get(User::$info['id']);
	}
}

if ($remove_id1 > 0) {
	if ($bank_accounts[$account1])
		Errors::add(Lang::string('bank-accounts-remove-error'));
	
	if (!is_array(Errors::$errors)) {
		db_delete('bank_accounts',$remove_id1);
		Messages::add(Lang::string('bank-accounts-removed-message'));
		$bank_accounts = BankAccounts::get(User::$info['id']);
	}
}

if (!$_REQUEST['action']) {
	$content = Content::getRecord('bank-accounts');
}
elseif ($_REQUEST['action'] == 'add') {
	$content = Content::getRecord('bank-accounts-add');
}

$page_title = Lang::string('bank-accounts');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.html"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="bank-accounts.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<? if (!$_REQUEST['action']) { ?>
    	<div class="text"><?= $content['content'] ?></div>
    	<div class="clearfix mar_top2"></div>
    	<div class="clear"></div>
    	<? Errors::display(); ?>
    	<? Messages::display(); ?>
    	<div class="clear"></div>
    	<ul class="list_empty">
			<li><a href="bank-accounts.php?action=add" class="but_user"><i class="fa fa-plus fa-lg"></i> <?= Lang::string('bank-accounts-add') ?></a></li>
		</ul>
    	<div class="table-style">
    		<table class="table-list trades">
				<tr>
					<th><?= Lang::string('bank-accounts-account') ?></th>
					<th><?= Lang::string('bank-accounts-currency') ?></th>
					<th><?= Lang::string('bank-accounts-description') ?></th>
					<th></th>
				</tr>
				<? 
				if ($bank_accounts) {
					foreach ($bank_accounts as $account) {
						$currency = DB::getRecord('currencies',$account['currency'],0,1);
				?>
				<tr>
					<td><?= $account['account_number'] ?></td>
					<td><?= $currency['currency'] ?></td>
					<td><?= $account['description'] ?></td>
					<td><a href="bank-accounts.php?remove_id=<?= $account['id'] ?>"><i class="fa fa-minus-circle"></i> <?= Lang::string('bank-accounts-remove') ?></a></td>
				</tr>
				<?
					}
				}
				else {
					echo '<tr><td colspan="4">'.Lang::string('bank-accounts-no').'</td></tr>';
				}
				?>
			</table>
		</div>
		<? } elseif ($_REQUEST['action'] == 'add') { ?>
		<div class="testimonials-4">
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? Errors::display(); ?>
			<form id="add_bank_account" action="bank-accounts.php" method="POST">
				<input type="hidden" name="action" value="add" />
				<div class="buyform">
					<div class="content">
		            	<h3 class="section_label">
		                    <span class="left"><i class="fa fa-plus fa-2x"></i></span>
		                    <span class="right"><?= Lang::string('bank-accounts-add-label') ?></span>
		                </h3>
		                <div class="clear"></div>
		                <div class="one_half">
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="param">
								<label for="account"><?= Lang::string('bank-accounts-account-cc') ?></label>
								<input name="account" id="account" type="text" value="<?= $account1 ?>" />
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="description"><?= Lang::string('bank-accounts-description') ?></label>
								<input name="description" id="description" type="text" value="<?= $description1 ?>" />
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="currency"><?= Lang::string('bank-accounts-currency') ?></label>
								<select id="currency" name="currency">
								<? 
								if ($CFG->currencies) {
									foreach ($CFG->currencies as $currency) {
										echo '<option '.(($currency['id'] == $currency1 || (!$currency1 && $currency['currency'] == 'USD')) ? 'selected="selected"' : '').' value="'.$currency['id'].'">'.$currency['currency'].'</option>';
									}
								}
								?>
								</select>
								<div class="clear"></div>
							</div>
							 <div class="mar_top2"></div>
							 <ul class="list_empty">
								<li><input type="submit" name="submit" value="<?= Lang::string('bank-accounts-add-account') ?>" class="but_user" /></li>
							</ul>
		                </div>
		                <div class="clear"></div>
		            </div>
	            </div>
            </form>
		</div>
		<? } ?>
    </div>
	<div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>