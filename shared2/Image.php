<?php

class Image {

	var $_image;				// source image resource
	var $_image_width;
	var $_image_height;
	var $_dest_image;			// destination image resource
	var $_dest_width;
	var $_dest_height;
	var $_border = 0;			// border width
	var $_border_color;
	var $_watermark;

	function Image($image, $die_on_error = true)
	{	
		
		$this->_die_on_error = $die_on_error;
		
		if (!is_resource($image)) {
			$this->_image2resource($image);
		}
		else {
			//already is a resource, just copy its data
			$this->_image_width = imagesx($image);
			$this->_image_height = imagesy($image);
			$this->_image = $image;
		}

		$this->_dest_width  = $this->_image_width;
		$this->_dest_height = $this->_image_height;
	}
	
	function setBorder($width=1,$color='000000')
	{
		$this->setBorderWidth($width);
		$this->setBorderColor($color);
	}

	function setBorderWidth($width=1)
	{
		$this->_border = ($width > 0) ? $width : 0;
	}

	function setBorderColor($color='000000')
	{
		$this->_border_color = $color;
	}
	
	function setSize($width,$height)
	{
		$this->_dest_width  = ($width > 0)  ? $width  : -1;
		$this->_dest_height = ($height > 0) ? $height : -1;
	}
	
	function setWatermark($watermark)
	{
		$this->_watermark = $watermark;
	}
	
	function save($filename,$type='png',$quality=100)
	{
		$this->_build();
		$this->_build_border();
		
		switch ($type) {
			case 'gif':
				$ret = imageGif($this->_dest_image, $filename);
				break;
			case 'jpg':
			case 'jpeg':
				$ret = imageJpeg($this->_dest_image, $filename, $quality);
				break;
			case 'png':
				$ret = imagePng($this->_dest_image, $filename);
				break;
			default:
				$this->_error('Save: Invalid Format');
				break;
		}

		if (!$ret) {
			$this->_error('Save: Unable to save');
		}
	}
	
	function show($type='png',$quality=100)
	{
		if ($this->_watermark) {
			$color = imagecolorat($this->_watermark,0,0);
			imagecolortransparent($this->_watermark,$color);
			
			$watermark_width = imagesx($this->_watermark);
			$watermark_height = imagesy($this->_watermark);

			$watermark_width2 = $watermark_width;
			$watermark_height2 = $watermark_height;
			
			$board_width = $this->_image_width;
			$board_height = $this->_image_height;

			if ($watermark_width > $board_width) {
				$watermark_width2 = $board_width;
				$watermark_height2 = ceil($board_width/$watermark_width*$watermark_height);
			}
		
			imagecopyresampled($this->_image,$this->_watermark,$board_width-$watermark_width2,$board_height-$watermark_height2,0,0,$watermark_width2,$watermark_height2,$watermark_width,$watermark_height);
		}
		
		$this->_build();
		$this->_build_border();
		
		switch ($type) {
			case 'gif':
				header("Content-type: image/gif");
				imagegif($this->_dest_image);
				break;
			case 'jpg':
			case 'jpeg':
				header("Content-type: image/jpeg");
				imagejpeg($this->_dest_image,'',$quality);
				break;
			case 'png':
				header("Content-type: image/png");
				imagepng($this->_dest_image);
				break;
			default:
				$this->_error('Show: Invalid Format');
				break;
		}
	}
	
	function get()
	{
		$this->_build();
		$this->_build_border();
		
		return $this->_dest_image;
	}
	
	function trim()
	{
		$start_x = $this->_image_width;
		$start_y = $this->_image_height;
		
		$trim_color = ImageColorAt($this->_image,0,0);
		for ($y=0;$y<$this->_image_height;$y++) {
			for ($x=0;$x<$this->_image_width;$x++) {
				$tmp_color = ImageColorAt($this->_image,$x,$y);
				
				if ($trim_color != $tmp_color) {
					$start_x = min($start_x,$x);
					$start_y = min($start_y,$y);
				}
			}
		}
		
		$end_x = 0;
		$end_y = 0;
		
		for ($y=$this->_image_height-1;$y>$start_x;$y--) {
			for ($x=$this->_image_width-1;$x>$start_x;$x--) {
				$tmp_color = ImageColorAt($this->_image,$x,$y);
				
				if ($trim_color != $tmp_color) {
					$end_x = max($end_x,$x);
					$end_y = max($end_y,$y);
				}
			}
		}
		
		$width = $end_x - $start_x;
		$height = $end_y - $start_y;
		
		//$color = imagecolorallocate($this->_image,255,0,0);
		//imagerectangle($this->_image,$start_x,$start_y,$end_x,$end_y,$color);
		
		$tmp_image = imagecreatetruecolor($width,$height);
		imagecopy($tmp_image,$this->_image,0,0,$start_x,$start_y,$width,$height);
		$this->_image = $tmp_image;
		$this->_image_width = $width;
		$this->_image_height = $height;
	}

	function _image2resource($image)
	{
		if ($img_details = getImageSize($image)) {
			$this->_image_width = $img_details[0];
			$this->_image_height = $img_details[1];

			switch ($img_details[2]) {	// image type
				case 1:
					$this->_image = imageCreateFromGif($image);
					break;
				case 2:
					$this->_image = imageCreateFromJpeg($image);
					break;
				case 3:
					$this->_image = imageCreateFromPng($image);
					break;
				default:
					$this->_error('Invalid Image Type');
					break;
			}

			if (!$this->_image) {
				$this->_error('Unable to create image');
			}
		}
		else {
			$this->_error('Unable to open image');
		}
	}

	function _build()
	{
		/* abstract */
	}
	
	function _build_border()
	{
		// add border
		
		if ($this->_border > 0) {
		
			$this->_border_color = imageColorAllocate($this->_dest_image,
				hexdec(substr($this->_border_color,0,2)),
				hexdec(substr($this->_border_color,2,2)),
				hexdec(substr($this->_border_color,4,2)));
		
			for ($x=0;$x<$this->_border;$x++) {
				ImageLine($this->_dest_image, $x, $x, $this->_dest_width, $x, $this->_border_color);
				ImageLine($this->_dest_image, $x, $x, $x, $this->_dest_height, $this->_border_color);
				ImageLine($this->_dest_image, $this->_dest_width-$x-1, $x, $this->_dest_width-$x-1, $this->_dest_height, $this->_border_color);
				ImageLine($this->_dest_image, $x, $this->_dest_height-$x-1, $this->_dest_width, $this->_dest_height-$x-1, $this->_border_color);
			}
		}
	}
	
	function _destroy()
	{
		ImageDestroy($this->_dest_image);
	}

	function _error($str)
	{
		if ($this->_die_on_error) 
			die("Resize: $str");
		else
		  echo ("Resize: $str");
	}
}

?>