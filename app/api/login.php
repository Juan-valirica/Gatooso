<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];

    // If logging in via invite link, add user to board
    $board_id = intval($_POST['board_id'] ?? 0);
    if ($board_id) {
        // Check if board exists
        $stmt = $pdo->prepare("SELECT id FROM boards WHERE id = ?");
        $stmt->execute([$board_id]);
        if ($stmt->fetch()) {
            // Add user to board (ignore if already member)
            $stmt = $pdo->prepare("INSERT IGNORE INTO board_users (board_id, user_id) VALUES (?, ?)");
            $stmt->execute([$board_id, $user['id']]);
        }
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
}
