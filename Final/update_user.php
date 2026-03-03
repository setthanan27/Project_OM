<?php
session_start();
// ตรวจสอบว่าเป็น Admin หรือไม่
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include 'config.php';

$user_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($user_id && $action) {
    // กำหนดสถานะตาม Action ที่ส่งมา
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    try {
        $stmt = $conn->prepare("UPDATE booth_owners SET status = ? WHERE id = ?");
        $stmt->execute([$status, $user_id]);

        // ส่งกลับไปหน้าเดิมพร้อมข้อความแจ้งเตือน
        echo "<script>
                alert('ดำเนินการเรียบร้อยแล้ว');
                window.location.href = 'admin_users.php';
              </script>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: admin_users.php");
}
?>