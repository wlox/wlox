<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$page1 = (!empty($_REQUEST['page'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['page']) : false;
$bypass = !empty($_REQUEST['bypass']);

API::add('History','get',array(1,$page1));
$query = API::send();
$total = $query['History']['get']['results'][0];

API::add('History','get',array(false,$page1,30));
$query = API::send();

$history = $query['History']['get']['results'][0];
$pagination = Content::pagination('history.php',$page1,$total,30,5,false);

$page_title = Lang::string('history');

if (!$bypass) {
	include 'includes/head.php';

	?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="history.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_account.php'; ?>
	<div class="content_right">
		<? Messages::display(); ?>
		<div id="filters_area">
<? } ?>
        	<div class="table-style">
        		<table class="table-list trades" id="history_list">
        			<tr id="table_first">
        				<th><?= Lang::string('transactions-time') ?></th>
        				<th><?= Lang::string('transactions-type') ?></th>
        				<th><?= Lang::string('history-ip') ?></th>
        			</tr>
        			<? 
        			if ($history) {
						foreach ($history as $item) {
							echo '
					<tr>
						<td><input type="hidden" class="localdate" value="'.(strtotime($item['date'])/* + $CFG->timezone_offset*/).'" /></td>
						<td>'.$item['type'].(($item['request_currency']) ? ' ('.$item['request_currency'].')' : false).'</td>
						<td>'.(($item['ip']) ? $item['ip'] : 'N/A').'</td>
					</tr>';
						}
					}
        			?>
        		</table>
        		<?= $pagination ?>
			</div>
			<div class="clear"></div>
		</div>
<? if (!$bypass) { ?>
		<div class="mar_top5"></div>
	</div>
</div>
<? include 'includes/foot.php'; ?>
<? } ?>