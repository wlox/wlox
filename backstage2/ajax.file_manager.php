<?php


if ($_REQUEST['action'] == 'download') {
	header('Content-type: application/zip');
	header('Content-Disposition: attachment; filename="archivos_encriptados.zip"');
}

include'lib/common.php';
$action = $_REQUEST['action'];

if ($action == 'expand') {
	$sql = "SELECT * FROM {$_REQUEST['folder_table']} WHERE p_id = {$_REQUEST['p_id']}";
	$result = db_query_array($sql);
	
	if ($result) {
		foreach ($result as $row) {
			$has_sub = (FileManager::hasSubfolders($row['id'],$_REQUEST['folder_table'])) ? 'this' : 'false';
			$no_triangle = ($has_sub == 'false') ? 'not' : '';
			echo '
			<div class="folder_container">
				<div id="triangle" class="triangle'.$down.' '.$no_triangle.'" onclick="file_manager.showSubfolders('.$row['id'].','.$has_sub.')"></div>
				<div class="folder" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder('.$row['id'].','.$has_sub.')"></div>
				<div class="desc" onclick="file_manager.select(this,event);" ondblclick="file_manager.openFolder('.$row['id'].','.$has_sub.')">'.$row['name'].'</div>
				<div class="clear"></div>
			</div>';
		}
	}
}
elseif ($action == 'drop') {
	if ($_REQUEST['rows']) {
		foreach ($_REQUEST['rows'] as $id => $row) {
			DB::update($row['table'],array($row['folder_field']=>$row['p_id']),$id);
		}
		Messages::add($CFG->ajax_save_message);
		Messages::display();
	}
}
elseif ($action == 'download') {
	if ($_REQUEST['download']) {
		$page_id = Control::getPageId($_REQUEST['current_url'],$_REQUEST['is_tab']);
		$controls = Control::getControls($page_id,'form',$_REQUEST['is_tab']);
		$key = key($controls);
		$methods = $controls[$key]['methods'];
		if ($methods) {
			foreach ($methods as $method) {
				if ($method['method'] == 'fileInput' || $method['method'] == 'fileMultiple') {
					$args = Control::parseArguments($method['arguments'],'Form',$method['method']);
					$field_names[] = $args['name'];
					$image_sizes = DB::getImageSizes($args['name']);
					end($image_sizes);
					$suffixes[$args['name']] = key($image_sizes);
				}
			}
		}
		
		$filename = $CFG->dirroot.$CFG->temp_file_location.'archivos_'.date('Y-m-d').'.zip';
		$zip = new ZipArchive;
		$res = $zip->open($filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
		if ($res) {
			foreach ($_REQUEST['download'] as $table => $ids) {
				$record_ids = explode('|',$ids);
				$table_fields = explode('|',$_REQUEST['table_fields']);
				if (is_array($record_ids)) {
					foreach ($record_ids as $id) {
						$record = DB::getRecord($_REQUEST['current_url'],$id);
						$files = DB::getFiles($_REQUEST['current_url'].'_files',$id);
						if ($files) {
							$i = 1;
							foreach ($files as $row) {
								$i = ($name_in_zip != $old_name) ? 1 : $i;
								$f_name = $row['field_name'];
								$suffix = '_'.$suffixes[$f_name];
								$row['name'] = ($row['name']) ? $row['name'] : $row['f_id'].'_'.$row['id'];
								$url = File::fileExists($row['name'].$suffix.'.'.$row['ext'],$CFG->default_upload_location,$_REQUEST['current_url']);
								if (!$url)
									$url = File::fileExists($row['f_id'].'_'.$row['id'].'.'.$row['ext'],$CFG->default_upload_location,$_REQUEST['current_url']);
								
								if ($url) {
									$name_parts = array();
									if ($table_fields) {
										foreach ($table_fields as $field_name) {
											$name_parts[] = str_replace(' ','_',$record[$field_name]);
										}
									}
									$name_in_zip = (count($name_parts) > 0) ? implode('_',$name_parts).'_'.$i.'.'.$row['ext'] : $url;
									$str = file_get_contents($url);
									$decrypted = Encryption::decrypt($str);
									$zip->addFromString($name_in_zip,$decrypted);
								}
								$old_name = $name_in_zip;
								$i++;
							}
						}
					}
				}
			}
		}
		$zip->close();
		
		echo file_get_contents($filename);
	}
}
?>