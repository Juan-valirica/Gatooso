<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
}
