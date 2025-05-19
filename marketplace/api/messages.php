<?php
session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle POST request to send a message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $receiver_id = isset($data['receiver_id']) ? (int)$data['receiver_id'] : 0;
    $message = isset($data['message']) ? trim($data['message']) : '';
    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : null;
    $product_name = isset($data['product_name']) ? trim($data['product_name']) : null;
    
    if (empty($receiver_id) || empty($message)) {
        echo json_encode(['error' => 'Invalid request parameters']);
        exit;
    }
    
    try {
        // If product info is provided, add it to the message
        if ($product_id && $product_name) {
            $product_link = "<a href='../marketplace/product_details.php?id={$product_id}' target='_blank'>{$product_name}</a>";
            $message = "About product: {$product_link}\n\n{$message}";
        }
        
        // Insert message into database
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $receiver_id, $message]);
        
        echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
// Handle GET request to fetch messages
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $contact_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    if (empty($contact_id)) {
        echo json_encode(['error' => 'Contact ID not specified']);
        exit;
    }
    
    try {
        // Get all messages between current user and contact
        $stmt = $pdo->prepare("SELECT * FROM messages 
                             WHERE (sender_id = ? AND receiver_id = ?) 
                             OR (sender_id = ? AND receiver_id = ?) 
                             ORDER BY created_at ASC");
        $stmt->execute([$user_id, $contact_id, $contact_id, $user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark messages as read
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 
                             WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->execute([$contact_id, $user_id]);
        
        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Method not allowed']);
}
?>