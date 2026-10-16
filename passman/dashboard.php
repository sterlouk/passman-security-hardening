<?php require_once('bootstrap.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        table {
            border-collapse: collapse;
            width: 30%;
            border: 1px solid black;
        }
        td, tr {
            width: 50%;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>

<?php
include('test_encrypt.php');

// If not logged in redirect to login page
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Every POST on this page changes data, so it must carry the CSRF token
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	csrf_check();
}

// Connect to the database
$conn = db_connect();

// Check if 'Insert-new-website' button is selected
if(isset($_POST['new_website'], $_POST['new_username'], $_POST['new_password']) && 
   trim($_POST['new_website']) !='' && trim($_POST['new_username']) != '' && trim($_POST['new_password']) != '') {
	$new_website = trim($_POST["new_website"]);
	$new_username = trim($_POST["new_username"]);
	$new_password = trim($_POST["new_password"]);

	//password encryption using the derived session key from login.php
	$key = $_SESSION['encryption_key'];
	$encrypted_password = encryptData($new_password, $key);
	$new_password = $encrypted_password;

	// Insert new web site
	//$sql_query = "INSERT INTO websites (login_user_id,web_url,web_username,web_password) VALUES " .
	//			"((SELECT id FROM login_users WHERE username='{$username}'),'{$new_website}','{$new_username}','{$new_password}');";
	//echo $sql_query;
	//$result = $conn->query($sql_query);

	$sql_query =  "INSERT INTO websites (login_user_id,web_url,web_username,web_password) VALUES " .
				"((SELECT id FROM login_users WHERE username=?),?,?,?);";
	$statement = mysqli_prepare($conn, $sql_query);
	mysqli_stmt_bind_param($statement, 'ssss', $username, $new_website, $new_username, $new_password);
	mysqli_stmt_execute($statement);
	//$result = mysqli_stmt_get_result($statement);
	mysqli_stmt_close($statement);
	$conn -> close();

	// After processing, redirect to the same page to clear the form
	unset($_POST['new_website']);
	unset($_POST['new_username']);
	unset($_POST['new_password']);
	header("Location: " . $_SERVER['PHP_SELF']);
	exit();
}

// Check if 'Delete-website' button was selected
if(isset($_POST['delete_website']) && trim($_POST["websiteid"] != '')) {
	$webid = trim($_POST["websiteid"]);

	// Delete selected web site
	//$sql_query = "DELETE FROM websites WHERE webid='{$webid}';";
	//echo $sql_query;
	//$result = $conn->query($sql_query);

	// Only delete the entry if it belongs to the logged-in user (prevents IDOR:
	// without this check any user could delete anyone's entry by changing websiteid)
	$sql_query = "DELETE FROM websites WHERE webid=? AND login_user_id=(SELECT id FROM login_users WHERE username=?);";
	$statement = mysqli_prepare($conn, $sql_query);
	mysqli_stmt_bind_param($statement, 'ss', $webid, $username);
	mysqli_stmt_execute($statement);
	//$result = mysqli_stmt_get_result($statement);
	mysqli_stmt_close($statement);
	$conn -> close();

	// After processing, redirect to the same page to clear the form
	unset($_POST['websiteid']);
	header("Location: " . $_SERVER['PHP_SELF']);
	exit();
}

// Display list of user's web sites
//$sql_query = "SELECT * FROM websites INNER JOIN login_users ON websites.login_user_id=login_users.id WHERE login_users.username='{$username}';";
//echo $sql_query;
//$result = $conn->query($sql_query);
//echo htmlspecialchars($username);
$sql_query = "SELECT * FROM websites INNER JOIN login_users ON websites.login_user_id=login_users.id WHERE login_users.username=?;";
$statement = mysqli_prepare($conn, $sql_query);
mysqli_stmt_bind_param($statement, 's', $username);
mysqli_stmt_execute($statement);
$result = mysqli_stmt_get_result($statement);
mysqli_stmt_close($statement);

echo "<h3>Entries of " . htmlspecialchars($username) . "</h3>";

if (!empty($result) && $result->num_rows >= 1) {
	while ($row = $result -> fetch_assoc()) {
		//decrypt the password before displaying
		$key = $_SESSION['encryption_key'];
		$row["web_password"] = decryptData($row["web_password"], $key);
		echo "<table border=0>";
		echo	"<tr style='background-color: #f4f4f4;'><td colspan=2>" . htmlspecialchars($row["web_url"]) . "</td></tr>" . 
				"<tr><td>Username: " . htmlspecialchars($row["web_username"]) . "</td><td>Password: " . htmlspecialchars($row["web_password"]) . "</td></tr>";

		echo	"<tr><td><form method='POST' style='height: 3px'>" . csrf_field() .
				"<input type='hidden' name='websiteid' value='" . htmlspecialchars($row["webid"]) . "'>" .
				"<button type='submit' name='delete_website'>Delete</button></form></td></tr>";

		echo	"<tr><td colspan=2 style=height: 20px;></td></tr>";
		echo "</table><p/>";
	}

	// Free result set
	$result -> free_result();
} else {
	echo "<p><font color=red>No entries found.</font></p>";
}

$conn -> close();

?>

<body>
	<p/>
	<form method="POST" action="dashboard.php">
		<?php echo csrf_field(); ?>
        <input type="text" name="new_website" placeholder="website"><br />
        <input type="text" name="new_username" placeholder="Username"><br />
        <input type="password" name="new_password" placeholder="Password"><br />
        <button type="submit">Insert new website</button>
    </form>
	<p/>
    <a href="notes.php">Notes - announcements</a>
	<p/>
    <a href="logout.php">Logout</a>
	<p/>
	<a href="index.html">Home page</a>
</body>
</html>