<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์ (Admin หรือ Owner)
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['owner_id'])) { exit; }

$id = $_GET['id'] ?? null;
$booking_id = $_GET['booking_id'] ?? null;

if ($id && $booking_id) {
    try {
        $conn->beginTransaction();
        
        // 1. ลบข้อมูลการจองของลูกค้าในรอบนั้นก่อน (ถ้ามี)
        $stmt1 = $conn->prepare("DELETE FROM user_activity_reservations WHERE activity_id = ?");
        $stmt1->execute([$id]);
        
        // 2. ลบรอบกิจกรรม
        $stmt2 = $conn->prepare("DELETE FROM booth_activities WHERE id = ?");
        $stmt2->execute([$id]);
        
        $conn->commit();
        header("Location: manage_activity.php?id=" . $booking_id);
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>
