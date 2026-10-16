<?php
// Simple example of hashing password
/*** 
$username = "user123";
$password = "securepassword";

// Compute salt as the hash of the username
$salt = hash('sha256', $username);

// Get a salted password by combining salt and password
$saltedPwd = $salt . $password;

// Hash the salted password using SHA-256
$hashedPwd = hash('sha256', $saltedPwd);

// Display variables
echo "Username: $username<br>";
echo "Password: $password<br>";

echo "Salt (computed as the username's hash): $salt<br>";
echo "Salted password: $saltedPwd<br>";
echo "Hash of salted password: $hashedPwd<br>";
echo "<p>";
***/

// Same as above but using a function
function getPasswordHash_Hex($username, $password) {
    // Compute hash of salted-password (and salt) from username and password (in hex format)
    $salt = hash('sha256', $username);	// Compute salt as the hash of the username
    $saltedPwd = $salt . $password;		// Get a salted password by combining salt and password
    $hashedPwd = hash('sha256', $saltedPwd);	// Hash the salted password using SHA-256
    // Return the password hash and the salt
    return [
        'hash' => $hashedPwd,
        'salt' => $salt
    ];
}
/***
// Example usage of function getPasswordHash
$getHasedPwd = getPasswordHash_Hex($username, $password);
// Display results
echo "Salt (in hex) computed using function getPasswordHash_Hex: " . $getHasedPwd['salt'] . "<br>";
echo "Hash (in hex) computed using function getPasswordHash_Hex: " . $getHasedPwd['hash'] . "<br>";
***/
?>
