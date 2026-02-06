<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$image_id = intval($_GET['image_id'] ?? 0);
$user_id  = $_SESSION['user_id'] ?? 0;

if (!$image_id) {
    echo json_encode(['success' => false]);
    exit;
}

// Get image info
$stmt = $pdo->prepare("
    SELECT i.id, i.image_url, i.caption, i.rating, i.user_id, u.name AS user_name
    FROM images i
    JOIN users u ON u.id = i.user_id
    WHERE i.id = ?
");
$stmt->execute([$image_id]);
$image = $stmt->fetch();

if (!$image) {
    echo json_encode(['success' => false, 'message' => 'Imagen no encontrada']);
    exit;
}

// Get user's own rating for this image
$my_rating = 0;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT rating FROM image_ratings WHERE image_id = ? AND user_id = ?");
    $stmt->execute([$image_id, $user_id]);
    $r = $stmt->fetch();
    if ($r) $my_rating = intval($r['rating']);
}

// Rating count
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM image_ratings WHERE image_id = ?");
$stmt->execute([$image_id]);
$rating_count = intval($stmt->fetch()['cnt']);

// Comments (with auto-create fallback)
$comments = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS image_comments (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        image_id   INT NOT NULL,
        user_id    INT NOT NULL,
        comment    TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ic_image (image_id)
    ) ENGINE=InnoDB");

    $stmt = $pdo->prepare("
        SELECT ic.id, ic.comment, ic.created_at, u.name AS user_name
        FROM image_comments ic
        JOIN users u ON u.id = ic.user_id
        WHERE ic.image_id = ?
        ORDER BY ic.created_at ASC
    ");
    $stmt->execute([$image_id]);
    $comments = $stmt->fetchAll();
} catch (PDOException $e) {}

echo json_encode([
    'success'      => true,
    'image'        => [
        'id'        => intval($image['id']),
        'image_url' => $image['image_url'],
        'caption'   => $image['caption'],
        'rating'    => round(floatval($image['rating']), 1),
        'user_id'   => intval($image['user_id']),
        'user_name' => $image['user_name']
    ],
    'my_rating'    => $my_rating,
    'rating_count' => $rating_count,
    'comments'     => $comments
]);
