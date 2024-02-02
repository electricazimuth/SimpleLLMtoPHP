<?php
/*
* Mysql database class - only one connection alowed
*/
class Db {
	private $_connection;
	private static $_instance; //The single instance
	private $_loopError = false;
	public $options = array();

	/*
	Get an instance of the Database
	@return Instance
	*/
	public static function getInstance() {
		if(!self::$_instance) { // If no instance then make one
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	// Constructor
	public function __construct($user, $password, $dbase, $server) {

	// we will loop attempt to get a connection to the db - if it fails we try again until we hit number of attempts
	// hopefully the pause and re attempt allows user to get a connection
		$numAttempts = 5;
		$attempts = 0;
		$this->_loopError = true;
		
		do {
		    
		    try{
			    if(!empty($server) && !empty($user) && !empty($password) && !empty($dbase)){	

			        $this->_connection = new mysqli($server, $user, $password, $dbase);
			        
			        
		        }else{
			        die('<!-- ndefdb -->');
		        }
		    }
		    
		    catch(Exception $e){
		        $attempts++;
		        usleep(500000); // 0.5sec
		        continue; //skip the rest of the current loop - back to start of do loop (_loopError stays true)
		    }
		
		    $this->_loopError = false; 
		    break; 
		    
		} while($attempts < $numAttempts);		
		
		
		if($this->_loopError){
		//tried num attempt to connect couldnt do it server up error page
			error_log('Could not connect to MySQL ' . $dbase . ' ' .$user);
		    echo 'Sorry website busy, please come back later';
		    die();
		}
	
		// Error handling
		if( mysqli_connect_error() ) {
			trigger_error("Failed to access the database " . mysqli_connect_error(), E_USER_ERROR);
		}
	}

	// Magic method clone is empty to prevent duplication of connection
	private function __clone() { }

	// Get mysqli connection
	public function getConnection() {
		return $this->_connection;
	}
/*	
	//just key value settings
	public function getSettings( $type = 'auto' ){
		$rows = $this->getRows("SELECT * FROM options WHERE loadtype = '".$type."'");
		foreach($rows as $row){
			$this->options[ $row['option_name'] ] = $row['option_value'];
		}
		
	}
*/	
/*
	public function updateSettings(string $key, string $value ){
		$q = "UPDATE options SET option_value = '" . $value . "' WHERE option_name = '" . $key . "'";
		if( isset($this->options[$key]) ){
			$this->options[$key] = $value;
		}
		$this->sendQuery($q);
		
	}
*/

	public function getRowsP(string $query, array $params, string $types = "", $key = false) {

		$return = array();

		$types = ($types == "") ? str_repeat("s", count($params)) : $types;

		if ( $stmt = $this->_connection->prepare($query) ){
		
			$stmt->bind_param($types, ...$params);
			$stmt->execute();
			//var_dump($okay);

			$result = $stmt->get_result();

			printf("%s\n", $this->_connection->info);
			
			if($result === false){
				$msg = $this->_connection->errno . ':' . $this->_connection->error . "\r\n\r\n" . $query .  " \r\n\r\n ";// . var_export(debug_backtrace(), true) .  " \r\n\r\n " . var_export($_SERVER , true);
			
				$return = 'Database error <br> ' . str_replace('->', '', $msg)  . ' ';
				
			}
			
			//var_dump( gettype($result) );
			if( gettype($result) == 'boolean'){
				return $result;
			}
			
			while ($row = $result->fetch_assoc() ) {
			//while( $row = mysql_fetch_array($result, MYSQL_ASSOC) ){
				if(isset($key) && $key != false){
					$return[ $row[$key] ] = $row;
				}else{
					$return[] = $row;
				}
			}
		
			return $return;
		}else{
			$msg = $this->_connection->errno . ':' . $this->_connection->error . " Check your get query " . $query;
			
			Utils::DoingItWrong($msg);
		}
	}
		
	public function getRows($query,$key = false){
	//
	// returns an associated array result from a mysql query ready to be "foreach"-ed through
	// $key will add a value as the key to each arrays row
	// otherwise is just incremented
	//
//		$db = dijonDb::getInstance();
//		$mysqli = $db->getConnection();
	
	//	$timeparts = explode(' ',microtime());
	//	$starttime = $timeparts[1].substr($timeparts[0],1);
	
		$return = array();
		//$result = mysql_query( preg_replace( '/[\t]+/', ' ', $query ), DB_CONNECTION);
		$result = $this->_connection->query( $query );
		if($result === false){
			$msg = $this->_connection->errno . ':' . $this->_connection->error . "\r\n\r\n" . $query .  " \r\n\r\n ";// . var_export(debug_backtrace(), true) .  " \r\n\r\n " . var_export($_SERVER , true);
		
			$return = 'Database error <br> ' . str_replace('->', '', $msg)  . ' ';
			
		}
		
		//var_dump( gettype($result) );
		if( gettype($result) == 'boolean'){
			return $result;
		}
		
		while ($row = $result->fetch_assoc() ) {
		//while( $row = mysql_fetch_array($result, MYSQL_ASSOC) ){
			if($key != false){
				$return[ $row[$key] ] = $row;
			}else{
				$return[] = $row;
			}
		}
	
	//	$timeparts = explode(' ',microtime());
	//	$endtime = $timeparts[1].substr($timeparts[0],1);
	//	$_debug['query'][] = array('time' => $endtime - $starttime . ' secs', 'query' => $query);
	
		return $return;
	
	}
/*	
	public function log_entry($title, $status = 'completed', $table = 'logs'){
		$data = array(
				'title' => $title, 
				'status' => $status
				);
				
		$insert_q = $this->makeInsertQuery($table, $data, 'insert');
		return $this->sendQuery( $insert_q );
	}
	
	function job_log( $job, $status, $info, $data = ''){
        $data = array( 'job' => $job, 'status' => (int)$status, 'info' => $info, 'data' => $data);
        $q = $this->makeInsertQuery('jobs', $data);
    	// do insert
    	$insertId = $this->sendQuery($q);
    	return $insertId;   
    }
    
    function update_job_log($job_id, $status, $data = ''){
        $q = "UPDATE jobs SET data = '" . $data . "', status = ".(int)$status." WHERE id = " . (int)$job_id;
        $this->sendQuery($q);
    }
*/

/*
TYPES
i 	corresponding variable has type int
d 	corresponding variable has type float
s 	corresponding variable has type string
b 	corresponding variable is a blob and will be sent in packets

*/

	public function sendQueryP(string $query, array $params, string $types = "") {

		$return = 0;

		$types = ($types == "") ? str_repeat("s", count($params)) : $types;

		if( $stmt = $this->_connection->prepare($query) ){

        	$stmt->bind_param($types , ...$params);//$identifier, $md5);
        	$stmt->execute();

			$er = $this->_connection->errno; 
			//0 = no error
			//var_dump("err" . $er);
		
			if(!$er){
				if( substr($query, 0,6) == 'INSERT'){
					$return = $this->_connection->insert_id; 
				}else{
					$return = $this->_connection->affected_rows;
				}
		
			}else{

				switch($er) {
					case 1062 :
						$return = 'duplicate';
						break;
					case 1065 :
						$return = 'empty';
						break;
					default :
						$return = 'send qerror';
						if(defined('DEBUG') && DEBUG == '1') {
							
							echo $query . '<br>';
							die(' er:' .  $this->_connection->error  );
						}
						break;
				}
			}

			return $return;


		}else{
			$msg = $this->_connection->errno . ':' . $this->_connection->error . " Check your send query " . $query;
			
			Utils::DoingItWrong($msg);
		}
	}

	
	/**
	 * for INSERT, UPDATE and DELETE
	 * returns the INSERT id OR number of rows affected or an error string on failure
	 **/
	public function sendQuery($query){
		//die("OLD SENDQ");
		//$db = dijonDb::getInstance();
		//$mysqli = $db->getConnection();
		$result = $this->_connection->query($query);
	
		$er = $this->_connection->errno; 
		//0 = no error
	
		if(!$er){
			if( substr($query, 0,6) == 'INSERT'){
				$return = $this->_connection->insert_id; 
			}else{
				$return = $this->_connection->affected_rows;
			}
	
		}else{

			switch($er) {
				case 1062 :
					$return = 'duplicate';
					break;
				case 1065 :
					$return = 'empty';
					break;
				default :
					$return = 'mysqlError';
					if(defined('DEBUG') && DEBUG == '1') {
						
						echo $query . '<br>';
						die(' er:' .  $this->_connection->error  );
					}
					break;
			}
		}
	
		return $return;
	}
	
	// build an update or insert query from an array
	public function makeInsertQuery($table, $data, $action = 'insert', $parameters = '') {
		reset($data);
	
		if ($action == 'insert' || $action == 'replace' || $action == 'ignore') {
			if($action == 'replace') {
				$query = 'REPLACE INTO ' . $table . ' (';
			}elseif($action == 'ignore'){
				$query = 'INSERT IGNORE INTO ' . $table . ' (';
			}else{
				$query = 'INSERT INTO ' . $table . ' (';
			}
			
			$columns = array();
			$values = array();
			//todo - rewrite for php7.2+
			foreach($data as $column => $value):

				$columns[] = '`' . $column . '`';
				
				switch ((string)$value) {
					case 'NOW()':
						$values[] = 'NOW()';
						break;
					case 'NULL':
						$values[] = 'NULL';
						break;
					default:
					    if( substr($value, 0, strlen('FROM_UNIXTIME')) == 'FROM_UNIXTIME'):
					        $values[] = $value;
					    else:
					        $values[] = "'" . $this->_connection->real_escape_string($value) . "'";
					    endif;
						//strip non utf 8 characters from being inserted
						break;
				}
				
			endforeach;

			$query .= implode(',', $columns) .  ') VALUES (' . implode(',', $values) . ')';
			
	
		} elseif ($action == 'update') {
	
			$query = 'UPDATE ' . $table . ' SET ';
	
			//while (list($columns, $value) = each($data)) {
			foreach($data as $column => $value){
				switch ((string)$value) {
					case 'NOW()':
						$query .= '`' . $column . '` = NOW(), ';
						break;
					case 'NULL':
						$query .= '`' . $column .= '` = NULL, ';
						break;
					default:
					    if( substr($value, 0, strlen('FROM_UNIXTIME')) == 'FROM_UNIXTIME'):
					        $query .= '`' . $column . "` = " . $value . ", ";
					    else:
					        $query .= '`' . $column . "` = '" . $this->_connection->real_escape_string( $value ) . "', ";
					    endif;
						//$query .= '`' . $columns . '` = \'' . $value . '\', ';
						//$query .= '`' . $columns . "` = '" . $this->_connection->real_escape_string( $value ) . "', ";
						break;
				}
			}
			
			$query = substr($query, 0, -2) . ' WHERE ' . $parameters;
		}
	
		return $query;
	}
}
