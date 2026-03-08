<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
include 'config.php';

$id = $_GET['id'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['type_name'];
    $price = $_POST['price'];
    $slots = $_POST['total_slots'];

    $stmt = $conn->prepare("UPDATE booth_types SET type_name = ?, price = ?, total_slots = ? WHERE id = ?");
    $stmt->execute([$name, $price, $slots, $id]);
    
    // ดึง event_id เพื่อกลับไปหน้าเดิม
    $stmt_ev = $conn->prepare("SELECT event_id FROM booth_types WHERE id = ?");
    $stmt_ev->execute([$id]);
    $ev_id = $stmt_ev->fetchColumn();
    
    header("Location: booth_management.php?id=$ev_id");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM booth_types WHERE id = ?");
$stmt->execute([$id]);
$booth = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขประเภทบูธ | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --su-green: #3a8173; }
        body { background-color: #f0f2f5; font-family: 'Sarabun', sans-serif; padding-top: 50px; }
        .card { border: none; border-radius: 15px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">แก้ไขประเภทบูธ</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ชื่อประเภทบูธ</label>
                            <input type="text" name="type_name" class="form-control" value="<?php echo htmlspecialchars($booth['type_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">ราคา (฿)</label>
                            <input type="number" name="price" class="form-control" value="<?php echo $booth['price']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">จำนวนบูธทั้งหมด</label>
                            <input type="number" name="total_slots" class="form-control" value="<?php echo $booth['total_slots']; ?>" required>
                        </div>
                        <div class="text-end mt-4">
                            <button type="button" onclick="history.back()" class="btn btn-light me-2">กลับ</button>
                            <button type="submit" class="btn btn-success px-4" style="background: #3a8173; border: none;">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>