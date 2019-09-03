<?php 
class customer {
	
	private $mysqldb;
	
	public function __construct() {
		$this->mysqldb = new mysqldb();  
	}
	
	public function details($params=NULL) {
		
		$UserId	= (isset($_POST['UserId']) ? $_POST['UserId']:$params['UserId']);
		
		if($UserId) {
			$sql = 'SELECT * FROM customer WHERE UserId='.$UserId;
			$res = $this->mysqldb->getOne($sql);
			$return['data'] = $res;
		} else {
			$return['status'] = 4000;
			$return['msg'] = 'user id required';
		}
		
		return $return;
	}
	
	public function addedit($params=NULL) {
		
		$Name	 	= (isset($_POST['name']) ? $_POST['name']:$params['name']);
		$MobileNo  	= (isset($_POST['mobile']) ? $_POST['mobile']:$params['mobile']); 
		$EmailId	= (isset($_POST['email']) ? $_POST['email']:$params['email']); 
		$Country 	= (isset($_POST['country']) ? $_POST['country']:$params['country']); 
		$uploadedfiles 	= (isset($_POST['uploadedfiles']) ? $_POST['uploadedfiles']:$params['uploadedfiles']); 
		
		if($Name && $MobileNo) {
			
			$sql = "INSERT INTO customer (UserId, Name, MobileNo, EmailId, Country, UpdatedTime) VALUES (NULL, '".$Name."', '".$MobileNo."',  '".$EmailId."',  '".$Country."',CURRENT_TIMESTAMP);";
			$this->mysqldb->execute($sql);
			$UserId = $this->mysqldb->LastInsertId;
			
			if($UserId) {
				if(count($uploadedfiles)) {
					$sql_images = '';
					foreach($uploadedfiles as $file) {
						$sql_images.= "INSERT INTO `images` (`id`, `UserId`, `source`, `image_name`, `status`, `date_time`) VALUES (NULL, ".$UserId.", 'CUST_REG', '".$file."', 1, CURRENT_TIMESTAMP);";	
					}
					$this->mysqldb->executeMultiple($sql_images);	
				}
				$return = $this->details(array('UserId'=>$UserId));
			} else {
				$return['status'] = 4000;
				$return['msg'] = 'Action failed';
			}
		} else {
			$return['status'] = 4000;
			$return['msg'] = 'name and mobile are required';
		} 
		
		return $return;	
	}
	
	public function authentication() {
		
		$MobileNo  = (isset($_POST['MobileNo']) ? $_POST['MobileNo']:'');
		$Password  = (isset($_POST['Password']) ? $_POST['Password']:'');

		if($MobileNo && $Password) {
			
			$sql = 'SELECT UserId, Name FROM customer WHERE MobileNo='.$MobileNo.' AND Password=\''.$Password.'\'';
			$res = $this->mysqldb->getOne($sql);
			
			$UserId = $res["UserId"];
			$return['data'] = $res;
			
			if($UserId) {
				$return['msg'] = "Login sucessfully";	
			} else {
				$return['msg'] = "Login failed";
				$return['status'] = 4000;
			}
			
		} else {
			$return['status'] = 4000;
			$return['msg'] = 'mobile and password required';
		}
		
		return $return;
	}

	public function myorders() {
		
		$query	= $_GET['q'];
		$page	= ($_GET['p'] ? $_GET['p']:1);
		$perpage= ($_GET['n'] ? $_GET['n']:10);
		
		$total = 0;
		$result = array();
		
		if($query) {
			
			$sql = "SELECT o.id,o.UserId,o.amount,o.payment_method,o.datetime,oi.DisplayName,oi.Qty,oi.OfferPrice, oi.itemTotal FROM `orders` o LEFT JOIN orderitem oi ON o.id=oi.OrderId WHERE o.UserId=".$query;
			$start = ($page - 1) * $perpage;
			$sql.= " LIMIT $start,$perpage";
			$res = $this->mysqldb->getAll($sql);
			foreach($res as $k=>$v) {
				$result[$v['id']]['id'] = $v['id'];
				$result[$v['id']]['amount'] = $v['amount'];
				$result[$v['id']]['payment_method'] = $v['payment_method'];
				$result[$v['id']]['datetime'] = $v['datetime'];
				$result[$v['id']]['items'][] = $v;
				//$result[] = $v;
			}
			
			$sqltotal = "SELECT count(1) as total FROM `orders` WHERE UserId=".$query;
			$restotal = $this->mysqldb->getOne($sqltotal);
			$total = $restotal['total'];
			
			$return['data']['orders'] = $result;
			$return['data']['total'] = $total;
		} else {
			$return['status'] = 4000;
			$return['msg'] = 'user id required';
		}
		
		return $return;
	}
	
}
?>