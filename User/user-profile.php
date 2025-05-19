<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$baseURL = '/agriculture-website/';

// Include database configuration
require_once '../config/db_config.php';

// Include currency configuration
require_once '../config/currency_config.php';

// Set default currency or get from session
$current_currency = isset($_SESSION['currency']) ? $_SESSION['currency'] : 'USD';

// Handle currency change
if(isset($_GET['currency']) && array_key_exists($_GET['currency'], $exchange_rates)) {
    $current_currency = $_GET['currency'];
    $_SESSION['currency'] = $current_currency;
}

// Fetch user details from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT username, email, created_at, phone_number, address FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();
$stmt->closeCursor();

// Fetch the number of products posted by the user
$product_query = "SELECT COUNT(*) AS product_count FROM products WHERE seller_id = :seller_id";
$product_stmt = $pdo->prepare($product_query);
$product_stmt->bindParam(':seller_id', $user_id, PDO::PARAM_INT);
$product_stmt->execute();
$product_count = $product_stmt->fetchColumn();
$product_stmt->closeCursor();

// Fetch all product details posted by the user
$all_products_query = "SELECT id, name, description, price, category, image_url, status, created_at FROM products WHERE seller_id = :seller_id";
$all_products_stmt = $pdo->prepare($all_products_query);
$all_products_stmt->bindParam(':seller_id', $user_id, PDO::PARAM_INT);
$all_products_stmt->execute();
$user_products = $all_products_stmt->fetchAll(PDO::FETCH_ASSOC);
$all_products_stmt->closeCursor();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>

    <title>B2B Marketplace - FarmKnowledge Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* Theme-based User Profile Page Styles */
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

.logo {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2e7d32;
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

/* Profile Container Styles */
.profile-details-section {
    margin-bottom: 2rem;
}

.profile-card {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.profile-avatar {
    font-size: 3rem;
    color: #f1c40f;
}

profile-info h2 {
    color: #2e7d32;
    margin: 0;
}

.profile-info .member-since {
    color: #888;
    font-size: 0.9rem;
}

.profile-content .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.info-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.info-label {
    font-weight: 500;
    color: #555;
    display: block;
    margin-bottom: 0.5rem;
}

.info-value {
    color: #333;
}

.update-profile-btn {
    width: 100%;
    padding: 0.8rem;
    background: #2e7d32;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.update-profile-btn:hover {
    background: #1b5e20;
}

/* Products Section Styles */
.products-section {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.products-header .header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.products-header .header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.currency-form {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8f9fa;
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

.currency-form label {
    font-weight: 500;
    color: #555;
    font-size: 0.9rem;
}

.currency-form select {
    padding: 0.3rem 0.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
}

.products-header h2 {
    color: #2e7d32;
    font-size: 1.5rem;
}

.product-count {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 500;
}

.create-product-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #2e7d32;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.3s ease;
}

.create-product-btn:hover {
    background: #1b5e20;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.product-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #eee;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.product-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.product-info {
    padding: 1rem;
}

.product-category {
    font-size: 0.8rem;
    color: #666;
    text-transform: uppercase;
}

.product-status {
    float: right;
    padding: 0.2rem 0.5rem;
    border-radius: 3px;
    font-size: 0.8rem;
    font-weight: 500;
}

.product-status.active {
    background: #e8f5e9;
    color: #2e7d32;
}

.product-status.inactive {
    background: #ffebee;
    color: #c62828;
}

.product-name {
    margin: 0.5rem 0;
    font-size: 1.1rem;
    color: #333;
}

.product-price {
    color: #2e7d32;
    font-weight: 600;
    font-size: 1.2rem;
    margin: 0.5rem 0;
}

.original-price {
    font-size: 0.8rem;
    font-weight: normal;
    color: #777;
    margin-left: 0.5rem;
}

.product-date {
    font-size: 0.8rem;
    color: #888;
}

.no-products {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.product-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    border-top: 1px solid #eee;
    padding-top: 1rem;
}

.action-btn {
    padding: 0.5rem 0.8rem;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    flex: 1;
    transition: all 0.2s ease;
}

.edit-btn {
    background-color: #e3f2fd;
    color: #1976d2;
    border: 1px solid #bbdefb;
}

.edit-btn:hover {
    background-color: #bbdefb;
}

.delete-btn {
    background-color: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}

.delete-btn:hover {
    background-color: #ffcdd2;
}

/* Filter Section Styles */
.product-filters {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
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

.filter-group button {
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

.filter-group button:hover {
    background: #1b5e20;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
}

.main-content {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-content .info-grid {
        grid-template-columns: 1fr;
    }
    
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .main-content {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
<header>
        <div class="container">
            <nav>
                <div class="logo">FarmKnowledge</div>
                <ul class="nav-links">
                        <li><a href="../farm-website-homepage.php">Home</a></li>
                        <li><a href="../about.php">About</a></li>
                        <li><a href="../learning-center/index.php">Learning Center</a></li>
                        <li><a href="../marketplace/marketplace.php">Marketplace</a></li>
                        <li><a href="#">Community</a></li>
                        <li><a href="messages.php">Messages</a></li>
                    </ul>
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
            </nav>
        </div>
    </header>
    <br>
    <main>
        <div class="container">
            <!-- User Profile Details Section -->
            <div class="profile-details-section">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                            <p class="member-since">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="profile-content">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-phone"></i> Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['phone_number']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-map-marker-alt"></i> Address</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['address']); ?></span>
                            </div>
                        </div>
                        <form action="update-profile.php" method="POST">
                            <button type="submit" class="update-profile-btn">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="products-section">                <div class="products-header">
                    <div class="header-left">
                        <h2>Your Products</h2>
                        <span class="product-count"><?php echo $product_count; ?> Products</span>
                    </div>
                    <div class="header-right">
                        <form id="currencyForm" class="currency-form" method="GET">
                            <label for="currency">Currency:</label>
                            <select name="currency" id="currency" onchange="this.form.submit()">
                                <?php foreach($exchange_rates as $code => $rate): ?>
                                    <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($current_currency === $code) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($code); ?> (<?php echo $currency_symbols[$code]; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <!-- Preserve any existing filters when changing currency -->
                            <?php 
                                foreach($_GET as $key => $value) {
                                    if($key !== 'currency' && !empty($value)) {
                                        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                                    }
                                }
                            ?>
                        </form>
                        <a href="create-product.php" class="create-product-btn">
                            <i class="fas fa-plus"></i>
                            <span>Add Product</span>
                        </a>
                    </div>
                </div>

                <div class="main-content">
                    <!-- Filter Sidebar -->
                    <div class="product-filters">
                        <form class="filter-form" method="GET">
                            <div class="filter-group">
                                <label for="search"><i class="fas fa-search"></i> Search by Name</label>
                                <input type="text" id="search" name="search" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" placeholder="Search products...">
                            </div>

                            <div class="filter-group">
                                <label for="category"><i class="fas fa-tags"></i> Category</label>
                                <select name="category" id="category">
                                    <option value="">All Categories</option>
                                    <?php
                                    $categories = array_unique(array_column($user_products, 'category'));
                                    foreach($categories as $cat):
                                    ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"<?= isset($_GET['category']) && $_GET['category'] === $cat ? ' selected' : '' ?>>
                                        <?= htmlspecialchars($cat) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="status"><i class="fas fa-check-circle"></i> Status</label>
                                <select name="status" id="status">
                                    <option value="">All Status</option>
                                    <option value="available"<?= isset($_GET['status']) && $_GET['status'] === 'available' ? ' selected' : '' ?>>Available</option>
                                    <option value="sold"<?= isset($_GET['status']) && $_GET['status'] === 'sold' ? ' selected' : '' ?>>Sold</option>
                                    <option value="inactive"<?= isset($_GET['status']) && $_GET['status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="price_min"><i class="fas fa-dollar-sign"></i> Min Price</label>
                                <input type="number" id="price_min" name="price_min" min="0" value="<?= isset($_GET['price_min']) ? htmlspecialchars($_GET['price_min']) : '' ?>" placeholder="Min Price">
                            </div>

                            <div class="filter-group">
                                <label for="price_max"><i class="fas fa-dollar-sign"></i> Max Price</label>
                                <input type="number" id="price_max" name="price_max" min="0" value="<?= isset($_GET['price_max']) ? htmlspecialchars($_GET['price_max']) : '' ?>" placeholder="Max Price">
                            </div>

                            <div class="filter-group">
                                <button type="submit">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Product Grid -->
                    <?php if (!empty($user_products)): ?>
                        <div class="product-grid">
                            <?php 
                            $filtered_products = $user_products;
                            
                            // Apply search filter
                            if (isset($_GET['search']) && !empty($_GET['search'])) {
                                $search_term = strtolower($_GET['search']);
                                $filtered_products = array_filter($filtered_products, function($product) use ($search_term) {
                                    return stripos(strtolower($product['name']), $search_term) !== false;
                                });
                            }
                            
                            // Apply other filters
                            if (isset($_GET['category']) && !empty($_GET['category'])) {
                                $filtered_products = array_filter($filtered_products, function($product) {
                                    return $product['category'] === $_GET['category'];
                                });
                            }
                            
                            if (isset($_GET['status']) && !empty($_GET['status'])) {
                                $filtered_products = array_filter($filtered_products, function($product) {
                                    return strtolower($product['status']) === strtolower($_GET['status']);
                                });
                            }
                            
                            if (isset($_GET['price_min']) && !empty($_GET['price_min'])) {
                                $filtered_products = array_filter($filtered_products, function($product) {
                                    return floatval($product['price']) >= floatval($_GET['price_min']);
                                });
                            }
                            
                            if (isset($_GET['price_max']) && !empty($_GET['price_max'])) {
                                $filtered_products = array_filter($filtered_products, function($product) {
                                    return floatval($product['price']) <= floatval($_GET['price_max']);
                                });
                            }
                            
                            foreach ($filtered_products as $product): 
                            ?>                                <div class="product-card">
                                    <img src="<?= strpos($product['image_url'], 'http') === 0 ? htmlspecialchars($product['image_url']) : $baseURL . htmlspecialchars($product['image_url']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         class="product-image">
                                    <div class="product-info">
                                        <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                                        <span class="product-status <?php echo strtolower($product['status']); ?>"><?php echo htmlspecialchars($product['status']); ?></span>
                                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>                                        <p class="product-date"><i class="fas fa-calendar-alt"></i> Posted on <?php echo date('M d, Y', strtotime($product['created_at'])); ?></p>
                                        <p class="product-price">
                                            <?php 
                                            $converted_price = convertCurrency($product['price'], $current_currency, $exchange_rates);
                                            echo formatPrice($converted_price, $current_currency, $currency_symbols); 
                                            ?>
                                            <?php if($current_currency !== 'USD'): ?>
                                                <span class="original-price">($<?php echo number_format($product['price'], 2); ?> USD)</span>
                                            <?php endif; ?>
                                        </p>
                                        <div class="product-actions">
                                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="action-btn edit-btn">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="delete-product.php?id=<?php echo $product['id']; ?>" class="action-btn delete-btn">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-products">
                            <p>You haven't posted any products yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>