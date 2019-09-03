<?php 
require_once("pg.php");

class cart {
	
	private $mysqldb;
	
	public function __construct() {
		$this->mysqldb = new mysqldb();  
	}
	
	public function array_search_key_val($array, $key, $value)
	{
		$results = array();

		if (is_array($array)) {
			foreach ($array as $subarray) {
				if($subarray[$key]==$value) {
					$results = $subarray;	
				}
			}
		}

		return $results;
	}
	
	public function getAddress($param=NULL) {
		if($param['UserId']) {
			$sql = 'SELECT * FROM `address` WHERE isactive = 1 AND UserId = '.$param['UserId'];
		} else if($param['id']) {
			$sql = 'SELECT * FROM `address` WHERE isactive = 1 AND id = '.$param['id'];
		} else {
			return false;
		}
		$res = $this->mysqldb->getAll($sql);
		return $res;
	}
	
	public function addAddress() {
		
		$UserId = $_POST['UserId'];
		
		if($UserId) {
			
			$name = $_POST['name'];
			$email = $_POST['email'];
			$street = $_POST['street'];
			$state = $_POST['state'];
			$country = $_POST['country'];
			$pin = $_POST['pin'];
			
			$sql_addr = "INSERT INTO `address` (`id`, `UserId`, `name`, `email`, `street`, `state`, `country`, `pin`, `isdefault`, `isactive`, `created_date`) VALUES (NULL, ".$UserId.", '".$name."', '".$email."', '".$street."', '".$state."', '".$country."', '".$pin."', '0', '1', CURRENT_TIMESTAMP);";
			$this->mysqldb->execute($sql_addr);
			$address_id = $this->mysqldb->LastInsertId;
			
			if($address_id) {
				$this->setAddress(array('UserId' => $UserId, 'address_id' => $address_id));
			} else {
				$return['status'] = 4000;
				$return['msg'] = 'Failed!!!';
			}
			
		
			$return['status'] = 2000;
			$return['msg'] = 'Success...';
			return $return;
		} 
		$return['status'] = 4000;
		$return['msg'] = 'Failed!!!';
		return $return;
		
		
		
		return $res;
	}
	
	public function setAddress($param=NULL) {
		$param['UserId'] = (isset($_POST['UserId']) ? $_POST['UserId']:$param['UserId']);
		$param['address_id'] = (isset($_POST['address_id']) ? $_POST['address_id']:$param['address_id']);
		
		if($param['UserId'] && $param['address_id']) {
			$sql = "UPDATE `cart` SET `address_id` = ".$param['address_id']." WHERE OrderId IS NULL AND UserId = ".$param['UserId'];
			$this->mysqldb->execute($sql);
			$return['status'] = 2000;
			$return['msg'] = 'Success...';
			return $return;
		} 
		$return['status'] = 4000;
		$return['msg'] = 'Failed!!!';
		return $return;
		
	}
	
	public function details() {
		
		$UserId	= $_POST['UserId'];
		
		if($UserId) {
			
			$sql = 'SELECT p.ProductId,p.DisplayName,p.MRP,p.OfferPrice,ci.Qty,(p.OfferPrice*ci.Qty) as itemTotal FROM `cart_items` as ci INNER JOIN products as p ON ci.ProductId=p.ProductId LEFT JOIN cart as c ON c.id=ci.CartId WHERE c.OrderId IS NULL AND ci.UserId='.$UserId;
			$res = $this->mysqldb->getAll($sql);
			
			$res_address = $this->getAddress(array('UserId'=>$UserId));
			
			$return['data']['items'] = $res;
			$return['data']['address_all'] = $res_address;
			$address_default = $this->array_search_key_val($res_address, 'isdefault', 1);
			$return['data']['address_default'] = $address_default['id'];
		} else {
			$return['status'] = 4000;
			$return['msg'] = 'user id required';
		}
		
		return $return;
	}
	
	public function addedit() {
		
		$UserId	   = $_POST['UserId'];
		$ProductId = $_POST['ProductId'];
		$Qty = $_POST['Qty'];
		
		if($UserId && $ProductId) {
			
			$sql4 = "SELECT id FROM cart WHERE OrderId IS NULL AND UserId = ".$UserId;
			$res4 = $this->mysqldb->getOne($sql4);
			$CartId = $res4['id'];
				
			if($Qty) {
				
				if(!$CartId) {
					$sql5 = 'INSERT INTO `cart` (`id`, `UserId`, `OrderId`, `datetime`) VALUES (NULL, '.$UserId.', NULL, CURRENT_TIMESTAMP);';
					$this->mysqldb->execute($sql5);
					$CartId = $this->mysqldb->LastInsertId;
				}
				
				$sql1 = "SELECT id FROM cart_items WHERE UserId = ".$UserId." AND ProductId = ".$ProductId;
				$this->mysqldb->execute($sql1);
				$NumRows = $this->mysqldb->NumRows;
				if(!$NumRows) {
					$sql = "INSERT INTO cart_items (id, CartId, UserId, ProductId, Qty, datetime) VALUES (NULL, ".$CartId.", ".$UserId.", '".$ProductId."', ".$Qty.", CURRENT_TIMESTAMP);";	
				} else {
					$sql = "UPDATE cart_items SET Qty = ".$Qty." WHERE UserId = ".$UserId." AND ProductId = ".$ProductId;
				}
					
			} else {
				$sql = "DELETE FROM `cart_items` WHERE UserId = ".$UserId." AND ProductId = ".$ProductId;
			}
			
			$this->mysqldb->execute($sql);
			
			$sql7 = 'SELECT SUM((p.OfferPrice*ci.Qty)) as total FROM `cart_items` as ci INNER JOIN products as p ON ci.ProductId=p.ProductId LEFT JOIN cart as c ON c.id=ci.CartId WHERE c.OrderId IS NULL AND ci.CartId='.$CartId;
			$res7 = $this->mysqldb->getOne($sql7);
			$total = $res7['total'];
			
			$sql3 = 'UPDATE cart SET total='.$total.' WHERE id='.$CartId;
			$this->mysqldb->execute($sql3);			
			
			$return = $this->details($_POST);
			$return['status'] = 2000;
			$return['msg'] = 'Cart updated successfully';
			
		} else {
			$return['status'] = 4000;
			$return['msg'] = 'user id and product id are required';
		} 
		
		return $return;	
	}
	
	public function placeOrder($param=NULL) {
		
		$return = array();
		$return['status'] = 4000;
		$return['msg'] = 'Action failed';
		
		$paymentStatus = 'Fail';
		
		$PG_ORDERID = $PG_MID = $PG_TXNID = $PG_TXNAMOUNT = $PG_PAYMENTMODE = $PG_CURRENCY = $PG_TXNDATE = $PG_STATUS = $PG_RESPCODE = $PG_RESPMSG = $PG_GATEWAYNAME = $PG_BANKTXNID = $PG_BANKNAME = $PG_CHECKSUMHASH = '';
		
		
		if(!isset($_POST)) {
			$_POST = $param;
		}
		
		$UserId = (isset($_POST['UserId'])? $_POST['UserId']:0);
		$extra_input = $_POST['extra_input']; 
		$payment_method = $extra_input['payment_method'];
		
		if($payment_method == 'paytm') {
			
			$PG_ORDERID = (isset($extra_input['ORDERID']) ? $extra_input['ORDERID']:'');
			$PG_MID = (isset($extra_input['MID']) ? $extra_input['MID']:'');
			$PG_TXNID = (isset($extra_input['TXNID']) ? $extra_input['TXNID']:'');
			$PG_TXNAMOUNT = (isset($extra_input['TXNAMOUNT']) ? $extra_input['TXNAMOUNT']:'');
			$PG_PAYMENTMODE = (isset($extra_input['PAYMENTMODE']) ? $extra_input['PAYMENTMODE']:'');
			$PG_CURRENCY = (isset($extra_input['CURRENCY']) ? $extra_input['CURRENCY']:'');
			$PG_TXNDATE = (isset($extra_input['TXNDATE']) ? $extra_input['TXNDATE']:'');
			$PG_STATUS = (isset($extra_input['STATUS']) ? $extra_input['STATUS']:'');
			$PG_RESPCODE = (isset($extra_input['RESPCODE']) ? $extra_input['RESPCODE']:'');
			$PG_RESPMSG = (isset($extra_input['RESPMSG']) ? $extra_input['RESPMSG']:'');
			$PG_GATEWAYNAME = (isset($extra_input['GATEWAYNAME']) ? $extra_input['GATEWAYNAME']:'');
			$PG_BANKTXNID = (isset($extra_input['BANKTXNID']) ? $extra_input['BANKTXNID']:'');
			$PG_BANKNAME = (isset($extra_input['BANKNAME']) ? $extra_input['BANKNAME']:'');
			$PG_CHECKSUMHASH = (isset($extra_input['CHECKSUMHASH']) ? $extra_input['CHECKSUMHASH']:'');
			
			if($PG_CHECKSUMHASH){
				$sqlpgupdate = "UPDATE `payments` SET `PG_TXNID` = '".$PG_TXNID."', `PG_TXNAMOUNT` = '".$PG_TXNAMOUNT."', `PG_PAYMENTMODE` = '".$PG_PAYMENTMODE."', `PG_CURRENCY` = '".$PG_CURRENCY."', `PG_TXNDATE` = '".$PG_TXNDATE."', `PG_STATUS` = '".$PG_STATUS."', `PG_RESPCODE` = '".$PG_RESPCODE."', `PG_RESPMSG` = '".$PG_RESPMSG."', `PG_GATEWAYNAME` = '".$PG_GATEWAYNAME."', `PG_BANKTXNID` = '".$PG_BANKTXNID."', `PG_BANKNAME` = '".$PG_BANKNAME."', `PG_CHECKSUMHASH` = '".$PG_CHECKSUMHASH."' WHERE `PG_ORDERID` = '".$PG_ORDERID."'";
				$this->mysqldb->execute($sqlpgupdate);
			}
			
			$sql_userid = "SELECT UserId FROM `payments` WHERE PG_ORDERID='".$PG_ORDERID."' ORDER BY id DESC LIMIT 1";
			$res_userid = $this->mysqldb->getOne($sql_userid);
			$UserId = $res_userid['UserId'];
			
		}	
		
		
		
		if($UserId && $payment_method) {
			
			$sql1 = 'SELECT id,total,address_id FROM `cart` WHERE OrderId IS NULL AND UserId='.$UserId.' ORDER BY id ASC LIMIT 1';
			$res1 = $this->mysqldb->getOne($sql1);
			
			$CartId = $res1['id'];
			$total = $res1['total'];
			$address_id = $res1['address_id'];
			
			if($CartId && $total) {
				
				if($payment_method == 'paytm') {
					
					$sql_chk_trns = 'SELECT txnAmount FROM `payments` WHERE UserId='.$UserId.' AND CartId = '.$CartId.' ORDER BY id ASC LIMIT 1';
					$res_chk_trns = $this->mysqldb->getOne($sql_chk_trns);
					$DB_txnAmount = $res_chk_trns['txnAmount'];
					
					
					$this->pg = new pg();
					$verify = $this->pg->verify(array('payment_method'=>$payment_method,'ORDER_ID'=>$PG_ORDERID));
					$PG_TXNAMOUNT = $verify['data']['TXNAMOUNT'];
					$PG_STATUS = $verify['data']['STATUS'];
					
					if($PG_STATUS != 'TXN_SUCCESS') {
						$return['status'] = 4000;
						$return['msg'] = 'TXN Failed!!!';
						return $return;
					} else if($DB_txnAmount != $PG_TXNAMOUNT) {
						$return['status'] = 4000;
						$return['msg'] = 'TXNAMOUNT mismatch!!!';
						return $return;
					} else {
						$paymentStatus = 'Success';
					}
				
				} else {
					$paymentStatus = 'Success';
				}
				
				if($paymentStatus=='Fail') {
					$return['status'] = 4000;
					$return['msg'] = 'Try again later...';
					return $return;
				}
				
				
				$sql = "INSERT INTO `orders` (`id`, `UserId`, `CartId`, `amount`, `address_id`, `payment_method`, `PG_TXNID`, `PG_STATUS`, `datetime`) VALUES (NULL, '".$UserId."', '".$CartId."', '".$total."', '".$address_id."', '".$payment_method."', '".$PG_TXNID."', '".$PG_STATUS."', CURRENT_TIMESTAMP);";
				$this->mysqldb->execute($sql);
				$NumRows = $this->mysqldb->NumRows;
				$LastInsertId = $this->mysqldb->LastInsertId;	
				if($NumRows && $LastInsertId) {
					
					$sql9 = 'SELECT p.ProductId,p.DisplayName,p.MRP,p.OfferPrice,ci.Qty,(p.OfferPrice*ci.Qty) as itemTotal FROM `cart_items` as ci INNER JOIN products as p ON ci.ProductId=p.ProductId LEFT JOIN cart as c ON c.id=ci.CartId WHERE c.OrderId IS NULL AND ci.CartId='.$CartId;
					$res9 = $this->mysqldb->getAll($sql9);
					
					$sql2 = '';
					foreach($res9 as $k=>$v) {
						$sql2.= "INSERT INTO `orderitem` (`id`, `OrderId`, `ProductId`, `DisplayName`, `Qty`, `OfferPrice`, `itemTotal`, `datetime`) VALUES (NULL, ".$LastInsertId.", ".$v['ProductId'].", '".$v['DisplayName']."', ".$v['Qty'].", '".$v['OfferPrice']."', '".$v['itemTotal']."', CURRENT_TIMESTAMP);";
						
					}
					
					$sql2.= "UPDATE cart SET OrderId='.$LastInsertId.' WHERE id=".$CartId.";";
					if($PG_CHECKSUMHASH) {
						$sql2.= "UPDATE payments SET OrderId=".$LastInsertId." WHERE INIT_CHECKSUMHASH='".$PG_CHECKSUMHASH."'";	
					}
					
					$this->mysqldb->executeMultiple($sql2);
					
					$return['status'] = 2000;
					$return['msg'] = 'Order placed successfully. Order No.-'.$LastInsertId;
					$return['data']['OrderId'] = $LastInsertId;
					$return['data']['PG_TXNID'] = $PG_TXNID;
					
				} else {
					$return['status'] = 4000;
					$return['msg'] = 'Action failed';
				}
			}
		} else {
			$return['status'] = 4000;
			$return['msg'] = 'user id required';
		} 
		
		return $return;	
		
	}

	public function getOrderId() {
		
		$return = array();
		$OrderId ='';
				
		$PG_TXNID	= $_POST['PG_TXNID'];
		
		if($PG_TXNID) {
			$sql = "SELECT id as OrderId FROM `orders` WHERE PG_TXNID = '".$PG_TXNID."'";
		} else {
			return false;
		}
		$res = $this->mysqldb->getOne($sql);
		
		$OrderId = $res['OrderId'];
		
		if($OrderId) {
			$return['msg'] = 'Success...';
		} else {
			$return['status'] = 4000;
			$return['msg'] = 'Failed!!!';
		}
		$return['data']['OrderId'] = $OrderId;
		return $return;
	}
}
?>