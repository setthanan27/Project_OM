<?php
session_start();
date_default_timezone_set("Asia/Bangkok");
include 'config.php';

if (isset($_GET['id'])) {
    $_SESSION['last_scanned_id'] = $_GET['id'];
}

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_picture = $_SESSION['user_picture'] ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) die("ไม่พบรหัสบูธกิจกรรม");

// 1. ดึงข้อมูลบูธ (JOIN events เพื่อเอาวันที่)
$stmt = $conn->prepare("
    SELECT b.*, o.shop_name, e.event_date 
    FROM event_bookings b 
    JOIN booth_owners o ON b.owner_id = o.id 
    JOIN events e ON b.event_id = e.id 
    WHERE b.id = ?
");
$stmt->execute([$booking_id]);
$booth = $stmt->fetch();

if (!$booth) {
    echo "<script>alert('ไม่พบข้อมูลบูธกิจกรรม'); window.location.href='index.php';</script>";
    exit;
}

$today = date('Y-m-d');
$event_date = $booth['event_date'];
$is_event_day = ($today == $event_date);
$is_past_event = ($today > $event_date);
$is_future_event = ($today < $event_date);

// 2. ดึงรอบกิจกรรม (นับเฉพาะคนที่ไม่ยกเลิก)
$stmt_times = $conn->prepare("
    SELECT a.*, 
    (SELECT COUNT(*) FROM user_activity_reservations r 
     WHERE r.activity_id = a.id AND r.status != 'cancelled') as current_booked 
    FROM booth_activities a 
    WHERE a.booking_id = ? AND a.status != 'cancelled'
    ORDER BY a.start_time ASC
");
$stmt_times->execute([$booking_id]);
$activities = $stmt_times->fetchAll();

// 3. ตรวจสอบประวัติการจอง (แก้บั๊ก: เช็คเฉพาะของบูธนี้/งานนี้ เพื่อไม่ให้ ID ชนกับงานอื่น)
$booked_ids = [];
if ($is_logged_in) {
    $stmt_check = $conn->prepare("
        SELECT r.activity_id 
        FROM user_activity_reservations r 
        JOIN booth_activities a ON r.activity_id = a.id
        WHERE r.user_id = ? AND r.status != 'cancelled' AND a.booking_id = ?
    ");
    $stmt_check->execute([$user_id, $booking_id]);
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
        .navbar { background: #ffffff !important; border-bottom: 1px solid #eee; padding: 12px 0; }
        .navbar-brand { color: var(--su-green) !important; font-size: 1.1rem; }
        .profile-img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
        .booth-banner { background: white; border-radius: 0 0 30px 30px; padding: 30px 15px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .card-booking { border-radius: 20px; border: none; background: white; transition: 0.3s; padding: 20px; position: relative; overflow: hidden; border: 2px solid transparent; }
        .card-booking.booked { border-color: var(--su-green); background-color: var(--su-soft); }
        /* แก้ UI: ถ้าจองแล้ว ให้การ์ดสว่างเสมอ */
        .card-booking.finished-card:not(.booked) { opacity: 0.6; background-color: #eee; filter: grayscale(1); }
        .time-display { font-size: 1.4rem; font-weight: 800; color: #2d3436; }
        .btn-booking { padding: 12px; border-radius: 15px; font-weight: bold; }
        .note-box { background: #fff9db; border-left: 4px solid #fcc419; padding: 10px; border-radius: 8px; font-size: 0.85rem; margin-top: 10px; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        .animate-pulse { animation: pulse 1.5s infinite; }
    </style>
</head>
<body>

<nav class="navbar sticky-top">
    <div class="container px-3">
        <a class="navbar-brand fw-bold">EventQ+</a>
        <div class="d-flex align-items-center">
            <?php if ($is_logged_in): ?>
                <img src="<?php echo $user_picture; ?>" class="profile-img shadow-sm">
            <?php else: ?>
                <a href="google_login_page.php?id=<?php echo $booking_id; ?>" class="btn btn-primary btn-sm rounded-pill px-3">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="booth-banner text-center">
    <div class="container">
        <h2 class="fw-bold mb-2"><?php echo htmlspecialchars($booth['shop_name']); ?></h2>
        
        <?php if ($is_future_event): ?>
            <div class="alert alert-info py-2 border-0 rounded-4 d-inline-block small mb-0">
                <i class="fas fa-calendar-alt me-1"></i> กิจกรรมนี้จะเริ่มในวันที่ <?php echo date('d/m/Y', strtotime($event_date)); ?>
            </div>
        <?php elseif ($is_past_event): ?>
            <div class="alert alert-danger py-2 border-0 rounded-4 d-inline-block small mb-0">
                <i class="fas fa-exclamation-triangle me-1"></i> งานกิจกรรมนี้สิ้นสุดลงแล้ว
            </div>
        <?php else: ?>
            <p class="text-muted small">เลือกเวลาที่คุณสะดวกเพื่อสำรองที่นั่งเข้าร่วมกิจกรรม</p>
            <div class="alert alert-warning py-2 border-0 rounded-4 d-inline-block small mb-0">
                <i class="fas fa-clock me-1"></i> กรุณามารอก่อนเวลารอบกิจกรรม 10 นาที
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="container px-3 pb-5">
    <div class="row g-3">
        <?php if (count($activities) > 0): ?>
            <?php foreach ($activities as $act): 
                $is_booked = in_array($act['id'], $booked_ids);
                $is_full = ($act['current_booked'] >= $act['max_slots']);
                $status = $act['status']; 

                $start_time_ts = strtotime($event_date . ' ' . $act['start_time']);
                $now_ts = time();
                $is_started = ($now_ts >= $start_time_ts);
                
                // คลาสสถานะ: ถ้าเริ่มแล้ว/จบแล้ว และไม่ได้จองไว้ ให้จางลง
                $is_finished_ui = ($status == 'finished' || ($is_event_day && $is_started && !$is_booked));
            ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card card-booking <?php echo $is_booked ? 'booked' : ''; ?> <?php echo $is_finished_ui ? 'finished-card' : ''; ?>">
                    
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="small text-muted fw-bold">รอบกิจกรรม</span>
                        <?php if ($status == 'calling'): ?>
                            <span class="badge bg-warning text-dark rounded-pill animate-pulse">กำลังเรียกคิว!</span>
                        <?php elseif ($status == 'finished' || ($is_event_day && $is_started)): ?>
                            <span class="badge bg-secondary rounded-pill text-white">ดำเนินการแล้ว</span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted rounded-pill">รอคิว</span>
                        <?php endif; ?>
                    </div>

                    <div class="time-display mb-2">
                        <?php echo date('H:i', strtotime($act['start_time'])); ?> - <?php echo date('H:i', strtotime($act['end_time'])); ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small text-muted">ผู้เข้าร่วม</span>
                        <span class="fw-bold <?php echo $is_full ? 'text-danger' : 'text-success'; ?>">
                            <?php echo $act['current_booked']; ?>/<?php echo $act['max_slots']; ?>
                        </span>
                    </div>

                    <?php if (!$is_event_day): ?>
                        <button class="btn btn-secondary w-100 btn-booking" disabled>
                            <?php echo $is_future_event ? 'ยังไม่ถึงวันจัดงาน' : 'งานสิ้นสุดแล้ว'; ?>
                        </button>
                    <?php elseif ($is_booked): ?>
                        <button class="btn btn-success w-100 btn-booking" disabled><i class="fas fa-check-circle me-2"></i>จองสำเร็จแล้ว</button>
                    <?php elseif ($is_started): ?>
                        <button class="btn btn-light w-100 btn-booking text-muted" disabled>เริ่มกิจกรรมไปแล้ว</button>
                    <?php elseif ($is_full): ?>
                        <button class="btn btn-light w-100 btn-booking text-muted" disabled>รอบนี้เต็มแล้ว</button>
                    <?php else: ?>
                        <button class="btn btn-primary w-100 btn-booking shadow-sm" onclick="doBooking(<?php echo $act['id']; ?>)">จองรอบนี้</button>
                    <?php endif; ?>

                    <?php if (!empty($act['completion_note'])): ?>
                        <div class="note-box">
                            <i class="fas fa-comment-dots me-1 text-warning"></i>
                            <strong>บันทึกจากบูธ:</strong> <?php echo htmlspecialchars($act['completion_note']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5"><h6 class="text-muted">ยังไม่มีกิจกรรมที่เปิดจองในขณะนี้</h6></div>
        <?php endif; ?>
    </div>
    
    <div class="text-center mt-5">
        <button onclick="checkLoginBeforeHistory()" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fas fa-history me-2"></i>ดูประวัติการจองของฉัน
        </button>
    </div>
</div>

<script>
// รีเฟรชทุก 30 วินาที
setTimeout(function(){ location.reload(); }, 30000);

function doBooking(id) {
    if (!<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
        Swal.fire({
            title: 'กรุณาล็อกอิน',
            text: 'เข้าสู่ระบบเพื่อดำเนินการจองกิจกรรม',
            icon: 'info',
            confirmButtonText: 'Login with Google',
            confirmButtonColor: '#3a8173',
            showCancelButton: true
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
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ปิด',
        confirmButtonColor: '#3a8173'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'save_reservation.php?activity_id=' + id + '&booking_id=<?php echo $booking_id; ?>';
                }
            });
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('error') === 'collision') {
            Swal.fire({
                title: 'เวลาทับซ้อน!',
                text: 'คุณมีการจองกิจกรรมอื่นในช่วงเวลานี้อยู่แล้ว',
                icon: 'error',
                confirmButtonColor: '#3a8173'
            });
        }
        function checkLoginBeforeHistory() {
            // เช็คสถานะการ Login จากตัวแปร PHP ที่เราเตรียมไว้
            if (!<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
                Swal.fire({
                    title: 'กรุณาล็อกอิน',
                    text: 'เข้าสู่ระบบเพื่อดูประวัติการจองกิจกรรมของคุณ',
                    icon: 'info',
                    confirmButtonText: 'Login with Google',
                    confirmButtonColor: '#3a8173',
                    showCancelButton: true,
                    cancelButtonText: 'ปิด'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // ถ้ากดตกลง ให้ไปหน้า Login พร้อมจำ ID บูธนี้ไว้
                        window.location.href = 'google_login_page.php?id=<?php echo $booking_id; ?>';
                    }
                });
            } else {
                // ถ้าล็อกอินแล้ว ให้ไปหน้าประวัติได้เลย
                window.location.href = 'my_activity_history.php';
            }
        }
</script>
</body>
</html>