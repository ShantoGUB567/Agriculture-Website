<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Include database configuration
require_once '../config/db_config.php';

$user_id = $_SESSION['user_id'];
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
$error = '';
$success = '';

// Verify that the product exists and belongs to the current user
if ($product_id > 0) {
    $product_query = "SELECT * FROM products WHERE id = :id AND seller_id = :seller_id";
    $product_stmt = $pdo->prepare($product_query);
    $product_stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $product_stmt->bindParam(':seller_id', $user_id, PDO::PARAM_INT);
    $product_stmt->execute();
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    $product_stmt->closeCursor();

    if (!$product) {
        // Product doesn't exist or doesn't belong to current user
        header('Location: user-profile.php');
        exit();
    }

    if ($confirm) {
        try {
            // Get the image URL before deleting
            $image_url = $product['image_url'];
            
            // Delete the product from the database
            $delete_query = "DELETE FROM products WHERE id = :id AND seller_id = :seller_id";
            $delete_stmt = $pdo->prepare($delete_query);
            $delete_stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
            $delete_stmt->bindParam(':seller_id', $user_id, PDO::PARAM_INT);
            
            if ($delete_stmt->execute()) {
                // Delete the image file if it exists and isn't an external URL
                if (!empty($image_url) && strpos($image_url, 'http') !== 0) {
                    $image_path = '../' . $image_url;
                    if (file_exists($image_path)) {
                        @unlink($image_path);
                    }
                }
                
                $success = "Product deleted successfully!";
            } else {
                $error = "Failed to delete product. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
} else {
    // Invalid product ID
    header('Location: user-profile.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Product - FarmKnowledge Hub</title>
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

        .page-title {
            text-align: center;
            margin: 2rem 0;
            color: #2e7d32;
        }

        .delete-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto 2rem;
            text-align: center;
        }

        .product-info {
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            text-align: left;
        }

        .product-info h3 {
            margin-bottom: 0.5rem;
            color: #2e7d32;
        }

        .product-info p {
            margin-bottom: 0.5rem;
        }

        .product-image {
            max-width: 100%;
            max-height: 200px;
            margin: 1rem 0;
            border-radius: 8px;
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

        .warning-text {
            color: #c62828;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-width: 120px;
        }

        .btn-danger {
            background: #c62828;
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background: #b71c1c;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(198, 40, 40, 0.2);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
            border: none;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
            justify-content: center;
            margin-top: 2rem;
        }

        .back-link:hover {
            color: #1b5e20;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1 class="page-title">Delete Product</h1>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="delete-card">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                    <a href="user-profile.php" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        <span>Return to Profile</span>
                    </a>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                    <div class="button-group">
                        <a href="delete-product.php?id=<?php echo $product_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Try Again
                        </a>
                        <a href="user-profile.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Profile
                        </a>
                    </div>
                <?php else: ?>
                    <h2>Are you sure you want to delete this product?</h2>
                    <p class="warning-text">This action cannot be undone!</p>
                    
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                        <p><strong>Price:</strong> $<?php echo htmlspecialchars($product['price']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($product['status']); ?></p>
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?= strpos($product['image_url'], 'http') === 0 ? htmlspecialchars($product['image_url']) : '../' . htmlspecialchars($product['image_url']) ?>" 
                                alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        <?php endif; ?>
                    </div>
                    
                    <div class="button-group">
                        <a href="user-profile.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <a href="delete-product.php?id=<?php echo $product_id; ?>&confirm=yes" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
