<?php
//if (isset($_GET['dev'])) {
	ini_set("display_errors","1");
	error_reporting(E_ALL);
//} else {
//	ini_set("display_errors","0");
//	error_reporting(E_PARSE);
//}

/** Absolute path to the directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

$classes_to_load = array('datastorer','registry','serviceuse','db','route','utils', 'koboldapi');
foreach($classes_to_load as $class_load){
	require_once(ABSPATH . 'classes/' . $class_load . '.class.php');
}

$local_setting_file = ABSPATH . 'inc/localsettings.inc.php';//localsettings.inc.php'; localsettings.inc.php
if( file_exists($local_setting_file)):
	require($local_setting_file); //common settings
else:
	require(ABSPATH . 'inc/settings.inc.php'); //common settings
endif;




$registry = new Registry();
$registry->db = new Db( $DB_USER, $DB_PASS, $DB_DATABASE, $DB_SERVER );
$registry->debuglevel = $debuglevel;
$registry->llm = new KoboldApi($LLM_SERVER);
//$registry->data_store_dir = $data_store_dir;


/*
$registry->route = new Route( $URLPATH, $FOLDER_PREFIX );

//$route = new Route( $URLPATH, $FOLDER_PREFIX );

if( in_array($registry->route->operation, $allowed_operations ) ){

	$operator = new $registry->route->operation;
	$operator->SetRegistry($registry);
	
}else{

	Utils::DoingItWrong("No operator for that");
}
*/
