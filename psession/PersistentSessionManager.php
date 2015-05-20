<?php

namespace abhidilliwal\psession;

use \PDO;


class PersistentSessionManager {

    protected $config = array (
            "name" => "PS",
            "secure" => false, // for https make it true
            "path" => "/", // current path by default
            "domain" => "", // default is the current domain
            "timeout" => 2592000, // 30 days: 30 * 24 * 60 * 60
            "timeout_session" => 10800
    ) // 3 hrs: 3 * 60 * 60
;

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
     *
     * @var boolean
     */
    private $sessionStarted = false;

    /**
     *
     * @param PDO $pdo
     * @param boolean $initSession
     *            Should the session too start on object instantiation
     */
    function __construct(PDO $pdo, $config = null, $initSession = true) {
        $this->model = new PersistentSessionModel ( $pdo );

        $this->setConfig ( $config );

        if ($initSession === true) {
            // lets initialize the session
            $this->init ();
        }
    }

    public function setConfig($config) {
        if (empty ( $config )) {
            return;
        }
        foreach ( $config as $configName => $configVal ) {
            if (isset ( $this->config [$configName] )) {
                $this->configp [$configName] = $configVal;
            }
        }
    }

    /**
     * Initialize the session
     * This is done just once!
     */
    public function init() {

        // ensure that init has not been called already
        if ($this->sessionStarted === false) {

            $clientKey = $this->readClientCookie ();
            if (! empty ( $clientKey )) {
                try {
                    $this->session = $this->model->getSession ( $clientKey );
                } catch ( NotValidSessionException $nvs ) {
                    // nothing to do, there was no session!
                }
            }

            $this->setSessionStarted ();
        }
    }

    /**
     * Flag to check if the init has been called?
     */
    private function setSessionStarted() {
        $this->sessionStarted = true;
    }

    /**
     * Know if the session is set or not.
     *
     * @return boolean A false means that no session was set or the session does not exist
     *         a true means you can access the session via PersistentSessionManager::getSession()
     * @throws PersistentSessionException
     */
    public function isActiveSession() {
        if ($this->sessionStarted === false) {
            // we should ensure that init has been called.
            throw new PersistentSessionException ( "Please call the init before invoking isActiveSession" );
        }
        return isset ( $this->session );
    }

    /**
     * Get the current session set.
     * To query if the session is set use {@link PersistentSessionManager::isActiveSession()}
     *
     * @return PersistentSession default persistent session bean
     */
    public function getSession() {
        return $this->session;
    }

    public function getSessionUsername() {
        return $this->session->username;
    }

    public function getSessionData() {
        return $this->session->data;
    }

    /**
     *
     * @param String $username
     *            username can be string or integer (id) depeneding on how you identify a user.
     * @param Array $data
     *            Associative array; this is the extra data which you want to keep in session.
     *            for example: data = array('group' => 'admin', 'fname' => 'Abhishek', 'age' => 25)
     * @param boolean $remeber
     *            should the session persist even when the user closes the browser.
     * @throws PersistentSessionException
     * @throws PDOException
     */
    public function startSession($username, $data = null, $remember = false) {
        if ($this->sessionStarted === false) {
            // we should ensure that init has been called.
            throw new PersistentSessionException ( "Please call the init before invoking startSession" );
        } else if (! $this->isActiveSession ()) {
            // there was no active session lets create a new session
            $session = new PersistentSession ();

            // set all the variables
            $session->username = $username;
            $session->data = $data;
            $session->clientKey = $this->generateKey ( $session );
            $session->ua = $_SERVER ['HTTP_USER_AGENT'];
            $session->starttime = time ();
            if ($remember === false) {
                // we need to make a session cookie
                $session->timeout = time () + $this->config ['timeout_session'];
            } else {
                // we need to make a persistent cookie
                $session->timeout = time () + $this->config ['timeout'];
            }
            // add to database
            $this->model->addSession ( $session );

            // added to database now its time for cookie!
            $this->writeClientCookie ( $session->clientKey, $remember === false ? 0 : $session->timeout );

            $this->session = $session;
        }
    }

    /**
     * logout; end the session of the user
     */
    public function endSession() {
        if ($this->sessionStarted === false) {
            // we should ensure that init has been called.
            throw new PersistentSessionException ( "Please call the init before invoking startSession" );
        } else if ($this->isActiveSession ()) {

            // there is a active session available
            // delete the session from database
            $this->model->deleteSession ( $this->session );

            // deleted from database now its time for cookie!
            $this->writeClientCookie ( $this->session->clientKey, (time () - 3600) );

            // and unset the object itself
            $this->session = null;

        }
    }

    /**
     *
     * @param PersistentSession $session
     */
    protected function generateKey($session) {
        return sha1 ( $session->username + $_SERVER ['REMOTE_ADDR'] + time () + rand ( 100000, 1000000 ) );
    }

    /**
     * get the cookie stored in users browser
     */
    protected function readClientCookie() {
        $clientKey = isset ( $_COOKIE [$this->config ['name']] ) ? trim ( $_COOKIE [$this->config ['name']] ) : null;
        return $clientKey;
    }

    /**
     *
     * @param PersistentSession $psession
     */
    protected function writeClientCookie($key, $timeout) {
        // we want this to be transferred via HTTP only (the last true)
        setcookie ( $this->config ['name'], $key, $timeout, $this->config ['path'], $this->config ['domain'], $this->config ['secure'], true );
    }

}

?>