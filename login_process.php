<?php
require_once 'config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        // Check if the user exists in the database
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Start a session and store user information
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Set cookies for login with enhanced security
            if (isset($_POST['stay_logged_in']) && $_POST['stay_logged_in'] === 'on') {
                // Set cookies for a long duration (e.g., 10 years) with enhanced security
                $long_expiry = time() + (10 * 365 * 24 * 60 * 60);
                setcookie('user_id', $user['id'], [
                    'expires' => $long_expiry,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                setcookie('username', $user['username'], [
                    'expires' => $long_expiry,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            } else {
                // Set cookies for 1 hour with enhanced security
                $short_expiry = time() + (60 * 60);
                setcookie('user_id', $user['id'], [
                    'expires' => $short_expiry,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                setcookie('username', $user['username'], [
                    'expires' => $short_expiry,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }

            // Redirect to the homepage
            header("Location: farm-website-homepage.php");
            exit;
        } else {
            echo "<script>alert('Invalid username or password.'); window.location.href='login.php';</script>";
        }
    } catch (PDOException $e) {
        die("ERROR: Could not execute query. " . $e->getMessage());
    }
}
?>