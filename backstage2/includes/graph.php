<?php
if ($_REQUEST['mode'] == 'graph' || $_REQUEST['mode'] == 'graph_line') {
	include '../lib/phpgraphlib.php';

	$graph = new PHPGraphLib(1120,600);
	$graph->addData(unserialize($_REQUEST['graph_data']));
	
	if ($_REQUEST['mode'] == 'graph_line') {
		$graph->setBars(false);
		$graph->setLine(true);
		$graph->setLineColor();
	}
	else {
		$graph->setGradient();
	}
	
	$graph->setLegend(true);
	$graph->setLegendTitle(unserialize($_REQUEST['titles']));
	$graph->createGraph();
}
elseif ($_REQUEST['mode'] == 'graph_pie') {
	include '../lib/phpgraphlib.php';
	include '../lib/phpgraphlib_pie.php';
	
	$graph = new PHPGraphLibPie(1120,600);
	$graph->addData(unserialize($_REQUEST['graph_data'])); 
	$graph->setLabelTextColor("50,50,50");
	$graph->createGraph();
}
?>