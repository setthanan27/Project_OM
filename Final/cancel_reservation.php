<?php
session_start();
include 'config.php';

$id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if ($id && $user_id) {
    // แทนที่จะลบ (DELETE) ให้เปลี่ยนสถานะ (UPDATE)
    $stmt = $conn->prepare("UPDATE user_activity_reservations SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
}

header("Location: my_activity_history.php?status=cancelled_ok");
exit;