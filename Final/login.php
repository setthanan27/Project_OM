<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ - EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --su-green: #3a8173; }
        body { background: #f4f7f6; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn-login { background: var(--su-green); color: white; border: none; padding: 12px; border-radius: 8px; width: 100%; }
        .btn-login:hover { background: #2d6358; color: white; }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="card-body p-5">
        <div class="text-center mb-4">
            <h4 class="fw-bold" style="color: var(--su-green);">Admin Login</h4>
            <p class="text-muted small">ระบบจัดการการจองคิวอีเวนท์</p>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger p-2 small"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form action="check_login.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-login shadow-sm">เข้าสู่ระบบ</button>
        </form>
    </div>
</div>

</body>
</html>