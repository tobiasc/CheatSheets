<?php
function write_files($results, $output_dir){

	// result.csv - header
	$fh_wr = fopen($output_dir.'result.csv', 'w');
	$name_string = 'title';
	$usl_string = 'usl';
	$lsl_string = 'lsl';
	$unit_string = 'unit';
	$optional_string = 'optional';
	$measurement_id_string = 'measurement_id';
	foreach($results[0]['result'] as $i => $result){
		$name_string .= ','.$results[0]['result'][$i]['name'];
		$usl_string .= ','.$results[0]['result'][$i]['usl'];
		$lsl_string .= ','.$results[0]['result'][$i]['lsl'];
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
	foreach($results as $i => $r){
		$string = 'data';
		foreach($results[$i]['result'] as $key => $result){
			$string .= ','.$results[$i]['result'][$key]['value'];
		}
		fwrite($fh_wr, $string."\n");
	}

	// measurement.csv
	$fh_wm = fopen($output_dir.'measurement.csv', 'w');
	$measurement = $results[0]['measurement'];
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

function parse($filename){
	$results = array();

	$place_separator = '#INIT';
	$file = utf8_encode(file_get_contents($filename));

	$i = 0;

	while(strpos($file, $place_separator) > 0){
		// set $file to start from result
		$file = substr($file, strpos($file, $place_separator));

		// pull out result
		$result = (strpos($file, $place_separator, strlen($place_separator)) > 0)? substr($file, 0, strpos($file, $place_separator, strlen($place_separator))): substr($file, 0); 

		// read data from place
		array_push($results, read_result($result));

		// make sure $file doesn't start with the same place next time
		$file = substr($file, strlen($place_separator));
	}

	return $results;
}

function read_result($result){
	$result_return = array('measurement' => array(), 'result' => array());

	// measurement
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

	// result
	$result = substr($result, strpos($result, '#TEST'));
	$results = explode("\n", $result);
	foreach($results as $key => $value){
		if(substr_count($value, ',') === 8){
			$items = explode(',', $value);
			$result_return['result'][$items[0]] = array('name' => $items[0], 'usl' => $items[3], 'lsl' => $items[2], 'value' => $items[1], 'P/F' => $items[4]);
		}
	}
	
	return $result_return;
}

if(isset($_FILES['input_file']['name'])){
	$id = sha1(microtime());

	$output_dir = "/tmp/parser/";
	if(!is_dir($output_dir)){
		mkdir($output_dir);
	}
	$output_dir = "/tmp/parser/".$id.'/';
	if(!is_dir($output_dir)){
		mkdir($output_dir);
	}
	move_uploaded_file($_FILES['input_file']['tmp_name'], $output_dir.'resultdata.csv');

	$zip = new ZipArchive();
	$res = $zip->open($output_dir.'output.zip', ZipArchive::CREATE);
	
	$results = parse($output_dir.'resultdata.csv');
	write_files($results, $output_dir);

	$zip->addFile($output_dir.'measurement.csv', 'measurement.csv');
	$zip->addFile($output_dir.'result.csv', 'result.csv');
	$zip->close();
	
	// write the result out as a file
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	header('Content-Disposition: attachment; filename='.$_FILES['input_file']['name'].'_new.zip;');
	$lines = file($output_dir.'output.zip');
	foreach($lines as $line){
		echo($line);
	}

	// clean up (delete all files)
	if ($dh = opendir($output_dir)) {
		while (($file = readdir($dh)) !== false) {
			if($file != '.' && $file != '..'){
				unlink($output_dir.$file);
			}
		}
	}
	unlink($output_dir.'output.zip');

} else {
	// some simple HTML to make a file upload form
	echo '<html><head><title>Parse Broadcom Data</title></head><body>
		<h2>Parse Broadcom Data</h2>
		<form enctype="multipart/form-data" action="parse_broadcom_data.php" method="POST">
		<input name="input_file" type="file">
		<input type="submit" value="Upload"></form>
		<br><br><br><a href="/parse_broadcom_data.php">Parse Broadcom data</a>
		</body></html>';
}

?>
