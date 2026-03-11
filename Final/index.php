<?php
session_start();
include 'config.php';

// ตรวจสอบการ Login
if (!isset($_SESSION['owner_id'])) {
    header("Location: user_login.php");
    exit;
}

$owner_id = $_SESSION['owner_id'];
// ตรวจสอบสถานะการกรองจาก URL (all = หน้าหลักเลือกงาน, confirmed = หน้าจัดการกิจกรรม)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// 1. ดึงข้อมูลงานอีเวนท์ทั้งหมด (สำหรับหน้าหลัก)
$stmt_events = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
$events = $stmt_events->fetchAll();

// 2. ดึงข้อมูลการจองที่ "ยืนยันแล้ว" เพื่อใช้แสดงในเมนูและหน้าจัดการกิจกรรม
$sql_confirmed = "SELECT b.*, e.event_name, t.type_name 
                  FROM event_bookings b
                  LEFT JOIN events e ON b.event_id = e.id
                  LEFT JOIN booth_types t ON b.type_id = t.id
                  WHERE b.owner_id = ? AND b.booking_status = 'confirmed'
                  ORDER BY b.id DESC";
$stmt_conf = $conn->prepare($sql_confirmed);
$stmt_conf->execute([$owner_id]);
$confirmed_bookings = $stmt_conf->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจองบูธแสดงสินค้า | EventQ+</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --su-dark: #2d6358; }
        body { background-color: #f8f9fa; font-family: 'Sarabun', sans-serif; }
        .navbar { background: var(--su-green) !important; padding: 15px 0; }
        .nav-link { font-weight: 500; margin: 0 10px; transition: 0.3s; }
        .nav-link.active { font-weight: bold; border-bottom: 2px solid white; }
        .event-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .btn-su { background: var(--su-green); color: white; border-radius: 10px; border: none; font-weight: bold; }
        .btn-su:hover { background: var(--su-dark); color: white; }
        .status-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="fas fa-layer-group me-2"></i>Booth Booking System
         </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="userNavbar">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter == 'all' ? 'active' : ''; ?>" href="index.php">หน้าหลัก</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter == 'confirmed' ? 'active' : ''; ?>" href="index.php?filter=confirmed">ยืนยันแล้ว</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_bookings.php">ประวัติการจอง</a>
                </li>
                <li class="nav-item ms-lg-4 border-start ps-lg-4 text-white">
                    <div class="d-flex align-items-center">
                        <div class="text-end me-3">
                            <small class="d-block opacity-75">ร้านค้า</small>
                            <span class="fw-bold"><?php echo $_SESSION['shop_name']; ?></span>
                        </div>
                        <a href="logout_user.php" class="btn btn-sm btn-outline-light rounded-pill" onclick="return confirm('ยืนยันการออกจากระบบ?')">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <?php if ($filter == 'confirmed'): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <h3 class="fw-bold"><i class="fas fa-check-circle text-success me-2"></i>รายการจองที่ได้รับการยืนยัน</h3>
                <p class="text-muted">กรุณาแจ้งความประสงค์การจัดกิจกรรมเพื่อรับ QR Code สำหรับบูธของคุณ</p>
            </div>
        </div>

        <div class="status-table">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3">งานอีเวนท์</th>
                        <th>ประเภทบูธ</th>
                        <th>สถานะกิจกรรม</th>
                        <th class="text-center">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($confirmed_bookings) > 0): ?>
                        <?php foreach ($confirmed_bookings as $b): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo htmlspecialchars($b['event_name']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($b['type_name']); ?></td>
                            <td>
                                <?php if($b['has_activity'] === NULL): ?>
                                    <span class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>ยังไม่ได้เลือกแผนกิจกรรม</span>
                                <?php elseif($b['has_activity'] == 'yes'): ?>
                                    <span class="badge bg-primary px-3">จัดกิจกรรม</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary px-3">ไม่มีกิจกรรม</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($b['has_activity'] === NULL): ?>
                                    <div class="btn-group btn-group-sm shadow-sm">
                                        <a href="update_activity.php?id=<?php echo $b['id']; ?>&has=yes" class="btn btn-primary px-3">มีกิจกรรม</a>
                                        <a href="update_activity.php?id=<?php echo $b['id']; ?>&has=no" class="btn btn-outline-secondary px-3">ไม่มี</a>
                                    </div>
                                <?php elseif($b['has_activity'] == 'yes'): ?>
                                    <a href="manage_activity.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-dark px-3 rounded-pill">
                                        <i class="fas fa-qrcode me-1"></i> จัดการรอบ & QR
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">ยืนยันเสร็จสิ้น</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">ไม่พบรายการที่ได้รับการยืนยันในขณะนี้</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-center">
            <a href="index.php" class="btn btn-link text-decoration-none text-muted">
                <i class="fas fa-arrow-left me-2"></i>กลับสู่หน้าเลือกงานอีเวนท์
            </a>
        </div>

    <?php else: ?>
        <div class="row mb-4 align-items-end">
            <div class="col-md-7">
                <h3 class="fw-bold mb-1">งานอีเวนท์ที่เปิดให้จอง</h3>
                <p class="text-muted mb-0">เลือกงานที่คุณต้องการเพื่อตรวจสอบพื้นที่ว่างและทำรายการจอง</p>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <span class="badge bg-white text-dark border p-2 px-3 rounded-pill shadow-sm">
                    <i class="fas fa-info-circle text-primary me-2"></i>อัปเดตข้อมูลแบบเรียลไทม์
                </span>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <?php if (count($events) > 0): ?>
                <?php foreach ($events as $event): ?>
                <div class="col-md-4">
                    <div class="card event-card h-100 border-0">
                        <div class="card-body p-4 text-center">
                            <div class="mb-3">
                                <div class="bg-light d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px;">
                                    <i class="fas fa-calendar-alt fa-2x text-success"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($event['event_name']); ?></h4>
                            <div class="mb-4">
                                <p class="mb-1 text-muted"><i class="fas fa-clock me-2 text-primary"></i><?php echo date('d M Y', strtotime($event['event_date'])); ?></p>
                                <p class="small text-muted mb-0"><i class="fas fa-map-marker-alt text-danger me-2"></i><?php echo htmlspecialchars($event['location']); ?></p>
                            </div>
                            <hr class="opacity-25 mb-4">
                            <div class="d-grid">
                                <a href="booking.php?event_id=<?php echo $event['id']; ?>" class="btn btn-su py-2">
                                    <i class="fas fa-search-plus me-2"></i>ดูบูธว่างและจองพื้นที่
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="bg-white p-5 rounded-4 shadow-sm">
                        <i class="fas fa-calendar-times fa-4x text-muted opacity-25 mb-3"></i>
                        <h5 class="text-muted">ขณะนี้ยังไม่มีงานอีเวนท์เปิดรับจอง</h5>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>