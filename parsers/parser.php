<?php
$filename = 'test.csv';
$output_dir = './';
$results = array('result' => array(), 'measurement' => array());
$results = parse($filename, $results);
write_files($results, $output_dir);

function write_files($results, $output_dir){

	// result.csv - header
	$fh_wr = fopen($output_dir.'result.csv', 'w');
	$name_string = 'title';
	$usl_string = 'usl';
	$lsl_string = 'lsl';
	$unit_string = 'unit';
	$optional_string = 'optional';
	$measurement_id_string = 'measurement_id';
	foreach($results['result'] as $name => $result){
		$length = sizeof($results['result'][$name]);
		$name_string .= ','.$results['result'][$name][0]['name'];
		$usl_string .= ','.$results['result'][$name][0]['usl'];
		$lsl_string .= ','.$results['result'][$name][0]['lsl'];
		$unit_string .= ',';
		$optional_string .= ',';
		$measurement_id_string .= ',1';
	}
	fwrite($fh_wr, 'hash,'."\n");
	fwrite($fh_wr, $name_string."\n");
	fwrite($fh_wr, $usl_string."\n");
	fwrite($fh_wr, $lsl_string."\n");
	fwrite($fh_wr, $unit_string."\n");
	fwrite($fh_wr, $optional_string."\n");
	fwrite($fh_wr, $measurement_id_string."\n");
	
	// result.csv - data
	for($i = 0; $i < $length; $i++){
		$string = 'data';
		foreach($results['result'] as $name => $r){
			$string .= ','.$results['result'][$name][$i]['value'];
		}
		fwrite($fh_wr, $string."\n");
	}

	// measurement.csv
	$fh_wm = fopen($output_dir.'measurement.csv', 'w');
	$measurement = $results['measurement'];
	$length = sizeof($measurement) + 2;
	
	$name_string = 'measurement.id,measurement.name';
	$unit_string = 'unit,';
	$value_string = '1,1';
	foreach($measurement as $key => $val){
		$name_string .= ','.$key;
		$unit_string .= ',';
		$value_string .= ','.$val;
	}
	fwrite($fh_wm, $name_string."\n");
	fwrite($fh_wm, $unit_string."\n");
	fwrite($fh_wm, $value_string."\n");
	fclose($fh_wm);
}

function parse($filename, $results){

	$place_separator = '#INIT';
	$file = utf8_encode(file_get_contents($filename));

	$i = 0;

	while(strpos($file, $place_separator) > 0){
		// set $file to start from result
		$file = substr($file, strpos($file, $place_separator));

		// pull out result
		$result = (strpos($file, $place_separator, strlen($place_separator)) > 0)? substr($file, 0, strpos($file, $place_separator, strlen($place_separator))): substr($file, 0); 

		// read data from place
		$results = read_result($result, $results);

		// make sure $file doesn't start with the same place next time
		$file = substr($file, strlen($place_separator));
	}

	return $results;
}

function read_result($result, $result_return){

	// measurement - only look at the first measurement
	if(sizeof($result_return['measurement']) === 0){
		$result = substr($result, strpos($result, '#INIT'));
		$measurement = trim(substr($result, 5, strpos($result, '#TEST')-5));
		$measurements = explode("\n", $measurement);
		foreach($measurements as $key => $value){
			$value = trim($value);
			if(strstr($value, ':')){
				$k = trim(substr($value, 0, strpos($value, ':')));
				$v = trim(substr($value, strpos($value, ':') + 1));
				$result_return['measurement'][$k] = $v;
			} else {
				$result_return['measurement'][$value] = '';
			}
		}
	}

	// result
	$result = substr($result, strpos($result, '#TEST'));
	$results = explode("\n", $result);
	foreach($results as $key => $value){
		if(substr_count($value, ',') === 8){
			$items = explode(',', $value);
			if(!isset($result_return['result'][$items[0]])){
				$result_return['result'][$items[0]] = array();
			}
			array_push($result_return['result'][$items[0]], array('name' => $items[0], 'usl' => $items[3], 'lsl' => $items[2], 'value' => $items[1], 'P/F' => $items[4]));
		}
	}
	
	return $result_return;
}
?>
