<?php
require_once 'config/session.php';

// Destroy session and redirect to login
logout();
header('Location: login.php');
exit();
