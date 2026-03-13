<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $event_name = $_POST['event_name'];
    $event_date = $_POST['event_date'];
    $location = $_POST['location'];
    
    // ข้อมูลเพิ่มเติม (ถ้าไม่กรอกจะให้เป็นค่าว่าง)
    $event_detail = $_POST['event_detail'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    $facilities = $_POST['facilities'] ?? '';

    try {
        // เตรียม SQL Statement
        $sql = "INSERT INTO events (event_name, event_date, location, event_detail, contact_phone, instructions, facilities) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        // ประมวลผลการบันทึก
        $result = $stmt->execute([
            $event_name, 
            $event_date, 
            $location, 
            $event_detail, 
            $contact_phone, 
            $instructions, 
            $facilities
        ]);

        if ($result) {
            header("Location: admin_panel.php?status=success");
        } else {
            header("Location: admin_panel.php?status=error");
        }
        exit;

    } catch (PDOException $e) {
        // กรณีเกิดข้อผิดพลาด เช่น คอลัมน์ยังไม่ได้เพิ่มใน DB
        die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage());
    }
} else {
    header("Location: admin_panel.php");
    exit;
}
?>