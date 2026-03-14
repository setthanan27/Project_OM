<?php
session_start();
date_default_timezone_set("Asia/Bangkok"); // ตั้งเวลาให้ตรงกับไทย

// 1. ตรวจสอบสิทธิ์การเข้าใช้งานเฉพาะเจ้าของบูธ (Owner) เท่านั้น
if (!isset($_SESSION['owner_id'])) { 
    header("Location: user_login.php"); 
    exit; 
}

include 'config.php';

$booking_id = $_GET['id'] ?? null;
$owner_id = $_SESSION['owner_id'];

if (!$booking_id) die("ไม่พบรหัสการจอง");

// 2. Security Check: ตรวจสอบเจ้าของบูธ
$stmt_check_owner = $conn->prepare("SELECT owner_id FROM event_bookings WHERE id = ?");
$stmt_check_owner->execute([$booking_id]);
$actual_owner = $stmt_check_owner->fetchColumn();

if ($actual_owner != $owner_id) {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

// --- 3. จัดการการเพิ่มรอบเวลากิจกรรม ---
$error_msg = ""; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_time'])) {
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $max_slots = $_POST['max_slots'] ?: 10;
    
    if (!empty($start_time) && !empty($end_time)) {
        if ($start_time >= $end_time) {
            $error_msg = "เวลาเริ่มกิจกรรมต้องมาก่อนเวลาสิ้นสุด";
        } else {
            $sql_overlap = "SELECT start_time, end_time FROM booth_activities 
                            WHERE booking_id = ? 
                            AND status != 'cancelled' 
                            AND (? < end_time AND ? > start_time)
                            LIMIT 1";
            $stmt_overlap = $conn->prepare($sql_overlap);
            $stmt_overlap->execute([$booking_id, $start_time, $end_time]);
            $overlap_row = $stmt_overlap->fetch();

            if ($overlap_row) {
                $conf_start = date('H:i', strtotime($overlap_row['start_time']));
                $conf_end = date('H:i', strtotime($overlap_row['end_time']));
                $error_msg = "ไม่สามารถเพิ่มได้! เวลาที่คุณเลือกทับซ้อนกับรอบที่มีอยู่แล้ว (ช่วงเวลา $conf_start - $conf_end น.)";
            } else {
                $stmt_add = $conn->prepare("INSERT INTO booth_activities (booking_id, start_time, end_time, max_slots, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt_add->execute([$booking_id, $start_time, $end_time, $max_slots]);
                header("Location: manage_activity.php?id=" . $booking_id);
                exit;
            }
        }
    }
}

// --- 4. ดึงข้อมูลรายละเอียดบูธและกิจกรรม (เพิ่ม e.event_date) ---
$sql = "SELECT b.*, e.event_name, e.event_date, o.shop_name 
        FROM event_bookings b
        JOIN events e ON b.event_id = e.id
        JOIN booth_owners o ON b.owner_id = o.id
        WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

$stmt_times = $conn->prepare("
    SELECT a.*, 
    (SELECT COUNT(*) FROM user_activity_reservations r WHERE r.activity_id = a.id AND r.status != 'cancelled') as current_booked 
    FROM booth_activities a 
    WHERE a.booking_id = ? 
    ORDER BY a.start_time ASC
");
$stmt_times->execute([$booking_id]);
$activities = $stmt_times->fetchAll();

// เตรียมค่าเวลาปัจจุบัน
$now_ts = time();
$today_date = date('Y-m-d');

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
        :root { --su-green: #3a8173; --su-dark: #2d6358; --su-light: #f8fafb; }
        body { background-color: var(--su-light); font-family: 'Sarabun', sans-serif; color: #334155; }
        .card { border: none; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: #ffffff; }
        .header-card { background: linear-gradient(135deg, var(--su-green), var(--su-dark)); color: white; padding: 2.5rem; border-radius: 30px; }
        .table thead th { background: #f1f5f9; border: none; padding: 1rem; color: #64748b; font-weight: 600; font-size: 0.85rem; }
        .table tbody td { padding: 1.25rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .status-badge { padding: 0.5rem 1rem; border-radius: 10px; font-weight: 600; font-size: 0.8rem; }
        .btn-su { background: var(--su-green); color: white; border-radius: 14px; transition: 0.3s; border: none; }
        .btn-su:hover { background: var(--su-dark); color: white; transform: translateY(-2px); }
        .btn-action { width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; }
        .qr-card { border: 2px dashed #e2e8f0; position: sticky; top: 2rem; }
        .bg-su-green { background-color: var(--su-green); color: white; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        .animate-pulse { animation: pulse 1.5s infinite; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            .container { max-width: 100%; width: 100%; }
            .qr-print-area { border: 2px solid #333; padding: 3rem; text-align: center; border-radius: 2rem; max-width: 500px; margin: 2rem auto; }
            #qrcode img { margin: 0 auto; }
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="header-card mb-5 no-print shadow-lg">
        <div class="row align-items-center">
            <div class="col-md-8">
                <a href="<?php echo $back_link; ?>" class="btn btn-light btn-sm rounded-pill px-3 mb-3 text-dark fw-bold">
                    <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
                </a>
                <h1 class="display-5 fw-bold mb-1"><?php echo htmlspecialchars($booking['shop_name']); ?></h1>
                <p class="lead mb-0 opacity-75"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($booking['event_name']); ?></p>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
                <i class="fas fa-store fa-5x opacity-25"></i>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8 no-print">
            <div class="card p-4 mb-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-success bg-opacity-10 p-2 rounded-3 me-3">
                        <i class="fas fa-plus text-success fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-0">เพิ่มรอบกิจกรรม</h4>
                </div>
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger border-0 rounded-4 mb-4">
                        <i class="fas fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">เวลาเริ่ม</label>
                        <input type="time" name="start_time" class="form-control form-control-lg border-0 bg-light rounded-4" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-bold">เวลาสิ้นสุด</label>
                        <input type="time" name="end_time" class="form-control form-control-lg border-0 bg-light rounded-4" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted fw-bold">จำนวนคน</label>
                        <input type="number" name="max_slots" class="form-control form-control-lg border-0 bg-light rounded-4 text-center" value="10" min="1" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="add_time" class="btn btn-su btn-lg w-100 shadow-sm"><i class="fas fa-save"></i></button>
                    </div>
                </form>
            </div>

            <div class="card overflow-hidden">
                <div class="p-4 border-bottom"><h5 class="fw-bold mb-0">รายการรอบกิจกรรมทั้งหมด</h5></div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="ps-4">ลำดับ</th>
                                <th>ช่วงเวลา</th>
                                <th class="text-center">จองแล้ว / จำกัด</th>
                                <th>สถานะ</th>
                                <th class="text-end pe-4">ดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $index => $act): 
                                // คำนวณเงื่อนไขการเรียกคิว
                                $start_ts = strtotime($booking['event_date'] . ' ' . $act['start_time']);
                                $diff_minutes = ($start_ts - $now_ts) / 60;
                                $can_call = ($today_date == $booking['event_date'] && $diff_minutes <= 30);
                                
                                $lock_reason = "";
                                if ($today_date != $booking['event_date']) {
                                    $lock_reason = "เรียกได้เฉพาะวันงาน (" . date('d/m/Y', strtotime($booking['event_date'])) . ")";
                                } elseif ($diff_minutes > 30) {
                                    $lock_reason = "เรียกได้ก่อนเริ่ม 30 นาที";
                                }
                            ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold">#<?php echo $index + 1; ?></td>
                                <td><span class="fs-5 fw-bold"><?php echo date('H:i', strtotime($act['start_time'])); ?> - <?php echo date('H:i', strtotime($act['end_time'])); ?></span></td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center bg-light rounded-pill px-3 py-1">
                                        <span class="fw-bold <?php echo ($act['current_booked'] >= $act['max_slots']) ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo $act['current_booked']; ?> / <?php echo $act['max_slots']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($act['status'] == 'calling'): ?>
                                        <span class="status-badge bg-warning text-dark animate-pulse"><i class="fas fa-bullhorn me-2"></i>กำลังเรียก</span>
                                    <?php elseif ($act['status'] == 'finished'): ?>
                                        <span class="status-badge bg-secondary text-white">จบกิจกรรม</span>
                                    <?php elseif ($act['status'] == 'cancelled'): ?>
                                        <span class="status-badge bg-danger text-white">ยกเลิก</span>
                                    <?php else: ?>
                                        <span class="status-badge bg-info bg-opacity-10 text-info">รอเรียก</span>
                                        <?php if (!$can_call): ?>
                                            <div class="text-danger mt-1" style="font-size: 0.7rem;"><i class="fas fa-lock me-1"></i><?php echo $lock_reason; ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <?php if ($act['status'] == 'pending'): ?>
                                            <?php if ($can_call): ?>
                                                <a href="update_round_status.php?id=<?php echo $act['id']; ?>&action=call&booking_id=<?php echo $booking_id; ?>" class="btn btn-success btn-action shadow-sm" title="เรียกคิว"><i class="fas fa-play"></i></a>
                                            <?php else: ?>
                                                <button class="btn btn-light btn-action text-muted" style="cursor: not-allowed;" title="<?php echo $lock_reason; ?>" disabled><i class="fas fa-play"></i></button>
                                            <?php endif; ?>
                                        <?php elseif ($act['status'] == 'calling'): ?>
                                            <a href="update_round_status.php?id=<?php echo $act['id']; ?>&action=finish&booking_id=<?php echo $booking_id; ?>" class="btn btn-primary btn-action shadow-sm" title="จบงาน"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                        <a href="update_round_status.php?id=<?php echo $act['id']; ?>&action=cancel&booking_id=<?php echo $booking_id; ?>" class="btn btn-outline-danger btn-action" onclick="return confirm('ยกเลิกรอบนี้?')" title="ลบ"><i class="fas fa-trash-can"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 text-center qr-card shadow-lg">
                <div class="qr-print-area">
                    <div class="mb-4">
                        <span class="badge bg-su-green px-3 py-2 rounded-pill mb-3">SCAN TO BOOK</span>
                        <h3 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($booking['shop_name']); ?></h3>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($booking['event_name']); ?></p>
                    </div>
                    <div id="qrcode" class="d-flex justify-content-center mb-4 p-4 bg-white rounded-4 shadow-sm border"></div>
                    <div class="mb-2">
                        <p class="fw-bold text-su-green mb-0">ระบบจองคิวกิจกรรมออนไลน์</p>
                        <small class="text-muted opacity-50">Booth ID: #<?php echo $booking_id; ?></small>
                    </div>
                </div>
                <div class="no-print mt-4 border-top pt-4">
                    <button onclick="window.print()" class="btn btn-dark w-100 py-3 rounded-4 fw-bold mb-3 shadow"><i class="fas fa-print me-2"></i>พิมพ์ป้ายหน้าร้าน</button>
                    <a href="booth_stats.php?id=<?php echo $booking_id; ?>" class="btn btn-outline-primary w-100 py-3 rounded-4 fw-bold"><i class="fas fa-chart-line me-2"></i>ดูสถิติบูธ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    new QRCode(document.getElementById("qrcode"), { 
        text: "<?php echo $qr_data; ?>", 
        width: 220, height: 220, colorDark : "#1e293b", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.H
    });
</script>
</body>
</html>