<?php

date_default_timezone_set($CFG->default_timezone);
String::magicQuotesOff();

if ($CFG->action != 'add_class') {
	$pm = new PageMaker($_REQUEST['id'],$CFG->action,$CFG->is_tab);
	$pm->showEditor($_REQUEST['tab_bypass']);
}
else {
	if ($_REQUEST['method'] == 'grid') {
		$_REQUEST['parent_method_id'] = 0;
	}
	
	if (!($_REQUEST['parent_method_id'] > 0)) {
		$parent_method_id = 0;
		$c_id = $_REQUEST['control_id'];
		$class = $_REQUEST['class'];
		$method = ($_REQUEST['method']) ? $_REQUEST['method'] : '__construct';
		$table = ($c_id > 0) ? 'admin_controls_methods' : 'admin_controls';
	}
	else {
		$class = 'Form';
		$parent_method_id = $_REQUEST['parent_method_id'];
		$parent_method_text = ' in grid';
		$method = $_REQUEST['method'];
		$table = 'admin_controls_methods';
	}

	$db_tables = DB::getTables();
	$is_tab = $_REQUEST['is_tab'];
	$next_page_id = $_REQUEST['pm_page_id'];
	$next_page_action = $_REQUEST['pm_action'];
	$method = ($method == 'selectInput') ? 'fauxSelect' : $method; 
	if (!empty($_REQUEST['field_name'])) {
		$fn = $_REQUEST['field_name'];
		$fv = $_REQUEST['field_value'];
		$table_fields = DB::getTableFields($fv,false,true);
	}
	
	echo "<span id=\"edit_title\">{$class}::{$method}{$parent_method_text}</span>";
	
	$CFG->in_popup = 1;
	$form = new Form('form',false,false,false,$table);
	$form->record_id = $_REQUEST['id'];
	
	if ($_REQUEST['id']) {
		$info = DB::getRecord($table,$_REQUEST['id']);
		$args = unserialize($info['arguments']);
		if (is_array($args)) {
			foreach ($args as $name => $value) {
				$name = 'argument_'.$name;
				$args1[$name] = $value;
			}
		}
		else {
			$args1 = array();
		}
		$form->info = array_merge($args1,$form->info);
	}
	
	if ($c_id > 0) {
		$form->hiddenInput('control_id',false,false,false,'int');
		$form->hiddenInput('method');
	}
	elseif ($parent_method_id > 0) {
		if ($parent_method_id > 0) {
			$form->hiddenInput('p_id',false,$parent_method_id,false,'int');
		}
		elseif ($info['p_id'] > 0) {
			$form->hiddenInput('p_id',false,$info['p_id'],false,'int');
		}
		$form->hiddenInput('method');
	}
	else {
		if ($is_tab) {
			$form->hiddenInput('tab_id',false,false,false,'int');
		}
		else {
			$form->hiddenInput('page_id',false,false,false,'int');
		}
		$form->hiddenInput('action');
		$form->hiddenInput('class');
	}
	
	$ref = new ReflectionClass($class);
	$params = $ref->getMethod($method)->getParameters();
	$m_name = $ref->getMethod($method)->getName();

	if (is_array($params)) {
		foreach ($params as $param) {
			$name = $param->getName();
			$required = ($param->isDefaultValueAvailable()) ? false : true;
			if ($name == 'image_sizes' || $name == 'insert_array' || $name == 'formula_id_field' || ($name == 'variables') || ($m_name == 'addTable' && $name == 'filters')) {
				$form->info['argument_'.$name] = String::fauxArray($form->info['argument_'.$name]);
				$form->textInput('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,false,false,false,false,false,false,true);
			}
			elseif ($m_name == 'catSelect' && $name == 'input_type') {
				$form->selectInput('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,false,array(''=>'Checkbox','textInput'=>'Text input'));
			}
			elseif ($name == 'color') {
				$form->colorPicker('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required);
			}
			elseif ($name == 'formula') {
				$form->textArea('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required);
			}
			elseif (($m_name == 'selectInput' || $m_name == 'filterSelect') && $name == 'level') {
				$form->selectInput('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,false,array(1=>1,2=>2,3=>3,4=>4,5=>5));
			}
			elseif ($m_name == 'startArea' && $name == 'class') {
				$form->selectInput('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,false,array('box_left'=>'Box Left','box_right'=>'Box Right','box'=>'Box','left'=>'Left','right alert'=>'Right Alert','box_left alert'=>'Box Left Alert','box_right alert'=>'Box Right Alert','box alert'=>'Box Alert','left alert'=>'Left Alert','right alert'=>'Right Alert'));
			}
			elseif (($m_name == 'startRestricted' && ($name == 'groups' || $name == 'exclude_groups')) || $name == 'download_encrypted_group') {
				$form->autoComplete('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,false,1,false,'admin_groups',array('name'));
			}
			elseif ($m_name == 'startRestricted' && ($name == 'users' || $name == 'exclude_users')) {
				$form->autoComplete('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,false,1,false,'admin_users',array('first_name','last_name'));
			}
			elseif ($name == 'create_db_field') {
				$form->selectInput('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,false,array(''=>'','int'=>'INT','date'=>'DATE','datetime'=>'DATETIME','vchar'=>'VARCHAR','checkbox'=>'Y/N'));
			}
			elseif ($name == 'j' || $name == 'static' || $name == 'grid_input' || $name == 'inputs_array') {
				continue;
			}
			elseif ($name == 'is_inset') {
				$form->hiddenInput('argument_'.$name,false,1);
			}
			elseif ($name == 'aggregate_function') {
				$form->selectInput('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,false,array('grand_total'=>'Grand Total','page_total'=>'Page Total','both_total'=>'Both Grand and Page Totals','page_avg'=>'Page Average','grand_avg'=>'Grand Average','both_avg'=>'Both Grand and Page Average'));
			}
			elseif ($name == 'cumulative_function') {
				$form->selectInput('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,false,array('sum'=>'Sum','avg'=>'Average'));
			}
			elseif (stristr($name,'fields')) {
				if (!empty($_REQUEST['c_table']) && $_REQUEST['c_table'] != 'undefined') $table_fields = DB::getTableFields($_REQUEST['c_table'],false,true);
				if (is_array($_REQUEST['added_tables'])) {
					foreach ($_REQUEST['added_tables'] as $added_table) {
						$added_fields = DB::getTableFields($added_table,false,true);
						if (is_array($added_fields)) {
							$table_fields = (is_array($table_fields)) ? $table_fields : array();
							$table_fields = array_merge($table_fields,$added_fields);
						}
					}
				}
				$form->autoComplete('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,false,true,$table_fields,false,false,false,false,false,false,false,$t_field,false,false,false,false,false,false,false,false,false,1);
			}
			elseif($name == 'name' && $m_name == 'field' && $class != 'Calendar') {
				$table_fields = DB::getTableFields($_REQUEST['c_table'],false,true);
				$form->autoComplete('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,false,false,$table_fields);
				
			}
			elseif (((stristr($name,'table')) || (stristr($m_name,'table') && $name == 'name')) && (!stristr($name,'id')) && (!stristr($name,'enable'))) {
				$form->autoComplete('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,$fields_table,false,$db_tables);
				$t_field = 'argument_'.$name;
			}
			elseif ($name == 'checked' || $name == 'is_media' || $name == 'accept_children' || $name == 'dragdrop' || $name == 'show_buttons' || $name == 'filter' || $name == 'return_to_self' || $name == 'not_equals') {
				$form->checkBox('argument_'.$name,ucfirst(str_replace('_',' ',$name)));
			}
			elseif (stristr($name,'target') && $class != 'Form') {
				$form->selectInput('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required,'content',array('content'=>'Main','edit_box'=>'Popup'));
			}
			elseif ($name == 'disable_if_no_record_id') {
				$form->textInput('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required);
			}
			elseif (!stristr($name,'record_id') && !stristr($name,'error') && $name != 'action' && $name != 'is_navigation') {
				$form->textInput('argument_'.$name,ucfirst(str_replace('_',' ',$name)),$required);
			}
		}
	}
	
	if ($m_name == 'textInput' && $parent_method_id > 0) {
		$form->textInput('argument_show_total','Show total');
	}
	$form->hiddenInput('order',false,$_REQUEST['order']);
	$form->button(false,$CFG->ok_button,false,false,false,'primary','onclick="saveForm(this,\'pm_editor\',\'index.php?tab_bypass=1&current_url='.$CFG->url.'&id='.$next_page_id.'&action='.$next_page_action.'&is_tab='.$is_tab.'\')"');
	$form->button(false,$CFG->cancel_button,false,false,false,false,'onclick="closePopup(this)"');
	$form->display();
}
?>