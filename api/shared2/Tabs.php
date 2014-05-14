<?php
class Tabs {
	public $tables,$pages,$class,$target_elem,$HTML,$is_navigation,$is_inset,$i;
	public static $j;
	private $order = 1;
	
	function __construct($class=false,$target_elem=false,$is_navigation=false,$callback=false,$is_inset=false,$i=0) {
		global $CFG;
		$this->class = "$class tabs";
		//$this->class = ($is_inset) ? 'content_tabs' : $this->class;
		$this->i = (Tabs::$j > 1) ? Tabs::$j + 1 : 1;
		$this->i = ($CFG->backstage_mode) ? rand(1,999) : $this->i;
		$this->i = ($i > 0) ? $i : $this->i;
		$this->target_elem = ($is_inset) ? 'inset_area_'.$this->i : $target_elem;
		$this->is_navigation = $is_navigation;
		$this->callback = $callback;
		$this->is_inset = $is_inset;	
	}
	
	function addTable($tabs_table,$pages_table=false) {
		global $CFG;
		
		$this->tables[$tabs_table] = array(
			'pages_table'=>$pages_table,
			'method_id'=>$CFG->method_id,
			'order'=>$this->order);
		
		$this->order++;
	}
	
	function makeTab($name,$url,$variables=false,$identifier=false,$is_tab=false,$inset_id_field=false,$selected=false) {
		global $CFG;
		
		$this->tabs[$name] = array(
			'url' => $url,
			'variables' => $variables,
			'identifier' => $identifier,
			'is_tab' => $is_tab,
			'inset_id_field' => $inset_id_field,
			'method_id'=>$CFG->method_id,
			'order'=>$this->order,
			'selected'=>$selected);
		
		$this->order++;
	}
	
	function display() {
		global $CFG;

		if ($this->is_inset && !($CFG->id > 0))
			return false;
		
		$link_prefix = ($CFG->backstage_mode) ? 'index.php?is_tab=1&current_url=' : '';
		$HTML = "
		<div class=\"area full_box inset_tabs\">  
			<h2>$legend</h2>
			<div class=\"box_bar\"></div>
			<div class=\"box_tl\"></div>
			<div class=\"box_tr\"></div>
			<div class=\"box_bl\"></div>
			<div class=\"box_br\"></div>
			<div class=\"t_shadow\"></div>
			<div class=\"r_shadow\"></div>
			<div class=\"b_shadow\"></div>
			<div class=\"l_shadow\"></div>
			<div class=\"box_b\"></div>
			<div class=\"a_tabs\">";
		
		if (is_array($this->tabs)) {
			foreach ($this->tabs as $name => $properties) {
				if (User::permission(0,0,$properties['url'],false,$properties['is_tab']) > 0) {
					$key = $properties['order'];
					$link_prefix1 = ($CFG->backstage_mode) ? 'index.php?inset_id='.$CFG->id.'&inset_id_field='.$properties['inset_id_field'].'&inset_url='.$properties['url'].'&is_tab='.$properties['is_tab'].'&inset_i='.$this->i.'&current_url=' : '';
					$selected = (!is_array($first_tab) || $properties['selected']) ? 'visible' : '';
					$first_tab = (!is_array($first_tab) || $properties['selected']) ? $properties : $first_tab;
					$query_string = Link::parseVariables($properties['variables']);
					$query_string = ($query_string) ? '?'.$query_string : '';
					$identifier = (!empty($properties['identifier'])) ? $properties['identifier'] : "tabs_{$this->i}_blank";
					$tab_identifier = ($this->is_navigation) ? ((!strstr($properties['url'],'?')) ? '?':'&').'tab_identifier='.$key : '';
					$onclick = (!empty($properties['url'])) ? ' onclick="tabsSelectTab(\''.$link_prefix1.$properties['url'].$query_string.$tab_identifier.'\',\''.$this->target_elem.'\',this)" ' : '';
					$pm_method = ($CFG->pm_editor) ? 'pm_method' : '';
					//$del_id = ($this->is_navigation) ? "id=\"del_tab_$key\"" : '';
					
					if ($CFG->pm_editor)
						$method_name = Form::peLabel($properties['method_id'],'makeTab');
						
					$HTML_ARR[$key] = '<a class="a_tab '.$selected.' '.$pm_method.'" '.$onclick.' id="'.$identifier.'"><div class="contain1">'.$name.'</div><div class="l"></div><div class="r"></div><div class="c"></div></a>'.$method_name;
				}
			}
		}
		
		ksort($HTML_ARR);
		$HTML .= implode($HTML_ARR);
		$HTML .= '</div><div class="contain">';
		echo $HTML;
		
		if ($this->is_inset) {
			$CFG->inset_id = $CFG->id;
			$CFG->inset_id_field = $first_tab['inset_id_field'];
			$CFG->inset_target_elem = $this->target_elem;
			$CFG->inset_is_tab = $first_tab['is_tab'];
			$CFG->inset_url = $first_tab['url'];
			$CFG->inset_i = $this->i;
			
			echo '<div class="inset_area" id="inset_area_'.$this->i.'">';
			
			if (is_array($this->tabs)) {
				$control = new Control($first_tab['url'],false,$first_tab['is_tab']);
			}
			echo '</div>';
		}
		echo '</div></div>';
	}
	
	function getTabs($table) {
		$sql = "SELECT * FROM $table ORDER BY $table.order ASC ";
		return db_query_array($sql);
	}
	
	function getPages($pages_table) {
		$sql = "SELECT * FROM $pages_table ORDER BY $pages_table.order, name ASC ";
		$result = db_query_array($sql);
		if (is_array($result)) {
			foreach ($result as $row) {
				$key = $row['f_id'];
				$pages[$key][] = $row;
			}
			return $pages;
		}
	}
}
?>