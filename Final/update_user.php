<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include 'config.php';

// รับค่าจาก URL
$user_id = isset($_GET['id']) ? $_GET['id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($user_id && $action) {
    // กำหนดค่า status ตาม action ที่ส่งมา
    $status = 'pending';
    if ($action == 'approve') $status = 'approved';
    if ($action == 'reject') $status = 'rejected';
    if ($action == 'pending') $status = 'pending';

    try {
        $stmt = $conn->prepare("UPDATE booth_owners SET status = ? WHERE id = ?");
        $stmt->execute([$status, $user_id]);
        
        // ส่งกลับไปหน้าเดิมพร้อมข้อความสำเร็จ (Optional)
        header("Location: admin_users.php?msg=success");
    } catch (PDOException $e) {
        die("เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $e->getMessage());
    }
} else {
    header("Location: admin_users.php");
}