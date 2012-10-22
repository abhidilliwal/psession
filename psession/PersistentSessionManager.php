<?php

class PersistentSessionManager {

	const COOKIE_NAME = "PS";
	const TIMEOUT = 2592000; // 30 days: 30 * 24 * 60 * 60

	/**
	 *
	 * @var PersistentSessionModel
	 */
	protected $model;

	/**
	 *
	 * @var PersistentSession
	 */
	protected $session = null;

	/**
	 * Was the session initialized?
	 * @var boolean
	 */
	private $sessionStarted = false;

	/**
	 *
	 * @param PDO $pdo
	 * @param boolean $initSession
	 * Should the session too start on object instantiation
	 */
	function __construct(PDO $pdo, $initSession = true) {
		$this->model = new PersistentSessionModel($pdo);

		if ($initSession === true) {
			// lets initialize the session
			$this->init();
		}
	}

	/**
	 * Initialize the session
	 * This is done just once!
	 */
	public function init () {

		if ($this->sessionStarted === false) {

			$clientKey = $this->readClientCookie();

			if (!empty($clientKey)) {
				$this->session = $this->model->getSession($clientKey);
				$this->setSessionStarted();
			}
				
		}
			
	}

	private function setSessionStarted () {
		$this->sessionStarted = true;
	}

	public function isActiveSession() {
		return isset($this->session);
	}

	public function getSession() {
		return $this->session;
	}

	/**
	 *
	 * @param String $username
	 * username can be string or integer (id) depeneding on how you identify a user.
	 * @param Array $data
	 * Associative array; this is the extra data which you want to keep in session.
	 * for example: data = array('group' => 'admin', 'fname' => 'Abhishek', 'age' => 25)
	 * @param boolean $remeber
	 * should the session persist even when the user closes the browser.
	 */
	public function startSession($username, $data = null, $remeber = false) {
		if (!$this->isActiveSession()) {
			$session = new PersistentSession();
			$session->username = $username;
			$session->data = $data;
			$session->clientKey = $this->generateKey();
			$this->writeClientCookie();
			$this->model->addSession($session);

		}
	}

	/**
	 * logout; end the session of the user
	 */
	public function endSession () {
		if ($this->readClientCookie()){
			// lookup the cookie in database

			$this->readSession();

		}
	}

	public function setData($data) {
		$this->data = json_encode($data);
	}

	public function getData() {
		return json_decode($this->data);
	}


	public function saveSession () {
		// save the session changed information
	}

	protected function generateKey() {
		return sha1($this->username + $_SERVER['REMOTE_ADDR'] + time() + rand(100000, 1000000));
	}

	/**
	 * get the cookie stored in users browser
	 */
	protected function readClientCookie() {
		$clientKey = isset($_COOKIE[PersistentSessionManager::COOKIE_NAME]) ? trim($_COOKIE[PersistentSessionManager::COOKIE_NAME]) : null;
		return $clientKey;
	}

	protected function writeClientCookie() {

	}

}

?>