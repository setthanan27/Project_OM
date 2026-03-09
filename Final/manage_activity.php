<?php
session_start();
include 'config.php';
$booking_id = $_GET['id'];

// เพิ่มรอบเวลาใหม่
if(isset($_POST['add_time'])) {
    $stmt = $conn->prepare("INSERT INTO booth_activities (booking_id, start_time, end_time) VALUES (?, ?, ?)");
    $stmt->execute([$booking_id, $_POST['start'], $_POST['end']]);
}

// ดึงรอบเวลาที่สร้างไว้
$stmt = $conn->prepare("SELECT * FROM booth_activities WHERE booking_id = ?");
$stmt->execute([$booking_id]);
$slots = $stmt->fetchAll();

// สร้างข้อมูลสำหรับ QR Code (เช่น URL ให้คนสแกนเพื่อลงทะเบียน)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$qr_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/register_activity.php?id=" . $booking_id;
$qr_image = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($qr_url);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการรอบกิจกรรม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <a href="index.php" class="btn btn-secondary mb-3">กลับหน้าแรก</a>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">เพิ่มรอบเวลากิจกรรม</div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-5">
                            <label>เวลาเริ่ม</label>
                            <input type="time" name="start" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label>เวลาสิ้นสุด</label>
                            <input type="time" name="end" class="form-control" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button name="add_time" class="btn btn-success w-100">เพิ่ม</button>
                        </div>
                    </form>
                </div>
            </div>

            <table class="table bg-white shadow-sm">
                <thead>
                    <tr><th>รอบที่</th><th>เวลา</th></tr>
                </thead>
                <tbody>
                    <?php foreach($slots as $i => $s): ?>
                    <tr>
                        <td><?php echo $i+1; ?></td>
                        <td><?php echo $s['start_time']; ?> - <?php echo $s['end_time']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-header">QR Code สำหรับลูกค้า</div>
                <div class="card-body">
                    <img src="<?php echo $qr_image; ?>" alt="QR Code" class="img-fluid mb-3">
                    <p class="small text-muted">ให้ลูกค้าสแกนเพื่อเข้าร่วมกิจกรรม</p>
                    <button onclick="window.print()" class="btn btn-outline-dark btn-sm">พิมพ์ QR Code</button>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>