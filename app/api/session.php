<?php
session_start();

echo json_encode([
    'authenticated' => isset($_SESSION['user_id'])
]);
