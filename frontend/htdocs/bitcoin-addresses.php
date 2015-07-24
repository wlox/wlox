<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

API::add('BitcoinAddresses','get',array(false,false,30,1));
API::add('Content','getRecord',array('bitcoin-addresses'));
$query = API::send();

$bitcoin_addresses = $query['BitcoinAddresses']['get']['results'][0];
$content = $query['Content']['getRecord']['results'][0];
$page_title = Lang::string('bitcoin-addresses');

if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'add' && $_SESSION["btc_uniq"] == $_REQUEST['uniq']) {
	if (strtotime($bitcoin_addresses[0]['date']) >= strtotime('-1 day'))
		Errors::add(Lang::string('bitcoin-addresses-too-soon'));
	
	if (!is_array(Errors::$errors)) {
		API::add('BitcoinAddresses','getNew');
		API::add('BitcoinAddresses','get',array(false,false,30,1));
		$query = API::send();
		$bitcoin_addresses = $query['BitcoinAddresses']['get']['results'][0];
		
		Messages::add(Lang::string('bitcoin-addresses-added'));
	}
}

$_SESSION["btc_uniq"] = md5(uniqid(mt_rand(),true));
include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="bitcoin-addresses.php"><?= $page_title ?></a></div>
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
			<li><a href="bitcoin-addresses.php?action=add&uniq=<?= $_SESSION["btc_uniq"] ?>" class="but_user"><i class="fa fa-plus fa-lg"></i> <?= Lang::string('bitcoin-addresses-add') ?></a></li>
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