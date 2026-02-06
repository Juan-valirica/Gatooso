<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];

try {
    // Auto-migrate: add avatar_url column if missing
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL AFTER password");
    } catch (PDOException $e) {
        // Column already exists, ignore
    }

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            email,
            avatar_url,
            created_at
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Get stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM images WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $photoCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COALESCE(AVG(r.rating), 0) as avg_rating
        FROM images i
        LEFT JOIN image_ratings r ON r.image_id = i.id
        WHERE i.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $avgRating = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $boardCount = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar_url' => $user['avatar_url'],
            'created_at' => $user['created_at']
        ],
        'stats' => [
            'photos' => (int)$photoCount,
            'rating' => round((float)$avgRating, 1),
            'boards' => (int)$boardCount
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar perfil',
        'debug' => $e->getMessage()
    ]);
}
