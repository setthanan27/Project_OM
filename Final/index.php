<?php
session_start();
include 'config.php';

// กรณีที่ Login แล้ว: ดึงข้อมูลงานอีเวนท์ทั้งหมดมาแสดง
$events = [];
if (isset($_SESSION['owner_id'])) {
    $stmt = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
    $events = $stmt->fetchAll();
}
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
        
        /* Hero Section Styling */
        .hero-section { background: var(--su-green); color: white; padding: 100px 0; border-bottom: 5px solid var(--su-dark); position: relative; }
        .hero-section::after { content: ""; position: absolute; bottom: 0; left: 0; right: 0; height: 50px; background: #f8f9fa; clip-path: polygon(0 100%, 100% 100%, 100% 0); }
        
        /* Card Styling */
        .auth-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: 0.3s; height: 100%; }
        .auth-card:hover { transform: translateY(-10px); }
        .event-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .event-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        
        /* Button Styling */
        .btn-su { background: var(--su-green); color: white; border-radius: 10px; padding: 12px 25px; border: none; font-weight: bold; transition: 0.3s; }
        .btn-su:hover { background: var(--su-dark); color: white; transform: scale(1.02); }
        
        .navbar { background: var(--su-green) !important; padding: 15px 0; }
        .nav-link { font-weight: 500; margin: 0 10px; }
        .nav-link.active { font-weight: bold; border-bottom: 2px solid white; }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['owner_id'])): ?>
    <div class="hero-section text-center">
        <div class="container">
            <div class="mb-4">
                <i class="fas fa-store-alt fa-5x"></i>
            </div>
            <h1 class="fw-bold display-5">ระบบจองบูธสำหรับร้านค้า</h1>
            <p class="lead opacity-75">EventQ+ - ศูนย์รวมงานอีเวนท์และพื้นที่จัดแสดงสินค้า</p>
        </div>
    </div>

    <div class="container" style="margin-top: -60px; position: relative; z-index: 10;">
        <div class="row justify-content-center g-4">
            <div class="col-md-5">
                <div class="card auth-card p-4 text-center">
                    <div class="card-body">
                        <div class="text-primary mb-3"><i class="fas fa-user-lock fa-3x"></i></div>
                        <h4 class="fw-bold">เข้าสู่ระบบ</h4>
                        <p class="text-muted">มีบัญชีที่ผ่านการอนุมัติแล้ว? เข้าสู่ระบบเพื่อเริ่มจองบูธได้เลย</p>
                        <hr class="my-4 opacity-25">
                        <a href="user_login.php" class="btn btn-su w-100 py-3 shadow-sm">เข้าสู่ระบบ (Login)</a>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card auth-card p-4 text-center">
                    <div class="card-body">
                        <div class="text-success mb-3"><i class="fas fa-id-card fa-3x"></i></div>
                        <h4 class="fw-bold">สมัครสมาชิกใหม่</h4>
                        <p class="text-muted">ลงทะเบียนข้อมูลร้านค้าของคุณ เพื่อขอสิทธิ์เข้าใช้งานระบบจองบูธ</p>
                        <hr class="my-4 opacity-25">
                        <a href="register.php" class="btn btn-outline-success border-2 w-100 py-3 fw-bold" style="border-radius: 10px;">สมัครสมาชิก (Register)</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-5 text-muted small">
            <p>© 2026 EventQ+ Management System. All rights reserved.</p>
        </div>
    </div>

<?php else: ?>
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
                        <a class="nav-link active" href="index.php">หน้าหลัก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_bookings.php">ประวัติการจอง</a>
                    </li>
                    <li class="nav-item ms-lg-4 border-start ps-lg-4">
                        <div class="text-white d-flex align-items-center">
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
                        <p class="small text-muted mb-0">กรุณากลับมาตรวจสอบใหม่อีกครั้งภายหลัง</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>