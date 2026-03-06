<?php
session_start();
if (!isset($_SESSION['admin_id'])) { exit; }
include 'config.php';

$booking_id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;

if ($booking_id && $status) {
    $stmt = $conn->prepare("UPDATE event_bookings SET booking_status = ? WHERE id = ?");
    if ($stmt->execute([$status, $booking_id])) {
        echo "<script>alert('อัปเดตสถานะการจองเรียบร้อย'); window.location.href='admin_bookings.php';</script>";
    }
}
?>