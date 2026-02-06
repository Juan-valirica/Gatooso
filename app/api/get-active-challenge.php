<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$board_id = intval($_GET['board_id'] ?? 0);

if (!$board_id) {
    echo json_encode(['success' => false]);
    exit;
}

// Auto-migrate columns (silent)
try { $pdo->exec("ALTER TABLE challenges ADD COLUMN status ENUM('queued', 'active', 'completed') DEFAULT 'queued'"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE challenges ADD COLUMN starts_at TIMESTAMP NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE challenges ADD COLUMN ends_at TIMESTAMP NULL"); } catch (PDOException $e) {}

// Check if active challenge has expired
$stmt = $pdo->prepare("
    UPDATE challenges
    SET status = 'completed'
    WHERE board_id = ? AND status = 'active' AND ends_at IS NOT NULL AND ends_at < NOW()
");
$stmt->execute([$board_id]);

// Get active challenge
$stmt = $pdo->prepare("
    SELECT c.id, c.title, c.description, c.starts_at, c.ends_at,
           (SELECT COUNT(*) FROM images WHERE challenge_id = c.id) AS photo_count
    FROM challenges c
    WHERE c.board_id = ? AND c.status = 'active'
    LIMIT 1
");
$stmt->execute([$board_id]);
$active = $stmt->fetch();

// If no active, try to activate next queued
if (!$active) {
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

        // Fetch it again
        $stmt = $pdo->prepare("
            SELECT c.id, c.title, c.description, c.starts_at, c.ends_at,
                   (SELECT COUNT(*) FROM images WHERE challenge_id = c.id) AS photo_count
            FROM challenges c
            WHERE c.id = ?
        ");
        $stmt->execute([$next['id']]);
        $active = $stmt->fetch();
    }
}

// Count queued
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM challenges WHERE board_id = ? AND status = 'queued'");
$stmt->execute([$board_id]);
$queuedCount = intval($stmt->fetch()['cnt']);

if ($active) {
    echo json_encode([
        'success' => true,
        'challenge' => [
            'id' => intval($active['id']),
            'title' => $active['title'],
            'description' => $active['description'],
            'starts_at' => $active['starts_at'],
            'ends_at' => $active['ends_at'],
            'photo_count' => intval($active['photo_count'])
        ],
        'queued_count' => $queuedCount
    ]);
} else {
    echo json_encode([
        'success' => true,
        'challenge' => null,
        'queued_count' => $queuedCount
    ]);
}
