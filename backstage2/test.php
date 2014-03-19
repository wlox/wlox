<?
include 'cfg.php';

$header = new Header();
$header->metaAuthor();
$header->metaDesc();
$header->metaKeywords();
$header->cssFile('../shared2/css/colorpicker.css');
$header->cssFile('../shared2/css/reset.css');
$header->cssFile('css/'.$CFG->skin.'/default.css','all');
$header->cssFile('css/'.$CFG->skin.'/default_ie6.css','all','IE 6');
$header->cssFile('css/'.$CFG->skin.'/default_ie7.css','all','IE 7');
$header->cssFile('css/'.$CFG->skin.'/default_ie8.css','all','IE 8');
$header->jsFile('../shared2/js/jquery-1.4.2.min.js');
$header->jsFile('../shared2/js/jquery-ui-1.8.5.custom.min.js');
$header->jsFile('../shared2/js/ajax.js');
$header->jsFile('../shared2/js/calendar.js');
$header->jsFile('../shared2/js/colorpicker.js');
$header->jsFile('../shared2/js/comments.js');
$header->jsFile('../shared2/js/form.js');
$header->jsFile('../shared2/js/file_manager.js');
$header->jsFile('../shared2/js/flow_chart.js');
$header->jsFile('../shared2/js/gallery.js');
$header->jsFile('../shared2/js/grid.js');
$header->jsFile('../shared2/js/multi_list.js');
$header->jsFile('../shared2/js/popups.js');
$header->jsFile('../shared2/js/page_maker.js');
$header->jsFile('../shared2/js/permissions.js');
$header->jsFile('../shared2/js/swfupload.js');
$header->jsFile('../shared2/js/jquery.swfupload.js');
$header->jsFile('js/Ops.js');
$header->display();
?>

<div class="haybob">
<div class="bob">This is bob</div>
</div>
<div class="other">
<ul style="float:left" class="bobdrop">
	<li style="float:left">Habob1</li>
	<li style="float:left">Habob2</li>
	<li style="float:left">Habob3</li>
	<li style="float:left">Habob4</li>
	<li style="float:left">Habob5</li>
</ul>
</div>

<script type="text/javascript">
$(".bob").draggable({
	revert: true,
	cursor: "move",
	opacity: 1,
	helper: 'clone',
	connectToSortable: '.bobdrop',
	appendTo:'body'
});

$(".bobdrop").sortable({
	revert: true,
	cursor: "move",
	opacity: 0.7
});
</script>



</body></html>