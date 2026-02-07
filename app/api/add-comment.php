<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require 'db.php';

$user_id  = $_SESSION['user_id'];
$image_id = intval($_POST['image_id'] ?? 0);
$comment  = trim($_POST['comment'] ?? '');

if (!$image_id || !$comment) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Validate comment length (max 500 characters)
if (mb_strlen($comment) > 500) {
    echo json_encode(['success' => false, 'message' => 'Comentario muy largo (máx. 500 caracteres)']);
    exit;
}

// Auto-create table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS image_comments (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        image_id   INT NOT NULL,
        user_id    INT NOT NULL,
        comment    TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ic_image (image_id)
    ) ENGINE=InnoDB");
} catch (PDOException $e) {}

try {
    $stmt = $pdo->prepare("INSERT INTO image_comments (image_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$image_id, $user_id, $comment]);

    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'comment' => [
            'id'         => intval($pdo->lastInsertId()),
            'user_name'  => $user['name'],
            'comment'    => $comment,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el comentario']);
}
