<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "Testing all users in database...\n";

// Get all users
$pdo = getDBConnection();
$stmt = $pdo->prepare('SELECT * FROM users ORDER BY id');
$stmt->execute();
$users = $stmt->fetchAll();

echo "Total users in database: " . count($users) . "\n";

foreach ($users as $user) {
    echo "ID: {$user['id']}, Name: {$user['full_name']}, Username: {$user['username']}, Type: {$user['user_type']}, Active: {$user['is_active']}\n";
}

echo "\nTest completed.\n";
?> 