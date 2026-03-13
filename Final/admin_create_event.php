<?php
session_start();
// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include 'config.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างงานใหม่ | EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --su-dark: #2d6358; --sidebar-width: 260px; }
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; }
        
        .top-nav { background: var(--su-green); color: white; height: 65px; display: flex; align-items: center; padding: 0 25px; position: fixed; width: 100%; z-index: 1050; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .sidebar { width: var(--sidebar-width); background: white; height: 100vh; position: fixed; top: 65px; border-right: 1px solid #e0e0e0; padding-top: 15px; z-index: 1000; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { padding: 15px 25px; border-left: 5px solid transparent; color: #444; font-weight: 500; cursor: pointer; display: flex; align-items: center; }
        .sidebar-menu li i { width: 25px; margin-right: 15px; color: #888; }
        .sidebar-menu li.active { background: #edf5f3; border-left-color: var(--su-green); color: var(--su-green); }

        .main-content { margin-left: var(--sidebar-width); padding: 95px 40px 40px; }
        .form-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .section-title { border-left: 5px solid var(--su-green); padding-left: 12px; font-weight: bold; color: var(--su-green); margin-bottom: 25px; }
        .booth-item { background: #f8fbf9; padding: 20px; border-radius: 12px; border: 1px solid #e0e0e0; margin-bottom: 15px; position: relative; }
        .btn-save { background: var(--su-green); color: white; border-radius: 8px; padding: 12px 30px; font-weight: bold; border: none; }
        .btn-save:hover { background: var(--su-dark); color: white; }
    </style>
</head>
<body>

<div class="top-nav justify-content-between">
    <div class="d-flex align-items-center">
        <i class="fas fa-layer-group me-3 fs-4"></i>
        <h5 class="mb-0 fw-bold">Admin Panel <span class="fw-light opacity-75 ms-2">Create an event</span></h5>
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
        <li class="active"><i class="fas fa-calendar-plus"></i> สร้างงานอีเวนท์</li>
        <li onclick="location.href='admin_bookings.php'"><i class="fas fa-clipboard-list"></i> รายการจองทั้งหมด</li>
        <li onclick="location.href='admin_users.php'"><i class="fas fa-users-cog"></i> จัดการสมาชิก</li>
        <li onclick="location.href='admin_stats.php'"><i class="fas fa-chart-pie"></i> รายงานสถิติ</li>
    </ul>
</div>

<div class="main-content">
    <div class="card form-card">
        <div class="card-body p-4">
            <form action="save_event.php" method="POST">
                <div class="section-title h5">ข้อมูลอีเวนท์</div>
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">ชื่ออีเวนท์</label>
                        <input type="text" name="event_name" class="form-control" placeholder="เช่น SU Open House 2026" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">วันที่</label>
                        <input type="date" name="event_date" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">เบอร์โทรติดต่อ</label>
                        <input type="text" name="contact_phone" class="form-control" placeholder="08x-xxx-xxxx">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">สถานที่</label>
                        <input type="text" name="location" class="form-control" placeholder="ระบุอาคารหรือพิกัดจัดงาน" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">รายละเอียดอีเวนท์</label>
                        <textarea name="event_detail" class="form-control" rows="3" placeholder="ภาพรวมของงานกิจกรรม..."></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-primary">คำแนะนำสำหรับผู้เข้าร่วม</label>
                        <textarea name="instructions" class="form-control" rows="2" placeholder="ข้อควรปฏิบัติ, การเตรียมตัว..."></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-primary">สิ่งอำนวยความสะดวก</label>
                        <textarea name="facilities" class="form-control" rows="2" placeholder="ที่จอดรถ, WIFI, จุดบริการน้ำ..."></textarea>
                    </div>
                </div>

                <div class="section-title h5 d-flex justify-content-between align-items-center">
                    การตั้งค่าประเภทบูธ
                    <button type="button" id="add-booth" class="btn btn-sm btn-outline-success">+ เพิ่มประเภทบูธ</button>
                </div>

                <div id="booth-container">
                    <div class="booth-item shadow-sm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">ประเภทบูธ</label>
                                <input type="text" name="booth_type[]" class="form-control" placeholder="เช่น อาหาร" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">ราคา (฿)</label>
                                <input type="number" name="booth_price[]" class="form-control" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">จำนวน (บูธ)</label>
                                <input type="number" name="booth_qty[]" class="form-control" value="1">
                            </div>
                            <div class="col-md-2 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-outline-danger remove-booth"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <button type="button" onclick="history.back()" class="btn btn-save shadow me-2" style="background: #6c757d;">กลับ</button>
                    <button type="submit" class="btn btn-save shadow">ยืนยันการสร้างงาน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // สคริปต์เพิ่มแถวประเภทบูธใหม่
    document.getElementById('add-booth').addEventListener('click', function() {
        const container = document.getElementById('booth-container');
        const newItem = document.querySelector('.booth-item').cloneNode(true);
        newItem.querySelectorAll('input').forEach(input => input.value = input.defaultValue);
        container.appendChild(newItem);
    });

    // สคริปต์ลบแถวประเภทบูธ
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-booth')) {
            const items = document.querySelectorAll('.booth-item');
            if (items.length > 1) e.target.closest('.booth-item').remove();
        }
    });
</script>
</body>
</html>