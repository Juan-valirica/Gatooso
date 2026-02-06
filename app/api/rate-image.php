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
$rating   = intval($_POST['rating'] ?? 0);

if (!$image_id || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    // Upsert rating
    $stmt = $pdo->prepare("INSERT INTO image_ratings (image_id, user_id, rating) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating)");
    $stmt->execute([$image_id, $user_id, $rating]);

    // Update average rating on image
    $stmt = $pdo->prepare("UPDATE images SET rating = (SELECT AVG(rating) FROM image_ratings WHERE image_id = ?) WHERE id = ?");
    $stmt->execute([$image_id, $image_id]);

    // Get new average + count
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as cnt FROM image_ratings WHERE image_id = ?");
    $stmt->execute([$image_id]);
    $row = $stmt->fetch();

    echo json_encode([
        'success'      => true,
        'new_avg'      => round(floatval($row['avg_rating']), 1),
        'rating_count' => intval($row['cnt'])
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el rating']);
}
