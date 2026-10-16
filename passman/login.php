<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
</head>

<?php
include('test_hash.php');
include('test_encrypt.php');

// Start a new session (or resume an existing one)
session_start();

// Check if the user is already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && $_SESSION['username'] !== '') {
    // Redirect to the dashboard page
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	if(!isset($_POST['username'], $_POST['password']) || trim($_POST['username']) =='' || trim($_POST['password']) == '') {
		$login_message = "Missing username or password.";
	}
	else {
		
		// Get user submitted information
		$username = trim($_POST['username']);
		$password = trim($_POST['password']);
		//key derivation to encrypt/decrypt data in dashboard.php
		$key = $_SESSION['encryption_key'] = deriveEncryptionKey($username, $password);
		$getHasedPwd = getPasswordHash_Hex($username, $password);
		$password = $getHasedPwd['hash'];
		// Connect to the database
		$conn=mysqli_connect("localhost","root","","pwd_mgr");
		// Check connection
		if (mysqli_connect_errno())	{
		  echo "Failed to connect to MySQL: " . mysqli_connect_error();
		  exit();
		}

		// xxx' OR 1=1; -- '
		//$sql_query = "SELECT * FROM login_users WHERE username='{$username}' AND password='{$password}';";
		//echo $sql_query;
		// Check if the credentials are valid
		//$result = $conn->query($sql_query);

		$sql_query = "SELECT * FROM login_users WHERE username= ? AND password= ? ;";
		$statement = mysqli_prepare($conn, $sql_query);
		mysqli_stmt_bind_param($statement, "ss", $username, $password);
		mysqli_stmt_execute($statement);
		$result = mysqli_stmt_get_result($statement);
		mysqli_stmt_close($statement);


		
		unset($_POST['username']);
		unset($_POST['password']);

		if (!empty($result) && $result->num_rows >= 1) {
			// Regenerate session ID to prevent session fixation!
			//session_regenerate_id(true);

			// Successfully logged in
			$_SESSION['username'] = $username;
			$_SESSION['loggedin'] = true;

			//while ($row = $result -> fetch_assoc()) {
			//	print_r($row);
			//	$_SESSION['user_id'] = $row['id'];
			//}

			// Free result set
			$result -> free_result();
			$conn -> close();

			// Redirect to a dashboard page
			header("Location: dashboard.php");
			exit;
		} else {
			$login_message = "Invalid username or password";
		}

		$conn -> close();
	}
}
?>

<body>
    <h3>Password Manager</h3>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" required><br />
        <input type="password" name="password" placeholder="Password"><br />
        <button type="submit">Login</button>
    </form>
	<br />
    <?php if (!empty($login_message)) { echo "<font color=red>$login_message</font>"; } ?>
	<p/>
    <a href="register.php">Register new user</a>
	<p/>
	<a href="index.html">Home page</a>
</body>
</html>