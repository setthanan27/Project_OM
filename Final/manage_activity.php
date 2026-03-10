<?php
session_start();
// ตรวจสอบสิทธิ์การเข้าใช้งาน
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['owner_id'])) { 
    header("Location: login.php"); 
    exit; 
}
include 'config.php';

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) die("ไม่พบรหัสการจอง");

// --- 1. จัดการการเพิ่มรอบเวลากิจกรรม ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_time'])) {
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $max_slots = $_POST['max_slots'] ?: 10;
    
    if (!empty($start_time) && !empty($end_time)) {
        $stmt_add = $conn->prepare("INSERT INTO booth_activities (booking_id, start_time, end_time, max_slots, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt_add->execute([$booking_id, $start_time, $end_time, $max_slots]);
        header("Location: manage_activity.php?id=" . $booking_id);
        exit;
    }
}

// --- 2. ดึงข้อมูลรายละเอียดบูธและกิจกรรม ---
$sql = "SELECT b.*, e.event_name, o.shop_name 
        FROM event_bookings b
        JOIN events e ON b.event_id = e.id
        JOIN booth_owners o ON b.owner_id = o.id
        WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

// ดึงรอบเวลา พร้อมนับจำนวนคนจองและสถานะรอบ
$stmt_times = $conn->prepare("
    SELECT a.*, 
    (SELECT COUNT(*) FROM user_activity_reservations r WHERE r.activity_id = a.id) as current_booked 
    FROM booth_activities a 
    WHERE a.booking_id = ? 
    ORDER BY a.start_time ASC
");
$stmt_times->execute([$booking_id]);
$activities = $stmt_times->fetchAll();

$back_link = isset($_SESSION['owner_id']) ? "index.php?filter=confirmed" : "admin_bookings.php";
$qr_data = "https://yourdomain.com/activity_details.php?id=" . $booking_id;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการกิจกรรม | EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root { --su-green: #3a8173; }
        body { background-color: #f8f9fa; font-family: 'Sarabun', sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn-su { background: var(--su-green); color: white; }
        .animate-pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <a href="<?php echo $back_link; ?>" class="btn btn-secondary btn-sm rounded-pill px-3">
            <i class="fas fa-arrow-left me-1"></i> ย้อนกลับ
        </a>
        <h4 class="fw-bold">จัดการกิจกรรม: <span class="text-success"><?php echo htmlspecialchars($booking['shop_name']); ?></span></h4>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card p-4 mb-4 border-start border-success border-4">
                <h5 class="fw-bold mb-3 text-success">เพิ่มรอบเวลากิจกรรม</h5>
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="small fw-bold">เวลาเริ่ม</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">เวลาสิ้นสุด</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">จำกัด (คน)</label>
                        <input type="number" name="max_slots" class="form-control" value="10" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_time" class="btn btn-su w-100 fw-bold">เพิ่ม</button>
                    </div>
                </form>
            </div>

            <div class="card overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">รอบที่</th>
                            <th>เวลาจัดกิจกรรม</th>
                            <th class="text-center">จองแล้ว/จำกัด</th>
                            <th>สถานะ</th>
                            <th class="text-end pe-4">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $index => $act): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-muted"><?php echo $index + 1; ?></td>
                            <td><?php echo date('H:i', strtotime($act['start_time'])); ?> - <?php echo date('H:i', strtotime($act['end_time'])); ?> น.</td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?php echo ($act['current_booked'] >= $act['max_slots']) ? 'bg-danger' : 'bg-info'; ?>">
                                    <?php echo $act['current_booked']; ?> / <?php echo $act['max_slots']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($act['status'] == 'calling'): ?>
                                    <span class="badge bg-warning text-dark animate-pulse">กำลังเรียก...</span>
                                <?php elseif ($act['status'] == 'cancelled'): ?>
                                    <span class="badge bg-danger">ยกเลิกแล้ว</span>
                                <?php elseif ($act['status'] == 'finished'): ?>
                                    <span class="badge bg-secondary">จบแล้ว</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">รอเรียก</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($act['status'] == 'pending'): ?>
                                        <a href="update_round_status.php?id=<?php echo $act['id']; ?>&action=call&booking_id=<?php echo $booking_id; ?>" class="btn btn-success">เรียกคิว</a>
                                    <?php elseif ($act['status'] == 'calling'): ?>
                                        <a href="update_round_status.php?id=<?php echo $act['id']; ?>&action=finish&booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">จบกิจกรรม</a>
                                    <?php endif; ?>
                                    
                                    <a href="update_round_status.php?id=<?php echo $act['id']; ?>&action=cancel&booking_id=<?php echo $booking_id; ?>" 
                                       class="btn btn-outline-danger" onclick="return confirm('ยืนยันการยกเลิกรอบนี้?')">ยกเลิก</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-4 text-center">
            <div class="card p-4">
                <h5 class="fw-bold mb-3">QR Code สำหรับลูกค้า</h5>
                <?php if (count($activities) > 0): ?>
                    <div id="qrcode" class="my-4 d-flex justify-content-center"></div>
                    <button onclick="window.print()" class="btn btn-dark w-100 fw-bold"><i class="fas fa-print me-2"></i>พิมพ์ใบ QR Code</button>
                <?php else: ?>
                    <p class="py-5 text-muted">เพิ่มรอบเวลาก่อนเพื่อรับ QR Code</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    <?php if (count($activities) > 0): ?>
    new QRCode(document.getElementById("qrcode"), { text: "<?php echo $qr_data; ?>", width: 200, height: 200 });
    <?php endif; ?>
</script>
</body>
</html>