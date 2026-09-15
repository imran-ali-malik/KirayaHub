<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'owner') {
        header("Location: owner/dashboard.php");
    } else {
        header("Location: renter/browse.php");
    }
    exit();
}
header("Location: auth/login.php");
exit();
?>