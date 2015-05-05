<?php

namespace abhidilliwal\psession;

use \Exception;

class PersistentSessionException extends Exception{

	function __construct($message = null, $code = null) {
		parent::__construct($message, $code);
	}
}

class NotValidSessionException extends PersistentSessionException{

	function __construct($message = null, $code = null) {
		parent::__construct($message, $code);
	}
}

?>