<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
include 'config.php';

$id = $_GET['id'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['event_name'];
    $date = $_POST['event_date'];
    $loc = $_POST['location'];

    $stmt = $conn->prepare("UPDATE events SET event_name = ?, event_date = ?, location = ? WHERE id = ?");
    $stmt->execute([$name, $date, $loc, $id]);
    header("Location: booth_management.php?id=$id");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลงาน | EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --su-green: #3a8173; }
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; padding-top: 50px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .btn-save { background: var(--su-green); color: white; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">แก้ไขข้อมูลงานอีเวนท์</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ชื่ออีเวนท์</label>
                            <input type="text" name="event_name" class="form-control" value="<?php echo htmlspecialchars($event['event_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">วันที่</label>
                            <input type="date" name="event_date" class="form-control" value="<?php echo $event['event_date']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">สถานที่</label>
                            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($event['location']); ?>" required>
                        </div>
                        <div class="text-end mt-4">
                            <a href="booth_management.php?id=<?php echo $id; ?>" class="btn btn-light me-2">ยกเลิก</a>
                            <button type="submit" class="btn btn-save px-4">บันทึกการแก้ไข</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>