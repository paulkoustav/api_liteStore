<?php
class response {
	
	private $params;
	
	public function data($params=array()) {
		
       $this->params['status'] = (isset($params['status']) ? $params['status']:2000);
	   $this->params['msg'] = (isset($params['msg']) ? $params['msg']:($this->params['status'] == 2000 ? 'Success':'Fail'));
	   $this->params['data'] = (isset($params['data']) ? $params['data']:'');
	   return json_encode($this->params);
	   
    }
} 

?>