<?php
session_start();
include 'config.php';

$id = $_GET['id'];
$status = $_GET['status'];

$sql = "UPDATE event_bookings SET booking_status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt->execute([$status, $id])) {
    // ส่งกลับมาหน้าเดิมพร้อมแนบพารามิเตอร์ success เพื่อให้ JS แสดงผล
    header("Location: admin_bookings.php?msg=success");
} else {
    echo "เกิดข้อผิดพลาดในการอัปเดต";
}
exit;
?>