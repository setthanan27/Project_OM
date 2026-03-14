<?php
session_start();

// รับค่า ID บูธที่จะกลับไป (ถ้ามี)
$back_id = $_GET['back_to'] ?? null;

// ล้าง Session ทั้งหมด
session_destroy();

// ถ้ามี ID บูธ ให้ส่งกลับไปหน้าจองกิจกรรมของบูธนั้น
if ($back_id) {
    header("Location: activity_details.php?id=" . $back_id);
} else {
    // ถ้าไม่มีข้อมูลเลย ให้กลับไปหน้า Login หลัก
    header("Location: google_login_page.php");
}
exit;
?>