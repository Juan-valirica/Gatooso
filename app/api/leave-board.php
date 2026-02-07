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
$new_owner_id = intval($_POST['new_owner_id'] ?? 0);

if (!$board_id) {
    echo json_encode(['success' => false, 'message' => 'Board ID requerido']);
    exit;
}

try {
    // Check user's role in board
    $stmt = $pdo->prepare("SELECT role FROM board_users WHERE board_id = ? AND user_id = ?");
    $stmt->execute([$board_id, $user_id]);
    $membership = $stmt->fetch();

    if (!$membership) {
        echo json_encode(['success' => false, 'message' => 'No eres miembro de este tablero']);
        exit;
    }

    // If user is owner, they must transfer ownership first
    if ($membership['role'] === 'owner') {
        // Count other members
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_users WHERE board_id = ? AND user_id != ?");
        $stmt->execute([$board_id, $user_id]);
        $otherMembers = $stmt->fetchColumn();

        if ($otherMembers > 0) {
            // There are other members, so owner must transfer ownership
            if (!$new_owner_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Debes elegir un nuevo dueño antes de salir',
                    'requires_transfer' => true
                ]);
                exit;
            }

            // Check if new owner is a member
            $stmt = $pdo->prepare("SELECT id FROM board_users WHERE board_id = ? AND user_id = ?");
            $stmt->execute([$board_id, $new_owner_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El nuevo dueño debe ser miembro del tablero']);
                exit;
            }

            // Transfer ownership
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE board_users SET role = 'owner' WHERE board_id = ? AND user_id = ?");
            $stmt->execute([$board_id, $new_owner_id]);

            $stmt = $pdo->prepare("UPDATE boards SET created_by = ? WHERE id = ?");
            $stmt->execute([$new_owner_id, $board_id]);

            // Remove current user
            $stmt = $pdo->prepare("DELETE FROM board_users WHERE board_id = ? AND user_id = ?");
            $stmt->execute([$board_id, $user_id]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Has salido del tablero y transferido el ownership']);
            exit;
        } else {
            // No other members, delete the board and all related data
            $pdo->beginTransaction();

            // Delete in order of dependencies
            $stmt = $pdo->prepare("
                DELETE ic FROM image_comments ic
                INNER JOIN images i ON ic.image_id = i.id
                INNER JOIN challenges c ON i.challenge_id = c.id
                WHERE c.board_id = ?
            ");
            $stmt->execute([$board_id]);

            $stmt = $pdo->prepare("
                DELETE ir FROM image_ratings ir
                INNER JOIN images i ON ir.image_id = i.id
                INNER JOIN challenges c ON i.challenge_id = c.id
                WHERE c.board_id = ?
            ");
            $stmt->execute([$board_id]);

            $stmt = $pdo->prepare("
                DELETE i FROM images i
                INNER JOIN challenges c ON i.challenge_id = c.id
                WHERE c.board_id = ?
            ");
            $stmt->execute([$board_id]);

            $stmt = $pdo->prepare("DELETE FROM challenges WHERE board_id = ?");
            $stmt->execute([$board_id]);

            $stmt = $pdo->prepare("DELETE FROM board_users WHERE board_id = ?");
            $stmt->execute([$board_id]);

            $stmt = $pdo->prepare("DELETE FROM activity_log WHERE board_id = ?");
            $stmt->execute([$board_id]);

            $stmt = $pdo->prepare("DELETE FROM boards WHERE id = ?");
            $stmt->execute([$board_id]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Tablero eliminado (eras el único miembro)']);
            exit;
        }
    }

    // Not owner, just leave
    $stmt = $pdo->prepare("DELETE FROM board_users WHERE board_id = ? AND user_id = ?");
    $stmt->execute([$board_id, $user_id]);

    echo json_encode(['success' => true, 'message' => 'Has salido del tablero']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Leave board error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al salir: ' . $e->getMessage()]);
}
