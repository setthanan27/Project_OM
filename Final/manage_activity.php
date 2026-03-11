<?php
session_start();

// 1. ตรวจสอบสิทธิ์การเข้าใช้งานเฉพาะเจ้าของบูธ (Owner) เท่านั้น
if (!isset($_SESSION['owner_id'])) { 
    header("Location: user_login.php"); // ถ้าไม่ได้ล็อกอินในฐานะ User ให้ไปหน้าล็อกอิน
    exit; 
}

include 'config.php';

$booking_id = $_GET['id'] ?? null;
$owner_id = $_SESSION['owner_id'];

if (!$booking_id) die("ไม่พบรหัสการจอง");

// 2. Security Check: ตรวจสอบว่าผู้ล็อกอินเป็นเจ้าของบูธนี้จริงๆ หรือไม่
$stmt_check_owner = $conn->prepare("SELECT owner_id FROM event_bookings WHERE id = ?");
$stmt_check_owner->execute([$booking_id]);
$actual_owner = $stmt_check_owner->fetchColumn();

if ($actual_owner != $owner_id) {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้"); // ป้องกันการเปลี่ยน ID บน URL
}

// --- 3. จัดการการเพิ่มรอบเวลากิจกรรม ---
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

// --- 4. ดึงข้อมูลรายละเอียดบูธและกิจกรรม ---
$sql = "SELECT b.*, e.event_name, o.shop_name 
        FROM event_bookings b
        JOIN events e ON b.event_id = e.id
        JOIN booth_owners o ON b.owner_id = o.id
        WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

$stmt_times = $conn->prepare("
    SELECT a.*, 
    (SELECT COUNT(*) FROM user_activity_reservations r WHERE r.activity_id = a.id) as current_booked 
    FROM booth_activities a 
    WHERE a.booking_id = ? 
    ORDER BY a.start_time ASC
");
$stmt_times->execute([$booking_id]);
$activities = $stmt_times->fetchAll();

// ลิงก์ย้อนกลับไปหน้าประวัติการจองของผู้ใช้
$back_link = isset($_SESSION['owner_id']) ? "index.php?filter=confirmed" : "admin_bookings.php";

$public_url = "https://felinely-hypochloremic-rosy.ngrok-free.dev"; 
$qr_data = $public_url . "/finals/Project_OM/Final/activity_details.php?id=" . $booking_id;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการกิจกรรมบูธ | <?php echo htmlspecialchars($booking['shop_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root { --su-green: #3a8173; --su-light: #f4f7f6; }
        body { background-color: var(--su-light); font-family: 'Sarabun', sans-serif; color: #444; }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); }
        .header-card { background: linear-gradient(135deg, var(--su-green), #2d6358); color: white; }
        .table { border-collapse: separate; border-spacing: 0 10px; }
        .table tbody tr { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.02); border-radius: 15px; }
        .table tbody td { border: none; padding: 20px 15px; }
        .btn-su { background: var(--su-green); color: white; border-radius: 12px; }
        .btn-su:hover { background: #2d6358; color: white; }
        .animate-pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(0.98); } 100% { transform: scale(1); } }
        @media print { .no-print { display: none; } body { background: white; } }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row mb-4 no-print">
        <div class="col-12">
            <div class="card header-card p-4 d-flex flex-row justify-content-between align-items-center">
                <div>
                    <a href="<?php echo $back_link; ?>" class="btn btn-light btn-sm rounded-pill mb-2 shadow-sm">
                        <i class="fas fa-chevron-left me-1"></i> ย้อนกลับ
                    </a>
                    <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($booking['shop_name']); ?></h2>
                    <p class="mb-0 opacity-75"><i class="fas fa-calendar-alt me-2"></i><?php echo htmlspecialchars($booking['event_name']); ?></p>
                </div>
                <div class="text-end d-none d-md-block">
                    <i class="fas fa-store-alt fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8 no-print">
            <div class="card p-4 mb-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-light p-2 me-3"><i class="fas fa-plus text-success"></i></div>
                    <h5 class="fw-bold mb-0">เพิ่มรอบเวลากิจกรรม</h5>
                </div>
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">เวลาเริ่ม</label>
                        <input type="time" name="start_time" class="form-control form-control-lg border-0 bg-light" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">เวลาสิ้นสุด</label>
                        <input type="time" name="end_time" class="form-control form-control-lg border-0 bg-light" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">จำกัด (คน)</label>
                        <input type="number" name="max_slots" class="form-control form-control-lg border-0 bg-light" value="10" min="1" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="add_time" class="btn btn-su btn-lg w-100 fw-bold">
                            <i class="fas fa-save"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small">
                            <th class="ps-4">ลำดับ</th>
                            <th>ช่วงเวลา</th>
                            <th class="text-center">จอง/จำกัด</th>
                            <th>สถานะ</th>
                            <th class="text-end pe-4">จัดการรอบ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $index => $act): ?>
                        <tr>
                            <td class="ps-4">#<?php echo $index + 1; ?></td>
                            <td class="fw-bold"><?php echo date('H:i', strtotime($act['start_time'])); ?> - <?php echo date('H:i', strtotime($act['end_time'])); ?> น.</td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?php echo ($act['current_booked'] >= $act['max_slots']) ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'; ?>">
                                    <?php echo $act['current_booked']; ?> / <?php echo $act['max_slots']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($act['status'] == 'calling'): ?>
                                    <span class="badge bg-warning text-dark animate-pulse">กำลังเรียก</span>
                                <?php elseif ($act['status'] == 'finished'): ?>
                                    <span class="badge bg-light text-muted">จบกิจกรรม</span>
                                <?php elseif ($act['status'] == 'cancelled'): ?>
                                    <span class="badge bg-danger-subtle text-danger">ยกเลิก</span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-info">รอเรียก</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <?php if ($act['status'] == 'pending'): ?>
                                        <a href="update_round_status.php?id=<?php echo $act['id']; ?>&action=call&booking_id=<?php echo $booking_id; ?>" class="btn btn-sm btn-success"><i class="fas fa-play"></i></a>
                                    <?php elseif ($act['status'] == 'calling'): ?>
                                        <a href="update_round_status.php?id=<?php echo $act['id']; ?>&action=finish&booking_id=<?php echo $booking_id; ?>" class="btn btn-sm btn-primary"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                    <a href="update_round_status.php?id=<?php echo $act['id']; ?>&action=cancel&booking_id=<?php echo $booking_id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยกเลิกรอบนี้?')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 text-center sticky-top" style="top: 100px;">
                <h5 class="fw-bold mb-1">QR Code</h5>
                <p class="small text-muted mb-4">ให้ลูกค้าสแกนเพื่อจองคิวรอบกิจกรรม</p>
                <div id="qrcode" class="d-flex justify-content-center mb-4"></div>
                <button onclick="window.print()" class="btn btn-dark w-100 py-3 rounded-4 fw-bold">
                    <i class="fas fa-print me-2"></i>พิมพ์ป้ายหน้าร้าน
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    new QRCode(document.getElementById("qrcode"), { 
        text: "<?php echo $qr_data; ?>", 
        width: 180, height: 180,
        colorDark : "#2d6358",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
</script>
</body>
</html>