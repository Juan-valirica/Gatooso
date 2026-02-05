<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT b.id, b.title, b.description, b.created_at,
           (SELECT COUNT(*) FROM board_users WHERE board_id = b.id) AS member_count
    FROM board_users bu
    JOIN boards b ON b.id = bu.board_id
    WHERE bu.user_id = ?
    ORDER BY bu.joined_at DESC
");
$stmt->execute([$user_id]);
$boards = $stmt->fetchAll();

echo json_encode(['success' => true, 'boards' => $boards]);
