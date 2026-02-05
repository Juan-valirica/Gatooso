<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Get the user's current board
$stmt = $pdo->prepare("
    SELECT bu.board_id
    FROM board_users bu
    WHERE bu.user_id = ?
    ORDER BY bu.joined_at DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode([]);
    exit;
}

$board_id = $row['board_id'];

// Get all members of that board
$stmt = $pdo->prepare("
    SELECT u.id, u.name, bu.role
    FROM board_users bu
    JOIN users u ON u.id = bu.user_id
    WHERE bu.board_id = ?
    ORDER BY bu.role = 'owner' DESC, bu.joined_at ASC
");
$stmt->execute([$board_id]);
$members = $stmt->fetchAll();

echo json_encode([
    'board_id' => (int) $board_id,
    'members' => $members
]);
