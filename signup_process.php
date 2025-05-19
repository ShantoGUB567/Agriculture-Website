<?php
require_once 'config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate passwords match
    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Insert user into the database
        $query = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->execute();

        // Show a success message and redirect to login page
        echo "<script>alert('Registration complete!'); window.location.href='login.php';</script>";
        exit;
    } catch (PDOException $e) {
        die("ERROR: Could not execute query. " . $e->getMessage());
    }
}
?>