<?php
include '../lib/common.php';

$page1 = (!empty($_REQUEST['page'])) ? ereg_replace("[^0-9]", "",$_REQUEST['page']) : false;
$bypass = !empty($_REQUEST['bypass']);

API::add('News','get',array(1));
$query = API::send();
$total = $query['News']['get']['results'][0];

API::add('News','get',array(false,$page1,10));
API::add('Transactions','pagination',array('news.php',$page1,$total,10,5,false));
$query = API::send();

$news = $query['News']['get']['results'][0];
$pagination = $query['Transactions']['pagination']['results'][0];

$page_title = Lang::string('news');

if (!$bypass) {
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
        <p class="explain"><?= Lang::string('news-explain') ?></p>
        <div class="clearfix mar_top3"></div>
        <div id="filters_area">
	        <? 
}
	        if ($news) {
				$i = 1;
				$c = count($news);
				foreach ($news as $news_item) {
				?>
			<div class="blog_post">
				<div class="blog_postcontent">
					<div class="post_info_content_small nomargin">
						<a class="date" href="#" onclick="return false;"><strong><?= date('j',strtotime($news_item['date']))?></strong><i><?= Lang::string(strtolower(date('M',strtotime($news_item['date'])))) ?></i></a>
						<div class="postcontent">	
							<h3><a href="#" onclick="return false;"><?= $news_item['title_'.$CFG->language] ?></a></h3>
							<div class="posttext"><?= $news_item['content_'.$CFG->language] ?></div>
						</div>
					</div>
				</div>
			</div>
			<?= ($c != $i) ? '<div class="clearfix divider_line3"></div>' : '' ?>
				<?
				$i++;
				}
			}
	        ?>
	        <?= '<div class="clearfix mar_top2"></div>'.$pagination; ?>
<? if (!$bypass) { ?>
        </div>
    </div>
	<div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>
<? } ?>