<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: admin_panel.php"); exit; }

// --- 1. ดึงข้อมูลเดิมจากฐานข้อมูลมาแสดง ---
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) { die("ไม่พบข้อมูลงานอีเวนท์"); }

// --- 2. ส่วนการบันทึกข้อมูลเมื่อกดปุ่ม (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = $_POST['event_name'];
    $event_date = $_POST['event_date'];
    $location = $_POST['location'];
    $event_detail = $_POST['event_detail'];
    $contact_phone = $_POST['contact_phone'];
    $instructions = $_POST['instructions'];
    $facilities = $_POST['facilities'];

    try {
        $sql = "UPDATE events SET 
                event_name = ?, 
                event_date = ?, 
                location = ?, 
                event_detail = ?, 
                contact_phone = ?, 
                instructions = ?, 
                facilities = ? 
                WHERE id = ?";
        $stmt_update = $conn->prepare($sql);
        $stmt_update->execute([
            $event_name, 
            $event_date, 
            $location, 
            $event_detail, 
            $contact_phone, 
            $instructions, 
            $facilities, 
            $id
        ]);

        echo "<script>
                alert('อัปเดตข้อมูลเรียบร้อยแล้ว');
                window.location.href = 'admin_panel.php';
              </script>";
        exit;
    } catch (PDOException $e) {
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลงานอีเวนท์ | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; }
        .edit-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-label { font-weight: bold; color: #495057; }
        .btn-save { background-color: #3a8173; color: white; border-radius: 10px; padding: 10px 30px; border: none; }
        .btn-save:hover { background-color: #2d6358; color: white; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card edit-card p-4 p-md-5">
                <h3 class="fw-bold mb-4 text-dark text-center">แก้ไขข้อมูลงานอีเวนท์</h3>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">ชื่ออีเวนท์</label>
                            <input type="text" name="event_name" class="form-control" value="<?php echo htmlspecialchars($event['event_name']); ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">วันที่จัดงาน</label>
                            <input type="date" name="event_date" class="form-control" value="<?php echo $event['event_date']; ?>" required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">สถานที่จัดงาน</label>
                            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($event['location']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">เบอร์โทรติดต่อ</label>
                            <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($event['contact_phone'] ?? ''); ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">รายละเอียดอีเวนท์</label>
                            <textarea name="event_detail" class="form-control" rows="4"><?php echo htmlspecialchars($event['event_detail'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-primary">คำแนะนำสำหรับผู้เข้าร่วม</label>
                            <textarea name="instructions" class="form-control" rows="3"><?php echo htmlspecialchars($event['instructions'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-success">สิ่งอำนวยความสะดวก</label>
                            <textarea name="facilities" class="form-control" rows="3"><?php echo htmlspecialchars($event['facilities'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-12 text-center mt-5">
                            <button type="button" onclick="history.back()" class="btn btn-light px-4 me-2">ยกเลิก</button>
                            <button type="submit" class="btn btn-save shadow-sm fw-bold">บันทึกการแก้ไข</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>