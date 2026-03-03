<?php
session_start();
// ทำลายเฉพาะ Session ของ User แต่คงของ Admin ไว้ (ถ้าเปิดสองแท็บ)
unset($_SESSION['owner_id']);
unset($_SESSION['shop_name']);
header("Location: index.php");
exit;
?>