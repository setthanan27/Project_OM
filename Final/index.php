<?php
session_start();
include 'config.php';

// ตรวจสอบสถานะการกรองจาก URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// กรณี Login แล้วให้ดึงข้อมูลมาเตรียมไว้
if (isset($_SESSION['owner_id'])) {
    $owner_id = $_SESSION['owner_id'];
    
    // 1. ดึงข้อมูลงานอีเวนท์ทั้งหมด
    $stmt_events = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
    $events = $stmt_events->fetchAll();

    // 2. ดึงข้อมูลการจองที่ "ยืนยันแล้ว"
    $sql_confirmed = "SELECT b.*, e.event_name, t.type_name 
                      FROM event_bookings b
                      LEFT JOIN events e ON b.event_id = e.id
                      LEFT JOIN booth_types t ON b.type_id = t.id
                      WHERE b.owner_id = ? AND b.booking_status = 'confirmed'
                      ORDER BY b.id DESC";
    $stmt_conf = $conn->prepare($sql_confirmed);
    $stmt_conf->execute([$owner_id]);
    $confirmed_bookings = $stmt_conf->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจองบูธแสดงสินค้า | EventQ+</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --su-dark: #2d6358; }
        body { background-color: #f8f9fa; font-family: 'Sarabun', sans-serif; }
        
        /* สไตล์สำหรับหน้ายังไม่ Login */
       .hero-section { background: var(--su-green); color: white; padding: 100px 0; border-bottom: 5px solid var(--su-dark); position: relative; }
        .auth-card { 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            transition: 0.3s; 
            background: white;
        }
        .auth-card:hover { transform: translateY(-10px); }

        /* สไตล์สำหรับหน้า Login แล้ว */
        .navbar { background: var(--su-green) !important; padding: 15px 0; }
        .nav-link.active { font-weight: bold; border-bottom: 2px solid white; }
        .event-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; background: white; }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .btn-su { background: var(--su-green); color: white; border-radius: 10px; border: none; font-weight: bold; }
        .btn-su:hover { background: var(--su-dark); color: white; }
        .status-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
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

    <div class="container" style="margin-top: -80px; position: relative; z-index: 10;">
        <div class="row justify-content-center g-4">
            <div class="col-md-5 col-lg-4">
                <div class="card auth-card p-4 text-center h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="text-primary mb-3"><i class="fas fa-user-lock fa-3x"></i></div>
                        <h4 class="fw-bold">เข้าสู่ระบบ</h4>
                        <p class="text-muted small">มีบัญชีที่ผ่านการอนุมัติแล้ว? เข้าสู่ระบบเพื่อเริ่มจองบูธได้เลย</p>
                        <div class="mt-auto">
                            <hr class="my-4 opacity-25">
                            <a href="user_login.php" class="btn btn-su w-100 py-3 shadow-sm">เข้าสู่ระบบ (Login)</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5 col-lg-4">
                <div class="card auth-card p-4 text-center h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="text-success mb-3"><i class="fas fa-id-card fa-3x"></i></div>
                        <h4 class="fw-bold">สมัครสมาชิกใหม่</h4>
                        <p class="text-muted small">ลงทะเบียนข้อมูลร้านค้าของคุณ เพื่อขอสิทธิ์เข้าใช้งานระบบจองบูธ</p>
                        <div class="mt-auto">
                            <hr class="my-4 opacity-25">
                            <a href="register.php" class="btn btn-outline-success border-2 w-100 py-3 fw-bold" style="border-radius: 10px;">สมัครสมาชิก (Register)</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-5 text-muted small pb-5">
            <p>© 2026 EventQ+ Management System. All rights reserved.</p>
        </div>
    </div>

<?php else: ?>
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
                    <li class="nav-item"><a class="nav-link <?php echo $filter == 'all' ? 'active' : ''; ?>" href="index.php">หน้าหลัก</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $filter == 'confirmed' ? 'active' : ''; ?>" href="index.php?filter=confirmed">บูธของฉัน</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_bookings.php">ประวัติการจอง</a></li>
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

    <div class="container mt-5 pb-5">
        <?php if ($filter == 'confirmed'): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <h3 class="fw-bold"><i class="fas fa-check-circle text-success me-2"></i>รายการจองที่ได้รับการยืนยัน</h3>
                    <p class="text-muted">กรุณาจัดการกิจกรรมเพื่อรับ QR Code สำหรับบูธของคุณ</p>
                </div>
            </div>

            <div class="status-table border">
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
                                <td class="ps-4"><div class="fw-bold"><?php echo htmlspecialchars($b['event_name']); ?></div></td>
                                <td><?php echo htmlspecialchars($b['type_name']); ?></td>
                                <td>
                                    <?php if($b['has_activity'] === NULL): ?>
                                        <span class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>ยังไม่เลือกแผน</span>
                                    <?php elseif($b['has_activity'] == 'yes'): ?>
                                        <span class="badge bg-primary px-3">จัดกิจกรรม</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-3">ไม่มีกิจกรรม</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($b['has_activity'] === NULL): ?>
                                        <div class="btn-group btn-group-sm">
                                            <a href="update_activity.php?id=<?php echo $b['id']; ?>&has=yes" class="btn btn-primary">มี</a>
                                            <a href="update_activity.php?id=<?php echo $b['id']; ?>&has=no" class="btn btn-outline-secondary">ไม่มี</a>
                                        </div>
                                    <?php elseif($b['has_activity'] == 'yes'): ?>
                                        <a href="manage_activity.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-dark px-3 rounded-pill">
                                            <i class="fas fa-qrcode me-1"></i> จัดการคิว
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">เสร็จสิ้น</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">ไม่พบรายการที่ยืนยันแล้ว</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <h3 class="fw-bold mb-4">งานอีเวนท์ที่เปิดให้จอง</h3>
            <div class="row g-4">
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
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>