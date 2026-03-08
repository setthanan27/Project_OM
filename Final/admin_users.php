<?php
session_start();
// ตรวจสอบสิทธิ์ Admin เพื่อป้องกันคนนอกแอบเข้าหน้าจัดการ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include 'config.php';

// ดึงรายชื่อเจ้าของบูธทั้งหมด เรียงตามสถานะ (pending มาก่อน) และวันที่สมัครล่าสุด
$stmt = $conn->query("SELECT * FROM booth_owners ORDER BY FIELD(status, 'pending', 'approved', 'rejected'), created_at DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติสมาชิก | SU Web Portal</title>
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
        .user-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; background: white; }
        .table thead { background: #f8fbf9; border-bottom: 2px solid #edf2f0; }
        .table thead th { font-weight: 600; color: #555; padding: 15px 20px; }
        .table tbody td { padding: 15px 20px; vertical-align: middle; }
        
        .badge-pending { background: #fff8e1; color: #ff8f00; border: 1px solid #ffe082; padding: 6px 12px; border-radius: 20px; font-weight: 500; }
        .badge-approved { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; padding: 6px 12px; border-radius: 20px; font-weight: 500; }
        .badge-rejected { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; padding: 6px 12px; border-radius: 20px; font-weight: 500; }
    </style>
</head>
<body>

<div class="top-nav justify-content-between">
    <div class="d-flex align-items-center">
        <i class="fas fa-layer-group me-3 fs-4"></i>
        <h5 class="mb-0 fw-bold">Admin Panel <span class="fw-light opacity-75 ms-2">SU Web Portal</span></h5>
    </div>
    <div class="d-flex align-items-center">
        <div class="text-end me-3">
            <small class="d-block opacity-75">ผู้ดูแลระบบ</small>
            <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
        </div>
        <a href="logout.php" class="text-white ms-2"><i class="fas fa-sign-out-alt fs-5"></i></a>
    </div>
</div>

<div class="sidebar">
    <ul class="sidebar-menu">
        <li onclick="location.href='admin_panel.php'"><i class="fas fa-chart-line"></i> Dashboard</li>
        <li onclick="location.href='admin_create_event.php'"><i class="fas fa-calendar-plus"></i> สร้างงานอีเวนท์</li>
        <li class="active" onclick="location.href='admin_users.php'"><i class="fas fa-users-cog"></i> อนุมัติสมาชิก</li>
        <li onclick="location.href='admin_bookings.php'"><i class="fas fa-clipboard-list"></i> รายการจองทั้งหมด</li>
    </ul>
</div>

<div class="main-content">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold">จัดการอนุมัติสมาชิก (Booth Owners)</h3>
            <p class="text-muted">ตรวจสอบและอนุมัติสิทธิ์ให้เจ้าของร้านค้าเพื่อเข้าใช้งานระบบจองบูธ</p>
        </div>
    </div>

    <div class="card user-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>วัน-เวลาที่สมัคร</th>
                        <th>ชื่อร้านค้า</th>
                        <th>ผู้ติดต่อ</th>
                        <th>เบอร์โทรศัพท์</th>
                        <th>สถานะ</th>
                        <th class="text-center">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
                            <td><span class="fw-bold text-dark"><?php echo htmlspecialchars($u['shop_name']); ?></span></td>
                            <td><?php echo htmlspecialchars($u['owner_name']); ?></td>
                            <td><?php echo htmlspecialchars($u['phone']); ?></td>
                            <td>
                                <?php if($u['status'] == 'pending'): ?>
                                    <span class="badge-pending"><i class="fas fa-clock me-1"></i> รออนุมัติ</span>
                                <?php elseif($u['status'] == 'approved'): ?>
                                    <span class="badge-approved"><i class="fas fa-check-circle me-1"></i> อนุมัติแล้ว</span>
                                <?php else: ?>
                                    <span class="badge-rejected"><i class="fas fa-times-circle me-1"></i> ปฏิเสธ</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($u['status'] == 'pending'): ?>
                                    <a href="update_user.php?id=<?php echo $u['id']; ?>&action=approve" 
                                       class="btn btn-success btn-sm px-3 shadow-sm" 
                                       onclick="return confirm('ยืนยันการอนุมัติสมาชิกนี้?')">อนุมัติ</a>
                                    
                                    <a href="update_user.php?id=<?php echo $u['id']; ?>&action=reject" 
                                       class="btn btn-outline-danger btn-sm px-3" 
                                       onclick="return confirm('ยืนยันการปฏิเสธสมาชิกนี้?')">ปฏิเสธ</a>
                                <?php else: ?>
                                    <a href="update_user.php?id=<?php echo $u['id']; ?>&action=pending" 
                                       class="btn btn-link btn-sm text-decoration-none text-muted">แก้ไขสถานะ</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">ไม่พบข้อมูลการสมัครสมาชิก</td>
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