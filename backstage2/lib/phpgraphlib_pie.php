<?php
///////////////////////////////////////////////////////////
//PHPGraphLib -  PHP Graphing Library v2.21 - Pie Chart Extension
//Author: Elliott Brueggeman
//PHP v4.04 + compatible
//Please visit www.ebrueggeman.com for usage policy
//and documentation + examples
///////////////////////////////////////////////////////////
class PHPGraphLibPie extends PHPGraphLib
{
	//USER CHANGEABLE DEFAULTS
	var $pie_precision=0; //NUMBER OF SIGNIFICANT DIGITS IN LABEL %
	var $bool_legend=true;
	var $bool_data_labels=true;
	var $pie_3D_height_percent=4; //IS % OF TOTAL WIDTH 
	var $pie_legend_text_width=6; //IN PX...
	var $pie_legend_text_height=12;
	var $pie_label_text_width=6;
	var $pie_label_text_height=12;
	var $pie_legend_padding=5; //PADDING INSIDE LEGEND BOX
	//DEFAULT COLORS, IN ORDER OF DISPLAY ON GRAPH - YOU CAN CHANGE ORDER IF NEEDED
	var $pie_avail_colors=array('pastel_orange_1','pastel_orange_2','pastel_blue_1','pastel_green_1','clay',
		'pastel_blue_2','pastel_yellow','silver','pastel_green_2','brown','gray','pastel_purple', 'olive', 
		'aqua','yellow','teal','lime');
	//INTERNAL DEFAULTS - CHANGE NOT RECOMMENDED
	var $pie_center_y_offset=50; //in %
	var $pie_center_x_offset=50; //in %
	var $pie_center_legend_scale=55; //OFFSET IN % OF EXISTING COORDS WHEN LEGEND
	var $pie_width_percent=75; //DEFAULT WIDTH % OF TOTAL WIDTH
	var $pie_height_percent=28; 
	var $pie_label_scale=90; //IN % SCALE WIDTH/HEIGHT IF DATA LABELS
	var $pie_legend_scale=64;//IN % SCALE WIDTH/HEIGHT IF LEGEND
	//INTERNALS - DO NOT CHANGE
	var $pie_width;
	var $pie_height;
	var $pie_center_x;
	var $pie_center_y;
	var $pie_legend_width;
	var $pie_legend_height;
	var $pie_legend_x;
	var $pie_legend_y;
	var $pie_data_max_length=0;
	var $pie_color_pointer=0;
	var $pie_data_array_percents;
	var $pie_data_label_space;
	var $pie_3D_height;
	function PHPGraphLibPie($width='', $height='')
	{
		PHPGraphLib::PHPGraphLib($width,$height);	
	}
	function initialize()
	{
		PHPGraphLib::initialize();
		$this->pie_data_array_percents=array();
	}
	function calcCoords()
	{
		//CALC COORDS OF PIE CENTER AND WIDTH/HEIGHT
		$this->pie_width=$this->width*($this->pie_width_percent/100);
		$this->pie_height=$this->width*($this->pie_height_percent/100);
		$this->pie_center_y=$this->height*($this->pie_center_y_offset/100);
		$this->pie_center_x=$this->width*($this->pie_center_x_offset/100);
		//SET DATA LABEL SPACING 
		if($this->bool_data_labels)
		{
			//SET TO NUMBER OF PIXELS THAT ARE EQUAL TO TEXT WIDTH
			//7 IS A BASE SPACER THAT ALL LABELS GET
			$this->pie_data_label_space=7+$this->width/30;
			$this->pie_width*=$this->pie_label_scale/100;
			$this->pie_height*=$this->pie_label_scale/100;
		}
		if($this->bool_legend)
		{
			//COMPENSATE FOR LEGEND WITH LESSER PRESET PERCENT
			$this->pie_width*=$this->pie_legend_scale/100;
			$this->pie_height*=$this->pie_legend_scale/100;
			$this->pie_center_x*=$this->pie_center_legend_scale/100;
		}
		$this->pie_3D_height=$this->pie_3D_height_percent*($this->pie_width/100);	
	}
	function setupData()
	{
		//IN THE PIE EXTENSION, THIS WILL CALCULATE THE TOTAL SUM AND THE CORRESPONDING PERCENTAGES
		if($this->data_set_count==1)
		{
			$sum=array_sum($this->data_array[0]);
			if($sum>0)
			{
				foreach($this->data_array[0] as $dataText => $dataValue)
				{
					$this->pie_data_array_percents[]=$dataValue/$sum;
					//FIND DATA TEXT LENGTH
					$len=strlen($dataText);
					if($len>$this->pie_data_max_length){ $this->pie_data_max_length=$len; }
				}
				$this->bool_bars_generate=true;
			}
			else
			{
				$this->bool_bars_generate=false;
				$this->error[]="Sum of data must be greater than 0.";
			}
		}
		else
		{
			$this->error[]="Multiple datasets not allowed with pie charts";
		}
	}
	function generateGrid(){}
	function generateLegend()
	{
		$maxChars=NULL;
		//CALC HEIGHT / WIDTH BASED ON # OF VALUES
		$this->pie_legend_height=($this->pie_legend_text_height*$this->data_count)+(2*$this->pie_legend_padding);	
		$this->pie_legend_width=($this->pie_data_max_length*$this->pie_legend_text_width)+(6*$this->pie_legend_padding);
		//ALLOTTED SPACE DOES NOT INCLUDE PADDING AROUND LEGEND (SMALLER)
		$allottedSpace=$this->width-$this->pie_center_x-($this->pie_width/2)-(2*$this->pie_legend_padding);
		if($this->bool_data_labels)
		{
			//ALSO COMPENSATE FOR DISPLAYED TEXT DATA % ON GRAPH
			$allottedSpace-=((4+$this->pie_precision)*$this->pie_label_text_width)+$this->pie_data_label_space;
		}
		//CHECK TO MAKE SURE WE ARE NOT > ALLOTTED SPACE
		if($this->pie_legend_width>$allottedSpace)
		{
			//IF WE ARE, ADJUST WIDTH AND MAX LENGTH FOR DATA VALUES
			//4 = Padding | Swatch(Padding Width) | Padding | ...text... |Padding
			$swatchAndPaddingWidth=4*$this->pie_legend_padding;
			//MAX CHARS = ALOTTED SPACE - ENOUGH ROOM FOR SWATCHES / TEXT WIDTH
			$maxChars=floor(($allottedSpace-$swatchAndPaddingWidth)/$this->pie_legend_text_width);
			$this->pie_legend_width=($maxChars*$this->pie_legend_text_width)+$swatchAndPaddingWidth;
		}
		else
		{
			//WE DIDNT GO OVER ALLOTTED SPACE, SO WE SHOULD ADJUST THE CENTER OF THE PIE CHART NOW
			$equalSpacing=($this->width-($this->pie_width+$this->pie_legend_width))/3;
			//SO NOW REPOSITION CENTER AT SPACING + 1/2 PIE WIDTH
			$this->pie_center_x=($this->pie_width/2)+$equalSpacing;
		}
		//AUTO ADJUSTING FORMULA FOR POSITION OF pie_legend_x BASED ON PIE CHART SIZE
		$a=($this->pie_center_x+$this->pie_width/2);
		$b=$this->width-$a;
		$c=($b-$this->pie_legend_width)/2;
		//SET PIE X & Y ARGS
		$this->pie_legend_x=$a+$c;
		$this->pie_legend_y=($this->height-$this->pie_legend_height)/2;		
		//BACKGROUND
		imagefilledrectangle($this->image, $this->pie_legend_x, $this->pie_legend_y, $this->pie_legend_x+$this->pie_legend_width, 
			$this->pie_legend_y+$this->pie_legend_height, $this->legend_color);
		//BORDER
		imagerectangle($this->image, $this->pie_legend_x, $this->pie_legend_y, $this->pie_legend_x+$this->pie_legend_width, 
			$this->pie_legend_y+$this->pie_legend_height, $this->legend_outline_color);
		$xValue=$this->pie_legend_x+($this->pie_legend_padding);
		$count=0;
		$this->resetColorPointer();
		$swatchToTextOffset=($this->pie_legend_text_height-6)/2;
		$swatchSize=$this->pie_legend_text_height-2*$swatchToTextOffset;
		foreach($this->data_array[0] as $dataText => $dataValue)
		{
			$yValue=$this->pie_legend_y+$this->pie_legend_text_height*$count+$this->pie_legend_padding;
			//DRAW COLOR BOXES
			$color=$this->generateNextColor();
			imagefilledrectangle($this->image, $xValue, $yValue+$swatchToTextOffset, $xValue+$swatchSize, $yValue+$swatchToTextOffset+$swatchSize, $color);
			imagerectangle($this->image, $xValue, $yValue+$swatchToTextOffset, $xValue+$swatchSize, $yValue+$swatchToTextOffset+$swatchSize, $this->legend_swatch_outline_color);	
			//IF LONGER THAN OUR MAX, TRIM TEXT
			if($maxChars){ $dataText=substr($dataText,0, $maxChars); }
			imagestring($this->image, 2, $xValue+(2*$this->pie_legend_padding), $yValue, $dataText, $this->legend_text_color);
			$count++;
		}
	}
	function generateBars()
	{
		$this->resetColorPointer();
		//LOOP THROUGH AND CREATE SHADAING
		for($i=$this->pie_center_y+$this->pie_3D_height; $i>$this->pie_center_y; $i--) 
		{
			$arcStart=0;
			foreach($this->pie_data_array_percents as $key => $value)
			{
				$color=$this->generateNextColor(true); //GENERATE A DARKER VERSION OF THE INDEXED COLOR
				imagefilledarc($this->image, $this->pie_center_x, $i, $this->pie_width, $this->pie_height, $arcStart, 360*$value+$arcStart, $color, IMG_ARC_PIE);
				$arcStart+=360*$value;
			}
			$this->resetColorPointer();
		}
		$arcStart=0;	
		foreach($this->pie_data_array_percents as $key => $value)
		{
			$color=$this->generateNextColor();
			imagefilledarc($this->image, $this->pie_center_x, $this->pie_center_y, $this->pie_width, $this->pie_height, $arcStart, 360*$value+$arcStart, $color, IMG_ARC_PIE);
			if($this->bool_data_labels){ $this->generateDataLabel($value, $arcStart); }
			$arcStart+=360*$value;
		}
	}
	function generateDataLabel($value, $arcStart)
	{
		//MIDWAY IF THE MID ARC ANGLE OF THE WEDGE WE JUST DREW
		$midway=($arcStart+360*$value+$arcStart)/2;
		//ADJUST FOR ELLIPSE HEIGHT/WIDTH RATIO
		$skew=$this->pie_height_percent/$this->pie_width_percent;
		$pi = atan(1.0)*4.0;
		$theta=($midway/180)*$pi;
		$valueX=$this->pie_center_x+($this->pie_width/2+$this->pie_data_label_space)*cos($theta);
		$valueY=$this->pie_center_y+($this->pie_width/2+$this->pie_data_label_space)*sin($theta)*$skew;
		$displayValue=$this->formatPercent($value);
		$valueArray=$this->dataLabelHandicap($valueX, $valueY, $displayValue, $midway);
		$valueX=$valueArray[0];
		$valueY=$valueArray[1];	
		imagestring($this->image, 2, $valueX, $valueY, $displayValue, $this->label_text_color);
	}
	function formatPercent($input)
	{	
		return number_format($input*100, $this->pie_precision).'%';
	}
	function dataLabelHandicap($x, $y, $value, $midway)
	{
		//MOVES DATA LABEL X/Y BASED ON QUADRANT AND LENGTH OF DISPLAYED DATA
		//AND HOW TEXT IS DISPLAYED (UPPER LEFT CORNER X/Y)
		//EXTRA 1 FOR % SIGN
		$lengthOffset=(strlen($value)*($this->pie_label_text_width))/2;
		$vertOffset=$this->pie_label_text_height/2;
		if($midway<=30)
		{
			$newX=$x-1.5*$lengthOffset;
			$newY=$y-$vertOffset;
		}
		else if($midway>30&&$midway<=135)
		{
			$newX=$x-$lengthOffset;
			$newY=$y-$vertOffset+$this->pie_3D_height;
		}
		else if($midway>135&&$midway<=165)
		{
			$newX=$x-$lengthOffset;
			$newY=$y-$vertOffset;
		}
		else if($midway>165&&$midway<=200)
		{
			//VALUE AT RISK FOR BEING OUT OF BOUNDS ON SMALLER GRAPHS
			$newX=$x-1/3*$lengthOffset;
			$newY=$y-$vertOffset;
		}
		else if($midway>200&&$midway<=330)
		{
			$newX=$x-$lengthOffset;
			$newY=$y-$vertOffset;
		}
		else if($midway>330)
		{
			//VALUE AT RISK FOR OVERLAPPING THE LEGEND ON SMALLER GRAPHS
			$newX=$x-1.5*$lengthOffset;
			$newY=$y-$vertOffset;
		}
		else
		{
			$newX=$x-$lengthOffset;
			$newY=$y-$vertOffset;
		}
		return array($newX,$newY);
	}
	function generateNextColor($dark=false)
	{
		$array=$this->returnColorArray($this->pie_avail_colors[$this->pie_color_pointer]);
		if($dark)
		{
			//WE ARE TRYING TO GENERATE A DARKER VERSION OF THE EXISTING COLOR
			$array[0]*=.8;
			$array[1]*=.8;
			$array[2]*=.8;
		}
		$color=imagecolorallocate($this->image, $array[0], $array[1], $array[2]);
		$this->pie_color_pointer++;
		if($this->pie_color_pointer>=count($this->pie_avail_colors))
		{
			$this->pie_color_pointer=0;
		}
		return $color;
	}
	function resetColorPointer()
	{
		$this->pie_color_pointer=0;
	}
	function returnColorArray($color)
	{
		//THIS FUNCTION FIRST CHECKS EXISITNG COLORS IN PHPGraphLib
		//THEN IF NOT FOUND CHECKS ITS OWN LIST
		//COMES WITH VARIOUS PRESET LIGHTER PIE CHART FRIENDLY COLORS
		if($resultColor=PHPGraphLib::returnColorArray($color))
		{
			return $resultColor;
		}
		else
		{
			//REMOVE LAST ERROR GENERATED (PHPGraphLib::returnColorArray) SETS ONLY ONE ERROR IF FALSE)
			array_pop($this->error);
			//CHECK TO SEE IF NUMERIC COLOR PASSED THROUGH IN FORM '128,128,128'
			if(strpos($color,',')!==false)
			{
				return explode(',',$color);
			}
			switch(strtolower($color))
			{
				//NAMED COLORS BASED ON W3C's RECOMMENDED HTML COLORS
				case 'pastel_orange_1': return array(238,197,145); break;
				case 'pastel_orange_2': return array(238,180,34); break;
				case 'pastel_blue_1': return array(122,197,205); break;
				case 'pastel_green_1': return array(102,205,0); break;
				case 'pastel_blue_2': return array(125,167,217); break;
				case 'pastel_green_2': return array(196,223,155); break;
				case 'clay': return array(246,142,85); break;
				case 'pastel_yellow': return array(255,247,153); break;
				case 'pastel_purple': return array(135,129,189); break;
				case 'brown': return array(166,124,81); break;	
			}
			$this->error[]="Color name \"$color\" not recogized.";
			return false;
		}
	}
	function generateTitle()
	{
		//DRAWS TITLE B/T TOP OF GRAPH AND EDGE OF CANVAS
		$pieTop=$this->pie_center_y-$this->pie_height/2;
		if($this->bool_legend)
		{
			$topElement=($pieTop<$this->pie_legend_y) ? $pieTop : $this->pie_legend_y;
		}
		else
		{
			$topElement=$pieTop;
		}
		if($topElement<0)
		{
			$this->error[]="Not enough room for a title. Increase graph height, or eliminate data values.";
		}
		else
		{
			$title_y=$topElement/2-$this->title_char_height/2;
			$title_x=$this->width/2-(strlen($this->title_text)*$this->title_char_width)/2;
			imagestring($this->image, 2, $title_x , $title_y , $this->title_text,  $this->title_color);
		}
	}
	//"PUBLIC" CUSTOMIZATION FUNCTIONS
	function setLabelTextColor($color)
	{
		$this->setGenericColor($color, '$this->label_text_color', "Label text color not specified properly.");
	}
	function setPrecision($digits)
	{
		if(is_int($digits)){ $this->pie_precision=$digits;}
		else{ $this->error[]="Integer arg for setPrecision() not specified properly."; }
	}
	function setDataLabels($bool)
	{
		if(is_bool($bool)){ $this->bool_data_labels=$bool;}
		else{ $this->error[]="Boolean arg for setDataLabels() not specified properly."; }
	}
	//UNUSED PHPGRAPHLIB FUNCTIONS OVERWRITTEN
	function generateXAxis(){}
	function generateYAxis(){}
	function setupXAxis(){}
	function setupYAxis(){}
	function calcTopMargin(){}
	function calcRightMargin(){}
	function generateDataPoints(){}
}
?>