<?php
session_start();
date_default_timezone_set("Asia/Bangkok"); // ตั้งเวลาให้ตรง
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: google_login_page.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$activity_id = $_GET['activity_id'] ?? null;
$booking_id = $_GET['booking_id'] ?? null;

if (!$activity_id) die("ข้อมูลไม่ครบถ้วน");

try {
    $conn->beginTransaction(); // ใช้ Transaction เพื่อความปลอดภัย 100%

    // 1. ดึงข้อมูลรอบและเช็คที่ว่าง (นับเฉพาะคนที่ยังไม่ยกเลิก)
    $stmt = $conn->prepare("
        SELECT a.*, 
        (SELECT COUNT(*) FROM user_activity_reservations r 
         WHERE r.activity_id = a.id AND r.status != 'cancelled') as current_booked 
        FROM booth_activities a WHERE a.id = ? FOR UPDATE
    ");
    $stmt->execute([$activity_id]);
    $slot = $stmt->fetch();

    if (!$slot) throw new Exception("ไม่พบรอบกิจกรรมนี้");
    if ($slot['status'] == 'cancelled') throw new Exception("ขออภัย! รอบกิจกรรมนี้ถูกยกเลิกโดยเจ้าของบูธ");
    if ($slot['current_booked'] >= $slot['max_slots']) throw new Exception("ขออภัย! รอบกิจกรรมนี้เต็มแล้ว");

    $new_start = $slot['start_time'];
    $new_end = $slot['end_time'];

    // 2. ตรวจสอบการจองซ้ำในรอบเดิม (ต้องไม่นับรายการที่เคยยกเลิกไปแล้ว)
    $stmt_dup = $conn->prepare("SELECT COUNT(*) FROM user_activity_reservations WHERE user_id = ? AND activity_id = ? AND status != 'cancelled'");
    $stmt_dup->execute([$user_id, $activity_id]);
    if ($stmt_dup->fetchColumn() > 0) throw new Exception("คุณได้จองรอบเวลานี้ไปแล้ว");

    // 3. ตรวจสอบเวลาชนกับกิจกรรมอื่น (Collision Check)
    // เช็คเฉพาะรอบที่ 'confirmed' หรือ 'calling' เท่านั้น
    $sql_check = "SELECT COUNT(*) FROM user_activity_reservations r
                  WHERE r.user_id = ? AND r.status != 'cancelled'
                  AND (? < r.end_time AND ? > r.start_time)";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$user_id, $new_start, $new_end]);
    if ($stmt_check->fetchColumn() > 0) throw new Exception("ไม่สามารถจองได้! คุณมีกิจกรรมอื่นในช่วงเวลานี้แล้ว");

    // 4. บันทึกการจอง (ระบุ status เป็น confirmed เสมอ)
    $sql_insert = "INSERT INTO user_activity_reservations (user_id, activity_id, start_time, end_time, status) VALUES (?, ?, ?, ?, 'confirmed')";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->execute([$user_id, $activity_id, $new_start, $new_end]);

    $conn->commit();
    header("Location: my_activity_history.php?status=success");
    exit;

} catch (Exception $e) {
    $conn->rollBack();
    $msg = $e->getMessage();
    echo "<script>
            alert('$msg');
            window.location.href = 'activity_details.php?id=$booking_id';
          </script>";
}