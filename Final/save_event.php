<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // เริ่มต้น Transaction เพื่อความปลอดภัยของข้อมูล
        $conn->beginTransaction();

        // 1. บันทึกข้อมูลลงตาราง events
        $sql_event = "INSERT INTO events (event_name, event_date, location) VALUES (?, ?, ?)";
        $stmt_event = $conn->prepare($sql_event);
        $stmt_event->execute([
            $_POST['event_name'],
            $_POST['event_date'],
            $_POST['location']
        ]);

        // ดึง ID ของงานที่เพิ่งสร้างล่าสุด
        $event_id = $conn->lastInsertId();

        // 2. บันทึกข้อมูลประเภทบูธ (เนื่องจากส่งมาเป็น Array)
        $booth_types = $_POST['booth_type'];
        $booth_prices = $_POST['booth_price'];
        $booth_qtys = $_POST['booth_qty'];

        $sql_booth = "INSERT INTO booth_types (event_id, type_name, price, total_slots) VALUES (?, ?, ?, ?)";
        $stmt_booth = $conn->prepare($sql_booth);

        foreach ($booth_types as $index => $type_name) {
            // ป้องกันค่าว่าง
            if (!empty($type_name)) {
                $stmt_booth->execute([
                    $event_id,
                    $type_name,
                    $booth_prices[$index],
                    $booth_qtys[$index]
                ]);
            }
        }

        // ยืนยันการบันทึกข้อมูลทั้งหมด
        $conn->commit();

        // เมื่อสำเร็จ ให้เด้งกลับไปหน้า Admin Panel พร้อมแจ้งเตือน
        echo "<script>
                alert('สร้างงานอีเวนท์เรียบร้อยแล้ว!');
                window.location.href = 'admin_panel.php';
              </script>";

    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาด ให้ยกเลิกสิ่งที่ทำค้างไว้ (Rollback)
        $conn->rollBack();
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>