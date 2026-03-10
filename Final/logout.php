<?php
session_start();

// รับค่า ID ของบูธที่ต้องการกลับไป (ถ้ามี)
$back_id = $_GET['back_id'] ?? null;

// ทำลาย Session ทั้งหมด
session_destroy();

// ตรวจสอบเงื่อนไขการ Redirect
if ($back_id) {
    // กลับไปหน้ากิจกรรมเดิม
    header("Location: activity_details.php?id=" . $back_id);
} else {
    // กรณีไม่มี ID ให้กลับหน้า Login หลัก
    header("Location: login.php");
}
exit;
?>