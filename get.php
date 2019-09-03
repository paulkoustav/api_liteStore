<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description'); 

include('includes/uri.class.php'); 
include('includes/response.class.php');
include('includes/mysqldb.class.php'); 

$uri = new uri(); 
$response = new response();

$segments = $uri->segment();

if(isset($segments[3])) {

$controller = $segments[3];
$method = $segments[4];
$queryString = $segments[5];
parse_str($queryString, $queryArr);
$_GET = $queryArr;

$ctrlPath = "controllers/".$controller.".php";
$return = array();

require($ctrlPath); 

if (class_exists($controller)) {
	if(method_exists($controller,$method)) {
		$theCtrl = new $controller();
		$return = $theCtrl->$method();		
	} else {
		$return['status'] = 5000;
		$return['msg'] = 'method not exists';
	}  
} else {
	$return['status'] = 5000;
	$return['msg'] = 'controller not exists';
}


die($response->data($return));

} else {
   die('wrong call!!!');
}

?>