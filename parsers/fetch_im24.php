<?php
// determines how a website should be fetched
function fetch_im24(){
	$min = 1;	
	$max = 420;
	for($i = $min; $i <= $max; $i++){
		echo 'Fetching page: '.$i."\n";
		$tmp = '';
		if($i > 1){
			$tmp = '/P-'.$i;
		}
		insert_url_im24('http://www.immobilienscout24.de/Suche/S-T'.$tmp.'/Wohnung-Miete/Berlin/Berlin');
		sleep(3);
	}

	// insert_url_im24('im24.txt'); // cached version
	// http://www.immobilienscout24.de/Suche/S-T/Wohnung-Miete/Berlin/Berlin // search result - page 1
	// http://www.immobilienscout24.de/Suche/S-T/P-3/Wohnung-Miete/Berlin/Berlin // search result - page 3
}

// fetch a specific URL & inserts it into the DB
function insert_url_im24($filename){
	$place_separator = '<li class="is24-res-entry';
	$file = utf8_encode(file_get_contents($filename));

	while(strpos($file, $place_separator) > 0){
		// set $file to start from next place
		$file = substr($file, strpos($file, $place_separator));

		// pull out place
		$place = (strpos($file, $place_separator, strlen($place_separator)) > 0)? substr($file, 0, strpos($file, $place_separator, strlen($place_separator))): substr($file, 0); 

		// read data from place
		$data = read_place_im24($place);

		// insert place
		$key = array('vendor_id' => $data['vendor_id'], 'vendor_name' => $data['vendor_name']);
		$collection = get_db_collection('places');
		$collection->update($key, $data, array('upsert' => true));

		echo "Inserted place: ".$data['title']."\n";

		// make sure $file doesn't start with the same place next time
		$file = substr($file, strlen($place_separator));
	}
}

// decodes a place string
function read_place_im24($place){
	$data = array();

	// set time inserted
	$data['time_inserted'] = date('Y-m-d H:i:s', time());

	// set vendor info
	$data['vendor_name'] = 'ImmobilienScout24.de';
	$data['vendor_link'] = 'http://www.immobilienscout24.de';

	// get vendor id
	$place = substr($place, strpos($place, 'data-realEstateId="') + strlen('data-realEstateId="'));
	$data['vendor_id'] = strip_tags(substr($place, 0, strpos($place, '">')));

	// get url	
	$data['url'] = 'http://www.immobilienscout24.de/expose/'.$data['vendor_id'];

	// get title
	$place = substr($place, strpos($place, '<a href="/expose/'));
	$data['title'] = trim(strip_tags(substr($place, 0, strpos($place, '</a>') + strlen('</a>'))));

	// get img
	$place = substr($place, strpos($place, 'http://picture.immobilienscout24.de'));
	$data['img'] = strip_tags(substr($place, 0, strpos($place, '?')));

	// get price
	$place = substr($place, strpos($place, '<dt class="'));
	$type = strip_tags(substr($place, 0, strpos($place, '</dt>') + strlen('</dt>')));
	$place = substr($place, strpos($place, '<dd class="'));
	$data['price'] = ($type === 'Kaltmiete: ')?(int)str_replace('.','',strip_tags(substr($place, 0, strpos($place, '</dd>') + strlen('</dd>')))):'';

	// get size
	$place = substr($place, strpos($place, '<dt class="'));
	$type = strip_tags(substr($place, 0, strpos($place, '</dt>') + strlen('</dt>')));
	$place = substr($place, strpos($place, '<dd class="'));
	$data['size'] = ($type === ' Wohnfl&auml;che: ')?(int)strip_tags(substr($place, 0, strpos($place, '</dd>') + strlen('</dd>'))):'';

	// get rooms
	$place = substr($place, strpos($place, '<dt class="'));
	$type = strip_tags(substr($place, 0, strpos($place, '</dt>') + strlen('</dt>')));
	$place = substr($place, strpos($place, '<dd class="'));
	$data['rooms'] = ($type === 'Zimmer: ')?(int)strip_tags(substr($place, 0, strpos($place, '</dd>') + strlen('</dd>'))):'';

	// get tags
	$data['tags'] = array();
	$place = substr($place, strpos($place, '<ul class="is24-checklist">'));
	$tags = substr($place, 0, strpos($place, '</ul>'));
	while(strpos($tags, '<li') > 0){
		$tags = substr($tags, strpos($tags, '<li'));
		array_push($data['tags'], str_replace('*', '', strip_tags(substr($tags, 0, strpos($tags, '</li>') + strlen('</li>')))));
		$tags = substr($tags, strlen('<li'));
	}

	// get address
	$place = substr($place, strpos($place, '<p class="is24-address">'));
	$address = substr($place, 0, strpos($place, '</p>'));
	$data['address'] = trim(strip_tags(substr($address, strrpos($address, '</span>'))));

	// insert address accuracy
	$data['area'] = determine_address_accuracy_im24($data['address']);

	// get lat/lng
	$location = getLatLng($data['address']);
	$data['lat'] = $location['lat'];
	$data['lng'] = $location['lng'];

	return $data;
}

// determines an inaccurate address
//
// Test data:
// Mitte (Mitte), Berlin
// Otto-Suhr-Allee, Charlottenburg (Charlottenburg), Berlin
// Cantianstr. 15, Prenzlauer Berg (Prenzlauer Berg), Berlin
// Graefestraße 2, Kreuzberg (Kreuzberg), Berlin
function determine_address_accuracy_im24($address){
	$data = array();
	$data['accuracy'] = 0;

	$matches = array();

	// full address
	if(preg_match('/^([^0-9\,]+) ([a-zA-Z0-9 \-\/\_]+), ([^0-9\,\(\)]+) \([^0-9\,\(\)]+\), ([^0-9\,]+)$/', $address, $matches)){
		$data['accuracy'] = 1;
		$data['street'] = $matches[1];
		$data['street_num'] = $matches[2];
		$data['minor_area'] = $matches[3];
		$data['major_area'] = $matches[4];

	// missing street number
	} else if(preg_match('/^([^0-9\,]+), ([^0-9\,\(\)]+) \([^0-9\,\(\)]+\), ([^0-9\,]+)$/', $address, $matches)){
		$data['accuracy'] = 2;
		$data['street'] = '';
		$data['street_num'] = $matches[1];
		$data['minor_area'] = $matches[2];
		$data['major_area'] = $matches[3];

	// missing street & street number
	} else if(preg_match('/^([^0-9\,\(\)]+) \([^0-9\,\(\)]+\), ([^0-9\,]+)$/', $address, $matches)){
		$data['accuracy'] = 3;
		$data['street'] = '';
		$data['street_num'] = '';
		$data['minor_area'] = $matches[1];
		$data['major_area'] = $matches[2];
	} 
	
	return $data;
}
?>
