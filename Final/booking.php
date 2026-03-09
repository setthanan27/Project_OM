<?php
session_start();
if (!isset($_SESSION['owner_id'])) { header("Location: index.php"); exit; }
include 'config.php';

$event_id = $_GET['event_id'] ?? null;
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

$stmt_booths = $conn->prepare("SELECT * FROM booth_types WHERE event_id = ?");
$stmt_booths->execute([$event_id]);
$booth_types = $stmt_booths->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จองบูธ - <?php echo htmlspecialchars($event['event_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; }
        .booth-card { border: 2px solid #eee; border-radius: 15px; cursor: pointer; transition: 0.3s; position: relative; }
        .booth-card:hover { border-color: var(--su-green); }
        .booth-radio:checked + .booth-card { border-color: var(--su-green); background-color: #f0f7f5; }
        .price-text { color: var(--su-green); font-size: 1.25rem; font-weight: bold; }
        #payment-section { display: none; background: #fff; border-radius: 15px; padding: 20px; border: 1px dashed var(--su-green); }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h2 class="fw-bold mb-4 text-dark"><i class="fas fa-store-alt me-2"></i>เลือกจองบูธ: <?php echo htmlspecialchars($event['event_name']); ?></h2>
            
            <form action="confirm_booking.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                
                <div class="row g-3 mb-4">
                    <?php foreach ($booth_types as $type): 
                        // คำนวณจำนวนคงเหลือแบบ Real-time
                        $stmt_count = $conn->prepare("SELECT COUNT(*) FROM event_bookings WHERE type_id = ? AND booking_status != 'cancelled'");
                        $stmt_count->execute([$type['id']]);
                        $booked = $stmt_count->fetchColumn();
                        $remaining = $type['total_slots'] - $booked;
                        $is_full = ($remaining <= 0);
                    ?>
                    <div class="col-md-6">
                        <input type="radio" name="type_id" id="type_<?php echo $type['id']; ?>" value="<?php echo $type['id']; ?>" 
                               class="d-none booth-radio" data-price="<?php echo $type['price']; ?>" 
                               <?php echo $is_full ? 'disabled' : ''; ?> required>
                        <label for="type_<?php echo $type['id']; ?>" class="w-100">
                            <div class="booth-card p-3 <?php echo $is_full ? 'opacity-50' : ''; ?>">
                                <div class="d-flex justify-content-between">
                                    <h5 class="fw-bold"><?php echo htmlspecialchars($type['type_name']); ?></h5>
                                    <span class="price-text"><?php echo $type['price'] > 0 ? number_format($type['price'])." ฿" : "ฟรี"; ?></span>
                                </div>
                                <div class="mt-2 small">
                                    <?php if($is_full): ?>
                                        <span class="text-danger fw-bold"><i class="fas fa-times-circle"></i> บูธเต็มแล้ว</span>
                                    <?php else: ?>
                                        <span class="text-muted">คงเหลือ <b class="text-dark"><?php echo $remaining; ?></b> / <?php echo $type['total_slots']; ?> บูธ</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold mb-3">ยืนยันข้อมูลผู้จอง</h5>
                    <div class="row g-3 text-muted">
                        <div class="col-md-6">ชื่อร้านค้า: <b><?php echo $_SESSION['shop_name']; ?></b></div>
                        <div class="col-md-6">ผู้ติดต่อ: <b><?php echo $_SESSION['owner_name']; ?></b></div>
                    
                    </div>
                </div>

                <div id="payment-section" class="mb-4 text-center">
                    <h5 class="fw-bold text-dark mb-3">ชำระเงินค่าจองบูธ</h5>
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=0853184074" alt="QR Code" class="img-fluid rounded mb-2">
                            <p class="small text-muted">สแกนเพื่อจ่ายเงินผ่านพร้อมเพย์</p>
                        </div>
                        <div class="col-md-7 text-start">
                            <p class="mb-2 fw-bold">ขั้นตอนการชำระเงิน:</p>
                            <ol class="small text-muted">
                                <li>สแกน QR Code เพื่อชำระเงินตามยอดที่ระบุ</li>
                                <li>แคปหน้าจอหลักฐานการโอน (สลิป)</li>
                                <li>แนบรูปสลิปที่ช่องด้านล่างนี้</li>
                            </ol>
                            <label class="form-label fw-bold">แนบรูปสลิปหลักฐานการโอน</label>
                            <input type="file" name="payment_slip" id="slip_input" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg shadow" style="background: var(--su-green); border-radius: 12px;">
                        ยืนยันการจองบูธ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ดักจับการเลือกบูธเพื่อโชว์/ซ่อนหน้าชำระเงิน
    const radios = document.querySelectorAll('.booth-radio');
    const paymentSection = document.getElementById('payment-section');
    const slipInput = document.getElementById('slip_input');

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            const price = parseFloat(this.getAttribute('data-price'));
            if (price > 0) {
                paymentSection.style.display = 'block';
                slipInput.required = true;
            } else {
                paymentSection.style.display = 'none';
                slipInput.required = false;
            }
        });
    });
</script>

</body>
</html>