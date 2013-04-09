<?php
$GLOBALS['m'] = null;
$GLOBALS['db'] = null;

// gets a collection from the DB
function get_db_collection($collection, $db = 'imapper'){
	if($GLOBALS['m'] === null){
		$GLOBALS['m'] = new Mongo();
	}
	if($GLOBALS['db'] === null){
		$GLOBALS['db'] = $GLOBALS['m']->{$db};
	}
	return $GLOBALS['db']->{$collection};
}

// closes a DB connection
function close_db(){
	if($GLOBALS['m'] !== null){
		$GLOBALS['m']->close();
		$GLOBALS['m'] = null;
	}
	if($GLOBALS['db'] !== null){
		$GLOBALS['db'] = null;
	}
}
?>
