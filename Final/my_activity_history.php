<?php
session_start();
include 'config.php';

// 1. ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: google_login_page.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'ผู้ใช้งาน';
$user_picture = $_SESSION['user_picture'] ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

// 2. ดึงข้อมูลการจอง และหา ID บูธล่าสุดเพื่อทำปุ่มย้อนกลับ
$sql = "SELECT r.id as res_id, r.*, b.id as b_id, o.shop_name, e.event_name, a.status as round_status, a.start_time, a.end_time 
        FROM user_activity_reservations r
        JOIN booth_activities a ON r.activity_id = a.id
        JOIN event_bookings b ON a.booking_id = b.id
        JOIN booth_owners o ON b.owner_id = o.id
        JOIN events e ON b.event_id = e.id
        WHERE r.user_id = ?
        ORDER BY FIELD(a.status, 'calling', 'pending', 'finished', 'cancelled'), r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$my_bookings = $stmt->fetchAll();

// หา ID บูธล่าสุดที่จองไว้เพื่อทำ Link ย้อนกลับ
$latest_booth_id = (count($my_bookings) > 0) ? $my_bookings[0]['b_id'] : null;
$back_link = $latest_booth_id ? "activity_details.php?id=" . $latest_booth_id : "index.php";

// 3. เช็คสถานะการเรียกคิว
$has_calling = false;
foreach ($my_bookings as $check) {
    if ($check['round_status'] == 'calling') {
        $has_calling = true;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กิจกรรมของฉัน | EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --su-green: #3a8173; --su-soft: #f0f7f5; }
        body { background-color: #f4f7f6; font-family: 'Sarabun', sans-serif; }
        .navbar { background: #ffffff !important; border-bottom: 1px solid #eee; padding: 12px 0; }
        .navbar-brand { color: var(--su-green) !important; font-size: 1.1rem; }
        .profile-section { background: white; border-radius: 0 0 30px 30px; padding: 30px 0; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .profile-img-main { width: 70px; height: 70px; border-radius: 50%; border: 3px solid var(--su-soft); object-fit: cover; }
        .alert-calling { background: #fff9db; border: none; border-left: 5px solid #fcc419; border-radius: 15px; animation: pulse-bg 2s infinite; }
        @keyframes pulse-bg { 0% { transform: scale(1); } 50% { transform: scale(0.98); } 100% { transform: scale(1); } }
        .history-card { border: none; border-radius: 20px; background: white; transition: 0.3s; padding: 20px; }
        .badge-status { padding: 8px 16px; border-radius: 12px; font-weight: 700; font-size: 0.75rem; }
        .time-box { background: var(--su-soft); color: var(--su-green); border-radius: 10px; padding: 5px 12px; font-weight: 800; display: inline-block; }
    </style>
</head>
<body>

<audio id="notificationSound" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>

<nav class="navbar sticky-top">
    <div class="container px-3">
        <a class="navbar-brand fw-bold text-decoration-none" href="<?php echo $back_link; ?>">
            <i class="fas fa-chevron-left me-2 small"></i>กลับไปหน้าบูธ
        </a>
        <div class="d-flex align-items-center">
            <a href="logout_users.php" class="text-danger small text-decoration-none fw-bold bg-light rounded-pill px-3 py-1">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="profile-section text-center">
    <div class="container">
        <img src="<?php echo $user_picture; ?>" class="profile-img-main mb-3 shadow-sm">
        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($user_name); ?></h4>
        <p class="text-muted small mb-0">ติดตามสถานะคิวของคุณแบบเรียลไทม์</p>
    </div>
</div>

<div class="container px-3 pb-5">
    <div class="notification-area">
        <?php foreach ($my_bookings as $res): if ($res['round_status'] == 'calling'): ?>
            <div class="alert alert-calling shadow-sm p-4 d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    <div class="bg-warning rounded-circle p-3 me-3 d-none d-sm-block">
                        <i class="fas fa-bullhorn fa-lg text-white"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">ถึงคิวของคุณแล้ว!</h5>
                        <p class="mb-0 text-muted small">โปรดไปที่บูธ <strong><?php echo htmlspecialchars($res['shop_name']); ?></strong> ทันที</p>
                    </div>
                </div>
                <div class="text-warning fw-bold animate-pulse"><i class="fas fa-walking fa-2x"></i></div>
            </div>
        <?php endif; endforeach; ?>
    </div>

    <h6 class="fw-bold text-muted mb-4 px-1"><i class="fas fa-list-ul me-2"></i>รายการจองของคุณ</h6>

    <div class="row g-3">
        <?php if (count($my_bookings) > 0): ?>
            <?php foreach ($my_bookings as $res): ?>
                <div class="col-12 col-md-6">
                    <div class="card history-card shadow-sm">
                        <div class="d-flex justify-content-between align-items-start">
                            <div style="flex: 1;">
                                <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($res['shop_name']); ?></h5>
                                <p class="text-muted small mb-3"><?php echo htmlspecialchars($res['event_name']); ?></p>
                                <div class="time-box">
                                    <i class="far fa-clock me-1"></i> <?php echo date('H:i', strtotime($res['start_time'])); ?> - <?php echo date('H:i', strtotime($res['end_time'])); ?> น.
                                </div>
                            </div>
                            <div class="text-end">
                                <?php if ($res['round_status'] == 'calling'): ?>
                                    <span class="badge-status bg-warning text-dark">เรียกคิว</span>
                                <?php elseif ($res['round_status'] == 'cancelled'): ?>
                                    <span class="badge-status bg-danger-subtle text-danger">ยกเลิก</span>
                                <?php elseif ($res['round_status'] == 'finished'): ?>
                                    <span class="badge-status bg-light text-muted">เสร็จสิ้น</span>
                                <?php else: ?>
                                    <span class="badge-status bg-success-subtle text-success">จองสำเร็จ</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($res['round_status'] == 'pending'): ?>
                            <div class="mt-3 border-top pt-2 text-end">
                                <button onclick="cancelBooking(<?php echo $res['res_id']; ?>)" class="btn btn-sm text-danger border-0 small p-0">
                                    <i class="fas fa-times-circle me-1"></i> ยกเลิกการจอง
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <h6 class="text-muted">ยังไม่มีประวัติการจองกิจกรรม</h6>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // 1. ระบบแจ้งเตือนเสียงและสั่น
    function playAlert() {
        const sound = document.getElementById('notificationSound');
        if (sound) { sound.play().catch(e => {}); }
        if ("vibrate" in navigator) { navigator.vibrate([200, 100, 200]); }
    }

    <?php if ($has_calling): ?>
        setTimeout(playAlert, 1000);
    <?php endif; ?>

    // 2. รีเฟรชหน้าเว็บทุก 15 วินาที
    setTimeout(function(){ window.location.reload(); }, 15000);

    // 3. ฟังก์ชันยกเลิกการจอง
    function cancelBooking(id) {
        Swal.fire({
            title: 'ต้องการยกเลิกใช่ไหม?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ยืนยันยกเลิก',
            cancelButtonText: 'ปิด'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete_reservation.php?id=' + id;
            }
        });
    }

    // 4. แก้ไขบั๊ก SweetAlert เด้งซ้ำ: เช็คพารามิเตอร์และล้าง URL ทันที
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');

    if (status === 'success' || status === 'cancelled') {
        let title = (status === 'success') ? 'จองสำเร็จ!' : 'ยกเลิกสำเร็จ';
        let icon = (status === 'success') ? 'success' : 'info';

        Swal.fire({ 
            title: title, 
            icon: icon, 
            confirmButtonColor: '#3a8173',
            timer: 3000,
            timerProgressBar: true
        }).then(() => {
            // ล้างค่า status ออกจาก URL ทันทีหลังจากกด OK หรือหมดเวลา
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        });
    }
</script>
</body>
</html>