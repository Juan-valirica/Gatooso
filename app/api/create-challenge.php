<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require 'db.php';

$user_id  = $_SESSION['user_id'];
$board_id = intval($_POST['board_id'] ?? 0);
$title    = trim($_POST['title'] ?? '');
$desc     = trim($_POST['description'] ?? '');
$duration = intval($_POST['duration_hours'] ?? 72);

if (!$board_id) {
    echo json_encode(['success' => false, 'message' => 'Board ID requerido']);
    exit;
}

if (!$title) {
    echo json_encode(['success' => false, 'message' => 'El título es requerido']);
    exit;
}

if ($duration < 1) $duration = 72;
if ($duration > 168) $duration = 168; // Max 1 week

// Auto-migrate columns
try {
    $pdo->exec("ALTER TABLE challenges ADD COLUMN duration_hours INT DEFAULT 72");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE challenges ADD COLUMN status ENUM('queued', 'active', 'completed') DEFAULT 'queued'");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE challenges ADD COLUMN starts_at TIMESTAMP NULL");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE challenges ADD COLUMN ends_at TIMESTAMP NULL");
} catch (PDOException $e) {}

// Check if user is member of this board
$stmt = $pdo->prepare("SELECT id FROM board_users WHERE board_id = ? AND user_id = ?");
$stmt->execute([$board_id, $user_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'No eres miembro de este tablero']);
    exit;
}

try {
    // Check if there's an active challenge
    $stmt = $pdo->prepare("SELECT id FROM challenges WHERE board_id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$board_id]);
    $hasActive = $stmt->fetch();

    // If no active challenge, this one becomes active immediately
    if (!$hasActive) {
        $stmt = $pdo->prepare("
            INSERT INTO challenges (board_id, title, description, created_by, duration_hours, status, starts_at, ends_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR))
        ");
        $stmt->execute([$board_id, $title, $desc, $user_id, $duration, $duration]);
    } else {
        // Add to queue
        $stmt = $pdo->prepare("
            INSERT INTO challenges (board_id, title, description, created_by, duration_hours, status)
            VALUES (?, ?, ?, ?, ?, 'queued')
        ");
        $stmt->execute([$board_id, $title, $desc, $user_id, $duration]);
    }

    $challenge_id = $pdo->lastInsertId();

    // Get the position in queue
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as pos FROM challenges
        WHERE board_id = ? AND status = 'queued' AND id <= ?
    ");
    $stmt->execute([$board_id, $challenge_id]);
    $position = intval($stmt->fetch()['pos']);

    echo json_encode([
        'success' => true,
        'challenge_id' => intval($challenge_id),
        'status' => $hasActive ? 'queued' : 'active',
        'queue_position' => $hasActive ? $position : 0,
        'message' => $hasActive ? 'Reto agregado a la cola' : 'Reto activado'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al crear el reto: ' . $e->getMessage()]);
}
