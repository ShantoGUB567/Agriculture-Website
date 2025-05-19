<?php
session_start();
require_once '../../config/db_config.php';

// Enable error display temporarily for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to perform this action']);
    exit;
}

// Ensure proper JSON response
header('Content-Type: application/json');

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);
    
    if (!$data || !isset($data['product_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request: ' . $jsonInput]);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $product_id = (int)$data['product_id'];
    $action = isset($data['action']) ? $data['action'] : 'toggle';
    
    try {
        // Check if wishlist table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'wishlist'");
        if ($tableCheck->rowCount() == 0) {
            // Wishlist table doesn't exist, create it
            $sql = file_get_contents('../../database/wishlist.sql');
            $pdo->exec($sql);
            
            echo json_encode([
                'success' => false, 
                'message' => 'Wishlist table was missing and has been created. Please try again.',
                'debug' => 'Created wishlist table'
            ]);
            exit;
        }
        
        // Check if product exists
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        if (!$stmt->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Product not found', 
                'debug' => ['product_id' => $product_id]
            ]);
            exit;
        }
        
        // Check if product is already in wishlist
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $exists = $stmt->fetch();
        
        if ($action === 'add' || ($action === 'toggle' && !$exists)) {
            // Add to wishlist
            if (!$exists) {
                $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                $result = $stmt->execute([$user_id, $product_id]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'status' => 'added', 'message' => 'Product added to wishlist']);
                } else {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Failed to insert record', 
                        'debug' => ['error_info' => $stmt->errorInfo()]
                    ]);
                }
            } else {
                echo json_encode(['success' => true, 'status' => 'exists', 'message' => 'Product already in wishlist']);
            }
        } else if ($action === 'remove' || ($action === 'toggle' && $exists)) {
            // Remove from wishlist
            if ($exists) {
                $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                $result = $stmt->execute([$user_id, $product_id]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'status' => 'removed', 'message' => 'Product removed from wishlist']);
                } else {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Failed to delete record', 
                        'debug' => ['error_info' => $stmt->errorInfo()]
                    ]);
                }
            } else {
                echo json_encode(['success' => true, 'status' => 'not_exists', 'message' => 'Product not in wishlist']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage(),
            'debug' => [
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'General error: ' . $e->getMessage(),
            'debug' => [
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]
        ]);
    }
}

// Handle GET requests to retrieve wishlist items
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if wishlist table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'wishlist'");
        if ($tableCheck->rowCount() == 0) {
            // Return empty array if table doesn't exist
            echo json_encode(['success' => true, 'items' => []]);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT p.*, w.created_at as favorited_at, u.username as seller_name
            FROM wishlist w
            JOIN products p ON w.product_id = p.id
            JOIN users u ON p.seller_id = u.id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $wishlist_items = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'items' => $wishlist_items]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage(),
            'debug' => [
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]
        ]);
    }
}
?>