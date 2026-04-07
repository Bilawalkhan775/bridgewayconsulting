<?php
session_start();

// If admin is logged in → go to dashboard
if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

// If not logged in → go to login
header("Location: login.php");
exit;