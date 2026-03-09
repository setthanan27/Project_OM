<?php
session_start();
include 'config.php';

// ตรวจสอบการ Login
if (!isset($_SESSION['owner_id'])) {
    header("Location: user_login.php");
    exit;
}

$owner_id = $_SESSION['owner_id'];

// ดึงข้อมูลประวัติการจองทั้งหมดของร้านค้านี้
$sql = "SELECT b.*, e.event_name, e.event_date, t.type_name, t.price 
        FROM event_bookings b
        LEFT JOIN events e ON b.event_id = e.id
        LEFT JOIN booth_types t ON b.type_id = t.id
        WHERE b.owner_id = ?
        ORDER BY b.id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$owner_id]);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการจองของฉัน | EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --su-dark: #2d6358; }
        body { background-color: #f4f7f6; font-family: 'Sarabun', sans-serif; }
        
        /* Navbar Style ให้เหมือนหน้า Index */
        .navbar { background: var(--su-green); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        .main-container { padding: 40px 0; }
        .page-title { border-left: 5px solid var(--su-green); padding-left: 15px; margin-bottom: 30px; }
        
        /* Booking Card Style */
        .booking-card { border: none; border-radius: 15px; transition: 0.3s; background: white; }
        .booking-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        
        .status-badge { padding: 6px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
        .status-pending { background: #fff3e0; color: #ef6c00; }
        .status-confirmed { background: #e8f5e9; color: #2e7d32; }
        .status-cancelled { background: #ffebee; color: #c62828; }
        
        .btn-print { border-radius: 10px; font-weight: bold; transition: 0.3s; }
        .btn-print:hover { background: var(--su-green); color: white; border-color: var(--su-green); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="fas fa-layer-group me-2"></i>SU Web Portal
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="userNavbar">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">หน้าหลัก</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?filter=confirmed">ยืนยันแล้ว</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="my_bookings.php">ประวัติการจอง</a>
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

<div class="container main-container">
    <div class="page-title">
        <h2 class="fw-bold mb-0">ประวัติการจองของฉัน</h2>
        <p class="text-muted">ตรวจสอบสถานะการจองและพิมพ์ใบยืนยันเพื่อยื่นที่หน้างาน</p>
    </div>

    <div class="row g-4">
        <?php if (count($bookings) > 0): ?>
            <?php foreach ($bookings as $b): ?>
            <div class="col-12">
                <div class="card booking-card shadow-sm p-3 p-md-4">
                    <div class="row align-items-center">
                        <div class="col-md-5 mb-3 mb-md-0">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary mb-0 me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-calendar-check fa-lg"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($b['event_name']); ?></h5>
                                    <p class="mb-0 text-muted small">
                                        
                                        <i class="fas fa-clock me-1"></i> <?php echo date('d/m/Y', strtotime($b['event_date'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3 mb-md-0 border-start-md ps-md-4">
                            <div class="small text-muted">ประเภทบูธ:</div>
                            <div class="fw-bold"><?php echo htmlspecialchars($b['type_name']); ?></div>
                            <div class="text-success fw-bold"><?php echo number_format($b['price'], 2); ?> ฿</div>
                        </div>
                        
                        <div class="col-md-2 text-md-center mb-3 mb-md-0">
                            <?php if($b['booking_status'] == 'pending'): ?>
                                <span class="status-badge status-pending"><i class="fas fa-hourglass-half me-1"></i> รอตรวจสอบ</span>
                            <?php elseif($b['booking_status'] == 'confirmed'): ?>
                                <span class="status-badge status-confirmed"><i class="fas fa-check-circle me-1"></i> ยืนยันแล้ว</span>
                            <?php else: ?>
                                <span class="status-badge status-cancelled"><i class="fas fa-times-circle me-1"></i> ถูกยกเลิก</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-2 text-md-end">
                            <a href="booking_receipt.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-secondary btn-print w-100 w-md-auto py-2">
                                <i class="fas fa-print me-2"></i> พิมพ์ใบยืนยัน
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-folder-open fa-4x text-muted opacity-25"></i>
                </div>
                <h4 class="text-muted">ยังไม่มีประวัติการจอง</h4>
                <p class="text-muted mb-4">คุณยังไม่ได้จองบูธในงานอีเวนท์ใดๆ</p>
                <a href="index.php" class="btn btn-success px-4" style="background: var(--su-green); border-radius: 10px;">ไปหน้าเลือกงานอีเวนท์</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>