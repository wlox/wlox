<?php

class Upload {
	function saveTemp($form_name,$input_name=false) {
		global $CFG;
		
		$file_reqs = $_REQUEST['files'];
		$file_info = $_FILES[$form_name];
		$file_parts = ($input_name) ? explode('.',$file_info['name'][$input_name]) : explode('.',$file_info['name']);
		$file_name = ($input_name) ? $file_info['name'][$input_name] : $file_info['name'];
		$tmp_name = ($input_name) ? $file_info['tmp_name'][$input_name] : $file_info['tmp_name'];
		$ext = end($file_parts);
		$size = ($input_name) ? $file_info['size'][$input_name] : $file_info['size'];
		$input_name_parts = explode('__',$input_name);
		$input_name_n = $input_name_parts[0];;

		if ($size <= 0)
			return array('error' => $CFG->verify_file_misc_error,'input_name' => $input_name);
		if (is_array($file_reqs[$input_name_n]['exts']) && !@in_array(strtolower($ext),$file_reqs[$input_name_n]['exts']))
			return array('error' => $CFG->verify_file_type_error,'input_name' => $input_name);
		if (!empty($file_reqs[$input_name_n]['max_size']) && ($size > $file_reqs[$input_name_n]['max_size']))
			return array('error' => $CFG->verify_file_size_error,'input_name' => $input_name);
		
		if (move_uploaded_file($tmp_name,$CFG->dirroot.$CFG->temp_file_location.$file_name)) {
			return array('filename' => $file_name,'input_name' => $input_name,'file_desc'=>$_REQUEST['file_descs_new'][$input_name]);
		}
		else {
			return array('error' => $CFG->verify_file_misc_error,'input_name' => $input_name);
		}
		
	}
	
	// $img_sizes takes an array of all the sizes as array([height] => [width]);
	// if no table or id are provided, it returns the filename
	function save($temp_name,$field_name=false,$table=false,$id=false,$dir = false,$img_sizes = false,$field_name_n=false) {
		global $CFG;
		
		if ($temp_name) {
			$temp_name1 = $temp_name;
			$temp_name = $CFG->dirroot.$CFG->temp_file_location.$temp_name;
			$dir1 = $dir;
			$dir = ($dir) ? $CFG->dirroot.$dir : $CFG->dirroot.$CFG->default_upload_location;
			$dir = (!stristr($dir,'/')) ? $dir.'/' : $dir;
			$file_parts = explode('.',$temp_name);
			$ext = strtolower(end($file_parts));
			
			DB::saveImageSizes($field_name,$_REQUEST['image_sizes'][$field_name]);
			$image_sizes = DB::getImageSizes($field_name);
			
			if ($id) {
				$ext1 = (in_array($ext,$CFG->accepted_image_formats)) ? 'jpg' : $ext;
				$i = DB::insert($table.'_files',array('f_id'=>$id,'ext'=>$ext1,'dir'=>$dir1,'old_name'=>$temp_name1,'field_name'=>$field_name));
				self::saveDescriptions($table,$field_name_n,$i);
				
				if (in_array($ext,$CFG->accepted_image_formats)) {
					$ir = new ImageResize($temp_name,false,false,false);
					
					if ($_REQUEST['crop_images'][$field_name])
						$ir->setAutoCrop(true);
					
					$ir->setHighQuality();
					
					if (!is_array($img_sizes)) {
						$ir->save($dir.$table.'_'.$id.'_'.$i.'.'.$ext1,'jpeg',90);
						if ($_REQUEST['encrypt_files'][$field_name])
							File::encrypt($dir.$table.'_'.$id.'_'.$i.'.'.$ext1);
					}
					else {
						foreach ($image_sizes as $key => $size) {
							$ir->setSize($size['width'],$size['height']);
							$ir->save($dir.$table.'_'.$id.'_'.$i.'_'.$key.'.'.$ext1,'jpeg',90);
							if ($_REQUEST['encrypt_files'][$field_name])
								File::encrypt($dir.$table.'_'.$id.'_'.$i.'_'.$key.'.'.$ext1);
						}
					}
					@unlink($temp_name);
					return true;
				}
				else {
					if (rename($temp_name,$dir.$table.'_'.$id.'_'.$i.'.'.$ext)) {
						if ($_REQUEST['encrypt_files'][$field_name])
							File::encrypt($dir.$table.'_'.$id.'_'.$i.'.'.$ext);
						
						return true;
					}
				}
			}
			else {
				if (@rename($temp_name,$dir.$temp_name)) {
					if ($_REQUEST['encrypt_files'][$field_name])
						File::encrypt($dir.$dir.$temp_name);
						
					return $dir.$temp_name;
				}
			}
		}
		else {
			return false;
		}
	}
	
	function saveDescriptions($table,$field_name,$i) {
		if (!($i > 0) || !$table)
			return false;
		
		if ($field_name && @array_key_exists($field_name,$_REQUEST['file_descs_new']))
			$desc = $_REQUEST['file_descs_new'][$field_name];
		elseif ($field_name && @array_key_exists($field_name,$_REQUEST['file_descs_temp']))
			$desc = $_REQUEST['file_descs_temp'][$field_name];
		elseif (@array_key_exists($i,$_REQUEST['file_descs']))
			$desc = $_REQUEST['file_descs'][$i];
			
		if ($desc)
			return DB::update($table.'_files',array('file_desc'=>$desc),$i);
			
		return false;
	}
}
?>