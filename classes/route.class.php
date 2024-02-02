<?php
/**
 * Route discovery - translates URLs into database items
 * @version 0.1
 * @package condiment
 * @subpackage Core
 * @author David Harris <theinternetlab@gmail.com>
 */

//! convert this to a singleton so info can be called from anywhere


//! add isOwner - so we can check they're owner
class Route{
	
	private $url;
	public $data = array();
	private $cache = array();
    public $operation = "";

	
	// url - full URL of page - including domain & GET variables
	function __construct($url , $prefixFolder = ''){

		$this->url = trim($url," \t\n\r\0\x0B/");

        if( $prefixFolder != '' && strpos($this->url, $prefixFolder . '/') === 0){
            $this->url = substr($this->url, strlen($prefixFolder)+1 );
        }

		if( $this->url === ""){
			//should always be a route;
            return  Utils::DoingItWrong("No route available");
		}else{
			$this->data = explode('/', $this->url);
		}

        //first entry goes into the
        if( count($this->data) > 0 ){
            $this->operation = array_shift($this->data);
        }else{
            return  Utils::DoingItWrong("No data available");
        }
		
	}

	// when script finishes update cache if needed
	function __destruct(){
		
	}
/*	
	public function getRouteInfo(){
		return $this->page;
	}
	
	public function getType(){
		return $this->type;
	}
	
	public function getController(){
		return 'controllers/' . $this->type . '/' . $this->page['template_file'] . '.class.php';
	}
	

	// this is for the breadcrumb builder - so it can access the full URL parts
	public function getAllParts(){
	
	}
*/	
	
	public function getInfo(){
		return var_export($this->data);
	}
	

}
