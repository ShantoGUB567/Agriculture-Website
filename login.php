<?php
session_start();

// Include translation functionality
require_once 'config/translate_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FarmKnowledge</title>
    <link rel="stylesheet" href="styles/login.css">
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
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        header {
            background: white;
            padding: 1rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-left {
            flex: 0 0 20%;
        }

        .nav-middle {
            flex: 0 0 60%;
            display: flex;
            justify-content: center;
        }

        .nav-right {
            flex: 0 0 20%;
            display: flex;
            justify-content: flex-end;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2e7d32;
            text-decoration: none;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 1rem;
        }

        .nav-links li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links li a:hover {
            color: #2e7d32;
        }


        .hero {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 60vh; /* Reduced height for a more compact design */
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)), url('Image/login.jpg');
            background-size: cover;
            background-position: center;
            color: white;
        }

        .hero-content {
            max-width: 400px; /* Reduced the width for a smaller container */
            margin: 0 auto; /* Centered the container horizontally */
            background: rgba(255, 255, 255, 0.8);
            padding: 1.5rem; /* Adjusted padding for compact design */
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .hero-content h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #2e7d32;
        }

        .hero-content form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .hero-content input {
            padding: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        .hero-content button {
            padding: 0.8rem;
            background-color: #2e7d32;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }        .hero-content button:hover {
            background-color: #1b5e20;
        }
        
        /* Footer Styles */
        footer {
            background-color: #f8f9fa;
            color: #333;
            padding: 3rem 0;
            margin-top: 2rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        
        .footer-section h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .footer-section ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-section a:hover {
            color: #2e7d32;
        }
        
        .footer-section p {
            color: #666;
            line-height: 1.6;
        }
        
        .footer-section i {
            margin-right: 0.5rem;
            color: #2e7d32;
        }
        
        .copyright {
            margin-top: 2rem;
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            color: #666;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="nav-left">
                    <a href="farm-website-homepage.php" class="logo">FarmKnowledge</a>
                </div>
                <div class="nav-middle">
                    <ul class="nav-links">
                        <li><a href="farm-website-homepage.php">Home</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="learning-center/index.php">Learning Center</a></li>
                        <li><a href="marketplace/marketplace.php">Marketplace</a></li>
                        <li><a href="#">Community</a></li>
                        <li><a href="#">News</a></li>
                    </ul>
                </div>
                <div class="nav-right">
                    <!-- Language Selector -->
                    <?php echo get_language_selector(); ?>
                </div>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Login to Your Account</h1>
                <form action="login_process.php" method="POST">
                    <input type="text" name="username" placeholder="Username" required>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" placeholder="Password" required style="width: 100%; padding-right: 40px;">
                        <span id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                            <img src="Image/eye-icon.png" alt="" style="width: 20px; height: 20px;">
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <input type="checkbox" name="stay_logged_in" id="stay_logged_in">
                            <label for="stay_logged_in">Stay logged in</label>
                        </div>
                        <div>
                            <a href="forgot_password.php" class="forgot-password-link">Forgot Password?</a>
                        </div>
                    </div>
                    <button type="submit" class="cta-button">Login</button>
                </form>
                <br>
                <p>Don't have an account? <a href="signup.php" class="signin-link">Sign Up</a></p>
            </div>
        </div>    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>FarmKnowledge</h3>
                    <p>Your complete resource for agricultural knowledge and marketplace.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="farm-website-homepage.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="learning-center/index.php">Learning Center</a></li>
                        <li><a href="marketplace/marketplace.php">Marketplace</a></li>
                        <li><a href="#">Community</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Categories</h3>
                    <ul>
                        <li><a href="learning-center/category.php?slug=organic-farming">Organic Farming</a></li>
                        <li><a href="learning-center/category.php?slug=hydroponics">Hydroponics</a></li>
                        <li><a href="learning-center/category.php?slug=soil-management">Soil Management</a></li>
                        <li><a href="learning-center/category.php?slug=pest-control">Pest Control</a></li>
                        <li><a href="learning-center/category.php?slug=water-management">Water Management</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-envelope"></i> info@farmknowledge.com</li>
                        <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-map-marker-alt"></i> 1234 Farm Road, Rural County</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> FarmKnowledge. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Include Google Translate Script -->
    <?php echo get_translate_javascript(); ?>
    
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            const eyeIcon = this.querySelector('img');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.src = 'Image/eye-slash-icon.png'; // Change to eye-slash icon
            } else {
                passwordField.type = 'password';
                eyeIcon.src = 'Image/eye-icon.png'; // Change back to eye icon
            }
        });
    </script>
</body>
</html>