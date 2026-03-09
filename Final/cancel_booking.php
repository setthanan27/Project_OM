<?php
session_start();
include 'config.php';

if (!isset($_SESSION['owner_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$booking_id = $_GET['id'];
$owner_id = $_SESSION['owner_id'];

// อัปเดตสถานะการจองเป็น cancelled
$sql = "UPDATE event_bookings SET booking_status = 'cancelled' WHERE id = ? AND owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$booking_id, $owner_id]);

// กลับไปหน้า index
header("Location: index.php");
exit;
?>