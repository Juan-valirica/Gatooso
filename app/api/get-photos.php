<?php
session_start();
header("Content-Type: application/json");
require "db.php";

$board_id = intval($_GET['board_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? 0;

if (!$board_id) {
    echo json_encode([]);
    exit;
}

// Get board rating icon
$rating_icon = '⭐';
try {
    $stmt = $pdo->prepare("SELECT rating_icon FROM boards WHERE id = ?");
    $stmt->execute([$board_id]);
    $board = $stmt->fetch();
    if ($board && $board['rating_icon']) {
        $rating_icon = $board['rating_icon'];
    }
} catch (PDOException $e) {
    // Column might not exist yet, use default
}

// Get active challenge ID for this board
$activeChallengeId = null;
try {
    $stmtChallenge = $pdo->prepare("SELECT id FROM challenges WHERE board_id = ? AND status = 'active' LIMIT 1");
    $stmtChallenge->execute([$board_id]);
    $activeChallenge = $stmtChallenge->fetch();
    if ($activeChallenge) {
        $activeChallengeId = $activeChallenge['id'];
    }
} catch (PDOException $e) {
    // Silently fail
}

// Try with comment_count, avatar_url, is_winner, and user_rated
try {
    $stmt = $pdo->prepare("
        SELECT i.id, i.image_url, i.rating, i.caption, i.user_id, i.challenge_id, u.name AS user_name, u.avatar_url,
               (SELECT COUNT(*) FROM image_comments WHERE image_id = i.id) AS comment_count,
               (SELECT COUNT(*) FROM challenges WHERE winner_image_id = i.id) AS is_winner,
               (SELECT COUNT(*) FROM image_ratings WHERE image_id = i.id AND user_id = ?) AS user_rated
        FROM images i
        JOIN challenges c ON c.id = i.challenge_id
        JOIN users u ON u.id = i.user_id
        WHERE c.board_id = ?
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$user_id, $board_id]);
    $photos = $stmt->fetchAll();
    // Convert is_winner and user_rated to boolean, add is_active_challenge
    foreach ($photos as &$p) {
        $p['is_winner'] = intval($p['is_winner']) > 0;
        $p['user_rated'] = intval($p['user_rated']) > 0;
        $p['is_active_challenge'] = $activeChallengeId && intval($p['challenge_id']) === intval($activeChallengeId);
    }
    unset($p);
} catch (PDOException $e) {
    // Fallback without comment_count, avatar_url, is_winner, or user_rated
    $stmt = $pdo->prepare("
        SELECT i.id, i.image_url, i.rating, i.caption, i.user_id, i.challenge_id, u.name AS user_name
        FROM images i
        JOIN challenges c ON c.id = i.challenge_id
        JOIN users u ON u.id = i.user_id
        WHERE c.board_id = ?
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$board_id]);
    $photos = $stmt->fetchAll();
    // Add defaults
    foreach ($photos as &$p) {
        $p['comment_count'] = 0;
        $p['avatar_url'] = null;
        $p['is_winner'] = false;
        $p['user_rated'] = false;
        $p['is_active_challenge'] = $activeChallengeId && intval($p['challenge_id']) === intval($activeChallengeId);
    }
    unset($p);
}

echo json_encode([
    'rating_icon' => $rating_icon,
    'photos' => $photos,
    'active_challenge_id' => $activeChallengeId
]);
