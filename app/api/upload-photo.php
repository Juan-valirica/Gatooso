<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Validate file
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se recibió la imagen']);
    exit;
}

$file = $_FILES['photo'];
$caption = trim($_POST['caption'] ?? '');

// Validate mime type
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Solo se permiten imágenes JPG, PNG o WebP']);
    exit;
}

// Max 10MB raw upload
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'La imagen no puede pesar más de 10MB']);
    exit;
}

// Ensure user has a board + challenge (auto-create for MVP)
$board = $pdo->prepare("
    SELECT b.id AS board_id, c.id AS challenge_id
    FROM board_users bu
    JOIN boards b ON b.id = bu.board_id
    LEFT JOIN challenges c ON c.board_id = b.id
    WHERE bu.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 1
");
$board->execute([$user_id]);
$context = $board->fetch();

if (!$context || !$context['board_id']) {
    // Create default board
    $stmt = $pdo->prepare("INSERT INTO boards (title, description, created_by) VALUES (?, ?, ?)");
    $stmt->execute(['Mi primer tablero', 'Tablero creado automáticamente', $user_id]);
    $board_id = $pdo->lastInsertId();

    // Add user as owner
    $stmt = $pdo->prepare("INSERT INTO board_users (board_id, user_id, role) VALUES (?, ?, 'owner')");
    $stmt->execute([$board_id, $user_id]);

    // Create default challenge
    $stmt = $pdo->prepare("INSERT INTO challenges (board_id, title, description, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$board_id, 'La foto más sexy sin querer', 'Esa que no estaba planeada, pero que es el estandarte de tu perfil', $user_id]);
    $challenge_id = $pdo->lastInsertId();
} else {
    $board_id = $context['board_id'];
    $challenge_id = $context['challenge_id'];

    if (!$challenge_id) {
        // Board exists but no challenge — create one
        $stmt = $pdo->prepare("INSERT INTO challenges (board_id, title, description, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$board_id, 'La foto más sexy sin querer', 'Esa que no estaba planeada, pero que es el estandarte de tu perfil', $user_id]);
        $challenge_id = $pdo->lastInsertId();
    }
}

// Create uploads directory
$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Process and optimize image
$source = null;
switch ($mime) {
    case 'image/jpeg':
        $source = imagecreatefromjpeg($file['tmp_name']);
        break;
    case 'image/png':
        $source = imagecreatefrompng($file['tmp_name']);
        break;
    case 'image/webp':
        $source = imagecreatefromwebp($file['tmp_name']);
        break;
}

if (!$source) {
    echo json_encode(['success' => false, 'message' => 'No se pudo procesar la imagen']);
    exit;
}

// Resize to max 1200px on longest side
$orig_w = imagesx($source);
$orig_h = imagesy($source);
$max_dim = 1200;

if ($orig_w > $max_dim || $orig_h > $max_dim) {
    if ($orig_w >= $orig_h) {
        $new_w = $max_dim;
        $new_h = intval($orig_h * ($max_dim / $orig_w));
    } else {
        $new_h = $max_dim;
        $new_w = intval($orig_w * ($max_dim / $orig_h));
    }

    $resized = imagecreatetruecolor($new_w, $new_h);
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);
    imagedestroy($source);
    $source = $resized;
}

// Save as optimized JPEG (80% quality)
$filename = uniqid('img_', true) . '.jpg';
$filepath = $upload_dir . $filename;
imagejpeg($source, $filepath, 80);
imagedestroy($source);

$image_url = '/app/uploads/' . $filename;

// Insert into database
try {
    $stmt = $pdo->prepare("INSERT INTO images (challenge_id, user_id, image_url, caption) VALUES (?, ?, ?, ?)");
    $stmt->execute([$challenge_id, $user_id, $image_url, $caption]);

    // Log activity
    $img_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, board_id, action, reference_id) VALUES (?, ?, 'uploaded_image', ?)");
    $stmt->execute([$user_id, $board_id, $img_id]);

    echo json_encode(['success' => true, 'image_url' => $image_url]);
} catch (Exception $e) {
    unlink($filepath);
    echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos']);
}
