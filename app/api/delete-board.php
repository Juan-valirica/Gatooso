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

if (!$board_id) {
    echo json_encode(['success' => false, 'message' => 'Board ID requerido']);
    exit;
}

try {
    // Check if user is owner
    $stmt = $pdo->prepare("SELECT role FROM board_users WHERE board_id = ? AND user_id = ?");
    $stmt->execute([$board_id, $user_id]);
    $membership = $stmt->fetch();

    if (!$membership || $membership['role'] !== 'owner') {
        echo json_encode(['success' => false, 'message' => 'Solo el dueño puede eliminar el tablero']);
        exit;
    }

    // Delete board (cascades to board_users, challenges, images, etc.)
    $stmt = $pdo->prepare("DELETE FROM boards WHERE id = ?");
    $stmt->execute([$board_id]);

    echo json_encode(['success' => true, 'message' => 'Tablero eliminado']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
}
