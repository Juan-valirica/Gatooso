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
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se recibió la imagen']);
    exit;
}

$file = $_FILES['avatar'];

// Validate mime type
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Solo se permiten imágenes JPG, PNG o WebP']);
    exit;
}

// Max 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'La imagen no puede pesar más de 5MB']);
    exit;
}

// Create avatars directory
$upload_dir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Process image
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

// Fix EXIF orientation for JPEG
if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
    $exif = @exif_read_data($file['tmp_name']);
    if ($exif && isset($exif['Orientation'])) {
        switch ($exif['Orientation']) {
            case 3:
                $source = imagerotate($source, 180, 0);
                break;
            case 6:
                $source = imagerotate($source, -90, 0);
                break;
            case 8:
                $source = imagerotate($source, 90, 0);
                break;
        }
    }
}

// Crop to square and resize to 400x400
$orig_w = imagesx($source);
$orig_h = imagesy($source);
$size = min($orig_w, $orig_h);

// Calculate crop position (center)
$crop_x = ($orig_w - $size) / 2;
$crop_y = ($orig_h - $size) / 2;

// Create square cropped image
$cropped = imagecreatetruecolor(400, 400);
imagecopyresampled($cropped, $source, 0, 0, $crop_x, $crop_y, 400, 400, $size, $size);
imagedestroy($source);

// Save as optimized JPEG
$filename = 'avatar_' . $user_id . '_' . time() . '.jpg';
$filepath = $upload_dir . $filename;
imagejpeg($cropped, $filepath, 85);
imagedestroy($cropped);

$avatar_url = '/app/uploads/avatars/' . $filename;

try {
    // Auto-migrate: add avatar_url column if missing
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL AFTER password");
    } catch (PDOException $e) {
        // Column already exists, ignore
    }

    // Get old avatar to delete
    $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $oldAvatar = $stmt->fetchColumn();

    // Update user
    $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
    $stmt->execute([$avatar_url, $user_id]);

    // Delete old avatar file
    if ($oldAvatar) {
        $oldPath = __DIR__ . '/..' . $oldAvatar;
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    echo json_encode([
        'success' => true,
        'avatar_url' => $avatar_url
    ]);

} catch (Exception $e) {
    @unlink($filepath);
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar avatar'
    ]);
}
