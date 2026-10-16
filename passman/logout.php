<?php
require_once('bootstrap.php');

// Destroy the session in case of using session-based authentication
$_SESSION = [];		// Unset all session variables (including the encryption key)
$params = session_get_cookie_params();
setcookie(session_name(), '', [
	'expires' => time() - 3600,
	'path' => $params['path'],
	'secure' => $params['secure'],
	'httponly' => $params['httponly'],
	'samesite' => $params['samesite'],
]);			// Remove the session cookie from the browser
session_destroy();	// Destroy the session

//redirect to the login page
header("Location: login.php");
exit();
?>
