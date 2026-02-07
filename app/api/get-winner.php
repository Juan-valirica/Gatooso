<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
$board_id = intval($_GET['board_id'] ?? 0);

if (!$user_id || !$board_id) {
    echo json_encode(['success' => false, 'message' => 'Parámetros requeridos']);
    exit;
}

// Auto-migrate: ensure tables exist
try {
    $pdo->exec("ALTER TABLE challenges ADD COLUMN winner_image_id INT NULL");
} catch (PDOException $e) {}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS winner_seen (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        challenge_id INT NOT NULL,
        seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_challenge (user_id, challenge_id)
    )");
} catch (PDOException $e) {}

// Find completed challenges with winners that this user hasn't seen yet
$stmt = $pdo->prepare("
    SELECT c.id AS challenge_id, c.title AS challenge_title, c.winner_image_id,
           i.id AS image_id, i.image_url, i.rating,
           u.id AS winner_id, u.name AS winner_name, u.avatar_url AS winner_avatar
    FROM challenges c
    JOIN images i ON i.id = c.winner_image_id
    JOIN users u ON u.id = i.user_id
    WHERE c.board_id = ?
      AND c.status = 'completed'
      AND c.winner_image_id IS NOT NULL
      AND c.id NOT IN (
          SELECT challenge_id FROM winner_seen WHERE user_id = ?
      )
    ORDER BY c.ends_at DESC
    LIMIT 1
");
$stmt->execute([$board_id, $user_id]);
$winner = $stmt->fetch();

if (!$winner) {
    echo json_encode(['success' => true, 'has_winner' => false]);
    exit;
}

echo json_encode([
    'success' => true,
    'has_winner' => true,
    'winner' => [
        'challenge_id' => $winner['challenge_id'],
        'challenge_title' => $winner['challenge_title'],
        'image_id' => $winner['image_id'],
        'image_url' => $winner['image_url'],
        'rating' => $winner['rating'],
        'winner_id' => $winner['winner_id'],
        'winner_name' => $winner['winner_name'],
        'winner_avatar' => $winner['winner_avatar']
    ]
]);
