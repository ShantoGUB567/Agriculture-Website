<?php
session_start();

// Include translation functionality
require_once 'config/translate_config.php';

// Get user data if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    // Include database configuration
    require_once 'config/db_config.php';
    
    // Fetch user details from the database
    $user_id = $_SESSION['user_id'];
    $query = "SELECT username, email FROM users WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch();
    $stmt->closeCursor();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - FarmKnowledge Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
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
            font-size: 1rem;
        }

        .nav-links li a:hover {
            color: #2e7d32;
        }
        
        .nav-links li a.active {
            color: #2e7d32;
            font-weight: 600;
        }

        .auth-buttons a {
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .auth-buttons .login {
            background: #fff;
            color: #2e7d32;
            border: 2px solid #2e7d32;
        }

        .auth-buttons .login:hover {
            background: #2e7d32;
            color: #fff;
        }

        .auth-buttons .register {
            background: #2e7d32;
            color: #fff;
            border: none;
        }

        .auth-buttons .register:hover {
            background: #1b5e20;
        }

        /* User Dropdown Styles */
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            cursor: pointer;
            padding: 10px 0;
        }
        
        .user-avatar {
            font-size: 1.5rem;
            color: #f1c40f;
        }
        
        .username {
            font-weight: 500;
            font-size: 1rem;
        }
        
        .user-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            background: white;
            min-width: 200px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
            border-radius: 4px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        
        .user-info:hover .user-dropdown,
        .user-dropdown:hover {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            transition-delay: 0s;
        }
        
        /* Create a transparent gap to prevent accidental mouseout */
        .user-info::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 20px;
            background: transparent;
        }
        
        .user-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .user-dropdown li {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
        }
        
        .user-dropdown li:hover {
            background: #f5f5f5;
        }
        
        .user-dropdown li:last-child {
            border-bottom: none;
        }
        
        .user-dropdown a {
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
            font-size: 1rem;
        }
        
        .user-dropdown a:hover {
            color: #2e7d32;
        }

        /* Hero Section */
        .about-hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('Image/hero2.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 100px 0;
            margin-bottom: 50px;
        }

        .about-hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .about-hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Mission Section */
        .mission-section {
            padding: 50px 0;
            background-color: white;
            margin-bottom: 50px;
        }

        .mission-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }

        .mission-text h2 {
            color: #2e7d32;
            margin-bottom: 20px;
            font-size: 2rem;
        }

        .mission-text p {
            line-height: 1.6;
            color: #666;
            margin-bottom: 1rem;
        }

        .mission-image img {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Values Section */
        .values-section {
            padding: 50px 0;
            background-color: #e8f5e9;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-header h2 {
            color: #2e7d32;
            margin-bottom: 15px;
            font-size: 2rem;
        }

        .section-header p {
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 40px;
        }

        .value-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .value-card:hover {
            transform: translateY(-5px);
        }

        .value-icon {
            font-size: 2.5rem;
            color: #2e7d32;
            margin-bottom: 20px;
        }

        .value-card h3 {
            color: #2e7d32;
            margin-bottom: 15px;
        }

        .value-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Features Section */
        .features-section {
            padding: 50px 0;
            background-color: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .feature-icon {
            background-color: #e8f5e9;
            color: #2e7d32;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .feature-content h3 {
            color: #2e7d32;
            margin-bottom: 10px;
        }

        .feature-content p {
            color: #666;
            line-height: 1.6;
        }

        /* Stats Section */
        .stats-section {
            padding: 50px 0;
            background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            color: white;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-text {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Team Section - Enhanced card design */
        .team-section {
            padding: 70px 0;
            background-color: #f8f9fa;
        }

        .team-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 40px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .team-member {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            width: 280px;
            flex-shrink: 0;
            position: relative;
        }

        .team-member:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(46,125,50,0.15);
        }

        .member-image {
            height: 280px;
            overflow: hidden;
            position: relative;
        }

        .member-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(to top, rgba(0,0,0,0.6), transparent);
        }

        .member-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .team-member:hover .member-image img {
            transform: scale(1.1);
        }

        .member-info {
            padding: 25px 20px;
            text-align: center;
            position: relative;
        }

        .member-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 40%;
            height: 3px;
            background: linear-gradient(to right, #2e7d32, #43a047);
            border-radius: 10px;
        }

        .member-info h3 {
            color: #2e7d32;
            margin-bottom: 5px;
            font-size: 1.3rem;
        }

        .member-info .role {
            color: #666;
            font-style: italic;
            margin-bottom: 15px;
            font-size: 0.9rem;
            display: block;
        }

        .member-bio {
            color: #555;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }

        .social-links a {
            color: #fff;
            text-decoration: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #2e7d32;
        }

        .social-links a:hover {
            transform: translateY(-3px);
            background-color: #1b5e20;
            box-shadow: 0 5px 15px rgba(46,125,50,0.3);
        }

        /* Contact Section */
        .contact-section {
            padding: 50px 0;
            background-color: white;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .contact-card {
            text-align: center;
            padding: 30px;
            border-radius: 10px;
            background: #f8f9fa;
            transition: transform 0.3s ease;
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .contact-icon {
            font-size: 2rem;
            color: #2e7d32;
            margin-bottom: 15px;
        }

        .contact-card h3 {
            color: #2e7d32;
            margin-bottom: 10px;
        }

        .contact-card p {
            color: #666;
        }

        /* Footer Styles */
        footer {
            background-color: #f8f9fa;
            padding: 3rem 0;
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

        .copyright {
            margin-top: 2rem;
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            color: #666;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .team-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .nav-middle {
                display: none;
            }
            
            .nav-left {
                flex: 1;
            }
            
            .mission-content,
            .features-grid,
            .team-grid,
            .values-grid,
            .contact-grid {
                grid-template-columns: 1fr;
            }
            
            .about-hero h1 {
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .about-hero h1 {
                font-size: 2rem;
            }
            
            .about-hero p {
                font-size: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Feedback Form Styles */
        .feedback-section {
            padding: 70px 0;
            background-color: #e8f5e9;
        }
        
        .feedback-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46,125,50,0.1);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .submit-button {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
        }
        
        .submit-button:hover {
            background: #1b5e20;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46,125,50,0.2);
        }
        
        .form-message {
            display: none;
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .success-message {
            background-color: rgba(46,125,50,0.1);
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }
        
        .error-message {
            background-color: rgba(211,47,47,0.1);
            color: #d32f2f;
            border: 1px solid #d32f2f;
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
                        <li><a href="about.php" class="active">About</a></li>
                        <li><a href="learning-center/index.php">Learning Center</a></li>
                        <li><a href="marketplace/marketplace.php">Marketplace</a></li>
                        <li><a href="#">Community</a></li>
                        <li><a href="#">News</a></li>
                    </ul>
                </div>
                <div class="nav-right">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- Language Selector -->
                    <?php echo get_language_selector(); ?>
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                        <div class="user-dropdown">
                            <ul>
                                <li><a href="User/user-profile.php"><i class="fas fa-user"></i> My Account</a></li>
                                <li><a href="User/sse_messaging.php"><i class="fas fa-envelope"></i> Messages</a></li>
                                <li><a href="#"><i class="fas fa-heart"></i> Wishlist</a></li>
                                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Language Selector -->
                    <?php echo get_language_selector(); ?>
                    <div class="auth-buttons">
                        <a href="login.php" class="login">Login</a>
                        <a href="signup.php" class="register">Sign Up</a>
                    </div>
                <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <section class="about-hero">
        <div class="container">
            <h1>About FarmKnowledge</h1>
            <p>Empowering farmers worldwide with knowledge, resources, and community to create a sustainable agricultural future</p>
        </div>
    </section>

    <section class="mission-section">
        <div class="container">
            <div class="mission-content">
                <div class="mission-text">
                    <h2>Our Mission</h2>
                    <p>At FarmKnowledge Hub, we're on a mission to bridge the gap between traditional farming wisdom and modern agricultural technology. Founded in 2022, our platform serves as a comprehensive resource for farmers of all scales and experience levels.</p>
                    <p>We believe that by democratizing access to agricultural knowledge and creating marketplace opportunities, we can help farmers increase productivity, sustainability, and profitability while addressing global food security challenges.</p>
                    <p>Our integrated approach combines educational resources, community support, and a specialized B2B marketplace to create an ecosystem where farmers can learn, connect, and grow their agricultural businesses.</p>
                </div>
                <div class="mission-image">
                    <img src="Image/about.jpg" alt="Farmers in a field">
                </div>
            </div>
        </div>
    </section>

    <section class="values-section">
        <div class="container">
            <div class="section-header">
                <h2>Our Core Values</h2>
                <p>The principles that guide everything we do</p>
            </div>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <h3>Sustainability</h3>
                    <p>We promote farming practices that protect our environment, conserve resources, and ensure long-term food security for future generations.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community</h3>
                    <p>We foster a supportive global network where farmers can share knowledge, resources, and experiences regardless of location or scale.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Innovation</h3>
                    <p>We embrace and promote technological advancements and creative solutions to improve farming efficiency, productivity, and sustainability.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="container">
            <div class="section-header">
                <h2>What We Offer</h2>
                <p>Comprehensive solutions for modern agricultural challenges</p>
            </div>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Learning Center</h3>
                        <p>Access thousands of articles, tutorials, and guides covering every aspect of modern farming, from soil management to hydroponic systems.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="feature-content">
                        <h3>B2B Marketplace</h3>
                        <p>Connect directly with suppliers and buyers in our specialized marketplace designed specifically for agricultural goods and services.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Plant Disease Diagnosis</h3>
                        <p>Identify plant diseases quickly and get recommended treatments with our advanced diagnostic tools and expert knowledge base.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Community Forum</h3>
                        <p>Join discussions with farmers worldwide, share experiences, ask questions, and collaborate on solutions to common challenges.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">150K+</div>
                    <div class="stat-text">Registered Farmers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">5,000+</div>
                    <div class="stat-text">Educational Resources</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">120+</div>
                    <div class="stat-text">Countries Reached</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">25K+</div>
                    <div class="stat-text">Marketplace Listings</div>
                </div>
            </div>
        </div>
    </section>

    <section class="team-section">
        <div class="container">
            <div class="section-header">
                <h2>Meet Our Team</h2>
                <p>The passionate experts behind FarmKnowledge Hub</p>
            </div>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-image">
                        <img src="Image/market-product-IMG/default-product.jpg" alt="Antu Marma">
                    </div>
                    <div class="member-info">
                        <h3>Antu Marma</h3>
                        <span class="role">Founder & CEO</span>
                        <p class="member-bio">Agricultural visionary with 10+ years of experience in sustainable farming practices and agri-tech innovation.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                <div class="team-member">
                    <div class="member-image">
                        <img src="Image/market-product-IMG/default-product.jpg" alt="Shanto">
                    </div>
                    <div class="member-info">
                        <h3>Shanto</h3>
                        <span class="role">Technology Director</span>
                        <p class="member-bio">Tech expert specializing in agricultural software solutions and digital farming systems integration.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                <div class="team-member">
                    <div class="member-image">
                        <img src="Image/market-product-IMG/default-product.jpg" alt="Suma">
                    </div>
                    <div class="member-info">
                        <h3>Suma</h3>
                        <span class="role">Agricultural Expert</span>
                        <p class="member-bio">Plant scientist with expertise in organic farming methods, crop health, and sustainable agricultural practices.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-section">
        <div class="container">
            <div class="section-header">
                <h2>Get In Touch</h2>
                <p>We'd love to hear from you</p>
            </div>
            <div class="contact-grid">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Our Location</h3>
                    <p>123 Farm Road<br>Agricultural City, AC 12345</p>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email Us</h3>
                    <p>info@farmknowledge.com<br>support@farmknowledge.com</p>
                </div>
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h3>Call Us</h3>
                    <p>+1 (555) 123-4567<br>+1 (555) 765-4321</p>
                </div>
            </div>
        </div>
    </section>

    <section class="feedback-section">
        <div class="container">
            <div class="section-header">
                <h2>Share Your Feedback</h2>
                <p>We value your input to improve our services</p>
            </div>
            <div class="feedback-container">
                <form id="feedbackForm">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Enter your name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email address" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" placeholder="Enter subject">
                    </div>
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" class="form-control" placeholder="Enter your feedback or questions here..." required></textarea>
                    </div>
                    <button type="submit" class="submit-button"><i class="fas fa-paper-plane"></i> Submit Feedback</button>
                </form>
                <div id="successMessage" class="form-message success-message">Thank you for your feedback! We'll get back to you soon.</div>
                <div id="errorMessage" class="form-message error-message">Something went wrong. Please try again.</div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>FarmKnowledge</h3>
                    <p>Your complete resource for agricultural knowledge and marketplace solutions.</p>
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
                        <li><a href="#">Farming Methods</a></li>
                        <li><a href="#">Plant Health</a></li>
                        <li><a href="#">Soil Management</a></li>
                        <li><a href="#">Water Management</a></li>
                        <li><a href="#">Pest Control</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <ul>
                        <li>Email: info@farmknowledge.com</li>
                        <li>Phone: +1 (555) 123-4567</li>
                        <li>Address: 123 Farm Road, Agricultural City</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 FarmKnowledge Hub. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Include Google Translate Script -->
    <?php echo get_translate_javascript(); ?>
    
    <script>
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            // Hide any previous messages
            document.getElementById('successMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
            
            // In a real application, you would send this data to a server
            // For demonstration, we'll simulate a successful submission
            setTimeout(() => {
                // Show success message
                document.getElementById('successMessage').style.display = 'block';
                
                // Reset the form
                document.getElementById('feedbackForm').reset();
                
                // Hide success message after 5 seconds
                setTimeout(() => {
                    document.getElementById('successMessage').style.display = 'none';
                }, 5000);
            }, 1000);
            
            // For a real implementation, you'd use fetch() or XMLHttpRequest to send data to a server
            // Example:
            /*
            fetch('process_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    subject: subject,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('successMessage').style.display = 'block';
                    document.getElementById('feedbackForm').reset();
                } else {
                    document.getElementById('errorMessage').style.display = 'block';
                }
            })
            .catch(error => {
                document.getElementById('errorMessage').style.display = 'block';
                console.error('Error:', error);
            });
            */
        });
    </script>
</body>
</html>