<?php
require_once('functions.php');

// go through all websites
$websites = array('im24');
foreach($websites as $key => $val){
	require_once('fetch_'.$val.'.php');
	eval('fetch_'.$val.'();');
}

// create the list of distinct tags
create_tags_list();

// close any open db's
close_db();

// inserts a list of distinct tags
function create_tags_list(){
	$places = get_db_collection('places');
	
	// remove the old tags
	$tags = get_db_collection('tags');
	$tags->remove();

	// insert the list of distinct tags
	$tags_array = array();
	$obj = array();
	$return = array('tags' => 1, '_id' => 0);
	$cursor = $places->find($obj, $return);
	foreach($cursor as $place){
		if(is_array($place['tags']) && sizeof($place['tags'] > 0)){
			foreach($place['tags'] as $key => $tag){
				if(!isset($tags_array[$tag])){
					$tags->insert(array('tag' => $tag));
					$tags_array[$tag] = 1;
				}
				array_push($tags_array, $tag);
			}
		}
	}
}

// get lat/lng from address, this uses Google Maps API
function getLatLng($address){
	$collection = get_db_collection('addresses');

	// set default object
	$data = array('lat' => '', 'lng' => '');

	// get cached location
	$obj = array('address' => $address);
	$return = array('lat' => 1, 'lng' => 1, '_id' => 0);
	$loc = $collection->findOne($obj, $return);
	if($loc !== null){
		$data = $loc;

	} else {
		$location = json_decode(file_get_contents('http://maps.google.com/maps/geo?q='.urlencode($address).'&output=json&key=ABQIAAAAYNrxZqPTuL_GZv4fRpfHnBTzqM0MAg6XW9uzXQA5aaO_HBeqyxTQq-yWHLLsTFnJv4lK-4n91zKGSA'));
		if(isset($location->Placemark[0]->Point)){
			$first_location = $location->Placemark[0]->Point;
			$data = array('lat' => $first_location->coordinates[1], 'lng' => $first_location->coordinates[0]);
			$insert = array('lat' => $data['lat'], 'lng' => $data['lng'], 'address' => $address);
			$collection->update($obj, $insert, array('upsert' => true));
		}
	}

	return $data;
}
?>
