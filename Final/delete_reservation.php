<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) { exit; }

$reservation_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if ($reservation_id) {
    // ลบเฉพาะรายการที่เป็นของตัวเองเพื่อความปลอดภัย
    $stmt = $conn->prepare("DELETE FROM user_activity_reservations WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$reservation_id, $user_id])) {
        header("Location: my_activity_history.php?status=cancelled");
        exit;
    }
}
header("Location: my_activity_history.php");
?>