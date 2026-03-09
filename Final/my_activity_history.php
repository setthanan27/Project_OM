<?php
session_start();
include 'config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: google_login_page.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'ผู้ใช้งาน';
$user_picture = $_SESSION['user_picture'] ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

// ดึงข้อมูลการจองกิจกรรมของผู้ใช้รายนี้
$sql = "SELECT r.*, o.shop_name, e.event_name 
        FROM user_activity_reservations r
        JOIN booth_activities a ON r.activity_id = a.id
        JOIN event_bookings b ON a.booking_id = b.id
        JOIN booth_owners o ON b.owner_id = o.id
        JOIN events e ON b.event_id = e.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$my_bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติการจองกิจกรรมของฉัน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; }
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; }
        .profile-section { background: white; border-radius: 20px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .profile-img { width: 100px; height: 100px; border-radius: 50%; border: 4px solid var(--su-green); object-fit: cover; }
        .history-card { border: none; border-radius: 15px; transition: 0.3s; }
        .history-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="profile-section text-center">
        <img src="<?php echo $user_picture; ?>" class="profile-img mb-3" alt="Profile">
        <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($user_name); ?></h3>
        <p class="text-muted">ยินดีต้อนรับสู่ระบบจองกิจกรรม</p>
        <a href="activity_details.php?id=<?php echo $back_id; ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-4">
        <i class="fas fa-arrow-left me-1"></i> กลับไปหน้าจองกิจกรรม </a>
    </div>

    <h4 class="fw-bold mb-4"><i class="fas fa-history me-2 text-primary"></i>กิจกรรมที่คุณจองไว้</h4>

    <div class="row g-4">
        <?php if (count($my_bookings) > 0): ?>
            <?php foreach ($my_bookings as $res): ?>
                <div class="col-md-6">
                    <div class="card history-card shadow-sm p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($res['shop_name']); ?></h5>
                                <div class="badge bg-success mb-3">จองสำเร็จ</div>
                            </div>
                            <i class="fas fa-calendar-check fa-2x text-opacity-25 text-success"></i>
                        </div>
                        <div class="text-muted small">
                            <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i>งาน: <?php echo htmlspecialchars($res['event_name']); ?></p>
                            <p class="mb-0"><i class="fas fa-clock me-2"></i>เวลา: <strong><?php echo date('H:i', strtotime($res['start_time'])); ?> - <?php echo date('H:i', strtotime($res['end_time'])); ?> น.</strong></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3 opacity-25"></i>
                <h5 class="text-muted">คุณยังไม่มีประวัติการจองกิจกรรม</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>