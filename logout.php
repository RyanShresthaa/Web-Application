<?php
session_start();
require_once 'includes/functions.php';

// Destroy user session in database
if (isset($_SESSION['user_id'])) {
    destroyUserSession();
}

// Destroy PHP session
session_destroy();

// Clear all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header("Location: index.php?message=logged_out");
exit();
?> 