<?php
/*
* Handle grabbing and storing raw data as txt files
* files are put in folders by identifier with timestamps on filename

**** 

!TODO add in job logging

*/
class datastorer {
    
	private $_registry; //passed in with app config info
	private $_transient = array(); //data used during the runnign of the script
	
	public $error = false;
	public $messages = array();

	// Constructor
	public function __construct($registry) {
    	$this->_registry = $registry;
    }
    
    
    /* $config is a row from the data_sources database table  (dtype, identifier, url, data, auth, active 
    *  	
    */   
    public function startup(){
        $this->_transient['configs'] = $this->_registry->db->getRows("SELECT * FROM data_sources WHERE active = 1");
        $this->_transient['timestamp'] = time();
    }
    
    public function process(){
        
        foreach($this->_transient['configs'] as $config):
            
            $this->_transient['dir_to_store'] = ABSPATH . $this->_registry->data_store_dir . DIRECTORY_SEPARATOR . $config['identifier'];
            $this->checkdirectory($config);
            
            if( $this->okaytorun($config) ):
                $this->doStore($config);
            endif;
        
        endforeach;
        
    }
    
    private function checkdirectory($config){
        	
    	if( !is_dir( $this->_transient['dir_to_store'] ) ) :
    	
    		$return = mkdir( $this->_transient['dir_to_store'] , 0777); 
    		
    		if( $this->_registry->debuglevel > 5 ):
    			
    			$this->messages[] = 'Directory WASNT there ' . $this->_transient['dir_to_store'];
    			
            endif;
            
            if($return === false):
    			
			    $this->error = true;
				$this->messages[] = 'Is the parent directory writable? ' . $this->_transient['dir_to_store'];
    				
    		endif;
    		
    	else:
    	
    		if($this->_registry->debuglevel > 5):
    			$this->messages[] = ' Directory IS there';
    		endif;
    	endif;
 
    }
    
    //check for last run - filename has timestamp in it
    private function okaytorun($config){
        
        $files = glob($this->_transient['dir_to_store'] . DIRECTORY_SEPARATOR . $config['identifier'] . '.*.txt');
        foreach( $files as $file):
        
            $filename = basename($file);
            $explo = explode('.', $file); //0 indentifier, 1 timestamp, 2 suffix
            $filetstamp = (int)$explo[1];

            //early out - if any are newer dont grab another
            if( $filetstamp + $config['freq'] > $this->_transient['timestamp'] ):
                return false;
            endif;
        endforeach;
        
        return true;
    }
    
    //grab the data and store in a file
    private function doStore($config){
        
        $filename = $config['identifier'] . '.' . $this->_transient['timestamp'] . '.txt';
        $fullpath = $this->_transient['dir_to_store'] . DIRECTORY_SEPARATOR . $filename; 
        
        switch( $config['dtype'] ):
            case 'get':
            
                file_put_contents($fullpath, fopen($config['url'], 'r'));
                //chown($fullpath, 'nobody');
                chmod("/somedir/somefile", 0766);

            break;
        endswitch;
        
        

        return true;
    }
}