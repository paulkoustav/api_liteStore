<?php
class mysqldb {
	private $conn;
	private $host;
	private $user;
	private $password;
	private $baseName;
	private $port;
	private $Debug;
	
	function __construct($params=array()) {
		$this->conn = false;
		$this->host = '127.0.0.1'; //hostname
		$this->user = 'root'; //username
		$this->password = ''; //password
		$this->baseName = 'litestore'; //name of your database
		$this->port = '3306';
		$this->debug = true;
		$this->connect();
	}
 
	function __destruct() {
		$this->disconnect();
	}
	
	function connect() {
		if (!$this->conn) {
			$this->conn = new mysqli($this->host, $this->user, $this->password, $this->baseName, $this->port);	
			if (!$this->conn) {
				$this->status_fatal = true;
				echo 'Connection failed';
				die();
			} 
			else {
				$this->status_fatal = false;
			}
		}
 
		return $this->conn;
	}
 
	function disconnect() {
		if ($this->conn) {
			$cnx = $this->conn;
			$cnx->close();
		}
	}
	
	function getOne($query) { 
		
		$return = '';
		$cnx = $this->conn;
		if (!$cnx || $this->status_fatal) {
			echo 'GetOne -> Connection failed';
			die();
		}
		
		$cur = $cnx->query($query);
		
		if ($cur == FALSE) {		
			$errorMessage = $cnx->error;
			$this->errorHandler($query, $errorMessage);
		} 
		else {
			$this->Error=FALSE;
			$this->BadQuery="";
			$tmp = $cur->fetch_array(MYSQLI_ASSOC);
			
			$return = $tmp;
		}
		
		
		return $return;
	}
	
	function getAll($query) { 
		$cnx = $this->conn;
		if (!$cnx || $this->status_fatal) {
			echo 'GetAll -> Connection failed';
			die();
		}
		
		$cur = $cnx->query($query);
		$return = array();

		while($data = $cur->fetch_array(MYSQLI_ASSOC)) {
			array_push($return, $data);
		}
 
		return $return;
	}
	
	function execute($query,$use_slave=false) { 
		$cnx = $this->conn;
		if (!$cnx||$this->status_fatal) {
			return null;
		}
 
		$cur = $cnx->query($query);
		if($query == 'UPDATE cart SET OrderId=1 WHERE id=1') {
		  echo $query; echo "<pre>"; print_r($cnx);
		}

		if ($cur == FALSE) {
			$ErrorMessage = $cnx->error;
			$this->errorHandler($query, $ErrorMessage);
		}
		else {
			$this->Error=FALSE;
			$this->BadQuery="";
			$this->NumRows = $cnx->affected_rows;
			$this->LastInsertId = $cnx->insert_id;
			return;
		}
		
	}
	
	function executeMultiple($query) { 
		$cnx = $this->conn;
		if (!$cnx||$this->status_fatal) {
			return null;
		}
 
		$cur = $cnx->multi_query($query);

		if ($cur == FALSE) {
			$ErrorMessage = $cnx->error;
			$this->errorHandler($query, $ErrorMessage);
		}
		else {
			$this->Error=FALSE;
			$this->BadQuery="";
			return;
		}
		
	}
	
	function errorHandler($query, $str_erreur) {
		$this->Error = TRUE;
		$this->BadQuery = $query;
		if ($this->Debug) {
			echo "Query : ".$query."<br>";
			echo "Error : ".$str_erreur."<br>";
		}
	}
}