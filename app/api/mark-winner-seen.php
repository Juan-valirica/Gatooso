<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
$challenge_id = intval($_POST['challenge_id'] ?? 0);

if (!$user_id || !$challenge_id) {
    echo json_encode(['success' => false, 'message' => 'Parámetros requeridos']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO winner_seen (user_id, challenge_id) VALUES (?, ?)
    ");
    $stmt->execute([$user_id, $challenge_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar']);
}
