<?php

namespace abhidilliwal\psession;

use \PDO;

class PersistentSessionModel {

	const TABLE_NAME = "psession";

	protected $db;

	function __construct(PDO $pdo) {
		$this->db = $pdo;
	}

	/**
	 * @param PersistentSession $psession
	 * @throws PDOException
	 * @throws NotValidSessionException
	 */
	public  function getSession ($clientKey) {

		$sha1ClientKey = sha1($clientKey);
		$currentTime = time();

		if (empty($clientKey)) {
			throw new NotValidSessionException('Client Key not set');
		}
		$sql = 'SELECT * FROM `' . PersistentSessionModel::TABLE_NAME . '` where `key` = :clientKey and `timeout` > :currentTime limit 1';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':clientKey', $sha1ClientKey, PDO::PARAM_STR);
		$stmt->bindParam(':currentTime', $currentTime, PDO::PARAM_INT);
		$stmt->execute();

		/* Bind by column name */
		$stmt->bindColumn('id', $id, PDO::PARAM_INT);
		$stmt->bindColumn('ua', $ua, PDO::PARAM_STR);
		$stmt->bindColumn('timeout', $timeout, PDO::PARAM_INT);
		$stmt->bindColumn('data', $data, PDO::PARAM_STR);
		$stmt->bindColumn('username', $username, PDO::PARAM_STR);
		$stmt->bindColumn('starttime', $starttime, PDO::PARAM_INT);

		if($stmt->fetch(PDO::FETCH_BOUND)){
			$psession = new PersistentSession();
			$psession->id = $id;
			// remeber that we are not reading the key stored in database as it is encrypted
			$psession->clientKey = $clientKey;
			$psession->ua = $ua;
			$psession->timeout = $timeout;
			$psession->data = strlen($data) ? json_decode($data) : null;
			$psession->username = $username;
			$psession->starttime = $starttime;
			return $psession;
		} else {
			throw new NotValidSessionException();
		}
	}

	public function getAllUserSession($username = null) {

		$currentTime = time();

		if (empty($username)) {
			throw new NotValidSessionException('Username not provided');
		}
		$sql = 'SELECT * FROM `' . PersistentSessionModel::TABLE_NAME . '` where `username` = :username and `timeout` > :currentTime limit 1';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$stmt->bindParam(':currentTime', $currentTime, PDO::PARAM_INT);
		$stmt->execute();

		if($stmt->fetch(PDO::FETCH_BOUND)){
			return $psession;
		} else {
			throw new NotValidSessionException();
		}
	}

	/**
	 *
	 * @param PersistentSession $psession
	 * @throws NotValidSessionException
	 * @throws PDOException
	 */
	public function deleteSession ($psession) {
		$clientKey = sha1($psession->getClientKey());
		$username = $psession->username;

		if (!(isset($clientKey) && isset($username))) {
			throw new NotValidSessionException('Client key and username should be provided');
		}

		$sql = 'delete FROM `' . PersistentSessionModel::TABLE_NAME . '` where `key` = :clientKey and `username` = :username limit 1';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':clientKey', $clientKey, PDO::PARAM_STR);
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$stmt->execute();
	}

	/**
	 *
	 * @param String $username
	 * @throws NotValidSessionException
	 * @throws PDOException
	 */
	public function deleteAllSessions ($username) {

		if (!(isset($username))) {
			throw new NotValidSessionException('Username should be provided');
		}

		$sql = 'delete FROM `' . PersistentSessionModel::TABLE_NAME . '` where `username` = :username';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$stmt->execute();
	}

	/**
	 * Delete all expired sessions
	 * Caution: this should not be done tht often!
	 */
	static function gc () {

		$currentTime = time();

		$sql = 'delete FROM `' . PersistentSessionModel::TABLE_NAME . '` where `timeout` < :currentTime';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':currentTime', $currentTime, PDO::PARAM_INT);
		$stmt->execute();
	}

	/**
	 *
	 * @param PersistentSession $psession
	 * @throws NotValidSessionException
	 * @throws PDOException
	 */
	public function addSession ($psession) {


		$clientKey = sha1($psession->getClientKey());
		$timeout = $psession->timeout;
		$data = isset($psession->data) ? json_encode($psession->data) : '';
		$username = trim($psession->username);

		if (!isset($psession->username) || empty($clientKey) || !isset($timeout)) {
			throw new NotValidSessionException('Username, timeout and clientKey should be provided');
		}

		$sql = 'insert into `' . PersistentSessionModel::TABLE_NAME . '` (`key`, `ua`, `timeout`, `data`, `username`, `starttime`)
		values(:key, :ua, :timeout, :data, :username, :starttime)';
		$stmt = $this->db->prepare($sql);
		// we would be storing the hash of the client key and not the actual user client key
		$stmt->bindParam(':key', $clientKey, PDO::PARAM_STR);
		$stmt->bindParam(':ua', $psession->ua, PDO::PARAM_STR);
		$stmt->bindParam(':timeout', $timeout, PDO::PARAM_INT);
		// we will be storing the user data in JSON format, see its becoming so popular :D
		// well the reason is we are not using php serialize so that the DB can be utilized by some other app as well.
		$stmt->bindParam(':data', $data, PDO::PARAM_STR);
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$stmt->bindParam(':starttime', $psession->starttime, PDO::PARAM_INT);
		$stmt->execute();
	}

}