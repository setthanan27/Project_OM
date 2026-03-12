<?php
session_start();
if (!isset($_SESSION['owner_id'])) { 
    header("Location: user_login.php"); 
    exit; 
}
include 'config.php';

$event_id = $_GET['event_id'] ?? null;
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) { die("ไม่พบข้อมูลงานอีเวนท์"); }

$stmt_booths = $conn->prepare("SELECT * FROM booth_types WHERE event_id = ?");
$stmt_booths->execute([$event_id]);
$booth_types = $stmt_booths->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองบูธ - <?php echo htmlspecialchars($event['event_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; }
        body { background-color: #f4f7f6; font-family: 'Sarabun', sans-serif; }
        .booth-card { border: 2px solid #eee; border-radius: 15px; cursor: pointer; transition: 0.3s; background: white; }
        .booth-radio:checked + label .booth-card { border-color: var(--su-green); background-color: #f0f7f5; box-shadow: 0 4px 12px rgba(58, 129, 115, 0.1); }
        .price-text { color: var(--su-green); font-size: 1.25rem; font-weight: bold; }
        #payment-section { display: none; background: #fff; border-radius: 20px; padding: 25px; border: 2px dashed var(--su-green); }
        .btn-su { background: var(--su-green); color: white; border-radius: 12px; padding: 15px; font-weight: bold; border: none; transition: 0.3s; }
        .btn-su:hover { background: #2d6358; transform: translateY(-2px); }
        .custom-check {
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: 0.2s;
            border: 1px solid transparent;
        }
    .custom-check:hover { background: #eef2f1; }
    .form-check-input:checked + .form-check-label { color: var(--su-green); font-weight: bold; }
    .form-check-input:checked { background-color: var(--su-green); border-color: var(--su-green); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="mb-4">
        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="fas fa-arrow-left me-2"></i>ย้อนกลับ
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h2 class="fw-bold mb-4 text-dark"><i class="fas fa-store-alt me-2 text-success"></i>จองบูธ: <?php echo htmlspecialchars($event['event_name']); ?></h2>
            
            <form action="confirm_booking.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                
                <h5 class="fw-bold mb-3">1. เลือกประเภทพื้นที่บูธ</h5>
                <div class="row g-3 mb-4">
                    <?php foreach ($booth_types as $type): 
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
                            <div class="booth-card p-3 h-100 <?php echo $is_full ? 'opacity-50 bg-light' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($type['type_name']); ?></h5>
                                    <span class="price-text"><?php echo $type['price'] > 0 ? number_format($type['price'])." ฿" : "ฟรี"; ?></span>
                                </div>
                                <div class="mt-2 small">
                                    <?php if($is_full): ?>
                                        <span class="text-danger fw-bold"><i class="fas fa-times-circle"></i> เต็มแล้ว</span>
                                    <?php else: ?>
                                        <span class="text-muted font-monospace">คงเหลือ <?php echo $remaining; ?> / <?php echo $type['total_slots']; ?> บูธ</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold mb-1">2. หมวดหมู่สินค้า/บริการ</h5>
                    <p class="small text-muted mb-3">ระบุประเภทสินค้าของคุณ (เลือกได้มากกว่า 1 ข้อ)</p>
                    <div class="row g-2">
                        <?php 
                        $categories = ['กิจกรรมเกม', 'อาหาร', 'เครื่องดื่ม', 'ขนม/เบเกอรี่', 'เครื่องประดับ', 'ของใช้', 'อื่นๆ'];
                        foreach ($categories as $cat): 
                        ?>
                        <div class="col-6 col-md-3">
                            <div class="form-check custom-check d-flex align-items-center">
                                <input class="form-check-input mt-0 me-2" type="checkbox" name="categories[]" value="<?php echo $cat; ?>" id="cat_<?php echo $cat; ?>">
                                <label class="form-check-label small flex-grow-1 cursor-pointer" for="cat_<?php echo $cat; ?>"><?php echo $cat; ?></label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold mb-1">3. รายละเอียดกิจกรรมบูธ</h5>
                    <p class="small text-muted mb-3">ข้อมูลนี้จะแสดงผลให้ลูกค้าเห็นเมื่อสแกน QR Code</p>
                    <textarea name="activity_detail" class="form-control border-0 bg-light" rows="3" placeholder="ระบุรายละเอียด เช่น กิจกรรมนาทีทอง, สุ่มแจกรางวัล ฯลฯ"></textarea>
                </div>

                <div id="payment-section" class="mb-4 shadow-sm border-0">
                    <div class="text-center mb-4">
                        <h5 class="fw-bold text-dark mb-1">ขั้นตอนการชำระเงิน</h5>
                        <p class="small text-muted">กรุณาโอนเงินและแนบหลักฐานเพื่อรอการตรวจสอบ</p>
                    </div>
                    
                    <div class="row align-items-center g-4">
                        <div class="col-md-5 text-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=0853184074" alt="PromptPay QR" class="img-fluid rounded-4 mb-2 border p-3 bg-white shadow-sm">
                            <div class="fw-bold text-su-green mt-2">พร้อมเพย์: 085-xxx-4074</div>
                            <small class="text-muted">ชื่อบัญชี: มอส (Setthanan)</small>
                        </div>
                        <div class="col-md-7">
                            <div class="bg-light p-3 rounded-4 mb-3">
                                <ul class="small text-muted mb-0">
                                    <li>โอนเงินตามยอดราคาบูธที่ท่านเลือก</li>
                                    <li>บันทึกภาพสลิปหลักฐานการโอนเงิน</li>
                                    <li>แนบไฟล์รูปภาพสลิปที่ช่องด้านล่างนี้</li>
                                </ul>
                            </div>
                            <label class="form-label fw-bold small text-dark"><i class="fas fa-image me-1"></i> แนบรูปสลิปหลักฐาน</label>
                            <input type="file" name="payment_slip" id="slip_input" class="form-control border-0 shadow-sm" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-su btn-lg shadow">
                        <i class="fas fa-check-circle me-2"></i> ยืนยันการจองบูธ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const radios = document.querySelectorAll('.booth-radio');
    const paymentSection = document.getElementById('payment-section');
    const slipInput = document.getElementById('slip_input');

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            const price = parseFloat(this.getAttribute('data-price'));
            if (price > 0) {
                paymentSection.style.display = 'block';
                slipInput.required = true;
                // เลื่อนหน้าจอลงมาที่ส่วนชำระเงิน
                paymentSection.scrollIntoView({ behavior: 'smooth' });
            } else {
                paymentSection.style.display = 'none';
                slipInput.required = false;
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>