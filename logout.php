<?php
session_start();

// Destroy the session
session_unset();
session_destroy();

// Remove all cookies
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
    }
}

// Redirect to the login page
header("Location: login.php");
exit;