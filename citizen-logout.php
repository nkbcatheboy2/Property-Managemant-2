<?php
session_start();
unset($_SESSION['citizen_id'], $_SESSION['citizen_phone'], $_SESSION['citizen_name']);
header('Location: index.php');
exit;
