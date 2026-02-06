<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'authenticated' => false,
        'user_id' => null,
        'user_name' => null,
        'avatar_url' => null
    ]);
    exit;
}

require 'db.php';

// Get user data including avatar
$avatar_url = null;
try {
    $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $avatar_url = $stmt->fetchColumn() ?: null;
} catch (PDOException $e) {
    // avatar_url column might not exist yet
}

echo json_encode([
    'authenticated' => true,
    'user_id' => $_SESSION['user_id'],
    'user_name' => $_SESSION['user_name'] ?? null,
    'avatar_url' => $avatar_url
]);
