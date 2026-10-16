<?php
// Resume existing session (or start a new one)
session_start();

// Destroy the session in case of using session-based authentication
session_unset();	// Unset all session variables
session_destroy();	// Destroy the session

//redirect to the login page
echo '<script>window.location.href = "login.php";</script>';
exit();

/*
if (session_status() !== PHP_SESSION_ACTIVE) :void
{
  session_start();
  session_unset();
  session_destroy();
  session_write_close();
  setcookie(session_name(), '', 0, '/');
  session_regenerate_id(true);
}
*/
?>
