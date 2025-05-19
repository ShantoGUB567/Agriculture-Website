<?php
// Installer script to create the wishlist table
require_once 'config/db_config.php';

echo "<h1>Wishlist Table Installer</h1>";

try {
    // Read the SQL from the wishlist.sql file
    $sql = file_get_contents('database/wishlist.sql');
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "<p style='color: green; font-weight: bold;'>Success! The wishlist table has been created.</p>";
    echo "<p>You can now use the wishlist feature. <a href='marketplace/marketplace.php'>Go to Marketplace</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Details:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
?>