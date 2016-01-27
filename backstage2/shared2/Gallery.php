<?php
class Gallery {
	static $i = 0,$image_sizes;

	// $tables can be string [table] or array of tables
	public static function multiple($tables,$f_id=false,$field_name=false,$size=false,$thumbnails=0,$class=false,$limit=0,$dimensions_l=false,$dimensions_s=false,$get_still_image=false,$overlay=false,$start_record=0,$left_arrow=false,$right_arrow=false,$randomize=false,$link=false,$link_target_blank=false,$encrypted=false,$alt_field=false) {
		global $CFG;
		
		if (!$tables) return false;
		if ($CFG->backstage_mode && !($f_id > 0)) return false;
		self::$image_sizes = DB::getImageSizes($field_name);
		$left_arrow = ($left_arrow) ? $left_arrow : $CFG->gallery_left_arrow;
		$right_arrow = ($right_arrow) ? $right_arrow : $CFG->gallery_right_arrow;
		
		$tables_array = (is_array($tables)) ? $tables : array($tables);
		$class = ($class) ? $class : 'image_gallery';
		$small = key(self::$image_sizes);
		$small_dimensions = self::$image_sizes[$small];
		$small_dimensions = ($dimensions_s) ? $dimensions_s : $small_dimensions;
		end(self::$image_sizes);
		$large = key(self::$image_sizes);
		$large_dimensions = ($size) ? self::$image_sizes[$size] : self::$image_sizes[$large];
		$large_dimensions = ($dimensions_l) ? $dimensions_l : $large_dimensions;
		$size = ($size) ? $size : $large;
		
		if ($overlay) {
			$overlay = File::fileExists($overlay);
			if ($overlay) {
				$overlay = '<img style="height:'.$large_dimensions['height'].'px; width:'.$large_dimensions['width'].'px;" class="gallery_overlay" src="'.$overlay.'" />';
			}
		}
	
		foreach ($tables_array as $table) {
			$s_items = DB::getFiles($table,$f_id,$field_name,$limit,$start_record,$randomize);
			if ($s_items)
				$items = ($items) ? array_merge($items,$s_items) : $s_items;
		}

		if (!$items) $items = array(0 => array('ext' => 'jpg'));
		//$class = ($CFG->pm_editor) ? 'pm_method' : $class;
		
		$j = 0;
		$HTML = '<div class="'.$class.'" id="image_gallery_'.Gallery::$i.'">
					<input type="hidden" id="gallery_name_'.$field_name.'" class="gallery_name" value="'.$field_name.'" />
					<div id="display" style="/*height:'.$large_dimensions['height'].'px; width:'.$large_dimensions['width'].'px;*/ position: relative;">';
		if (count($items) > 1) {
		$HTML .=	'   <div onclick="galleryNext(this,-1)" id="left-arrow">'.$CFG->gallery_left_arrow.'</div>
						<div onclick="galleryNext(this,1)" id="right-arrow">'.$CFG->gallery_right_arrow.'</div>';
		}
	
		foreach ($items as $row) {
			$description = ($row['file_desc']) ? '<div class="file_desc">'.$row['file_desc'].'</div>' : '';
			if (!empty($row['url'])) {
				$item = Gallery::url($row['url'],$size,$large_dimensions,$get_still_image,$field_name);
			}
 			elseif (in_array($row['ext'],$CFG->accepted_image_formats)) {
				$item = Gallery::image($row,$size,$large_dimensions,$field_name,false,$tables_array[0],$link,$link_target_blank,$encrypted,$alt_field);
			}
			elseif(in_array($row['ext'],$CFG->accepted_audio_formats)) {
				
			}
			elseif($row['ext'] == 'swf') {
				$item = Gallery::flash($row,$size,$large_dimensions,$field_name,false,$tables_array[0]);
			}
			if ($item) {
				$display = ($j == 0) ? '' : 'none';
				$HTML .= '<div class="gallery_item gf_'.$row['field_name'].'" id="'.$j.'" style="display:'.$display.'">'.$item.$overlay.$description.'</div>';
				$j++;
			}
		}
		$HTML .= '	</div>';
		
		if ($thumbnails > 0) {
			$k = 0;
			$HTML .= '<div id="thumbs">
						<ul>';
			if (count($items) > $thumbnails) {
				$HTML .= '<li><div id="left-arrow" onclick="galleryThumbsNext(this,-1,'.$thumbnails.')">'.$left_arrow.'</div></li>';
			}
			foreach ($items as $row) {
				if (!empty($row['url'])) {
					$item = Gallery::url($row['url'],$small,$small_dimensions,$get_still_image,$field_name,true);
				}
 				elseif (in_array($row['ext'],$CFG->accepted_image_formats)) {
					$item = Gallery::image($row,$small,$small_dimensions,$field_name,true,false,false,false,$encrypted,$alt_field);
				}
				elseif(in_array($row['ext'],$CFG->accepted_audio_formats)) {
					
				}
				
				if ($item) {
					$display = ($k < $thumbnails) ? '' : 'none';
					$HTML .= '<li class="gallery_thumb '.(($k == 0) ? 'selected' : '').'" id="'.$k.'" style="display:'.$display.';" onclick="galleryShow(this)"><div>'.$item.'</div></li>';
					$k++;
				}
			}
			
			if (count($items) > $thumbnails) {
				$HTML .= '	<li><div id="right-arrow" onclick="galleryThumbsNext(this,1,'.$thumbnails.')">'.$right_arrow.'</div></li>';
			}
			if ($CFG->backstage_mode) {
				$HTML .= '
				<script type="text/javascript">
					thumbsResize('.Gallery::$i.');
				</script>';
			}
			$HTML .= '	</ul><div style="clear: both;height:0;">&nbsp;</div>
					</div>
					<div style="clear: both;height:0;">&nbsp;</div>';
		}
		$HTML .= '</div>';
		Gallery::$i++;
		
		return $HTML;
	}
	
	function image($row,$size=false,$dimensions=false,$field_name=false,$img_size_persist=false,$table=false,$link=false,$link_target_blank=false,$encrypted=false,$alt_field=false) {
		global $CFG;

		if (!is_array(self::$image_sizes) || !$img_size_persist)
			self::$image_sizes = DB::getImageSizes($field_name);
			
		if ($size && array_key_exists($size, self::$image_sizes)) {
			$suffix = '_'.$size;
			$dimensions = ($dimensions) ? $dimensions : self::$image_sizes[$size];
		}

		$row['name'] = ($row['name']) ? $row['name'] : $row['f_id'].'_'.$row['id'];
		$dir = (!$row['dir']) ? $CFG->default_upload_location : $row['dir'];
		$url = File::fileExists($row['name'].$suffix.'.'.$row['ext'],$dir,$table);
		$url = ($url) ? $url : $CFG->placeholder_image;
		
		if ($alt_field) {
			if (@array_key_exists($alt_field,$row)) {
				$alt = $row[$alt_field];
			}
			elseif ($row['name'])
				$alt = $row['name'];
			elseif ($row['nombre'])
				$alt = $row['nombre'];
			elseif ($row['title'])
				$alt = $row['title'];
			elseif ($row['titulo'])
				$alt = $row['titulo'];
			elseif ($row['desc'])
				$alt = $row['desc'];
			elseif ($row['description'])
				$alt = $row['description'];
			elseif ($row['descripcion'])
				$alt = $row['descripcion'];
		}

		if ($encrypted)
			$url = 'includes/encrypted_image.php?url='.urlencode($url);
		
		if (empty($link)) {
			return "<img src=\"$url\" alt=\"$alt\" height=\"{$dimensions['height']}\" width=\"{$dimensions['width']}\" />";
		}
		else {
			$target = ($link_target_blank) ? '_blank' : false;
			return "<a href=\"$link\" target=\"$target\"><img alt=\"$alt\" src=\"$url\" height=\"{$dimensions['height']}\" width=\"{$dimensions['width']}\" /></a>";
		}
	}
	
	function imageUrl($url,$dimensions=false,$field_name=false,$field_name=false,$maintain_ratio=false,$img_size_persist=false) {
		
		if (!is_array(self::$image_sizes) || !$img_size_persist)
			self::$image_sizes = DB::getImageSizes($field_name);
			
		$url = File::fileExists($url);
		$url = ($url) ? $url : $CFG->placeholder_image;
		
		if ($maintain_ratio) {
			$info = @getimagesize($url);
			if (is_array($info)) {
				$width = $info[0];
				$height = $info[1];
				if ($width >= $height) {
					$ratio = $dimensions['width'] / $width;
				}
				else {
					$ratio = $dimensions['height'] / $height;
				}
				$dimensions['width'] = $dimensions['width'] * $ratio;
				$dimensions['height'] = $dimensions['height'] * $ratio;
			}
		}
		
		return "<img src=\"$url\" height=\"{$dimensions['height']}\" width=\"{$dimensions['width']}\" />";
	}
	
	function url($url,$size=false,$dimensions=false,$get_still_image=false,$field_name=false,$img_size_persist=false) {
		global $CFG;
		
		if (!is_array(self::$image_sizes) || !$img_size_persist)
			self::$image_sizes = DB::getImageSizes($field_name);
		
		if ($size && array_key_exists($size, self::$image_sizes)) {
			$suffix = '_'.$size;
			$dimensions = ($dimensions) ? $dimensions : self::$image_sizes[$size];
		}
	
		if(strstr($url, "<embed")) {
			$url = String::getUrlFromEmbed($url);
		}
		
		$path = pathinfo($url);
		$ext = $path['extension'];
		
		if (in_array($ext,$CFG->accepted_image_formats)) {
			return "<img src=\"$url\" height=\"{$dimensions['height']}\" width=\"{$dimensions['width']}\" />";
		}
		elseif(in_array($ext,$CFG->accepted_audio_formats)) {
			
		}
		else {
			if(strstr($url, "youtube") || strstr($url,'youtu.be')) {
				if (!$get_still_image) {
					$url = (strstr($url,'youtu.be')) ? str_ireplace('youtu.be','youtube.com/embed',$url) : $url;
					return '<iframe width="'.$dimensions['width'].'" height="'.$dimensions['height'].'" src="'.$url.'" frameborder="0" allowfullscreen></iframe>';
					//return '<object width="'.$dimensions['width'].'" height="'.$dimensions['height'].'"><param name="movie" value="'.$url.'"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="'.$url.'" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="'.$dimensions['width'].'" height="'.$dimensions['height'].'"></embed></object>';
				}
				else {
	      			$url = str_ireplace('http://www.youtube.com/v/','',$url);
	      			$url1 = explode('&',$url);
	      			$key = $url1[0];
	      			
					return Gallery::imageUrl('http://i2.ytimg.com/vi/'.$key.'/default.jpg',$dimensions,$field_name,true);
				}
			}
		}
	}
	
	function flash($row,$size=false,$dimensions=false,$field_name=false,$img_size_persist=false,$table=false) {
		global $CFG;
		
		if (!is_array(self::$image_sizes) || !$img_size_persist)
			self::$image_sizes = DB::getImageSizes($field_name);
			
		if ($size && array_key_exists($size, self::$image_sizes)) {
			$dimensions = ($dimensions) ? $dimensions : self::$image_sizes[$size];
		}
		
		$row['name'] = ($row['name']) ? $row['name'] : $row['f_id'].'_'.$row['id'];
		$dir = (!$row['dir']) ? $CFG->default_upload_location : $row['dir'];
		$url = File::fileExists($row['name'].'.swf',$dir,$table);
		if ($url) {
			$HTML ='			
			<embed width="'.$dimensions['width'].'" height="'.$dimensions['height'].'" align="middle" type="application/x-shockwave-flash" salign="" allowscriptaccess="sameDomain" allowfullscreen="false" menu="true" name="'.$row['name'].'" devicefont="false" wmode="transparent" scale="showall" loop="true" play="true" pluginspage="http://www.adobe.com/go/getflashplayer" quality="high" src="'.$url.'"/>
			<noscript>
				<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="'.$dimensions['width'].'" height="'.$dimensions['height'].'" id="'.$row['name'].'" align="middle">
					<param name="allowScriptAccess" value="sameDomain" />
					<param name="allowFullScreen" value="false" />
					<param name="movie" value="'.$url.'" />
					<param name="quality" value="high" />
					<param name="wmode" value="transparent" />
					<embed src="'.$url.'" quality="high" wmode="transparent" width="'.$dimensions['width'].'" height="'.$dimensions['height'].'" name="'.$row['name'].'" align="middle" allowScriptAccess="sameDomain" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" />
				</object>
			</noscript>';
		}
		else {
			return "<img src=\"{$CFG->placeholder_image}\" title=\"Flash File Not Found\" height=\"{$dimensions['height']}\" width=\"{$dimensions['width']}\" />";	
		}
		
		return $HTML.'<div class="clear"></div>';
	}
}
?>