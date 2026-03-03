<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include 'config.php';

$event_id = $_GET['id'] ?? null;
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

$stmt_types = $conn->prepare("SELECT * FROM booth_types WHERE event_id = ?");
$stmt_types->execute([$event_id]);
$booth_types = $stmt_types->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบูธ | SU Web Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --sidebar-width: 260px; }
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; }
        .top-nav { background: var(--su-green); color: white; height: 65px; display: flex; align-items: center; padding: 0 25px; position: fixed; width: 100%; z-index: 1050; }
        .sidebar { width: var(--sidebar-width); background: white; height: 100vh; position: fixed; top: 65px; border-right: 1px solid #e0e0e0; padding-top: 15px; }
        .sidebar-menu { list-style: none; padding: 0; }
        .sidebar-menu li { padding: 15px 25px; color: #444; cursor: pointer; display: flex; align-items: center; }
        .sidebar-menu li.active { background: #edf5f3; border-left: 5px solid var(--su-green); color: var(--su-green); }
        .sidebar-menu li i { width: 25px; margin-right: 15px; }
        .main-content { margin-left: var(--sidebar-width); padding: 95px 40px 40px; }
        .data-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; }
        .table thead { background: #f8fbf9; }
        .event-info-header { background: white; border-radius: 15px; padding: 25px; margin-bottom: 25px; border-left: 8px solid var(--su-green); box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
    </style>
</head>
<body>

<div class="top-nav justify-content-between">
    <div class="d-flex align-items-center">
        <i class="fas fa-layer-group me-3 fs-4"></i>
        <h5 class="mb-0 fw-bold">Admin Panel <span class="fw-light opacity-75 ms-2">Admin</span></h5>
    </div>
    <div class="d-flex align-items-center">
        <span class="fw-bold me-3"><?php echo $_SESSION['admin_name']; ?></span>
        <a href="logout.php" class="text-white"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

<div class="sidebar">
    <ul class="sidebar-menu">
        <li onclick="location.href='admin_panel.php'"><i class="fas fa-chart-line"></i> Dashboard</li>
        <li><i class="fas fa-calendar-plus"></i> สร้างงานอีเวนท์</li>
        <li class="active"><i class="fas fa-clipboard-list"></i> รายการจองทั้งหมด</li>
    </ul>
</div>

<div class="main-content">
    <div class="event-info-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($event['event_name']); ?></h3>
            <p class="mb-0 text-muted">
                <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($event['location']); ?> 
                <span class="mx-2">|</span> 
                <i class="fas fa-calendar-alt me-2"></i><?php echo date('d/m/Y', strtotime($event['event_date'])); ?>
            </p>
        </div>
        <button class="btn btn-outline-danger btn-sm" onclick="return confirm('ลบงานนี้?')">ลบอีเวนท์</button>
    </div>

    <div class="card data-card">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
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
                        // ดึงจำนวนที่จองแล้วของประเภทนี้
                        $stmt_booked = $conn->prepare("SELECT COUNT(*) as current FROM event_bookings WHERE type_id = ? AND booking_status != 'cancelled'");
                        $stmt_booked->execute([$type['id']]);
                        $current_booked = $stmt_booked->fetch()['current'];
                        
                        // คำนวณเปอร์เซ็นต์
                        $percent = ($current_booked / $type['total_slots']) * 100;
                ?>
                <tr>
                    <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($type['type_name']); ?></td>
                    <td><?php echo number_format($type['price']); ?> ฿</td>
                    <td><?php echo $type['total_slots']; ?></td>
                    <td>
                        <div class="progress" style="height: 8px; width: 120px; border-radius: 10px;">
                            <div class="progress-bar bg-primary" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                        <small class="text-muted">จองแล้ว <?php echo $current_booked; ?> / <?php echo $type['total_slots']; ?></small>
                    </td>
                    ...
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>