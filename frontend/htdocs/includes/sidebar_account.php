<div class="left_sidebar">
	<div class="sidebar_widget">
    	<div class="sidebar_title"><h3><?= Lang::string('account-nav') ?></h3></div>
		<ul class="arrows_list1">
			<li><a href="account.php" <?= ($CFG->self == 'account.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('account') ?></a></li>
			<li><a href="open-orders.php" <?= ($CFG->self == 'open-orders.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('open-orders') ?></a></li>
			<li><a href="transactions.php" <?= ($CFG->self == 'transactions.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('transactions') ?></a></li>
			<li><a href="security.php" <?= ($CFG->self == 'security.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('security') ?></a></li>
			<li><a href="settings.php" <?= ($CFG->self == 'settings.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('settings') ?></a></li>
			<li><a href="bank-accounts.php" <?= ($CFG->self == 'bank-accounts.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('bank-accounts') ?></a></li>
			<li><a href="bitcoin-addresses.php" <?= ($CFG->self == 'bitcoin-addresses.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('bitcoin-addresses') ?></a></li>
			<li><a href="history.php" <?= ($CFG->self == 'history.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('history') ?></a></li>
			<li><a href="api-access.php" <?= ($CFG->self == 'api-access.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('api-access') ?></a></li>
			<li><a href="logout.php?log_out=1&uniq=<?= $_SESSION["logout_uniq"] ?>"><i class="fa fa-angle-right"></i> <?= Lang::string('log-out') ?></a></li>
		</ul>
	</div>
	<div class="clearfix mar_top3"></div>
	<div class="sidebar_widget">
    	<div class="sidebar_title"><h3><?= Lang::string('account-functions') ?></h3></div>
		<ul class="arrows_list1">
			<li><a href="buy-sell.php" <?= ($CFG->self == 'buy-sell.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('buy-sell') ?></a></li>
			<li><a href="deposit.php" <?= ($CFG->self == 'deposit.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('deposit') ?></a></li>
			<li><a href="withdraw.php" <?= ($CFG->self == 'withdraw.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('withdraw') ?></a></li>
		</ul>
	</div>
	<div class="mar_top8"></div>
	<div class="clear"></div>
</div>