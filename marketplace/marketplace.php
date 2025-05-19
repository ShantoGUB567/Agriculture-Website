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

// Helper function to check if a product is in the user's wishlist
function checkWishlist($pdo, $userId, $productId) {
    $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
    $stmt->execute([$userId, $productId]);
    return $stmt->fetchColumn() ? true : false;
}

// Get user data if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Get the number of products per page from the user or use the default value
$productsPerPage = isset($_GET['products_per_page']) && is_numeric($_GET['products_per_page']) ? (int)$_GET['products_per_page'] : 9;

// Base query
$query = "SELECT DISTINCT p.*, u.username as seller_name 
          FROM products p 
          JOIN users u ON p.seller_id = u.id 
          WHERE p.status = 'available'";

// Add filters
if (!empty($category)) {
    $query .= " AND p.category = :category";
}
if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
}

// Add sorting
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    default:
        $query .= " ORDER BY p.name ASC";
}

// Pagination setup
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $productsPerPage;

// Add LIMIT and OFFSET to the query
$query .= " LIMIT :limit OFFSET :offset";

try {
    $stmt = $pdo->prepare($query);
    
    // Bind parameters
    if (!empty($category)) {
        $stmt->bindParam(':category', $category);
    }
    if (!empty($search)) {
        $search_param = "%$search%";
        $stmt->bindParam(':search', $search_param);
    }
    $stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $products = $stmt->fetchAll();

    // Get total product count for pagination
    $countQuery = "SELECT COUNT(*) FROM products p WHERE p.status = 'available'";
    if (!empty($category)) {
        $countQuery .= " AND p.category = :category";
    }
    if (!empty($search)) {
        $countQuery .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    }
    $countStmt = $pdo->prepare($countQuery);
    if (!empty($category)) {
        $countStmt->bindParam(':category', $category);
    }
    if (!empty($search)) {
        $countStmt->bindParam(':search', $search_param);
    }
    $countStmt->execute();
    $totalProducts = $countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $productsPerPage);

    // Get distinct categories for filter
    $categories = $pdo->query("SELECT DISTINCT category FROM products WHERE status = 'available' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2B Marketplace - FarmKnowledge Hub</title>
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
    
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(46, 125, 50, 0.9), rgba(27, 94, 32, 0.9)), url('../Image/hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero p {
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.9;
        }
        
        /* Filters Section */
        .filters {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .filters form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-weight: 500;
            color: #444;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            border-color: #2e7d32;
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }
        
        button {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        button:hover {
            background: #1b5e20;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
        }
        
        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
            cursor: pointer;
            position: relative; /* Added for absolute positioning of wishlist button */
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 100%;
            height: 240px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        .product-image.no-image {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 240px;
            background-color: #f0f0f0;
            color: #666;
            font-size: 1.2rem;
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-category {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.8rem;
        }
        
        .product-name {
            font-size: 1.3rem;
            margin-bottom: 0.8rem;
            color: #222;
            font-weight: 600;
        }
          .product-price {
            font-size: 1.5rem;
            color: #2e7d32;
            font-weight: 700;
            margin: 0.8rem 0;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .original-price {
            font-size: 0.8rem;
            font-weight: normal;
            color: #777;
            margin-left: 0.5rem;
        }
        
        .product-seller {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .product-seller i {
            color: #2e7d32;
        }
        
        .wishlist-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 1.5rem;
            z-index: 10; /* Ensure it's above other elements */
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .wishlist-btn:hover {
            background: rgba(255, 255, 255, 1);
            transform: scale(1.1);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }
        
        .wishlist-btn .far {
            color: #999; /* Unfavorited color */
        }
        
        .wishlist-btn .fas {
            color: #e74c3c; /* Favorited color - bright red */
        }
        
        .wishlist-btn:hover .far {
            color: #666;
        }
        
        .wishlist-btn:hover .fas {
            color: #c0392b;
        }
        
        .wishlist-btn.loading {
            opacity: 0.7;
        }
        
        .no-products {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            grid-column: 1 / -1;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .no-products h2 {
            color: #2e7d32;
            margin-bottom: 1rem;
        }
        
        .no-products p {
            color: #666;
        }
        
        @media (max-width: 768px) {
            .hero {
                padding: 3rem 0;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .filters form {
                grid-template-columns: 1fr;
            }
            
            .product-grid {
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

        /* Pagination Styles */
        .pagination-links {
            display: flex;
            justify-content: center;
            list-style: none;
            padding: 0;
            margin: 2rem 0;
            gap: 0.5rem;
        }

        .pagination-links li {
            display: inline;
        }

        .pagination-links a {
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid #2e7d32;
            border-radius: 5px;
            color: #2e7d32;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .pagination-links a.active,
        .pagination-links a:hover {
            background-color: #2e7d32;
            color: #fff;
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
                        <li><a href="marketplace.php" class="active">Marketplace</a></li>
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

    <section class="hero">
        <div class="container">
            <h1>Agricultural B2B Marketplace</h1>
            <p>Connect with trusted suppliers and buyers in the agricultural industry</p>
        </div>
    </section>
    
    <main class="container">
        <section class="filters">
            <form action="" method="GET">
                <div class="filter-group">
                    <label for="category"><i class="fas fa-tags"></i> Category</label>
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search"><i class="fas fa-search"></i> Search</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products...">
                </div>
                
                <div class="filter-group">
                    <label for="sort"><i class="fas fa-sort"></i> Sort By</label>
                    <select name="sort" id="sort">
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
                    </select>
                </div>                <div class="filter-group">
                    <label for="products_per_page"><i class="fas fa-list"></i> Products Per Page</label>
                    <select name="products_per_page" id="products_per_page">
                        <option value="9" <?= $productsPerPage === 9 ? 'selected' : '' ?>>Default</option>
                        <option value="20" <?= $productsPerPage === 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $productsPerPage === 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $productsPerPage === 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
                  <div class="filter-group">
                    <label for="currency"><i class="fas fa-money-bill"></i> Currency</label>
                    <select name="currency" id="currency" onchange="this.form.submit()">
                        <?php foreach($exchange_rates as $code => $rate): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($current_currency === $code) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($code); ?> (<?php echo $currency_symbols[$code]; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit"><i class="fas fa-filter"></i> Apply Filters</button>
                </div>
            </form>
        </section>
        
        <section class="product-grid">
            <?php if (empty($products)): ?>
                <div class="no-products">
                    <h2>No products found</h2>
                    <p>Try adjusting your filters or search criteria</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card" onclick="window.location.href='product_details.php?id=<?= $product['id'] ?>'">
                        <?php if(isset($_SESSION['user_id'])): ?>
                        <button class="wishlist-btn" data-product-id="<?= $product['id'] ?>" onclick="toggleWishlist(event, <?= $product['id'] ?>)">
                            <i class="<?= checkWishlist($pdo, $_SESSION['user_id'], $product['id']) ? 'fas' : 'far' ?> fa-heart"></i>
                        </button>
                        <?php endif; ?>
                        <img src="<?= strpos($product['image_url'], 'http') === 0 ? htmlspecialchars($product['image_url']) : $baseURL . htmlspecialchars($product['image_url']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="product-image">
                        <div class="product-info">
                            <span class="product-category"><?= htmlspecialchars($product['category']) ?></span>                            <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="product-seller"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($product['seller_name']) ?></p>
                            <p class="product-price">
                                <?php 
                                $converted_price = convertCurrency($product['price'], $current_currency, $exchange_rates);
                                echo formatPrice($converted_price, $current_currency, $currency_symbols); 
                                ?>
                                <?php if($current_currency !== 'USD'): ?>
                                    <span class="original-price">($<?php echo number_format($product['price'], 2); ?> USD)</span>
                                <?php endif; ?>
                            </p>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- Pagination Links -->
        <section class="pagination">
            <div class="container">
                <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination-links">
                            <?php if ($page > 1): ?>
                                <li><a href="?page=<?= $page - 1 ?>&products_per_page=<?= $productsPerPage ?>&category=<?= htmlspecialchars($category) ?>&search=<?= htmlspecialchars($search) ?>&sort=<?= htmlspecialchars($sort) ?>">Previous</a></li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li><a href="?page=<?= $i ?>&products_per_page=<?= $productsPerPage ?>&category=<?= htmlspecialchars($category) ?>&search=<?= htmlspecialchars($search) ?>&sort=<?= htmlspecialchars($sort) ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <li><a href="?page=<?= $page + 1 ?>&products_per_page=<?= $productsPerPage ?>&category=<?= htmlspecialchars($category) ?>&search=<?= htmlspecialchars($search) ?>&sort=<?= htmlspecialchars($sort) ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
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
                        <li><a href="farm-website-homepage.html">Home</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Learning Center</a></li>
                        <li><a href="marketplace.php">Marketplace</a></li>
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
    
    <script>
        // Load favorite status for all products when page loads or after AJAX navigation
        function loadWishlistStatus() {
            if (!document.querySelector('.wishlist-btn')) return; // No wishlist buttons
            
            // Show loading indicator
            document.querySelectorAll('.wishlist-btn').forEach(btn => {
                btn.classList.add('loading');
            });
            
            // Call the API to get all wishlist items
            fetch('api/wishlist.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.items) {
                        // Create a set of product IDs in the wishlist for quick lookup
                        const wishlistProductIds = new Set(data.items.map(item => item.id));
                        
                        // Update all wishlist buttons
                        document.querySelectorAll('.wishlist-btn').forEach(btn => {
                            const productId = btn.getAttribute('data-product-id');
                            const heartIcon = btn.querySelector('i');
                            
                            if (wishlistProductIds.has(parseInt(productId))) {
                                heartIcon.classList.remove('far');
                                heartIcon.classList.add('fas');
                            } else {
                                heartIcon.classList.remove('fas');
                                heartIcon.classList.add('far');
                            }
                            
                            btn.classList.remove('loading');
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading wishlist status:', error);
                    // Remove loading indicator
                    document.querySelectorAll('.wishlist-btn').forEach(btn => {
                        btn.classList.remove('loading');
                    });
                });
        }

        function toggleWishlist(event, productId) {
            // Prevent the click from navigating to the product details
            event.stopPropagation();
            event.preventDefault();
            
            // Change the cursor to indicate loading
            const button = event.currentTarget;
            const heartIcon = button.querySelector('i');
            button.style.cursor = 'wait';
            
            console.log("Toggling wishlist for product ID:", productId);
            
            // Make an AJAX call to add/remove from wishlist
            fetch('api/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    action: 'toggle'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                // Reset cursor
                button.style.cursor = 'pointer';
                
                if (data.success) {
                    // Debug info
                    console.log("Success response:", data);
                    
                    // Toggle the heart icon class
                    if (data.status === 'added') {
                        heartIcon.classList.remove('far');
                        heartIcon.classList.add('fas');
                    } else {
                        heartIcon.classList.remove('fas');
                        heartIcon.classList.add('far');
                    }
                } else {
                    // If the table was created and we need to try again
                    if (data.debug === 'Created wishlist table') {
                        // Try again after a short delay
                        setTimeout(() => toggleWishlist(event, productId), 500);
                        return;
                    }
                    
                    console.error('Error:', data.message, data.debug || '');
                    alert(data.message || 'An error occurred. Please try again.');
                }
            })
            .catch(error => {
                // Reset cursor
                button.style.cursor = 'pointer';
                
                console.error('Error:', error);
                alert('Failed to connect to the server. Please check your connection and try again.');
            });
        }
        
        // Call the function when page loads
        document.addEventListener('DOMContentLoaded', loadWishlistStatus);
        
        // Update wishlist status when filters are applied or pagination is used
        document.querySelectorAll('.filters form, .pagination-links a').forEach(element => {
            element.addEventListener('click', function() {
                // Delayed call to ensure the DOM has updated
                setTimeout(loadWishlistStatus, 500);
            });
        });
    </script>
</body>
</html>