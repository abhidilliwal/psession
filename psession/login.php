<?php

	require_once 'PersistentSessionExceptions.php';
	require_once 'PersistentSession.php';
	require_once 'PersistentSessionModel.php';
	require_once 'PersistentSessionManager.php';

	$pdo = new PDO('mysql:dbname=psession;host=127.0.0.1', 'psession', 'psession');

	$session = new PersistentSessionManager($pdo);
	
	if (isset($_POST['username']) && strlen(trim($_POST['username']))) {
		$username = trim($_POST['username']);
		$remember = isset($_POST['remember_me']) ? true : false;
		
	}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="ISO-8859-1">
<title>Insert title here</title>
</head>
<body>
<form action="login.php" method="get">
	username: <input type="text" name="username" /> <br />
	<label><input type="checkbox" name="remember_me" /> Remember me</label><br />
	<input type="submit" value="submit" />
</form>
<div>
	<a href="logout.php">Logout</a>
</div>
</body>
</html>