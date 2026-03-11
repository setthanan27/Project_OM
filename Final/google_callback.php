<?php
session_start();
include 'config.php';

// 1. รับค่า Token และ ID บูธจาก URL
$id_token = $_GET['id_token'] ?? null;
$booking_id = $_GET['id'] ?? null;

if ($id_token) {
    try {
        // 2. ถอดรหัส JWT Token (ส่วนที่ 2 คือ Payload ที่เก็บข้อมูลผู้ใช้)
        $token_parts = explode('.', $id_token);
        if (count($token_parts) !== 3) {
            throw new Exception("รูปแบบ Token ไม่ถูกต้อง");
        }

        // แปลง Base64Url เป็น Base64 ปกติแล้วถอดรหัส
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $token_parts[1]));
        $google_data = json_decode($payload, true);

        if ($google_data && isset($google_data['sub'])) {
            // 3. บันทึกข้อมูลลงใน Session เพื่อระบุตัวตนผู้ใช้ในระบบ
            $_SESSION['user_id'] = $google_data['sub'];       // รหัสเฉพาะของ Google
            $_SESSION['user_name'] = $google_data['name'];     // ชื่อผู้ใช้งาน
            $_SESSION['user_email'] = $google_data['email'];   // อีเมล
            $_SESSION['user_picture'] = $google_data['picture']; // รูปโปรไฟล์
            $_SESSION['user_logged_in'] = true;               // สถานะการล็อกอิน

            // 4. ส่งผู้ใช้กลับไปยังหน้ากิจกรรมของบูธเดิม
            header("Location: activity_details.php?id=" . $booking_id);
            exit;
        } else {
            throw new Exception("ไม่สามารถดึงข้อมูลจาก Google ได้");
        }

    } catch (Exception $e) {
        // กรณีเกิดข้อผิดพลาด
        die("การล็อกอินผิดพลาด: " . $e->getMessage());
    }
} else {
    header("Location: google_login_page.php?id=" . $booking_id . "&error=no_token");
    exit;
}
?>