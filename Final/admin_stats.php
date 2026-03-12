<?php
session_start();
if (!isset($_SESSION['admin_id'])) { 
    header("Location: login.php"); 
    exit; 
}
include 'config.php';

// --- 1. สรุปรายได้ (ยอดจองบูธ) ---
$income_confirmed = $conn->query("SELECT SUM(t.price) FROM event_bookings b JOIN booth_types t ON b.type_id = t.id WHERE b.booking_status = 'confirmed'")->fetchColumn() ?: 0;
$income_pending = $conn->query("SELECT SUM(t.price) FROM event_bookings b JOIN booth_types t ON b.type_id = t.id WHERE b.booking_status = 'pending'")->fetchColumn() ?: 0;

// --- 2. สรุปผู้เข้าร่วมกิจกรรม (ยอดสแกนจองคิวหน้างาน) ---
$total_activity_reservations = $conn->query("SELECT COUNT(*) FROM user_activity_reservations WHERE status = 'confirmed'")->fetchColumn() ?: 0;
$total_activity_cancelled = $conn->query("SELECT COUNT(*) FROM user_activity_reservations WHERE status = 'cancelled'")->fetchColumn() ?: 0;

// --- 3. วิเคราะห์หมวดหมู่สินค้า (จาก Category List) ---
$sql_cats = "SELECT category_list FROM event_bookings WHERE booking_status = 'confirmed'";
$res_cats = $conn->query($sql_cats)->fetchAll(PDO::FETCH_COLUMN);
$cat_counts = [];
foreach($res_cats as $list) {
    if($list) {
        $parts = explode(", ", $list);
        foreach($parts as $p) { if($p) $cat_counts[$p] = ($cat_counts[$p] ?? 0) + 1; }
    }
}
arsort($cat_counts);
$top_categories = array_slice($cat_counts, 0, 5); // เอาแค่ Top 5

// --- 4. บูธยอดนิยม (มีคนจองกิจกรรมเยอะที่สุด) ---
$stmt_top_booths = $conn->query("
    SELECT o.shop_name, COUNT(r.id) as guest_count 
    FROM user_activity_reservations r
    JOIN booth_activities a ON r.activity_id = a.id
    JOIN event_bookings b ON a.booking_id = b.id
    JOIN booth_owners o ON b.owner_id = o.id
    WHERE r.status = 'confirmed'
    GROUP BY o.id 
    ORDER BY guest_count DESC LIMIT 5");
$top_performance_booths = $stmt_top_booths->fetchAll();

// --- 5. ข้อมูลพื้นฐานเดิม ---
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
        .top-nav { background: var(--su-green); color: white; height: 65px; display: flex; align-items: center; padding: 0 25px; position: fixed; width: 100%; z-index: 1050; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .sidebar { width: var(--sidebar-width); background: white; height: 100vh; position: fixed; top: 65px; border-right: 1px solid #e0e0e0; padding-top: 15px; z-index: 1000; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { padding: 15px 25px; border-left: 5px solid transparent; transition: all 0.2s; color: #444; font-weight: 500; cursor: pointer; display: flex; align-items: center; }
        .sidebar-menu li i { width: 25px; margin-right: 15px; color: #888; }
        .sidebar-menu li:hover { background: #f8fbf9; color: var(--su-green); }
        .sidebar-menu li.active { background: #edf5f3; border-left-color: var(--su-green); color: var(--su-green); }
        .main-content { margin-left: var(--sidebar-width); padding: 95px 40px 40px; }
        .stat-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; background: white; }
        .progress { height: 10px; border-radius: 5px; background-color: #e9ecef; }
        .bg-light-su { background-color: #f0f7f5; }
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
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card p-4 h-100 border-start border-success border-5">
                <h6 class="text-muted fw-bold">รายได้จากการจองบูธ</h6>
                <h2 class="fw-bold text-success mb-1">฿<?php echo number_format($income_confirmed); ?></h2>
                <small class="text-warning">รอยืนยันอีก ฿<?php echo number_format($income_pending); ?></small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-4 h-100 border-start border-primary border-5">
                <h6 class="text-muted fw-bold">ยอดผู้ร่วมกิจกรรมรวม</h6>
                <h2 class="fw-bold text-primary mb-1"><?php echo number_format($total_activity_reservations); ?> <small class="fs-6">คน</small></h2>
                <small class="text-danger">ยกเลิกคืนสิทธิ์ <?php echo $total_activity_cancelled; ?> ครั้ง</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-4 h-100 border-start border-info border-5">
                <h6 class="text-muted fw-bold">อัตราการจองพื้นที่</h6>
                <h2 class="fw-bold text-info mb-1"><?php echo $occupancy_rate; ?>%</h2>
                <div class="progress mt-2"><div class="progress-bar bg-info" style="width:<?php echo $occupancy_rate; ?>%"></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-7">
            <div class="card stat-card h-100 overflow-hidden">
                <div class="p-4 border-bottom bg-white">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-trophy text-warning me-2"></i>5 อันดับบูธที่ได้รับความนิยมสูงสุด</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">ชื่อร้านค้า</th>
                                <th class="text-center">จำนวนคนร่วมกิจกรรม</th>
                                <th class="pe-4">ความฮอต</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_performance_booths as $tb): 
                                $tb_pct = ($total_activity_reservations > 0) ? ($tb['guest_count'] / $total_activity_reservations) * 100 : 0;
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?php echo htmlspecialchars($tb['shop_name']); ?></td>
                                <td class="text-center"><?php echo number_format($tb['guest_count']); ?> คน</td>
                                <td class="pe-4">
                                    <div class="progress" style="height: 6px;"><div class="progress-bar bg-warning" style="width:<?php echo $tb_pct * 3; ?>%"></div></div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card stat-card h-100">
                <div class="p-4 border-bottom bg-white">
                    <h6 class="fw-bold mb-0"><i class="fas fa-tags text-primary me-2"></i>ประเภทสินค้าในงาน</h6>
                </div>
                <div class="p-4">
                    <?php foreach($top_categories as $cat => $count): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold text-muted"><?php echo $cat; ?></span>
                            <span class="badge bg-light-su text-su-green rounded-pill px-3"><?php echo $count; ?> บูธ</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center no-print">
        <button onclick="window.print()" class="btn btn-dark px-5 py-2 rounded-pill shadow">
            <i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปผลดำเนินงาน
        </button>
    </div>
</div>

</body>
</html>