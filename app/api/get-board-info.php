<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];
$board_id = intval($_GET['board_id'] ?? 0);

if (!$board_id) {
    echo json_encode(['success' => false, 'message' => 'Board ID requerido']);
    exit;
}

try {
    // Get board info
    $stmt = $pdo->prepare("SELECT id, title, description, rating_icon FROM boards WHERE id = ?");
    $stmt->execute([$board_id]);
    $board = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$board) {
        echo json_encode(['success' => false, 'message' => 'Tablero no encontrado']);
        exit;
    }

    // Get user's role
    $stmt = $pdo->prepare("SELECT role FROM board_users WHERE board_id = ? AND user_id = ?");
    $stmt->execute([$board_id, $user_id]);
    $membership = $stmt->fetch();

    if (!$membership) {
        echo json_encode(['success' => false, 'message' => 'No eres miembro de este tablero']);
        exit;
    }

    // Get all members (for ownership transfer)
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.avatar_url, bu.role
        FROM board_users bu
        JOIN users u ON u.id = bu.user_id
        WHERE bu.board_id = ?
        ORDER BY bu.role = 'owner' DESC, u.name ASC
    ");
    $stmt->execute([$board_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'board' => $board,
        'my_role' => $membership['role'],
        'members' => $members
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al cargar información']);
}
