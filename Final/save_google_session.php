<?php
session_start();
header('Content-Type: application/json');

// รับข้อมูล JSON ที่ส่งมาจากหน้าบ้าน
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['sub'])) {
    // เก็บข้อมูลลงใน Session เพื่อใช้ระบุตัวตนผู้ร่วมงาน (User)
    $_SESSION['user_id'] = $input['sub'];       // รหัส ID จาก Google
    $_SESSION['user_name'] = $input['name'];     // ชื่อที่แสดง
    $_SESSION['user_email'] = $input['email'];   // อีเมล
    $_SESSION['user_picture'] = $input['picture']; // รูปโปรไฟล์
    
    // กำหนดสถานะการ Login เพื่อให้หน้าอื่นๆ ตรวจสอบได้ง่าย
    $_SESSION['user_logged_in'] = true; 
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}