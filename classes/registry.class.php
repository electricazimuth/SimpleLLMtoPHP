<?php

class Registry extends ArrayObject{
    
	private $_vars = array();
	
	public function __construct() {
	    parent::__construct(array(), ArrayObject::ARRAY_AS_PROPS);
	}
	
    public function offsetGet($index): mixed{
        
        if (!parent::offsetExists($index)) {
            return NULL;
        }
        return parent::offsetGet($index);
    }
}
//  Return type of Registry::offsetGet($index) should either be compatible with ArrayObject::offsetGet(mixed $key): mixed, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in /var/www/vhosts/racelab.azimuth.games/racelab-api/classes/registry.class.php on line 11