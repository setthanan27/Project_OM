<?php
session_start();
if (!isset($_SESSION['admin_id'])) { 
    header("Location: login.php"); 
    exit; 
}
include 'config.php';

$filter_type = $_GET['type_id'] ?? null;

$sql = "SELECT b.*, e.event_name, t.type_name, t.price 
        FROM event_bookings b
        JOIN events e ON b.event_id = e.id
        JOIN booth_types t ON b.type_id = t.id";

if ($filter_type) {
    $sql .= " WHERE b.type_id = :type_id";
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
if ($filter_type) {
    $stmt->bindParam(':type_id', $filter_type, PDO::PARAM_INT);
}
$stmt->execute();
$bookings = $stmt->fetchAll();

$page_title = "รายการจองบูธทั้งหมด";
if ($filter_type && count($bookings) > 0) {
    $page_title = "รายการจอง: " . htmlspecialchars($bookings[0]['type_name']);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรายการจอง | EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --su-dark: #2d6358; --sidebar-width: 260px; }
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; }
        
        /* Sidebar & Nav Styles - ปรับปรุงให้เหมือนหน้าอื่นๆ */
        .top-nav { background: var(--su-green); color: white; height: 65px; display: flex; align-items: center; padding: 0 25px; position: fixed; width: 100%; z-index: 1050; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .sidebar { width: var(--sidebar-width); background: white; height: 100vh; position: fixed; top: 65px; border-right: 1px solid #e0e0e0; padding-top: 15px; z-index: 1000; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { padding: 15px 25px; border-left: 5px solid transparent; transition: all 0.2s; color: #444; font-weight: 500; cursor: pointer; display: flex; align-items: center; }
        .sidebar-menu li i { width: 25px; margin-right: 15px; color: #888; }
        .sidebar-menu li:hover { background: #f8fbf9; color: var(--su-green); }
        .sidebar-menu li.active { background: #edf5f3; border-left-color: var(--su-green); color: var(--su-green); }

        .main-content { margin-left: var(--sidebar-width); padding: 95px 40px 40px; }
        .data-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; background: white; }
        .slip-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 1px solid #eee; transition: 0.2s; }
        .slip-thumb:hover { transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .badge { font-weight: 500; padding: 0.5em 1em; }
    </style>
</head>
<body>

<div class="top-nav justify-content-between">
    <div class="d-flex align-items-center">
        <i class="fas fa-layer-group me-3 fs-4"></i>
        <h5 class="mb-0 fw-bold">Admin Panel <span class="fw-light opacity-75 ms-2 text-white">All reservations</span></h5>
    </div>
    <div class="d-flex align-items-center">
        <div class="text-end me-3 d-none d-md-block">
            <small class="d-block opacity-75">ผู้ดูแลระบบ</small>
            <span class="fw-bold"><?php echo $_SESSION['admin_name']; ?></span>
        </div>
        
        <div class="rounded-circle bg-white text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <?php echo mb_substr($_SESSION['admin_name'], 0, 1, 'UTF-8'); ?>
        </div>

        <a href="logout.php" class="btn btn-link text-white ms-3 p-0" title="ออกจากระบบ" onclick="return confirm('คุณต้องการออกจากระบบใช่หรือไม่?')">
            <i class="fas fa-sign-out-alt fs-5"></i>
        </a>
    </div>
</div>

<div class="sidebar">
    <ul class="sidebar-menu">
        <li onclick="location.href='admin_panel.php'"><i class="fas fa-chart-line"></i> Dashboard</li>
        <li onclick="location.href='admin_create_event.php'"><i class="fas fa-calendar-plus"></i> สร้างงานอีเวนท์</li>
        <li class="active"><i class="fas fa-clipboard-list"></i> รายการจองทั้งหมด</li>
        <li onclick="location.href='admin_users.php'"><i class="fas fa-users-cog"></i> จัดการสมาชิก</li>
        <li onclick="location.href='admin_stats.php'"><i class="fas fa-chart-pie"></i> รายงานสถิติ</li>
    </ul>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><?php echo $page_title; ?></h3>
            <p class="text-muted small mb-0">จัดการตรวจสอบหลักฐานการโอนเงินและยืนยันสิทธิ์บูธ</p>
        </div>
        <?php if ($filter_type): ?>
            <a href="admin_bookings.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                <i class="fas fa-sync-alt me-1"></i> แสดงทั้งหมด
            </a>
        <?php endif; ?>
    </div>
    
    <div class="card data-card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3 border-0">วัน-เวลาที่จอง</th>
                        <th class="border-0">งานอีเวนท์</th>
                        <th class="border-0">ข้อมูลร้านค้า / บูธ</th>
                        <th class="text-center border-0">หลักฐานการโอน</th>
                        <th class="border-0">สถานะ</th>
                        <th class="text-end pe-4 border-0">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td class="ps-4 small text-muted">
                                <div class="text-dark fw-bold"><?php echo date('d/m/Y', strtotime($b['created_at'])); ?></div>
                                <span class="opacity-75"><?php echo date('H:i', strtotime($b['created_at'])); ?> น.</span>
                            </td>
                            <td><span class="fw-bold text-dark"><?php echo htmlspecialchars($b['event_name']); ?></span></td>
                            <td>
                                <div class="fw-bold text-su-green" style="color: var(--su-green);"><?php echo htmlspecialchars($b['customer_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($b['type_name']); ?> (฿<?php echo number_format($b['price']); ?>)</small>
                            </td>
                            <td class="text-center">
                                <?php if ($b['payment_slip']): ?>
                                    <img src="uploads/slips/<?php echo $b['payment_slip']; ?>" class="slip-thumb" onclick="window.open(this.src)" title="คลิกเพื่อดูรูปขยาย">
                                <?php else: ?>
                                    <span class="text-muted small"><em>ไม่มีสลิป</em></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($b['booking_status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark rounded-pill">รอตรวจสอบ</span>
                                <?php elseif($b['booking_status'] == 'confirmed'): ?>
                                    <span class="badge bg-success rounded-pill text-white">ยืนยันแล้ว</span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-pill text-white">ยกเลิก</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if($b['booking_status'] == 'pending'): ?>
                                    <a href="update_booking.php?id=<?php echo $b['id']; ?>&status=confirmed" 
                                       class="btn btn-sm btn-success px-3 shadow-sm rounded-pill" 
                                       onclick="return confirm('ยืนยันการชำระเงินและสิทธิ์บูธนี้?')">อนุมัติ</a>
                                    
                                    <a href="update_booking.php?id=<?php echo $b['id']; ?>&status=cancelled" 
                                       class="btn btn-sm btn-outline-danger px-3 ms-1 rounded-pill" 
                                       onclick="return confirm('ต้องการยกเลิกการจองนี้ใช่หรือไม่?')">ปฏิเสธ</a>
                                <?php else: ?>
                                    <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i> เรียบร้อย</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i><br>
                                ยังไม่มีรายการจองบูธในขณะนี้
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>