<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');

if (!$title) {
    echo json_encode(['success' => false, 'message' => 'El tablero necesita un nombre']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Create board
    $stmt = $pdo->prepare("INSERT INTO boards (title, created_by) VALUES (?, ?)");
    $stmt->execute([$title, $user_id]);
    $board_id = $pdo->lastInsertId();

    // Add creator as owner
    $stmt = $pdo->prepare("INSERT INTO board_users (board_id, user_id, role) VALUES (?, ?, 'owner')");
    $stmt->execute([$board_id, $user_id]);

    // Create default challenge
    $stmt = $pdo->prepare("INSERT INTO challenges (board_id, title, description, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$board_id, 'La foto más sexy sin querer', 'Esa que no estaba planeada, pero que es el estandarte de tu perfil', $user_id]);

    // Log activity
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, board_id, action, reference_id) VALUES (?, ?, 'created_board', ?)");
    $stmt->execute([$user_id, $board_id, $board_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'board_id' => (int) $board_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error al crear el tablero']);
}
