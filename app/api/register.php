<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$name || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
try {
    $stmt->execute([$name, $email, $hash]);
    $user_id = $pdo->lastInsertId();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $name;

    // If registering via invite link, add user to board
    $board_id = intval($_POST['board_id'] ?? 0);
    if ($board_id) {
        // Check if board exists
        $stmt = $pdo->prepare("SELECT id FROM boards WHERE id = ?");
        $stmt->execute([$board_id]);
        if ($stmt->fetch()) {
            // Add user to board (ignore if already member)
            $stmt = $pdo->prepare("INSERT IGNORE INTO board_users (board_id, user_id) VALUES (?, ?)");
            $stmt->execute([$board_id, $user_id]);
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Este correo ya existe']);
}
