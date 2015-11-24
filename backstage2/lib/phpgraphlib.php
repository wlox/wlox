<?php 
///////////////////////////////////////////////////////////
//PHPGraphLib -  PHP Graphing Library v2.21
//Author: Elliott Brueggeman
//PHP v4.04 + compatible
//Please visit www.ebrueggeman.com for usage policy
//and full documentation + examples
///////////////////////////////////////////////////////////
class PHPGraphLib
{
	//---------------USER CHANGEABLE DEFAULTS----------------/
	var $height=300;
	var $width=400;
	var $data_max_allowable=9999999999999999;
	var $data_min_allowable=-9999999999999999;
	var $data_set_count=0;
	//SET TO ACTUAL FONT HEIGHTS AND WIDTHS USED
	var $title_char_width=6;
	var $title_char_height=12;
	var $text_width=6;
	var $text_height=12;
	var $data_value_text_width=6;
	var $data_value_text_height=12;
	//PADDING BETWEEN AXIS AND VALUE DISPLAYED
	var $axis_value_padding=5;
	//SPACE B/T TOP OF BAR OR CENTER OF POINT AND DATA VALUE
	var $data_value_padding=5; 
	//DEFAULT MARGIN % OF WIDTH / HEIGHT
	var $x_axis_default_percent=12; 
	var $y_axis_default_percent=8;
	//DATA POINT DIAMETER IN PX
	var $data_point_width=6;
	//USER CHANGEABLE DEFAULT BOOLEANS (SHOW ELEMENT BY DEFAULT?)
	var $bool_bar_outline=true;
	var $bool_x_axis=true;
	var $bool_y_axis=true;
	var $bool_x_axis_values=true;
	var $bool_y_axis_values=true;
	var $bool_grid=true;
	var $bool_line=false;
	var $bool_data_values=false;
	var $bool_x_axis_values_vert=true;
	var $bool_data_points=false;
	var $bool_title_left=false;
	var $bool_title_right=false;
	var $bool_title_center=true;
	//----------INTERNAL VARIABLES (DO NOT CHANGE)------------/
	var $image;
	var $output_file;
	var $error;
	var $bool_x_axis_setup=false;
	var $bool_y_axis_setup=false;
	var $bool_data=false;
	var $bool_bars_generate=true;
	var $bool_bars=true;
	var $bool_background=false;
	var $bool_title=false;
	var $bool_all_negative=false;
	var $bool_all_positive=false;
	var $bool_gradient=false;
	var $bool_user_data_range=false;
	var $all_zero_data=false;
	var $bool_gradient_colors_found;//INIT AS ARRAY
	//COLOR VARS
	var $background_color;
	var $grid_color;
	var $bar_color;
	var $outline_color;
	var $x_axis_text_color;
	var $y_axis_text_color;
	var $title_color;
	var $x_axis_color;
	var $y_axis_color;
	var $data_point_color;
	var $data_value_color;
	var $line_color;
	var $line_color_default;
	var $goal_line_color;
	//GRADIENT COLORS STORED AS ARRAYS, NOT ALLOCATED COLOR
	var $gradient_color_1;
	var $gradient_color_2;
	var $gradient_color_array;
	var $gradient_max=200;
	var $gradient_handicap;
	//DATA VARS
	var $data_array;
	var $data_count;
	var $data_min;
	var $data_max;
	var $true_displayed_max_value;
	var $true_displayed_min_value;
	var $data_currency;
	var $data_format_array;
	var $data_additional_length=0;
	var $data_format_generic;
	//BAR VARS / SCALE
	var $bar_spaces;
	var $bar_width;
	var $space_width;
	var $unit_scale;
	var $goal_line_array;
	//TEXT / FONT
	var $title_x;
	var $title_y;
	//AXIS POINTS
	var $x_axis_x1;
	var $x_axis_y1;
	var $x_axis_x2;
	var $x_axis_y2;
	var $y_axis_x1;
	var $y_axis_y1;
	var $y_axis_x2;
	var $y_axis_y2;
	var $x_axis_margin; //AKA BOTTOM MARGIN
	var $y_axis_margin; //AKA LEFT MARGIN
	var $data_range_max;
	var $data_range_min;
	var $top_margin=0;
	var $right_margin=0;
	var $range_divisor_factor=25; //CONTROLS AUTO-ADJUSTING GRID INTERVAL
	var $data_point_array;
	//MULTIPLE DATASET VARIABLES
	var $bool_multi_offset=true;
	var $multi_offset_two=24; //PERCENT OF BAR WIDTH
	var $multi_offset_three=15; //PERCENT OF BAR WIDTH
	var $multi_gradient_colors_1;
	var $multi_gradient_colors_2;
	var $multi_bar_colors;
	var $color_darken_factor=30	;//PERCENT DECREASE
	//LEGEND VARIABLES
	var $bool_legend=false;
	var $legend_text_width=6; //IN PX...
	var $legend_max_chars=15; 
	var $legend_total_chars; 
	var $legend_text_height=12;
	var $legend_padding=4; //PADDING INSIDE LEGEND BOX
	var $legend_width;
	var $legend_height;
	var $legend_x;
	var $legend_y;
	var $legend_text_color;
	var $legend_outline_color;
	var $legend_swatch_outline_color;
	var $legend_color;
	var $legend_titles=array();
	//--------------------"PUBLIC" CONSTRUCTOR----------------------//
	function PHPGraphLib($width='', $height='', $output_file=NULL)
	{
		if(!empty($width)&&!empty($height))
		{
			$this->width=$width;
			$this->height=$height;
		}
	
		$this->output_file=$output_file;
		$this->initialize();
		$this->allocateColors(); //SETS DEFAULT COLORS	
	}
	//----------------"PRIVATE" MAIN PROGRAM FUNCTIONS ----------------//
	function initialize()
	{
		//HEADER MUST BE SENT BEFORE ANY HTML OR BLANK SPACE OUTPUT
		if(!$this->output_file){ header("Content-type: image/png"); }
		$this->image = @imagecreate($this->width, $this->height)
			or die("Cannot Initialize new GD image stream - Check your PHP setup");
		$this->data_point_array=array();
		$this->goal_line_array=array();
		$this->multi_bar_colors=array();
		$this->multi_gradient_colors_1=array();
		$this->multi_gradient_colors_2=array();
		//THESE VALUES ARE NOW ARRAYS
		$this->gradient_handicap=array();
		$this->bool_gradient_colors_found=array();
		$this->legend_total_chars=array();
		$this->line_color=array();
		$this->data_format_array=array();
	}
	function createGraph() //MAIN CLASS METHOD - CALLED LAST
	{
		//SETUP AXIS IF NOT ALREADY SETUP BY USER
		if($this->bool_data){
			if(!$this->bool_x_axis_setup){ $this->setupXAxis(); }
			if(!$this->bool_y_axis_setup){ $this->setupYAxis(); }
			//CALCULATIONS
			$this->calcTopMargin();
			$this->calcRightMargin();
			$this->calcCoords();
			$this->setupData();
			//START CREATING ACTUAL IMAGE ELEMENTS
			if($this->bool_background){ $this->generateBackgound(); }
			//ALWAYS GEN GRID VALUES, EVEN IF NOT DISPLAYED
			$this->generateGrid();
			if($this->bool_bars_generate){$this->generateBars(); }
			if($this->bool_data_points){$this->generateDataPoints(); }
			if($this->bool_legend){ $this->generateLegend(); }
			if($this->bool_title) { $this->generateTitle(); }
			if($this->bool_x_axis){ $this->generateXAxis(); }
			if($this->bool_y_axis){ $this->generateYAxis(); }
		}
		else
		{
			$this->error[]="No valid data added to graph. Add data with the addData() function.";
		}
		//DISPLAY ERRORS
		$this->displayErrors();
		//OUTPUT TO BROWSER
		imagepng($this->image, $this->output_file);
		imagedestroy($this->image);
	}
	function setupData()
	{
		$this->bar_spaces=($this->data_count*2)+1;
		$unit_width=($this->width-$this->y_axis_margin-$this->right_margin)/(($this->data_count*2)+($this->data_count));
		if($unit_width<1)
		{	
			//ERROR UNITS TOO SMALL, TOO MANY DATA POINTS OR NOT LARGE ENOUGH GRAPH
			$this->bool_bars_generate=false;
			$this->error[]="Graph too small or too many data points.";
		}
		else
		{
			//DEFAULT SPACE BETWEEN BARS IS 1/2 THE WIDTH OF THE BAR
			//FIND BAR AND SPACE WIDTHS. BAR = 2 UNITS, SPACE = 1 UNIT
			$this->bar_width=2*$unit_width;
			$this->space_width=$unit_width;		
			//NOW CALCULATE HEIGHT (SCALE) UNITS
			$availVertSpace=$this->height-$this->x_axis_margin-$this->top_margin;	
			if($availVertSpace<1)
			{
				$this->bool_bars_generate=false;
				$this->error[]="Graph height not tall enough.";
				//ERROR SCALE UNITS TOO SMALL, X AXIS MARGIN TOO BIG OR GRAPH HEIGHT NOT TALL ENOUGH
			}
			else
			{
				if($this->bool_user_data_range)
				{
					//IF ALL ZERO DATA, SET FAKE MAX AND MIN TO RANGE BOUNDARIES
					if ($this->all_zero_data) {
						if ($this->data_range_min>$this->data_min) { $this->data_min = $this->data_range_min; }
						if ($this->data_range_max<$this->data_max) { $this->data_max = $this->data_range_max; }
					}

					$graphTopScale=$this->data_range_max;
					$graphBottomScale=$this->data_range_min;	
					$graphScaleRange=$graphTopScale-$graphBottomScale;
					$this->unit_scale=$availVertSpace/$graphScaleRange;			
					$this->data_max=$this->data_range_max;
					$this->data_min=$this->data_range_min;

					if($this->data_min<0)
					{
						$this->x_axis_y1-=(int)($this->unit_scale*abs($this->data_min));
						//$this->x_axis_y1=(int)($this->unit_scale*abs($this->data_min));
						$this->x_axis_y2-=(int)($this->unit_scale*abs($this->data_min));
					}

					if($graphScaleRange==0) //ALL DATA IDENTICAL
					{
						$graphScaleRange = 100;
					}
				}
				else
				{
					//START AT Y VALUE 0 OR DATA MIN, WHICHEVER IS LESS
					$graphBottomScale=($this->data_min<0) ? $this->data_min : 0;
					$graphTopScale=($this->data_max<0) ? 0 : $this->data_max;
					$graphScaleRange=$graphTopScale-$graphBottomScale;

					if($graphScaleRange==0) //ALL DATA IDENTICAL
					{
						$graphScaleRange = 100;
					}

					$this->unit_scale=$availVertSpace/$graphScaleRange;			
					//NOW ADJUST X AXIS IN Y VALUE IF NEGATIVE VALUES

					if($this->data_min<0)
					{
						$this->x_axis_y1-=(int)($this->unit_scale*abs($this->data_min));
						$this->x_axis_y2-=(int)($this->unit_scale*abs($this->data_min));
					}
				}
				$this->bool_bars_generate=true;	
			}
		}
	}
	function generateBars()
	{
		$this->finalizeColors();
		$barCount=0;
		$adjustment=0;
		if($this->bool_user_data_range && $this->data_min>=0)
		{
			$adjustment=$this->data_min*$this->unit_scale;
		}
		//REVERSE ARRAY TO ORDER DATA SETS IN ORDER OF PRIORITY
		$this->data_array=array_reverse($this->data_array);
		$dataset_offset=0;
		if($this->bool_multi_offset)
		{
			//SET DIFFERENT OFFSETS BASED ON NUMBER OF DATA SETS
			$dataset_offset=($this->data_set_count>2) ? 
				$this->bar_width*($this->multi_offset_three/100) : $this->bar_width*($this->multi_offset_two/100);
		}
		foreach($this->data_array as $data_set_num => $data_set)
		{
			$lineX2=NULL;
			$xStart=$this->y_axis_x1+$this->space_width/2;
			foreach($data_set as $key => $item)
			{
				$hideBarOutline=false;

				$x1=(int)$xStart+($dataset_offset*$data_set_num);
				$x2=(int)($xStart+$this->bar_width)+($dataset_offset*$data_set_num);

				$y1=(int)($this->x_axis_y1-($item*$this->unit_scale)+$adjustment);
				$y2=(int)$this->x_axis_y1;
				
				//IF WE ARE USING A USER SPECIFIED DATA RANGE, NEED TO LIMIT WHAT'S DISPLAYED
				if ($this->bool_user_data_range) {
					if ($item <= $this->data_range_min) {
						//DON'T DISPLAY, WE ARE OUT OF OUR ALLOWED DISPLAY RANGE!
						$y1=$y2;
						$hideBarOutline=true;
					}
					else if ($item >= $this->data_range_max) {
						//DISPLAY, BUT CUT OFF DISPLAY ABOVE RANGE MAX
						$y1=$this->x_axis_y1-($this->true_displayed_max_value*$this->unit_scale)+$adjustment;	
					}
				}
				
				//DRAW BAR 
				if($this->bool_bars)
				{
					if($this->bool_gradient)
					{
						//DRAW GRADIENT IF DESIRED
						$this->drawGradientBar($x1, $y1, $x2, $y2, $this->multi_gradient_colors_1[$data_set_num], $this->multi_gradient_colors_2[$data_set_num], $data_set_num);
					}
					else
					{
						imagefilledrectangle($this->image, $x1, $y1,$x2, $y2,  $this->multi_bar_colors[$data_set_num]);
					}
					//DRAW BAR OUTLINE	
					if($this->bool_bar_outline && !$hideBarOutline)
					{ 
						imagerectangle($this->image,  $x1, $y2, $x2, $y1, $this->outline_color); 
					}
				}
				// DRAW LINE
				if($this->bool_line)
				{
					$lineX1=$x1+($this->bar_width)/2; //MIDPOINT OF BARS, IF SHOWN
					$lineY1=$y1;
					if(isset($lineX2))
					{
						imagesetthickness($this->image,2);
						imageline($this->image, $lineX2, $lineY2, $lineX1, $lineY1, $this->line_color[$data_set_num]);
						imagesetthickness($this->image,1);
						$lineX2=$lineX1;
						$lineY2=$lineY1;
					}
					else
					{
						$lineX2=$lineX1;
						$lineY2=$lineY1;
					}	
				}


				// DISPLAY DATA POINTS
				if($this->bool_data_points)
				{
					//DONT DRAW DATAPOINTS HERE OR WILL OVERLAP POORLY WITH LINE
					//INSTEAD COLLECT COORDINATES
					$pointX=$x1+($this->bar_width)/2; //MIDPOINT OF BARS, IF SHOWN
					$this->data_point_array[]=array($pointX, $y1);
				}

				// DISPLAY DATA VALUES
				if($this->bool_data_values)
				{
					$dataX=($x1+($this->bar_width)/2)-((strlen($item)*$this->data_value_text_width)/2);

					//VALUE TO BE GRAPHED IS EQUAL/OVER 0
					if($item>=0) 
					{
						$dataY=$y1-$this->data_value_padding-$this->data_value_text_height;
					}
					else
					{
						//CHECK FOR ITEM VALUES BELOW USER SPEC'D RANGE
						if ($this->bool_user_data_range && $item <= $this->data_range_min)
						{
							$dataY=$y1-$this->data_value_padding-$this->data_value_text_height;
						}
						else
						{
							$dataY=$y1+$this->data_value_padding;
						}
					}

					//ADD CURRENCY SIGN, FORMATTING ETC
					if ($this->data_format_array) {
						$item = $this->applyDataFormats($item);
					}
					if ($this->data_currency) {
						$item = $this->applyDataCurrency($item);
					}

					//RECENTER DATA POSITION IF NECESSARY
					$dataX -= ($this->data_additional_length*$this->data_value_text_width)/2;

					imagestring($this->image, 2, $dataX, $dataY, $item,  $this->data_value_color);
				}
				
				//WRITE X AXIS VALUE 
				if($this->bool_x_axis_values)
				{
					if($data_set_num==$this->data_set_count-1)
					{
						if($this->bool_x_axis_values_vert)
						{
							if($this->bool_all_negative)
							{
								//WE MUST PUT VALUES ABOVE 0 LINE
								$textVertPos=(int)($this->y_axis_y2-$this->axis_value_padding);
							}
							else
							{
								//MIX OF BOTH POS AND NEG NUMBERS
								//WRITE VALUE Y AXIS BOTTOM VALUE (WILL BE UNDER BOTTOM OF GRID EVEN IF X AXIS IS FLOATING DUE TO
								$textVertPos=(int)($this->y_axis_y1+strlen($key)*$this->text_width+$this->axis_value_padding);
							}
							$textHorizPos=(int)($xStart+($this->bar_width/2)-($this->text_height/2));
							imagestringup($this->image, 2, $textHorizPos, $textVertPos, $key,  $this->x_axis_text_color);
						}
						else
						{
							if($this->bool_all_negative)
							{
								//WE MUST PUT VALUES ABOVE 0 LINE
								$textVertPos=(int)($this->y_axis_y2-$this->text_height-$this->axis_value_padding);
							}
							else
							{
								//MIX OF BOTH POS AND NEG NUMBERS
								//WRITE VALUE Y AXIS BOTTOM VALUE (WILL BE UNDER BOTTOM OF GRID EVEN IF X AXIS IS FLOATING DUE TO
								$textVertPos=(int)($this->y_axis_y1+($this->text_height*2/3)-$this->axis_value_padding);
							}
							//HORIZONTAL DATA KEYS
							$textHorizPos=(int)($xStart+($this->bar_width/2)-((strlen($key)*$this->text_width)/2));
							imagestring($this->image, 2, $textHorizPos, $textVertPos, $key,  $this->x_axis_text_color);
						}
					}
				}
				$xStart+=$this->bar_width+$this->space_width;
			}
		}
	}
	function finalizeColors()
	{
		if($this->bool_gradient)
		{
			$num_set=count($this->multi_gradient_colors_1);
			//LOOP THROUGH SET COLORS AND ADD BACKING COLORS IF NECESSARY
			if($num_set!=$this->data_set_count)
			{
				$color_darken_decimal=(100-$this->color_darken_factor)/100;
				while($num_set<$this->data_set_count)
				{
					$color_ref_1=$this->multi_gradient_colors_1[$num_set-1];
					$color_ref_2=$this->multi_gradient_colors_2[$num_set-1];
					$this->multi_gradient_colors_1[]=array( 
						(int)$color_ref_1[0]*$color_darken_decimal, 
						(int)$color_ref_1[1]*$color_darken_decimal, 
						(int)$color_ref_1[2]*$color_darken_decimal);
					$this->multi_gradient_colors_2[]=array(
						(int)$color_ref_2[0]*$color_darken_decimal, 
						(int)$color_ref_2[1]*$color_darken_decimal, 
						(int)$color_ref_2[2]*$color_darken_decimal);
					$num_set++;
				}	
			}
			while(count($this->multi_gradient_colors_1)>$this->data_set_count)
			{
				$temp=array_pop($this->multi_gradient_colors_1);
			}
			while(count($this->multi_gradient_colors_2)>$this->data_set_count)
			{
				$temp=array_pop($this->multi_gradient_colors_2);
			}
			$this->multi_gradient_colors_1=array_reverse($this->multi_gradient_colors_1);
			$this->multi_gradient_colors_2=array_reverse($this->multi_gradient_colors_2);
		}
		else if(!$this->bool_gradient)
		{
			$num_set=count($this->multi_bar_colors);
			if($num_set==0)
			{
				$this->multi_bar_colors[0]=$this->bar_color;
				$num_set=1;
			}
			//LOOP THROUGH SET COLORS AND ADD BACKING COLORS IF NECESSARY
			while($num_set<$this->data_set_count)
			{
				$color_ref=$this->multi_bar_colors[$num_set-1];
				$color_parts=imagecolorsforindex($this->image, $color_ref);
				$color_darken_decimal=(100-$this->color_darken_factor)/100;
				$this->multi_bar_colors[$num_set]=imagecolorallocate($this->image, 
					(int)$color_parts['red']*$color_darken_decimal, 
					(int)$color_parts['green']*$color_darken_decimal, 
					(int)$color_parts['blue']*$color_darken_decimal);
				$num_set++;
			}
			while(count($this->multi_bar_colors)>$this->data_set_count)
			{
				$temp=array_pop($this->multi_bar_colors);
			}
			$this->multi_bar_colors=array_reverse($this->multi_bar_colors);
		}
		if($this->bool_line)
		{
			if(!$this->bool_bars)
			{
				$num_set=count($this->line_color);
				if($num_set==0)
				{
					$this->line_color[0]=$this->line_color_default;
					$num_set=1;
				}
				//ONLY DARKEN EACH DATA SET'S LINES WHEN NO BARS PRESENT
				while($num_set<$this->data_set_count)
				{
					$color_ref=$this->line_color[$num_set-1];
					$color_parts=imagecolorsforindex($this->image, $color_ref);
					$color_darken_decimal=(100-$this->color_darken_factor)/100;
					$this->line_color[$num_set]=imagecolorallocate($this->image, 
						(int)$color_parts['red']*$color_darken_decimal, 
						(int)$color_parts['green']*$color_darken_decimal, 
						(int)$color_parts['blue']*$color_darken_decimal);
					$num_set++;
				}
			}
			else
			{
				$num_set=count($this->line_color);
				while($num_set<$this->data_set_count)
				{
					$this->line_color[$num_set]=$this->line_color_default;
					$num_set++;
				}
			}
			while(count($this->line_color)>$this->data_set_count)
			{
				$temp=array_pop($this->line_color);
			}
			$this->line_color=array_reverse($this->line_color);
		}	
	}
	function drawGradientBar($x1, $y1, $x2, $y2, $colorArr1, $colorArr2, $data_set_num)
	{
		if(!isset($this->bool_gradient_colors_found[$data_set_num])||$this->bool_gradient_colors_found[$data_set_num]==false)
		{
			$this->gradient_handicap[$data_set_num]=0;
			$numLines=abs($x1-$x2)+1;
			while($numLines*$this->data_set_count>$this->gradient_max)
			{
				//WE HAVE MORE LINES THAN ALLOWABLE COLORS
				//USE HANDICAP TO RECORD THIS
				$numLines/=2;
				$this->gradient_handicap[$data_set_num]++;
			}
			$color1R=$colorArr1[0];
			$color1G=$colorArr1[1];
			$color1B=$colorArr1[2];
			$color2R=$colorArr2[0];
			$color2G=$colorArr2[1];
			$color2B=$colorArr2[2];
			$rScale=($color1R-$color2R)/$numLines;
			$gScale=($color1G-$color2G)/$numLines;
			$bScale=($color1B-$color2B)/$numLines;
			$this->allocateGradientColors($color1R, $color1G, $color1B, $rScale, $gScale, $bScale, $numLines, $data_set_num);
		}
		$numLines=abs($x1-$x2)+1;
		if($this->gradient_handicap[$data_set_num]>0)
		{
			//IF HANDICAP IS USED, IT WILL ALLOW US TO MOVE THROUGH THE ARRAY MORE SLOWLY, DEPENDING ON THE SET VALUE
			$interval=$this->gradient_handicap[$data_set_num];
			for($i=0;$i<$numLines;$i++)
			{
				$adjusted_index=ceil($i/pow(2,$interval))-1;
				if($adjusted_index<0)
				{
					$adjusted_index=0;
				}
				imageline($this->image, $x1+$i, $y1, $x1+$i, $y2, $this->gradient_color_array[$data_set_num][$adjusted_index]);		
			}
		}
		else
		{
			//NORMAL GRADIENTS WITH COLORS < $this->gradient_max
			for($i=0;$i<$numLines;$i++)
			{
				imageline($this->image, $x1+$i, $y1, $x1+$i, $y2, $this->gradient_color_array[$data_set_num][$i]);		
			}
		}
	}
	function generateGrid()
	{
		//DETERMINE HORIZONTAL GRID LINES
		$horizGridArray=array();
		if($this->bool_user_data_range)
		{
			$min=$this->data_min;
		}
		else
		{
			$min=0;
		}
		$horizGridArray[]=$min;
		//USE OUR FUNCTION TO DETERMINE IDEAL Y AXIS SCALE INTERVAL
		$intervalFromZero=$this->determineAxisMarkerScale($this->data_max, $this->data_min);

		//IF WE HAVE POSITIVE VALUES, ADD GRID VALUES TO ARRAY 
		//UNTIL WE REACH THE MAX NEEDED (WE WILL GO 1 OVER)
		$cur=$min;
		while($cur<$this->data_max)
		{
			$cur+=$intervalFromZero;
			$horizGridArray[]=$cur;
		}
		//IF WE HAVE NEGATIVE VALUES, ADD GRID VALUES TO ARRAY 
		//UNTIL WE REACH THE MIN NEEDED (WE WILL GO 1 OVER)
		$cur=$min;
		while($cur>$this->data_min)
		{
			$cur-=$intervalFromZero;
			$horizGridArray[]=$cur;
		}
		//SORT NEEDED B/C WE WILL USE LAST VALUE LATER (MAX)
		sort($horizGridArray);

		$this->true_displayed_max_value = $horizGridArray[count($horizGridArray)-1];
		$this->true_displayed_min_value = $horizGridArray[0];

		//DETERMINE VERTICAL GRID LINES
		$vertGridArray=array();
		$vertGrids=$this->data_count+1;
		$interval=$this->bar_width+$this->space_width;
		//ASSEMBLE VERT GRIDLINE ARRAY
		for($i=1;$i<$vertGrids;$i++)
		{
			$vertGridArray[]=$this->y_axis_x1+($interval*$i);
		}
		//LOOP THROUGH EACH HORIZONTAL LINE
		if($this->bool_user_data_range && $this->data_min>=0)
		{
			$adjustment=$this->data_min*$this->unit_scale;
		}
		else
		{
			$adjustment=0;
		}

		foreach($horizGridArray as $value)
		{
			$yValue=(int)($this->x_axis_y1-($value*$this->unit_scale)+$adjustment);
			if($this->bool_grid)
			{
				imageline($this->image, $this->y_axis_x1, $yValue, $this->x_axis_x2 , $yValue, $this->grid_color);
			}
			//DISPLAY VALUE ON Y AXIS IF DESIRED USING CALC'D GRID VALUES
			if($this->bool_y_axis_values)
			{
				$adjustedYValue=$yValue-($this->text_height/2);
				$adjustedXValue=$this->y_axis_x1-((strlen($value)+$this->data_additional_length)*$this->text_width)-$this->axis_value_padding;

				//ADD CURRENCY SIGN, FORMATTING ETC
				if ($this->data_format_array) {
					$value = $this->applyDataFormats($value);
				}
				if ($this->data_currency) {
					$value = $this->applyDataCurrency($value);
				}

				imagestring($this->image, 2, $adjustedXValue, $adjustedYValue, $value, $this->y_axis_text_color);
			}
		}
		if(!$this->bool_all_positive&&!$this->bool_user_data_range)
		{
			//RESET WITH BETTER VALUE BASED ON GRID MIN VALUE CALCULATIONS, NOT DATA MIN
			$this->y_axis_y1=$this->x_axis_y1-($horizGridArray[0]*$this->unit_scale);
		}
		//RESET WITH BETTER VALUE BASED ON GRID VALUE CALCULATIONS, NOT DATA MIN
		$this->y_axis_y2=$yValue;
		//LOOP THROUGH EACH VERTICAL LINE
		if($this->bool_grid)
		{
			foreach($vertGridArray as $value)
			{
				$xValue=$this->y_axis_y1;
				imageline($this->image, $value, $this->y_axis_y2, $value, $xValue , $this->grid_color);
			}
		}
		//DRAW GOAL LINES IF PRESENT (AFTER GRID) - DOESN'T GET EXECUTED IF ARRAY EMPTY
		foreach($this->goal_line_array as $yLocation)
		{
			$yLocation=(int)($this->x_axis_y1-($yLocation*$this->unit_scale)+$adjustment);
			imageline($this->image, $this->y_axis_x1, $yLocation, $this->x_axis_x2 , $yLocation, $this->goal_line_color);
		}
	}
	function generateDataPoints()
	{
		foreach($this->data_point_array as $pointArray)
		{
			imagefilledellipse($this->image, $pointArray[0], $pointArray[1], $this->data_point_width, $this->data_point_width, $this->data_point_color);
		}		
	}
	function generateXAxis()
	{
		imageline($this->image, $this->x_axis_x1, $this->x_axis_y1, $this->x_axis_x2, $this->x_axis_y2, $this->x_axis_color);
	}
	function generateYAxis()
	{
		imageline($this->image, $this->y_axis_x1, $this->y_axis_y1, $this->y_axis_x2, $this->y_axis_y2, $this->y_axis_color);
	}
	function generateBackgound()
	{
		imagefilledrectangle($this->image, 0, 0, $this->width, $this->height, $this->background_color);
	}
	function generateTitle()
	{
		//SPACING MAY HAVE CHANGED SINCE EARLIER
		//USE TOP MARGIN OR GRID TOP Y, WHICHEVER LESS
		$highestElement=($this->top_margin<$this->y_axis_y2) ? $this->top_margin : $this->y_axis_y2;
		$textVertPos=($highestElement/2)-($this->title_char_height/2); //CENTERED
		$titleLength=strlen($this->title_text);
		if($this->bool_title_center)
		{
			$this->title_x=($this->width/2)-(($titleLength*$this->title_char_width)/2);
			$this->title_y=$textVertPos;
		}
		else if($this->bool_title_left)
		{
			$this->title_x=$this->y_axis_x1;
			$this->title_y=$textVertPos;
		}
		else if($this->bool_title_right)
		{
			$this->title_x=$this->x_axis_x2-($titleLength*$this->title_char_width);
			$this->title_y=$textVertPos;
		}
		imagestring($this->image, 2, $this->title_x , $this->title_y , $this->title_text,  $this->title_color);
	}
	function calcTopMargin()
	{
		if($this->bool_title)
		{
			//INCLUDE SPACE FOR TITLE, APPROX MARGIN + 3*TITLE HEIGHT
			$this->top_margin=($this->height*($this->x_axis_default_percent/100))+$this->title_char_height;
		}
		else
		{
			//JUST USE DEFAULT SPACING
			$this->top_margin=$this->height*($this->x_axis_default_percent/100);
		}	
	}
	function calcRightMargin()
	{
		//JUST USE DEFAULT SPACING
		$this->right_margin=$this->width*($this->y_axis_default_percent/100);
	}
	function calcCoords()
	{
		//CALCULATE AXIS POINTS, ALSO USED FOR OTHER CALCULATIONS
		$this->x_axis_x1=$this->y_axis_margin;
		$this->x_axis_y1=$this->height-$this->x_axis_margin;
		$this->x_axis_x2=$this->width-$this->right_margin;
		$this->x_axis_y2=$this->height-$this->x_axis_margin;

		$this->y_axis_x1=$this->y_axis_margin;
		$this->y_axis_y1=$this->height-$this->x_axis_margin;
		$this->y_axis_x2=$this->y_axis_margin;
		$this->y_axis_y2=$this->top_margin;
	}
	function determineAxisMarkerScale($max, $min)
	{
		//FOR CALCLATION, TAKE RANGE OR MAX-0
		if($this->bool_user_data_range)
		{
			$range=abs($max-$min);
		}
		else
		{
			$range=(abs($max-$min)>abs($max-0)) ? abs($max-$min) : abs($max-0);
		}

		//HANDLE ALL ZERO DATA
		if ($range == 0) 
		{
			$range = 10;
		}

		//MULTIPLY UP TO OVER 100, TO BETTER FIGURE INTERVAL
		$count=0;
		while(abs($range)<100)
		{
			$range*=10;
			$count++;
		}
		//DIVIDE INTO INTERVALS BASED ON HEIGHT / PRESET CONSTANT - AFTER ROUNDING WILL BE APPROX
		$divisor=round($this->height/$this->range_divisor_factor);
		$divided=round($range/$divisor);
		$result=$this->roundUpOneExtraDigit($divided);
		//IF ROUNDED UP W/ EXTRA DIGIT IS MORE THAN 200% OF DIVIDED VALUE,
		//ROUND UP TO NEXT SIG NUMBER WITH SAME NUM OF DIGITS
		if($result/$divided>=2)
		{
			$result=$this->roundUpSameDigits($divided);
		}
		//DIVIDE BACK DOWN, IF NEEDED
		for($i=0;$i<$count;$i++)
		{
			$result/=10;
		}
		return $result;	
	}
	function roundUpSameDigits($num)
	{           
		$len=strlen($num);  
		if(round($num, -1*($len-1))==$num) 
		{
			//WE ALREADY HAVE A SIG NUMBER
			return $num;
		}
		else
		{
			$firstDig=substr($num, 0,1);
			$secondDig=substr($num, 1,1);
			$rest=substr($num, 2);
			$secondDig=5;
			$altered=$firstDig.$secondDig.$rest;
			//AFTER REASSEMBLY, ROUND UP TO NEXT SIG NUMBER, SAME # OF DIGITS
			return round((int)$altered, -1*($len-1));
		}
	}
	function roundUpOneExtraDigit($num)
	{                     
		$len=strlen($num);  
		$firstDig=substr($num, 0,1);
		$rest=substr($num, 1);
		$firstDig=5;
		$altered=$firstDig.$rest;
		//AFTER REASSEMBLY, ROUND UP TO NEXT SIG NUMBER, ONE EXTRA # OF DIGITS
		return round((int)$altered, -1*($len)); 
	}
	function displayErrors()
	{
		if(count($this->error)>0)
		{
			$lineHeight=12;
			$errorColor = imagecolorallocate($this->image, 0, 0, 0);
			$errorBackColor = imagecolorallocate($this->image, 255, 204, 0);
			imagefilledrectangle($this->image, 0, 0, $this->width-1, 2*$lineHeight,  $errorBackColor);
			imagestring($this->image, 3, 2, 0, "!!----- PHPGraphLib Error -----!!",  $errorColor);
			foreach($this->error as $key => $errorText)
			{
				
				imagefilledrectangle($this->image, 0, ($key*$lineHeight)+$lineHeight, $this->width-1, ($key*$lineHeight)+2*$lineHeight,  $errorBackColor);	
				imagestring($this->image, 2, 2, ($key*$lineHeight)+$lineHeight, "[". ($key+1) . "] ". $errorText,  $errorColor);	
			}
			$errorOutlineColor = imagecolorallocate($this->image, 255, 0, 0);
			imagerectangle($this->image, 0, 0, $this->width-1,($key*$lineHeight)+2*$lineHeight,  $errorOutlineColor);		
		}
	}
	function addData($data)
	{
		$data = (!is_array($data)) ? array($data) : $data;
		if (is_array($data)) {
			foreach ($data as $set) {
				$this->data_array[] = $set;
			}
		}
		
		//ASSESS DATA
		$min=$this->data_max_allowable;
		$max=$this->data_min_allowable;
		$this->data_count=0;
		//GET RID OF BAD DATA, FIND MAX, MIN
		foreach($this->data_array as $data_set_num => $data_set)
		{
			foreach($data_set as $key => $item)
			{
				if(!is_numeric($item))
				{
					unset($this->data_array[$data_set_num][$key]);
				}
				else
				{
					if($item<$min){ $min=$item; }
					if($item>$max){ $max=$item; }
				}
			}
			$count=count($this->data_array[$data_set_num]);
			$count>$this->data_count ? $this->data_count=$count : NULL; 
		}
		$set_count=count($this->data_array);
		$this->data_set_count=$set_count;
		if($set_count==0)
		{
			$this->error[]="No valid datasets added in adddata() function.";
		}
		else
		{
			$this->bool_data=true;
			//MIN AND MAX FOR ALL DATASETS
			$this->data_min=$min;
			$this->data_max=$max;
			if($this->data_min>=0)
			{
				$this->bool_all_positive=true;
			}
			else if($this->data_max<=0)
			{
				$this->bool_all_negative=true;
			}

			//SETUP FAKE MAX AND MIN FOR ALL ZERO DATA
			if($this->data_min>=0 && $this->data_max ==0 ) 
			{
				$this->data_min=0;
				$this->data_max=10;
				$this->all_zero_data=true;
			} 
		}
	}
	function setupXAxis($percent='', $color='')
	{
		if($percent===false)
		{
			$this->bool_x_axis=false;
		}
		else
		{
			$this->bool_x_axis=true;
		}
		$this->bool_x_axis_setup=true;
		if(!empty($color)&&$arr=$this->returnColorArray($color))
		{
			$this->x_axis_color = imagecolorallocate($this->image, $arr[0], $arr[1], $arr[2]);
		}
		if(is_numeric($percent)&&$percent>0)
		{ 
			$percent=$percent/100;
			$this->x_axis_margin=(int)($this->height*$percent);
		}
		else
		{
			$percent=$this->x_axis_default_percent/100;
			$this->x_axis_margin=(int)($this->height*$percent);
		}	
	}
	function setupYAxis($percent='', $color='')
	{
		if($percent===false)
		{
			$this->bool_y_axis=false;
		}
		else
		{
			$this->bool_y_axis=true;
		}
		$this->bool_y_axis_setup=true;
		if(!empty($color)&&$arr=$this->returnColorArray($color))
		{
			$this->y_axis_color = imagecolorallocate($this->image, $arr[0], $arr[1], $arr[2]);
		}
		if(is_numeric($percent)&&$percent>0)
		{ 
			$this->y_axis_margin=(int)($this->width*($percent/100));
		}
		else
		{
			$percent=$this->y_axis_default_percent/100;
			$this->y_axis_margin=(int)($this->width*$percent);
		}
	}
	function setRange($max, $min)
	{
		$this->data_range_max=$max;
		$this->data_range_min=$min;
		$this->bool_user_data_range=true;
	}
	function setTitle($title)
	{
		if(!empty($title))
		{
			$this->title_text=$title;
			$this->bool_title=true;
		}
		else{ $this->error[]="String arg for setTitle() not specified properly."; }	
	}
	function setTitleLocation($location)
	{
		$this->bool_title_left=false;
		$this->bool_title_right=false;
		$this->bool_title_center=false;
		switch(strtolower($location))
		{
			case 'left': $this->bool_title_left=true; break;
			case 'right': $this->bool_title_right=true; break;
			case 'center': $this->bool_title_center=true; break;
			default: $this->error[]="String arg for setTitleLocation() not specified properly.";
		}	
	}
	function setBars($bool)
	{
		if(is_bool($bool)){ $this->bool_bars=$bool;}
		else{ $this->error[]="Boolean arg for setBars() not specified properly."; }
	}
	function setGrid($bool)
	{
		if(is_bool($bool)){ $this->bool_grid=$bool;}
		else{ $this->error[]="Boolean arg for setGrid() not specified properly."; }
	}
	function setXValues($bool)
	{
		if(is_bool($bool)){ $this->bool_x_axis_values=$bool;}
		else{ $this->error[]="Boolean arg for setXValues() not specified properly."; }
	}
	function setYValues($bool)
	{
		if(is_bool($bool)){ $this->bool_y_axis_values=$bool;}
		else{ $this->error[]="Boolean arg for setYValues() not specified properly."; }
	}
	function setXValuesHorizontal($bool)
	{
		if(is_bool($bool)){ 
			($bool) ? $this->bool_x_axis_values_vert=false : $this->bool_x_axis_values_vert=true;
		}
		else{ $this->error[]="Boolean arg for setXValuesHorizontal() not specified properly."; }
	}
	function setXValuesVertical($bool)
	{
		if(is_bool($bool)){ $this->bool_x_axis_values_vert=$bool;}
		else{ $this->error[]="Boolean arg for setXValuesVertical() not specified properly."; }
	}
	function setBarOutline($bool)
	{
		if(is_bool($bool)){ $this->bool_bar_outline=$bool;}
		else{ $this->error[]="Boolean arg for setBarOutline() not specified properly."; }
	}
	function setDataPoints($bool)
	{
		if(is_bool($bool)){ $this->bool_data_points=$bool;}
		else{ $this->error[]="Boolean arg for setDataPoints() not specified properly."; }
	}
	function setDataPointSize($size)
	{
		if(is_numeric($size)){ $this->data_point_width=$size;}
		else{ $this->error[]="Data point size in setDataPointSize() not specified properly."; }
	}
	function setDataValues($bool)
	{
		if(is_bool($bool)){ $this->bool_data_values=$bool;}
		else{ $this->error[]="Boolean arg for setDataValues() not specified properly."; }
	}
	function setDataCurrency($currency_type = 'dollar') 
	{
		switch (strtolower($currency_type)) 
		{
			case 'dollar': $this->data_currency = '$'; break;
			case 'yen': $this->data_currency = '�'; break;
			case 'pound': $this->data_currency = '�'; break;
			case 'lira': $this->data_currency = '�'; break;
			/* Euro doesn't display properly...
			case 'euro': $this->data_currency = '�'; break; */
			
			/* Franc doesn't display properly
			case 'franc': $this->data_currency = '?'; break; */
			default: $this->data_currency = $currency_type; break;
		}
		$this->data_additional_length += strlen($this->data_currency);
	}
	function applyDataCurrency($input) {
		return $this->data_currency . $input;
	}
	function setDataFormat($format) 
	{
		//SETUP STRUCTURE FOR FUTURE ADDITIONAL DATA FORMATS - SPECIFY CALLBACK FUNCTIONS
		switch ($format) 
		{
			case 'comma': 
				$this->data_format_array[] = 'formatDataAsComma';
				$this->data_additional_length += floor(strlen($this->data_max)/3);
				break;
			case 'percent': 
				$this->data_format_array[] = 'formatDataAsPercent'; 
				$this->data_additional_length++; 
				break;
			case 'degrees': 
				$this->data_format_array[] = 'formatDataAsDegrees'; 
				$this->data_additional_length++; 
				break;
			default: 
				$this->data_format_array[] = 'formatDataAsGeneric'; 
				$this->data_format_generic = $format; 
				$this->data_additional_length += strlen($format);
				break;
		}
	}
	function applyDataFormats($input)
	{
		//COMMA FORMATTING MUST BE DONE FIRST
		if ($pos = array_search('formatDataAsComma', $this->data_format_array)) {
			unset($this->data_format_array[$pos]);
			array_unshift($this->data_format_array, 'formatDataAsComma');
		}

		//LOOP THROUGH EACH FORMATTING FUNCTION
		foreach ($this->data_format_array as $format_type_callback)
		{
			eval('$input=$this->' . $format_type_callback . '($input);');
		}
		return $input;
	}
	function formatDataAsComma($input) {

		//CHECK FOR NEGATIVE SIGN
		$sign_part = '';
		if (substr($input, 0, 1) == '-') {
			$input = substr($input, 1);
			$sign_part = '-';
		}

		//HANDLE DECIMALS
		$decimal_part = '';
		if (($pos = strpos($input, '.')) !== false) {
			$decimal_part = substr($input, $pos);
			$input = substr($input, 0, $pos);
		}
		
		//TURN DATA INTO FORMAT 12,234...
		$parts = '';
		while (strlen($input)>3) {
			$parts = ',' . substr($input, -3) . $parts;	
			$input = substr($input, 0, strlen($input)-3);
		}
		$input = $sign_part . $currency_part . $input . $parts . $decimal_part;
		return $input;
	}
	function formatDataAsPercent($input) {
		return $input . '%';
	}
	function formatDataAsDegrees($input) {
		return $input . '�';
	}
	function formatDataAsGeneric($input) {
		return $input . $this->data_format_generic;
	}
	function setLine($bool)
	{
		if(is_bool($bool)){ $this->bool_line=$bool;}
		else{ $this->error[]="Boolean arg for setLine() not specified properly."; }
	}
	function setGoalLine($yValue)
	{
		if(is_numeric($yValue))
		{
			$this->goal_line_array[]=$yValue;
		}
		else
		{
			$this->error[]="Goal line Y axis value not specified properly.";
		}
	}
	//-------------"PRIVATE" COLOR HANDLING FUNCTIONS---------------//
	function allocateColors()
	{
		$this->background_color = imagecolorallocate($this->image, 255, 255, 255);
		$this->grid_color = imagecolorallocate($this->image, 220, 220, 220);
		$this->bar_color = imagecolorallocate($this->image, 200, 200, 200);
		$this->line_color_default = imagecolorallocate($this->image, 100, 100, 100);
		$this->x_axis_text_color = $this->line_color_default;
		$this->y_axis_text_color = $this->line_color_default;
		$this->data_value_color = $this->line_color_default;
		$this->title_color = imagecolorallocate($this->image, 0, 0, 0);
		$this->outline_color = $this->title_color;
		$this->data_point_color = $this->title_color;
		$this->x_axis_color = $this->title_color;
		$this->y_axis_color = $this->title_color;
		$this->goal_line_color = $this->title_color;
		//New Legend Colors
		$this->legend_outline_color=$this->grid_color;
		$this->legend_color=$this->background_color;
		$this->legend_text_color=$this->line_color_default;
		$this->legend_swatch_outline_color=$this->line_color_default;
	}
	function returnColorArray($color)
	{
		//CHECK TO SEE IF NUMERIC COLOR PASSED THROUGH IN FORM '128,128,128'
		if(strpos($color,',')!==false)
		{
			return explode(',',$color);
		}
		switch(strtolower($color))
		{
			//NAMED COLORS BASED ON W3C's RECOMMENDED HTML COLORS
			case 'black': return array(0,0,0); break;
			case 'silver': return array(192,192,192); break;
			case 'gray': return array(128,128,128); break;
			case 'white': return array(255,255,255); break;
			case 'maroon': return array(128,0,0); break;
			case 'red': return array(255,0,0); break;
			case 'purple': return array(128,0,128); break;
			case 'fuscia': return array(255,0,255); break;
			case 'green': return array(0,128,0); break;
			case 'lime': return array(0,255,0); break;
			case 'olive': return array(128,128,0); break;
			case 'yellow': return array(255,255,0); break;
			case 'navy': return array(0,0,128); break;	
			case 'blue': return array(0,0,255); break;
			case 'teal': return array(0,128,128); break;
			case 'aqua': return array(0,255,255); break;	
		}
		$this->error[]="Color name \"$color\" not recogized.";
		return false;
	}
	function allocateGradientColors($color1R, $color1G, $color1B, $rScale, $gScale, $bScale, $num, $data_set_num)
	{
		//CALUCLATE THE COLORS USED IN OUR GRADIENT AND STORE THEM IN ARRAY
		$this->gradient_color_array[$data_set_num]=array();
		for($i=0;$i<=$num+1;$i++)
		{
			$this->gradient_color_array[$data_set_num][$i] = imagecolorallocate($this->image, $color1R-($rScale*$i), $color1G-($gScale*$i), $color1B-($bScale*$i));
		}
		$this->bool_gradient_colors_found[$data_set_num]=true;
	}
	function setGenericColor($inputColor, $var, $errorMsg)
	{
		//CAN BE USED FOR MOST COLOR SETTING OPTIONS
		if(!empty($inputColor)&&$arr=$this->returnColorArray($inputColor))
		{
			eval($var . ' = imagecolorallocate($this->image, $arr[0], $arr[1], $arr[2]);');
			return true;	
		}
		else
		{
			$this->error[]=$errorMsg;
			return false;
		}
	}
	//-------------------"PUBLIC" COLOR FUNCTIONS----------------------//
	function setBackgroundColor($color)
	{
		if($this->setGenericColor($color, '$this->background_color', "Background color not specified properly."))
		{
			$this->bool_background=true;
		}
	}
	function setTitleColor($color)
	{
		$this->setGenericColor($color, '$this->title_color', "Title color not specified properly.");
	}
	function setTextColor($color)
	{
		$this->setGenericColor($color, '$this->x_axis_text_color', "X axis text color not specified properly.");
		$this->setGenericColor($color, '$this->y_axis_text_color', "Y axis Text color not specified properly.");
	}
	function setXAxisTextColor($color)
	{
		$this->setGenericColor($color, '$this->x_axis_text_color', "X axis text color not specified properly.");
	}
	function setYAxisTextColor($color)
	{
		$this->setGenericColor($color, '$this->y_axis_text_color', "Y axis Text color not specified properly.");
	}
	function setBarColor($color,$color2='',$color3='')
	{
		$this->setGenericColor($color, '$this->multi_bar_colors[]', "Bar color not specified properly.");
		if(!empty($color2)){
			$this->setGenericColor($color2, '$this->multi_bar_colors[]', "Bar color 2 not specified properly.");
		}
		if(!empty($color3)){
			$this->setGenericColor($color3, '$this->multi_bar_colors[]', "Bar color 3 not specified properly.");
		}	
	}
	function setGridColor($color)
	{
		$this->setGenericColor($color, '$this->grid_color', "Grid color not specified properly.");
	}
	function setBarOutlineColor($color)
	{
		$this->setGenericColor($color, '$this->outline_color', "Bar outline color not specified properly.");
	}
	function setDataPointColor($color)
	{
		$this->setGenericColor($color, '$this->data_point_color', "Data point color not specified properly.");
	}
	function setDataValueColor($color)
	{
		$this->setGenericColor($color, '$this->data_value_color', "Data value color not specified properly.");	
	}
	function setLineColor()
	{
		$colors[] = 'blue';
		$colors[] = 'red';
		$colors[] = 'lime';
		$colors[] = 'yellow';
		$colors[] = 'fuscia';
		$colors[] = 'aqua';
		$colors[] = 'grey';
		$colors[] = 'navy';
		$colors[] = 'maroon';
		$colors[] = 'green';
		$colors[] = 'olive';
		$colors[] = 'purple';
		$colors[] = 'teal';
		$colors[] = 'silver';
		$colors[] = 'grey';
		$colors[] = 'black';
		$c = count($this->data_array);
		
		for ($i=0;$i<$c;$i++) {
			$color = $colors[$i];
			if(!empty($color))
			{
				$this->setGenericColor($color, '$this->line_color[]', "Line color not specified properly.");
			}
		}
	}
	function setGoalLineColor($color)
	{
		$this->setGenericColor($color, '$this->goal_line_color', "Goal line color not specified properly.");
	}
	function setGradient()
	{
		$colors[] = array('blue','navy');
		$colors[] = array('red','maroon');
		$colors[] = array('lime','green');
		$colors[] = array('yellow','olive');
		$colors[] = array('fuscia','purple');
		$colors[] = array('aqua','teal');
		$colors[] = array('white','silver');
		$colors[] = array('grey','black');
		$colors[] = array('navy','black');
		$colors[] = array('maroon','black');
		$colors[] = array('green','black');
		$colors[] = array('olive','green');
		$colors[] = array('purple','black');
		$colors[] = array('teal','black');
		$colors[] = array('blue','white');
		$colors[] = array('red','white');
		$colors[] = array('yellow','white');
		$c = count($this->data_array);
		
		for ($i=0;$i<$c;$i++) {
			$color = $colors[$i];
			if(!empty($color)&&($arr1=$this->returnColorArray($color[0]))&&($arr2=$this->returnColorArray($color[1])))
			{
				$this->bool_gradient=true;
				$this->multi_gradient_colors_1[]=$arr1;
				$this->multi_gradient_colors_2[]=$arr2;
			}
		}
	}
	//Legend Related Functions
	function setLegend($bool)
	{
		if(is_bool($bool)){ $this->bool_legend=$bool;}
		else{ $this->error[]="Boolean arg for setLegend() not specified properly."; }
	}
	function setLegendColor($color)
	{
		$this->setGenericColor($color, '$this->legend_color', "Legend color not specified properly.");
	}
	function setLegendTextColor($color)
	{
		$this->setGenericColor($color, '$this->legend_text_color', "Legend text color not specified properly.");
	}
	function setLegendOutlineColor($color)
	{
		$this->setGenericColor($color, '$this->legend_outline_color', "Legend outline color not specified properly.");
	}
	function setSwatchOutlineColor($color)
	{
		$this->setGenericColor($color, '$this->legend_swatch_outline_color', "Swatch outline color not specified properly.");
	}
	function setLegendTitle($titles)
	{
		$titles = (!is_array($titles)) ? array($title) : $titles;
		if (is_array($titles)) {
			foreach($titles as $title) {
				$len=strlen($title);
				if($len>$this->legend_max_chars){ 
					$title=substr($title, 0, $this->legend_max_chars); 
					$this->legend_total_chars[]=$this->legend_max_chars;
				}
				else{$this->legend_total_chars[]=$len;}
				$this->legend_titles[]=$title; 
			}
		}
	}
	function generateLegend()
	{
		$swatchToTextOffset=($this->legend_text_height-6)/2;
		$swatchSize=$this->legend_text_height-2*$swatchToTextOffset;
		//CALC HEIGHT / WIDTH BASED ON # OF DATA SETS
		$this->legend_height=$this->legend_text_height+(2*$this->legend_padding);
		$totalChars=0;
		for($i=0;$i<$this->data_set_count;$i++)
		{
			//COULD HAVE MORE TITLES THAN DATA SETS - CHECK FOR THIS
			if(isset($this->legend_total_chars[$i])){ $totalChars+=$this->legend_total_chars[$i]; }
		}
		$this->legend_width=$totalChars*$this->legend_text_width+($this->legend_padding*1.5)+
			($this->data_set_count*($swatchSize+($this->legend_padding*2)));
		$this->legend_x=$this->x_axis_x2-$this->legend_width;
		$highestElement=($this->top_margin<$this->y_axis_y2) ? $this->top_margin : $this->y_axis_y2;
		$this->legend_y=($highestElement/2)-($this->legend_height/2); //CENTERED
				
		//BACKGROUND
		imagefilledrectangle($this->image, $this->legend_x, $this->legend_y, $this->legend_x+$this->legend_width, 
			$this->legend_y+$this->legend_height, $this->legend_color);
		//BORDER
		imagerectangle($this->image, $this->legend_x, $this->legend_y, $this->legend_x+$this->legend_width, 
			$this->legend_y+$this->legend_height, $this->legend_outline_color);
		
		$length_covered=0;
		for($i=0;$i<$this->data_set_count;$i++)
		{
			$data_label='';
			if(isset($this->legend_titles[$i]))
			{
				$data_label=$this->legend_titles[$i];
			}
			$yValue=$this->legend_y+$this->legend_padding;
			$xValue=$this->legend_x+$this->legend_padding+($length_covered*$this->legend_text_width)+($i*4*$this->legend_padding);
			$length_covered+=strlen($data_label);
			//DRAW COLOR BOXES
			if($this->bool_bars)
			{
				if($this->bool_gradient){
					$color=$this->gradient_color_array[$this->data_set_count-$i-1][0];
				}
				else{
					$color=$this->multi_bar_colors[$this->data_set_count-$i-1];
				}
			}
			else if($this->bool_line&&!$this->bool_bars)
			{
				$color=$this->line_color[$this->data_set_count-$i-1];
			}
			
			imagefilledrectangle($this->image, $xValue, $yValue+$swatchToTextOffset, $xValue+$swatchSize, $yValue+$swatchToTextOffset+$swatchSize, $color);
			imagerectangle($this->image, $xValue, $yValue+$swatchToTextOffset, $xValue+$swatchSize, $yValue+$swatchToTextOffset+$swatchSize, $this->legend_swatch_outline_color);	
			imagestring($this->image, 2, $xValue+(2*$this->legend_padding+2), $yValue, $data_label, $this->legend_text_color);
		}
	}
}
?>