<?php

	// These are required files in order.
	require_once 'PersistentSessionExceptions.php';
	require_once 'PersistentSession.php';
	require_once 'PersistentSessionModel.php';
	require_once 'PersistentSessionManager.php';
	
	// you can do this in your own way what just session manager needs is PDO object
	$pdo = new PDO('mysql:dbname=psession;host=127.0.0.1', 'psession', 'psession');
	
	// this line should be the initial line of your code. Just like session_start of PHP
	// if you want to initialize the session at later stage pass a second argument as false in constructor, see the source for details
	$session = new PersistentSessionManager($pdo);
	
	if (isset($_GET['logout'])) {
		// logout link was clicked
		$session->endSession(); // and this is how you end the session ;)
		header('Location: login.php');
	}
	
	if ($session->isActiveSession()) {
		
		// logged in
		echo "<div>Logged in as <b>", $session->getSessionUsername(), "</b></div>";
		echo "<div>Some Data <br /><pre>", print_r($session->getSessionData(), true), "</pre></div>";
		
		// well this should not concern you, showing for demo
		echo "<div>timeout <b>", date(DateTime::RSS, $session->getSession()->timeout), "</b></div>";
		
		echo '<div><a href="login.php?logout=true">Logout</a></div>';
		exit;
	}else {
		// not logged in	
		if (isset($_POST['username']) && strlen(trim($_POST['username']))) {
			$username = trim($_POST['username']);
			$remember = isset($_POST['remember_me']) ? true : false;
			
			// say you validated everything and now you want to start the session...
			$session->startSession($username, array('name'=>'Abhishek Dilliwal', 'age' => 25), $remember);
			
			// from now on session has started, though you can use $session->isActiveSession() am redirecting just for setting the cookie.
			header('Location: login.php');
		}
	
	}



?>

<!DOCTYPE html>
<html>
<head>
<meta charset="ISO-8859-1">
<title>Insert title here</title>
</head>
<body>
	<form action="login.php" method="post">
		username: <input type="text" name="username" /> <br /> <label><input
			type="checkbox" name="remember_me" /> Remember me (for 30 days!)</label><br /> <input
			type="submit" value="submit" />
	</form>
</body>
</html>
