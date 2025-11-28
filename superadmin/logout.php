<?php
// superadmin/logout.php
session_start();

// Log logout action before destroying session
if (isset($_SESSION['superadmin_id'])) {
    // You can log the logout action here if needed
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: ../superadmin_login.php?success=logout');
exit();
?>