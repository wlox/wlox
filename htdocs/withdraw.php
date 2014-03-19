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

$page1 = ereg_replace("[^0-9]", "",$_REQUEST['page']);
$btc_address1 = ereg_replace("/[^\da-z]/i", "",$_REQUEST['btc_address']);
$btc_amount1 = ($_REQUEST['btc_amount'] > 0) ? ereg_replace("[^0-9.]", "",$_REQUEST['btc_amount']) : 0;
$account1 = ereg_replace("[^0-9]", "",$_REQUEST['account']);
$fiat_amount1 = ($_REQUEST['fiat_amount'] > 0) ? ereg_replace("[^0-9.]", "",$_REQUEST['fiat_amount']) : 0;

$bank_accounts = BankAccounts::get(User::$info['id']);
if ($account1 > 0) {
	$bank_account = DB::getRecord('bank_accounts',$account1,0,1);
}
elseif ($bank_accounts) {
	$key = key($bank_accounts);
	$bank_account = $bank_accounts[$key];	
}
$currency_info = DB::getRecord('currencies',$bank_account['currency']);
$currency1 = $currency_info['currency'];
$user_available = SiteUser::getAvailable();
$bank_account_currency = DB::getRecord('currencies',$bank_account['currency'],0,1);


if ($_REQUEST['request_2fa']) {
	$token1 = ereg_replace("[^0-9]", "",$_REQUEST['token']);

	if ($_REQUEST['send_sms']) {
		$sent_sms = SiteUser::sendSMS();
		if ($sent_sms)
			Messages::add(Lang::string('withdraw-sms-sent'));

		$request_2fa = true;
	}
	else {
		$did_2fa = SiteUser::confirmToken($token1);
		if (!$did_2fa)
			$request_2fa = true;
	}
}
elseif ($_REQUEST['email_auth']) {
	$request_id = Encryption::decrypt(urldecode($_REQUEST['authcode']));
	if ($request_id > 0) {
		db_update('requests',$request_id,array('request_status'=>$CFG->request_pending_id));
		Link::redirect('withdraw.php?message=withdraw-2fa-success');
	}
}


if ($_REQUEST['bitcoins']) {
	if (!($btc_amount1 > 0))
		Errors::add(Lang::string('withdraw-amount-zero'));
	if ($btc_amount1 > $user_available['BTC'])
		Errors::add(Lang::string('withdraw-too-much'));
	
	require_once('../lib/easybitcoin.php');
	$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
	$response = $bitcoin->validateaddress($btc_address1);

	if (!$response['isvalid'] || !is_array($response))
		Errors::add(Lang::string('withdraw-address-invalid'));
	
	if (!is_array(Errors::$errors)) {
		if (User::$info['confirm_withdrawal_2fa_btc'] == 'Y' && !$did_2fa) {
			$request_2fa = true;
				
			if (User::$info['using_sms'] == 'Y' && !$sent_sms) {
				$sent_sms = SiteUser::sendSMS();
				if ($sent_sms)
					Messages::add(Lang::string('withdraw-sms-sent'));
			}
		}
		elseif (User::$info['confirm_withdrawal_email_btc'] == 'Y' && !$did_2fa) {
			$request_id = db_insert('requests',array('date'=>date('Y-m-d H:i:s'),'site_user'=>User::$info['id'],'currency'=>$CFG->btc_currency_id,'amount'=>$btc_amount1,'description'=>$CFG->withdraw_btc_desc,'request_status'=>$CFG->request_awaiting_id,'request_type'=>$CFG->request_withdrawal_id,'send_address'=>$btc_address1));
			if ($request_id > 0) {
				$status = DB::getRecord('status',1,0,1);
				$pending_withdrawals = $status['pending_withdrawals'];
				db_update('status',1,array('pending_withdrawals'=>($status['pending_withdrawals'] + $btc_amount1)));
				
				$vars = User::$info;
				$vars['authcode'] = urlencode(Encryption::encrypt($request_id));
					
				$email = SiteEmail::getRecord('request-auth');
				Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$vars);
				Link::redirect('withdraw.php?notice=email');
			}
		}
		elseif (!$request_2fa) {
			$request_id = db_insert('requests',array('date'=>date('Y-m-d H:i:s'),'site_user'=>User::$info['id'],'currency'=>$CFG->btc_currency_id,'amount'=>$btc_amount1,'description'=>$CFG->withdraw_btc_desc,'request_status'=>$CFG->request_pending_id,'request_type'=>$CFG->request_withdrawal_id,'send_address'=>$btc_address1));
			if ($request_id) {
				$status = DB::getRecord('status',1,0,1);
				$pending_withdrawals = $status['pending_withdrawals'];
				db_update('status',1,array('pending_withdrawals'=>($status['pending_withdrawals'] + $btc_amount1)));
				
				if ($did_2fa) {
					Link::redirect('withdraw.php?message=withdraw-2fa-success');
				}
				else {
					Link::redirect('withdraw.php?message=withdraw-success');
				}
			}
		}
	}
}
elseif ($_REQUEST['fiat']) {
	if (!($account1 > 0))
		Errors::add(Lang::string('withdraw-no-account'));
	if (!is_array($bank_account))
		Errors::add(Lang::string('withdraw-account-not-found'));
	if (!($fiat_amount1 > 0))
		Errors::add(Lang::string('withdraw-amount-zero'));
	if (!$bank_accounts[$bank_account['account_number']])
		Errors::add(Lang::string('withdraw-account-not-associated'));
	if ($fiat_amount1 > $user_available[strtoupper($currency1)])
		Errors::add(Lang::string('withdraw-too-much'));
		
	if (!is_array(Errors::$errors)) {
		if (User::$info['confirm_withdrawal_2fa_bank'] == 'Y' && !$did_2fa) {
			$request_2fa = true;
			
			if (User::$info['using_sms'] == 'Y' && !$sent_sms) {
				$sent_sms = SiteUser::sendSMS();
				if ($sent_sms)
					Messages::add(Lang::string('withdraw-sms-sent'));
			}
		}
		elseif (User::$info['confirm_withdrawal_email_bank'] == 'Y' && !$did_2fa) {
			$request_id = db_insert('requests',array('date'=>date('Y-m-d H:i:s'),'site_user'=>User::$info['id'],'currency'=>$bank_account['currency'],'amount'=>$fiat_amount1,'description'=>$CFG->withdraw_fiat_desc,'request_status'=>$CFG->request_awaiting_id,'request_type'=>$CFG->request_withdrawal_id,'account'=>$bank_account['account_number']));
			if ($request_id > 0) {
				$vars = User::$info;
				$vars['authcode'] = urlencode(Encryption::encrypt($request_id));
			
				$email = SiteEmail::getRecord('request-auth');
				Email::send($CFG->form_email,User::$info['email'],$email['title'],$CFG->form_email_from,false,$email['content'],$vars);
				Link::redirect('withdraw.php?notice=email');
			}
		}
		elseif (!$request_2fa) {
			$request_id = db_insert('requests',array('date'=>date('Y-m-d H:i:s'),'site_user'=>User::$info['id'],'currency'=>$bank_account['currency'],'amount'=>$fiat_amount1,'description'=>$CFG->withdraw_fiat_desc,'request_status'=>$CFG->request_pending_id,'request_type'=>$CFG->request_withdrawal_id,'account'=>$bank_account['account_number']));
			
			if ($did_2fa) {
				Link::redirect('withdraw.php?message=withdraw-2fa-success');
			}
			else {
				Link::redirect('withdraw.php?message=withdraw-success');
			}
		}
	}
}

if ($_REQUEST['message'] == 'withdraw-2fa-success')
	Messages::add(Lang::string('withdraw-2fa-success'));
elseif ($_REQUEST['message'] == 'withdraw-success')
	Messages::add(Lang::string('withdraw-success'));
elseif ($_REQUEST['notice'] == 'email')
	$notice = Lang::string('withdraw-email-notice');

if (!$bank_accounts)
	$bank_instructions = Content::getRecord('deposit-no-bank');

$total = Requests::get(1,false,false,User::$info['id'],$CFG->request_withdrawal_id);
$requests = Requests::get(false,$page1,15,User::$info['id'],$CFG->request_withdrawal_id);
$pagination = Transactions::pagination('withdraw.php',$page1,$total,15,5,$CFG->pagination_label);

$page_title = Lang::string('withdraw');

if (!$_REQUEST['bypass']) {
	include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.html"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="withdraw.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<? Errors::display(); ?>
		<? Messages::display(); ?>
		<?= ($notice) ? '<div class="notice"><div class="message-box-wrap">'.$notice.'</div></div>' : '' ?>
		<div class="testimonials-4">
			<? if (!$request_2fa) { ?>
			<div class="one_half">
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-btc fa-2x"></i></span>
						<span class="right"><?= Lang::string('withdraw-bitcoins') ?></span>
					</h3>
					<div class="clear"></div>
					<form id="buy_form" action="withdraw.php" method="POST">
						<div class="buyform">
							<div class="spacer"></div>
							<div class="calc dotted">
								<div class="label"><?= Lang::string('sell-btc-available') ?></div>
								<div class="value"><?= number_format($user_available['BTC'],8) ?> BTC</div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="param">
								<label for="btc_address"><?= Lang::string('withdraw-send-to-address') ?></label>
								<input type="text" id="btc_address" name="btc_address" value="<?= $btc_address1 ?>" />
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="btc_amount"><?= Lang::string('withdraw-send-amount') ?></label>
								<input type="text" id="btc_amount" name="btc_amount" value="<?= number_format($btc_amount1,8) ?>" />
								<div class="qualify">BTC</div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<input type="hidden" name="bitcoins" value="1" />
							<input type="submit" name="submit" value="<?= Lang::string('withdraw-send-bitcoins') ?>" class="but_user" />
						</div>
					</form>
					<div class="clear"></div>
				</div>
			</div>
			<div class="one_half last">
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-usd fa-2x"></i></span>
						<span class="right"><?= Lang::string('withdraw-fiat') ?></span>
					</h3>
					<div class="clear"></div>
					<form id="buy_form" action="withdraw.php" method="POST">
						<div class="buyform">
							<div class="spacer"></div>
							<? if ($bank_accounts) { ?>
							<div class="calc dotted">
								<div class="label"><?= str_replace('[currency]','<span class="currency_label">'.$currency_info['currency'].'</span>',Lang::string('buy-fiat-available')) ?></div>
								<div class="value"><span class="currency_char"><?= $currency_info['fa_symbol'] ?></span><span id="user_available"><?= number_format($user_available[strtoupper($currency1)],2) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="param">
							<label for="withdraw_account"><?= Lang::string('withdraw-fiat-account') ?></label>
								<select id="withdraw_account" name="account">
								<?
								if ($bank_accounts) {
									foreach ($bank_accounts as $account) {
										echo '<option '.(($bank_account['id'] == $account['id']) ? 'selected="selected"' : '').' value="'.$account['id'].'">'.$account['account_number'].' - ('.$account['description'].')</option>';
									}
								}	
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="fiat_amount"><?= Lang::string('withdraw-amount') ?></label>
								<input type="text" id="fiat_amount" name="fiat_amount" value="<?= number_format($fiat_amount1,2) ?>" />
								<div class="qualify"><span class="currency_label"><?= $currency_info['currency'] ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<input type="hidden" name="fiat" value="1" />
							<input type="submit" name="submit" value="<?= Lang::string('withdraw-withdraw') ?>" class="but_user" />
							<? } else { ?>
							<div class="calc">
								<div class="text"><?= $bank_instructions['content'] ?></div>
								<div class="mar_top2"></div>
								<a class="item_label" href="bank-accounts.php"><i class="fa fa-cog"></i> <?= Lang::string('deposit-manage-bank-accounts') ?></a>
								<div class="clear"></div>
							</div>
							<? } ?>
						</div>
					</form>
				</div>
			</div>
			<? } else { ?>
			<div class="content">
				<h3 class="section_label">
					<span class="left"><i class="fa fa-mobile fa-2x"></i></span>
					<span class="right"><?= Lang::string('security-enter-token') ?></span>
				</h3>
				<form id="enable_tfa" action="withdraw.php" method="POST">
					<input type="hidden" name="request_2fa" value="1" />
					<input type="hidden" name="account" value="<?= $account1 ?>" />
					<input type="hidden" name="fiat_amount" value="<?= $fiat_amount1 ?>" />
					<input type="hidden" name="btc_address" value="<?= $btc_address1 ?>" />
					<input type="hidden" name="btc_amount" value="<?= $btc_amount1 ?>" />
					<input type="hidden" name="bitcoins" value="<?= ($_REQUEST['bitcoins']) ? '1' : '' ?>" />
					<input type="hidden" name="fiat" value="<?= ($_REQUEST['fiat']) ? '1' : '' ?>" />
					<div class="buyform">
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
								<? if (User::$info['using_sms'] == 'Y') { ?>
								<li><input type="submit" name="sms" value="<?= Lang::string('security-resend-sms') ?>" class="but_user" /></li>
								<? } ?>
							</ul>
						</div>
					</div>
				</form>
                <div class="clear"></div>
			</div>
			<? } ?>
		</div>
		<div class="mar_top3"></div>
		<div class="clear"></div>
		<h3><?= Lang::string('withdrawal-recent') ?></h3>
		<div id="filters_area">
<? } ?>
        	<div class="table-style">
        		<table class="table-list trades" id="bids_list">
        			<tr>
        				<th>ID</th>
        				<th><?= Lang::string('deposit-date') ?></th>
        				<th><?= Lang::string('deposit-description') ?></th>
        				<th><?= Lang::string('deposit-amount') ?></th>
        				<th><?= Lang::string('deposit-status') ?></th>
        			</tr>
        			<? 
        			if ($requests) {
						foreach ($requests as $request) {
							$class = ($request['request_status'] == $CFG->request_awaiting_id) ? 'class="waiting"' : '';
							echo '
					<tr '.$class.'>
						<td>'.$request['id'].'</td>
						<td><input type="hidden" class="localdate" value="'.(strtotime($request['date']) + $CFG->timezone_offset).'" /></td>
						<td>'.$request['description'].'</td>
						<td>'.(($request['fa_symbol'] == 'BTC') ? number_format($request['amount'],8).' '.$request['fa_symbol'] : $request['fa_symbol'].number_format($request['amount'],2)).'</td>
						<td>'.$request['status'].'</td>
					</tr>';
						}
					}
					else {
						echo '<tr><td colspan="5">'.Lang::string('withdraw-no').'</td></tr>';
					}
        			?>
        		</table>
			</div>
			<?= $pagination ?>
<? if (!$_REQUEST['bypass']) { ?>
		</div>
		<div class="mar_top5"></div>
	</div>
</div>
<? include 'includes/foot.php'; ?>
<? } ?>