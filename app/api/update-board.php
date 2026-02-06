<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];
$board_id = intval($_POST['board_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$rating_icon = trim($_POST['rating_icon'] ?? '');

if (!$board_id || !$title) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    // Check if user is owner
    $stmt = $pdo->prepare("SELECT role FROM board_users WHERE board_id = ? AND user_id = ?");
    $stmt->execute([$board_id, $user_id]);
    $membership = $stmt->fetch();

    if (!$membership || $membership['role'] !== 'owner') {
        echo json_encode(['success' => false, 'message' => 'Solo el dueño puede editar el tablero']);
        exit;
    }

    // Update board
    $stmt = $pdo->prepare("UPDATE boards SET title = ?, description = ?, rating_icon = ? WHERE id = ?");
    $stmt->execute([$title, $description ?: null, $rating_icon ?: '⭐', $board_id]);

    echo json_encode(['success' => true, 'message' => 'Tablero actualizado']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
}
