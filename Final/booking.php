<?php
include 'config.php';
$event_id = $_GET['event_id'] ?? null;

// ดึงข้อมูลงาน
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

// ดึงประเภทบูธในงานนี้
$stmt_booths = $conn->prepare("SELECT * FROM booth_types WHERE event_id = ?");
$stmt_booths->execute([$event_id]);
$booth_types = $stmt_booths->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เลือกประเภทบูธ - <?php echo htmlspecialchars($event['event_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; }
        .booth-selector { border: 2px solid #eee; border-radius: 12px; padding: 20px; transition: 0.3s; cursor: pointer; }
        .booth-selector:hover { border-color: var(--su-green); background: #f0f7f5; }
        .price-tag { color: var(--su-green); font-size: 1.5rem; font-weight: bold; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark shadow-sm mb-4" style="background: var(--su-green);">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-chevron-left me-2"></i> กลับหน้าหลัก</a>
    </div>
</nav>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold"><?php echo htmlspecialchars($event['event_name']); ?></h2>
            <p class="text-muted"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($event['location']); ?></p>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4">กรุณาเลือกประเภทบูธที่คุณต้องการ</h5>
            
            <form action="confirm_booking.php" method="POST">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                
                <div class="row g-3">
                    <?php foreach ($booth_types as $type): ?>
                    <div class="col-md-6">
                        <label class="w-100">
                            <input type="radio" name="type_id" value="<?php echo $type['id']; ?>" class="d-none" required>
                            <div class="booth-selector">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($type['type_name']); ?></h5>
                                        <span class="text-muted small">ว่าง <?php echo $type['total_slots']; ?> บูธ</span>
                                    </div>
                                    <div class="price-tag text-end">
                                        <?php echo $type['price'] > 0 ? number_format($type['price']) . " ฿" : "ฟรี"; ?>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-5 border-top pt-4">
                    <h5 class="fw-bold mb-3">ข้อมูลผู้จอง</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="customer_name" class="form-control" placeholder="ชื่อ-นามสกุล" required>
                        </div>
                        <div class="col-md-6">
                            <input type="tel" name="customer_phone" class="form-control" placeholder="เบอร์โทรศัพท์" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100 mt-4 shadow" style="background: var(--su-green);">ยืนยันการจองบูธ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ทำให้เวลาคลิก Card แล้ววิทยุถูกเลือกและเปลี่ยนสี
    document.querySelectorAll('.booth-selector').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.booth-selector').forEach(i => i.style.borderColor = '#eee');
            document.querySelectorAll('.booth-selector').forEach(i => i.style.background = 'white');
            this.style.borderColor = '#3a8173';
            this.style.background = '#f0f7f5';
        });
    });
</script>

</body>
</html>