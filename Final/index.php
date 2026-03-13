<?php
session_start();
include 'config.php';

// ตรวจสอบสถานะการกรองจาก URL (หน้าหลัก หรือ บูธของฉัน)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// กรณี Login แล้วให้ดึงข้อมูลมาเตรียมไว้
if (isset($_SESSION['owner_id'])) {
    $owner_id = $_SESSION['owner_id'];
    
    // 1. ดึงข้อมูลงานอีเวนท์ทั้งหมด (รวมฟิลด์รายละเอียดและเบอร์โทร)
    // ตรวจสอบให้แน่ใจว่าใช้ SELECT * เพื่อดึงทุกคอลัมน์มาใช้งาน
    $stmt_events = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
    $events = $stmt_events->fetchAll();

    // 2. ดึงข้อมูลการจองที่ "ยืนยันแล้ว" สำหรับเมนู 'บูธของฉัน'
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
        .navbar { background: var(--su-green) !important; padding: 15px 0; }
        .nav-link.active { font-weight: bold; border-bottom: 2px solid white; }
        .event-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; background: white; }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .btn-su { background: var(--su-green); color: white; border-radius: 10px; border: none; font-weight: bold; }
        .btn-su:hover { background: var(--su-dark); color: white; }
        .btn-info-event { border-radius: 10px; font-weight: bold; font-size: 0.85rem; border: 2px solid #eee; background: white; }
        .status-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<?php if (isset($_SESSION['owner_id'])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-layer-group me-2"></i>Booth Booking System
            </a>
            <div class="collapse navbar-collapse" id="userNavbar">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link <?php echo $filter == 'all' ? 'active' : ''; ?>" href="index.php">หน้าหลัก</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $filter == 'confirmed' ? 'active' : ''; ?>" href="index.php?filter=confirmed">บูธของฉัน</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_bookings.php">ประวัติการจอง</a></li>
                    <li class="nav-item ms-lg-4 border-start ps-lg-4 text-white">
                        <div class="d-flex align-items-center">
                            <span class="fw-bold me-3 small"><?php echo htmlspecialchars($_SESSION['shop_name']); ?></span>
                            <a href="logout_user.php" class="btn btn-sm btn-outline-light rounded-pill"><i class="fas fa-sign-out-alt"></i></a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pb-5">
        <?php if ($filter == 'confirmed'): ?>
            <h3 class="fw-bold mb-4">รายการจองที่ได้รับการยืนยัน</h3>
            <div class="status-table border">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr><th class="ps-4">งานอีเวนท์</th><th>ประเภทบูธ</th><th>สถานะกิจกรรม</th><th class="text-center">การจัดการ</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($confirmed_bookings as $b): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo htmlspecialchars($b['event_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['type_name']); ?></td>
                            <td><?php echo $b['has_activity'] == 'yes' ? '<span class="badge bg-primary">จัดกิจกรรม</span>' : '<span class="badge bg-secondary">ไม่มีกิจกรรม</span>'; ?></td>
                            <td class="text-center">
                                <?php if($b['has_activity'] == 'yes'): ?>
                                    <a href="manage_activity.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-dark px-3 rounded-pill"><i class="fas fa-qrcode me-1"></i> จัดการคิว</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <h3 class="fw-bold mb-4">งานอีเวนท์ที่เปิดให้จอง</h3>
            <div class="row g-4">
                <?php foreach ($events as $event): ?>
                <div class="col-md-4">
                    <div class="card event-card h-100 border-0">
                        <div class="card-body p-4 text-center d-flex flex-column">
                            <div class="mb-3">
                                <div class="bg-light d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px;">
                                    <i class="fas fa-calendar-alt fa-2x text-success"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($event['event_name']); ?></h4>
                            <div class="mb-4 small text-muted text-start">
                                <p class="mb-1"><i class="fas fa-clock me-2 text-primary"></i><?php echo date('d M Y', strtotime($event['event_date'])); ?></p>
                                <p class="mb-0"><i class="fas fa-map-marker-alt text-danger me-2"></i><?php echo htmlspecialchars($event['location']); ?></p>
                            </div>
                            <div class="row g-2 mt-auto">
                                <div class="col-12">
                                    <button class="btn btn-info-event w-100 py-2" data-bs-toggle="modal" data-bs-target="#eventModal<?php echo $event['id']; ?>">
                                        <i class="fas fa-info-circle me-1"></i> รายละเอียดงาน
                                    </button>
                                </div>
                                <div class="col-12">
                                    <a href="booking.php?event_id=<?php echo $event['id']; ?>" class="btn btn-su w-100 py-2">จองพื้นที่บูธ</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="eventModal<?php echo $event['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow" style="border-radius: 25px;">
                            <div class="modal-header border-0 pb-0">
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 pt-0">
                                <div class="text-center mb-4">
                                    <div class="bg-success-subtle p-3 rounded-circle d-inline-block mb-3">
                                        <i class="fas fa-calendar-check fa-3x text-success"></i>
                                    </div>
                                    <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($event['event_name']); ?></h4>
                                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                                        วันที่ <?php echo date('d/m/Y', strtotime($event['event_date'])); ?>
                                    </span>
                                </div>

                                <div class="mb-4">
                                    <label class="small fw-bold text-muted text-uppercase mb-2"><i class="fas fa-align-left me-1"></i> รายละเอียดงาน</label>
                                    <p class="mb-0 text-dark"><?php echo nl2br(htmlspecialchars($event['event_detail'] ?? 'ไม่มีรายละเอียด')); ?></p>
                                </div>

                                <div class="row g-2 mb-4">
                                    <div class="col-6">
                                        <div class="p-2 border rounded-3 bg-light h-100">
                                            <label class="small fw-bold text-muted d-block">คำแนะนำ</label>
                                            <span class="small"><?php echo htmlspecialchars($event['instructions'] ?? '-'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 border rounded-3 bg-light h-100">
                                            <label class="small fw-bold text-muted d-block">สิ่งอำนวยความสะดวก</label>
                                            <span class="small"><?php echo htmlspecialchars($event['facilities'] ?? '-'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-light p-3 rounded-4">
                                    <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                                        <i class="fas fa-phone-alt text-success me-3 fs-5"></i>
                                        <div>
                                            <label class="small fw-bold text-muted d-block">เบอร์โทรติดต่อ</label>
                                            <a href="tel:<?php echo $event['contact_phone']; ?>" class="fw-bold text-dark text-decoration-none">
                                                <?php echo htmlspecialchars($event['contact_phone'] ?? 'ไม่ระบุ'); ?>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-map-marker-alt text-danger me-3 fs-5"></i>
                                        <div>
                                            <label class="small fw-bold text-muted d-block">สถานที่</label>
                                            <span class="fw-bold"><?php echo htmlspecialchars($event['location']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 p-4 pt-0">
                                <a href="booking.php?event_id=<?php echo $event['id']; ?>" class="btn btn-su w-100 py-3 shadow">จองพื้นที่งานนี้เลย</a>
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