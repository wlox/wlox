<?php

class FileManager {
	private $class,$tables,$folder_table,$filters,$filters1,$mode,$order_by,$order_asc,$folders,$folders1,$current_folder_id,$data,$bypass,$path,$filter_results,$download_group,$only_admin,$data_download;
	static $i;
		
	//mode can be search or icons
	function __construct($class=false,$folder_table=false,$mode=false,$download_encrypted_group=false,$only_admin_can_download=false) {
		global $CFG;
		
		$this->class = ($class) ? $class : 'file_manager';
		$this->i = ($this->i > 0) ? $this->i : 1;
		$this->folder_table = $folder_table;
		$this->mode = ($mode) ? $mode : 'icons';
		$this->order_by = $_REQUEST['order_by'];
		$this->order_asc = $_REQUEST['order_asc'];
		$this->current_folder_id = ($_REQUEST['current_id'] > 0) ? $_REQUEST['current_id'] : '0';
		$this->current_folder_id = (!($_REQUEST['current_id'] > 0 || $_REQUEST['current_id'] == '0') && ($_SESSION['current_folder_id'.$CFG->control_pass_id] > 0 || $_SESSION['current_folder_id'.$CFG->control_pass_id] == '0')) ? $_SESSION['current_folder_id'.$CFG->control_pass_id] : $this->current_folder_id;
		$this->history = $_REQUEST['history'];
		$this->bypass = $_REQUEST['fm_bypass'];
		$this->n_or_b = $_REQUEST['b_or_n'];
		$this->download_group = $download_encrypted_group;
		$this->only_admin = $only_admin_can_download;
		$_SESSION['current_folder_id'.$CFG->control_pass_id] = $this->current_folder_id;

		if (!DB::tableExists($folder_table)) {
			if (DB::createTable($folder_table,array('name'=>'vchar','p_id'=>'int'))) {
				Messages::add($CFG->table_created);
			}
		}
		
		$form = new Form('form_filters');
		$this->filter_results = $form->info;
		if (!$this->filter_results) {
			if (!array_key_exists('current_id',$_REQUEST)) {
				$this->filter_results = $_SESSION['current_filter_results'.$CFG->control_pass_id];
				unset($_SESSION['current_filter_results'.$CFG->control_pass_id]);
			}
			
			$_REQUEST['search_fields'] = $_SESSION['search_fields'.$CFG->control_pass_id];
			$_REQUEST['datefields'] = $_SESSION['datefields'.$CFG->control_pass_id];
			$_REQUEST['month_fields'] = $_SESSION['month_fields'.$CFG->control_pass_id];
			$_REQUEST['year_fields'] = $_SESSION['year_fields'.$CFG->control_pass_id];
			$_REQUEST['cat_selects'] = $_SESSION['cat_selects'.$CFG->control_pass_id];
			$_REQUEST['subtables'] = $_SESSION['subtables'];
			$this->filters1 = $_SESSION['filter_properties'.$CFG->control_pass_id];
		}
		else {
			$this->current_folder_id = '0';
			$this->filter_results['first_letter'] = $_REQUEST['fl'];
			$this->filter_results['first_letter_field'] = $_REQUEST['fl_field'];
			$this->filter_results['first_letter_subtable'] = $_REQUEST['fl_subtable'];
			$_SESSION['current_filter_results'.$CFG->control_pass_id] = $this->filter_results;
			$_SESSION['search_fields'.$CFG->control_pass_id] = $_REQUEST['search_fields'];
			$_SESSION['datefields'.$CFG->control_pass_id] = $_REQUEST['datefields'];
			$_SESSION['month_fields'.$CFG->control_pass_id] = $_REQUEST['month_fields'];
			$_SESSION['year_fields'.$CFG->control_pass_id] = $_REQUEST['year_fields'];
			$_SESSION['cat_selects'.$CFG->control_pass_id] = $_REQUEST['cat_selects'];
			$_SESSION['subtables'] = $_REQUEST['subtables'];
			
			if (is_array($_REQUEST['filter_properties'])) {
				foreach ($_REQUEST['filter_properties'] as $properties) {
					$this->filters1[] = unserialize(urldecode($properties));
				}
				$_SESSION['filter_properties'.$CFG->control_pass_id] = $this->filters1;
			}
		}
		if (is_array($this->filter_results)) {
			foreach ($this->filter_results as $key => $row) {
				if (empty($row))
					unset($this->filter_results[$key]);
			}
		}
		if (count($this->filter_results) > 0)
			$this->mode = 'search';
	}
	
	function addTable($table,$table_fields=false,$folder_field=false,$url=false,$target_elem_id=false,$link_is_tab=false,$alert_condition1=false,$alert_condition2=false) {
		global $CFG;
		
		if (DB::tableExists($table)) {
			$this->tables[$table] = array(
				'table_fields' => $table_fields,
				'folder_field' => $folder_field,
				'url' => $url,
				'target_elem_id' => $target_elem_id,
				'method_id'=>$CFG->method_id,
				'link_is_tab'=>$link_is_tab,
				'alert_condition1'=>$alert_condition1,
				'alert_condition2'=>$alert_condition2);
		}
		
		if ($CFG->pm_editor) {
			echo '
			<li>'.$table.' [addTable] '.Form::peLabel($CFG->method_id,'addTable').'</li>';
		}
		echo '
		<input type="hidden" class="added_table" value="'.$table.'" />
		<input type="hidden" id="table_'.$table.'_fields" value="'.implode('|',$table_fields).'" />';
	}
	
	private function getFolders() {
		global $CFG;
		
		$CFG->in_file_manager = true;
		$filters = array_merge(($this->filters ? $this->filters:array()),($this->filters1 ? $this->filters1:array()));
		if (is_array($filters)) {
			foreach ($filters as $key => $properties) {
				if ($properties['type'] == 'search') {
					$filters[$key]['subtable_fields'] = array(1=>'name');
				}
			}
		}
		$_REQUEST['search_fields'] = array('name'=>'');
		$filter_results = DB::adequateFilterResults($this->filter_results,$filters,$this->folder_table,1);
		if (count($this->filter_results) <= count($filter_results)) {
			$result = DB::get($this->folder_table,array('folder_name'=>array('name'=>'folder_name','subtable_fields'=>array('name')),'p_id'=>array('name'=>'p_id')),0,0,'name',1,false,$filter_results);	
		}
		if ($this->mode == 'icons') {
			$this->folders = self::structureFolders($result);
		}
		elseif ($this->mode == 'search') {
			$this->folders = self::structureFolders(DB::get($this->folder_table,array('folder_name'=>array('name'=>'folder_name','subtable_fields'=>array('name')),'p_id'=>array('name'=>'p_id')),0,0,'name'));
			$this->folders1 = $result;
		}
	}
	
	private function getData() {
		global $CFG;
		
		$CFG->in_file_manager = true;
		$filters = array_merge(($this->filters ? $this->filters:array()),($this->filters1 ? $this->filters1:array()));
		foreach ($this->tables as $table => $properties) {
			$filter_results = DB::adequateFilterResults($this->filter_results,$filters,$table);
			$current_folder_id = (!$filter_results) ? $this->current_folder_id : false;
			$data[$table] = DB::get($table,array('file_name'=>array('name'=>'file_name','subtable_fields'=>$properties['table_fields'])),0,0,$this->order_by,$this->order_asc,false,$filter_results,$current_folder_id,$properties['folder_field']);
			
			if ($this->filter_results) {
				$result = DB::get($table,array('file_name'=>array('name'=>'file_name','subtable_fields'=>$properties['table_fields'])),0,0,$this->order_by,$this->order_asc,false,$filter_results);
				if ($result) {
					foreach ($result as $row) {
						$this->data_download[$table][] = $row['id'];
					}
				}
			}
			else
				$this->data_download[$table] = self::getIds($table);
		}
		
		if ($data) {
			foreach ($data as $table => $files) {
				if (is_array($files)) {
					foreach ($files as $file) {
						$id = $file['id'];
						$file['table'] = $table;
						$file['target_elem_id'] = $this->tables[$table]['target_elem_id'];
						$file['link_is_tab'] = $this->tables[$table]['link_is_tab'];
						$file['url'] = $this->tables[$table]['url'];
						$file['folder_field'] = $this->tables[$table]['folder_field'];
						$file['alert_condition1'] = $this->tables[$table]['alert_condition1'];
						$file['alert_condition2'] = $this->tables[$table]['alert_condition2'];
						$this->data[$id.'_'.$table] = $file;
					}
				}
			}
		}
	}
	
	function display() {
		global $CFG;
		
		if (!is_array($this->tables))
			return false;
		
		self::getData();
		self::getFolders();

		if ($this->mode == 'icons') {
			if (is_array($this->folders['path'])) {
				$this->folders['path'] = array_reverse($this->folders['path']);
			}
			$this->folders['path'][] = $this->folders['this'];
			foreach ($this->folders['path'] as $folder) {
				$path .= '/'.$folder['folder_name'];
			}
		}
		else {
			$path = '/'.$CFG->filter_submit_text;
		}
		
		if (!$this->bypass) {
			self::show_filters();
			
			$HTML .= '
			<div class="'.$this->class.'">
				<table>
					<tr>
						<td class="bar" colspan="2">
							<a class="m_back" title="'.$CFG->back.'" onclick="file_manager.last()"></a>
							<a class="m_forward" title="'.$CFG->forward.'" onclick="file_manager.next()"></a>
							<a class="m_up" title="'.$CFG->up_directory.'" onclick="file_manager.openFolder('.$this->folders['up']['id'].')"></a>
							<a class="m_add_folder" title="'.$CFG->add_directory.'" onclick="file_manager.addFolder()"></a>';
			if ($this->tables) {
				foreach ($this->tables as $table => $properties) {
					$table1 = (substr($table,-1) == 's') ? ucfirst(substr($table,0,-1)) : ucfirst($table);
					$HTML .= '<a class="m_add_file" title="'.$CFG->add_new_caption.' '.$table1.'" onclick="file_manager.addFile(\''.$table.'\')"></a>';
				}
			}
			
			$HTML .= '		
							<div id="fm_path" class="fm_path">'.$path.'</div>';
			
			if (User::$info['is_admin'] == 'Y' || (!$this->only_admin && key($this->download_group) == User::$info['f_id']))
				$HTML .= '
							<div class="fm_download"><a href="#" onclick="file_manager.downloadResults();return false;">'.(($this->mode == 'icons') ? $CFG->download_all : $CFG->download_results).'</a><iframe class="fm_download_iframe" src="" name="download"></iframe></div>';
			$HTML .= '
							<div class="clear"></div>
						</td>
					</tr>
					<tr>
						<td class="tree">';
			
			if (is_array($this->folders['path'])) {
				$last_p_id = $this->folders['path'][0]['id'];
			}
			
			$has_sub = (self::hasSubfolders($id)) ? 'this' : 'false';
			$no_triangle = ($has_sub == 'false') ? 'not' : '';
			$down = (($last_p_id > 0) && ($id == $last_p_id)) ? 1 : false;
			$HTML .= '
			<div class="folder_container">
				<input type="hidden" id="id" value="0" />
				<div id="triangle" class="triangle'.$down.' '.$no_triangle.'" onclick="file_manager.showSubfolders(0,'.$has_sub.')"></div>
				<div class="folder home" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder(0,'.$has_sub.')"></div>
				<div class="desc" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder(0,'.$has_sub.')">/</div>
				<div class="clear"></div>
			</div>';
			
			if (is_array($this->folders['top_level'])) {
				foreach ($this->folders['top_level'] as $id => $row) {
					$has_sub = (self::hasSubfolders($id)) ? 'this' : 'false';
					$no_triangle = ($has_sub == 'false') ? 'not' : '';
					$down = (($last_p_id > 0) && ($id == $last_p_id)) ? 1 : false;
					$HTML .= '
					<div class="folder_container">
						<input type="hidden" id="id" value="'.$row['id'].'" />
						<div id="triangle" class="triangle'.$down.' '.$no_triangle.'" onclick="file_manager.showSubfolders('.$row['id'].','.$has_sub.')"></div>
						<div class="folder" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder('.$row['id'].','.$has_sub.')"></div>
						<div class="desc" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder('.$row['id'].','.$has_sub.')">'.$row['folder_name'].'</div>
						<div class="clear"></div>';
					if (($last_p_id > 0) && ($id == $last_p_id)) {
						$i = 0;
						foreach ($this->folders['path'] as $key => $sub) {
							if (!($key > 0))
								continue;
							
							$has_sub = (self::hasSubfolders($id)) ? 'this' : 'false';
							$no_triangle = ($has_sub == 'false') ? 'not' : '';
							$down = ($sub['id'] == $this->folders['this']['id']) ? false : 1;
							$HTML .= '
							<div class="folder_container indent">
								<input type="hidden" id="id" value="'.$sub['id'].'" />
								<div id="triangle" class="triangle'.$down.' '.$no_triangle.'" onclick="file_manager.showSubfolders('.$sub['id'].','.$has_sub.')"></div>
								<div class="folder" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder('.$sub['id'].','.$has_sub.')"></div>
								<div class="desc" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder('.$sub['id'].','.$has_sub.')">'.$sub['folder_name'].'</div>
								<div class="clear"></div>';
							$i++;
						}
						for ($j=0;$j<$i;$j++) {
							$HTML .= '</div>';
						}
					}
					$HTML .= '</div>';
				}
			}
			
			$HTML .= '
						</td>
						<td class="navigator" id="navigator">';
		}
		
		if ($this->data_download) {
			foreach ($this->data_download as $d_table => $d_ids) {
				$HTML .= '<input type="hidden" class="download_ids" id="download_'.$d_table.'" value="'.@implode('|',$d_ids).'" />';
			}
		}
		
		$HTML .= '
		<input type="hidden" id="folder_table" value="'.$this->folder_table.'" />
		<input type="hidden" id="current_id" value="'.$this->current_folder_id.'" />
		<input type="hidden" id="current_url" value="'.$CFG->url.'" />
		<input type="hidden" id="is_tab" value="'.$CFG->is_tab.'" />';
		
		if ($this->mode == 'icons') {
			if ($this->tables) {
				foreach ($this->tables as $table => $properties) {
					$HTML .= '<input type="hidden" id="folder_link" value="&action=form&p_id='.$this->current_folder_id.'" />';
					$HTML .= '<input type="hidden" id="'.$table.'_link" value="current_url='.$properties['url'].'&action=form&is_tab='.$properties['link_is_tab'].'&'.$properties['url'].'['.$properties['folder_field'].']='.$this->current_folder_id.'" />';
					$HTML .= '<input type="hidden" id="'.$table.'_target" value="'.$properties['target_elem_id'].'" />';
				}
			}
			
			if (!($this->current_folder_id > 0) && $this->folders['top_level']) {
				foreach ($this->folders['top_level'] as $folder) {
					$HTML .= '
					<div class="folder_container">
						<div class="ops">';
						if (User::permission(0,0,$this->folder_table) > 0)
							$HTML .= Link::url($this->folder_table,false,'id='.$folder['id'].'&action=record&is_tab='.$folder['link_is_tab'],false,false,'edit_box','view',false,false,false,false,$CFG->view_hover_caption).' ';
						if (User::permission(0,0,$this->folder_table) > 1)
							$HTML .= Link::url($this->folder_table,false,'id='.$folder['id'].'&action=form&is_tab='.$folder['link_is_tab'],false,false,'edit_box','edit',false,false,false,false,$CFG->edit_hover_caption).' ';
						if (User::permission(0,0,$this->folder_table) > 1)	
							$HTML .= '<a href="#" title="'.$CFG->delete_hover_caption.'" onclick="file_manager.deleteThis('.$folder['id'].',\''.$this->folder_table.'\',this)" class="delete"></a>';
					$HTML .= '
						</div>
						<div class="folder" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder('.$folder['id'].')"></div>
						<div class="desc" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder('.$folder['id'].')">'.$folder['folder_name'].'</div>
						<input type="hidden" id="id" value="'.$folder['id'].'" />
					</div>';
				}
			}
			elseif ($this->folders['children']) {
				foreach ($this->folders['children'] as $folder) {
					$HTML .= '
					<div class="folder_container">
						<div class="ops">';
						if (User::permission(0,0,$this->folder_table) > 0)
							$HTML .= Link::url($this->folder_table,false,'id='.$folder['id'].'&action=record',false,false,'edit_box','view',false,false,false,false,$CFG->view_hover_caption).' ';
						if (User::permission(0,0,$this->folder_table) > 1)
							$HTML .= Link::url($this->folder_table,false,'id='.$folder['id'].'&action=form',false,false,'edit_box','edit',false,false,false,false,$CFG->edit_hover_caption).' ';
						if (User::permission(0,0,$this->folder_table) > 1)	
							$HTML .= '<a href="#" title="'.$CFG->delete_hover_caption.'" onclick="file_manager.deleteThis('.$folder['id'].',\''.$this->folder_table.'\',this)" class="delete"></a>';
					$HTML .= '
						</div>
						<div class="folder" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder('.$folder['id'].')"></div>
						<div class="desc" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder('.$folder['id'].')">'.$folder['folder_name'].'</div>
						<input type="hidden" id="id" value="'.$folder['id'].'" />
					</div>';
				}
			}
			
			if ($this->data) {
				foreach ($this->data as $file) {
					if ($file['alert_condition1']) {
						$file_row = DB::getRecord($file['table'],$file['id']);
						$condition = String::doFormulaReplacements($file['alert_condition1'],$file_row,1);
						$alert_class1 = (eval("if ($condition) { return 1;} else { return 0;}")) ? 'alert1' : '';
					}
					if ($file['alert_condition2']) {
						$file_row = (is_array($file_row)) ? $file_row : DB::getRecord($file['table'],$file['id']);
						$condition = String::doFormulaReplacements($file['alert_condition2'],$file_row,1);
						$alert_class2 = (eval("if ($condition) { return 1;} else { return 0;}")) ? 'alert2' : '';
					}
					
					$HTML .= '
					<div class="file_container '.$alert_class1.' '.$alert_class2.'">
						<div class="ops">';
						if (User::permission(0,0,$file['url']) > 0)
							$HTML .= Link::url($file['url'],false,'id='.$file['id'].'&action=record&is_tab='.$file['link_is_tab'],false,false,$file['target_elem_id'],'view',false,false,false,false,$CFG->view_hover_caption).' ';
						if (User::permission(0,0,$file['url']) > 1)
							$HTML .= Link::url($file['url'],false,'id='.$file['id'].'&action=form&is_tab='.$file['link_is_tab'],false,false,$file['target_elem_id'],'edit',false,false,false,false,$CFG->edit_hover_caption).' ';
						if (User::permission(0,0,$file['url']) > 1)	
							$HTML .= '<a href="#" title="'.$CFG->delete_hover_caption.'" onclick="file_manager.deleteThis('.$file['id'].',\''.$file['table'].'\',this)" class="delete"></a>';
					
					$is_tab = ($file['link_is_tab']) ? $file['link_is_tab'] : 'false';
					$HTML .= '
						</div>
						<div class="file" onclick="file_manager.select(this,event);" ondblclick="file_manager.showFile(\''.$file['url'].'\','.$file['id'].','.$is_tab.',\''.$file['target_elem_id'].'\');"></div>
						<div class="desc" onclick="file_manager.select(this,event);" ondblclick="file_manager.showFile(\''.$file['url'].'\','.$file['id'].','.$is_tab.',\''.$file['target_elem_id'].'\');">'.$file['file_name'].'</div>
						<input type="hidden" id="id" value="'.$file['id'].'" />
						<input type="hidden" id="table" value="'.$file['table'].'" />
						<input type="hidden" id="folder_field" value="'.$file['folder_field'].'" />
					</div>';
				}
			}
		}
		elseif ($this->mode == 'search') {
			if ($this->data) {
				foreach ($this->data as $row) {
					$k = $row['file_name'];
					$all_data[$k.'_fi']  = $row; 
				}
			}
			if ($this->folders1) {
				foreach ($this->folders1 as $row) {
					$k = $row['folder_name'];
					$all_data[$k.'_fo']  = $row; 
				}
			}
			
			if ($all_data) {
				ksort($all_data);
				$HTML .= '<div class="search">';
				foreach ($all_data as $row) {
					if (array_key_exists('file_name',$row)) {
						if ($row['alert_condition1']) {
							$file_row = DB::getRecord($row['table'],$row['id']);
							$condition = String::doFormulaReplacements($row['alert_condition1'],$file_row,1);
							$alert_class1 = (eval("if ($condition) { return 1;} else { return 0;}")) ? 'alert1' : '';
						}
						if ($row['alert_condition2']) {
							$file_row = (is_array($file_row)) ? $file_row : DB::getRecord($row['table'],$row['id']);
							$condition = String::doFormulaReplacements($row['alert_condition2'],$file_row,1);
							$alert_class2 = (eval("if ($condition) { return 1;} else { return 0;}")) ? 'alert2' : '';
						}
						$is_tab = ($row['link_is_tab']) ? $row['link_is_tab'] : 'false';
						$HTML .= '<div class="search_container '.$alert_class1.' '.$alert_class2.'" onclick="file_manager.select(this,event);" ondblclick="file_manager.showFile(\''.$row['url'].'\','.$row['id'].','.$is_tab.',\''.$row['target_elem_id'].'\');">';
						$HTML .= '
							<div class="file"></div>
							<div class="desc">'.$row['file_name'].'</div>
							<input type="hidden" id="id" value="'.$row['id'].'" />
							<input type="hidden" id="table" value="'.$row['table'].'" />
							<input type="hidden" id="folder_field" value="'.$row['folder_field'].'" />
							<div class="ops1">';
							if (User::permission(0,0,$row['url']) > 0)
								$HTML .= Link::url($row['url'],false,'id='.$row['id'].'&action=record&is_tab='.$row['link_is_tab'],false,false,$row['target_elem_id'],'view',false,false,false,false,$CFG->view_hover_caption).' ';
							if (User::permission(0,0,$row['url']) > 1)
								$HTML .= Link::url($row['url'],false,'id='.$row['id'].'&action=form&is_tab='.$row['link_is_tab'],false,false,$row['target_elem_id'],'edit',false,false,false,false,$CFG->edit_hover_caption).' ';
							if (User::permission(0,0,$row['url']) > 1)	
								$HTML .= '<a href="#" title="'.$CFG->delete_hover_caption.'" onclick="file_manager.deleteThis('.$row['id'].',\''.$row['table'].'\',this)" class="delete"></a>';
						$HTML .= "</div></div>";
					}
					else {
						$HTML .= '<div class="search_container" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder('.$row['id'].')">';
						$HTML .= '
						<div class="folder"></div>
						<div class="desc">'.$row['folder_name'].'</div>
						<input type="hidden" id="id" value="'.$row['id'].'" />
						<div class="ops1">';
						if (User::permission(0,0,$this->folder_table) > 0)
							$HTML .= Link::url($this->folder_table,false,'id='.$row['id'].'&action=record',false,false,'edit_box','view',false,false,false,false,$CFG->view_hover_caption).' ';
						if (User::permission(0,0,$this->folder_table) > 1)
							$HTML .= Link::url($this->folder_table,false,'id='.$row['id'].'&action=form',false,false,'edit_box','edit',false,false,false,false,$CFG->edit_hover_caption).' ';
						if (User::permission(0,0,$this->folder_table) > 1)	
							$HTML .= '<a href="#" title="'.$CFG->delete_hover_caption.'" onclick="file_manager.deleteThis('.$row['id'].',\''.$this->folder_table.'\',this)" class="button"></a>';
						$HTML .= '
						</div>';
						$HTML .= '</div>';
					}
				}
				$HTML .= '</div>';
			}
		}
		
		if (!$CFG->pm_editor) {
			$HTML .= '
			<script language="text/javascript">
				'.((!$this->n_or_b) ? 'file_manager.addHistory('.$this->current_folder_id.');' : '').'
				file_manager.setPath("'.$path.'");
				$("#navigator").click(function() {
					file_manager.unselect();
				});
				$(".tree").click(function() {
					file_manager.unselect();
				});
				file_manager.startSelectable();
			</script>
			';
		}
		
		if (!$this->bypass) {
			$HTML .= '		<div class="clear"></div>
						</td>
					</tr>
				</table>
			</div>';
		}
		echo $HTML;
	}
	
	function hasSubfolders($id,$folder_table=false) {
		if (!($id > 0))
			return false;
			
		if (!$folder_table)
			return DB::getRecord($this->folder_table,0,$id,1,'p_id');
		else
			return DB::getRecord($folder_table,0,$id,1,'p_id');
	}
	
	function filterSearch($fields_array,$caption=false,$class=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'search',
			'caption'=>$caption,
			'subtable_fields' => $fields_array,
			'class'=> $class,
			'method_id'=>$CFG->method_id);
	}
	
	function filterAutocomplete($field_name,$caption=false,$options_array=false,$subtable=false,$subtable_fields=false,$class=false) {
		global $CFG;
		
		$this->filters[$field_name] = array(
			'type'=>'autocomplete',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'options_array'=>$options_array,
			'subtable' => $subtable,
			'subtable_fields' => $subtable_fields,
			'class'=> $class,
			'method_id'=>$CFG->method_id);
	}
	
	function filterCats($subtable,$caption=false,$class=false,$subtable_fields=false,$concat_char=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'cats',
			'caption'=>$caption,
			'subtable' => $subtable,
			'class'=> $class,
			'method_id'=>$CFG->method_id,
			'subtable_fields'=>$subtable_fields,
			'concat_char'=>$concat_char);
	}

	function filterFirstLetter($field_name,$subtable=false) {
		global $CFG;
		
		$this->filters[$field_name] = array(
			'field_name'=>$field_name,
			'type'=>'first_letter',
			'subtable' => $subtable,
			'method_id'=>$CFG->method_id);
	}
	
	function filterSelect($field_name,$caption=false,$options_array=false,$subtable=false,$subtable_fields=false,$class=false,$f_id_field=false,$depends_on=false) {
		global $CFG;
		
		$this->filters[$field_name] = array(
			'type'=>'select',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'options_array'=>$options_array,
			'subtable' => $subtable,
			'subtable_fields' => $subtable_fields,
			'class'=> $class,
			'method_id'=>$CFG->method_id,
			'f_id_field'=>$f_id_field,
			'depends_on'=>$depends_on);
	}
	
	function filterTokenizer($field_name,$caption=false,$options_array=false,$subtable=false,$subtable_fields=false,$class=false,$f_id_field=false,$depends_on=false) {
		global $CFG;
		
		$this->filters[$field_name] = array(
			'type'=>'tokenizer',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'options_array'=>$options_array,
			'subtable' => $subtable,
			'subtable_fields' => $subtable_fields,
			'class'=> $class,
			'method_id'=>$CFG->method_id);
	}
	
	function filterCheckbox($field_name,$caption=false,$checked=false,$class=false) {
		global $CFG;
		
		$this->filters[$field_name] = array(
			'type'=>'checkbox',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'checked' => $checked,
			'class'=> $class,
			'method_id'=>$CFG->method_id);
	}
	
	function filterRadio($field_name,$caption=false,$value=false,$checked=false,$class=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'radio',
			'field_name'=>$field_name,
			'value'=>$value,
			'caption'=>$caption,
			'checked' => $checked,
			'class'=> $class,
			'method_id'=>$CFG->method_id);
	}
						
	function filterDateStart($field_name,$caption=false,$value=false,$time=false,$ampm=false,$req_start=false,$req_end=false,$link_to=false,$format=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'start_date',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'value'=>$value,
			'time'=>$time,
			'ampm'=>$ampm,
			'req_start'=>$req_start,
			'req_end'=>$req_end,
			'link_to'=>$link_to,
			'format'=>$format,
			'method_id'=>$CFG->method_id);
	}
	
	function filterDateEnd($field_name,$caption=false,$value=false,$time=false,$ampm=false,$req_start=false,$req_end=false,$link_to=false,$format=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'end_date',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'value'=>$value,
			'time'=>$time,
			'ampm'=>$ampm,
			'req_start'=>$req_start,
			'req_end'=>$req_end,
			'link_to'=>$link_to,
			'format'=>$format,
			'method_id'=>$CFG->method_id);
	}
	
	function filterMonth($field_name,$caption=false,$language=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'month',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'language'=>$language,
			'method_id'=>$CFG->method_id);
	}
	
	function filterYear($field_name,$caption=false,$back_to=false) {
		global $CFG;
		
		$this->filters[] = array(
			'type'=>'year',
			'field_name'=>$field_name,
			'caption'=>$caption,
			'back_to'=>$back_to,
			'method_id'=>$CFG->method_id);
	}
	
	function show_filters() {
		global $CFG;
		
		if ($this->inset_id > 0)
			return false;
		
		if (is_array($this->filters)) {
			$form_filters = new Form('form_filters',false,'GET','form_filters',false);
			$form_filters->show_errors();
			$form_filters->info = ($form_filters->info) ? $form_filters->info : $this->filter_results;
			
			foreach ($this->filters as $filter) {
				$name = $filter['field_name'];
				$caption = (!empty($filter['caption'])) ? $caption : $name;
				
				if (($filter['type'] != 'radio' && $filter['type'] != 'start_date' && $filter['type'] != 'end_date') && $group) {
					$form_filters->endGroup();
					$group = false;
				}
				switch ($filter['type']) {
					case 'per_page':
						$options_array = (is_array($filter['options_array'])) ? $filter['options_array'] : array(10=>10,30=>30,50=>50);
						$caption = (!empty($filter['caption'])) ? $filter['caption'] : $CFG->results_per_page_text;
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterPerPage';
						$form_filters->selectInput('per_page',$caption,false,$this->rows_per_page,$options_array,false,false,false,false,$filter['class']);
						break;
					case 'search':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterSearch';
						$form_filters->textInput('search',$filter['caption'],false,false,false,false,$filter['class']);
						foreach ($filter['subtable_fields'] as $s_field => $s_subtable) {
							$s_subtable = (($s_subtable) && ($s_subtable != $s_field)) ? $s_subtable : $this->table;
							$CFG->o_method_suppress = true;
							$form_filters->HTML('<input type="hidden" name="search_fields['.$s_field.']" value="'.$s_subtable.'" />');
						}
						break;
					case 'autocomplete':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterAutocomplete';
						$form_filters->autoComplete($name,$filter['caption'],false,$filter['value'],false,$filter['options_array'],$filter['subtable'],$filter['subtable_fields'],false,false,$filter['class']);
						$CFG->o_method_suppress = true;
						$form_filters->HTML('<input type="hidden" name="subtables['.$name.'][subtable]" value="'.$filter['subtable'].'" />');
						$CFG->o_method_suppress = true;
						$form_filters->HTML('<input type="hidden" name="subtables['.$name.'][subtable_fields]" value="'.implode('|',$filter['subtable_fields']).'" />');
						$CFG->o_method_suppress = true;
						$form_filters->HTML('<input type="hidden" name="subtables['.$name.'][f_id_field]" value="'.$filter['f_id_field'].'" />');
						break;
					case 'tokenizer':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterTokenizer';
						$form_filters->autoComplete($name,$filter['caption'],false,$filter['value'],false,$filter['options_array'],$filter['subtable'],$filter['subtable_fields'],false,false,$filter['class'],false,false,false,false,false,false,false,false,false,false,false,1);
						break;
					case 'cats':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterCats';
						$form_filters->catSelect($filter['subtable'],$filter['caption'],0,$filter['class'],false,false,false,$filter['subtable_fields'],$filter['concat_char']);
						break;
					case 'first_letter':
						$range = range('A','Z');
						$HTML = '';
						foreach ($range as $l) {
							$HTML .= Link::url($this->link_url,$l,'fl='.$l.'&fl_field='.$name.'&fl_subtable='.$filter['subtable'].'&is_tab='.$this->is_tab,false,false,'content');
						}
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterFirstLetter';
						$form_filters->HTML($HTML);
						break;
					case 'select':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterSelect';
						$form_filters->selectInput($name,$filter['caption'],false,false,$filter['options_array'],$filter['subtable'],$filter['subtable_fields'],false,false,$filter['class'],false,false,$filter['f_id_field'],false,$filter['depends_on']);
						$CFG->o_method_suppress = true;
						$form_filters->HTML('<input type="hidden" name="subtables['.$name.'][subtable]" value="'.$filter['subtable'].'" />');
						$CFG->o_method_suppress = true;
						$form_filters->HTML('<input type="hidden" name="subtables['.$name.'][subtable_fields]" value="'.implode('|',$filter['subtable_fields']).'" />');
						$CFG->o_method_suppress = true;
						$form_filters->HTML('<input type="hidden" name="subtables['.$name.'][f_id_field]" value="'.$filter['f_id_field'].'" />');
						break;
					case 'checkbox':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterCheckbox';
						$form_filters->checkBox($name,$filter['caption'],false,false,$filter['class'],false,false,$filter['checked']);
						break;
					case 'radio':
						if (!$group) {
							$CFG->o_method_suppress = true;
							$form_filters->startGroup();
						}
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterRadio';
						$form_filters->radioInput($name,$filter['caption'],false,$filter['value'],false,$filter['class'],false,false,$filter['checked']);
						if (!$group) {
							$group = true;
						}
						else {
							$CFG->o_method_suppress = true;
							$form_filters->endGroup();
							$group = false;
						}
						break;
					case 'start_date':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterDateStart';
						$form_filters->dateWidget($name,$filter['caption'],false,$filter['time'],$filter['ampm'],$filter['req_start'],$filter['req_end'],$filter['value'],false,false,$filter['class'],$filter['format']);
						break;
					case 'end_date':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterDateEnd';
						$form_filters->dateWidget($name,$filter['caption'],false,$filter['time'],$filter['ampm'],$filter['req_start'],$filter['req_end'],$filter['value'],$filter['link_to'],false,$filter['class'],$filter['format'],false,false,true);
						break;
					case 'month':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterMonth';
						$form_filters->selectInput($name.'_month',$filter['caption'],false,false,String::getMonthNames($filter['language']));
						$CFG->o_method_suppress = true;
						$form_filters->HTML('<input type="hidden" name="month_fields[]" value="'.$name.'_month" />');
						break;
					case 'year':
						$CFG->o_method_id = $filter['method_id'];
						$CFG->o_method_name = 'filterYear';
						$back_to = ($filter['back_to']) ? $filter['back_to'] : 1975;
						$years = range(date('Y'),$back_to);
						$years = array_combine($years,$years);
						$form_filters->selectInput($name.'_year',$filter['caption'],false,false,$years);
						$CFG->o_method_suppress = true;
						$form_filters->HTML('<input type="hidden" name="year_fields[]" value="'.$name.'_year" />');
						break;
				}
			}
			
			if ($group) 
				$form_filters->endGroup();
			
			$CFG->o_method_suppress = true;
			$form_filters->HTML('<input type="hidden" name="mode" value="'.$this->mode.'" />');
			$CFG->o_method_suppress = true;
			$form_filters->submitButton('submit',$CFG->filter_submit_text,false,'not_method');
			$CFG->o_method_suppress = true;
			$form_filters->resetButton('Reset',false,'not_method');
			$form_filters->display();
		}
	}
	
	function structureFolders($result) {
		global $CFG;

		if (!$result)
			return false;

		foreach ($result as $row) {
			if ($row['id'] == $this->current_folder_id) {
				$folders['this'] = $row;
				$id = $row['id'];
				$p_id = $row['p_id'];
			}
		}
		
		foreach ($result as $row) {
			if ($id > 0) {
				if ($p_id == $row['id'])
					$folders['up'] = $row;
				elseif ($row['p_id'] == $id)
					$folders['children'][] = $row;
			}
			else {
				if ($row['p_id'] > 0)
					$folders['children'][] = $row;
			}
			
			if (!($row['p_id'] > 0)) {
				$f_id = $row['id'];
				$folders['top_level'][$f_id] = $row;
			}
		}
		
		if ($p_id !== false) {
			$f_id = $id;
			$cur = ($folders['this']) ? $folders['this'] : array('p_id'=>0);
			$i = 0;
			while (!array_key_exists($f_id,$folders['top_level']) && ($f_id > 0)) {
				foreach ($result as $row) {
					if ($cur['p_id'] == $row['id']) {
						$f_id = $row['id'];
						$cur = $row;
						$folders['path'][] = $row;
					}
				}
				if ($i > 300)
					break;
				
				$i++;
			}
		}
		return $folders;
	}
	
	private function getIds($table) {
		if (!$table)
			return false;
			
		$sql = "SELECT id FROM $table";
		$result = db_query_array($sql);
		if ($result) {
			foreach ($result as $row) {
				$return[] = $row['id'];
			}
		}
		return $return;
	}
}
?>