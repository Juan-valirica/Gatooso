<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require 'db.php';

$user_id      = $_SESSION['user_id'];
$challenge_id = intval($_POST['challenge_id'] ?? 0);
$title        = trim($_POST['title'] ?? '');
$desc         = trim($_POST['description'] ?? '');
$duration     = intval($_POST['duration_hours'] ?? 0);

if (!$challenge_id) {
    echo json_encode(['success' => false, 'message' => 'ID de reto requerido']);
    exit;
}

if (!$title) {
    echo json_encode(['success' => false, 'message' => 'El título es requerido']);
    exit;
}

if ($duration < 1) $duration = 24;
if ($duration > 168) $duration = 168;

try {
    // Get the challenge and its board
    $stmt = $pdo->prepare("SELECT c.*, c.board_id FROM challenges c WHERE c.id = ?");
    $stmt->execute([$challenge_id]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$challenge) {
        echo json_encode(['success' => false, 'message' => 'Reto no encontrado']);
        exit;
    }

    $board_id = $challenge['board_id'];

    // Check if user is owner or admin of this board
    $stmt = $pdo->prepare("SELECT role FROM board_users WHERE board_id = ? AND user_id = ?");
    $stmt->execute([$board_id, $user_id]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$membership) {
        echo json_encode(['success' => false, 'message' => 'No eres miembro de este tablero']);
        exit;
    }

    if (!in_array($membership['role'], ['owner', 'admin'])) {
        echo json_encode(['success' => false, 'message' => 'Solo el administrador o dueño puede editar retos']);
        exit;
    }

    // Update the challenge
    // If challenge is active, also update the ends_at based on new duration
    if ($challenge['status'] === 'active' && $challenge['starts_at']) {
        $stmt = $pdo->prepare("
            UPDATE challenges
            SET title = ?, description = ?, duration_hours = ?,
                ends_at = DATE_ADD(starts_at, INTERVAL ? HOUR)
            WHERE id = ?
        ");
        $stmt->execute([$title, $desc ?: null, $duration, $duration, $challenge_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE challenges
            SET title = ?, description = ?, duration_hours = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $desc ?: null, $duration, $challenge_id]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Reto actualizado correctamente'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el reto: ' . $e->getMessage()]);
}
