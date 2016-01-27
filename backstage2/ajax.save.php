<?php
	include'lib/common.php';
	String::magicQuotesOff();
//print_ar($_REQUEST);
	$action = $_REQUEST['action'];
	if ($action == 'delete') {
		if (is_array($_REQUEST['rows'])) {
			foreach ($_REQUEST['rows'] as $table => $rows) {
				if (!$table || $table == 'undefined')
					continue;
					
				if (is_array($rows)) {
					$subtables = DB::getSubtables($table);
					
					foreach ($rows as $id) {
						if (is_array($_REQUEST['sub_records'])) {
							DB::deleteRecursive($table,$id);
						}
						elseif (!(db_delete($table,$id))) {
							$errors[] = $CFG->ajax_delete_error;
						}
						DB::deleteFiles($table.'_files',$id);
						DB::deleteCats($table,$id);
						if ($_REQUEST['delete_controls']) {
							$f_key = ($table == 'admin_tabs') ? 'tab_id' : 'page_id';
							$sql = "
							DELETE admin_controls.*,admin_controls_methods.* 
							FROM admin_controls
							LEFT JOIN admin_controls_methods ON (admin_controls_methods.control_id = admin_controls.id)
							WHERE admin_controls.{$f_key} = $id";
							if (!db_query($sql)) {
								$errors[] = $CFG->ajax_delete_error;
							}
						}
					}
				}
			}
		}
		else {
			
			if (!(db_delete($_REQUEST['table'],$_REQUEST['id']))) {;
				$errors[] = $CFG->ajax_delete_error;
			}

			if (!empty($_REQUEST['subtable']) && $_REQUEST['subtable'] != 'false') {
				$f_id_field = (empty($_REQUEST['f_id_field'])) ? 'f_id' : $_REQUEST['f_id_field'];
				db_delete($_REQUEST['subtable'],$_REQUEST['id'],$f_id_field);
			}
			if (!empty($_REQUEST['filename'])) {
				$parts = pathinfo($_REQUEST['filename']);
				$name = $parts['filename'];
				$ext = $parts['extension'];
				
				File::deleteLike($name,$_REQUEST['dir']);
			}
		}
	}
	elseif ($action == 'set_active') {
		if (is_array($_REQUEST['rows'])) {
			foreach ($_REQUEST['rows'] as $table => $rows) {
				if (is_array($rows)) {
					$active = ($_REQUEST['active'] == 1) ? 'Y' : 'N';
					foreach ($rows as $id) {
						if (!(DB::update($table,array('is_active'=>$active),$id))) {
							$errors[] = $CFG->ajax_save_error;
						}
					}
				}
			}
		}
	}
	elseif ($action == 'delete_file') {
		if (!empty($_REQUEST['filename'])) {
			unlink($_REQUEST['filename']);
		}
	}
	elseif ($action == 'check_table') {
		if (!DB::tableExists($_REQUEST['table'])) {
			DB::createTable($_REQUEST['table'],$_REQUEST['db_fields'],$_REQUEST['radioinputs']);
		}
		else {
			DB::editTable($_REQUEST['table'],$_REQUEST['db_fields'],$_REQUEST['radioinputs']);
		}
	}
	elseif ($_REQUEST['rows'] && !empty($_REQUEST['rows'])) {
		foreach ($_REQUEST['rows'] as $row) {
			if ($row['info']) {
				if ($row['id']) {
					DB::update($row['table'],$row['info'],$row['id']);
				}
				else {
					DB::insert($row['table'],$row['info']);
				}
			}
		}
	}
	elseif ($_REQUEST['table']) {
		if ($row['id'])
			if (!(DB::update($_REQUEST['table'],$_REQUEST['info'],$_REQUEST['id']))) {
				$errors[] = $CFG->ajax_save_error;
			}
		else
			if (!(DB::insert($_REQUEST['table'],$_REQUEST['info']))) {
				$errors[] = $CFG->ajax_insert_error;	
			}
	}
	elseif ($_REQUEST['delete']) {
		
	}
	
	if ($_REQUEST['l_order'] && is_array($_REQUEST['l_order'])) {
		foreach ($_REQUEST['l_order'] as $table => $items) {
			$table_fields = DB::getTableFields($table,true);
			if (!in_array('order',$table_fields))
				continue;
			
			$i = 0;
			foreach ($items as $order => $id) {
				if (in_array('page_map_reorders',$table_fields) && $table == 'admin_pages') {
					$rec = DB::getRecord('admin_pages',$id,0,1);
					if ($rec['page_map_reorders'] > 0)
						continue;
				}
				DB::update($table,array('order'=>$i),$id);
				$i++;
			}
		}
	}
	
	if (is_array($_REQUEST['page_map'])) {
		if (is_array($_REQUEST['page_map']['methods'])) {
			foreach ($_REQUEST['page_map']['methods'] as $order => $id) {
				DB::update('admin_controls_methods',array('order'=>$order),$id);
			}
		}
		if (is_array($_REQUEST['page_map']['pages'])) {
			foreach ($_REQUEST['page_map']['pages'] as $order => $id) {
				DB::update('admin_pages',array('order'=>$order,'page_map_reorders'=>1),$id);
			}
		}
	}
	
	if (!$errors) {
		Messages::add($CFG->ajax_save_message);
		Messages::display();
	}
	else {
		Errors::merge($errors);
		Errors::display();
	}

?>