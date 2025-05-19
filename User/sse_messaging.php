<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Include database configuration
require_once '../config/db_config.php';

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Helper function to format message previews
function format_message_preview($message, $length = 50) {
    // Strip HTML tags
    $text = strip_tags($message);
    // Truncate if longer than specified length
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length) . '...';
    }
    return $text;
}

// Handle conversation deletion
$deletion_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_conversation'])) {
    $contact_id = isset($_POST['contact_id']) ? (int)$_POST['contact_id'] : 0;
    
    if (!empty($contact_id)) {
        try {
            // Delete all messages between the user and the contact
            $stmt = $pdo->prepare("DELETE FROM messages 
                                  WHERE (sender_id = ? AND receiver_id = ?)
                                  OR (sender_id = ? AND receiver_id = ?)");
            $stmt->execute([$user_id, $contact_id, $contact_id, $user_id]);
            
            $deletion_success = true;
        } catch(PDOException $e) {
            $error_message = 'Failed to delete conversation: ' . $e->getMessage();
        }
    }
}

// Handle sending a new message
$message_sent = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    if (empty($receiver_id) || empty($message)) {
        $error_message = 'All fields are required.';
    } else {
        try {
            // Save message to database
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, $receiver_id, $message]);
            
            $message_sent = true;
        } catch(PDOException $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get all conversations for the current user
$query = "SELECT DISTINCT 
            CASE 
                WHEN m.sender_id = :user_id THEN m.receiver_id
                ELSE m.sender_id
            END as contact_id,
            u.username as contact_name,
            (SELECT message FROM messages 
             WHERE ((sender_id = :user_id AND receiver_id = contact_id) OR (sender_id = contact_id AND receiver_id = :user_id)) 
             ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM messages 
             WHERE ((sender_id = :user_id AND receiver_id = contact_id) OR (sender_id = contact_id AND receiver_id = :user_id)) 
             ORDER BY created_at DESC LIMIT 1) as last_message_time,
            (SELECT COUNT(*) FROM messages 
             WHERE sender_id = contact_id AND receiver_id = :user_id AND is_read = 0) as unread_count
          FROM messages m
          JOIN users u ON u.id = 
            CASE 
                WHEN m.sender_id = :user_id THEN m.receiver_id
                ELSE m.sender_id
            END
          WHERE m.sender_id = :user_id OR m.receiver_id = :user_id
          GROUP BY contact_id, contact_name
          ORDER BY last_message_time DESC";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$conversations = $stmt->fetchAll();

// If a contact is selected, get all messages with that contact
$selected_contact = null;
$messages = [];

if (isset($_GET['contact']) && is_numeric($_GET['contact'])) {
    $contact_id = (int)$_GET['contact'];
    
    // Get contact details
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$contact_id]);
    $selected_contact = $stmt->fetch();
    
    if ($selected_contact) {
        // Mark all messages from this contact as read
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 
                              WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->execute([$contact_id, $user_id]);
        
        // Get all messages between the user and the selected contact
        $stmt = $pdo->prepare("SELECT * FROM messages 
                              WHERE (sender_id = ? AND receiver_id = ?) 
                              OR (sender_id = ? AND receiver_id = ?) 
                              ORDER BY created_at ASC");
        $stmt->execute([$user_id, $contact_id, $contact_id, $user_id]);
        $messages = $stmt->fetchAll();
    }
}

// Get all users for the new message form
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id != ?");
$stmt->execute([$user_id]);
$all_users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSE Messaging - FarmKnowledge</title>
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
        }

        .nav-links li a:hover, .nav-links li a.active {
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
        
        /* Main Messaging Styles */
        .messaging-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            margin-top: 20px;
            height: calc(100vh - 100px);
        }
        
        .contacts-panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .panel-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .panel-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2e7d32;
        }
        
        .new-message-btn {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 8px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }
        
        .new-message-btn:hover {
            background: #1b5e20;
        }
        
        .search-container {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .search-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            transition: border-color 0.2s;
        }
        
        .search-input:focus {
            border-color: #2e7d32;
        }
        
        .contacts-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border-bottom: 1px solid #f5f5f5;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            color: inherit;
            position: relative;
        }
        
        .contact-item:hover {
            background: #f9f9f9;
        }
        
        .contact-item:hover .delete-conversation {
            opacity: 1;
        }
        
        .contact-item.active {
            background: #e8f5e9;
            border-right: 3px solid #2e7d32;
        }
        
        .delete-conversation {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #f44336;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 10;
        }
        
        .delete-conversation:hover {
            background: #d32f2f;
        }
        
        .contact-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e8f5e9;
            color: #2e7d32;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .contact-details {
            flex: 1;
            min-width: 0;
        }
        
        .contact-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }
        
        .last-message {
            color: #777;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .contact-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: #999;
        }
        
        .unread-badge {
            background: #2e7d32;
            color: white;
            font-size: 0.7rem;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-panel {
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .back-button {
            margin-right: 10px;
            font-size: 1.2rem;
            color: #666;
            cursor: pointer;
            display: none;
        }
        
        .chat-contact-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-contact-name {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f9f9f9;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 12px 15px;
            border-radius: 15px;
            position: relative;
            line-height: 1.4;
        }
        
        .message-bubble.sent {
            align-self: flex-end;
            background: #dcf8c6;
            border-bottom-right-radius: 4px;
        }
        
        .message-bubble.received {
            align-self: flex-start;
            background: white;
            border-bottom-left-radius: 4px;
        }
        
        .message-time-badge {
            font-size: 0.7rem;
            color: #888;
            display: block;
            margin-top: 5px;
            text-align: right;
        }
        
        .message-form {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 30px;
            outline: none;
            transition: border-color 0.2s;
            font-size: 0.95rem;
        }
        
        .message-input:focus {
            border-color: #2e7d32;
        }
        
        .send-button {
            background: #2e7d32;
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .send-button:hover {
            background: #1b5e20;
        }
        
        .send-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
            color: #777;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: #e0e0e0;
            margin-bottom: 20px;
        }
        
        .empty-state-title {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #555;
        }
        
        .empty-state-text {
            max-width: 350px;
            line-height: 1.6;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 25px;
            border-radius: 10px;
            width: 450px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        
        .close-modal {
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            color: #777;
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #2e7d32;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .submit-btn {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .submit-btn:hover {
            background: #1b5e20;
        }
        
        .delete-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .delete-btn:hover {
            background: #d32f2f;
        }
        
        .cancel-btn {
            background: #f1f1f1;
            color: #333;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .cancel-btn:hover {
            background: #e0e0e0;
        }
        
        /* Show success message for deletion */
        .deletion-success {
            margin-top: 20px;
            text-align: center;
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            border-radius: 5px;
            display: none;
        }
        
        .new-message-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #2e7d32;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            animation: fadeIn 0.3s ease;
            z-index: 100;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .select-search-container {
            position: relative;
            margin-bottom: 15px;
        }
        
        .select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }
        
        .select-option {
            padding: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .select-option:hover {
            background: #f9f9f9;
        }
        
        .select-option:active {
            background: #e8f5e9;
        }
        
        .selected-user {
            background: #e8f5e9;
            padding: 8px 12px;
            border-radius: 5px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .selected-username {
            font-weight: 500;
            color: #2e7d32;
        }
        
        .remove-selected {
            cursor: pointer;
            color: #f44336;
            font-size: 1.2rem;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .messaging-container {
                grid-template-columns: 1fr;
            }
            
            .contacts-panel {
                display: none;
            }
            
            .contacts-panel.active {
                display: flex;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 100;
            }
            
            .back-button {
                display: block;
            }
            
            .chat-panel {
                display: flex;
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
                    <ul class="nav-links">
                        <li><a href="../farm-website-homepage.php">Home</a></li>
                        <li><a href="../about.php">About</a></li>
                        <li><a href="../learning-center/index.php">Learning Center</a></li>
                        <li><a href="../marketplace/marketplace.php">Marketplace</a></li>
                        <li><a href="#">Community</a></li>
                        <li><a href="messages.php">Messages</a></li>
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
                                <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <?php if ($message_sent): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Message sent successfully.
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <div class="messaging-container">
            <!-- Contacts Panel -->
            <div class="contacts-panel" id="contacts-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Messages</h2>
                    <button class="new-message-btn" id="new-message-btn">
                        <i class="fas fa-plus"></i> New
                    </button>
                </div>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search contacts..." id="search-contacts">
                </div>
                <div class="contacts-list" id="contacts-list">
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state" style="height: 200px;">
                            <div class="empty-state-icon"><i class="far fa-comments"></i></div>
                            <h3 class="empty-state-title">No conversations yet</h3>
                            <p class="empty-state-text">Start a conversation by clicking the New button above</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conversation): ?>
                            <a href="?contact=<?= $conversation['contact_id'] ?>" class="contact-item <?= isset($_GET['contact']) && $_GET['contact'] == $conversation['contact_id'] ? 'active' : '' ?>">
                                <div class="contact-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="contact-details">
                                    <div class="contact-name"><?= htmlspecialchars($conversation['contact_name']) ?></div>
                                    <div class="last-message"><?= htmlspecialchars(format_message_preview($conversation['last_message'])) ?></div>
                                </div>
                                <div class="contact-meta">
                                    <div class="message-time"><?= date('H:i', strtotime($conversation['last_message_time'])) ?></div>
                                    <?php if ($conversation['unread_count'] > 0): ?>
                                        <div class="unread-badge"><?= $conversation['unread_count'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="delete-conversation" data-id="<?= $conversation['contact_id'] ?>" data-name="<?= htmlspecialchars($conversation['contact_name']) ?>">
                                    <i class="fas fa-trash"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Panel -->
            <div class="chat-panel">
                <?php if ($selected_contact): ?>
                    <div class="chat-header">
                        <div class="back-button" id="back-button">
                            <i class="fas fa-arrow-left"></i>
                        </div>
                        <div class="chat-contact-info">
                            <div class="contact-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="chat-contact-name"><?= htmlspecialchars($selected_contact['username']) ?></div>
                        </div>
                    </div>
                    <div class="messages-container" id="messages-container">
                        <?php foreach ($messages as $message): ?>
                            <div class="message-bubble <?= $message['sender_id'] == $user_id ? 'sent' : 'received' ?>">
                                <?= $message['message'] // Changed from htmlspecialchars to allow HTML links ?>
                                <span class="message-time-badge"><?= date('H:i', strtotime($message['created_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form class="message-form" method="post" action="" id="message-form">
                        <input type="hidden" name="receiver_id" value="<?= $selected_contact['id'] ?>">
                        <input type="text" name="message" id="message-input" class="message-input" placeholder="Type a message..." required>
                        <button type="submit" name="send_message" class="send-button" disabled>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="far fa-comments"></i>
                        </div>
                        <h2 class="empty-state-title">Select a conversation</h2>
                        <p class="empty-state-text">Select a conversation from the sidebar or start a new one to begin messaging</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="new-message-indicator" id="new-message-indicator">
            <i class="fas fa-envelope"></i>
            <span>New message received</span>
        </div>
    </div>
    
    <!-- New Message Modal -->
    <div id="new-message-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">New Message</h3>
                <span class="close-modal" id="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="receiver">To:</label>
                        <div class="select-search-container">
                            <input type="text" id="user-search" class="form-control" placeholder="Search users...">
                            <div class="select-dropdown">
                                <?php foreach ($all_users as $recipient): ?>
                                    <div class="select-option" data-value="<?= $recipient['id'] ?>" data-name="<?= htmlspecialchars($recipient['username']) ?>">
                                        <?= htmlspecialchars($recipient['username']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="receiver_id" id="receiver" required>
                            <div class="selected-user" style="display: none;">
                                <span class="selected-username"></span>
                                <span class="remove-selected">&times;</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="message">Message:</label>
                        <textarea name="message" id="message" class="form-control" required></textarea>
                    </div>
                    <button type="submit" name="send_message" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Conversation Modal -->
    <div id="delete-conversation-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Delete Conversation</h3>
                <span class="close-modal" id="close-delete-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the entire conversation with <strong id="delete-contact-name"></strong>? This action cannot be undone.</p>
                <form method="post" action="" class="delete-form">
                    <input type="hidden" name="contact_id" id="delete-contact-id">
                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" class="cancel-btn" id="cancel-delete">Cancel</button>
                        <button type="submit" name="delete_conversation" class="delete-btn">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userId = <?= $user_id ?>;
            const contactId = <?= $selected_contact ? $selected_contact['id'] : 'null' ?>;
            
            // DOM elements
            const messageInput = document.getElementById('message-input');
            const sendButton = document.querySelector('.send-button');
            const messagesContainer = document.getElementById('messages-container');
            const newMessageBtn = document.getElementById('new-message-btn');
            const newMessageModal = document.getElementById('new-message-modal');
            const closeModal = document.getElementById('close-modal');
            const backButton = document.getElementById('back-button');
            const contactsPanel = document.getElementById('contacts-panel');
            const searchInput = document.getElementById('search-contacts');
            const contactsList = document.getElementById('contacts-list');
            const newMessageIndicator = document.getElementById('new-message-indicator');
            const userSearch = document.getElementById('user-search');
            const selectDropdown = document.querySelector('.select-dropdown');
            const receiverInput = document.getElementById('receiver');
            const selectedUser = document.querySelector('.selected-user');
            const selectedUsername = document.querySelector('.selected-username');
            const removeSelected = document.querySelector('.remove-selected');
            const deleteConversationModal = document.getElementById('delete-conversation-modal');
            const closeDeleteModal = document.getElementById('close-delete-modal');
            const cancelDelete = document.getElementById('cancel-delete');
            const deleteContactName = document.getElementById('delete-contact-name');
            const deleteContactId = document.getElementById('delete-contact-id');
            
            // Show deletion success message if conversation was deleted
            <?php if ($deletion_success): ?>
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = '<i class="fas fa-check-circle"></i> Conversation deleted successfully.';
            
            const container = document.querySelector('.container');
            const messagingContainer = document.querySelector('.messaging-container');
            container.insertBefore(alertDiv, messagingContainer);
            
            // Remove the message after 5 seconds
            setTimeout(function() {
                alertDiv.remove();
            }, 5000);
            <?php endif; ?>
            
            // Enable/disable send button
            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    sendButton.disabled = this.value.trim() === '';
                });
            }
            
            // Scroll messages to bottom
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            // New message modal
            newMessageBtn.addEventListener('click', function() {
                newMessageModal.style.display = 'block';
            });
            
            closeModal.addEventListener('click', function() {
                newMessageModal.style.display = 'none';
            });
            
            window.addEventListener('click', function(e) {
                if (e.target === newMessageModal) {
                    newMessageModal.style.display = 'none';
                }
            });
            
            // Mobile view - back button
            if (backButton) {
                backButton.addEventListener('click', function() {
                    contactsPanel.classList.add('active');
                });
            }
            
            // Search contacts
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase().trim();
                    const items = contactsList.querySelectorAll('.contact-item');
                    
                    items.forEach(function(item) {
                        const name = item.querySelector('.contact-name').textContent.toLowerCase();
                        const message = item.querySelector('.last-message').textContent.toLowerCase();
                        
                        if (name.includes(query) || message.includes(query)) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
            
            // Server-sent events for real-time messages
            function setupSSE() {
                if (typeof EventSource !== 'undefined') {
                    const evtSource = new EventSource('../api/sse_messages.php?user_id=' + userId);
                    
                    evtSource.onmessage = function(event) {
                        const data = JSON.parse(event.data);
                        
                        if (data.type === 'message') {
                            // If we have the chat with this sender open, append message
                            if (contactId === data.sender_id && messagesContainer) {
                                const messageElement = document.createElement('div');
                                messageElement.className = 'message-bubble received';
                                
                                const now = new Date();
                                const hours = now.getHours().toString().padStart(2, '0');
                                const minutes = now.getMinutes().toString().padStart(2, '0');
                                const timeStr = `${hours}:${minutes}`;
                                
                                messageElement.innerHTML = `
                                    ${data.message}
                                    <span class="message-time-badge">${timeStr}</span>
                                `;
                                
                                messagesContainer.appendChild(messageElement);
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                
                                // Mark as read via AJAX
                                fetch('../api/mark_read.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        sender_id: data.sender_id,
                                        receiver_id: userId
                                    }),
                                });
                            } else {
                                // Show notification for new message
                                newMessageIndicator.style.display = 'flex';
                                
                                // Play notification sound
                                const audio = new Audio('../Image/notification.mp3');
                                audio.play();
                                
                                setTimeout(function() {
                                    newMessageIndicator.style.display = 'none';
                                }, 5000);
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
                    
                    // Fallback to periodic polling
                    setInterval(function() {
                        checkForNewMessages();
                    }, 10000); // Check every 10 seconds
                }
            }
            
            // Fallback polling function
            function checkForNewMessages() {
                fetch('../api/check_messages.php?user_id=' + userId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.hasNewMessages) {
                            // Reload the page or update UI
                            window.location.reload();
                        }
                    })
                    .catch(error => console.error('Error checking messages:', error));
            }
            
            // Click on new message notification
            newMessageIndicator.addEventListener('click', function() {
                window.location.reload();
            });
            
            // Start SSE connection
            setupSSE();
            
            // User search functionality
            userSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const options = selectDropdown.querySelectorAll('.select-option');
                
                selectDropdown.style.display = query ? 'block' : 'none';
                
                options.forEach(function(option) {
                    const name = option.dataset.name.toLowerCase();
                    
                    if (name.includes(query)) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });
            
            selectDropdown.addEventListener('click', function(e) {
                if (e.target.classList.contains('select-option')) {
                    const selectedValue = e.target.dataset.value;
                    const selectedName = e.target.dataset.name;
                    
                    receiverInput.value = selectedValue;
                    selectedUsername.textContent = selectedName;
                    selectedUser.style.display = 'flex';
                    selectDropdown.style.display = 'none';
                    userSearch.value = '';
                }
            });
            
            removeSelected.addEventListener('click', function() {
                receiverInput.value = '';
                selectedUser.style.display = 'none';
            });
            
            // Delete conversation functionality
            document.addEventListener('click', function(e) {
                // Prevent the default link behavior when clicking the delete button
                if (e.target.closest('.delete-conversation')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const deleteButton = e.target.closest('.delete-conversation');
                    const contactName = deleteButton.dataset.name;
                    const contactId = deleteButton.dataset.id;
                    
                    deleteContactName.textContent = contactName;
                    deleteContactId.value = contactId;
                    
                    deleteConversationModal.style.display = 'block';
                }
            });
            
            closeDeleteModal.addEventListener('click', function() {
                deleteConversationModal.style.display = 'none';
            });
            
            cancelDelete.addEventListener('click', function() {
                deleteConversationModal.style.display = 'none';
            });
            
            window.addEventListener('click', function(e) {
                if (e.target === deleteConversationModal) {
                    deleteConversationModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>