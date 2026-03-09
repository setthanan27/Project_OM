<?php
session_start();
if (!isset($_SESSION['admin_id'])) { 
    header("Location: login.php"); 
    exit; 
}
include 'config.php';

// --- ส่วนการดึงข้อมูลสถิติ ---
$income_confirmed = $conn->query("SELECT SUM(t.price) FROM event_bookings b JOIN booth_types t ON b.type_id = t.id WHERE b.booking_status = 'confirmed'")->fetchColumn() ?: 0;
$income_pending = $conn->query("SELECT SUM(t.price) FROM event_bookings b JOIN booth_types t ON b.type_id = t.id WHERE b.booking_status = 'pending'")->fetchColumn() ?: 0;

$stmt_popular = $conn->query("SELECT t.type_name, COUNT(b.id) as count FROM booth_types t LEFT JOIN event_bookings b ON t.id = b.type_id GROUP BY t.id ORDER BY count DESC LIMIT 5");
$popular_booths = $stmt_popular->fetchAll();

$total_users = $conn->query("SELECT COUNT(*) FROM booth_owners WHERE status = 'approved'")->fetchColumn();
$wait_users = $conn->query("SELECT COUNT(*) FROM booth_owners WHERE status = 'pending'")->fetchColumn();

$total_slots = $conn->query("SELECT SUM(total_slots) FROM booth_types")->fetchColumn() ?: 0;
$total_booked = $conn->query("SELECT COUNT(*) FROM event_bookings WHERE booking_status != 'cancelled'")->fetchColumn();
$occupancy_rate = ($total_slots > 0) ? round(($total_booked / $total_slots) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปสถิติระบบ | EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --su-dark: #2d6358; --sidebar-width: 260px; }
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; }
        
        /* Sidebar & Nav Styles - ปรับให้เหมือน admin_panel.php */
        .top-nav { background: var(--su-green); color: white; height: 65px; display: flex; align-items: center; padding: 0 25px; position: fixed; width: 100%; z-index: 1050; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .sidebar { width: var(--sidebar-width); background: white; height: 100vh; position: fixed; top: 65px; border-right: 1px solid #e0e0e0; padding-top: 15px; z-index: 1000; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { padding: 15px 25px; border-left: 5px solid transparent; transition: all 0.2s; color: #444; font-weight: 500; cursor: pointer; display: flex; align-items: center; }
        .sidebar-menu li i { width: 25px; margin-right: 15px; color: #888; }
        .sidebar-menu li:hover { background: #f8fbf9; color: var(--su-green); }
        .sidebar-menu li.active { background: #edf5f3; border-left-color: var(--su-green); color: var(--su-green); }

        .main-content { margin-left: var(--sidebar-width); padding: 95px 40px 40px; }
        .stat-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .progress { height: 10px; border-radius: 5px; background-color: #e9ecef; }
    </style>
</head>
<body>

<div class="top-nav justify-content-between">
    <div class="d-flex align-items-center">
        <i class="fas fa-layer-group me-3 fs-4"></i>
        <h5 class="mb-0 fw-bold">Admin Panel <span class="fw-light opacity-75 ms-2">Admin Stats</span></h5>
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
         <li onclick="location.href='admin_bookings.php'"><i class="fas fa-clipboard-list"></i> รายการจองทั้งหมด</li>
         <li onclick="location.href='admin_users.php'"><i class="fas fa-users-cog"></i> จัดการสมาชิก</li>
        <li class="active"><i class="fas fa-chart-pie"></i> รายงานสถิติ</li>
    </ul>
</div>

<div class="main-content">
    <h3 class="fw-bold mb-4">รายงานสรุปผลการดำเนินงาน</h3>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card stat-card h-100 p-4">
                <h6 class="text-muted fw-bold mb-3">สรุปรายได้ (Revenue)</h6>
                <div class="d-flex justify-content-between align-items-end">
                    <div>
                        <h2 class="fw-bold text-success mb-0">฿<?php echo number_format($income_confirmed); ?></h2>
                        <small class="text-muted">ชำระเงินเรียบร้อยแล้ว</small>
                    </div>
                    <div class="text-end">
                        <h4 class="text-warning mb-0">฿<?php echo number_format($income_pending); ?></h4>
                        <small class="text-muted">รอตรวจสอบ</small>
                    </div>
                </div>
                <hr>
                <div class="small text-muted">รายได้รวมที่คาดการณ์: <b class="text-dark">฿<?php echo number_format($income_confirmed + $income_pending); ?></b></div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card stat-card h-100 p-4">
                <h6 class="text-muted fw-bold mb-3">อัตราการจองบูธ (Occupancy)</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold"><?php echo $occupancy_rate; ?>%</span>
                    <span class="text-muted small">จองแล้ว <?php echo $total_booked; ?> / <?php echo $total_slots; ?> บูธ</span>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar bg-primary" style="width: <?php echo $occupancy_rate; ?>%"></div>
                </div>
                <div class="row text-center mt-3">
                    <div class="col-6 border-end">
                        <h4 class="fw-bold mb-0"><?php echo $total_users; ?></h4>
                        <small class="text-muted small">สมาชิกที่อนุมัติแล้ว</small>
                    </div>
                    <div class="col-6">
                        <h4 class="fw-bold mb-0 text-warning"><?php echo $wait_users; ?></h4>
                        <small class="text-muted small">รออนุมัติสมาชิก</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card stat-card overflow-hidden">
        <div class="card-body p-0">
            <div class="p-4 border-bottom">
                <h6 class="text-muted fw-bold mb-0">ประเภทบูธยอดนิยม (Top 5)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ชื่อประเภทบูธ</th>
                            <th class="text-center">จำนวนครั้งที่จอง</th>
                            <th class="pe-4">สัดส่วนการจอง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($popular_booths as $pb): 
                            $pb_percent = ($total_booked > 0) ? ($pb['count'] / $total_booked) * 100 : 0;
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($pb['type_name']); ?></td>
                            <td class="text-center"><?php echo $pb['count']; ?> ครั้ง</td>
                            <td class="pe-4" style="width: 35%;">
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $pb_percent; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-5 no-print">
        <button onclick="window.print()" class="btn btn-dark px-4 shadow-sm rounded-pill">
            <i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปผล
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>