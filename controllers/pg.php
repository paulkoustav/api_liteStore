<?php 
require_once("./lib/config_paytm.php");
require_once("./lib/encdec_paytm.php");

require_once("cart.php");
	
class pg {
	
	private $mysqldb;
	//private $cart;
	
	public function __construct() {
		$this->mysqldb = new mysqldb();  
	}
	
	public function details() {
		
		$return = array();
		
		$UserId	= $_POST['UserId'];
		$payment_method	= $_POST['payment_method'];
		
		$checkSum = "";
		$paramList = array();

		
		
		$sql = 'SELECT c.id as CartId,p.OfferPrice,ci.Qty,(p.OfferPrice*ci.Qty) as itemTotal FROM `cart_items` as ci INNER JOIN products as p ON ci.ProductId=p.ProductId LEFT JOIN cart as c ON c.id=ci.CartId WHERE c.OrderId IS NULL AND ci.UserId='.$UserId;
		$res = $this->mysqldb->getOne($sql);
		$itemTotal = $res['itemTotal']; 
		$CartId = $res['CartId'];
		
		$sql2 = "INSERT INTO `payments` (`id`, `UserId`, `CartId`, `OrderId`, `txnAmount`, `payment_method`, `PG_ORDERID`, `INIT_CHECKSUMHASH`, `PG_TXNID`, `PG_TXNAMOUNT`, `PG_PAYMENTMODE`, `PG_CURRENCY`, `PG_TXNDATE`, `PG_STATUS`, `PG_RESPCODE`, `PG_RESPMSG`, `PG_GATEWAYNAME`, `PG_BANKTXNID`, `PG_BANKNAME`, `PG_CHECKSUMHASH`, `txnTime`) VALUES (NULL, '".$UserId."', '".$CartId."', '', '".$itemTotal."', '".$payment_method."', LEFT(UUID(),8), '', '', '', '', '', '', '', '', '', '', '', '', '', CURRENT_TIMESTAMP);";	
		$this->mysqldb->execute($sql2); 
		
		$sql3 = 'SELECT id,PG_ORDERID FROM `payments` WHERE UserId='.$UserId.' AND CartId = '.$CartId.' ORDER BY id DESC LIMIT 1';
		$res3 = $this->mysqldb->getOne($sql3);
		$PG_ORDERID = $res3['PG_ORDERID'];
		$PG_autoid = $res3['id'];
		
		
		
		if (isset($PG_ORDERID) && $PG_ORDERID != "") {

			$paramList["MID"] = PAYTM_MERCHANT_MID;
			$paramList["ORDER_ID"] = $PG_ORDERID;
			$paramList["CUST_ID"] = $UserId;
			$paramList["INDUSTRY_TYPE_ID"] = 'Retail';
			$paramList["CHANNEL_ID"] = 'WEB';
			$paramList["TXN_AMOUNT"] = $itemTotal;
			$paramList["WEBSITE"] = PAYTM_MERCHANT_WEBSITE;
			$paramList["CALLBACK_URL"] = PAYTM_CALLBACK_URL;
			
			$checkSum = getChecksumFromArray($paramList,PAYTM_MERCHANT_KEY);
			
			$paramList["CHECKSUMHASH"] = $checkSum;
			
			$sql4 = "UPDATE `payments` SET `INIT_CHECKSUMHASH` = '".$checkSum."' WHERE `id` = ".$PG_autoid;
			$this->mysqldb->execute($sql4);
			
			if($checkSum) {
				$return['data']['paramList'] = $paramList;
				$return['data']['method'] = 'post';
				$return['data']['path'] = 'https://securegw-stage.paytm.in/theia/processTransaction';
			} else {
				$return['status'] = 4000;
				$return['msg'] = 'pg details failed!!!';	
			}
			
			
			
		}
				
		return $return;
	}
	
	public function response() {
		
		$return = array();
		$OrderId = '';
		
		$INPUT = file_get_contents('php://input');
		parse_str($INPUT, $POST);
		
		$payment_method	= $POST['extra_input']['payment_method'];
		
		unset($POST['extra_input']['payment_method']);
		
		if($payment_method=='paytm') {
			
			$paytmChecksum = "";
			$paramList = array();
			$isValidChecksum = "FALSE";

			$paramList = $POST['extra_input'];
			$paytmChecksum = isset($POST['extra_input']["CHECKSUMHASH"]) ? $POST['extra_input']["CHECKSUMHASH"] : ""; //Sent by Paytm pg
			
			
			unset($paramList['extra_input']['payment_method']);
			
			//Verify all parameters received from Paytm pg to your application. Like MID received from paytm pg is same as your application’s MID, TXN_AMOUNT and ORDER_ID are same as what was sent by you to Paytm PG for initiating transaction etc.
			$isValidChecksum = verifychecksum_e($paramList, PAYTM_MERCHANT_KEY, $paytmChecksum); //will return TRUE or FALSE string.
			
			if($isValidChecksum == "TRUE") {
				//echo "<b>Checksum matched and following are the transaction details:</b>" . "<br/>";
				if ($POST['extra_input']["STATUS"] == "TXN_SUCCESS") {
					//echo "<b>Transaction status is success</b>" . "<br/>";
					//Process your transaction here as success transaction.
					//Verify amount & order id received from Payment gateway with your application's order id and amount.
					$POST['extra_input']['payment_method'] = $payment_method;
					
					$this->cart = new cart();
					$return = $this->cart->placeOrder($POST); //print_r($return);
					
				} else {
					echo "<b>Transaction status is failure</b>" . "<br/>";
				}
				
				//if (isset($POST['extra_input']) && count($POST['extra_input'])>0 )
				//{ 
					//foreach($POST['extra_input'] as $paramName => $paramValue) {
						//echo "<br/>" . $paramName . " = " . $paramValue;
					//}
				//} 
			} else {
				echo "<b>Checksum mismatched.</b>";
				//Process transaction as suspicious.
			}
		}
		
		
		
		
		
		return $return;
	}

	public function verify($param=NULL) {
		
		$return = array();
				
		$payment_method	= $param['payment_method'];
		
		unset($param['payment_method']);
		
		if($payment_method=='paytm') {
			
			$ORDER_ID = "";
			$requestParamList = array();
			$responseParamList = array();
		
			if (isset($param["ORDER_ID"]) && $param["ORDER_ID"] != "") {

				// In Test Page, we are taking parameters from POST request. In actual implementation these can be collected from session or DB. 
				$ORDER_ID = $param["ORDER_ID"];

				// Create an array having all required parameters for status query.
				$requestParamList = array("MID" => PAYTM_MERCHANT_MID , "ORDERID" => $ORDER_ID);  
				
				$StatusCheckSum = getChecksumFromArray($requestParamList,PAYTM_MERCHANT_KEY);
				
				$requestParamList['CHECKSUMHASH'] = $StatusCheckSum;

				// Call the PG's getTxnStatusNew() function for verifying the transaction status.
				$responseParamList = getTxnStatusNew($requestParamList);
			}
			
		}	
		
		if($responseParamList['STATUS']=='TXN_SUCCESS') {
			$return['msg'] = 'TXN SUCCESS...';
			$return['data'] = $responseParamList;
		} else {
			$return['status'] = 4000;
			$return['msg'] = 'TXN Failed!!!';
			$return['data'] = $responseParamList;
		}
		
		return $return;
	}	
}
?>