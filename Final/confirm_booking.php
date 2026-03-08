<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event_id'];
    $type_id = $_POST['type_id'];
    $owner_id = $_SESSION['owner_id'];
    $customer_name = $_SESSION['shop_name'];
    // รับเบอร์โทรจากหน้าก่อนหน้า (ถ้ามี) หรือกำหนดค่าเริ่มต้น
    $customer_phone = $_POST['customer_phone'] ?? '000-000-0000';
    $slip_name = null;

    // 1. จัดการอัปโหลดไฟล์สลิป (ถ้ามีการแนบมา)
    if (!empty($_FILES['payment_slip']['name'])) {
        $target_dir = "uploads/slips/";
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!is_dir($target_dir)) { 
            mkdir($target_dir, 0777, true); 
        }
        
        $ext = pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION);
        $slip_name = "slip_" . time() . "_" . $owner_id . "." . $ext;
        move_uploaded_file($_FILES['payment_slip']['tmp_name'], $target_dir . $slip_name);
    }

    try {
        $conn->beginTransaction();
        
        // 2. ตรวจสอบจำนวนบูธคงเหลืออีกครั้ง (ป้องกันการจองซ้ำซ้อนในวินาทีเดียวกัน)
        $stmt_check = $conn->prepare("SELECT total_slots FROM booth_types WHERE id = ? FOR UPDATE");
        $stmt_check->execute([$type_id]);
        $total = $stmt_check->fetchColumn();

        $stmt_count = $conn->prepare("SELECT COUNT(*) FROM event_bookings WHERE type_id = ? AND booking_status != 'cancelled'");
        $stmt_count->execute([$type_id]);
        $booked = $stmt_count->fetchColumn();

        if ($booked < $total) {
            // 3. บันทึกข้อมูลการจอง
            $sql = "INSERT INTO event_bookings (event_id, type_id, owner_id, customer_name, customer_phone, payment_slip, booking_status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$event_id, $type_id, $owner_id, $customer_name, $customer_phone, $slip_name]);
            
            $conn->commit();
            // เมื่อจองสำเร็จ ให้ส่งไปหน้า success
            header("Location: booking_success.php");
            exit;
        } else {
            $conn->rollBack();
            echo "<script>alert('ขออภัย บูธประเภทนี้เต็มแล้ว'); window.location.href='index.php';</script>";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>