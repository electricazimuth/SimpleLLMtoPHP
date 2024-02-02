<?php
$_debug['file'][] = 'modules/admin/classes/serviceUse.class.php';

#########################################################################################
# Global class, takes care of checking service usage
# 
#########################################################################################
/*
CREATE TABLE IF NOT EXISTS `services` (
  `service_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `service` varchar(15) NOT NULL,
  `quota` mediumint(9) NOT NULL,
  PRIMARY KEY (`service_id`),
  KEY `service` (`service`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `service_use` (
  `row_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `service_id` tinyint(4) NOT NULL,
  `ip_int` int(11) NOT NULL,
  `happened` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`row_id`),
  KEY `happened` (`happened`),
  KEY `ip_int` (`ip_int`),
  KEY `service_id` (`service_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


INSERT INTO `services` (`service_id`, `service`, `quota`) VALUES (1, 'pca', 10);

*/


class ServiceUse{

	private $_registry;
	private $service;
	private $service_id = 0;
	private $ip;
	public $okay = true;
		
	// url - full URL of page - including domain & GET variables
	// table - is the database table that the variables are in, there will be translation  
	function __construct($registry, $service, $ip_address = false){

		$this->_registry = $registry;
		if(false == $ip_address){
			if( isset( $_SERVER['HTTP_X_FORWARDED_FOR']) ){
				$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}else{
				$ip_address = Utils::GetRealIpAddr();
			}
		}
		
		$this->ip = ip2long($ip_address);
		$this->service = $service;
		//are they over quota?
		$q = "SELECT s.service_id, s.quota , 
			( SELECT COUNT(u.ip_int) FROM service_use u 
			WHERE u.service_id = s.service_id AND u.ip_int = '". $this->ip ."' AND u.happened > DATE_SUB(NOW(), INTERVAL 1 HOUR)

			GROUP BY u.ip_int ) as used
			FROM services s
			WHERE
				s.service = '" . $this->service ."' LIMIT 1";
		$rows = $this->_registry->db->getRows($q);
		if( is_array($rows) && count($rows) > 0 ){
			$row = $rows[0];
			$this->service_id = $row['service_id'];
			if($row['used'] >= $row['quota']){
				$this->okay = false;
			}
		}
		
	
	}
	
	// when script finishes update cache if needed
	function __destruct(){
		if($this->okay && is_numeric($this->service_id) && is_numeric($this->ip)){
			$q = "INSERT INTO service_use (service_id,ip_int) VALUE (" . (int)$this->service_id ."," . (int)$this->ip . ")";
			$this->_registry->db->sendQuery($q);
		}
	}
	
}
