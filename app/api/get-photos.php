<?php
session_start();
header("Content-Type: application/json");
require "db.php";

$board_id = intval($_GET['board_id'] ?? 0);

if (!$board_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT i.image_url, i.rating, i.caption, u.name AS user_name
    FROM images i
    JOIN challenges c ON c.id = i.challenge_id
    JOIN users u ON u.id = i.user_id
    WHERE c.board_id = ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$board_id]);

echo json_encode($stmt->fetchAll());
