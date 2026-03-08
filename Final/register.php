<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ลงทะเบียนเจ้าของบูธ | EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --su-green: #3a8173; }
        body { background: #f4f7f6; display: flex; align-items: center; min-height: 100vh; padding: 40px 0; }
        .reg-card { max-width: 500px; margin: auto; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn-reg { background: var(--su-green); color: white; border: none; padding: 12px; border-radius: 8px; width: 100%; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="card reg-card p-4">
        <div class="text-center mb-4">
            <h3 class="fw-bold" style="color: var(--su-green);">ลงทะเบียนเจ้าของบูธ</h3>
            <p class="text-muted">สมัครสมาชิกเพื่อขอสิทธิ์ในการจองบูธ</p>
        </div>
        <form action="process_register.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <hr>
            <div class="mb-3">
                <label class="form-label small fw-bold">ชื่อร้านค้า / บริษัท</label>
                <input type="text" name="shop_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">ชื่อ-นามสกุล ผู้ติดต่อ</label>
                <input type="text" name="owner_name" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">เบอร์โทรศัพท์</label>
                <input type="tel" name="phone" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-reg shadow-sm">ส่งคำขอลงทะเบียน</button>
            <div class="text-center mt-3 small">
                มีบัญชีอยู่แล้ว? <a href="user_login.php" style="color: var(--su-green);">เข้าสู่ระบบที่นี่</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>