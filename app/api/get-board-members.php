<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['board_id' => null, 'members' => []]);
    exit;
}

require 'db.php';

$board_id = intval($_GET['board_id'] ?? 0);

if (!$board_id) {
    echo json_encode(['board_id' => null, 'members' => []]);
    exit;
}

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
    'board_id' => $board_id,
    'members' => $members
]);
