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
require_once '../config/currency_config.php';

// Set default currency or get from session
$current_currency = isset($_SESSION['currency']) ? $_SESSION['currency'] : 'USD';

// Get user information
$user_id = $_SESSION['user_id'];
$query = "SELECT username FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();
$stmt->closeCursor();

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'available';
    
    // Validate required fields
    if (empty($name) || empty($description) || empty($category) || $price <= 0) {
        $error_message = "Please fill out all required fields with valid information.";
    } else {
        // File upload handling
        $image_url = '';
        
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../Image/market-product-IMG/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $error_message = "Only JPG, JPEG, PNG, and WEBP files are allowed.";
            } else {
                $new_filename = uniqid('product_') . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                    $image_url = 'Image/market-product-IMG/' . $new_filename;
                } else {
                    $error_message = "Failed to upload image. Please try again.";
                }
            }
        } else {
            $error_message = "Please select an image for your product.";
        }
        
        // If no errors, insert into database
        if (empty($error_message)) {
            try {
                $insert_query = "INSERT INTO products (name, description, price, category, image_url, status, seller_id, created_at) 
                                VALUES (:name, :description, :price, :category, :image_url, :status, :seller_id, NOW())";
                $insert_stmt = $pdo->prepare($insert_query);
                $insert_stmt->bindParam(':name', $name);
                $insert_stmt->bindParam(':description', $description);
                $insert_stmt->bindParam(':price', $price);
                $insert_stmt->bindParam(':category', $category);
                $insert_stmt->bindParam(':image_url', $image_url);
                $insert_stmt->bindParam(':status', $status);
                $insert_stmt->bindParam(':seller_id', $user_id, PDO::PARAM_INT);
                
                if ($insert_stmt->execute()) {
                    $success_message = "Product added successfully!";
                    // Clear form data after successful submission
                    $name = $description = $category = '';
                    $price = 0;
                    $status = 'available';
                } else {
                    $error_message = "Failed to add product. Please try again.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Get existing categories for dropdown
try {
    $categories_query = "SELECT DISTINCT category FROM products ORDER BY category";
    $categories_stmt = $pdo->query($categories_query);
    $existing_categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $existing_categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - FarmKnowledge Hub</title>
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

        /* Product Form Styles */
        .page-title {
            text-align: center;
            margin: 2rem 0;
            color: #2e7d32;
        }

        .product-form-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #444;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #2e7d32;
            outline: none;
            box-shadow: 0 0 0 3px rgba(46,125,50,0.1);
        }        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .currency-conversion-info {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background-color: #f5f5f5;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .live-conversion {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #2e7d32;
            font-weight: 500;
        }
        
        .conversion-rate {
            display: block;
            margin-top: 0.3rem;
            color: #666;
            font-size: 0.8rem;
        }

        .btn-primary {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-primary:hover {
            background: #1b5e20;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(46,125,50,0.2);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .image-preview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 1rem;
            border-radius: 8px;
            display: none;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 1.5rem;
            }
        }

        .back-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #2e7d32;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1.5rem;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #1b5e20;
        }

        .custom-file-upload {
            display: block;
            cursor: pointer;
        }

        .file-upload-container {
            position: relative;
            margin-top: 0.5rem;
        }

        .file-upload-btn {
            display: inline-block;
            background: #e0e0e0;
            color: #333;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .file-upload-btn:hover {
            background: #d0d0d0;
        }

        .file-name {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #555;
        }

        .required-mark {
            color: #c62828;
            margin-left: 3px;
        }

        /* Custom select styling */
        .select-wrapper {
            position: relative;
        }

        .select-wrapper::after {
            content: '\f078';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: #888;
            pointer-events: none;
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

    <main>
        <div class="container">
            <h1 class="page-title">Add New Product</h1>

            <a href="user-profile.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Profile</span>
            </a>

            <div class="product-form-card">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form action="create-product.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Product Name<span class="required-mark">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">                            <label for="price">Price (USD)<span class="required-mark">*</span></label>
                            <input type="number" class="form-control" id="price" name="price" min="0.01" step="0.01" value="<?php echo isset($price) ? htmlspecialchars($price) : ''; ?>" required>
                            <?php if($current_currency !== 'USD'): ?>
                            <div class="currency-conversion-info">
                                <div class="live-conversion">
                                    <span id="convertedPrice">
                                        <?php 
                                        if (isset($price) && $price > 0) {
                                            $converted_price = convertCurrency($price, $current_currency, $exchange_rates);
                                            echo formatPrice($converted_price, $current_currency, $currency_symbols);
                                        } else {
                                            echo $currency_symbols[$current_currency] . '0.00';
                                        }
                                        ?>
                                    </span>
                                    <small>(<?php echo $current_currency; ?>)</small>
                                </div>
                                <small class="conversion-rate">1 USD = <?php echo $exchange_rates[$current_currency]; ?> <?php echo $current_currency; ?></small>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="category">Category<span class="required-mark">*</span></label>
                            <div class="select-wrapper">
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <?php if (!empty($existing_categories)): ?>
                                        <?php foreach ($existing_categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($category) && $category === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <option value="Seeds">Seeds</option>
                                    <option value="Fertilizers">Fertilizers</option>
                                    <option value="Pesticides">Pesticides</option>
                                    <option value="Tools">Tools</option>
                                    <option value="Equipment">Equipment</option>
                                    <option value="Irrigation">Irrigation</option>
                                    <option value="Crops">Crops</option>
                                    <option value="Organic Products">Organic Products</option>
                                    <option value="Livestock">Livestock</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <div class="select-wrapper">
                                <select class="form-control" id="status" name="status">
                                    <option value="available" selected>Available</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description<span class="required-mark">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="product_image" class="custom-file-upload">Product Image<span class="required-mark">*</span></label>
                        <div class="file-upload-container">
                            <label for="product_image" class="file-upload-btn">
                                <i class="fas fa-upload"></i> Choose Image
                            </label>
                            <input type="file" id="product_image" name="product_image" style="display: none" accept="image/jpeg, image/png, image/jpg, image/webp" required>
                            <div class="file-name" id="file-name">No file chosen</div>
                        </div>
                        <img id="image-preview" class="image-preview" alt="Image Preview">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>    <script>
        // Image preview functionality
        document.getElementById('product_image').addEventListener('change', function(e) {
            const fileInput = e.target;
            const fileName = document.getElementById('file-name');
            const imagePreview = document.getElementById('image-preview');
            
            if (fileInput.files && fileInput.files[0]) {
                const file = fileInput.files[0];
                fileName.textContent = file.name;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = 'No file chosen';
                imagePreview.style.display = 'none';
            }
        });

        <?php if($current_currency !== 'USD'): ?>
        // Live currency conversion
        document.getElementById('price').addEventListener('input', function(e) {
            const usdPrice = parseFloat(e.target.value) || 0;
            const exchangeRate = <?php echo $exchange_rates[$current_currency]; ?>;
            const convertedPrice = usdPrice * exchangeRate;
            
            // Format the converted price
            let formattedPrice;
            <?php if($current_currency === 'JPY'): ?>
            formattedPrice = '<?php echo $currency_symbols[$current_currency]; ?>' + Math.round(convertedPrice).toLocaleString();
            <?php else: ?>
            formattedPrice = '<?php echo $currency_symbols[$current_currency]; ?>' + convertedPrice.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            <?php endif; ?>
            
            document.getElementById('convertedPrice').textContent = formattedPrice;
        });
        <?php endif; ?>
    </script>
</body>
</html>
