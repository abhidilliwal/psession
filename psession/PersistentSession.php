<?php

require_once 'PersistentSessionExceptions.php';

/**
 * Persistent Session as name suggest creates a session which can persist longer ('remember me')
 *
 * This class need that you first create the below table structure
CREATE TABLE IF NOT EXISTS `psession` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(128) NOT NULL,
  `ua` varchar(128) DEFAULT NULL,
  `timeout` int(11) NOT NULL DEFAULT '0',
  `data` varchar(512) DEFAULT NULL,
  `username` varchar(64) DEFAULT NULL,
  `starttime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
 *
 * @author Abhishek Dilliwal (dilliwal.com)
 *
 */
class PersistentSession {
	
	const COOKIE_NAME = "PS";
	const TABLE_NAME = "psession";
	
	/**
	 *
	 * @var Integer
	 */
	protected $id;

	/**
	 * The main cookie key which is unique and is looked up
	 * @var String
	 */
	protected $key;
	
	/**
	 * 
	 * @var String
	 */
	private $clientKey;
	
	/**
	 * the user-agent string for the current session
	 *
	 * @var String
	 */
	public $ua;

	/**
	 * when it will expire
	 * @var
	 */
	protected $timeout;

	/**
	 * Any data apart from username which is to be stored.
	 * This is stored as JSON in the database
	 *
	 * @var JSON
	 */
	protected $data;

	/**
	 * Username which the app expects from the session
	 * @var String
	 */
	protected $username = null;

	/**
	 * the start time
	 * @var Timestamp
	 */
	public $starttime;
	
	/**
	 * The pdo object to access database
	 * @var PDO
	 */
	protected $db;

	function __construct(PDO $pdo) {
		$this->db = $pdo;
	}

	public function startSession() {
		// read the cookie
		if ($this->readClientCookie()){
			// lookup the cookie in database
			
			$this->readSession();
			
		}
		// check for timeout
		// if valid set the session
	}
	
	/**
	 * Sets the data to be saved in session
	 * @param array $data
	 */
	public function setData($data) {
		$this->data = json_encode($data);
	}
	
	public function getData() {
		return json_decode($this->data);
	}
	
	public function saveSession () {
		// save the session changed information
	}
	
	/**
	 * 
	 * @param String $clientKey
	 */
	protected function readSession () {
		$clientKey = $this->clientKey;
		if (empty($clientKey)) {
			throw new NotValidSessionException('Client Key not set');
		}
		$sql = 'SELECT * FROM ' . PersistentSession::TABLE_NAME . ' where key = :clientKey and timeout < :currentTime limit 1';
		try {
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(':clientKey', sha1($clientKey), PDO::PARAM_STR);
			$stmt->bindParam(':currentTime', time(), PDO::PARAM_INT);
			$stmt->execute();
		
			/* Bind by column name */
			$stmt->bindColumn('id', $this->id, PDO::PARAM_INT);
			$stmt->bindColumn('key', $this->key, PDO::PARAM_STR);
			$stmt->bindColumn('ua', $this->ua, PDO::PARAM_STR);
			$stmt->bindColumn('timeout', $this->timeout, PDO::PARAM_INT);
			$stmt->bindColumn('data', $this->data, PDO::PARAM_STR);
			$stmt->bindColumn('username', $this->username, PDO::PARAM_STR);
			$stmt->bindColumn('starttime', $this->starttime, PDO::PARAM_INT);
		
			if($stmt->fetch(PDO::FETCH_BOUND)){
				return true;
			} else {
				throw new NotValidSessionException();
			}
		} catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	
	/**
	 * get the username from the session
	 */
	public function getUsername() {
		return $this->username;
	}
	
	/**
	 * get the cookie stored in users browser
	 */
	protected function readClientCookie() {
		$this->clientKey = isset($_COOKIE[PersistentSession::COOKIE_NAME]) ? trim($_COOKIE[PersistentSession::COOKIE_NAME]) : false;
		return $this->clientKey;
	}
}

?>