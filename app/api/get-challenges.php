<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$board_id = intval($_GET['board_id'] ?? 0);

if (!$board_id) {
    echo json_encode(['success' => false, 'message' => 'Board ID requerido']);
    exit;
}

// Auto-migrate: add new columns if they don't exist
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
try {
    $pdo->exec("ALTER TABLE challenges ADD COLUMN winner_image_id INT NULL");
} catch (PDOException $e) {}

// Create winner_seen table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS winner_seen (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        challenge_id INT NOT NULL,
        seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_challenge (user_id, challenge_id)
    )");
} catch (PDOException $e) {}

// Check if any challenge is active but expired - also determine winner
$stmt = $pdo->prepare("
    SELECT id FROM challenges
    WHERE board_id = ? AND status = 'active' AND ends_at IS NOT NULL AND ends_at < NOW()
");
$stmt->execute([$board_id]);
$expiredChallenges = $stmt->fetchAll();

foreach ($expiredChallenges as $expired) {
    // Find the winning image (highest rating)
    $winnerStmt = $pdo->prepare("
        SELECT id FROM images
        WHERE challenge_id = ?
        ORDER BY rating DESC, created_at ASC
        LIMIT 1
    ");
    $winnerStmt->execute([$expired['id']]);
    $winnerImage = $winnerStmt->fetch();

    // Update challenge to completed with winner
    $updateStmt = $pdo->prepare("
        UPDATE challenges
        SET status = 'completed', winner_image_id = ?
        WHERE id = ?
    ");
    $updateStmt->execute([
        $winnerImage ? $winnerImage['id'] : null,
        $expired['id']
    ]);
}

// If no active challenge, activate the next queued one
$stmt = $pdo->prepare("SELECT id FROM challenges WHERE board_id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$board_id]);
$active = $stmt->fetch();

if (!$active) {
    // Get oldest queued challenge
    $stmt = $pdo->prepare("
        SELECT id, duration_hours FROM challenges
        WHERE board_id = ? AND status = 'queued'
        ORDER BY created_at ASC LIMIT 1
    ");
    $stmt->execute([$board_id]);
    $next = $stmt->fetch();

    if ($next) {
        $duration = intval($next['duration_hours']) ?: 72;
        $stmt = $pdo->prepare("
            UPDATE challenges
            SET status = 'active',
                starts_at = NOW(),
                ends_at = DATE_ADD(NOW(), INTERVAL ? HOUR)
            WHERE id = ?
        ");
        $stmt->execute([$duration, $next['id']]);
    }
}

// Get active challenge
$stmt = $pdo->prepare("
    SELECT c.*, u.name AS creator_name,
           (SELECT COUNT(*) FROM images WHERE challenge_id = c.id) AS photo_count
    FROM challenges c
    LEFT JOIN users u ON u.id = c.created_by
    WHERE c.board_id = ? AND c.status = 'active'
    LIMIT 1
");
$stmt->execute([$board_id]);
$activeChallenge = $stmt->fetch();

// Get queued challenges
$stmt = $pdo->prepare("
    SELECT c.*, u.name AS creator_name
    FROM challenges c
    LEFT JOIN users u ON u.id = c.created_by
    WHERE c.board_id = ? AND c.status = 'queued'
    ORDER BY c.created_at ASC
");
$stmt->execute([$board_id]);
$queuedChallenges = $stmt->fetchAll();

// Get recently completed (last 3)
$stmt = $pdo->prepare("
    SELECT c.*, u.name AS creator_name,
           (SELECT COUNT(*) FROM images WHERE challenge_id = c.id) AS photo_count
    FROM challenges c
    LEFT JOIN users u ON u.id = c.created_by
    WHERE c.board_id = ? AND c.status = 'completed'
    ORDER BY c.ends_at DESC
    LIMIT 3
");
$stmt->execute([$board_id]);
$completedChallenges = $stmt->fetchAll();

// Get current user's role in this board
$user_role = null;
$can_edit = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM board_users WHERE board_id = ? AND user_id = ?");
    $stmt->execute([$board_id, $_SESSION['user_id']]);
    $membership = $stmt->fetch();
    if ($membership) {
        $user_role = $membership['role'];
        $can_edit = in_array($user_role, ['owner', 'admin']);
    }
}

echo json_encode([
    'success' => true,
    'active' => $activeChallenge ?: null,
    'queued' => $queuedChallenges,
    'queued_count' => count($queuedChallenges),
    'completed' => $completedChallenges,
    'user_role' => $user_role,
    'can_edit' => $can_edit
]);
