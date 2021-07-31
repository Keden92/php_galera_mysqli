<?php

/**********************************************************

MIT License
Copyright (c) 2021 Keden92


**********************************************************/

class galera_mysqli extends mysqli
{
	const galera_errno = 1234; 									// Feel free to customize 
	const galera_error = "Host not in useable wsrep state:"; 	// Feel free to customize
	
	private $DB;
	private $host;
	private $user;
	private $pass;
	private $port;
	private $socket;
	private $hostindex;
 		
 	function __construct($host = null,
						 string $username = null,
						 string $passwd = null,
						 string $dbname = null,
						 int $port = null,
						 string $socket = null,
						 int $hostindex = null)
 	{ 
		// Init Variables
		if(is_null($host)) 		$host 		= ini_get("mysqli.default_host");
		if(is_null($username)) 	$username 	= ini_get("mysqli.default_user");
		if(is_null($passwd)) 	$passwd 	= ini_get("mysqli.default_pw");
		if(is_null($dbname))	$dbname		= "";
		if(is_null($port)) 		$port 		= ini_get("mysqli.default_port");
		if(is_null($socket)) 	$socket 	= ini_get("mysqli.default_socket");

		// Store Variables 
		$this->host 	= $host;
		$this->user 	= $username;
		$this->pass 	= $passwd;
		$this->DB 		= $dbname;
		$this->port 	= $port;
		$this->socket 	= $socket;
		
		if(!is_array($host)) // Single Host
		{
			$this->parent_construct($host, $username, $passwd, $dbname, $port, $socket, true);
		}
		elseif(!is_null($hostindex)) // Use Hostindex if set
		{
			$this->hostindex = $hostindex;
			$this->parent_construct($host[$hostindex], $username, $passwd, $dbname, $port, $socket, true);
		}
		else // Random Shuffle
		{
			$server_cnt = count($host);
			$order = array();
			for($i=0;$i<$server_cnt;$i++) $order[] = $i;
			shuffle($order);
			$order = array_values($order);
			
			$found = false;
			for($i=0;$i<$server_cnt;$i++)
			{	
				if($this->parent_construct($host[$order[$i]], $username, $passwd, $dbname, $port, $socket, false))
				{
					$found = true;
					$this->hostindex = $order[$i];
					break;
				}
				else
					$this->close();
			}
			
			if(!$found)	throw new Exception(self::galera_error.' all hosts', self::galera_errno);
		}		
 	}
	
	private function parent_construct($host, $username, $passwd, $dbname, $port, $socket, $throw)
	{
		parent::__construct($host, $username, $passwd, $dbname, $port, $socket);
		
		if ($this->connect_error)	
		{
			if($throw) throw new Exception($this->connect_error, $this->connect_errno);
			return false;
		}
		
		if(!$this->galera_check())
		{
			if($throw) throw new Exception(self::galera_error.$host, self::galera_errno);
			return false;
		}
		return true;
	}
	
	private function galera_check()
	{
		/******************************************************************************************************************
		Num 	Comment 		Description
		1 		Joining 		Node is joining the cluster
		2 		Donor/Desynced 	Node is the donor to the node joining the cluster (USEABLE ONLY IN 2 SERVER SIUE CLUSTER)
		3 		Joined 			Node has joined the cluster
		4 		Synced 			Node is synced with the cluster (THE ONLY USEABLE STATE!!)
		******************************************************************************************************************/
		
		$g_state = 0;
		if ($result = $this->query("SHOW STATUS WHERE Variable_name = 'wsrep_local_state';")) 
		{
			while($row = $result->fetch_array()) $g_state = $row["Value"];
			
			if($g_state == 0) // mysql ok but galera state not found (galera=404 xD)
			{
				$g_size = -1;
				if ($result2 = $this->query("SHOW STATUS WHERE Variable_name = 'wsrep_cluster_size';"))  // Check Important!!!! - prevent SplitBrain
				{
					while($row = $result2->fetch_array()) $g_size = $row["Value"];
					if($g_size == 0) return true; // Is no Cluster if Cluster-Size == 0
				}
			}
			elseif($g_state == 4) return true; // Node State == 4 - Synced // ok
			/********************************************************************************************************/
			//elseif($g_state == 2) return true; // Node State == 2 - Donor // (USEABLE ONLY IN 2 SERVER SIUE CLUSTER)
			/********************************************************************************************************/
		}
		return false;
	}
 		
	function __sleep()
	{
		return array("host", "user", "pass", "DB", "port", "socket", "hostindex");
	}
	
	function __wakeup()
	{
		$this->__construct($this->host, $this->user, $this->pass, $this->DB, $this->port, $this->socket, $this->hostindex);
	}
	
	public function select_db($database)
	{
		$this->DB = $database;
		return parent::select_db($database);
	}

	public function get_selected_db()
	{
		return $this->DB;
	}
}

?>