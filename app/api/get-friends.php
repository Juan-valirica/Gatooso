<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Get all friends: users who share at least one board with current user
// Exclude the current user themselves
$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.name,
        u.email,
        u.created_at,
        GROUP_CONCAT(DISTINCT b.id) as board_ids,
        GROUP_CONCAT(DISTINCT b.title SEPARATOR '||') as board_titles,
        COUNT(DISTINCT bu2.board_id) as shared_boards_count,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(DISTINCT i.id) as photo_count
    FROM users u
    INNER JOIN board_users bu2 ON bu2.user_id = u.id
    INNER JOIN boards b ON b.id = bu2.board_id
    INNER JOIN board_users bu1 ON bu1.board_id = bu2.board_id AND bu1.user_id = ?
    LEFT JOIN images i ON i.user_id = u.id
    LEFT JOIN ratings r ON r.image_id = i.id
    WHERE u.id != ?
    GROUP BY u.id
    ORDER BY shared_boards_count DESC, avg_rating DESC, u.name ASC
");
$stmt->execute([$user_id, $user_id]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format the response
$formatted = [];
foreach ($friends as $friend) {
    $boardTitles = $friend['board_titles'] ? explode('||', $friend['board_titles']) : [];

    $formatted[] = [
        'id' => (int)$friend['id'],
        'name' => $friend['name'],
        'shared_boards' => (int)$friend['shared_boards_count'],
        'board_names' => $boardTitles,
        'avg_rating' => round((float)$friend['avg_rating'], 1),
        'photo_count' => (int)$friend['photo_count']
    ];
}

echo json_encode([
    'success' => true,
    'friends' => $formatted,
    'total' => count($formatted)
]);
