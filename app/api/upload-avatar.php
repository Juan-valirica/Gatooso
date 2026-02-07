<?php
session_start();
header('Content-Type: application/json');

// Allow larger uploads - set memory limit for image processing
ini_set('memory_limit', '256M');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Validate file
if (!isset($_FILES['avatar'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibió la imagen']);
    exit;
}

// Handle upload errors with specific messages
$file = $_FILES['avatar'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'La imagen es demasiado grande. Máximo permitido por el servidor.',
        UPLOAD_ERR_FORM_SIZE => 'La imagen es demasiado grande.',
        UPLOAD_ERR_PARTIAL => 'La imagen se subió parcialmente. Intenta de nuevo.',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ninguna imagen.',
        UPLOAD_ERR_NO_TMP_DIR => 'Error del servidor al procesar la imagen.',
        UPLOAD_ERR_CANT_WRITE => 'Error del servidor al guardar la imagen.',
        UPLOAD_ERR_EXTENSION => 'Tipo de archivo no permitido.'
    ];
    $msg = $errorMessages[$file['error']] ?? 'Error al subir la imagen.';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Validate mime type
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/heic', 'image/heif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

// For HEIC/HEIF, mime detection might not work well, check extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($mime, $allowed) && !in_array($ext, ['heic', 'heif'])) {
    echo json_encode(['success' => false, 'message' => 'Solo se permiten imágenes JPG, PNG, WebP, GIF o HEIC']);
    exit;
}

// Create avatars directory
$upload_dir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Process image - handle different formats
$source = null;
$tempFile = $file['tmp_name'];

// Try to create image from various formats
switch ($mime) {
    case 'image/jpeg':
        $source = @imagecreatefromjpeg($tempFile);
        break;
    case 'image/png':
        $source = @imagecreatefrompng($tempFile);
        break;
    case 'image/webp':
        $source = @imagecreatefromwebp($tempFile);
        break;
    case 'image/gif':
        $source = @imagecreatefromgif($tempFile);
        break;
    default:
        // Try GD's generic loader as fallback
        $source = @imagecreatefromstring(file_get_contents($tempFile));
        break;
}

if (!$source) {
    echo json_encode(['success' => false, 'message' => 'No se pudo procesar la imagen. Intenta con otro formato.']);
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
