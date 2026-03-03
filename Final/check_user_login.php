<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // ดึงข้อมูลจากตาราง booth_owners
    $stmt = $conn->prepare("SELECT * FROM booth_owners WHERE username = ?");
    $stmt->execute([$user]);
    $owner = $stmt->fetch();

    if ($owner && password_verify($pass, $owner['password'])) {
        // ตรวจสอบว่า Admin อนุมัติหรือยัง
        if ($owner['status'] === 'approved') {
            // เก็บ Session สำหรับ User
            $_SESSION['owner_id'] = $owner['id'];
            $_SESSION['shop_name'] = $owner['shop_name'];
            $_SESSION['owner_name'] = $owner['owner_name'];
            
            header("Location: index.php");
            exit;
        } else if ($owner['status'] === 'pending') {
            $_SESSION['error'] = "บัญชีของคุณอยู่ระหว่างรอการอนุมัติจากเจ้าหน้าที่";
            header("Location: user_login.php");
            exit;
        } else {
            $_SESSION['error'] = "บัญชีของคุณถูกปฏิเสธการเข้าใช้งาน";
            header("Location: user_login.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        header("Location: user_login.php");
        exit;
    }
}