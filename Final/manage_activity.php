<?php
session_start();
// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['admin_id'])) { 
    header("Location: login.php"); 
    exit; 
}
include 'config.php';

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) {
    die("ไม่พบรหัสการจอง");
}

// --- 1. จัดการการเพิ่มรอบเวลา (บันทึกลงฐานข้อมูล) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_time'])) {
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    if (!empty($start_time) && !empty($end_time)) {
        // บันทึกลงตาราง booth_activities ที่คุณสร้างไว้
        $stmt_add = $conn->prepare("INSERT INTO booth_activities (booking_id, start_time, end_time) VALUES (?, ?, ?)");
        $stmt_add->execute([$booking_id, $start_time, $end_time]);
        header("Location: manage_activity.php?id=" . $booking_id);
        exit;
    }
}

// --- 2. ดึงข้อมูลการจองเพื่อมาโชว์หัวข้อ ---
$sql = "SELECT b.*, e.event_name, t.type_name, o.shop_name 
        FROM event_bookings b
        JOIN events e ON b.event_id = e.id
        JOIN booth_types t ON b.type_id = t.id
        JOIN booth_owners o ON b.owner_id = o.id
        WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

// ดึงรายการรอบเวลาทั้งหมดของ ID นี้
$stmt_times = $conn->prepare("SELECT * FROM booth_activities WHERE booking_id = ? ORDER BY start_time ASC");
$stmt_times->execute([$booking_id]);
$activities = $stmt_times->fetchAll();

// --- 3. เตรียมข้อมูลสำหรับ QR Code ---
// สร้าง Link ที่จะให้ลูกค้าสแกน (ปรับเปลี่ยนตามชื่อไฟล์จริงของคุณ)
$qr_data = "https://yourdomain.com/activity_details.php?id=" . $booking_id;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการกิจกรรม | SU Web Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        :root { --su-green: #3a8173; }
        body { background-color: #f8f9fa; font-family: 'Sarabun', sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn-su { background: var(--su-green); color: white; border: none; }
        .btn-su:hover { background: #2d6358; color: white; }
        #qrcode img { margin: 0 auto; border: 10px solid white; border-radius: 10px; }
        @media print { .no-print { display: none; } .card { box-shadow: none; border: 1px solid #eee; } }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="mb-4 no-print d-flex justify-content-between">
        <a href="admin_bookings.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> กลับหน้าแรก</a>
        <h4 class="fw-bold text-dark">จัดการกิจกรรมร้าน: <?php echo htmlspecialchars($booking['shop_name']); ?></h4>
    </div>

    <div class="row g-4">
        <div class="col-lg-8 no-print">
            <div class="card p-4 mb-4">
                <h5 class="fw-bold mb-3 text-success">เพิ่มรอบเวลากิจกรรม</h5>
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">เวลาเริ่ม</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">เวลาสิ้นสุด</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_time" class="btn btn-su w-100 fw-bold">เพิ่ม</button>
                    </div>
                </form>
            </div>

            <div class="card overflow-hidden">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">รอบที่</th>
                            <th>เวลาจัดกิจกรรม</th>
                            <th class="text-end pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($activities) > 0): ?>
                            <?php foreach ($activities as $index => $act): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted"><?php echo $index + 1; ?></td>
                                <td><i class="far fa-clock me-2 text-primary"></i><?php echo date('H:i', strtotime($act['start_time'])); ?> - <?php echo date('H:i', strtotime($act['end_time'])); ?> น.</td>
                                <td class="text-end pe-4">
                                    <a href="delete_activity.php?id=<?php echo $act['id']; ?>&booking_id=<?php echo $booking_id; ?>" 
                                       class="btn btn-sm btn-outline-danger" onclick="return confirm('คุณต้องการลบรอบเวลานี้ใช่หรือไม่?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-5 text-muted">ยังไม่มีข้อมูลรอบเวลา โปรดเพิ่มข้อมูลด้านบน</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-4 text-center">
            <div class="card p-4 shadow-sm border-0">
                <h5 class="fw-bold mb-3">QR Code สำหรับลูกค้า</h5>
                <div class="my-4 d-flex justify-content-center">
                    <div id="qrcode"></div>
                </div>
                <div class="p-2 bg-light rounded-3 mb-3">
                    <p class="mb-1 small fw-bold">ร้าน: <?php echo htmlspecialchars($booking['shop_name']); ?></p>
                    <p class="mb-0 x-small text-muted"><?php echo htmlspecialchars($booking['event_name']); ?></p>
                </div>
                <div class="no-print">
                    <button onclick="window.print()" class="btn btn-dark w-100 fw-bold">
                        <i class="fas fa-print me-2"></i>พิมพ์ใบ QR Code
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var qrcode = new QRCode(document.getElementById("qrcode"), {
        text: "<?php echo $qr_data; ?>", // ข้อมูลที่จะใส่ใน QR Code
        width: 200,
        height: 200,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
</script>

</body>
</html>