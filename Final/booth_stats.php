<?php
session_start();
include 'config.php';

// 1. ตรวจสอบสิทธิ์เจ้าของบูธ
if (!isset($_SESSION['owner_id'])) { 
    header("Location: user_login.php"); 
    exit; 
}

$booking_id = $_GET['id'] ?? null;
$owner_id = $_SESSION['owner_id'];

if (!$booking_id) die("ไม่พบรหัสการจอง");

// 2. ดึงข้อมูลรายละเอียดบูธและชื่อร้าน
$stmt_booth = $conn->prepare("SELECT b.*, e.event_name, o.shop_name FROM event_bookings b JOIN events e ON b.event_id = e.id JOIN booth_owners o ON b.owner_id = o.id WHERE b.id = ?");
$stmt_booth->execute([$booking_id]);
$booth = $stmt_booth->fetch();

if ($booth['owner_id'] != $owner_id) die("คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้");

// 3. ดึงข้อมูลสถิติรายรอบกิจกรรม (แก้ไขชื่อคอลัมน์จาก round_status เป็น status)
$sql = "SELECT a.*, 
        (SELECT COUNT(*) FROM user_activity_reservations r 
         WHERE r.activity_id = a.id AND r.status != 'cancelled') as total_booked,
        (SELECT COUNT(*) FROM user_activity_reservations r 
         WHERE r.activity_id = a.id AND r.status = 'cancelled') as total_user_cancelled
        FROM booth_activities a 
        WHERE a.booking_id = ? 
        ORDER BY a.start_time ASC";
$stmt = $conn->prepare($sql);
$stmt->execute([$booking_id]);
$stats = $stmt->fetchAll();

// 4. คำนวณภาพรวม (Overview)
$grand_total_participants = 0;
$grand_total_capacity = 0;
$grand_total_cancelled = 0;

foreach ($stats as $row) {
    if ($row['status'] != 'cancelled') {
        $grand_total_participants += $row['total_booked'];
        $grand_total_capacity += $row['max_slots'];
        $grand_total_cancelled += $row['total_user_cancelled'];
    }
}
$fill_rate = ($grand_total_capacity > 0) ? round(($grand_total_participants / $grand_total_capacity) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถิติบูธ - <?php echo htmlspecialchars($booth['shop_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --su-dark: #2d6358; }
        body { background-color: #f4f7f6; font-family: 'Sarabun', sans-serif; color: #333; }
        
        /* Layout */
        .header-stats { background: white; border-bottom: 1px solid #eee; padding: 25px 0; margin-bottom: 30px; }
        .card-stat { border: none; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); background: white; transition: 0.3s; }
        .card-stat:hover { transform: translateY(-5px); }
        
        /* Stats Styles */
        .text-su { color: var(--su-green); }
        .bg-su-soft { background-color: #f0f7f5; }
        .progress { height: 8px; border-radius: 10px; background-color: #eee; }
        .table-stats { border-radius: 20px; overflow: hidden; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        
        .badge-cancelled { background-color: #fff0f0; color: #e03131; border: 1px solid #ffc9c9; }
        
        @media print { .no-print { display: none; } body { background: white; } .card-stat { border: 1px solid #eee; } }
    </style>
</head>
<body>

<div class="header-stats no-print">
    <div class="container d-flex justify-content-between align-items-center px-4">
        <div class="d-flex align-items-center">
            <a href="manage_activity.php?id=<?php echo $booking_id; ?>" class="btn btn-outline-secondary rounded-pill me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($booth['shop_name']); ?></h4>
                <p class="text-muted small mb-0"><i class="fas fa-calendar-check me-1"></i> รายงานสถิติกิจกรรมรายวัน</p>
            </div>
        </div>
        <button onclick="window.print()" class="btn btn-dark rounded-pill px-4 fw-bold">
            <i class="fas fa-print me-2"></i> พิมพ์รายงาน
        </button>
    </div>
</div>

<div class="container px-4 mb-5">
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card card-stat p-4 h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3"><i class="fas fa-users text-primary"></i></div>
                    <span class="small text-muted fw-bold">ผู้เข้าร่วมจริง</span>
                </div>
                <h2 class="fw-bold mb-0"><?php echo $grand_total_participants; ?> <small class="fs-6 fw-normal text-muted">คน</small></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat p-4 h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-su-soft p-2 rounded-3 me-3"><i class="fas fa-chart-pie text-su"></i></div>
                    <span class="small text-muted fw-bold">อัตราการเติมเต็ม</span>
                </div>
                <h2 class="fw-bold text-su mb-0"><?php echo $fill_rate; ?><small class="fs-6">%</small></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat p-4 h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-danger bg-opacity-10 p-2 rounded-3 me-3"><i class="fas fa-user-minus text-danger"></i></div>
                    <span class="small text-muted fw-bold">ยกเลิกคืนสิทธิ์</span>
                </div>
                <h2 class="fw-bold text-danger mb-0"><?php echo $grand_total_cancelled; ?> <small class="fs-6 fw-normal text-muted">ครั้ง</small></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat p-4 h-100 border-start border-su border-5">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-light p-2 rounded-3 me-3"><i class="fas fa-chair text-muted"></i></div>
                    <span class="small text-muted fw-bold">ความจุรวม</span>
                </div>
                <h2 class="fw-bold mb-0"><?php echo $grand_total_capacity; ?> <small class="fs-6 fw-normal text-muted">ที่นั่ง</small></h2>
            </div>
        </div>
    </div>

    <div class="table-stats border-0 shadow-sm mb-4">
        <div class="p-4 bg-white border-bottom">
            <h5 class="fw-bold mb-0"><i class="fas fa-list-ul me-2 text-su"></i>รายละเอียดข้อมูลรายรอบ</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-muted">
                        <th class="ps-4 py-3">รอบเวลา</th>
                        <th>สถานะรอบ</th>
                        <th class="text-center">เข้าใช้งาน / จำกัด</th>
                        <th class="text-center">User ยกเลิก</th>
                        <th class="pe-4">บันทึกจากเจ้าของบูธ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($stats) > 0): ?>
                        <?php foreach ($stats as $s): 
                            $row_fill = ($s['max_slots'] > 0) ? ($s['total_booked'] / $s['max_slots']) * 100 : 0;
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold fs-5"><?php echo date('H:i', strtotime($s['start_time'])); ?> - <?php echo date('H:i', strtotime($s['end_time'])); ?></div>
                                <div class="small text-muted">รหัสรอบ: #ACT-<?php echo $s['id']; ?></div>
                            </td>
                            <td>
                                <?php if ($s['status'] == 'finished'): ?>
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3">เสร็จสิ้น</span>
                                <?php elseif ($s['status'] == 'cancelled'): ?>
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3">บูธยกเลิกรอบ</span>
                                <?php elseif ($s['status'] == 'calling'): ?>
                                    <span class="badge bg-warning-subtle text-warning rounded-pill px-3">กำลังเรียก</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted rounded-pill px-3">รอดำเนินการ</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center" style="min-width: 180px;">
                                <div class="fw-bold mb-1"><?php echo $s['total_booked']; ?> / <?php echo $s['max_slots']; ?></div>
                                <div class="progress mx-auto" style="width: 100px;">
                                    <div class="progress-bar <?php echo ($row_fill >= 100) ? 'bg-danger' : 'bg-success'; ?>" style="width: <?php echo $row_fill; ?>%"></div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-cancelled rounded-pill px-3">
                                    <i class="fas fa-user-slash me-1"></i> <?php echo $s['total_user_cancelled']; ?>
                                </span>
                            </td>
                            <td class="pe-4">
                                <div class="small text-muted" style="max-width: 250px;">
                                    <?php echo !empty($s['completion_note']) ? '“'.htmlspecialchars($s['completion_note']).'”' : '<span class="opacity-25">- ไม่มีบันทึก -</span>'; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">ยังไม่มีข้อมูลกิจกรรม</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="text-center no-print">
        <p class="text-muted small">ข้อมูลอัปเดตล่าสุดเมื่อ: <?php echo date('d/m/Y H:i'); ?> น.</p>
    </div>
</div>

</body>
</html>