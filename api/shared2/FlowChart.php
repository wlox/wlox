<?php
class FlowChart {
	private $table,$steps;
	
	function __construct($step_table,$url_is_tab=false) {
		global $CFG;
		
		$this->table = $step_table;
		$this->url_is_tab = ($CFG->url == $step_table) ? $CFG->is_tab : $url_is_tab;

		if (!DB::tableExists($step_table)) {
			if (DB::createTable($step_table,array('name'=>'vchar','group_id'=>'int','user_id'=>'int','supervisor_id'=>'int','days_available'=>'int','step_order'=>'int'))) {
				Messages::add($CFG->table_created);
			}
		}
	}
	
	private function getSteps() {
		$this->steps = db_query_array("SELECT * FROM {$this->table} ORDER BY step_order,id");
	}
	
	function display() {
		global $CFG;
		
		self::getSteps();
		
		$HTML = '
		<div class="flow_chart">
			<table>
				<tr>
					<td class="bar">
						<a class="add_step" title="'.$CFG->add_step.'" onclick="flow_chart.addStep()"></a>
						<a class="save_order" title="'.$CFG->save_order.'" onclick="flow_chart.saveOrder()"></a>
					</td>
				</tr>
				<tr>
					<td class="navigator" id="navigator">
						<input type="hidden" id="table" value="'.$this->table.'" />
						<input type="hidden" id="current_url" value="'.$CFG->url.'" />
						<input type="hidden" id="is_tab" value="'.$CFG->is_tab.'" />';
		
		if ($this->steps) {
			foreach ($this->steps as $i => $step) {
				$HTML .= '
				<div class="step_container">
					<div class="ops">';
					if (User::permission(0,0,$this->table,false,$this->url_is_tab) > 0)
						$HTML .= Link::url($this->table,false,'id='.$step['id'].'&action=record&is_tab='.$this->url_is_tab,false,false,'edit_box','view',false,false,false,false,$CFG->view_hover_caption).' ';
					if (User::permission(0,0,$this->table,false,$this->url_is_tab) > 1)
						$HTML .= Link::url($this->table,false,'id='.$step['id'].'&action=form&is_tab='.$this->url_is_tab,false,false,'edit_box','edit',false,false,false,false,$CFG->edit_hover_caption).' ';
					if (User::permission(0,0,$this->table,false,$this->url_is_tab) > 1)	
						$HTML .= '<a href="#" title="'.$CFG->delete_hover_caption.'" onclick="flow_chart.deleteThis('.$step['id'].',\''.$this->table.'\',this)" class="delete"></a>';
				$HTML .= '
					</div>
					<div class="step" onclick="flow_chart.select(this,event);" ondblclick="flow_chart.open('.$step['id'].')"></div>
					<div class="desc" onclick="flow_chart.select(this,event);" ondblclick="flow_chart.open('.$step['id'].')">'.$step['name'].'</div>
					<input type="hidden" id="id" value="'.$step['id'].'" />
				</div>';
				if ($this->steps[$i+1]) {
					$HTML .= '<div class="step_link"></div>';
				}
			}
		}
		
		if (!$CFG->pm_editor) {
			$HTML .= '
			<script language="text/javascript">
				$("#navigator").click(function() {
					flow_chart.unselect();
				});
				flow_chart.startSortable();
			</script>
			';
		}
		
		$HTML .= '
						<div class="clear"></div>
					</td>
				</tr>
			</table>
		</div>
		';
		
		echo $HTML;
	}
}
?>