<?php
session_start();
require_once '../config/db_config.php';
require_once '../config/translate_config.php';
require_once '../config/currency_config.php';

$baseURL = '/agriculture-website/';

// Set default currency or get from session
$current_currency = isset($_SESSION['currency']) ? $_SESSION['currency'] : 'USD';

// Handle currency change
if(isset($_GET['currency']) && array_key_exists($_GET['currency'], $exchange_rates)) {
    $current_currency = $_GET['currency'];
    $_SESSION['currency'] = $current_currency;
}
// Get user data if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Get product ID from URL parameter
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch the product details
try {
    $stmt = $pdo->prepare("SELECT p.*, u.username as seller_name, u.email as seller_email, u.phone_number as seller_phone
                           FROM products p 
                           JOIN users u ON p.seller_id = u.id 
                           WHERE p.id = ? AND p.status = 'available'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        // Product not found or not available, redirect to marketplace
        header('Location: marketplace.php');
        exit;
    }
    
    // Fetch related products in the same category
    $stmt = $pdo->prepare("SELECT p.*, u.username as seller_name 
                          FROM products p 
                          JOIN users u ON p.seller_id = u.id 
                          WHERE p.category = ? AND p.id != ? AND p.status = 'available' 
                          ORDER BY RAND() LIMIT 3");
    $stmt->execute([$product['category'], $product_id]);
    $related_products = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - FarmKnowledge Marketplace</title>
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

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2e7d32;
            text-decoration: none;
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

        .auth-buttons a {
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
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
        
        /* User dropdown styles */
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
            transition: background 0.2s ease;
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
        }
        
        .user-dropdown a:hover {
            color: #2e7d32;
        }

        /* Product Detail Styles */
        .breadcrumb {
            display: flex;
            padding: 1rem 0;
            margin-bottom: 1rem;
            list-style: none;
        }
        
        .breadcrumb li {
            display: flex;
            align-items: center;
        }
        
        .breadcrumb li:not(:last-child)::after {
            content: '/';
            margin: 0 0.5rem;
            color: #aaa;
        }
        
        .breadcrumb a {
            color: #2e7d32;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .product-detail {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .product-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .product-image-container {
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f9f9f9;
        }
        
        .product-detail-image {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .product-detail-image:hover {
            transform: scale(1.03);
        }
        
        .product-detail-info {
            padding: 2rem;
        }
        
        .product-category-badge {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        
        .product-detail-name {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #222;
            font-weight: 600;
        }
          .product-detail-price {
            font-size: 2rem;
            color: #2e7d32;
            font-weight: 700;
            margin: 1rem 0;
            display: flex;
            align-items: center;
        }
        
        .original-price {
            font-size: 0.9rem;
            font-weight: normal;
            color: #777;
            margin-left: 0.8rem;
        }
        
        .product-detail-description {
            color: #666;
            line-height: 1.6;
            margin: 1.5rem 0;
            font-size: 1rem;
        }
        
        .product-meta {
            margin: 2rem 0;
            padding: 1rem 0;
            border-top: 1px solid #eee;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            color: #666;
        }
        
        .meta-item i {
            width: 25px;
            color: #2e7d32;
            margin-right: 0.5rem;
        }
        
        .seller-info {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
        }
        
        .seller-info h3 {
            color: #2e7d32;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .seller-contact {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        
        .contact-button, 
        .back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: #2e7d32;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .contact-button {
            width: 100%;
        }
        
        .contact-button:hover,
        .back-button:hover {
            background: #1b5e20;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
        }
        
        .back-button {
            background: #f0f0f0;
            color: #333;
            margin-right: 1rem;
        }
        
        .back-button:hover {
            background: #e0e0e0;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* Related Products - Improved Styles */
        .related-products {
            margin: 3rem 0;
        }
        
        .related-products h2 {
            color: #2e7d32;
            margin-bottom: 1.8rem;
            text-align: center;
            font-size: 1.8rem;
            position: relative;
            padding-bottom: 15px;
        }
        
        .related-products h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: #2e7d32;
        }
        
        .related-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(46, 125, 50, 0.15);
            border-color: rgba(46, 125, 50, 0.1);
        }
        
        .product-image-wrapper {
            height: 180px;
            overflow: hidden;
            position: relative;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.08);
        }
        
        .product-info {
            padding: 1.2rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .product-category {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 0.25rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .product-name {
            font-size: 1.1rem;
            margin-bottom: 0.6rem;
            color: #222;
            font-weight: 600;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
          .product-price {
            font-size: 1.2rem;
            color: #2e7d32;
            font-weight: 700;
            margin: 0.6rem 0;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: auto;
        }

        /* Footer Styles */
        footer {
            background-color: #f8f9fa;
            color: #333;
            padding: 3rem 0;
            margin-top: 3rem;
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
            .product-detail-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .nav-middle {
                display: none;
            }
            
            .nav-left {
                flex: 1;
            }
            
            .related-products-grid {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
        }

        /* Chat Interface Styles */
        .chat-container {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            height: 450px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            z-index: 1000;
            overflow: hidden;
            flex-direction: column;
        }
        
        .chat-header {
            background: #2e7d32;
            color: white;
            padding: 15px;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-header-actions {
            display: flex;
            gap: 10px;
        }
        
        .view-full-chat {
            cursor: pointer;
            font-size: 1rem;
            color: white;
        }
        
        .chat-close {
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: #999;
            display: block;
            margin-top: 5px;
        }
        
        .message-sender {
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .message.received {
            align-self: flex-start;
            background: #f1f1f1;
            border-bottom-left-radius: 5px;
        }
        
        .message.sent {
            align-self: flex-end;
            background: #e3f2fd;
            border-bottom-right-radius: 5px;
        }
        
        .chat-input-container {
            display: flex;
            padding: 10px;
            border-top: 1px solid #eee;
        }
        
        .chat-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 10px 15px;
            outline: none;
        }
        
        .chat-input:focus {
            border-color: #2e7d32;
        }
        
        .chat-send {
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 0 15px;
            margin-left: 10px;
            cursor: pointer;
        }
        
        .chat-send:hover {
            background: #1b5e20;
        }
        
        .chat-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #2e7d32;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            cursor: pointer;
            border: none;
        }
        
        .chat-toggle:hover {
            background: #1b5e20;
            transform: translateY(-2px);
        }
        
        .chat-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .login-prompt {
            text-align: center;
            padding: 15px;
        }
        
        /* Responsive chat */
        @media (max-width: 768px) {
            .chat-container {
                width: 100%;
                height: 100%;
                bottom: 0;
                right: 0;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="nav-left">
                    <a href="../farm-website-homepage.php" class="logo">FarmKnowledge</a>
                </div>
                <div class="nav-middle">
                    <ul class="nav-links">                        <li><a href="../farm-website-homepage.php">Home</a></li>
                        <li><a href="../about.php">About</a></li>
                        <li><a href="../learning-center/index.php">Learning Center</a></li>
                        <li><a href="marketplace.php?currency=<?php echo htmlspecialchars($current_currency); ?>" class="active">Marketplace</a></li>
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
                                <li><a href="../User/user-profile.php"><i class="fas fa-user"></i> My Account</a></li>
                                <li><a href="../User/sse_messaging.php"><i class="fas fa-envelope"></i> Messages</a></li>
                                <li><a href="../User/wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Language Selector -->
                    <?php echo get_language_selector(); ?>
                    <div class="auth-buttons">
                        <a href="../login.php" class="login">Login</a>
                        <a href="../signup.php" class="register">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <!-- Breadcrumb Navigation -->        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <ul class="breadcrumb" style="margin-bottom: 0;">
                <li><a href="farm-website-homepage.php">Home</a></li>
                <li><a href="marketplace/marketplace.php">Marketplace</a></li>
                <li><?= htmlspecialchars($product['name']) ?></li>
            </ul>
            
            <form id="currencyForm" style="display: flex; align-items: center; gap: 0.5rem; background: #f8f9fa; padding: 0.5rem 1rem; border-radius: 20px;">
                <label for="currency" style="font-weight: 500; color: #555; font-size: 0.9rem;">Currency:</label>
                <select name="currency" id="currency" onchange="this.form.submit()" style="padding: 0.3rem 0.5rem; border: 1px solid #e0e0e0; border-radius: 4px; font-size: 0.9rem; background: white;">
                    <?php foreach($exchange_rates as $code => $rate): ?>
                        <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($current_currency === $code) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($code); ?> (<?php echo $currency_symbols[$code]; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="id" value="<?php echo $product_id; ?>">
            </form>
        </div>
        
        <!-- Product Detail Section -->
        <section class="product-detail">
            <div class="product-detail-grid">
                <div class="product-image-container">
                    <img src="<?= strpos($product['image_url'], 'http') === 0 ? htmlspecialchars($product['image_url']) : $baseURL . htmlspecialchars($product['image_url']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="product-detail-image">
                </div>
                <div class="product-detail-info">                    <span class="product-category-badge"><?= htmlspecialchars($product['category']) ?></span>
                    <h1 class="product-detail-name"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="product-detail-price">
                        <?php 
                        $converted_price = convertCurrency($product['price'], $current_currency, $exchange_rates);
                        echo formatPrice($converted_price, $current_currency, $currency_symbols); 
                        ?>
                        <?php if($current_currency !== 'USD'): ?>
                            <span class="original-price">($<?php echo number_format($product['price'], 2); ?> USD)</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-detail-description">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </div>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>Listed on: <?= isset($product['created_at']) ? date('F j, Y', strtotime($product['created_at'])) : 'Not available' ?></span>
                        </div>
                        <?php if(isset($product['updated_at']) && !empty($product['updated_at'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-sync-alt"></i>
                            <span>Last updated: <?= date('F j, Y', strtotime($product['updated_at'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="seller-info">
                        <h3><i class="fas fa-user-circle"></i> Seller Information</h3>
                        <div class="seller-contact">
                            <div class="meta-item">
                                <i class="fas fa-user"></i>
                                <span><?= htmlspecialchars($product['seller_name']) ?></span>
                            </div>
                            <?php if(!empty($product['seller_phone'])): ?>
                            <div class="meta-item">
                                <i class="fas fa-phone"></i>
                                <span><?= htmlspecialchars($product['seller_phone']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="meta-item">
                                <i class="fas fa-envelope"></i>
                                <span><?= htmlspecialchars($product['seller_email']) ?></span>
                            </div>
                        </div>
                    </div>
                      <div class="button-group">
                        <a href="marketplace.php?currency=<?php echo htmlspecialchars($current_currency); ?>" class="back-button"><i class="fas fa-arrow-left"></i> Back to Marketplace</a>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <button class="chat-toggle" id="chatToggleBtn" 
                                    data-seller-id="<?= $product['seller_id'] ?>" 
                                    data-seller-name="<?= htmlspecialchars($product['seller_name']) ?>">
                                <i class="fas fa-comments"></i> Message Seller
                            </button>
                        <?php else: ?>
                            <a href="../login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="contact-button">
                                <i class="fas fa-envelope"></i> Login to Contact Seller
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Related Products Section -->
        <?php if (!empty($related_products)): ?>
        <section class="related-products">
            <h2>Related Products</h2>
            <div class="related-products-grid">
                <?php foreach ($related_products as $rel_product): ?>
                    <a href="product_details.php?id=<?= $rel_product['id'] ?>&currency=<?= htmlspecialchars($current_currency) ?>" class="product-card">
                        <div class="product-image-wrapper">
                            <img src="<?= strpos($rel_product['image_url'], 'http') === 0 ? htmlspecialchars($rel_product['image_url']) : $baseURL . htmlspecialchars($rel_product['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($rel_product['name']) ?>" 
                                 class="product-image">
                        </div>                        <div class="product-info">
                            <span class="product-category"><?= htmlspecialchars($rel_product['category']) ?></span>
                            <h3 class="product-name"><?= htmlspecialchars($rel_product['name']) ?></h3>
                            <p class="product-price">
                                <?php 
                                $converted_price = convertCurrency($rel_product['price'], $current_currency, $exchange_rates);
                                echo formatPrice($converted_price, $current_currency, $currency_symbols); 
                                ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>
    
    <?php if(isset($_SESSION['user_id'])): ?>
    <!-- Chat Interface -->
    <div class="chat-container" id="chatContainer">
        <div class="chat-header">
            <span id="chatSellerName"></span>
            <div class="chat-header-actions">
                <span class="view-full-chat" id="viewFullChat" title="View in messages"><i class="fas fa-expand"></i></span>
                <span class="chat-close" id="chatClose">&times;</span>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be inserted here by JavaScript -->
        </div>
        <div class="chat-input-container">
            <input type="text" class="chat-input" id="chatInput" placeholder="Type your message...">
            <button class="chat-send" id="chatSend">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>FarmKnowledge</h3>
                    <p>Your complete resource for agricultural knowledge and marketplace.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>                        <li><a href="../farm-website-homepage.php">Home</a></li>
                        <li><a href="../about.php">About Us</a></li>
                        <li><a href="../learning-center/index.php">Learning Center</a></li>
                        <li><a href="marketplace.php?currency=<?php echo htmlspecialchars($current_currency); ?>">Marketplace</a></li>
                        <li><a href="#">Community</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Categories</h3>
                    <ul>                        <li><a href="marketplace.php?category=Seeds&currency=<?php echo htmlspecialchars($current_currency); ?>">Seeds</a></li>
                        <li><a href="marketplace.php?category=Equipment&currency=<?php echo htmlspecialchars($current_currency); ?>">Equipment</a></li>
                        <li><a href="marketplace.php?category=Tools&currency=<?php echo htmlspecialchars($current_currency); ?>">Tools</a></li>
                        <li><a href="marketplace.php?category=Produce&currency=<?php echo htmlspecialchars($current_currency); ?>">Produce</a></li>
                        <li><a href="marketplace.php?currency=<?php echo htmlspecialchars($current_currency); ?>">All Categories</a></li>
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
    
    <?php if(isset($_SESSION['user_id'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatContainer = document.getElementById('chatContainer');
            const chatMessages = document.getElementById('chatMessages');
            const chatInput = document.getElementById('chatInput');
            const chatSend = document.getElementById('chatSend');
            const chatToggleBtn = document.getElementById('chatToggleBtn');
            const chatClose = document.getElementById('chatClose');
            const chatSellerName = document.getElementById('chatSellerName');
            const viewFullChat = document.getElementById('viewFullChat');
            
            let currentSellerId = null;
            let evtSource = null;
            
            // Add a message to the chat window
            function addMessage(messageText, type, timestamp) {
                const messageElement = document.createElement('div');
                messageElement.classList.add('message', type);
                
                let messageContent = '';
                if (type === 'received') {
                    const sellerName = chatSellerName.textContent;
                    messageContent += `<div class="message-sender">${sellerName}</div>`;
                }
                
                messageContent += messageText;
                
                if (timestamp) {
                    const date = new Date(timestamp);
                    const formattedTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    messageContent += `<span class="message-time">${formattedTime}</span>`;
                }
                
                messageElement.innerHTML = messageContent;
                chatMessages.appendChild(messageElement);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Load previous messages
            function loadMessages(sellerId) {
                fetch(`api/messages.php?user_id=${sellerId}`)
                    .then(response => response.json())
                    .then(data => {
                        chatMessages.innerHTML = '';
                        if (data.messages && data.messages.length > 0) {
                            data.messages.forEach(msg => {
                                const type = msg.sender_id == <?= $_SESSION['user_id'] ?> ? 'sent' : 'received';
                                addMessage(msg.message, type, msg.created_at);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading messages:', error);
                    });
            }
            
            // Send message function
            function sendMessage() {
                const message = chatInput.value.trim();
                if (message === '' || !currentSellerId) return;
                
                // Add message to UI first (optimistic UI update)
                const now = new Date();
                addMessage(message, 'sent', now);
                chatInput.value = '';
                
                // Send via REST API
                fetch('api/messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        receiver_id: currentSellerId,
                        message: message,
                        product_id: <?= $product_id ?>,
                        product_name: "<?= addslashes(htmlspecialchars($product['name'])) ?>"
                    })
                }).then(response => response.json())
                  .then(data => {
                      if (data.error) {
                          console.error('Error sending message:', data.error);
                      }
                  })
                  .catch(error => {
                      console.error('Error sending message:', error);
                  });
            }
            
            // Setup SSE connection for real-time messaging
            function setupSSE() {
                if (typeof EventSource !== 'undefined') {
                    // Close any existing connection
                    if (evtSource) {
                        evtSource.close();
                    }
                    
                    evtSource = new EventSource('../api/sse_messages.php?user_id=<?= $_SESSION['user_id'] ?>');
                    
                    evtSource.onmessage = function(event) {
                        const data = JSON.parse(event.data);
                        
                        if (data.type === 'message') {
                            // If we have the chat with this sender open, append message
                            if (currentSellerId === data.sender_id && chatContainer.style.display === 'flex') {
                                addMessage(data.message, 'received', new Date());
                                
                                // Mark as read via AJAX
                                fetch('../api/mark_read.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        sender_id: data.sender_id,
                                        receiver_id: <?= $_SESSION['user_id'] ?>
                                    }),
                                });
                            }
                        }
                    };
                    
                    evtSource.onerror = function() {
                        console.error('EventSource failed. Reconnecting...');
                        evtSource.close();
                        setTimeout(setupSSE, 5000);
                    };
                } else {
                    console.error('SSE not supported by this browser');
                }
            }
            
            // Chat toggle button event listener
            if (chatToggleBtn) {
                chatToggleBtn.addEventListener('click', function() {
                    const sellerId = this.getAttribute('data-seller-id');
                    const sellerName = this.getAttribute('data-seller-name');
                    
                    currentSellerId = sellerId;
                    chatSellerName.textContent = sellerName;
                    chatContainer.style.display = 'flex';
                    
                    // Load previous messages
                    loadMessages(sellerId);
                    
                    chatInput.focus();
                });
            }
            
            // View full conversation in the messages page
            viewFullChat.addEventListener('click', function() {
                if (currentSellerId) {
                    window.location.href = '../User/sse_messaging.php?contact=' + currentSellerId;
                }
            });
            
            // Chat close button event listener
            chatClose.addEventListener('click', function() {
                chatContainer.style.display = 'none';
            });
            
            // Chat send button event listener
            chatSend.addEventListener('click', sendMessage);
            
            // Chat input enter key event listener
            chatInput.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    sendMessage();
                    event.preventDefault();
                }
            });
            
            // Initialize SSE
            setupSSE();
        });
    </script>
    <?php endif; ?>
    
    <!-- Include Google Translate Script -->
    <?php echo get_translate_javascript(); ?>
</body>
</html>