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

// Validate file
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se recibió la imagen']);
    exit;
}

if (!$board_id) {
    echo json_encode(['success' => false, 'message' => 'Primero crea o selecciona un tablero']);
    exit;
}

$file = $_FILES['photo'];
$caption = trim($_POST['caption'] ?? '');

// Validate caption length (max 280 characters)
if (mb_strlen($caption) > 280) {
    $caption = mb_substr($caption, 0, 280);
}

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

// Get or create challenge for this board
$stmt = $pdo->prepare("SELECT id FROM challenges WHERE board_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$board_id]);
$challenge = $stmt->fetch();

if ($challenge) {
    $challenge_id = $challenge['id'];
} else {
    $stmt = $pdo->prepare("INSERT INTO challenges (board_id, title, description, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$board_id, 'La foto más sexy sin querer', 'Esa que no estaba planeada, pero que es el estandarte de tu perfil', $user_id]);
    $challenge_id = $pdo->lastInsertId();
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

// Fix EXIF orientation (phones often store rotated images)
if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
    $exif = @exif_read_data($file['tmp_name']);
    if ($exif && isset($exif['Orientation'])) {
        switch ($exif['Orientation']) {
            case 3: // 180 degrees
                $source = imagerotate($source, 180, 0);
                break;
            case 6: // 90 degrees CW (phone held upright)
                $source = imagerotate($source, -90, 0);
                break;
            case 8: // 90 degrees CCW
                $source = imagerotate($source, 90, 0);
                break;
        }
    }
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
