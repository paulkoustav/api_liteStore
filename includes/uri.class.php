<?php
class uri {
	
	public function segment($arg=null)
    {
		$segments = explode('/',$_SERVER['PHP_SELF']);
		
		if(!$arg) {
			
			return $segments;
		} else {
			return $segments[$arg];
		}
        
    }
} 

?>