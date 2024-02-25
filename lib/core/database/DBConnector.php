<?php

namespace PS\Core\Database;

use Config;
use PDO;
use PDOException;
use PS\Core\Logging\Logging;

class DBConnector
{
	private $db_host = Config::SERVERNAME;
	private $db_name = Config::DATABASE;
	private $db_user = Config::USERNAME;
	private	$db_pass = Config::PASSWORD;
	private	$db_port = Config::PORT;

	private $dbh;
	private $error;
	private $stmt;
	protected $transactionCounter = 0;

	public function __construct()
	{
		$dsn = 'mysql:host=' . $this->db_host . ';port=' . $this->db_host . ';dbname=' . $this->db_name;
		$db_options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);
		try {
			$this->dbh = new PDO($dsn, $this->db_user, $this->db_pass, $db_options);
		} catch (PDOException $e) {
			Logging::getInstance()->add(Logging::LOG_TYPE_ERROR, $e->getMessage());
			$this->error = $e->getMessage();
		}
	}

	function beginTransaction()
	{
		if (!$this->transactionCounter++) {
			return $this->dbh->beginTransaction();
		}
		return $this->transactionCounter >= 0;
	}

	function commit()
	{
		if (!--$this->transactionCounter) {
			return $this->dbh->commit();
		}
		return $this->transactionCounter >= 0;
	}

	function rollback()
	{
		if ($this->transactionCounter >= 0) {
			$this->transactionCounter = 0;
			return $this->dbh->rollback();
		}
		$this->transactionCounter = 0;
		return false;
	}

	public function query($query, ?array $params = null)
	{
		if (!str_contains(strtolower($query), 'select')) {
			Logging::getInstance()->add(Logging::LOG_TYPE_DB, str_replace(':', '', $query));
		}
		if (is_null($this->dbh)) {
			throw new \Exception('No connection to database!');
		}

		$arrValues = array();
		if (!is_null($params)) {
			foreach ($params as $key => $value) {
				$hash = hash("md5", $value);
				$arrValues[$hash] = $value;
				$query = str_replace($key, ":" . $hash, $query);
			}
		}

		$this->stmt = $this->dbh->prepare($query);
		if (count($arrValues)) {
			$this->stmt->execute($arrValues);
		}
	}

	public function bind($param, $value, $type = null)
	{
		if (is_null($type)) {
			switch (true) {
				case is_int($value);
					$type = PDO::PARAM_INT;
					break;
				case is_bool($value);
					$type = PDO::PARAM_BOOL;
					break;
				case is_null($value);
					$type = PDO::PARAM_NULL;
					break;
				default;
					$type = PDO::PARAM_STR;
					break;
			}
		}
		$this->stmt->bindValue($param, $value, $type);
	}

	public function execute($array = null)
	{
		return $this->stmt->execute($array);
	}

	public function lastInsertId()
	{
		return $this->dbh->lastInsertId();
	}

	public function rowCount()
	{
		return $this->stmt->rowCount();
	}

	public function result($array = null)
	{
		$this->execute($array);
		return $this->stmt->fetch();
	}

	public function resultSet($array = null)
	{
		$this->execute($array);
		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function close()
	{
		return $this->dbh = null;
	}
}
