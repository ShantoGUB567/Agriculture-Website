<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Include DB config only once
if (!isset($pdo)) {
    require_once '../config/db_config.php';
}

// Fetch user details from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT username, email, phone_number, address FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();
$stmt->closeCursor();

// Ensure the $user array is initialized with default values before accessing its keys
if (!$user) {
    $user = [
        'username' => '',
        'email' => '',
        'phone_number' => '',
        'address' => ''
    ];
}

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['email'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Basic validation
    if (empty($username) || empty($email)) {
        $error_message = 'Username and Email are required.';
    } else {
        try {
            // Update user info
            $update_query = "UPDATE users 
                             SET username = :username, email = :email, phone_number = :phone_number, address = :address 
                             WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $update_stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $update_stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
            $update_stmt->bindParam(':address', $address, PDO::PARAM_STR);
            $update_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

            if ($update_stmt->execute()) {
                $_SESSION['success_message'] = 'Profile updated successfully.';
                header('Location: user-profile.php');
                exit();
            } else {
                $error_message = 'Failed to update profile. Please try again.';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $error_message = 'The username or email is already taken.';
            } else {
                $error_message = 'An error occurred: ' . $e->getMessage();
            }
        }
    }
}

// Add functionality to handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate new password and confirmation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New password and confirmation do not match.';
    } else {
        try {
            // Verify current password
            $password_query = "SELECT password FROM users WHERE id = :id";
            $password_stmt = $pdo->prepare($password_query);
            $password_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $password_stmt->execute();
            $stored_password = $password_stmt->fetchColumn();

            if (!password_verify($current_password, $stored_password)) {
                $error_message = 'Current password is incorrect.';
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password_query = "UPDATE users SET password = :password WHERE id = :id";
                $update_password_stmt = $pdo->prepare($update_password_query);
                $update_password_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $update_password_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

                if ($update_password_stmt->execute()) {
                    $_SESSION['success_message'] = 'Password changed successfully.';
                    header('Location: user-profile.php');
                    exit();
                } else {
                    $error_message = 'Failed to change password. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'An error occurred: ' . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - FarmKnowledge</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        main {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .profile-update, .change-password {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        h2 {
            color: #2e7d32;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
        }

        form {
            display: grid;
            gap: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }

        input, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        button {
            background: #2e7d32;
            color: white;
            padding: 0.8rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        button:hover {
            background: #1b5e20;
            transform: translateY(-1px);
        }

        p[style*="color: green"] {
            background: #e8f5e9;
            color: #2e7d32 !important;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        p[style*="color: red"] {
            background: #ffebee;
            color: #c62828 !important;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .password-input-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .back-to-profile {
            display: inline-block;
            text-decoration: none;
            color: #2e7d32;
            margin-bottom: 1rem;
        }

        .back-to-profile i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            main {
                margin: 1rem auto;
            }

            .profile-update, .change-password {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <main>
        <a href="user-profile.php" class="back-to-profile">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
        
        <section class="profile-update">
            <h2>Update Your Profile</h2>

            <!-- Success Message -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <p style="color: green;"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (isset($error_message)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>

            <!-- Profile Update Form -->
            <form action="" method="POST">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

                <label for="phone_number">Phone Number:</label>
                <input type="text" id="phone_number" name="phone_number" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($user['phone_number']); ?>">

                <label for="address">Address:</label>
                <textarea id="address" name="address" rows="4" placeholder="Enter your address"><?php echo htmlspecialchars($user['address']); ?></textarea>

                <button type="submit">Save Changes</button>
            </form>
        </section>

        <section class="change-password">
            <h2>Change Password</h2>
            <form action="" method="POST">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required>

                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>

                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <button type="submit">Change Password</button>
            </form>
        </section>
    </main>

    <!-- Add client-side validation using JavaScript -->
    <script>
        document.querySelector('form').addEventListener('submit', function(event) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            if (!username || !email) {
                event.preventDefault();
                alert('Username and Email are required.');
            }
        });
    </script>
</body>
</html>
