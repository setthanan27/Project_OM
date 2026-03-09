<?php
session_start();
include 'config.php';

// 1. ข้อมูลผู้ใช้จาก Google Session
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_picture = $_SESSION['user_picture'] ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) die("ไม่พบรหัสบูธกิจกรรม");

// 2. ดึงข้อมูลบูธ
$stmt = $conn->prepare("SELECT b.*, o.shop_name FROM event_bookings b JOIN booth_owners o ON b.owner_id = o.id WHERE b.id = ?");
$stmt->execute([$booking_id]);
$booth = $stmt->fetch();

// 3. ดึงรอบกิจกรรมและเช็คว่าจองไปหรือยัง
$stmt_times = $conn->prepare("SELECT * FROM booth_activities WHERE booking_id = ? ORDER BY start_time ASC");
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
    <title>จองกิจกรรม - <?php echo $booth['shop_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .profile-img { width: 35px; height: 35px; border-radius: 50%; border: 2px solid #fff; }
        .card-booking { border-radius: 15px; border: none; transition: 0.3s; }
        .card-booking:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .booked { background-color: #e9ecef; }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">SU Activity</a>
        <div class="d-flex align-items-center">
            <?php if ($is_logged_in): ?>
                <span class="text-white me-2 small"><?php echo htmlspecialchars($user_name); ?></span>
                <img src="<?php echo $user_picture; ?>" class="profile-img">
                <a href="logout.php" class="btn btn-outline-light btn-sm ms-3">ออกระบบ</a>
            <?php else: ?>
                <a href="google_login_page.php?id=<?php echo $booking_id; ?>" class="btn btn-primary btn-sm">Login with Google</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
    <div class="text-center mb-4">
        <h3>ยินดีต้อนรับสู่ร้าน <strong><?php echo $booth['shop_name']; ?></strong></h3>
        <p class="text-muted">กรุณาเลือกรอบกิจกรรมที่คุณต้องการเข้าร่วม</p>
    </div>

    <div class="row g-3">
        <?php foreach ($activities as $act): 
            $is_booked = in_array($act['id'], $booked_ids);
        ?>
        <div class="col-md-4">
            <div class="card card-booking p-4 text-center shadow-sm <?php echo $is_booked ? 'booked' : ''; ?>">
                <h5 class="fw-bold"><?php echo date('H:i', strtotime($act['start_time'])); ?> - <?php echo date('H:i', strtotime($act['end_time'])); ?> น.</h5>
                <?php if ($is_booked): ?>
                    <button class="btn btn-success w-100 mt-2 rounded-pill disabled" disabled>จองสำเร็จแล้ว</button>
                <?php else: ?>
                    <button class="btn btn-outline-primary w-100 mt-2 rounded-pill" onclick="doBooking(<?php echo $act['id']; ?>)">จองรอบนี้</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-5">
        <a href="my_activity_history.php" class="btn btn-link text-muted"><i class="fas fa-history me-1"></i>ดูประวัติการจองของฉัน</a>
    </div>
</div>

<script>
function doBooking(id) {
    if (!<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
        Swal.fire('กรุณาล็อกอิน', 'กรุณาเข้าสู่ระบบ Google ก่อนจองกิจกรรมครับ', 'warning');
        return;
    }
    Swal.fire({
        title: 'ยืนยันการจอง?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ตกลง',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'save_reservation.php?activity_id=' + id + '&booking_id=<?php echo $booking_id; ?>';
        }
    });
}
</script>
</body>
</html>