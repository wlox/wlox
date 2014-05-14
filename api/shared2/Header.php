<?php

class Header {
	public $HTML,$js_globals;
	
	function google_verify($key=false){
		global $CFG;
		$this->HTML[] = '<meta name="verify-v1" content="'.(($key) ? $key : $CFG->default_google_key).'" />';
		$this->HTML[] = '<meta name="google-site-verification" content="'.(($key) ? $key : $CFG->default_google_key).'" />';
		
	}
	
	function title($title=false) {
		global $CFG;
		$this->HTML[] = '<title>'.(($title) ? $title : $CFG->default_title).'</title>';
	}
		
	
	function metaDesc($desc=false) {
		global $CFG;
		$this->HTML[] = '<meta name="description" content="'.(($desc) ? $desc : $CFG->default_meta_desc).'" />';
		$this->HTML[] = '<meta http-equiv="description" content="'.(($desc) ? $desc : $CFG->default_meta_desc).'" />';
		$this->HTML[] = '<meta http-equiv="DC.description" content="'.(($desc) ? $desc : $CFG->default_meta_desc).'" />';
	}
	
	function metaKeywords($keywords=false) {
		global $CFG;
		$this->HTML[] = '<meta name="keywords" content="'.(($keywords) ? $keywords : $CFG->default_meta_keywords).'">';
	}
	
	function metaKeywords2($keywords2=false) {
		global $CFG;
		$this->HTML[] = '<meta http-equiv="keywords" content="'.(($keywords2) ? $keywords2 : $CFG->default_meta_keywords2).'" />';
	}
	
	function metaRobot($roboact=false) {
		
		//index,follow - noindex,nofollow - index,nofollow - noindex,follow
		global $CFG;
		$this->HTML[] = '<meta name="robot" content="'.(($roboact) ? $roboact : $CFG->default_meta_roboact).'"/>';
	}
	
	function metaPragma($pragma=false) {		
		//no-cache 
		global $CFG;
		$this->HTML[] = '<meta http-equiv="pragma" content="'.(($pragma) ? $pragma : $CFG->default_meta_pragma).'"/>';
	}
	
	function metaControlCache($cache=false){
		global $CFG;
		$this->HTML[] = '<meta http-equiv="Cache-Control" content="'.(($cache) ? $cache : $CFG->default_meta_cache).'"/>';	
	}
	
	function metaTitles($title1=false,$title2=false,$title3=false){
		global $CFG;
		$this->HTML[] = '<meta http-equiv="title" content="'.(($title1) ? $title : $CFG->default_meta_title1).'"/>';
		$this->HTML[] = '<meta name="title" content="'.(($title2) ? $title2 : $CFG->default_meta_title2).'"/>';
		$this->HTML[] = '<meta name="DC.Title" content="'.(($title3) ? $title3 : $CFG->default_meta_title3).'"/>';	
		
	}
	
	function metaAuthor($author=false) {
		global $CFG;
		$this->HTML[] = '<meta name="author" content="'.(($author) ? $author : $CFG->default_meta_author).'">';
	}
		
	function cssFile($filename,$media='screen',$browser=false) {
		global $CFG;
		$this->HTML[] = (($browser) ? "<!--[if $browser]>" : '') .'<style type="text/css">/*\*/@import url("'.$filename.'");/**/</style>'.(($browser) ? '<![endif]-->' : '');		
	}
	
	function jsFile($filename,$browser=false) {
		$this->HTML[] = (($browser) ? "<!--[if $browser]>" : '') .'<script type="text/javascript" src="'.$filename.'"></script>'.(($browser) ? '<![endif]-->' : '');
	}
	
	function js($script) {
		$this->HTML[] = '<script type="text/javascript">'
					.$script.
				  '</script>';
	}
	
	function jsGlobalVar($name,$value) {
		$this->js_globals[$name] = $value;
	}
	
	function getJsGlobals() {
		if (is_array($this->js_globals)) {
			echo '
			<script type="text/javascript">
				$.cfg = {
			';
				foreach ($this->js_globals as $name => $value) {
					$vars[] = $name.':"'.$value.'"';	
				}
				echo implode(',',$vars);
				echo '
				}
			</script>';
		}
	}
	
	function icon($url) {
		$this->HTML[] = '<link rel="shortcut icon" href="'.$url.'">';
		//echo '<link rel="shortcut icon" href="'.$url.'">';
	}
	
	function custom($string) {
		$this->HTML[] = $string;
	}
	
	function display($body_class=false) { 
		global $CFG;
		
		if ($CFG->language) {
			if ($CFG->language == 'eng')
				$lang = 'en';
			elseif ($CFG->language == 'esp')
				$lang = 'es';
			elseif ($CFG->language == 'heb')
				$lang = 'he';
			else
				$lang = $CFG->language;
				
			$language = 'lang="'.$lang.'" xml:lang="'.$lang.'"';
		}
		
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>		
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" <?= $language ?> />
		<?
		foreach ($this->HTML as $elem) {
			echo $elem;		
		}
		$class = ($body_class) ? 'class="'.$body_class.'"' : false;
		?>
		</head>
		<body id="body" <?= $class ?>>
		<?
	}
	
}
?>