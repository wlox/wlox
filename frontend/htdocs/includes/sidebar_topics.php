<div class="left_sidebar">
	<div class="sidebar_widget">
    	<div class="sidebar_title"><h3><?= Lang::string('home-basic-nav') ?></h3></div>
		<ul class="arrows_list1">
			<li><a href="<?= Lang::url('what-are-bitcoins.php') ?>" <?= ($CFG->self == 'what-are-bitcoins.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('what-are-bitcoins') ?></a></li>
			<li><a href="<?= Lang::url('how-bitcoin-works.php') ?>" <?= ($CFG->self == 'how-bitcoin-works.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('how-bitcoin-works') ?></a></li>
			<li><a href="<?= Lang::url('about.php') ?>" <?= ($CFG->self == 'about.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('about') ?></a></li>
			<li><a href="<?= Lang::url('our-security.php') ?>" <?= ($CFG->self == 'our-security.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('our-security') ?></a></li>
			<li><a href="<?= Lang::url('buy-and-sell-bitcoin.php') ?>" <?= ($CFG->self == 'buy-and-sell-bitcoin.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('how-to-register') ?></a></li>
			<!-- li><a href="securing-account.php" <?= ($CFG->self == 'securing-account.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('securing-account') ?></a></li -->
			<li><a href="<?= Lang::url('reset_2fa.php') ?>" <?= ($CFG->self == 'reset_2fa.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('reset-2fa') ?></a></li>
			<!-- li><a href="funding-account.php" <?= ($CFG->self == 'funding-account.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('funding-account') ?></a></li -->
            <!-- li><a href="withdrawing-account.php" <?= ($CFG->self == 'withdrawing-account.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('withdrawing-account') ?></a></li -->
            <!-- li><a href="trading-bitcoins.php" <?= ($CFG->self == 'trading-bitcoins.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('trading-bitcoins') ?></a></li -->
            <li><a href="<?= Lang::url('fee-schedule.php') ?>" <?= ($CFG->self == 'fee-schedule.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('fee-schedule') ?></a></li>
            <li><a href="help.php" <?= ($CFG->self == 'help.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('help') ?></a></li>
            <li><a href="<?= Lang::url('press-releases.php') ?>" <?= ($CFG->self == 'press-releases.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('news') ?></a></li>
            <li><a href="<?= Lang::url('contact.php') ?>" <?= ($CFG->self == 'contact.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('contact') ?></a></li>
			<li><a href="api-docs.php" <?= ($CFG->self == 'api-docs.php') ? 'class="active"' : '' ?>><i class="fa fa-angle-right"></i> <?= Lang::string('api-docs') ?></a></li>
		</ul>
	</div>
</div>