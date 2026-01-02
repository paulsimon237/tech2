<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';

$authController = new AuthController();
$authController->logout();

// Redirect to splash page
header('Location: splash.php');
exit;
?>
