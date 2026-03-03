<?php
session_start();
include 'config.php';

// ถ้า Login แล้ว ให้ดึงข้อมูลงานมาแสดง
if (isset($_SESSION['owner_id'])) {
    $stmt = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
    $events = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จองบูธแสดงสินค้า | SU Web Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --su-dark: #2d6358; }
        body { background-color: #f8f9fa; font-family: 'Sarabun', sans-serif; }
        .hero-section { background: var(--su-green); color: white; padding: 80px 0; border-bottom: 5px solid var(--su-dark); }
        .auth-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: 0.3s; }
        .auth-card:hover { transform: translateY(-5px); }
        .btn-su { background: var(--su-green); color: white; border-radius: 10px; padding: 12px 25px; border: none; font-weight: bold; }
        .btn-su:hover { background: var(--su-dark); color: white; }
        .event-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['owner_id'])): ?>
    <div class="hero-section text-center">
        <div class="container">
            <i class="fas fa-store fa-4x mb-4"></i>
            <h1 class="fw-bold">ระบบจองบูธสำหรับร้านค้า</h1>
            <p class="lead opacity-75">กรุณาเข้าสู่ระบบเพื่อเลือกชมงานอีเวนท์และจองพื้นที่</p>
        </div>
    </div>

    <div class="container" style="margin-top: -50px;">
        <div class="row justify-content-center g-4">
            <div class="col-md-5">
                <div class="card auth-card p-4 text-center">
                    <div class="card-body">
                        <i class="fas fa-user-circle fa-3x mb-3 text-muted"></i>
                        <h4>มีบัญชีอยู่แล้ว?</h4>
                        <p class="text-muted">เข้าสู่ระบบเพื่อจัดการการจองของคุณ</p>
                        <a href="user_login.php" class="btn btn-su w-100">เข้าสู่ระบบ (Login)</a>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card auth-card p-4 text-center">
                    <div class="card-body">
                        <i class="fas fa-user-plus fa-3x mb-3 text-muted"></i>
                        <h4>ยังไม่มีบัญชี?</h4>
                        <p class="text-muted">ลงทะเบียนขอสิทธิ์เข้าใช้งานระบบ</p>
                        <a href="register.php" class="btn btn-outline-success border-2 w-100" style="border-radius: 10px; padding: 11px; font-weight: bold;">สมัครสมาชิก (Register)</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <nav class="navbar navbar-dark shadow-sm mb-4" style="background: var(--su-green);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">SU Web Portal</a>
            <div class="text-white">
                <span class="me-3">สวัสดี, คุณ <b><?php echo $_SESSION['shop_name']; ?></b></span>
                <a href="logout_user.php" class="btn btn-sm btn-outline-light">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h3 class="fw-bold mb-4">งานอีเวนท์ที่เปิดให้จอง</h3>
        <div class="row g-4">
            <?php foreach ($events as $event): ?>
            <div class="col-md-4">
                <div class="card event-card h-100">
                    <div class="card-body p-4 text-center">
                        <h4 class="fw-bold"><?php echo htmlspecialchars($event['event_name']); ?></h4>
                        <p class="text-muted"><i class="fas fa-calendar-alt me-2"></i><?php echo date('d M Y', strtotime($event['event_date'])); ?></p>
                        <p class="small"><i class="fas fa-map-marker-alt text-danger me-1"></i> <?php echo htmlspecialchars($event['location']); ?></p>
                        <hr>
                        <a href="booking.php?event_id=<?php echo $event['id']; ?>" class="btn btn-su px-4 shadow-sm">เลือกจองบูธ</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

</body>
</html>