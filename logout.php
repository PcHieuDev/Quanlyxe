<?php
session_start();

// Logout
session_destroy();
header('Location: login.php');
exit;
?>
