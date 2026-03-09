<?php
session_start();
if (!isset($_SESSION['owner_id'])) { header("Location: user_login.php"); exit; }
include 'config.php';

$booking_id = $_GET['id'] ?? null;

// ดึงข้อมูลการจอง + ข้อมูลงาน + ข้อมูลร้านค้า
$sql = "SELECT b.*, e.event_name, e.event_date, e.location as event_loc, t.type_name, t.price, o.shop_name, o.owner_name 
        FROM event_bookings b
        JOIN events e ON b.event_id = e.id
        JOIN booth_types t ON b.type_id = t.id
        JOIN booth_owners o ON b.owner_id = o.id
        WHERE b.id = ? AND b.owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$booking_id, $_SESSION['owner_id']]);
$booking = $stmt->fetch();

if (!$booking) { die("ไม่พบข้อมูลการจอง"); }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบยืนยันการจอง - EventQ+ <?php echo $booking['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eee; font-family: 'Sarabun', sans-serif; }
        .receipt-paper {
            background: white;
            width: 210mm; /* ขนาด A4 */
            min-height: 297mm;
            padding: 20mm;
            margin: 20px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .header-logo { color: #3a8173; font-weight: bold; font-size: 24px; border-bottom: 2px solid #3a8173; padding-bottom: 10px; margin-bottom: 30px; }
        .status-stamp {
            position: absolute;
            top: 50px;
            right: 50px;
            border: 3px solid;
            padding: 10px 20px;
            transform: rotate(15deg);
            font-weight: bold;
            text-transform: uppercase;
        }
        .stamp-confirmed { color: green; border-color: green; opacity: 0.7; }
        .stamp-pending { color: orange; border-color: orange; opacity: 0.7; }
        
        @media print {
            body { background: white; }
            .receipt-paper { margin: 0; box-shadow: none; width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="container no-print mt-4 text-center">
    <button onclick="window.print()" class="btn btn-primary btn-lg"><i class="fas fa-print me-2"></i> พิมพ์เอกสารใบยืนยัน</button>
    <button onclick="history.back()" class="btn btn-light btn-lg">กลับหน้าหลัก</button>
</div>

<div class="receipt-paper">
    <div class="header-logo">
        SU WEB PORTAL - EVENT BOOKING SYSTEM
    </div>

    <?php if($booking['booking_status'] == 'confirmed'): ?>
        <div class="status-stamp stamp-confirmed">CONFIRMED<br>ยืนยันแล้ว</div>
    <?php else: ?>
        <div class="status-stamp stamp-pending">PENDING<br>รอการยืนยัน</div>
    <?php endif; ?>

    <div class="row mb-5">
        <div class="col-6">
            <h6 class="text-muted small">ข้อมูลเจ้าของบูธ:</h6>
            <h5 class="fw-bold"><?php echo htmlspecialchars($booking['shop_name']); ?></h5>
            <p class="mb-1">ชื่อผู้ติดต่อ: <?php echo htmlspecialchars($booking['owner_name']); ?></p>
            <p>รหัสการจอง: #<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?></p>
        </div>
        <div class="col-6 text-end">
            <h6 class="text-muted small">วันที่ออกเอกสาร:</h6>
            <p><?php echo date('d/m/Y'); ?></p>
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>รายการ</th>
                <th class="text-center">ประเภทบูธ</th>
                <th class="text-end">ยอดชำระ</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($booking['event_name']); ?></h6>
                    <small class="text-muted">สถานที่: <?php echo htmlspecialchars($booking['event_loc']); ?></small><br>
                    <small class="text-muted">วันจัดงาน: <?php echo date('d/m/Y', strtotime($booking['event_date'])); ?></small>
                </td>
                <td class="text-center align-middle"><?php echo htmlspecialchars($booking['type_name']); ?></td>
                <td class="text-end align-middle fw-bold"><?php echo number_format($booking['price'], 2); ?> ฿</td>
            </tr>
            <tr>
                <td colspan="2" class="text-end fw-bold bg-light">รวมทั้งสิ้น</td>
                <td class="text-end fw-bold bg-light"><?php echo number_format($booking['price'], 2); ?> ฿</td>
            </tr>
        </tbody>
    </table>

    <div class="mt-5 p-3 border rounded">
        <h6 class="fw-bold"><i class="fas fa-info-circle me-1"></i> หมายเหตุ:</h6>
        <ul class="small text-muted mb-0">
            <li>กรุณานำเอกสารฉบับนี้ยื่นที่หน้างานเพื่อรับเลขตำแหน่งบูธ</li>
            <li>หากสถานะยังเป็น "รอการยืนยัน" กรุณารอเจ้าหน้าตรวจสอบสลิปภายใน 24 ชม.</li>
            <li>เอกสารฉบับนี้ใช้สำหรับยืนยันสิทธิ์การใช้พื้นที่เท่านั้น</li>
        </ul>
    </div>

    <div class="text-center mt-5 pt-5 text-muted small">
        <p>ขอบคุณที่ร่วมเป็นส่วนหนึ่งของงานเรา<br>สอบถามข้อมูลเพิ่มเติมติดต่อสำนักงานอำนวยการ</p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>