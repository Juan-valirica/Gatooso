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
$challenge_option = trim($_POST['challenge_option'] ?? 'default');
$challenge_title = trim($_POST['challenge_title'] ?? '');
$challenge_description = trim($_POST['challenge_description'] ?? '');
$challenge_duration = intval($_POST['challenge_duration'] ?? 72);

if (!$title) {
    echo json_encode(['success' => false, 'message' => 'El tablero necesita un nombre']);
    exit;
}

if (!$rating_icon) {
    $rating_icon = '🐼';
}

// Validate custom challenge
if ($challenge_option === 'custom' && !$challenge_title) {
    echo json_encode(['success' => false, 'message' => 'El reto personalizado necesita un título']);
    exit;
}

// Validate duration
if ($challenge_duration < 1) $challenge_duration = 24;
if ($challenge_duration > 168) $challenge_duration = 168;

try {
    $pdo->beginTransaction();

    // Create board
    $stmt = $pdo->prepare("INSERT INTO boards (title, description, rating_icon, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $description ?: null, $rating_icon, $user_id]);
    $board_id = $pdo->lastInsertId();

    // Add creator as owner
    $stmt = $pdo->prepare("INSERT INTO board_users (board_id, user_id, role) VALUES (?, ?, 'owner')");
    $stmt->execute([$board_id, $user_id]);

    // Create first challenge (default or custom)
    if ($challenge_option === 'custom') {
        $stmt = $pdo->prepare("INSERT INTO challenges (board_id, title, description, duration_hours, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$board_id, $challenge_title, $challenge_description ?: null, $challenge_duration, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO challenges (board_id, title, description, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$board_id, '🐼 Reto GatOOso', 'Sube tu foto más curiosa, rara o random que ya tengas en tu galería. Empieza el juego 😏📸', $user_id]);
    }

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
            $pdo->exec("ALTER TABLE boards ADD COLUMN rating_icon VARCHAR(10) DEFAULT '🐼' AFTER description");
            // Retry the insert
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO boards (title, description, rating_icon, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description ?: null, $rating_icon, $user_id]);
            $board_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO board_users (board_id, user_id, role) VALUES (?, ?, 'owner')");
            $stmt->execute([$board_id, $user_id]);
            // Create first challenge (default or custom)
            if ($challenge_option === 'custom') {
                $stmt = $pdo->prepare("INSERT INTO challenges (board_id, title, description, duration_hours, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$board_id, $challenge_title, $challenge_description ?: null, $challenge_duration, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO challenges (board_id, title, description, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$board_id, '🐼 Reto GatOOso', 'Sube tu foto más curiosa, rara o random que ya tengas en tu galería. Empieza el juego 😏📸', $user_id]);
            }
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
