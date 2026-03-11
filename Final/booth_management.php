<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include 'config.php';

$event_id = $_GET['id'] ?? null;
if (!$event_id) { header("Location: admin_panel.php"); exit; }

// ดึงข้อมูลอีเวนท์
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

// ดึงประเภทบูธในอีเวนท์นี้
$stmt_types = $conn->prepare("SELECT * FROM booth_types WHERE event_id = ?");
$stmt_types->execute([$event_id]);
$booth_types = $stmt_types->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบูธ | EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --sidebar-width: 260px; }
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; }
        .top-nav { background: var(--su-green); color: white; height: 65px; display: flex; align-items: center; padding: 0 25px; position: fixed; width: 100%; z-index: 1050; }
        .sidebar { width: var(--sidebar-width); background: white; height: 100vh; position: fixed; top: 65px; border-right: 1px solid #e0e0e0; padding-top: 15px; }
        .sidebar-menu { list-style: none; padding: 0; }
        .sidebar-menu li { padding: 15px 25px; color: #444; cursor: pointer; display: flex; align-items: center; transition: 0.3s; }
        .sidebar-menu li:hover { background: #f8fbf9; color: var(--su-green); }
        .sidebar-menu li.active { background: #edf5f3; border-left: 5px solid var(--su-green); color: var(--su-green); }
        .sidebar-menu li i { width: 25px; margin-right: 15px; }
        .main-content { margin-left: var(--sidebar-width); padding: 95px 40px 40px; }
        .data-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; }
        .event-info-header { background: white; border-radius: 15px; padding: 25px; margin-bottom: 25px; border-left: 8px solid var(--su-green); box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .progress { background-color: #e9ecef; }
    </style>
</head>
<body>

<div class="top-nav justify-content-between">
    <div class="d-flex align-items-center">
        <i class="fas fa-layer-group me-3 fs-4"></i>
        <h5 class="mb-0 fw-bold">Admin Panel <span class="fw-light opacity-75 ms-2">Booth Management</span></h5>
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
        <li class="active" onclick="location.href='admin_bookings.php'"><i class="fas fa-tasks me-2"></i> จัดการข้อมูลบูธ</li>
    </ul>
</div>

<div class="main-content">
    <?php if ($event): ?>
    <div class="event-info-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($event['event_name']); ?></h3>
            <p class="mb-0 text-muted">
                <i class="fas fa-map-marker-alt me-2 text-danger"></i><?php echo htmlspecialchars($event['location']); ?> 
                <span class="mx-2 text-secondary">|</span> 
                <i class="fas fa-calendar-alt me-2 text-primary"></i><?php echo date('d/m/Y', strtotime($event['event_date'])); ?>
            </p>
        </div>
        <div>
            <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-outline-secondary btn-sm me-2">แก้ไขข้อมูลงาน</a>
            <a href="delete_event.php?id=<?php echo $event['id']; ?>" 
               class="btn btn-danger btn-sm px-3" 
               onclick="return confirm('คำเตือน! การลบงานอีเวนท์จะทำให้ข้อมูลประเภทบูธและรายการจองทั้งหมดถูกลบไปด้วย คุณยืนยันที่จะลบหรือไม่?')">
               <i class="fas fa-trash-alt me-1"></i> ลบอีเวนท์
            </a>
        </div>
    </div>

    <div class="card data-card">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3">ประเภทบูธ</th>
                        <th>ราคา</th>
                        <th>จำนวนทั้งหมด</th>
                        <th>สถานะการจอง</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($booth_types as $type): 
                        $stmt_booked = $conn->prepare("SELECT COUNT(*) as current FROM event_bookings WHERE type_id = ? AND booking_status != 'cancelled'");
                        $stmt_booked->execute([$type['id']]);
                        $current_booked = $stmt_booked->fetch()['current'];
                        
                        $percent = ($type['total_slots'] > 0) ? ($current_booked / $type['total_slots']) * 100 : 0;
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($type['type_name']); ?></td>
                        <td><span class="text-success fw-bold"><?php echo number_format($type['price']); ?> ฿</span></td>
                        <td><?php echo $type['total_slots']; ?> บูธ</td>
                        <td>
                            <div class="progress" style="height: 10px; width: 150px; border-radius: 10px; margin-bottom: 4px;">
                                <div class="progress-bar <?php echo ($percent >= 100) ? 'bg-danger' : 'bg-primary'; ?>" 
                                     role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                            <small class="text-muted">จองแล้ว <?php echo $current_booked; ?> / <?php echo $type['total_slots']; ?></small>
                        </td>
                        <td class="text-center">
                            <a href="admin_bookings.php?type_id=<?php echo $type['id']; ?>" 
                               class="btn btn-sm btn-white border shadow-sm" title="จัดการรายการจอง">
                                <i class="fas fa-cog text-secondary"></i>
                            </a>
                            <a href="edit_booth.php?id=<?php echo $type['id']; ?>" class="btn btn-sm btn-white border shadow-sm ms-1">
                                <i class="fas fa-edit text-primary"></i>
                            </a>
                            <a href="delete_booth.php?id=<?php echo $type['id']; ?>" 
                               class="btn btn-sm btn-white border shadow-sm ms-1" 
                               onclick="return confirm('คุณแน่ใจว่าต้องการลบประเภทบูธนี้? การลบจะทำให้รายการจองที่เกี่ยวข้องถูกยกเลิกทั้งหมด')">
                               <i class="fas fa-trash-alt text-danger"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-warning">ไม่พบข้อมูลอีเวนท์</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>