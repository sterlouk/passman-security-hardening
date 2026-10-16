<?php require_once('bootstrap.php'); ?>
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

// Check if the user is already logged in
if (is_logged_in()) {
    // Redirect to the dashboard page
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	csrf_check();
	if(!isset($_POST['username'], $_POST['password']) || trim($_POST['username']) =='' || trim($_POST['password']) == '') {
		$login_message = "Missing username or password.";
	}
	else {
		
		// Get user submitted information
		$username = trim($_POST['username']);
		$password = trim($_POST['password']);
		// Connect to the database
		$conn = db_connect();

		// xxx' OR 1=1; -- '
		//$sql_query = "SELECT * FROM login_users WHERE username='{$username}' AND password='{$password}';";
		//echo $sql_query;
		// Check if the credentials are valid
		//$result = $conn->query($sql_query);

		// Look the user up by name only; the password is checked in PHP
		$sql_query = "SELECT id, username, password FROM login_users WHERE username= ? ;";
		$statement = mysqli_prepare($conn, $sql_query);
		mysqli_stmt_bind_param($statement, "s", $username);
		mysqli_stmt_execute($statement);
		$result = mysqli_stmt_get_result($statement);
		$row = $result ? $result->fetch_assoc() : null;
		mysqli_stmt_close($statement);

		$valid = false;
		if ($row && strlen($password) <= MAX_PASSWORD_BYTES) {
			// The lookup is case-insensitive, so continue with the spelling stored at
			// registration: the encryption key depends on the exact username
			$username = $row['username'];
			if (password_verify($password, $row['password'])) {
				$valid = true;
			} else {
				// Accounts created before the bcrypt migration store the
				// old SHA-256 hash: accept it once and upgrade it in place
				$legacy = getPasswordHash_Hex($username, $password);
				if (hash_equals($row['password'], $legacy['hash'])) {
					$valid = true;
					$new_hash = password_hash($password, PASSWORD_DEFAULT);
					$update = mysqli_prepare($conn, "UPDATE login_users SET password=? WHERE id=?;");
					mysqli_stmt_bind_param($update, "si", $new_hash, $row['id']);
					mysqli_stmt_execute($update);
					mysqli_stmt_close($update);
				}
			}
		}

		unset($_POST['username']);
		unset($_POST['password']);

		if ($valid) {
			// Regenerate session ID to prevent session fixation!
			if (!session_regenerate_id(true)) {
				error_log("session_regenerate_id failed during login");
				http_response_code(500);
				exit("Login failed, please try again.");
			}

			// Successfully logged in
			$_SESSION['username'] = $username;
			$_SESSION['loggedin'] = true;
			// Key derivation to encrypt/decrypt data in dashboard.php (only after a successful login)
			$_SESSION['encryption_key'] = deriveEncryptionKey($username, $password);

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
        <?php echo csrf_field(); ?>
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