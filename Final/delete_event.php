<?php
session_start();
if (!isset($_SESSION['admin_id'])) { exit; }
include 'config.php';

if (isset($_GET['id'])) {
    $event_id = $_GET['id'];

    try {
        $conn->beginTransaction();

        // 1. ลบรายการจองที่เกี่ยวข้องกับอีเวนท์นี้
        $stmt1 = $conn->prepare("DELETE FROM event_bookings WHERE event_id = ?");
        $stmt1->execute([$event_id]);

        // 2. ลบประเภทบูธที่เกี่ยวข้องกับอีเวนท์นี้
        $stmt2 = $conn->prepare("DELETE FROM booth_types WHERE event_id = ?");
        $stmt2->execute([$event_id]);

        // 3. ลบตัวงานอีเวนท์
        $stmt3 = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt3->execute([$event_id]);

        $conn->commit();
        header("Location: admin_panel.php?msg=deleted");
    } catch (Exception $e) {
        $conn->rollBack();
        echo "ไม่สามารถลบข้อมูลได้: " . $e->getMessage();
    }
}
?>