<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'renter') {
    header("Location: ../auth/login.php");
    exit();
}
header("Location: browse.php");
exit();
?>