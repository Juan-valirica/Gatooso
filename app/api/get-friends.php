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
    // Step 1: Get all boards the current user is in
    $stmt = $pdo->prepare("SELECT board_id FROM board_users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $myBoards = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($myBoards)) {
        echo json_encode([
            'success' => true,
            'friends' => [],
            'total' => 0,
            'debug_step' => 'no_boards',
            'user_id' => $user_id
        ]);
        exit;
    }

    // Step 2: Get all users in those boards (except me)
    $inClause = implode(',', array_map('intval', $myBoards));
    $sql = "
        SELECT DISTINCT u.id, u.name
        FROM users u
        INNER JOIN board_users bu ON bu.user_id = u.id
        WHERE bu.board_id IN ($inClause)
        AND u.id != ?
        ORDER BY u.name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($friends)) {
        echo json_encode([
            'success' => true,
            'friends' => [],
            'total' => 0,
            'debug_step' => 'no_friends_found',
            'my_boards' => $myBoards,
            'sql' => $sql
        ]);
        exit;
    }

    // Step 3: For each friend, get their shared boards and rating
    $formatted = [];
    foreach ($friends as $friend) {
        $friendId = (int)$friend['id'];

        // Get shared boards
        $stmt = $pdo->prepare("
            SELECT b.id, b.title
            FROM boards b
            INNER JOIN board_users bu ON bu.board_id = b.id
            WHERE bu.user_id = ?
            AND b.id IN ($inClause)
        ");
        $stmt->execute([$friendId]);
        $sharedBoards = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get average rating and photo count
        $stmt = $pdo->prepare("
            SELECT
                COUNT(DISTINCT i.id) as photo_count,
                COALESCE(AVG(r.rating), 0) as avg_rating
            FROM images i
            LEFT JOIN image_ratings r ON r.image_id = i.id
            WHERE i.user_id = ?
        ");
        $stmt->execute([$friendId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $boardNames = array_map(function($b) { return $b['title']; }, $sharedBoards);

        $formatted[] = [
            'id' => $friendId,
            'name' => $friend['name'],
            'shared_boards' => count($sharedBoards),
            'board_names' => $boardNames,
            'avg_rating' => round((float)($stats['avg_rating'] ?? 0), 1),
            'photo_count' => (int)($stats['photo_count'] ?? 0)
        ];
    }

    // Sort by shared boards count, then by rating
    usort($formatted, function($a, $b) {
        if ($b['shared_boards'] !== $a['shared_boards']) {
            return $b['shared_boards'] - $a['shared_boards'];
        }
        return $b['avg_rating'] <=> $a['avg_rating'];
    });

    echo json_encode([
        'success' => true,
        'friends' => $formatted,
        'total' => count($formatted)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar amigos',
        'debug' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
