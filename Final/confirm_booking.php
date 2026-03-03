<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event_id'];
    $type_id = $_POST['type_id'];
    $customer_name = $_POST['customer_name'];
    $customer_phone = $_POST['customer_phone'];

    try {
        $conn->beginTransaction();

        // 1. ตรวจสอบจำนวนบูธที่เหลืออยู่จริงใน Database
        $stmt_check = $conn->prepare("SELECT total_slots, type_name FROM booth_types WHERE id = ? FOR UPDATE");
        $stmt_check->execute([$type_id]);
        $booth_info = $stmt_check->fetch();

        // นับจำนวนที่จองไปแล้ว
        $stmt_count = $conn->prepare("SELECT COUNT(*) as booked FROM event_bookings WHERE type_id = ? AND booking_status != 'cancelled'");
        $stmt_count->execute([$type_id]);
        $booked_count = $stmt_count->fetch()['booked'];

        if ($booked_count < $booth_info['total_slots']) {
            // 2. ถ้ายังมีที่ว่าง ให้บันทึกการจอง
            $sql_booking = "INSERT INTO event_bookings (event_id, type_id, customer_name, customer_phone, booking_status) 
                            VALUES (?, ?, ?, ?, 'pending')";
            $stmt_booking = $conn->prepare($sql_booking);
            $stmt_booking->execute([$event_id, $type_id, $customer_name, $customer_phone]);

            $conn->commit();
            $success = true;
        } else {
            // ถ้าเต็มแล้วให้ Rollback
            $conn->rollBack();
            $success = false;
            $error_msg = "ขออภัย บูธประเภท " . $booth_info['type_name'] . " เต็มแล้ว";
        }

    } catch (Exception $e) {
        $conn->rollBack();
        $success = false;
        $error_msg = "เกิดข้อผิดพลาดทางเทคนิค: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ผลการจองบูธ | SU Web Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; }
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; }
        .result-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .icon-circle { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 40px; }
    </style>
</head>
<body>

<div class="container text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card result-card p-5">
                <?php if ($success): ?>
                    <div class="icon-circle bg-success text-white shadow-sm">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 class="fw-bold text-success">จองบูธสำเร็จ!</h2>
                    <p class="text-muted mt-3">ระบบได้รับข้อมูลการจองของคุณเรียบร้อยแล้ว เจ้าหน้าที่จะติดต่อกลับที่เบอร์ <b><?php echo htmlspecialchars($customer_phone); ?></b> เพื่อยืนยันอีกครั้ง</p>
                    <a href="index.php" class="btn btn-success mt-4 px-5 shadow-sm" style="background: var(--su-green); border: none;">กลับหน้าหลัก</a>
                <?php else: ?>
                    <div class="icon-circle bg-danger text-white shadow-sm">
                        <i class="fas fa-times"></i>
                    </div>
                    <h2 class="fw-bold text-danger">จองบูธไม่สำเร็จ</h2>
                    <p class="text-muted mt-3"><?php echo $error_msg; ?></p>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary mt-4 px-5">กลับไปเลือกใหม่</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>