<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์การเข้าใช้งาน
if (!isset($_SESSION['owner_id'])) {
    header("Location: user_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event_id'];
    $type_id = $_POST['type_id'];
    $owner_id = $_SESSION['owner_id'];
    $customer_name = $_SESSION['shop_name']; // ชื่อร้านค้า
    
    // 1. รับค่าที่ส่งมาจากฟอร์มเพิ่มเติม
    $customer_phone = $_POST['customer_phone'] ?? '000-000-0000';
    $activity_detail = $_POST['activity_detail'] ?? ''; 
    
    // จัดการข้อมูลประเภทสินค้า (Checkbox Categories)
    // รวม Array เป็น String เช่น "อาหาร, เครื่องดื่ม"
    $category_list = isset($_POST['categories']) ? implode(", ", $_POST['categories']) : "";

    $slip_name = null;

    // 2. จัดการอัปโหลดไฟล์สลิป
    if (!empty($_FILES['payment_slip']['name'])) {
        $target_dir = "uploads/slips/";
        if (!is_dir($target_dir)) { 
            mkdir($target_dir, 0777, true); 
        }
        
        $ext = strtolower(pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION));
        // ตรวจสอบประเภทไฟล์เบื้องต้น
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($ext, $allowed)) {
            $slip_name = "slip_" . time() . "_" . $owner_id . "." . $ext;
            move_uploaded_file($_FILES['payment_slip']['tmp_name'], $target_dir . $slip_name);
        }
    }

    try {
        $conn->beginTransaction();
        
        // 3. ตรวจสอบจำนวนบูธคงเหลือ (Lock แถวเพื่อป้องกัน Race Condition)
        $stmt_check = $conn->prepare("SELECT total_slots FROM booth_types WHERE id = ? FOR UPDATE");
        $stmt_check->execute([$type_id]);
        $total = $stmt_check->fetchColumn();

        $stmt_count = $conn->prepare("SELECT COUNT(*) FROM event_bookings WHERE type_id = ? AND booking_status != 'cancelled'");
        $stmt_count->execute([$type_id]);
        $booked = $stmt_count->fetchColumn();

        if ($booked < $total) {
            // 4. บันทึกข้อมูลการจอง (เพิ่มฟิลด์ category_list และ activity_detail)
            $sql = "INSERT INTO event_bookings 
                    (event_id, type_id, owner_id, customer_name, customer_phone, payment_slip, booking_status, activity_detail, category_list) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $event_id, 
                $type_id, 
                $owner_id, 
                $customer_name, 
                $customer_phone, 
                $slip_name, 
                $activity_detail,
                $category_list // ฟิลด์ประเภทสินค้าที่เราเพิ่มใหม่
            ]);
            
            $conn->commit();
            header("Location: booking_success.php");
            exit;
        } else {
            $conn->rollBack();
            echo "<script>alert('ขออภัย บูธประเภทนี้เต็มแล้วในขณะที่คุณกำลังทำรายการ'); window.location.href='index.php';</script>";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage());
    }
} else {
    // ถ้าไม่ได้มาด้วย POST ให้เด้งกลับ
    header("Location: index.php");
    exit;
}
?>