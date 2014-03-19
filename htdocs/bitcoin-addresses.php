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

$bitcoin_addresses = BitcoinAddresses::get(false,false,30,User::$info['id']);

if ($_REQUEST['action'] == 'add') {
	if (strtotime($bitcoin_addresses[0]['date']) >= strtotime('-1 day'))
		Errors::add(Lang::string('bitcoin-addresses-too-soon'));
	
	if (!is_array(Errors::$errors)) {
		if (count($bitcoin_addresses) >= 10) {
			$c = count($bitcoin_addresses) - 1;
			db_update('bitcoin_addresses',$bitcoin_addresses[$c]['id'],array('site_user'=>'0'));
		}
		
		$unassigned = BitcoinAddresses::get(false,false,1,false,true);
		if ($unassigned) {
			db_update('bitcoin_addresses',$unassigned[0]['id'],array('site_user'=>User::$info['id'],'date'=>date('Y-m-d H:i:s')));
		}
		else {
			require_once('../lib/easybitcoin.php');
			$bitcoin = new Bitcoin($CFG->bitcoin_username,$CFG->bitcoin_passphrase,$CFG->bitcoin_host,$CFG->bitcoin_port,$CFG->bitcoin_protocol);
			$new_address = $bitcoin->getnewaddress($CFG->bitcoin_accountname);
			db_insert('bitcoin_addresses',array('address'=>$new_address,'site_user'=>User::$info['id'],'date'=>date('Y-m-d H:i:s')));
			$bitcoin_addresses = BitcoinAddresses::get(false,false,30,User::$info['id']);
		}
		
		Messages::add(Lang::string('bitcoin-addresses-added'));
	}
}

$content = Content::getRecord('bitcoin-addresses');
$page_title = Lang::string('bitcoin-addresses');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.html"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="bitcoin-addresses.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
    	<div class="text"><?= $content['content'] ?></div>
    	<div class="clearfix mar_top2"></div>
    	<div class="clear"></div>
    	<? Errors::display(); ?>
    	<? Messages::display(); ?>
    	<div class="clear"></div>
    	<ul class="list_empty">
			<li><a href="bitcoin-addresses.php?action=add" class="but_user"><i class="fa fa-plus fa-lg"></i> <?= Lang::string('bitcoin-addresses-add') ?></a></li>
		</ul>
		<div id="filters_area">
	    	<div class="table-style">
	    		<table class="table-list trades">
					<tr>
						<th><?= Lang::string('bitcoin-addresses-date') ?></th>
						<th><?= Lang::string('bitcoin-addresses-address') ?></th>
					</tr>
					<? 
					if ($bitcoin_addresses) {
						foreach ($bitcoin_addresses as $address) {
					?>
					<tr>
						<td><input type="hidden" class="localdate" value="<?= (strtotime($address['date']) + $CFG->timezone_offset) ?>" /></td>
						<td><?= $address['address'] ?></td>
					</tr>
					<?
						}
					}
					else {
						echo '<tr><td colspan="3">'.Lang::string('bitcoin-addresses-no').'</td></tr>';
					}
					?>
				</table>
			</div>
		</div>
    </div>
	<div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>