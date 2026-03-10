<?php
session_start();
include 'config.php';

// ตรวจสอบการล็อกอิน Google
if (!isset($_SESSION['user_id'])) {
    header("Location: google_login_page.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'ผู้ใช้งาน';
$user_picture = $_SESSION['user_picture'] ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

// ดึงข้อมูลการจองกิจกรรมรวมจากทุกบูธที่ผู้ใช้เคยสแกนจองไว้
$sql = "SELECT r.*, o.shop_name, e.event_name, a.status as round_status, a.start_time, a.end_time 
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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ศูนย์รวมกิจกรรมของฉัน | EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --su-warning: #ffc107; }
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; }
        
        /* สไตล์สำหรับการแจ้งเตือนเรียกคิว */
        .notification-area { margin-bottom: 30px; }
        .alert-calling { 
            background: white; border-left: 5px solid var(--su-warning); 
            border-radius: 15px; animation: pulse-border 2s infinite; 
        }
        @keyframes pulse-border {
            0% { box-shadow: 0 0 0 rgba(255, 193, 7, 0.4); }
            70% { box-shadow: 0 0 20px rgba(255, 193, 7, 0.6); }
            100% { box-shadow: 0 0 0 rgba(255, 193, 7, 0); }
        }

        .profile-card { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .profile-img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; }
        .history-card { border: none; border-radius: 15px; background: white; transition: 0.3s; }
        .badge-status { padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="profile-card d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <img src="<?php echo $user_picture; ?>" class="profile-img me-3" alt="Profile">
            <div>
                <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($user_name); ?></h5>
                <small class="text-muted">รายการกิจกรรมที่คุณเข้าร่วมทั้งหมด</small>
            </div>
        </div>
        <a href="javascript:history.back()" class="btn btn-light rounded-pill px-4 shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> ย้อนกลับ
        </a>
    </div>

    <div class="notification-area">
        <?php 
        $has_calling = false;
        foreach ($my_bookings as $notif) {
            if ($notif['round_status'] == 'calling') {
                $has_calling = true;
                echo '
                <div class="alert alert-calling shadow-sm p-4 d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning rounded-circle p-3 me-3">
                            <i class="fas fa-bullhorn fa-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-dark">ถึงคิวของคุณแล้ว!</h5>
                            <p class="mb-0 text-muted small">ร้าน <strong>'.htmlspecialchars($notif['shop_name']).'</strong> กำลังเรียกคุณเข้าร่วมกิจกรรม</p>
                        </div>
                    </div>
                    <span class="badge bg-warning text-dark rounded-pill px-3">ไปที่บูธทันที</span>
                </div>';
            }
        }
        ?>
    </div>

    <h5 class="fw-bold mb-4"><i class="fas fa-tasks me-2 text-primary"></i>รายการจองกิจกรรมของคุณ</h5>
    
    <div class="row g-3">
        <?php if (count($my_bookings) > 0): ?>
            <?php foreach ($my_bookings as $res): ?>
                <div class="col-12 col-md-6">
                    <div class="card history-card shadow-sm p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($res['shop_name']); ?></h6>
                                <p class="text-muted small mb-2"><i class="fas fa-calendar-alt me-1"></i> <?php echo htmlspecialchars($res['event_name']); ?></p>
                                
                                <div class="text-primary small fw-bold">
                                    <i class="fas fa-clock me-1"></i> รอบเวลา: <?php echo date('H:i', strtotime($res['start_time'])); ?> - <?php echo date('H:i', strtotime($res['end_time'])); ?> น.
                                </div>
                            </div>
                            <div class="text-end">
                                <?php if ($res['round_status'] == 'calling'): ?>
                                    <span class="badge-status bg-warning text-dark border-warning border">กำลังเรียกคิว</span>
                                <?php elseif ($res['round_status'] == 'cancelled'): ?>
                                    <span class="badge-status bg-danger text-white border">ถูกยกเลิกแล้ว</span>
                                <?php elseif ($res['round_status'] == 'finished'): ?>
                                    <span class="badge-status bg-light text-muted border">เสร็จสิ้นแล้ว</span>
                                <?php else: ?>
                                    <span class="badge-status bg-success text-white">จองสำเร็จ</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($res['round_status'] == 'cancelled'): ?>
                            <div class="mt-2 p-2 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded small text-danger">
                                <i class="fas fa-info-circle me-1"></i> ขออภัย รอบกิจกรรมนี้มีความจำเป็นต้องยกเลิกโดยทางบูธ
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-qrcode fa-4x text-muted mb-3 opacity-25"></i>
                <h5 class="text-muted">ยังไม่พบข้อมูลการจองกิจกรรม<br><small>สแกน QR Code หน้าบูธเพื่อเริ่มจอง</small></h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    setTimeout(function(){
       window.location.reload();
    }, 15000); // รีเฟรชทุกๆ 15 วินาที
</script>

</body>
</html>