<?php
class File {
	function showLink($file,$path=false,$icon=true,$no_link=false) {
		global $CFG;
		
		$url = File::fileExists($file,$path);
		if (!$url) 
			return '<span class="error">File '.$file.' not found.</span>';
		
		if ($icon) {
			$file_parts = explode('.',$file);
			$ext = end($file_parts);

			$file_icon = File::showExtIcon($ext);
		}
		
		if (!$no_link)
			return "$file_icon <a target=\"_blank\" href=\"$url\">$file</a>";
		else
			return "$file_icon $file";
	}
	
	function showLinkForResult($row,$icon=true,$table=false,$no_link=false) {
		global $CFG;
		
		if (!$row['url']) {
			$image_sizes = DB::getImageSizes($row['field_name']);
			$dir = (!$row['dir']) ? $CFG->default_upload_location : $row['dir'];
			if (in_array($row['ext'],$CFG->accepted_image_formats)) {
				foreach ($image_sizes as $size => $dims) {
					$suffix = $size;
				}
				$suffix = '_'.$suffix;
			}
			$url = File::fileExists($row['name'].$suffix.'.'.$row['ext'],$dir,$table);
			if (!$url) 
				return '<span class="error">File '.$row['old_name'].' not found.</span>';
				
			$file_icon = ($icon) ? File::showExtIcon($row['ext']) : false;
			
			if (!$no_link)
				return "$file_icon <a target=\"_blank\" href=\"$url\">{$row['old_name']}</a>";
			else
				return "$file_icon {$row['old_name']}";
		}
		else {
			return File::showUrl($row['url'],$icon);
		}
	}
	
	// returns full relative url to file
	function fileExists($file,$path=false,$table=false) {
		global $CFG;
		
		if (stristr($file,'http://')) {
			if (fopen($file,'r')) 
				return $file;
			else
				return false;
		}
		
		if (!file_exists($CFG->dirroot.$path.$file)) {
			if (!file_exists($CFG->dirroot.$CFG->default_upload_location.$file)) {
				if ($table) {
					$table = str_ireplace('_files','',$table);
					$orig_name = str_ireplace($table.'_','',$file);
					if (!file_exists($CFG->dirroot.$path.$orig_name)) {
						if (!file_exists($CFG->dirroot.$CFG->default_upload_location.$orig_name))
							return false;
						else
							return $CFG->baseurl.$CFG->default_upload_location.$orig_name;
					}
					else
						return $CFG->baseurl.$path.$orig_name;
				}
				else
					return false;
			}
			else
				return $CFG->baseurl.$CFG->default_upload_location.$file;
		}
		else {
			return $CFG->baseurl.$path.$file;
		}
	}
	
	function showExtIcon($ext) {
		global $CFG;
		
		$icon = ($CFG->file_icons[$ext]) ? $CFG->file_icons[$ext] : $CFG->file_icons['file'];
		$icon = '<img src="'.$icon.'" />';
		
		return $icon;
	}
	
	function showUrl($url,$icon=true) {
		if(strstr($url, "<embed")) {
			$url = String::getUrlFromEmbed($url);
		}
		
		if(strstr($url, "youtube")) 
			$player = 'youtube';
		
		if(!strstr($url, "http://")) {
	        $url = "http://".$url;
	    }
	    
	    if ($icon) {
			$file_parts = explode('.',$url);
			$ext = (!$player) ? end($file_parts) : $player;
		
			$file_icon = File::showExtIcon($ext);
		}
    	return "$file_icon <a href=\"$url\">$url</a>";
	}
	
	function urlExists($url) {
	    if(!strstr($url, "http://")) {
	        $url = "http://".$url;
	    }
	
	    $fp = @fsockopen($url, 80);
	    if($fp === false)
	    {
	        return false;   
	    }
	    return true;
	}
	
	function deleteLike($search,$dir) {
		if (!$search || !$dir)
			return false;
		
		$files = scandir($CFG->dirroot.$dir);
		$matches = preg_grep("/$search(.)+/",$files);
		if ($matches) {
			foreach ($matches as $file) {
				$url = File::fileExists($file,$dir);
				if ($url) unlink($url);
			}
		}
	}
	
	function encrypt($filename) {
		$file = file_get_contents($filename);
		$encrypted = Encryption::encrypt($file);
		file_put_contents($filename,$encrypted);
	}
	
	function decrypt($filename) {
		$file = file_get_contents($filename);
		$decrypted = Encryption::decrypt($file);
		file_put_contents($filename,$decrypted);
	}
}
?>