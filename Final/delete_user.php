<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    exit;
}
include 'config.php';

$user_id = $_GET['id'] ?? null;

if ($user_id) {
    // ลบข้อมูลสมาชิกจากฐานข้อมูล
    $stmt = $conn->prepare("DELETE FROM booth_owners WHERE id = ?");
    $stmt->execute([$user_id]);
}

// ย้อนกลับไปหน้าจัดการสมาชิก
header("Location: admin_users.php");
exit;
?>