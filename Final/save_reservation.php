<?php
session_start();
include 'config.php';

// 1. ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: google_login_page.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$activity_id = $_GET['activity_id'] ?? null;
$booking_id = $_GET['booking_id'] ?? null; // รับ ID บูธเพื่อใช้ Redirect กลับกรณีผิดพลาด

if (!$activity_id) die("ข้อมูลไม่ครบถ้วน");

try {
    // 2. ดึงข้อมูลรอบกิจกรรมและตรวจสอบที่ว่าง (Capacity Check)
    $stmt = $conn->prepare("
        SELECT a.*, 
        (SELECT COUNT(*) FROM user_activity_reservations r WHERE r.activity_id = a.id) as current_booked 
        FROM booth_activities a WHERE a.id = ?
    ");
    $stmt->execute([$activity_id]);
    $slot = $stmt->fetch();

    if (!$slot) throw new Exception("ไม่พบรอบกิจกรรมนี้");
    if ($slot['current_booked'] >= $slot['max_slots']) throw new Exception("ขออภัย! รอบกิจกรรมนี้เต็มแล้ว");

    $new_start = $slot['start_time'];
    $new_end = $slot['end_time'];

    // 3. ตรวจสอบการจองซ้ำในรอบเดียวกัน
    $stmt_dup = $conn->prepare("SELECT COUNT(*) FROM user_activity_reservations WHERE user_id = ? AND activity_id = ?");
    $stmt_dup->execute([$user_id, $activity_id]);
    if ($stmt_dup->fetchColumn() > 0) throw new Exception("คุณได้จองรอบเวลานี้ไปแล้ว");

    // 4. ตรวจสอบเวลาชนกับกิจกรรมอื่น (Collision Check)
    // คัดกรองเฉพาะรอบที่ยังไม่จบหรือถูกยกเลิก
    $sql_check = "SELECT COUNT(*) FROM user_activity_reservations r
                  JOIN booth_activities a ON r.activity_id = a.id
                  WHERE r.user_id = ? AND a.status != 'cancelled'
                  AND (? < r.end_time) AND (? > r.start_time)";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$user_id, $new_start, $new_end]);
    if ($stmt_check->fetchColumn() > 0) throw new Exception("ไม่สามารถจองได้! คุณมีกิจกรรมอื่นในช่วงเวลานี้แล้ว");

    // 5. บันทึกการจอง
    $sql_insert = "INSERT INTO user_activity_reservations (user_id, activity_id, start_time, end_time) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->execute([$user_id, $activity_id, $new_start, $new_end]);

    // แจ้งผลสำเร็จด้วยการส่งค่าไปหน้าประวัติ
    header("Location: my_activity_history.php?status=success");
    exit;

} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด ส่งกลับหน้าเดิมพร้อมข้อความ
    $msg = $e->getMessage();
    echo "<script>
            alert('$msg');
            window.location.href = 'activity_details.php?id=$booking_id';
          </script>";
}