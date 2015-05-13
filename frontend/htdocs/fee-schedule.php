<?php
include '../lib/common.php';

if (!empty($_REQUEST['currency']))
	$_SESSION['currency'] = strtolower(ereg_replace("[^a-zA-Z]", "",$_REQUEST['currency']));
elseif (empty($_SESSION['currency']))
	$_SESSION['currency'] = 'usd';

$currency1 = $_SESSION['currency'];

API::add('Content','getRecord',array('fee-schedule'));
API::add('FeeSchedule','get',array($currency1));
$query = API::send();

$content = $query['Content']['getRecord']['results'][0];
$page_title = $content['title'];
$fee_schedule = $query['FeeSchedule']['get']['results'][0];

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('fee-schedule') ?></a> <i>/</i> <a href="fee-schedule.php"><?= Lang::string('fee-schedule') ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_topics.php'; ?>
	<div class="content_right">
    	<div class="text"><?= $content['content'] ?></div>
    	<div class="clearfix mar_top2"></div>
    	<div class="table-style">
    		<table class="table-list trades">
				<tr>
					<th><?= Lang::string('fee-schedule-fee1') ?></th>
					<th><?= Lang::string('fee-schedule-fee') ?></th>
					<th>
						<?= Lang::string('fee-schedule-volume') ?>
						<span class="graph_options" style="margin-left:5px;">
							<span style="margin:0;float:none;display:inline;">
								<select id="fee_currency">
								<? 
								if ($CFG->currencies) {
									foreach ($CFG->currencies as $key => $currency) {
										if (is_numeric($key) || $currency['currency'] == 'BTC')
											continue;
										
										echo '<option '.(strtolower($currency['currency']) == $currency1 || (empty($currency1) && $currency['currency'] == 'USD') ? 'selected="selected"' : '' ).' name="'.strtolower($currency['currency']).'">'.$currency['currency'].'</option>';
									}
								}
								?>
								</select>
							</span>
						</span>
					</th>
					<th><?= Lang::string('fee-schedule-flc') ?></th>
				</tr>
				<? 
				if ($fee_schedule) {
					$last_fee1 = false;
					$last_btc = false;
					foreach ($fee_schedule as $fee) {
						$symbol = ($fee['to_usd'] > 0) ? '<' : '>';
						$from = ($fee['to_usd'] > 0) ? number_format($fee['to_usd'],0) : number_format($fee['from_usd'],0);
				?>
				<tr>
					<?= ($fee['fee1'] != $last_fee1) ? '<td>'.$fee['fee1'].'%</td>' : '<td class="inactive"></td>' ?>
					<td><?= $fee['fee'] ?>%</td>
					<td><?= $symbol.' '.$fee['fa_symbol'].$from ?></td>
					<?= ($fee['global_btc'] != $last_btc) ? '<td>'.number_format($fee['global_btc'],1).' BTC</td>' : '<td class="inactive"></td>' ?>
				</tr>
				<?
						$last_fee1 = $fee['fee1'];
						$last_btc = $fee['global_btc'];
					}
				}
				?>
			</table>
    	</div>
    </div>
	<div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>