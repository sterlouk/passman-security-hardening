<?php
// Simple example of encrypting/decrypting data using a password

function getPasswordHash_Bin($username, $password) {
    $salt = hash('sha256', $username, true);	// Compute salt as the hash of the username (parameter 'true' computes hash in bin format, default is hex)
    $saltedPwd = $salt . $password;				// Get a salted password by combining salt and password
    $hashedPwd = hash('sha256', $saltedPwd, true);	// Hash the salted password using SHA-256
    // Return the password hash and the salt
    return [
        'hash' => $hashedPwd,
        'salt' => $salt
    ];
}

function deriveEncryptionKey($username, $password) {
    // Compute binary hash of salted-password (and salt) from username and password
    $pwdHash = getPasswordHash_Bin($username, $password);

    // Derive a secure key using PBKDF2
    $iterations = 100000; // Number of iterations for PBKDF2
    $keyLength = 32; // Key length = 32 bytes for AES-256
    $key = hash_pbkdf2('sha256', $pwdHash['hash'], $pwdHash['salt'], $iterations, $keyLength, true); // Parameter 'true' computes hash_pbkdf2 in bin
    return $key;
}

// Encrypt data using AES-256-GCM
function encryptData($data, $key) {
    $nonce = random_bytes(12); // 12 bytes for AES-GCM nonce
    $cipher = "aes-256-gcm";

    // Encrypt the data
    $ciphertext = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $nonce, $tag);

    //echo "nonce: " . bin2hex($nonce) . "<br>";;
    //echo "tag: " . bin2hex($tag) . "<br>";;

    // Concatenate nonce, tag, and ciphertext for storage
    $result = $nonce . $tag . $ciphertext;
    return base64_encode($result); // Encode to make it suitable for storage or transmission
}

// Decrypt data using AES-256-GCM, extracting nonce, tag, and ciphertext from the concatenated string
function decryptData($encryptedData, $key) {
    $cipher = "aes-256-gcm";

    // Decode the base64-encoded data
    $encryptedData = base64_decode($encryptedData);

    // Extract nonce (12 bytes), tag (16 bytes), and ciphertext
    $nonce = substr($encryptedData, 0, 12);
    $tag = substr($encryptedData, 12, 16);
    $ciphertext = substr($encryptedData, 28);

    // Decrypt the data
    $decryptedData = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $nonce, $tag);

    return $decryptedData;
}

/***
// Example Usage
$username = "user123";
$password = "securepassword";
$dataToEncrypt = "Sensitive Data";

// Derive a symmetric encryption/dec key by hashing the password (and username as the salt) using PBKDF2 algorithm
$encryptionKey = deriveEncryptionKey($username, $password);

// Encrypt the data
$encrypted = encryptData($dataToEncrypt, $encryptionKey);

// Decrypt the data
$decrypted = decryptData($encrypted, $encryptionKey);

// Display results
echo "Original Data: $dataToEncrypt<br>";
//echo "Encryption Key (in bin): " . $encryptionKey . "<br>";
//echo "Encryption Key (in hex): " . bin2hex($encryptionKey) . "<br>";
echo "Encrypted Data (in base64): " . $encrypted . "<br>";
//echo "Encrypted Data (in bin): " . base64_decode($encrypted) . "<br>";
//echo "Encrypted Data (in hex): " . bin2hex(base64_decode($encrypted)) . "<br>";
echo "Decrypted Data: $decrypted<br>";
***/
?>
