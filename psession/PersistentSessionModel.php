<?php

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
		if (empty($clientKey)) {
			throw new NotValidSessionException('Client Key not set');
		}
		$sql = 'SELECT * FROM `' . PersistentSessionModel::TABLE_NAME . '` where `key` = :clientKey and `timeout` > :currentTime limit 1';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':clientKey', sha1($clientKey), PDO::PARAM_STR);
		$stmt->bindParam(':currentTime', time(), PDO::PARAM_INT);
		$stmt->execute();

		/* Bind by column name */
		$stmt->bindColumn('id', $id, PDO::PARAM_INT);
		$stmt->bindColumn('key', $key, PDO::PARAM_STR);
		$stmt->bindColumn('ua', $ua, PDO::PARAM_STR);
		$stmt->bindColumn('timeout', $timeout, PDO::PARAM_INT);
		$stmt->bindColumn('data', $data, PDO::PARAM_STR);
		$stmt->bindColumn('username', $username, PDO::PARAM_STR);
		$stmt->bindColumn('starttime', $starttime, PDO::PARAM_INT);

		if($stmt->fetch(PDO::FETCH_BOUND)){
			$psession = new PersistentSession();
			$psession->id = $id;
			$psession->key = $key;
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
		if (empty($username)) {
			throw new NotValidSessionException('Username not provided');
		}
		$sql = 'SELECT * FROM `' . PersistentSessionModel::TABLE_NAME . '` where `username` = :username and `timeout` > :currentTime limit 1';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':username', sha1($clientKey), PDO::PARAM_STR);
		$stmt->bindParam(':currentTime', time(), PDO::PARAM_INT);
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
		$clientKey = $psession->getClientKey();
		$username = $psession->username;

		if (!(isset($clientKey) && isset($username))) {
			throw new NotValidSessionException('Client key and username should be provided');
		}

		$sql = 'delete FROM `' . PersistentSessionModel::TABLE_NAME . '` where `key` = :clientKey and `username` = :username limit 1';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':clientKey', sha1($clientKey), PDO::PARAM_STR);
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
	public function gc () {
		$sql = 'delete FROM `' . PersistentSessionModel::TABLE_NAME . '` where `timeout` < :currentTime';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':currentTime', time(), PDO::PARAM_INT);
		$stmt->execute();
	}

	/**
	 *
	 * @param PersistentSession $psession
	 * @throws NotValidSessionException
	 * @throws PDOException
	 */
	public function addSession ($psession) {
		$clientKey = $psession->getClientKey();
		$timeout = $psession->timeout;
		if (!isset($psession->username) || empty($clientKey) || empty($timeout)) {
			throw new NotValidSessionException('Username, timeout and clientKey should be provided');
		}

		$sql = 'insert into `' . PersistentSessionModel::TABLE_NAME . '` (`key`, `ua`, `timeout`, `data`, `username`, `starttime`)
		values(:key, :ua, :timeout, :data, :username, :starttime)';
		$stmt = $psession->db->prepare($sql);
		$stmt->bindParam(':key', sha1($clientKey), PDO::PARAM_STR);
		$stmt->bindParam(':ua', $_SERVER['HTTP_USER_AGENT'], PDO::PARAM_STR);
		$stmt->bindParam(':timeout', $timeout, PDO::PARAM_INT);
		$stmt->bindParam(':data', isset($psession->data) ? json_encode($psession->data) : '', PDO::PARAM_STR);
		$stmt->bindParam(':username', trim($psession->username), PDO::PARAM_STR);
		$stmt->bindParam(':starttime', trim(), PDO::PARAM_INT);
		$stmt->execute();
	}

}