<?php
session_start();
// ตรวจสอบสิทธิ์ Admin อีกครั้งเพื่อความปลอดภัย
if (!isset($_SESSION['admin_id'])) { 
    exit("Unauthorized Access");
}

include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // เริ่มต้น Transaction
        $conn->beginTransaction();

        // 1. บันทึกข้อมูลลงตาราง events (เพิ่มฟิลด์ตามหน้าฟอร์มที่มอสสร้าง)
        $sql_event = "INSERT INTO events (
                        event_name, 
                        event_date, 
                        location, 
                        contact_phone, 
                        event_detail, 
                        instructions, 
                        facilities
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_event = $conn->prepare($sql_event);
        $stmt_event->execute([
            $_POST['event_name'],
            $_POST['event_date'],
            $_POST['location'],
            $_POST['contact_phone'], // ฟิลด์ใหม่
            $_POST['event_detail'],  // ฟิลด์ใหม่
            $_POST['instructions'],  // ฟิลด์ใหม่
            $_POST['facilities']     // ฟิลด์ใหม่
        ]);

        // ดึง ID ของงานที่เพิ่งสร้างล่าสุด
        $event_id = $conn->lastInsertId();

        // 2. บันทึกข้อมูลประเภทบูธ (Array)
        if (isset($_POST['booth_type'])) {
            $booth_types = $_POST['booth_type'];
            $booth_prices = $_POST['booth_price'];
            $booth_qtys = $_POST['booth_qty'];

            $sql_booth = "INSERT INTO booth_types (event_id, type_name, price, total_slots) VALUES (?, ?, ?, ?)";
            $stmt_booth = $conn->prepare($sql_booth);

            foreach ($booth_types as $index => $type_name) {
                if (!empty($type_name)) {
                    $stmt_booth->execute([
                        $event_id,
                        $type_name,
                        $booth_prices[$index] ?: 0, // ถ้าว่างให้เป็น 0
                        $booth_qtys[$index] ?: 1    // ถ้าว่างให้เป็น 1
                    ]);
                }
            }
        }

        // ยืนยันการบันทึกข้อมูลทั้งหมด
        $conn->commit();

        echo "<script>
                alert('สร้างงานอีเวนท์และประเภทบูธเรียบร้อยแล้ว!');
                window.location.href = 'admin_panel.php';
              </script>";

    } catch (Exception $e) {
        $conn->rollBack();
        die("เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage());
    }
} else {
    header("Location: admin_create_event.php");
    exit;
}
?>