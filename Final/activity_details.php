<?php
session_start();
include 'config.php'; //

// 1. ข้อมูลผู้ใช้จาก Google Session
$is_logged_in = isset($_SESSION['user_id']); //
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_picture = $_SESSION['user_picture'] ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) die("ไม่พบรหัสบูธกิจกรรม");

// 2. ดึงข้อมูลบูธและรายละเอียดกิจกรรมที่เจ้าของบูธกรอกไว้
$stmt = $conn->prepare("SELECT b.*, o.shop_name FROM event_bookings b JOIN booth_owners o ON b.owner_id = o.id WHERE b.id = ?");
$stmt->execute([$booking_id]);
$booth = $stmt->fetch(); //

// 3. ดึงรอบกิจกรรมและนับจำนวนคนจองจริงเทียบกับค่า max_slots
$stmt_times = $conn->prepare("
    SELECT a.*, 
    (SELECT COUNT(*) FROM user_activity_reservations r WHERE r.activity_id = a.id) as current_booked 
    FROM booth_activities a 
    WHERE a.booking_id = ? 
    ORDER BY a.start_time ASC
");
$stmt_times->execute([$booking_id]);
$activities = $stmt_times->fetchAll(); //

// 4. ตรวจสอบว่าผู้ใช้จองรอบไหนไปแล้วบ้าง
$booked_ids = [];
if ($is_logged_in) {
    $stmt_check = $conn->prepare("SELECT activity_id FROM user_activity_reservations WHERE user_id = ?");
    $stmt_check->execute([$user_id]);
    $booked_ids = $stmt_check->fetchAll(PDO::FETCH_COLUMN); //
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองกิจกรรม - <?php echo htmlspecialchars($booth['shop_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --su-green: #3a8173; }
        body { background-color: #f8f9fa; font-family: 'Sarabun', sans-serif; }
        .navbar { background: #212529 !important; }
        .profile-img { width: 35px; height: 35px; border-radius: 50%; border: 2px solid #fff; object-fit: cover; }
        .card-booking { border-radius: 20px; border: none; transition: 0.3s; background: white; }
        .card-booking:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .booked { background-color: #f0f7f5; border: 2px solid var(--su-green); }
        .full-booked { background-color: #fff5f5; opacity: 0.8; }
        .badge-limit { font-size: 0.8rem; border-radius: 10px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><i class="fas fa-layer-group me-2"></i>EventQ+ Activity</a>
        <div class="d-flex align-items-center">
            <?php if ($is_logged_in): ?>
                <span class="text-white me-2 small d-none d-md-inline"><?php echo htmlspecialchars($user_name); ?></span>
                <img src="<?php echo $user_picture; ?>" class="profile-img">
                <a href="logout.php?back_id=<?php echo $booking_id; ?>" class="btn btn-outline-light btn-sm ms-3 rounded-pill">ออกจากระบบ</a>
            <?php else: ?>
                <a href="google_login_page.php?id=<?php echo $booking_id; ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="fab fa-google me-1"></i> Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
    <div class="text-center mb-5">
        <h2 class="fw-bold">ยินดีต้อนรับสู่ร้าน <span class="text-success"><?php echo htmlspecialchars($booth['shop_name']); ?></span></h2>
        
        <?php if (!empty($booth['activity_detail'])): ?>
            <div class="alert alert-info d-inline-block mt-2 px-4 rounded-pill shadow-sm">
                <i class="fas fa-star text-warning me-2"></i><?php echo htmlspecialchars($booth['activity_detail']); ?>
            </div>
        <?php endif; ?>

        <div class="mt-3">
        <div class="d-inline-flex align-items-center bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 px-3 py-2 rounded-3">
            <i class="fas fa-exclamation-circle me-2"></i>
            <span class="small fw-bold">เงื่อนไข: กรุณามารอหน้าบูธกิจกรรมก่อนเริ่มประมาณ 10 นาทีด้วย ค่ะ/ครับ</span>
        </div>
    </div>
        
        <p class="text-muted mt-3">กรุณาเลือกรอบกิจกรรมที่ต้องการเข้าร่วม (จำกัดจำนวนต่อรอบ)</p>
    </div>

    <div class="row g-4 justify-content-center">
        <?php if (count($activities) > 0): ?>
            <?php foreach ($activities as $act): 
                $is_booked = in_array($act['id'], $booked_ids);
                $is_full = ($act['current_booked'] >= $act['max_slots']);
                $is_calling = ($act['status'] == 'calling');
                $is_cancelled = ($act['status'] == 'cancelled');
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card card-booking p-4 text-center shadow-sm <?php echo $is_booked ? 'booked' : ''; ?> <?php echo ($is_full && !$is_booked) ? 'full-booked' : ''; ?>">
                    
                    <div class="mb-2">
                        <?php if ($is_calling): ?>
                            <span class="badge bg-warning text-dark mb-2 rounded-pill px-3"><i class="fas fa-bullhorn me-1"></i> กำลังเรียกคิว!</span>
                        <?php elseif ($is_cancelled): ?>
                            <span class="badge bg-danger mb-2 rounded-pill px-3">ยกเลิกรอบนี้</span>
                        <?php endif; ?>
                    </div>

                    <h4 class="fw-bold mb-2"><?php echo date('H:i', strtotime($act['start_time'])); ?> - <?php echo date('H:i', strtotime($act['end_time'])); ?> น.</h4>
                    
                    <div class="mb-3">
                        <span class="badge badge-limit <?php echo $is_full ? 'bg-danger' : 'bg-light text-dark border'; ?>">
                            <i class="fas fa-users me-1"></i> จองแล้ว <?php echo $act['current_booked']; ?> / <?php echo $act['max_slots']; ?> คน
                        </span>
                    </div>

                    <?php if ($is_booked): ?>
                        <button class="btn btn-success w-100 rounded-pill fw-bold" disabled>
                            <i class="fas fa-check-circle me-1"></i> จองสำเร็จแล้ว
                        </button>
                    <?php elseif ($is_cancelled): ?>
                        <button class="btn btn-secondary w-100 rounded-pill disabled" disabled>ไม่สามารถจองได้</button>
                    <?php elseif ($is_full): ?>
                        <button class="btn btn-outline-danger w-100 rounded-pill disabled" disabled>รอบนี้เต็มแล้ว</button>
                    <?php else: ?>
                        <button class="btn btn-outline-primary w-100 rounded-pill fw-bold" onclick="doBooking(<?php echo $act['id']; ?>)">
                            จองรอบนี้
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-clock fa-4x text-muted mb-3 opacity-25"></i>
                <h5 class="text-muted">ทางบูธยังไม่ได้เปิดรอบเวลากิจกรรมในขณะนี้</h5>
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center mt-5 mb-5">
        <a href="my_activity_history.php" class="btn btn-link text-decoration-none text-muted fw-bold">
            <i class="fas fa-history me-1"></i> ดูประวัติกิจกรรมทั้งหมดของฉัน
        </a>
    </div>
</div>

<script>
function doBooking(id) {
    // ตรวจสอบสถานะการล็อกอินผ่าน JavaScript
    if (!<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
        Swal.fire({
            title: 'กรุณาล็อกอิน',
            text: 'คุณต้องเข้าสู่ระบบ Google ก่อนทำการจองกิจกรรม',
            icon: 'warning',
            confirmButtonText: 'ไปหน้าล็อกอิน',
            confirmButtonColor: '#3a8173'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'google_login_page.php?id=<?php echo $booking_id; ?>';
            }
        });
        return;
    }

    // ยืนยันการจอง
    Swal.fire({
        title: 'ยืนยันการจองรอบนี้?',
        text: 'ระบบจะตรวจสอบว่าเวลาของคุณซ้ำกับกิจกรรมอื่นหรือไม่',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ยืนยันการจอง',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#3a8173',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'save_reservation.php?activity_id=' + id + '&booking_id=<?php echo $booking_id; ?>';
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>