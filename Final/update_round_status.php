<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['owner_id'])) exit;

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;
$booking_id = $_GET['booking_id'] ?? null;

// ถ้ามีการส่ง Form (หลังจากกรอกโน้ตแล้ว)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $note = $_POST['completion_note'] ?? '';
    $status = ($action == 'finish') ? 'finished' : 'cancelled';

    // อัปเดตสถานะและบันทึกข้อความ (ไม่ลบข้อมูลทิ้ง เพื่อเก็บสถิติจำนวนคน)
    $stmt = $conn->prepare("UPDATE booth_activities SET status = ?, completion_note = ? WHERE id = ?");
    $stmt->execute([$status, $note, $id]);

    header("Location: manage_activity.php?id=" . $booking_id);
    exit;
}

// สำหรับการกด 'call' (เรียกคิว) ให้เปลี่ยนสถานะทันทีไม่ต้องกรอกโน้ต
if ($id && $action == 'call') {
    $stmt = $conn->prepare("UPDATE booth_activities SET status = 'calling' WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_activity.php?id=" . $booking_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Sarabun', sans-serif; }
        .card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn-su { background: #3a8173; color: white; border-radius: 12px; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-light d-inline-flex p-3 mb-3">
                        <i class="fas <?php echo ($action == 'finish') ? 'fa-flag-checkered text-primary' : 'fa-times-circle text-danger'; ?> fa-3x"></i>
                    </div>
                    <h4 class="fw-bold">
                        <?php echo ($action == 'finish') ? 'ยืนยันการจบกิจกรรม' : 'ยืนยันการยกเลิกรอบ'; ?>
                    </h4>
                    <p class="text-muted small">บันทึกข้อความสั้นๆ เพื่อแจ้งให้ผู้เข้าร่วมทราบ</p>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ข้อความบันทึก / หมายเหตุ</label>
                        <textarea name="completion_note" class="form-control border-0 bg-light" rows="3" 
                            placeholder="<?php echo ($action == 'finish') ? 'เช่น ขอบคุณทุกท่านที่มาร่วมสนุกครับ' : 'เช่น ขออภัย อุปกรณ์ขัดข้องจำเป็นต้องยกเลิกรอบนี้'; ?>"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="manage_activity.php?id=<?php echo $booking_id; ?>" class="btn btn-light w-100 py-2 fw-bold">ย้อนกลับ</a>
                        <button type="submit" class="btn <?php echo ($action == 'finish') ? 'btn-primary' : 'btn-danger'; ?> w-100 py-2 fw-bold">
                            บันทึกข้อมูล
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>