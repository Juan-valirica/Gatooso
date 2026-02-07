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

    // Delete board and all related data
    $pdo->beginTransaction();

    // Delete in order of dependencies (deepest first)
    // 1. Delete image comments
    $stmt = $pdo->prepare("
        DELETE ic FROM image_comments ic
        INNER JOIN images i ON ic.image_id = i.id
        INNER JOIN challenges c ON i.challenge_id = c.id
        WHERE c.board_id = ?
    ");
    $stmt->execute([$board_id]);

    // 2. Delete image ratings
    $stmt = $pdo->prepare("
        DELETE ir FROM image_ratings ir
        INNER JOIN images i ON ir.image_id = i.id
        INNER JOIN challenges c ON i.challenge_id = c.id
        WHERE c.board_id = ?
    ");
    $stmt->execute([$board_id]);

    // 3. Delete images
    $stmt = $pdo->prepare("
        DELETE i FROM images i
        INNER JOIN challenges c ON i.challenge_id = c.id
        WHERE c.board_id = ?
    ");
    $stmt->execute([$board_id]);

    // 4. Delete challenges
    $stmt = $pdo->prepare("DELETE FROM challenges WHERE board_id = ?");
    $stmt->execute([$board_id]);

    // 5. Delete board_users
    $stmt = $pdo->prepare("DELETE FROM board_users WHERE board_id = ?");
    $stmt->execute([$board_id]);

    // 6. Delete activity_log
    $stmt = $pdo->prepare("DELETE FROM activity_log WHERE board_id = ?");
    $stmt->execute([$board_id]);

    // 7. Finally delete the board
    $stmt = $pdo->prepare("DELETE FROM boards WHERE id = ?");
    $stmt->execute([$board_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Tablero eliminado']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Delete board error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
}
