<?php
include '../lib/common.php';

$page_title = Lang::string('404');

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= Lang::string('404') ?></h1></div>
        <div class="pagenation">&nbsp;<a href="<?= Lang::url('index.php') ?>"><?= Lang::string('home') ?></a> <i>/</i> <a href="404.php"><?= Lang::string('404') ?></a></div>
	</div>
</div>
<div class="container">
	<div class="content_fullwidth">

	<div class="error_pagenotfound">
    	
        <strong>404</strong>
        <br>
        
        <em><?= Lang::string('404-desc') ?></em>
        
        <div class="clearfix mar_top3"></div>
    	
        <a class="but_user" href="#"><i class="fa fa-arrow-circle-left fa-lg"></i>&nbsp; <?= Lang::string('404-back') ?></a>
        
    </div><!-- end error page notfound -->
        
</div>
	<div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>