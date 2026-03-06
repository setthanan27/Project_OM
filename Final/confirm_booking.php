<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event_id'];
    $type_id = $_POST['type_id'];
    $owner_id = $_SESSION['owner_id'];
    $customer_name = $_SESSION['shop_name']; // ใช้ชื่อร้านจาก Session
    $customer_phone = $_POST['customer_phone'] ?? '0000000000'; // รับจาก hidden input
    $slip_name = null;

    // อัปโหลดไฟล์สลิป
    if (!empty($_FILES['payment_slip']['name'])) {
        $target_dir = "uploads/slips/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $ext = pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION);
        $slip_name = "slip_" . time() . "_" . $owner_id . "." . $ext;
        move_uploaded_file($_FILES['payment_slip']['tmp_name'], $target_dir . $slip_name);
    }

    try {
        $conn->beginTransaction();
        
        // เช็คที่ว่าง
        $stmt_check = $conn->prepare("SELECT total_slots FROM booth_types WHERE id = ? FOR UPDATE");
        $stmt_check->execute([$type_id]);
        $total = $stmt_check->fetchColumn();

        $stmt_count = $conn->prepare("SELECT COUNT(*) FROM event_bookings WHERE type_id = ? AND booking_status != 'cancelled'");
        $stmt_count->execute([$type_id]);
        $booked = $stmt_count->fetchColumn();

        if ($booked < $total) {
            $stmt = $conn->prepare("INSERT INTO event_bookings (event_id, type_id, owner_id, customer_name, customer_phone, payment_slip, booking_status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$event_id, $type_id, $owner_id, $customer_name, $customer_phone, $slip_name]);
            $conn->commit();
            // ต้องมีไฟล์นี้ในเครื่องด้วย
            header("Location: booking_success.php");
        } else {
            $conn->rollBack();
            echo "<script>alert('ขออภัย บูธเต็มแล้ว'); history.back();</script>";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>