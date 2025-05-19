<?php
session_start();

$baseURL = '/agriculture-website/';

// Include translation functionality
require_once 'config/translate_config.php';

// Get user data if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    // Include database configuration
    require_once 'config/db_config.php';
    
    // Fetch user details from the database
    $user_id = $_SESSION['user_id'];
    $query = "SELECT username, email, created_at, phone_number, address FROM users WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch();
    $stmt->closeCursor();
}

// Include database configuration if not already included
if (!isset($pdo)) {
    require_once 'config/db_config.php';
}

// Fetch random marketplace products
$random_products = [];
try {
    $query = "SELECT * FROM products ORDER BY RAND() LIMIT 4";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $random_products = $stmt->fetchAll();
} catch (PDOException $e) {
    // If there's an error or table doesn't exist yet, we'll use sample data
    $random_products = [];
}

// If no products found in database or if there was an error, use sample data
if (empty($random_products)) {
    // Sample product data as fallback
    $product_images = [
        'smart-irrigation.jpg', 'organic-compost.jpg', 'Heirloom Vegetable Seed Bundle.jpg', 'tool-set.jpg',
        'seed-bundle.jpg', 'soil-test.jpg', 'greenhouse.jpg', 'safety-kit.jpg', 'solar-pump.jpg',
        'biopesticide.jpg', 'composter.jpg', 'Drip Irrigation Kit.jpg'
    ];
    
    $product_names = [
        'Smart Irrigation Controller', 'Premium Organic Compost', 'Heirloom Seed Collection',
        'Professional Garden Tool Set', 'Vegetable Seed Bundle', 'Soil Testing Kit', 'Mini Greenhouse',
        'Farm Safety Equipment', 'Solar Water Pump', 'Organic Biopesticide', 'Compost Bin',
        'Drip Irrigation System'
    ];
    
    $product_descriptions = [
        'Wi-Fi enabled system for precision water management',
        'Plant-based, nutrient-rich soil amendment',
        '25 varieties of non-GMO vegetable seeds',
        'Ergonomic, rust-resistant essential tools',
        'Collection of high-yield vegetable seeds',
        'Professional-grade soil analysis tools',
        'Portable greenhouse for seedlings and small plants',
        'Complete safety gear for farm operations',
        'Energy-efficient water pump with solar panel',
        'Natural pest control solution for organic farming',
        'Large capacity compost bin for organic waste',
        'Complete water-saving irrigation kit'
    ];
    
    $product_prices = [
        '199.99', '34.99', '42.50', '89.95', '29.99', '75.00', '129.50',
        '65.00', '245.00', '19.99', '59.95', '84.50'
    ];
    
    // Create 4 random products
    $used_indices = [];
    for ($i = 0; $i < 4; $i++) {
        $index = array_rand($product_images);
        while (in_array($index, $used_indices)) {
            $index = array_rand($product_images);
        }
        $used_indices[] = $index;
        
        $random_products[] = [
            'id' => $i + 1,
            'image' => $product_images[$index],
            'name' => $product_names[$index],
            'description' => $product_descriptions[$index],
            'price' => $product_prices[$index]
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>FarmKnowledge Hub - Education & Marketplace for Farmers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
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
            background-color: transparent;
            color: white;
            padding: 1rem 0;
            position: absolute;
            width: 100%;
            z-index: 10;
            box-shadow: none;
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
            color:rgb(255, 255, 255);
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
        }
        
        .nav-links li {
            margin-left: 1.5rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .nav-links a:hover {
            text-decoration: underline;
        }
        
        .auth-buttons a {
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
        }
        
        .login {
            color: white;
            border: 1px solid white;
        }
        
        .register {
            background-color: white;
            color: #2e7d32;
        }

        .user-info {
display: flex;
align-items: center;
gap: 8px;
position: relative;
cursor: pointer;
}

.user-info {
display: flex;
align-items: center;
gap: 8px;
position: relative;
cursor: pointer;
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

/* Keep dropdown interactive */
.user-dropdown {
pointer-events: auto;
}

.user-dropdown li {
transition: background-color 0.2s ease;
}

.user-dropdown li:hover {
background-color: #f8f9fa;
}

/* Add padding to dropdown items */
.user-dropdown li {
padding: 12px 20px;
transition: background 0.2s ease;
}

.user-dropdown li:hover {
background: #f5f5f5;
}

/* Style for user info area */
.user-info {
position: relative;
padding: 10px 0;
display: flex;
align-items: center;
gap: 8px;
}

.user-info:hover {
cursor: pointer;
}

.user-dropdown ul {
list-style: none;
padding: 0;
margin: 0;
}

.user-dropdown li {
padding: 10px 15px;
border-bottom: 1px solid #eee;
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
        
        .hero {
            background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)), url('Image/hero.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            margin-top: 0;
            display: flex;
            align-items: center;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle, rgba(46,125,50,0.2) 0%, rgba(0,0,0,0.4) 100%);
        }

        .hero-content {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero h1 {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero p {
            font-size: 1.4rem;
            margin-bottom: 2.5rem;
            line-height: 1.6;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .cta-button {
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            background-color: #2e7d32;
            color: white;
            border: 2px solid transparent;
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(46,125,50,0.2);
        }

        .cta-button:hover {
            background-color: transparent;
            border-color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46,125,50,0.3);
        }
        
        .search-bar {
            margin: 2rem 0;
            display: flex;
            justify-content: center;
        }
        
        .search-bar input {
            width: 80%;
            padding: 1rem 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .search-bar input:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }
        
        /* Search Section Styles */
        
        .features {
            padding: 3rem 0;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .feature-card h3 {
            margin: 1rem 0;
            color: #2e7d32;
        }
        
        .quick-access {
            background-color: #e8f5e9;
            padding: 3rem 0;
        }
        
        .categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .category {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .category:hover {
            transform: translateY(-5px);
        }
        
        .category img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
        }
        
        .marketplace {
            padding: 3rem 0;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .product-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            /* Adding fixed dimensions for consistent card size */
            height: 420px;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(46,125,50,0.2);
        }
        
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            flex-shrink: 0; /* Prevent image from shrinking */
        }
        
        .product-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1; /* Allow product info to expand */
            justify-content: space-between;
        }
        
        .product-info h3 {
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            height: 2.4rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            flex-grow: 1;
            height: 3.8rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        
        .price {
            font-weight: bold;
            color: #2e7d32;
            font-size: 1.2rem;
            margin: 0.5rem 0;
        }
        
        .view-button {
            display: block;
            width: 100%;
            padding: 0.8rem;
            background-color: #2e7d32;
            color: white;
            text-align: center;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 1rem;
        }
        
        .reviews {
            padding: 4rem 0;
            background-color: #f5f5f5;
        }

        .reviews h2 {
            text-align: center;
            margin-bottom: 3rem;
            color: #2e7d32;
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 0 1rem;
        }

        .review-card {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-5px);
        }

        .review-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .reviewer-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 1rem;
        }

        .reviewer-info h3 {
            margin: 0;
            color: #2e7d32;
        }

        .reviewer-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .review-text {
            font-style: italic;
            color: #444;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .rating {
            color: #ffd700;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .reviews-grid {
                grid-template-columns: 1fr;
            }
        }
        
               /* Footer Styles */
               footer {
            background-color: #f8f9fa;
            color: #333;
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

        
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
        }

        /* Add styles for statistics section */
        .statistics {
            padding: 5rem 0;
            background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .statistics::before {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -100px;
            right: -100px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item {
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff 0%, #e8f5e9 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-text {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Add styles for trending and news sections */
        .trending-topics {
            padding: 4rem 0;
            background-color: #fff;
        }

        .topic-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .topic-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }

        .topic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(46,125,50,0.1);
        }

        .topic-image {
            height: 200px;
            overflow: hidden;
        }

        .topic-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .topic-card:hover .topic-image img {
            transform: scale(1.1);
        }

        .topic-content {
            padding: 1.5rem;
        }

        .topic-tag {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .topic-content h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .topic-content p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .read-more {
            color: #2e7d32;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        .read-more:hover {
            text-decoration: underline;
        }
        
        /* Search Section Styles */
        .search-section {
            background-color: #f8f9fa;
            padding: 2.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .search-container {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        .search-container h2 {
            margin-bottom: 1.5rem;
            color: #2e7d32;
        }
        
        .popular-searches {
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .popular-searches span {
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .popular-searches a {
            color: #2e7d32;
            text-decoration: none;
            margin: 0 0.5rem;
            padding: 0.2rem 0.5rem;
            background-color: #e8f5e9;
            border-radius: 2rem;
            transition: all 0.3s ease;
        }
        
        .popular-searches a:hover {
            background-color: #c8e6c9;
        }
        
        /* Tools Section Styles */
        .tools-section {
            padding: 4rem 0;
            background-color: #f9fbf9;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2.5rem;
        }
        
        .tool-card {
            background-color: white;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .tool-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(46,125,50,0.1);
        }
        
        .tool-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .tool-icon i {
            font-size: 2.5rem;
            color: #2e7d32;
        }
        
        .tool-card h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .tool-card p {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .tool-button {
            display: inline-block;
            padding: 0.7rem 1.5rem;
            background-color: #2e7d32;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .tool-button:hover {
            background-color: #1b5e20;
            transform: translateY(-2px);
        }
        
        /* Events Section Styles */
        .events-section {
            padding: 4rem 0;
            background-color: #fff;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2.5rem;
        }
        
        .event-card {
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
        }
        
        .event-date {
            background-color: #2e7d32;
            color: white;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 100px;
        }
        
        .event-month {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .event-day {
            font-size: 1.8rem;
            font-weight: bold;
            line-height: 1;
            margin-top: 0.3rem;
        }
        
        .event-details {
            padding: 1.5rem;
            flex: 1;
        }
        
        .event-details h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .event-host {
            color: #2e7d32;
            font-weight: 500;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }
        
        .event-desc {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .event-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
            color: #777;
            font-size: 0.9rem;
        }
        
        .event-meta i {
            margin-right: 0.3rem;
        }
        
        .event-register {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #e8f5e9;
            color: #2e7d32;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .event-register:hover {
            background-color: #2e7d32;
            color: white;
        }
        
        .events-cta {
            text-align: center;
            margin-top: 3rem;
        }
        
        /* Section Headers */
        .section-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .section-header h2 {
            color: #2e7d32;
            margin-bottom: 0.5rem;
            font-size: 2.2rem;
        }
        
        .section-header p {
            color: #666;
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        /* Marketplace CTA */
        .marketplace-cta {
            text-align: center;
            margin-top: 3rem;
        }
        
        /* App Promotion */
        .app-promotion {
            padding: 4rem 0;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        }
        
        .app-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }
        
        .app-content h2 {
            margin-bottom: 1rem;
            color: #2e7d32;
        }
        
        .app-content p {
            color: #444;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .app-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .app-button {
            background-color: #333;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .app-button i {
            font-size: 1.8rem;
            margin-right: 10px;
        }
        
        .app-button span {
            display: flex;
            flex-direction: column;
        }
        
        .app-button small {
            font-size: 0.7rem;
            opacity: 0.8;
        }
        
        .app-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .app-features {
            display: flex;
            gap: 1.5rem;
        }
        
        .app-feature {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .app-feature i {
            font-size: 1.5rem;
            color: #2e7d32;
            margin-bottom: 0.5rem;
        }
        
        .app-image img {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .newsletter-container, .app-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .newsletter-image, .app-image {
                order: -1;
            }
            
            .event-card {
                flex-direction: column;
            }
            
            .event-date {
                flex-direction: row;
                padding: 1rem;
                justify-content: center;
            }
            
            .event-month {
                margin-right: 0.5rem;
            }
            
            .app-buttons {
                flex-direction: column;
            }
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
                                <li><a href="User/wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
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
    
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Your One-Stop Agricultural Resource</h1>
                <p>Learn modern farming techniques, diagnose plant diseases, and connect with other farmers to buy and sell resources.</p>
                <a href="#" class="cta-button">Explore Now</a>
            </div>
        </div>
    </section>
    
    <section class="statistics">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">2.5B</div>
                    <div class="stat-text">People Depend on Agriculture for Livelihood</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">70%</div>
                    <div class="stat-text">Of World's Fresh Water Used in Agriculture</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">30%</div>
                    <div class="stat-text">Global Food Production Lost Annually</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">60%</div>
                    <div class="stat-text">More Food Needed by 2050</div>
                </div>
            </div>
        </div>
    </section>

    <section class="quick-access">
        <div class="container">
            <h2>Quick Access</h2>
            <div class="categories">
                <div class="category">
                    <img src="Image\Organic Farming.jpg" alt="Organic Farming">
                    <h3>Organic Farming</h3>
                </div>
                <div class="category">
                    <img src="Image\Hydroponics.jpg" alt="Hydroponics">
                    <h3>Hydroponics</h3>
                </div>
                <div class="category">
                    <img src="Image\Soil Management.jpg" alt="Soil Management">
                    <h3>Soil Management</h3>
                </div>
                <div class="category">
                    <img src="Image\Pest Control.jpg" alt="Pest Control">
                    <h3>Pest Control</h3>
                </div>
                <div class="category">
                    <img src="Image\Water Management.jpg" alt="Water Management">
                    <h3>Water Management</h3>
                </div>
            </div>
        </div>
    </section>
     
    <section class="features">
        <div class="container">
            <h2>Why Choose Us</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <h3>Comprehensive Knowledge Base</h3>
                    <p>Access thousands of articles, guides and solutions for all your farming needs.</p>
                </div>
                <div class="feature-card">
                    <h3>Plant Disease Diagnosis</h3>
                    <p>Identify plant diseases and get recommended treatments quickly.</p>
                </div>
                <div class="feature-card">
                    <h3>B2B Marketplace</h3>
                    <p>Buy and sell agricultural resources directly with other farmers.</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="tools-section">
        <div class="container">
            <div class="section-header">
                <h2>Agricultural Tools & Calculators</h2>
                <p>Free tools to help optimize your farming operations</p>
            </div>
            <div class="tools-grid">
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3>Fertilizer Calculator</h3>
                    <p>Calculate precise nutrient requirements based on crop type, area, and soil composition.</p>
                    <a href="#" class="tool-button">Use Calculator</a>
                </div>
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-cloud-rain"></i>
                    </div>
                    <h3>Irrigation Planner</h3>
                    <p>Determine optimal irrigation schedules based on crop water requirements and local weather patterns.</p>
                    <a href="#" class="tool-button">Plan Irrigation</a>
                </div>
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <h3>Planting Calendar</h3>
                    <p>Customized planting schedules based on your location, climate zone, and selected crops.</p>
                    <a href="#" class="tool-button">Create Calendar</a>
                </div>
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Crop Disease Identifier</h3>
                    <p>Upload photos of affected plants to identify diseases and get treatment recommendations.</p>
                    <a href="#" class="tool-button">Identify Disease</a>
                </div>
            </div>
        </div>
    </section>

    <section class="trending-topics">
        <div class="container">
            <div class="section-header">
                <h2>Trending in Agriculture</h2>
                <p>Stay updated with the latest trends and innovations in farming</p>
            </div>
            <div class="topic-grid">
                <div class="topic-card">
                    <div class="topic-image">
                        <img src="Image\Tranding\Smart Farming Technologies.jpg" alt="Smart Farming">
                    </div>
                    <div class="topic-content">
                        <span class="topic-tag">Innovation</span>
                        <h3>Smart Farming Technologies</h3>
                        <p>Discover how AI and IoT are revolutionizing modern agriculture with precision farming techniques.</p>
                        <a href="#" class="read-more">Learn More →</a>
                    </div>
                </div>
                <div class="topic-card">
                    <div class="topic-image">
                        <img src="Image\Tranding\Regenerative Agriculture.jpg" alt="Sustainable Farming">
                    </div>
                    <div class="topic-content">
                        <span class="topic-tag">Sustainability</span>
                        <h3>Regenerative Agriculture</h3>
                        <p>Learn about farming practices that reverse climate change while improving soil health.</p>
                        <a href="#" class="read-more">Learn More →</a>
                    </div>
                </div>
                <div class="topic-card">
                    <div class="topic-image">
                        <img src="Image/Tranding/Urban Farming.webp" alt="Vertical Farming">
                    </div>
                    <div class="topic-content">
                        <span class="topic-tag">Urban Farming</span>
                        <h3>Vertical Farming Solutions</h3>
                        <p>Explore how vertical farming is transforming urban food production and sustainability.</p>
                        <a href="#" class="read-more">Learn More →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="events-section">
        <div class="container">
            <div class="section-header">
                <h2>Upcoming Events & Webinars</h2>
                <p>Join us for live learning opportunities with agricultural experts</p>
            </div>
            <div class="events-grid">
                <div class="event-card">
                    <div class="event-date">
                        <span class="event-month">MAY</span>
                        <span class="event-day">12</span>
                    </div>
                    <div class="event-details">
                        <h3>Sustainable Irrigation Practices</h3>
                        <p class="event-host">By Dr. Emma Rodriguez, Water Management Specialist</p>
                        <p class="event-desc">Learn cutting-edge techniques to reduce water usage while maintaining crop yields.</p>
                        <div class="event-meta">
                            <span><i class="far fa-clock"></i> 2:00 PM EST</span>
                            <span><i class="fas fa-video"></i> Online Webinar</span>
                        </div>
                        <a href="#" class="event-register">Register Now</a>
                    </div>
                </div>
                <div class="event-card">
                    <div class="event-date">
                        <span class="event-month">MAY</span>
                        <span class="event-day">18</span>
                    </div>
                    <div class="event-details">
                        <h3>Organic Pest Management Workshop</h3>
                        <p class="event-host">By Michael Chen, Certified Organic Farmer</p>
                        <p class="event-desc">Discover natural solutions to common pest problems without harmful chemicals.</p>
                        <div class="event-meta">
                            <span><i class="far fa-clock"></i> 10:00 AM EST</span>
                            <span><i class="fas fa-map-marker-alt"></i> Virtual + In-Person</span>
                        </div>
                        <a href="#" class="event-register">Register Now</a>
                    </div>
                </div>
                <div class="event-card">
                    <div class="event-date">
                        <span class="event-month">MAY</span>
                        <span class="event-day">25</span>
                    </div>
                    <div class="event-details">
                        <h3>Farm Financing & Grants Panel</h3>
                        <p class="event-host">With Agricultural Finance Experts</p>
                        <p class="event-desc">Learn about available funding options, government programs, and how to apply successfully.</p>
                        <div class="event-meta">
                            <span><i class="far fa-clock"></i> 1:00 PM EST</span>
                            <span><i class="fas fa-video"></i> Online Webinar</span>
                        </div>
                        <a href="#" class="event-register">Register Now</a>
                    </div>
                </div>
            </div>
            <div class="events-cta">
                <a href="#" class="cta-button">View All Events</a>
            </div>
        </div>
    </section>
    
    <section class="reviews">
        <div class="container">
            <h2>What Our Community Says</h2>
            <div class="reviews-grid">
                <div class="review-card">
                    <div class="review-header">
                        <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="John Smith" class="reviewer-img">
                        <div class="reviewer-info">
                            <h3>John Smith</h3>
                            <p>Organic Farmer</p>
                        </div>
                    </div>
                    <p class="review-text">"The plant disease diagnosis tool saved my tomato crop! The immediate solutions provided were incredibly helpful. This platform is a game-changer for modern farmers."</p>
                    <div class="rating">★★★★★</div>
                </div>
                <div class="review-card">
                    <div class="review-header">
                        <img src="https://randomuser.me/api/portraits/women/1.jpg" alt="Sarah Johnson" class="reviewer-img">
                        <div class="reviewer-info">
                            <h3>Sarah Johnson</h3>
                            <p>Hydroponic Specialist</p>
                        </div>
                    </div>
                    <p class="review-text">"I've found amazing deals on equipment through the marketplace. The community here is supportive and knowledgeable. Highly recommend to both new and experienced farmers!"</p>
                    <div class="rating">★★★★★</div>
                </div>
                <div class="review-card">
                    <div class="review-header">
                        <img src="https://randomuser.me/api/portraits/men/2.jpg" alt="Mike Anderson" class="reviewer-img">
                        <div class="reviewer-info">
                            <h3>Mike Anderson</h3>
                            <p>Small Scale Farmer</p>
                        </div>
                    </div>
                    <p class="review-text">"The learning resources here are invaluable. I've improved my yield significantly by implementing the sustainable farming techniques I learned on this platform."</p>
                    <div class="rating">★★★★½</div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="app-promotion">
        <div class="container">
            <div class="app-container">
                <div class="app-content">
                    <h2>Take FarmKnowledge With You</h2>
                    <p>Access farming resources, marketplace listings, and expert advice anytime, anywhere with our mobile app.</p>
                    <div class="app-buttons">
                        <a href="#" class="app-button">
                            <i class="fab fa-apple"></i>
                            <span>
                                <small>Download on the</small>
                                App Store
                            </span>
                        </a>
                        <a href="#" class="app-button">
                            <i class="fab fa-google-play"></i>
                            <span>
                                <small>Get it on</small>
                                Google Play
                            </span>
                        </a>
                    </div>
                    <div class="app-features">
                        <div class="app-feature">
                            <i class="fas fa-wifi"></i>
                            <span>Offline Access</span>
                        </div>
                        <div class="app-feature">
                            <i class="fas fa-bell"></i>
                            <span>Price Alerts</span>
                        </div>
                        <div class="app-feature">
                            <i class="fas fa-camera"></i>
                            <span>Disease Scanner</span>
                        </div>
                    </div>
                </div>
                <div class="app-image">
                    <img src="Image/login.jpg" alt="FarmKnowledge Mobile App">
                </div>
            </div>
        </div>
    </section>
    
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
                <p>&copy; 2025 FarmKnowledge. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Include Google Translate Script -->
    <?php echo get_translate_javascript(); ?>
</body>
</html>
