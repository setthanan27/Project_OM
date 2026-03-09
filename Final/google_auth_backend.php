<?php
session_start();
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['sub'])) {
    $_SESSION['user_id'] = $input['sub'];
    $_SESSION['user_name'] = $input['name'];
    $_SESSION['user_email'] = $input['email'];
    $_SESSION['user_picture'] = $input['picture'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}