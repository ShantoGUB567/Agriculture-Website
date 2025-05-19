<?php
session_start();
require_once '../config/db_config.php';

$baseURL = '/agriculture-website/';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get user data
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Fetch wishlist items
$stmt = $pdo->prepare("
    SELECT p.*, w.created_at as favorited_at, u.username as seller_name
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    JOIN users u ON p.seller_id = u.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$wishlist_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - FarmKnowledge Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/user-profile.css">
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
        :root {
            --primary-color: #2e7d32;
            --primary-dark: #1b5e20;
            --primary-light: #e8f5e9;
            --secondary-color: #f5f5f5;
            --danger-color: #e74c3c;
            --danger-dark: #c0392b;
            --text-dark: #333;
            --text-light: #666;
            --text-muted: #999;
            --shadow-sm: 0 4px 6px rgba(0,0,0,0.05);
            --shadow-md: 0 6px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 12px 24px rgba(0,0,0,0.1);
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --transition: all 0.3s ease;
        }
        
        .page-header {
            background-color: var(--primary-light);
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            margin: 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .page-header .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .filter-sorting {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-dropdown,
        .sort-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-toggle {
            background: white;
            color: var(--text-dark);
            border: 1px solid #ddd;
            border-radius: var(--border-radius-sm);
            padding: 0.6rem 1.2rem;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: var(--transition);
        }
        
        .dropdown-toggle:hover {
            border-color: var(--primary-color);
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            display: none;
            min-width: 180px;
            background: white;
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow-md);
            padding: 0.5rem 0;
            margin-top: 0.5rem;
            border: 1px solid #eee;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            display: block;
            padding: 0.6rem 1.2rem;
            color: var(--text-dark);
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .dropdown-item:hover {
            background: var(--primary-light);
        }
        
        .dropdown-item.active {
            background: var(--primary-color);
            color: white;
        }
        
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .wishlist-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid #f0f0f0;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .wishlist-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .product-image-container {
            position: relative;
            overflow: hidden;
            height: 220px;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .wishlist-card:hover .product-image {
            transform: scale(1.08);
        }
        
        .favorite-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            color: var(--danger-color);
            font-size: 1.1rem;
            transition: var(--transition);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(231, 76, 60, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(231, 76, 60, 0);
            }
        }
        
        .price-badge {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: var(--primary-color);
            color: white;
            border-radius: 20px;
            padding: 0.4rem 0.8rem;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }
        
        .product-info {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-category {
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.8rem;
        }
        
        .product-name {
            font-size: 1.3rem;
            margin-bottom: 0.8rem;
            color: var(--text-dark);
            font-weight: 600;
            line-height: 1.3;
        }
        
        .product-seller {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .product-seller i {
            color: var(--primary-color);
        }
        
        .card-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #f0f0f0;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        
        .button-group {
            display: flex;
            gap: 0.8rem;
        }
        
        .view-button, 
        .remove-button {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.8rem 0;
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            border: none;
        }
        
        .view-button {
            background: var(--primary-color);
            color: white;
        }
        
        .view-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .remove-button {
            background: white;
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        .remove-button:hover {
            background: var(--danger-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .favorited-date {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-align: center;
        }
        
        .empty-wishlist {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: var(--border-radius);
            grid-column: 1 / -1;
            box-shadow: var(--shadow-sm);
        }
        
        .empty-wishlist i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            display: block;
        }
        
        .empty-wishlist h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .empty-wishlist p {
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .browse-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .browse-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-container {
            background: white;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        
        .modal-overlay.active .modal-container {
            transform: translateY(0);
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--text-dark);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
        }
        
        .modal-body {
            margin-bottom: 1.5rem;
        }
        
        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .modal-btn {
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: var(--transition);
        }
        
        .modal-btn-cancel {
            background: var(--secondary-color);
            color: var(--text-dark);
        }
        
        .modal-btn-cancel:hover {
            background: #e9e9e9;
        }
        
        .modal-btn-confirm {
            background: var(--danger-color);
            color: white;
        }
        
        .modal-btn-confirm:hover {
            background: var(--danger-dark);
        }
        
        /* Loading Spinner */
        .spinner {
            width: 40px;
            height: 40px;
            margin: 100px auto;
            background-color: var(--primary-color);
            border-radius: 100%;  
            animation: sk-scaleout 1.0s infinite ease-in-out;
        }
        
        @keyframes sk-scaleout {
            0% { 
                transform: scale(0);
            } 100% {
                transform: scale(1.0);
                opacity: 0;
            }
        }
        
        .wishlist-loading {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 1.5rem;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-sorting {
                width: 100%;
                justify-content: space-between;
            }
        }
        
        @media (max-width: 480px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
            
            .product-image-container {
                height: 180px;
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
                    <ul class="nav-links">
                        <li><a href="../farm-website-homepage.php">Home</a></li>
                        <li><a href="../about.php">About</a></li>
                        <li><a href="../learning-center/index.php">Learning Center</a></li>
                        <li><a href="../marketplace/marketplace.php">Marketplace</a></li>
                        <li><a href="#">Community</a></li>
                        <li><a href="#">News</a></li>
                    </ul>
                </div>
                <div class="nav-right">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                        <div class="user-dropdown">
                            <ul>
                                <li><a href="user-profile.php"><i class="fas fa-user"></i> My Account</a></li>
                                <li><a href="sse_messaging.php"><i class="fas fa-envelope"></i> Messages</a></li>
                                <li><a href="wishlist.php" class="active"><i class="fas fa-heart"></i> Wishlist</a></li>
                                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <section class="page-header">
        <div class="container">
            <div class="header-content">
                <h1><i class="fas fa-heart"></i> My Wishlist</h1>
                
                <?php if (!empty($wishlist_items)): ?>
                <div class="filter-sorting">
                    <div class="filter-dropdown">
                        <button class="dropdown-toggle" id="filterDropdown">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <div class="dropdown-menu" id="filterMenu">
                            <a class="dropdown-item active" data-filter="all">All Products</a>
                            <?php
                            $categories = [];
                            foreach ($wishlist_items as $item) {
                                if (!in_array($item['category'], $categories)) {
                                    $categories[] = $item['category'];
                                }
                            }
                            foreach ($categories as $category):
                            ?>
                            <a class="dropdown-item" data-filter="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="sort-dropdown">
                        <button class="dropdown-toggle" id="sortDropdown">
                            <i class="fas fa-sort"></i> Sort
                        </button>
                        <div class="dropdown-menu" id="sortMenu">
                            <a class="dropdown-item active" data-sort="date-desc">Newest First</a>
                            <a class="dropdown-item" data-sort="date-asc">Oldest First</a>
                            <a class="dropdown-item" data-sort="price-asc">Price: Low to High</a>
                            <a class="dropdown-item" data-sort="price-desc">Price: High to Low</a>
                            <a class="dropdown-item" data-sort="name-asc">Name: A-Z</a>
                            <a class="dropdown-item" data-sort="name-desc">Name: Z-A</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <main class="container">
        <div class="wishlist-grid" id="wishlistGrid">
            <?php if (empty($wishlist_items)): ?>
                <div class="empty-wishlist">
                    <i class="far fa-heart"></i>
                    <h2>Your wishlist is empty</h2>
                    <p>Browse the marketplace and save your favorite products.</p>
                    <a href="../marketplace/marketplace.php" class="browse-button">
                        <i class="fas fa-shopping-bag"></i> Browse Products
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="wishlist-card" 
                         data-category="<?= htmlspecialchars($item['category']) ?>"
                         data-price="<?= $item['price'] ?>"
                         data-name="<?= htmlspecialchars($item['name']) ?>"
                         data-date="<?= strtotime($item['favorited_at']) ?>">
                        <div class="product-image-container">
                            <div class="favorite-badge">
                                <i class="fas fa-heart"></i>
                            </div>
                            <span class="price-badge">$<?= number_format($item['price'], 2) ?></span>
                            <img src="<?= strpos($item['image_url'], 'http') === 0 ? htmlspecialchars($item['image_url']) : $baseURL . htmlspecialchars($item['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="product-image">
                        </div>
                        <div class="product-info">
                            <span class="product-category"><?= htmlspecialchars($item['category']) ?></span>
                            <h3 class="product-name"><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="product-seller"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($item['seller_name']) ?></p>
                        </div>
                        <div class="card-footer">
                            <div class="button-group">
                                <a href="../marketplace/product_details.php?id=<?= $item['id'] ?>" class="view-button">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <button class="remove-button" data-product-id="<?= $item['id'] ?>" onclick="showRemoveConfirmation(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['name'])) ?>')">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                            <p class="favorited-date">Added on <?= date('F j, Y', strtotime($item['favorited_at'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="removeConfirmationModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Remove from Wishlist</h3>
                <button class="modal-close" id="modalClose">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove <strong id="productName"></strong> from your wishlist?</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" id="cancelRemove">Cancel</button>
                <button class="modal-btn modal-btn-confirm" id="confirmRemove">Remove</button>
            </div>
        </div>
    </div>

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
                        <li><a href="../farm-website-homepage.php">Home</a></li>
                        <li><a href="../about.php">About Us</a></li>
                        <li><a href="../learning-center/index.php">Learning Center</a></li>
                        <li><a href="../marketplace/marketplace.php">Marketplace</a></li>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter dropdown
            const filterDropdown = document.getElementById('filterDropdown');
            const filterMenu = document.getElementById('filterMenu');
            
            if (filterDropdown) {
                filterDropdown.addEventListener('click', function() {
                    filterMenu.classList.toggle('show');
                    sortMenu.classList.remove('show');
                });
            }
            
            // Sort dropdown
            const sortDropdown = document.getElementById('sortDropdown');
            const sortMenu = document.getElementById('sortMenu');
            
            if (sortDropdown) {
                sortDropdown.addEventListener('click', function() {
                    sortMenu.classList.toggle('show');
                    filterMenu.classList.remove('show');
                });
            }
            
            // Close dropdowns when clicking outside
            window.addEventListener('click', function(event) {
                if (!event.target.matches('.dropdown-toggle') && !event.target.closest('.dropdown-menu')) {
                    const dropdowns = document.getElementsByClassName('dropdown-menu');
                    for (let i = 0; i < dropdowns.length; i++) {
                        dropdowns[i].classList.remove('show');
                    }
                }
            });
            
            // Filter functionality
            const filterItems = document.querySelectorAll('#filterMenu .dropdown-item');
            filterItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Update active state
                    filterItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update button text
                    filterDropdown.innerHTML = `<i class="fas fa-filter"></i> ${this.textContent}`;
                    
                    // Hide dropdown
                    filterMenu.classList.remove('show');
                    
                    // Apply filter
                    const filter = this.getAttribute('data-filter');
                    filterProducts(filter);
                });
            });
            
            // Sort functionality
            const sortItems = document.querySelectorAll('#sortMenu .dropdown-item');
            sortItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Update active state
                    sortItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update button text
                    sortDropdown.innerHTML = `<i class="fas fa-sort"></i> ${this.textContent}`;
                    
                    // Hide dropdown
                    sortMenu.classList.remove('show');
                    
                    // Apply sort
                    const sort = this.getAttribute('data-sort');
                    sortProducts(sort);
                });
            });
            
            // Filter products function
            function filterProducts(filter) {
                const cards = document.querySelectorAll('.wishlist-card');
                
                cards.forEach(card => {
                    if (filter === 'all' || card.getAttribute('data-category') === filter) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
            
            // Sort products function
            function sortProducts(sort) {
                const grid = document.getElementById('wishlistGrid');
                const cards = Array.from(grid.querySelectorAll('.wishlist-card'));
                
                // Sort the cards based on the selected sort option
                cards.sort((a, b) => {
                    switch(sort) {
                        case 'date-desc':
                            return b.getAttribute('data-date') - a.getAttribute('data-date');
                        case 'date-asc':
                            return a.getAttribute('data-date') - b.getAttribute('data-date');
                        case 'price-asc':
                            return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
                        case 'price-desc':
                            return parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price'));
                        case 'name-asc':
                            return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
                        case 'name-desc':
                            return b.getAttribute('data-name').localeCompare(a.getAttribute('data-name'));
                        default:
                            return 0;
                    }
                });
                
                // Append the sorted cards back to the grid
                cards.forEach(card => grid.appendChild(card));
            }
            
            // Remove confirmation modal
            const modal = document.getElementById('removeConfirmationModal');
            const modalClose = document.getElementById('modalClose');
            const cancelRemove = document.getElementById('cancelRemove');
            const confirmRemove = document.getElementById('confirmRemove');
            const productNameElement = document.getElementById('productName');
            
            let currentProductId = null;
            
            // Show modal with product info
            window.showRemoveConfirmation = function(productId, productName) {
                currentProductId = productId;
                productNameElement.textContent = productName;
                modal.classList.add('active');
            }
            
            // Close modal
            modalClose.addEventListener('click', function() {
                modal.classList.remove('active');
            });
            
            cancelRemove.addEventListener('click', function() {
                modal.classList.remove('active');
            });
            
            // Confirm removal
            confirmRemove.addEventListener('click', function() {
                if (currentProductId) {
                    removeFromWishlist(currentProductId);
                }
                modal.classList.remove('active');
            });
            
            // Click outside to close
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
        
        // Function to remove item from wishlist
        function removeFromWishlist(productId) {
            // Find the card to remove
            const card = document.querySelector(`.wishlist-card .remove-button[data-product-id="${productId}"]`).closest('.wishlist-card');
            
            // Add loading state
            card.style.opacity = '0.6';
            card.style.pointerEvents = 'none';
            
            // Make an AJAX call to remove from wishlist
            fetch('../marketplace/api/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    action: 'remove'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add animation to remove the card
                    card.style.transition = 'all 0.3s ease';
                    card.style.transform = 'scale(0.9)';
                    card.style.opacity = '0';
                    
                    setTimeout(() => {
                        card.remove();
                        
                        // Check if there are any items left
                        const remainingItems = document.querySelectorAll('.wishlist-card');
                        if (remainingItems.length === 0) {
                            const grid = document.querySelector('.wishlist-grid');
                            grid.innerHTML = `
                                <div class="empty-wishlist">
                                    <i class="far fa-heart"></i>
                                    <h2>Your wishlist is empty</h2>
                                    <p>Browse the marketplace and save your favorite products.</p>
                                    <a href="../marketplace/marketplace.php" class="browse-button">
                                        <i class="fas fa-shopping-bag"></i> Browse Products
                                    </a>
                                </div>
                            `;
                            
                            // Hide filter and sort dropdowns
                            const filterSorting = document.querySelector('.filter-sorting');
                            if (filterSorting) {
                                filterSorting.style.display = 'none';
                            }
                        }
                    }, 300);
                } else {
                    // Show error
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Restore card state
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
                alert('An error occurred. Please try again.');
            });
        }
    </script>
</body>
</html>