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
$description = trim($_POST['description'] ?? '');
$rating_icon = trim($_POST['rating_icon'] ?? '');

if (!$title) {
    echo json_encode(['success' => false, 'message' => 'El tablero necesita un nombre']);
    exit;
}

if (!$rating_icon) {
    $rating_icon = '⭐';
}

try {
    $pdo->beginTransaction();

    // Create board
    $stmt = $pdo->prepare("INSERT INTO boards (title, description, rating_icon, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $description ?: null, $rating_icon, $user_id]);
    $board_id = $pdo->lastInsertId();

    // Add creator as owner
    $stmt = $pdo->prepare("INSERT INTO board_users (board_id, user_id, role) VALUES (?, ?, 'owner')");
    $stmt->execute([$board_id, $user_id]);

    // Create default challenge
    $stmt = $pdo->prepare("INSERT INTO challenges (board_id, title, description, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$board_id, 'La foto más sexy sin querer', 'Esa que no estaba planeada, pero que es el estandarte de tu perfil', $user_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'board_id' => (int) $board_id
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    // Check if it's the missing column error
    if (strpos($e->getMessage(), 'rating_icon') !== false) {
        // Auto-migrate: add the column
        try {
            $pdo->exec("ALTER TABLE boards ADD COLUMN rating_icon VARCHAR(10) DEFAULT '⭐' AFTER description");
            // Retry the insert
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO boards (title, description, rating_icon, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description ?: null, $rating_icon, $user_id]);
            $board_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO board_users (board_id, user_id, role) VALUES (?, ?, 'owner')");
            $stmt->execute([$board_id, $user_id]);
            $stmt = $pdo->prepare("INSERT INTO challenges (board_id, title, description, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$board_id, 'La foto más sexy sin querer', 'Esa que no estaba planeada, pero que es el estandarte de tu perfil', $user_id]);
            $pdo->commit();
            echo json_encode(['success' => true, 'board_id' => (int) $board_id]);
        } catch (PDOException $e2) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error al crear el tablero: ' . $e2->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear el tablero: ' . $e->getMessage()]);
    }
}
