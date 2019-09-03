<?php 
class products {
	
	private $mysqldb;
	
	public function __construct() {
		$this->mysqldb = new mysqldb();  
	}
	
	public function search() {
		
		$query	= $_GET['q'];
		$page	= ($_GET['p'] ? $_GET['p']:1);
		$perpage= ($_GET['n'] ? $_GET['n']:18);
		
		if($query) {
			
			$sql = "SELECT * FROM `products` WHERE DisplayName LIKE '%".$query."%' OR category LIKE '%".$query."%'";
			$start = ($page - 1) * $perpage;
			$sql.= " LIMIT $start,$perpage";
			$res = $this->mysqldb->getAll($sql);
			
			$sqltotal = "SELECT count(1) as total FROM `products` WHERE DisplayName LIKE '%".$query."%' OR category LIKE '%".$query."%'";
			$restotal = $this->mysqldb->getOne($sqltotal);
			$total = $restotal['total'];
			
			$return['data']['products'] = $res;
			$return['data']['total'] = $total;
		} else {
			$return['status'] = 4000;
			$return['msg'] = 'string required';
		}
		
		return $return;
	}
	
	public function categories() {
		
		$sql = "SELECT category as cat_name FROM `products` WHERE category!='' GROUP BY category";
		$res = $this->mysqldb->getAll($sql);
		
		$return['data']['categories'] = $res;
				
		return $return;
	}
	
	public function details() {
		$ProductId	= $_GET['id'];
		if($ProductId) {
			
			$sql = "SELECT * FROM `products` WHERE ProductId=".$ProductId;
			$res = $this->mysqldb->getOne($sql);
			
			$return['data'] = $res;
			
		} else {
			$return['status'] = 4000;
			$return['msg'] = 'string required';
		}
		
		return $return;
	}
	
	
}
?>