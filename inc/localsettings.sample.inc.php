<?php

//! LOCAL DEV
$isDev = true;
$useGD = true;

date_default_timezone_set('Europe/London');

$WEBADDRESS 	= ( isset($_SERVER["HTTP_HOST"]) ) ?  'http://' . $_SERVER["HTTP_HOST"] . '/' : 'http://xxx.test/'; 

$DB_SERVER 		= 'X.X.X.X';// server name
$DB_DATABASE	= 'xxxxx'; // database to query
$DB_USER 		= 'xxxx';// username
$DB_PASS 		= 'xxxxx';// password

$LLM_SERVER 	= 'http://x.x.x.x:5xxx';

//CREATE USER 'lyrok'@'%' IDENTIFIED BY 'Lazib9rdm';
//GRANT ALL ON lyrics.* TO 'lyrok'@'%'
//$data_store_dir = 'datastore';
$debuglevel = 0;

$FOLDER_PREFIX = 'lyrics';

if ( ! defined( 'UPLOAD_FOLDER' ) ) {
	define( 'UPLOAD_FOLDER', 'uploads' );
}


//this is essentially allowing callses to be called from the gateway - we dont want to expose all classes
$allowed_operations = array();


if( isset( $_SERVER['HTTP_X_FORWARDED_FOR']) ){
	$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
}else{
	$ip_address = Utils::GetRealIpAddr();
}


		
$server_ips = array();

$URLPATH = '/';
if( isset($_SERVER['REDIRECT_URL']) ){
	$URLPATH = $_SERVER['REDIRECT_URL'];
}


//convert url
