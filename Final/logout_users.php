<?php
session_start();

// เคลียร์ค่า Session เฉพาะส่วนของผู้ใช้ Google
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_picture']);
unset($_SESSION['user_logged_in']);

// รับค่า ID บูธเพื่อพากลับไปหน้าเดิม
$back_id = $_GET['back_id'] ?? null;

if ($back_id) {
    header("Location: activity_details.php?id=" . $back_id);
} else {
    header("Location: index.php");
}
exit;
?>