<?php
// this class doesn't need config.php
class Dbase {
	function get($dbfname,$primary_key=false) {
		if (!file_exists($dbfname))
			return false;
		
	    $fdbf = fopen($dbfname,'r');
	    $buf = fread($fdbf,32);
	    $header = unpack( "VRecordCount/vFirstRecord/vRecordLength", substr($buf,4,8));
	    $fields = Array();
		$goon = true;
	    $unpackString='';
	    
	    while ($goon && !feof($fdbf)) {
	        $buf = fread($fdbf,32);
	        if (substr($buf,0,1)==chr(13)) {$goon=false;}
	        else {
	            $field=unpack( "a11fieldname/A1fieldtype/Voffset/Cfieldlen/Cfielddec", substr($buf,0,18));
	            $unpackString.="A$field[fieldlen]$field[fieldname]/";
	            array_push($fields, $field);
	        }
	    }
	    
	    fseek($fdbf, $header['FirstRecord']+1);
	    for ($i=1; $i<=$header['RecordCount']; $i++) {
	        $buf = fread($fdbf,$header['RecordLength']);
	        $record = unpack($unpackString,$buf);
	       	$key = ($primary_key) ? $record[$primary_key] : $i;
	       	$record['id'] = $i;
	        $records[$key] = $record;
	    }
	    fclose($fdbf); 
	    return $records;
	}
} 

?>