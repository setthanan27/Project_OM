<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
include 'config.php';

// ดึงข้อมูลการจองทั้งหมด จอยกับตารางอีเวนท์และประเภทบูธ
$sql = "SELECT b.*, e.event_name, t.type_name, t.price 
        FROM event_bookings b
        JOIN events e ON b.event_id = e.id
        JOIN booth_types t ON b.type_id = t.id
        ORDER BY b.created_at DESC";
$stmt = $conn->query($sql);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการรายการจอง | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --sidebar-width: 260px; }
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; }
        .top-nav { background: var(--su-green); color: white; height: 65px; display: flex; align-items: center; padding: 0 25px; position: fixed; width: 100%; z-index: 1050; }
        .sidebar { width: var(--sidebar-width); background: white; height: 100vh; position: fixed; top: 65px; border-right: 1px solid #e0e0e0; padding-top: 15px; }
        .sidebar-menu li { padding: 15px 25px; color: #444; cursor: pointer; display: flex; align-items: center; }
        .sidebar-menu li.active { background: #edf5f3; border-left: 5px solid var(--su-green); color: var(--su-green); }
        .main-content { margin-left: var(--sidebar-width); padding: 95px 40px 40px; }
        .slip-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 1px solid #ddd; }
    </style>
</head>
<body>

<div class="top-nav justify-content-between">
    <div class="d-flex align-items-center">
        <i class="fas fa-layer-group me-3 fs-4"></i>
        <h5 class="mb-0 fw-bold">SU Web Portal <span class="fw-light opacity-75 ms-2">Admin</span></h5>
    </div>
</div>

<div class="sidebar">
    <ul class="sidebar-menu list-unstyled">
        <li onclick="location.href='admin_panel.php'"><i class="fas fa-chart-line me-2"></i> Dashboard</li>
        <li onclick="location.href='admin_users.php'"><i class="fas fa-users-cog me-2"></i> อนุมัติสมาชิก</li>
        <li class="active"><i class="fas fa-clipboard-list me-2"></i> รายการจองทั้งหมด</li>
    </ul>
</div>

<div class="main-content">
    <h3 class="fw-bold mb-4">รายการจองบูธทั้งหมด</h3>
    
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">วัน-เวลา</th>
                        <th>งานอีเวนท์</th>
                        <th>ชื่อร้านค้า / ประเภทบูธ</th>
                        <th>หลักฐานโอนเงิน</th>
                        <th>สถานะ</th>
                        <th class="text-end pe-4">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td class="ps-4 small text-muted"><?php echo date('d/m/y H:i', strtotime($b['created_at'])); ?></td>
                        <td><span class="fw-bold"><?php echo htmlspecialchars($b['event_name']); ?></span></td>
                        <td>
                            <div><?php echo htmlspecialchars($b['customer_name']); ?></div>
                            <small class="text-primary"><?php echo htmlspecialchars($b['type_name']); ?> (<?php echo number_format($b['price']); ?>฿)</small>
                        </td>
                        <td>
                            <?php if ($b['payment_slip']): ?>
                                <img src="uploads/slips/<?php echo $b['payment_slip']; ?>" class="slip-thumb" onclick="window.open(this.src)">
                            <?php else: ?>
                                <span class="text-muted small">ไม่มี (บูธฟรี)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($b['booking_status'] == 'pending'): ?>
                                <span class="badge bg-warning text-dark">รอตรวจสอบ</span>
                            <?php elseif($b['booking_status'] == 'confirmed'): ?>
                                <span class="badge bg-success">ยืนยันแล้ว</span>
                            <?php else: ?>
                                <span class="badge bg-danger">ยกเลิก</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <?php if($b['booking_status'] == 'pending'): ?>
                                <a href="update_booking.php?id=<?php echo $b['id']; ?>&status=confirmed" class="btn btn-sm btn-success">ยืนยัน</a>
                                <a href="update_booking.php?id=<?php echo $b['id']; ?>&status=cancelled" class="btn btn-sm btn-outline-danger">ปฏิเสธ</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>