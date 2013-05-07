<?php
Class DbConnect {

	var $host = '';
	var $user = '';
	var $password = '';
	var $database = '';
	var $persistent = false;
	var $conn = NULL;
	var $result= false;
	var $error_reporting = false;

	/*constructor function this will run when we call the class */
	function DbConnect ($host, $user, $password, $database, $error_reporting=true, $persistent=false) {
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
		$this->database = $database;
		$this->persistent = $persistent;
		$this->error_reporting = $error_reporting;
	}
	function open() {
		if ($this->persistent) {
			$func = 'mysql_pconnect';
		} else {
			$func = 'mysql_connect';
		}
		
		/* Connect to the MySQl Server */
		$this->conn = $func($this->host, $this->user, $this->password);
		if (!$this->conn) {
			return false;
		}
		
		/* Select the requested DB */
		if (@!mysql_select_db($this->database, $this->conn)) {
			return false;
		}
		
		return true;
	}

	/*close the connection */
	function close() {
		return (@mysql_close($this->conn));
	}

	/* report error if error_reporting set to true */
	function error() {
		if ($this->error_reporting) {
			return (mysql_error()) ;
		}
	}
	function query($sql) {
		$this->result = @mysql_query($sql, $this->conn);
		return($this->result != false);
	}

	function fetcharray() {
		return(mysql_fetch_array($this->result));
	}
	
	function fetchassoc() {
		return(@mysql_fetch_assoc($this->result));
	}
	
	function freeresult() {
		return(@mysql_free_result($this->result));
	}
}
?>