<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: activity_details.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$activity_id = $_GET['activity_id'];

// 1. ดึงเวลาของรอบที่กำลังจะจอง
$stmt = $conn->prepare("SELECT * FROM booth_activities WHERE id = ?");
$stmt->execute([$activity_id]);
$new_slot = $stmt->fetch();

$new_start = $new_slot['start_time'];
$new_end = $new_slot['end_time'];

// 2. ตรวจสอบเวลาชน (Collision Check)
// เงื่อนไข: (เวลาเริ่มใหม่ < เวลาจบเดิม) AND (เวลาจบใหม่ > เวลาเริ่มเดิม)
$sql_check = "SELECT COUNT(*) FROM user_activity_reservations 
              WHERE user_id = ? 
              AND (? < end_time) AND (? > start_time)";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->execute([$user_id, $new_start, $new_end]);
$is_overlapping = $stmt_check->fetchColumn() > 0;

if ($is_overlapping) {
    echo "<script>
            alert('ไม่สามารถจองได้! คุณมีกิจกรรมอื่นในช่วงเวลานี้แล้ว');
            window.history.back();
          </script>";
} else {
    // 3. บันทึกการจองหากเวลาไม่ชน
    $sql_insert = "INSERT INTO user_activity_reservations (user_id, activity_id, start_time, end_time) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->execute([$user_id, $activity_id, $new_start, $new_end]);

    echo "<script>
            alert('จองกิจกรรมสำเร็จ!');
            window.location.href = 'my_activity_history.php'; 
          </script>";
}