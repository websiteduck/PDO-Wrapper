<?php
/**
 * Database
 * 
 * Manages connections, runs queries, returns results.
 * This class serves as a wrapper for PDO.
 *
 * @copyright  Copyright 2011-2013, Website Duck LLC (http://www.websiteduck.com)
 * @link       http://www.ignitext.com IgniteXT PHP Framework
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class Database
{
	protected $PDO_connection = null;
	protected $last_sth = null;
	
	/**
	 * Connects to a database server and stores the connection in $PDO_connection.
	 *  
	 * @param string $identifier A name used to identify this connection
	 * @param string $driver
	 * @param string $server
	 * @param string $username
	 * @param string $password
	 * @param string $database 
	 */
	public function connect($driver, $server, $username, $password, $database)
	{
		$this->PDO_connection = new \PDO($driver . ':host=' . $server . ';dbname=' . $database, $username, $password);
		$this->PDO_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	}
	
	/**
	 * Connects to a database the same way as the connect function but uses a DSN string instead
	 * 
	 * @param string $identifier A name used to identify this connection
	 * @param string $dsn Data Source Name, contains information required to connect to a database
	 * @param string $username
	 * @param string $password
	 */
	public function connect_dsn($dsn, $username = null, $password = null)
	{
		if ($username != null && $password != null)
			$this->PDO_connection = new \PDO($dsn, $username, $password);
		else if ($username != null)
			$this->PDO_connection = new \PDO($dsn, $username);
		else
			$this->PDO_connection = new \PDO($dsn);
		$this->PDO_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Use an existing PDO object instead of using this class to create one.
	 *
	 * @param PDO $pdo
	 */
	public function use_existing_pdo($pdo)
	{
		$this->PDO->connection = $pdo;
	}
	
	/**
	 * Gets the PDO object for the stored connection
	 * 
	 * @return PDO $pdo
	 */
	public function get_pdo()
	{
		return $this->PDO_connection;
	}
	
	/**
	 * Execute a query and return the PDO Statement
	 * 
	 * @param string $query
	 * @param string $value1 (optional, values to be escaped, then replaces ? in query, can be array or list)
	 * @return PDOStatement $statement
	 */
	public function query()
	{
		if ($this->PDO_connection === null) {
			throw new \Exception("Attempted to run a query before connecting to a database.");
		}
		$arguments = func_get_args();
		$query = array_shift($arguments);
		if (count($arguments)==1 && is_array($arguments[0])) $arguments = $arguments[0];
		$sth = $this->PDO_connection->prepare($query);
		$sth->execute($arguments);
		$this->last_sth = $sth;
		return $sth;
	}

	/**
	 * Execute a query and return an array of objects representing rows
	 * 
	 * @param string $query
	 * @param string $value1 (optional, values to be escaped, then replaces ? in query, can be array or list)
	 * @return array $rows
	 */
	public function rows()
	{
		$arguments = func_get_args();
		$sth = call_user_func_array(array($this, 'query'), $arguments);
		return $sth->fetchAll(\PDO::FETCH_OBJ);
	}
	
	/**
	 * Execute a query and return an array of class objects representing rows
	 * 
	 * @param string $class_name
	 * @param string $query
	 * @param string $value1 (optional, values to be escaped, then replaces ? in query, can be array or list)
	 * @return class $rows
	 */
	public function class_rows()
	{
		$arguments = func_get_args();
		$class_name = array_shift($arguments);
		$sth = call_user_func_array(array($this, 'query'), $arguments);
		return $sth->fetchAll(\PDO::FETCH_CLASS, $class_name);
	}
	
	/**
	 * Execute a query and return an associative array of objects representing rows, 
	 * uses $key to create associative array.
	 * 
	 * If $key = 'user_id' then the array will be in this form:
	 * $users['1092']['first_name']
	 * 
	 * @param string $key
	 * @param string $query
	 * @param string $value1 (optional, values to be escaped, then replaces ? in query, can be array or list)
	 * @return array $rows
	 */
	public function rows_key()
	{
		$arguments = func_get_args();
		
		if (count($arguments) > 1) $key = array_shift($arguments);
		else throw new \Exception('The rows_key function requires at least 2 parameters: $key and $query.');
				
		$rows = call_user_func_array(array($this, 'rows'), $arguments);
		
		if (count($rows) == 0) return $rows;
		
		if (property_exists($rows[0],$key) == false) 
			throw new \Exception('The specified key does not exist in the result set.');
		
		foreach ($rows as $row) 
			$rows_key[$row->$key] = $row;
		
		return $rows_key;
	}

	/**
	 * Execute a query and return a single object representing a row
	 * 
	 * @param string $query
	 * @param string $value1 (optional, values to be escaped, then replaces ? in query, can be array or list)
	 * @param array $row
	 */
	public function row()
	{
		$arguments = func_get_args();
		$sth = call_user_func_array(array($this, 'query'), $arguments);
		return $sth->fetch(\PDO::FETCH_OBJ);
	}
	
	/**
	 * Execute a query and return a single class object representing a row
	 * 
	 * @param string $class_name
	 * @param string $query
	 * @param string $value1 (optional, values to be escaped, then replaces ? in query, can be array or list)
	 * @param class $row
	 */
	public function class_row()
	{
		$arguments = func_get_args();
		$class_name = array_shift($arguments);
		$sth = call_user_func_array(array($this, 'query'), $arguments);
		$sth->setFetchMode(\PDO::FETCH_CLASS, $class_name);
		return $sth->fetch(\PDO::FETCH_CLASS);
	}
 
	/**
	 * Execute a query and return the first field that was selected
	 * 
	 * @param string $query
	 * @param string $value1 (optional, values to be escaped, then replaces ? in query, can be array or list)
	 * @return string $field
	 */
	public function value()
	{
		$arguments = func_get_args();
		$sth = call_user_func_array(array($this, 'query'), $arguments);
		return $sth->fetchColumn();
	}

	/**
	 * Execute a query and return an array of all fields in the first column of results
	 * 
	 * @param string $query
	 * @param string $value1 (optional, values to be escaped, then replaces ? in query, can be array or list)
	 * @return array $fields
	 */
	public function values_col()
	{
		$arguments = func_get_args();
		$sth = call_user_func_array(array($this, 'query'), $arguments);
		return $sth->fetchAll(\PDO::FETCH_COLUMN);
	}
	
	/**
	 * Execute a query and return an array of all fields in the first row of results
	 * 
	 * @param string $query
	 * @param string $value1 (optional, values to be escaped, then replaces ? in query, can be array or list)
	 * @return array $fields
	*/
	public function values_row()
	{
		$arguments = func_get_args();
		$sth = call_user_func_array(array($this, 'query'), $arguments);
		return $sth->fetch(\PDO::FETCH_NUM);
	}
	
	/**
	 * Execute a query and return the insert id.  If no parameters are passed,
	 * the function will just return the last insert id without running a query.
	 * 
	 * NOTE: This function will get the insert id for any query run through the
	 * PDO object, even if ran outside of this database object.
	 * 
	 * @param string $query
	 * @param string $value1 (optional, values to be escaped, then replaces ? in query, can be array or list)
	 * @return integer $insert_id
	 */
	public function insert_id()
	{
		$arguments = func_get_args();
		if (count($arguments) != 0) {
			$sth = call_user_func_array(array($this, 'query'), $arguments);	
		}		
		return $this->PDO_connection->lastInsertId();
	}
	
	/**
	 * Execute a query and return the number of affected rows.  If no parameters
	 * are passed, the function will just return the affected rows from the
	 * previous query.
	 * 
	 * NOTE: This function will only return the affected rows for queries ran
	 * using THIS database object, not externally using the PDO object.
	 * 
	 * @param string $query
	 * @param string $value1 (optional, values to be escaped, then replaces ? in query, can be array or list)
	 * @return integer $affected_rows
	 */
	public function affected_rows()
	{
		$arguments = func_get_args();
		if (count($arguments) != 0) {
			$sth = call_user_func_array(array($this, 'query'), $arguments);
		}
		else {
			$sth = $this->last_sth;
		}
		return $sth->rowCount();
	}
	
	public function begin_transaction()
	{
		return $this->PDO_connection->beginTransaction();
	}
	
	public function commit()
	{
		return $this->PDO_connection->commit();
	}
	
	public function roll_back()
	{
		return $this->PDO_connection->rollBack();
	}
	
	public function prepare($query)
	{
		return $this->PDO_connection->prepare($query);
	}
	
}
