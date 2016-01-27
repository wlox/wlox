<?php

class ImageResize extends Image {

	var $_high_quality = false;

	var $_center = false;
	
	var $_keep_if_smaller = false;
	
	var $_auto_center = true;
	var $_auto_crop = false;
	
	function setCenter($bool)
	{
		$this->_center = $bool;
	}
	
	function setAutoCenter($bool)
	{
		$this->_auto_center = ($bool) ? true : false;	
	}
	
	function setAutoCrop($bool)
	{
		$this->_auto_crop = ($bool) ? true : false;	
	}
	
	function ImageResize($image,$width=0,$height=0, $die_on_error = true,$keep_if_smaller=false)
	{	
		parent::Image($image, $die_on_error);
		$this->_dest_width  = ($width > 0)  ? $width  : 5;
		$this->_dest_height = ($height > 0) ? $height : 5;
		$this->_keep_if_smaller = $keep_if_smaller;
	}
	
	function setHighQuality($high=true)
	{
		$this->_high_quality = ($high) ? true : false;
	}
	
	function setLowQuality($high=false)
	{
		$this->_high_quality = ($high) ? true : false;
	}
	
	function _build()
	{		
		// scale 
		if ($this->_dest_height == -1 || $this->_dest_width == -1) {
			
			if ($this->_dest_height != -1) {
				$rat = ( $this->_dest_height / $this->_image_height );
			} else {
				$rat = ( $this->_dest_width / $this->_image_width );
			}
			$dest_width = ceil ($rat * $this->_image_width);
			$dest_height = ceil ($rat * $this->_image_height);
			
			$this->_dest_image = imageCreateTruecolor($dest_width,$dest_height);
		
			imageCopyResampled($this->_dest_image,$this->_image,0,0,0,0,$dest_width,$dest_height,$this->_image_width,$this->_image_height);

			return;
			
		}
		
		// add white and center
		
		else {
			      
			$this->_dest_image = imageCreateTruecolor($this->_dest_width,$this->_dest_height);
			$bg_color = ImageColorAllocate($this->_dest_image, 255, 255,255);
			//imageFill($this->_dest_image,0,0,$bg_color);
			//was causing exhausted memory problems - andy
			imagefilledrectangle($this->_dest_image,0,0,$this->_dest_width,$this->_dest_height,$bg_color);
						
			$imagemaxratio = $this->_dest_width / $this->_dest_height;
			$imageratio = $this->_image_width / $this->_image_height;
			
			// basically eliminate the white space by swapping the ratios
			
			if ($this->_auto_crop) {
				$tmp = $imagemaxratio;
				$imagemaxratio = $imageratio;
				$imageratio = $tmp;
				unset($tmp);
			}
			
			
	        if ($this->_keep_if_smaller && $this->_image_width <= $this->_dest_width && $this->_image_height <= $this->_dest_height){
					$dest_width = $this->_image_width;
					$dest_height = $this->_image_height;
			}			 
			else if ($imageratio > $imagemaxratio) {
				$dest_width = $this->_dest_width;
				$dest_height = ceil ($this->_dest_width/$this->_image_width*$this->_image_height);
			}
			else if ($imageratio < $imagemaxratio) {
				$dest_height = $this->_dest_height;
				$dest_width = ceil ($this->_dest_height/$this->_image_height*$this->_image_width);
			}
			else {
				$dest_width = $this->_dest_width;
				$dest_height = $this->_dest_height;
		  	}
		}
		
		

		// center
		
		if ($this->_auto_center) {
			$dest_x = ($this->_dest_width - $dest_width) / 2;
			$dest_y = ($this->_dest_height - $dest_height) / 2;
		}
		else {
			$dest_x = 0;
			$dest_y = 0;
		}
		
		//var_dump($dest_width);
		//var_dump($dest_height);
		
		// resize
		
		if ($this->_high_quality)
			imageCopyResampled($this->_dest_image,$this->_image,$dest_x,$dest_y,0,0,$dest_width,$dest_height,$this->_image_width,$this->_image_height);
		else
			imageCopyResized($this->_dest_image,$this->_image,$dest_x,$dest_y,0,0,$dest_width,$dest_height,$this->_image_width,$this->_image_height);
	}
}

?>