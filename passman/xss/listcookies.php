<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List stolen cookies</title>
</head>

<body>
    <h3>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;List of 'stolen' cookies</h3>

<?php
// Define the path to the cookie file
$cookie_file = 'stolencookies.txt';  // Change this to the path of your cookie file

// Check if the file exists
if (file_exists($cookie_file)) {
    // Read the contents of the cookie file
    $cookie_data = file($cookie_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

/*
	$expires = new DateTime('now', new DateTimeZone('UTC'));  // Current UTC date and time
	$expires->modify('+2 minutes');  // Add 2 minutes
	$expiration = $expires->format('D, d M Y H:i:s') . ' GMT';
*/
    // Process each line
    echo "<ol>";

	$cookie_name = "PHPSESSID=";
    foreach ($cookie_data as $line) {
        // Split the cookies in each line by semicolon
        $cookies = explode(';', $line);
        
        // List each cookie separately
        foreach ($cookies as $cookie) {
			$cookie = trim($cookie);
			// Check for PHPSESSID=... cookie
			if (strpos($cookie, $cookie_name) === 0) {
				// Get PHPSESSID cookie value
				$cookie = str_replace("PHPSESSID=", "", trim($cookie));
				echo "<li>";
				echo "<a href='https://localhost/passman/xss/usecookie.php?v=" . $cookie . "'>";
				echo "PHPSESSID=" . htmlspecialchars($cookie) . "</a>";
				echo "</li>";
			} else {
				// Cookie does not contain PHPSESSID value
				echo "<li>";
				echo " Skipping cookie: " . htmlspecialchars($cookie);
				echo "</li>";
			}
        }
    }

    echo "</ol>";
} else {
    // Error message if file does not exist
    echo "<p>Cookie file not found.</p>";
}
?>

</body>
</html>