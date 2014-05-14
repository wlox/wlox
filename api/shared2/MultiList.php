<?php
class MultiList {
	private $class,$dragdrop,$tables,$categories,$rootname,$row_end_button,$levels,$j;
	static $i;
		
	function __construct($class=false,$dragdrop=true,$rootname=false,$row_end_button=true) {
		$this->class = $class;
		$this->dragdrop = $dragdrop;
		$this->rootname = $rootname;
		$this->row_end_button = ($row_end_button == true) ? $CFG->row_end_button : $row_end_button;
		$this->i = ($this->i > 0) ? $this->i : 1;
		$this->j = 1;
		$this->levels = array();
	}
	
	function addTable($table,$table_fields=false,$url=false,$parent=false,$class=false,$target_elem_id=false,$accept_children=false,$url_is_tab=false) {
		global $CFG;
		
		$this->fields[$table] = DB::getTableFields($table);
		
		$this->tables[$table] = array(
			'table_fields' => $table_fields,
			'url' => $url,
			'url_is_tab' => $url_is_tab,
			'parent' => $parent,
			'class' => $class,
			'target_elem_id' => $target_elem_id,
			'accept_children' => $accept_children,
			'method_id'=>$CFG->method_id);
	}
	
	function display() {
		global $CFG;
		
		if (!is_array($this->tables))
			return false;
	
		$title = ($this->rootname) ? $this->rootname : Ops::getPageTitle();
		$HTML = '
		<div class="area full_box multi_list '.$this->class.'" id="mlist_'.$this->i.'">
			<h2>'.$title.'</h2>
			<div class="box_bar"></div>
			<div class="box_tl"></div>
			<div class="box_tr"></div>
			<div class="box_bl"></div>
			<div class="box_br"></div>
			<div class="t_shadow"></div>
			<div class="r_shadow"></div>
			<div class="b_shadow"></div>
			<div class="l_shadow"></div>
			<div class="box_b"></div>
			<div class="grid_buttons"><div class="button before"></div>';

		$HTML .= ($this->dragdrop && User::permission(0,0,$CFG->url) > 1) ? "<a href=\"#\" onclick=\"ml_save(this)\" class=\"button\"><div class=\"save\"></div> $CFG->save_caption</a>" : '';
		$HTML .= '<div class="button after"></div></div><div class="contain">';
		$HTML .= MultiList::displayItems($this->tables);
		$HTML .= '<div class="clear"></div></div></div>';
		
		if ($this->dragdrop && !$CFG->pm_editor) {
			$HTML .= '<script type="text/javascript">';
			foreach ($this->levels as $level => $value) {
				
				$n = count($value);
				$siblings = $value;
				
				foreach ($value as $j) {
					
					$HTML .= '
					'.( (!$CFG->bypass) ? '$(document).ready(function(){' : '' ).'
						$(".mlist_'.$this->i.'_level_'.$level.'").sortable({
									revert: true,
									cursor: "move",
									opacity: 0.7,
									items: ".li_'.$level.'",
									connectWith: ".mlist_'.$this->i.'_level_'.$level.'"			
						}); 
					'.( (!$CFG->bypass) ? '});' : '' ).'
					';
				}
			}
			$HTML .= '</script>';
		}
		
		echo $HTML;
	}
	
	private function displayItems($tables,$rows=false,$f_id=0,$level=1,$p_id=0) {
		global $CFG;
		
		$pm_method = ($CFG->pm_editor) ? ' class="pm_method"' : '';
		
		if ($tables) {
			$parent_id = ($rows) ? $p_id : $f_id;
			$this->levels[$level][$parent_id] = $parent_id;
			$HTML = '';
			foreach ($tables as $name => $properties) {
				$HTML .= "<ul id=\"mlist_{$this->i}_level_{$level}_{$parent_id}\" class=\"mlist_{$this->i}_level_{$level}\">";
				$properties['url'] = (empty($properties['url'])) ? $CFG->url : $properties['url'];
				$properties['url_is_tab'] = (empty($properties['url_is_tab'])) ? $CFG->is_tab : $properties['url_is_tab'];
				if (!empty($properties['parent']) && $f_id == 0)
					continue;
					
					$HTML .= "<input id=\"table\" type=\"hidden\" name=\"table\" value=\"$name\" />";
					$HTML .= "<input id=\"enabled\" type=\"hidden\" name=\"enabled\" value=\"$this->dragdrop\" />";
					$HTML .= "<input id=\"p_id\" type=\"hidden\" name=\"p_id\" value=\"$p_id\" />";
					$HTML .= "<input id=\"f_id\" type=\"hidden\" name=\"f_id\" value=\"$f_id\" />";
					
					if ($CFG->pm_editor)
						$HTML .= Form::peLabel($properties['method_id'],'addTable');
					
				$table = ($rows) ? $rows : DB::getCats($name,$f_id);
				
				if ($table) {
					foreach ($table as $id => $row) {
						$hidden = ($row['row']['hidden'] == 'Y') ? 'hidden' : '';
						$HTML .= "<li id=\"{$row['row']['id']}\" class=\"li_{$level} ml_li {$hidden}\">";
						$HTML .= (!empty($row['children'])) ? '<div onclick="ml_expand(this)" class="more"></div><div onclick="ml_collapse(this)" class="less"></div>' : '';
						$HTML .= "<div class=\"ml_item\">";
						
						if ($CFG->url == 'edit_tabs' && !empty($row['row']['url'])) {
							$is_tab = ($name == 'admin_tabs') ? '1' : '0';
							
							
							if (User::permission(0,0,$properties['url'],false,$properties['url_is_tab']) > 1)
								$HTML .= '<a class="edit_page" href="index.php?current_url=edit_page&table='.$name.'&id='.$row['row']['id'].'&is_tab='.$is_tab.'"></a>';
						}
						
						if (is_array($properties['table_fields'])) {
							foreach ($properties['table_fields'] as $field) {
								$value = Grid::detectData($field,$row['row'][$field],$this->fields[$name]);
								
								$HTML .= (!empty($properties['url'])) ? Link::url($properties['url'],$row['row'][$field],"table={$name}&id={$row['row']['id']}&f_id={$row['row']['f_id']}&p_id={$row['row']['p_id']}&action=record&is_tab=".$properties['url_is_tab'],false,false,$properties['target_elem_id']) : $value;
							}
							$del_function = ($CFG->url == 'edit_tabs') ? 'deletePage(this)' : 'ml_delete(this)';
							
							if (User::permission(0,0,$properties['url'],false,$properties['url_is_tab']) > 1)
								$HTML .= Link::url($properties['url'],false,"table={$name}&id={$row['row']['id']}&f_id={$row['row']['f_id']}&p_id={$row['row']['p_id']}&action=record&is_tab=".$properties['url_is_tab'],false,false,$properties['target_elem_id'],'view',false,false,false,false,$CFG->view_hover_caption)
										.Link::url($properties['url'],false,"table={$name}&id={$row['row']['id']}&f_id={$row['row']['f_id']}&p_id={$row['row']['p_id']}&action=form&is_tab=".$properties['url_is_tab'],false,false,$properties['target_elem_id'],'edit',false,false,false,false,$CFG->edit_hover_caption) 
										."<a href=\"#\" class=\"delete\" title=\"{$CFG->delete_hover_caption}\" onclick=\"$del_function\" class=\"button\"></a>";
						}
						else {
							foreach ($row['row'] as $field) {
								$HTML .= '<span>'.$field.'</span>';
							}
						}
						
						$HTML .= ($this->row_end_button) ? $this->row_end_button : '';
						
						if (User::permission(0,0,$properties['url'],false,$properties['url_is_tab']) > 1 && $accepts_children)
							$HTML .= ($properties['accept_children']) ? Link::url($properties['url'],'<div class="add_new"></div>'.$CFG->add_new_caption,"table={$name}&f_id={$f_id}&p_id={$p_id}&action=form&is_tab=".$properties['url_is_tab'],false,false,$properties['target_elem_id']) : '';
						
						$HTML .= '</div>';
			
						if (!empty($row['children'])) {	
							$HTML .= MultiList::displayItems(array($name => $properties),$row['children'],false,($level + 1),$id);
						}
						
						foreach ($this->tables as $name1 => $properties1) {	
							if ($properties1['parent'] == $name) {
								$HTML .= MultiList::displayItems(array($name1 => $properties1),false,$id,($level + 1));
							}
						}
						
						$HTML .= '</li>';
					}
				}
				$HTML .= '</ul>';
				$HTML .= "<div class=\"add_elem\">".Link::url($properties['url'],'<div class="add_new"></div>'.$CFG->add_new_caption,"table={$name}&f_id={$f_id}&p_id={$p_id}&action=form&is_tab=".$CFG->is_tab,false,false,$properties['target_elem_id'])."</div>";
			}		
			
			return $HTML;
		}
	}
}
?>