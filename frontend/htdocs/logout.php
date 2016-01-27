<?php
include '../lib/common.php';

if (!$_REQUEST['log_out'])
	Link::redirect('index.php');

API::add('Content','getRecord',array('logged-out'));
$query = API::send();

$page_title = Lang::string('log-out');
$content = $query['Content']['getRecord']['results'][0];

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="news.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_topics.php'; ?>
	<div class="content_right">
		<h2><?= $content['title'] ?></h2>
        <div class="text"><?= $content['content'] ?></div>
    </div>
	<div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>