<?php
require_once 'config/db_config.php';

if(isset($pdo)) {
    echo "Successfully connected to the database!<br>";
    
    // Test if we can query the database
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll();
        
        echo "Available tables in the database:<br>";
        foreach($tables as $table) {
            echo "- " . $table[0] . "<br>";
        }
    } catch(PDOException $e) {
        echo "Error querying database: " . $e->getMessage();
    }
}
?>