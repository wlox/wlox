<?php

class Control {
	private static $class;
	
	function __construct($url,$action,$is_tab=false,$editor_mode=false) {
		global $CFG;

		if ($url && !User::permission(false,false,$url))
			return false;
			
		date_default_timezone_set($CFG->default_timezone);
		String::magicQuotesOff();

		$page_id = (is_numeric($url)) ? $url : Control::getPageId($url,$is_tab);
		if (!($page_id > 0))
			return false;
			

		$page_info = ($is_tab) ? DB::getRecord('admin_tabs',$page_id,0,1) : DB::getRecord('admin_pages',$page_id,0,1);
		
		if ($page_info['one_record'] == 'Y' && !$editor_mode) {
			$action = 'form';
			$_REQUEST['id'] = 1;
			$CFG->control_one_record = 1;
		}

		$controls = Control::getControls($page_id,$action,$is_tab);
		$CFG->editor_page_id = $page_id;
		$CFG->editor_is_tab = $is_tab;
		$CFG->is_ctrl_panel = $page_info['is_ctrl_panel'];

		if ($controls) {
			foreach ($controls as $c_id => $control) {
				$params = $control['params'];
				$is_static = ($params['is_static'] == 'Y');
				$class = $params['class'];
				$CFG->control_pass_id = $params['id'];
				
				if ($_REQUEST['cal_bypass'] && $class != 'Calendar')
					continue;
				
				if ($editor_mode) {
					$pm_methods = array();
		
					echo '<div class="pm_class_container" id="control_'.$params['id'].'">
							<div class="control_label">'.$params['class'].' '.$params['id'].'
								<a href="#" title="'.$CFG->move_hover_caption.'" class="move_handle dont_disable"></a>
								<a class="edit dont_disable" title="'.$CFG->edit_hover_caption.'" onclick="pmControlEdit(\'control_'.$params['id'].'\');"></a>
								<a class="delete dont_disable" title="'.$CFG->delete_hover_caption.'" onclick="pmControlDelete(\'control_'.$params['id'].'\');"></a>
							</div>
							<input type="hidden" class="this_class" id="control_'.$params['id'].'_class" value="'.$params['class'].'"/>
							<input type="hidden" class="this_page_id" id="control_'.$params['id'].'_page_id" value="'.$params['page_id'].'"/>
							<input type="hidden" class="this_action" id="control_'.$params['id'].'_action" value="'.$params['action'].'"/>
							<input type="hidden" class="this_id" id="control_'.$params['id'].'_id" value="'.$params['id'].'"/>';
				}
				
				if (!$is_static) {
					$ref = new ReflectionClass($class);
					$args = Control::parseArguments($params['arguments'],$class,'__construct');
					$this->class = $ref->newInstanceArgs($args);
					
					if ($class == 'Form') {
						if (!$CFG->in_include) {
							$this->class->verify();
							$this->class->save();
							$this->class->show_errors();
							$this->class->show_messages();
							$this->class->get(($page_info['url'] == 'my-account' || $url == 'my-account') ? User::$info['id'] : $_REQUEST['id']);
						}
						else {
							$this->class->get($CFG->include_id);
						}
						$this->class->info['p_id'] = $_REQUEST['p_id'];
						$this->class->info['f_id'] = $_REQUEST['f_id'];

						if ($page_info['url'] == 'my-account' || $url == 'my-account') {
							$CFG->o_method_suppress = true;
							$this->class->passiveField('id','ID');
							$CFG->o_method_suppress = true;
							$this->class->textInput('user',$CFG->user_username,true,false,false,false,false,false,false,false,1,$CFG->user_unique_error);
							$CFG->o_method_suppress = true;
							$this->class->passwordInput('pass',$CFG->user_password,true);
							$CFG->o_method_suppress = true;
							$this->class->passwordInput('pass1',$CFG->user_password,true,false,false,false,false,false,'pass');
							$CFG->o_method_suppress = true;
							$this->class->textInput('first_name',$CFG->user_first_name,true);
							$CFG->o_method_suppress = true;
							$this->class->textInput('last_name',$CFG->user_last_name,true);
							$CFG->o_method_suppress = true;
							$this->class->textInput('phone',$CFG->user_phone);
							$CFG->o_method_suppress = true;
							$this->class->textInput('email',$CFG->user_email);
							if (User::$info['is_admin'] == 'Y') {
								$CFG->o_method_suppress = true;
								$this->class->selectInput('f_id',$CFG->user_group,false,$_REQUEST['f_id'],false,'admin_groups',array('name'));
								$CFG->o_method_suppress = true;
								$this->class->checkBox('is_admin',$CFG->user_is_admin);
							}
						}
					}

					echo '<input type="hidden" id="control_'.$params['id'].'_table" value="'.$args['table'].'"/>';
				}
	
				if (is_array($control['methods'])) {
					foreach ($control['methods'] as $method) {
						if ($method['p_id'] > 0)
							continue;
							
						//$method['method'] = ($method['method'] == 'selectInput') ? 'fauxSelect' : $method['method'];

						$CFG->method_id = $method['id'];
						$args = Control::parseArguments($method['arguments'],$class,$method['method']);
						$inputs_array = self::getSubMethods($method['id'],$class);
						
						if (is_array($inputs_array)) {
							$args['inputs_array'] = $inputs_array;
						}
						if ($is_static) {
							call_user_func_array("{$class}::{$method['method']}",$args);
						}
						else {
							$method_instance = $ref->getMethod($method['method']);
							$method_instance->invokeArgs($this->class,$args);
						}
					}
				}
				$CFG->method_id = false;
				
				if ($class == 'Form' && !$CFG->pm_decouple_cancel) {
					$this->class->cancelButton($CFG->cancel_button);
				}
				
				if ($class == 'Record' && !$CFG->pm_decouple_cancel) {
					$d = new Form('dummy');
					$d->cancelButton($CFG->ok_button);
					$d->display();
				}
				
				if (!$is_static) {
					if ($class == 'Grid')
						$this->class->display($_REQUEST['page'.$this->class->i]);
					else
						$this->class->display();
				}
			
				if ($editor_mode) { 
					echo '
					<div class="clear">&nbsp;</div></div>';
				}
				$this->class = false;
			}
		}
		if ($editor_mode) { 
			echo '
			<script type="text/javascript">
				$(document).ready(function(){
					startEditor();
				});
			</script>';
		}
	}
	
	public static function getControls($id,$action,$is_tab=false) {
		$id_field = ($is_tab) ? 'tab_id' : 'page_id';
		$sql = "SELECT * FROM admin_controls WHERE {$id_field} = '$id' AND action = '$action' ORDER BY admin_controls.order ASC, admin_controls.id ASC";
		$result = db_query_array($sql);
		
		if ($result) {
			foreach ($result as $control) {
				$c_id = $control['id'];
				$controls[$c_id]['params'] = $control;
				$methods = db_query_array("SELECT * FROM admin_controls_methods WHERE control_id = $c_id ORDER BY admin_controls_methods.order ASC, admin_controls_methods.id ASC");
				if ($methods) {
					foreach ($methods as $method) {
						$controls[$c_id]['methods'][] = $method;
					}
				}
			}
		}
		return $controls;
	}
	
	public static function getMethods($control_id) {
		if (!($control_id > 0))
			return false;
			
		$sql = "SELECT * FROM admin_controls_methods WHERE control_id = $control_id ";
		return db_query_array($sql);
	}
	
	public static function getPageId($url,$is_tab=false,$return_row=false) {
		if ($url) {
			if ($is_tab) {
				$sql = "SELECT * FROM admin_tabs WHERE url = '$url'";
			}
			else {
				$sql = "SELECT * FROM admin_pages WHERE url = '$url'";
			}
			$result = db_query_array($sql);
		}
		else {
			$sql = "SELECT id FROM admin_tabs WHERE is_ctrl_panel = 'Y' AND for_group = ".User::$info['f_id'];
			$result = db_query_array($sql);
			if (!$result) {
				$sql = "SELECT id FROM admin_tabs WHERE is_ctrl_panel = 'Y'";
				$result = db_query_array($sql);
			}
		}

		if (!$return_row)
			return $result[0]['id'];
		else
			return $result[0];
	}
	
	public static function getCurrentTabId($url,$is_tab=false) {
		if (!$url)
			return false;
			
		$row = self::getPageId($url,$is_tab,1);
		return ($is_tab) ? $row['id'] : $row['f_id'];
	}
	
	public static function parseArguments($serialized,$class,$method) {
		$unserialized = unserialize($serialized);
		$method = new ReflectionMethod($class,$method);
		$params = $method->getParameters();
		$args = array();
		
		foreach ($params as $param) {
			$name = $param->getName();
			$args[$name] = $unserialized[$name];
		}

		$args = Control::unSerializeAll($args);
		if ($class == 'Record') {
			$args['record_id'] = $_REQUEST['id'];
		}
		return $args;
	}
	
	public static function unSerializeAll($args){
		if (is_array($args)) {
			foreach ($args as $key => $arg) {
				$check = @unserialize($arg);
				$s = ($check===false && $var != serialize(false)) ? false : true;
				
				if ($s) {
					$args[$key] = unserialize($arg);
				}
			}
		}
		return $args;
	}
	
	public static function getSubMethods($method_id,$class) {
		if (!($method_id > 0))
			return false;
			
		$sql = "SELECT * FROM admin_controls_methods WHERE p_id = $method_id ORDER BY admin_controls_methods.order ASC ";
		$result = db_query_array($sql);
		if ($result) {
			foreach ($result as $i => $row) {
				$method = $row['method'].'|'.$i;
				$args = unserialize($row['arguments']);
				$args['pm_method_id'] = $row['id'];
				$inputs_array[$method] = $args;
			}
		}
		return $inputs_array;
	}
	
	public static function findByTable($table,$action=false) {
		if (!$table)
			return false;
			
		$sql = 'SELECT * FROM admin_controls WHERE arguments LIKE \'%table%\"'.$table.'\"%\' ';
		if ($action) {
			$action = ($action == 'list') ? false : $action;
			$sql .= " AND action = '$action'";
		}
		return db_query_array($sql);
	}
}
?>