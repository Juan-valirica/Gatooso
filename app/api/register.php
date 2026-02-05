<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$name || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
try {
    $stmt->execute([$name, $email, $hash]);
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['user_name'] = $name;
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Este correo ya existe']);
}
