<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์ (ต้องเป็น Admin หรือเจ้าของบูธ)
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['owner_id'])) exit;

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;
$booking_id = $_GET['booking_id'] ?? null;



if ($id && $action == 'cancel') {
    try {
        $conn->beginTransaction();
        
        // 1. ลบรายการจองของลูกค้าที่ผูกกับรอบนี้ออกก่อนเพื่อป้องกัน Data Integrity
        $stmt_del_res = $conn->prepare("DELETE FROM user_activity_reservations WHERE activity_id = ?");
        $stmt_del_res->execute([$id]);
        
        // 2. ลบรอบกิจกรรมนั้นออกจากตาราง booth_activities ทันที
        $stmt_del_act = $conn->prepare("DELETE FROM booth_activities WHERE id = ?");
        $stmt_del_act->execute([$id]);
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        die("เกิดข้อผิดพลาด: " . $e->getMessage());
    }
} elseif ($id && $action) {
    // สำหรับ action อื่นๆ เช่น 'call' หรือ 'finish' ให้ทำเหมือนเดิม
    $status = ($action == 'call') ? 'calling' : 'finished';
    $stmt = $conn->prepare("UPDATE booth_activities SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
}

// ส่งกลับไปยังหน้าจัดการกิจกรรมเดิม
header("Location: manage_activity.php?id=" . $booking_id);
exit;
?>