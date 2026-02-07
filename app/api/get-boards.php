<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'boards' => []]);
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Try with rating_icon, fallback without it
try {
    $stmt = $pdo->prepare("
        SELECT b.id, b.title, b.description, b.rating_icon, b.created_at,
               bu.role,
               (SELECT COUNT(*) FROM board_users WHERE board_id = b.id) AS member_count
        FROM board_users bu
        JOIN boards b ON b.id = bu.board_id
        WHERE bu.user_id = ?
        ORDER BY bu.joined_at DESC
    ");
    $stmt->execute([$user_id]);
} catch (PDOException $e) {
    // rating_icon column might not exist yet
    $stmt = $pdo->prepare("
        SELECT b.id, b.title, b.description, '⭐' AS rating_icon, b.created_at,
               bu.role,
               (SELECT COUNT(*) FROM board_users WHERE board_id = b.id) AS member_count
        FROM board_users bu
        JOIN boards b ON b.id = bu.board_id
        WHERE bu.user_id = ?
        ORDER BY bu.joined_at DESC
    ");
    $stmt->execute([$user_id]);
}

$boards = $stmt->fetchAll();

// For each board, count unrated photos from active challenge
foreach ($boards as &$board) {
    $board['unrated_count'] = 0;
    try {
        // Get active challenge for this board
        $stmtChallenge = $pdo->prepare("
            SELECT id FROM challenges
            WHERE board_id = ? AND status = 'active'
            LIMIT 1
        ");
        $stmtChallenge->execute([$board['id']]);
        $activeChallenge = $stmtChallenge->fetch();

        if ($activeChallenge) {
            // Count images from active challenge that user hasn't rated
            $stmtUnrated = $pdo->prepare("
                SELECT COUNT(*) as cnt FROM images i
                WHERE i.challenge_id = ?
                AND i.id NOT IN (
                    SELECT image_id FROM image_ratings WHERE user_id = ?
                )
            ");
            $stmtUnrated->execute([$activeChallenge['id'], $user_id]);
            $unrated = $stmtUnrated->fetch();
            $board['unrated_count'] = intval($unrated['cnt']);
        }
    } catch (PDOException $e) {
        // Silently fail, keep default 0
    }
}
unset($board);

echo json_encode(['success' => true, 'boards' => $boards]);
