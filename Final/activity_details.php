<?php
session_start();
include 'config.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_picture = $_SESSION['user_picture'] ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) die("ไม่พบรหัสบูธกิจกรรม");

$stmt = $conn->prepare("SELECT b.*, o.shop_name FROM event_bookings b JOIN booth_owners o ON b.owner_id = o.id WHERE b.id = ?");
$stmt->execute([$booking_id]);
$booth = $stmt->fetch();
if (!$booth) {
    echo "<script>alert('ไม่พบข้อมูลบูธกิจกรรม'); window.location.href='index.php';</script>";
    exit;
}

$stmt_times = $conn->prepare("
    SELECT a.*, 
    (SELECT COUNT(*) FROM user_activity_reservations r WHERE r.activity_id = a.id) as current_booked 
    FROM booth_activities a 
    WHERE a.booking_id = ? 
    ORDER BY a.start_time ASC
");
$stmt_times->execute([$booking_id]);
$activities = $stmt_times->fetchAll();

$booked_ids = [];
if ($is_logged_in) {
    $stmt_check = $conn->prepare("SELECT activity_id FROM user_activity_reservations WHERE user_id = ?");
    $stmt_check->execute([$user_id]);
    $booked_ids = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>จองกิจกรรม - <?php echo htmlspecialchars($booth['shop_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --su-green: #3a8173; --su-soft: #f0f7f5; }
        body { background-color: #f4f7f6; font-family: 'Sarabun', sans-serif; -webkit-tap-highlight-color: transparent; }
        
        /* Navbar Responsive */
        .navbar { background: #ffffff !important; border-bottom: 1px solid #eee; padding: 12px 0; }
        .navbar-brand { color: var(--su-green) !important; font-size: 1.1rem; }
        .profile-img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        /* Booth Header */
        .booth-banner { background: white; border-radius: 0 0 30px 30px; padding: 30px 15px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        
        /* Responsive Card Grid */
        .card-booking { border-radius: 20px; border: none; background: white; transition: 0.3s; padding: 20px; }
        .card-booking.booked { border: 2px solid var(--su-green); background-color: var(--su-soft); }
        .card-booking.full-booked { opacity: 0.7; }
        
        /* Typography Responsive */
        h2 { font-size: 1.5rem; }
        .time-display { font-size: 1.4rem; font-weight: 800; color: #2d3436; }
        
        /* Buttons */
        .btn-booking { padding: 12px; border-radius: 15px; font-weight: bold; font-size: 1rem; }
        
        @media (max-width: 576px) {
            .booth-banner { padding: 20px 10px; }
            h2 { font-size: 1.25rem; }
            .time-display { font-size: 1.2rem; }
            .card-booking { padding: 15px; }
        }
    </style>
</head>
<body>


<nav class="navbar sticky-top mb-0">
    <div class="container px-3">
        <a class="navbar-brand fw-bold">EventQ+
        </a>
        <div class="d-flex align-items-center">
            <?php if ($is_logged_in): ?>
                <div class="d-flex align-items-center bg-light rounded-pill p-1 pe-3">
                    <img src="<?php echo $user_picture; ?>" class="profile-img me-2">
                    <span class="small fw-bold d-none d-sm-inline me-2"><?php echo htmlspecialchars($user_name); ?></span>
                    
                    <div class="vr mx-2 d-none d-sm-block" style="height: 20px;"></div>
                    
                    <a href="logout_users.php?back_id=<?php echo $booking_id; ?>" class="text-danger small text-decoration-none fw-bold ms-1">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            <?php else: ?>
                <a href="google_login_page.php?id=<?php echo $booking_id; ?>" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                    <i class="fab fa-google me-1"></i> Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="booth-banner text-center">
    <div class="container">
        <h2 class="fw-bold mb-2">บูธ <span class="text-success"><?php echo htmlspecialchars($booth['shop_name']); ?></span></h2>
        <?php if (!empty($booth['activity_detail'])): ?>
            <p class="text-muted small mb-3"><?php echo htmlspecialchars($booth['activity_detail']); ?></p>
        <?php endif; ?>
        
        <div class="alert alert-warning py-2 border-0 rounded-4 d-inline-block small mb-0">
            <i class="fas fa-info-circle me-1"></i> กรุณามารอก่อนเวลา 10 นาที
        </div>
    </div>
</div>

<div class="container px-3 pb-5">
    <div class="row g-3">
        <?php if (count($activities) > 0): ?>
            <?php foreach ($activities as $act): 
                $is_booked = in_array($act['id'], $booked_ids);
                $is_full = ($act['current_booked'] >= $act['max_slots']);
                $is_calling = ($act['status'] == 'calling');
            ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card card-booking <?php echo $is_booked ? 'booked' : ''; ?> <?php echo ($is_full && !$is_booked) ? 'full-booked' : ''; ?>">
                    
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-muted fw-bold"><i class="far fa-clock me-1"></i> รอบเวลา</span>
                        <?php if ($is_calling): ?>
                            <span class="badge bg-warning text-dark rounded-pill animate-pulse">กำลังเรียกคิว</span>
                        <?php endif; ?>
                    </div>

                    <div class="time-display mb-3">
                        <?php echo date('H:i', strtotime($act['start_time'])); ?> - <?php echo date('H:i', strtotime($act['end_time'])); ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-2 rounded-3">
                        <span class="small text-muted">จำนวนที่จอง</span>
                        <span class="fw-bold <?php echo $is_full ? 'text-danger' : 'text-success'; ?>">
                            <?php echo $act['current_booked']; ?>/<?php echo $act['max_slots']; ?>
                        </span>
                    </div>

                    <?php if ($is_booked): ?>
                        <button class="btn btn-success w-100 btn-booking" disabled>
                            <i class="fas fa-check-circle me-2"></i>จองสำเร็จแล้ว
                        </button>
                    <?php elseif ($is_full): ?>
                        <button class="btn btn-light w-100 btn-booking text-muted" disabled>
                            รอบนี้เต็มแล้ว
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary w-100 btn-booking shadow-sm" onclick="doBooking(<?php echo $act['id']; ?>)">
                            จองรอบนี้
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <h6 class="text-muted">ยังไม่มีกิจกรรมที่เปิดจองในขณะนี้</h6>
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center mt-5">
        <a href="my_activity_history.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fas fa-history me-2"></i>ดูประวัติการจองของฉัน
        </a>
    </div>
</div>

<script>
function doBooking(id) {
    if (!<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
        Swal.fire({
            title: 'กรุณาล็อกอิน',
            text: 'เข้าสู่ระบบด้วย Google เพื่อจองกิจกรรม',
            icon: 'info',
            confirmButtonText: 'ล็อกอินเลย',
            confirmButtonColor: '#3a8173',
            showCancelButton: true,
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'google_login_page.php?id=<?php echo $booking_id; ?>';
            }
        });
        return;
    }

    Swal.fire({
        title: 'ยืนยันการจอง?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'จองรอบนี้',
        cancelButtonText: 'ปิด',
        confirmButtonColor: '#3a8173'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'save_reservation.php?activity_id=' + id + '&booking_id=<?php echo $booking_id; ?>';
        }
    });
}
</script>

</body>
</html>