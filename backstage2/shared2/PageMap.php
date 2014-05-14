<?php
class PageMap {
	private $areas_s,$areas_e;
	
	function __construct() {
		global $CFG;
		
	}
	
	function startArea($label) {
		global $CFG;
		
		$method_name = Form::peLabel($CFG->method_id,'startArea');
		$method = DB::getRecord('admin_controls_methods',$CFG->method_id);
		if (!$CFG->pm_editor) {
			$HTML = '<div class="page_area"><div class="pa_label"><span>'.$label.'</span></div>';
		}
		else {
			$HTML = "<div class=\"o\">[{$label}] $method_name</div>";
		}
		
		$order = ($method['order'] > 0) ? $method['order'] : '0';
		$this->areas_s[$order][] = $HTML;
	}
	
	function endArea() {
		global $CFG;
		
		$method_name = Form::peLabel($CFG->method_id,'endArea');
		$method = DB::getRecord('admin_controls_methods',$CFG->method_id);
		if (!$CFG->pm_editor) {
			$HTML = '<div class="clear"></div></div>';
		}
		else {
			$HTML = "<div class=\"o\">[End Area] $method_name</div>";
		}
		
		$order = ($method['order'] > 0) ? $method['order'] : '0';
		$this->areas_e[$order][] = $HTML;
	}
	
	private function getPages($tab_id) {
		global $CFG;
		
		$table_fields = DB::getTableFields('admin_pages',1);
		if (!in_array('icon',$table_fields)){
			$sql = "ALTER TABLE admin_pages ADD admin_pages.icon VARCHAR( 255 ) NOT NULL";
			db_query($sql);
		}
		if (!in_array('page_map_reorders',$table_fields)){
			$sql = "ALTER TABLE admin_pages ADD admin_pages.page_map_reorders TINYINT( 1 ) NOT NULL";
			db_query($sql);
		}
		
		$sql = "SELECT * FROM admin_pages WHERE f_id = $tab_id ORDER BY admin_pages.order, name ASC";
		return db_query_array($sql);
	}
	
	function display() {
		global $CFG;
		
		$tab_id = ($CFG->pm_editor) ? $CFG->id : Control::getPageId($CFG->url,1);
		
		if (!$tab_id > 0)
			return false;
		
		$current_tab = DB::getRecord('admin_tabs',$tab_id,0,1);	
		$pages = self::getPages($tab_id);

		if ($pages) {
			$HTML = '
			<div class="page_map">';
			
			foreach ($pages as $page) {
				if ($CFG->pm_editor) {
					$edit = '<a href="#" title="'.$CFG->edit_hover_caption.'" class="edit" class="method_edit_button" onclick="pmPageEdit('.$page['id'].',event);return false;"></a>';
				}
				$order = $page['order'];
				$icon = ($page['icon']) ? '<div class="lnk">'.Link::url($page['url'],'<img src="'.$page['icon'].'" title="'.$page['name'].'" />',false,false,false,'content').'</div>' : '';
				$pages_array[$order][] = '
				<div class="page_map_page o">
					<input type="hidden" id="id" value="'.$page['id'].'" />
					'.$icon.'
					<div class="lnk">'.Link::url($page['url'],$page['name'],false,false,false,'content').'</div>
					'.$edit.'
					<div class="clear"></div>
				</div>';
			}

			$total = max(array_keys($pages_array));
			$total = $total + count($this->areas_s);
			$total = $total + count($this->areas_e);
			for ($i=0;$i<=($total);$i++) {
				if ($this->areas_s[$i]) {
					foreach ($this->areas_s[$i] as $area) {
						$HTML .= $area;
					}
				}
				
				if ($pages_array[$i]) {
					foreach ($pages_array[$i] as $p) {
						$HTML .= $p;
					}
				}
				
				if ($this->areas_e[$i]) {
					foreach ($this->areas_e[$i] as $area) {
						$HTML .= $area;
					}
				}
			}
			
			
			
			$HTML .= '</div>';
		}
		else {
			$HTML .= '<div class="no_pages">'.$CFG->pagemap_nothing.'</div>';
		}
		echo $HTML;
	}
}
?>