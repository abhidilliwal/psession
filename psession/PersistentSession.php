<?php

namespace abhidilliwal\psession;

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


	/**
	 *
	 * @var Integer
	 */
	public $id;

	/**
	 * The main cookie key which is unique and is looked up
	 * @var String
	 */
	public $key;

	/**
	 *
	 * @var String
	 */
	public $clientKey;

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
	public $timeout;

	/**
	 * Any data apart from username which is to be stored.
	 * This is stored as JSON in the database
	 *
	 * @var JSON
	 */
	public $data;

	/**
	 * Username which the app expects from the session
	 * @var String
	 */
	public $username = null;

	/**
	 * the start time
	 * @var Timestamp
	 */
	public $starttime;

	function __construct() {
	}

	/**
	 * get the username from the session
	 */
	public function getUsername() {
		return $this->username;
	}

	public function getClientKey() {
		return $this->clientKey;
	}
}

?>