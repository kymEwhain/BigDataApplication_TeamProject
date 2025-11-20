<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header("Location: components/login.php");
  exit;
}
else {
    header("Location: components/main.php");
    exit;
}
?>