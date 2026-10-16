<?php require_once('bootstrap.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
</head>
<body>
    <h3>New user registration</h3>

<?php
// Check if the user is already logged in
if (is_logged_in()) {
	echo "<font color=red>You are already logged in!</font></br>";
	echo "Please <a href='logout.php'>logout</a> first";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	csrf_check();
	if(!isset($_POST['new_username'], $_POST['new_password']) || trim($_POST['new_username']) =='' || trim($_POST['new_password']) == '') {
		$login_message = "Missing username or password.";
	}
	else {
		// Get user submitted information
		$new_username = trim($_POST['new_username']);
		$new_password = trim($_POST['new_password']);
		// Slow, randomly salted hash (bcrypt) instead of a single SHA-256 round
		$new_password = password_hash($new_password, PASSWORD_DEFAULT);

		// Connect to the database
		$conn = db_connect();

		// Insert a new user
		//comment out this line and make prepared statement
		//$sql_query = "INSERT INTO login_users (username,password) VALUES ('{$new_username}','{$new_password}');";
		//echo $sql_query;
		//$result = $conn->query($sql_query);

		$sql_query = "INSERT INTO login_users (username,password) VALUES (?,?);";
		$statement = mysqli_prepare($conn, $sql_query);
		mysqli_stmt_bind_param($statement, "ss", $new_username, $new_password);
		$result = mysqli_stmt_execute($statement);
		mysqli_stmt_close($statement);


		unset($_POST['new_username']);
		unset($_POST['new_password']);

		if ($result) {
			echo "<font color=red>Successful registration!</font>";
			echo "<p />You can now use the <a href='login.php'>login</a> page";
			exit;
		}
		else 
			$login_message = "Error, probably user already exists!";

		// Free result set
		$conn -> close();

	}
}
?>


<body>
	<p/>
	<form method="POST" action="register.php">
		<?php echo csrf_field(); ?>
        <input type="text" name="new_username" placeholder="Username"><br />
        <input type="password" name="new_password" placeholder="Password"><br />
        <button type="submit">Register</button>
    </form>

	<br />

    <?php
		if (!empty($login_message)) { 
			echo "<font color=red>$login_message</font>";
			echo "<p />Go to the <a href='login.php'>login</a> page";
		}
	?>

</body>
</html>