<?php
// Shared setup for every page: hardened session, security headers,
// CSRF helpers and the database connection.
// Must be included before any HTML output.

// Buffer output so header() redirects work wherever they appear in a page,
// regardless of the php.ini output_buffering setting
ob_start();

// Security headers (defence in depth against XSS and clickjacking)
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; frame-ancestors 'none'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: same-origin");

// Session cookie: not readable from JavaScript, not sent cross-site,
// and only sent over HTTPS when the site is served over HTTPS
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
	'httponly' => true,
	'samesite' => 'Strict',
]);
ini_set('session.use_strict_mode', '1');	// reject session IDs the server did not issue
ini_set('session.use_only_cookies', '1');
session_start();

// bcrypt only uses the first 72 bytes of a password, while the encryption key
// is derived from all of it; longer passwords would let two different
// passwords log in to the same account with different keys
const MAX_PASSWORD_BYTES = 72;

function is_logged_in() {
	return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && $_SESSION['username'] !== '';
}

// CSRF: one random token per session, checked on every state-changing POST
function csrf_token() {
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['csrf_token'];
}

function csrf_field() {
	return "<input type='hidden' name='csrf_token' value='" . htmlspecialchars(csrf_token()) . "'>";
}

function csrf_check() {
	if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !is_string($_POST['csrf_token'])
		|| !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		http_response_code(403);
		exit("Invalid or missing CSRF token.");
	}
}

// Database connection. Credentials come from the environment; the defaults
// match the course's XAMPP setup (root with no password) for local use only.
function db_connect() {
	mysqli_report(MYSQLI_REPORT_OFF);	// disable exceptions, errors are checked below
	$conn = mysqli_connect(
		getenv('DB_HOST') ?: 'localhost',
		getenv('DB_USER') ?: 'root',
		getenv('DB_PASS') ?: '',
		getenv('DB_NAME') ?: 'pwd_mgr',
		(int)(getenv('DB_PORT') ?: ini_get('mysqli.default_port') ?: 3306)
	);
	if (mysqli_connect_errno()) {
		error_log("MySQL connection failed: " . mysqli_connect_error());	// details go to the log, not the user
		http_response_code(500);
		exit("Database unavailable, please try again later.");
	}
	return $conn;
}
?>
