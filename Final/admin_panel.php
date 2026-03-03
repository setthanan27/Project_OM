<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include 'config.php';
?>

<?php
include 'config.php';

// 1. ดึงข้อมูลอีเวนท์ทั้งหมด
$stmt = $conn->prepare("SELECT * FROM events ORDER BY id DESC");
$stmt->execute();
$events = $stmt->fetchAll();

// 2. ดึงสถิติต่างๆ มาโชว์ใน Dashboard
$total_events = count($events);

// คำนวณจำนวนบูธทั้งหมดที่มีในระบบ
$stmt_slots = $conn->query("SELECT SUM(total_slots) as total FROM booth_types");
$total_slots = $stmt_slots->fetch()['total'] ?? 0;
?>



<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | SU Web Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --su-dark: #2d6358; --sidebar-width: 260px; }
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; }
        
        /* Top Navigation */
        .top-nav { background: var(--su-green); color: white; height: 65px; display: flex; align-items: center; padding: 0 25px; position: fixed; width: 100%; z-index: 1050; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        /* Sidebar Navigation */
        .sidebar { width: var(--sidebar-width); background: white; height: 100vh; position: fixed; top: 65px; border-right: 1px solid #e0e0e0; padding-top: 15px; z-index: 1000; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { padding: 15px 25px; border-left: 5px solid transparent; transition: all 0.2s; color: #444; font-weight: 500; cursor: pointer; display: flex; align-items: center; }
        .sidebar-menu li i { width: 25px; font-size: 1.1rem; margin-right: 15px; color: #888; }
        .sidebar-menu li:hover { background: #f8fbf9; color: var(--su-green); }
        .sidebar-menu li.active { background: #edf5f3; border-left-color: var(--su-green); color: var(--su-green); }
        .sidebar-menu li.active i { color: var(--su-green); }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); padding: 95px 40px 40px; }
        
        /* Stats Cards */
        .stat-card { border: none; border-radius: 15px; padding: 20px; color: white; position: relative; overflow: hidden; }
        .stat-card.blue { background: linear-gradient(45deg, #4e73df, #224abe); }
        .stat-card.green { background: linear-gradient(45deg, #1cc88a, #13855c); }
        .stat-card i { position: absolute; right: 15px; bottom: 15px; font-size: 3rem; opacity: 0.2; }

        /* Event Cards */
        .event-card { border: none; border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; background: white; height: 100%; }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .card-category { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--su-green); font-weight: bold; }
        
        .btn-create { background: var(--su-green); color: white; border-radius: 8px; padding: 10px 20px; font-weight: bold; text-decoration: none; transition: 0.3s; }
        .btn-create:hover { background: var(--su-dark); color: white; box-shadow: 0 4px 12px rgba(58, 129, 115, 0.3); }
        
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }
        .status-active { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>

<div class="top-nav justify-content-between">
    <div class="d-flex align-items-center">
        <i class="fas fa-layer-group me-3 fs-4"></i>
        <h5 class="mb-0 fw-bold">Admin Panel XXX2X3X <span class="fw-light opacity-75 ms-2">Management System</span></h5>
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
        <li class="active"><i class="fas fa-chart-line"></i> Dashboard</li>
        <li onclick="location.href='admin_create_event.php'"><i class="fas fa-calendar-plus"></i> สร้างงานอีเวนท์</li>
        <li><i class="fas fa-clipboard-list"></i> รายการจองทั้งหมด</li>
        <li onclick="location.href='admin_users.php'"><i class="fas fa-users-cog"></i> จัดการสมาชิก</li>
        <li><i class="fas fa-chart-pie"></i> รายงานสถิติ</li>
        <hr class="mx-3 my-2">
        <li><i class="fas fa-cog"></i> ตั้งค่าระบบ</li>
    </ul>
</div>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-md-6">
            <h3 class="fw-bold">ภาพรวมระบบ (Overview)</h3>
            <p class="text-muted">ยินดีต้อนรับกลับมา, นี่คือสถานะล่าสุดของงานอีเวนท์ในระบบ</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="admin_create_event.php" class="btn btn-create shadow-sm">
                <i class="fas fa-plus me-2"></i>สร้างงานอีเวนท์ใหม่
            </a>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card blue shadow-sm">
                <div class="small opacity-75">งานอีเวนท์ทั้งหมด</div>
                <div class="h2 fw-bold mb-0"><?php echo number_format($total_events); ?></div>
                <div class="small mt-2">Active Events</div>
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card green shadow-sm">
                <div class="small opacity-75">จำนวนบูธรวมทุกงาน</div>
                <div class="h2 fw-bold mb-0"><?php echo number_format($total_slots); ?></div>
                <div class="small mt-2">Total Slots Available</div>
                <i class="fas fa-store"></i>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>รายการงานอีเวนท์ล่าสุด</h5>
    </div>

    <div class="row g-4">
        <?php if ($total_events > 0): ?>
            <?php foreach ($events as $event): ?>
                <div class="col-md-4 col-xl-3">
                    <div class="card event-card shadow-sm p-3">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="card-category">Event Management</span>
                            <span class="status-pill status-active">เปิดรับจอง</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($event['event_name']); ?></h5>
                        <div class="text-muted small mb-3">
                            <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($event['location']); ?></p>
                            <p class="mb-0"><i class="fas fa-calendar-day me-2"></i><?php echo date('d M Y', strtotime($event['event_date'])); ?></p>
                        </div>
                        <hr class="my-3">
                        <div class="d-grid">
                            <a href="booth_management.php?id=<?php echo $event['id']; ?>" class="btn btn-outline-primary border-2 fw-bold btn-sm">
                                <i class="fas fa-tasks me-2"></i>จัดการข้อมูลบูธ
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm">
                <img src="https://cdn-icons-png.flaticon.com/512/4076/4076402.png" alt="Empty" style="width: 80px; opacity: 0.5;">
                <h5 class="mt-3 text-muted">ยังไม่มีงานอีเวนท์ในระบบ</h5>
                <a href="admin_create_event.php" class="btn btn-link text-success">เริ่มสร้างงานแรกที่นี่</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>