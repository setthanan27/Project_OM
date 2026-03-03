<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบเจ้าของบูธ | SU Web Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; --su-dark: #2d6358; }
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 450px; border: none; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden; }
        .login-header { background: var(--su-green); color: white; padding: 30px; text-align: center; }
        .btn-login { background: var(--su-green); color: white; border: none; padding: 12px; border-radius: 10px; width: 100%; font-weight: bold; transition: 0.3s; }
        .btn-login:hover { background: var(--su-dark); transform: translateY(-2px); }
        .form-control { border-radius: 10px; padding: 12px; border: 1px solid #ddd; }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="login-header">
        <i class="fas fa-store fa-3x mb-3"></i>
        <h4 class="mb-0 fw-bold">Booth Owner Login</h4>
        <p class="small opacity-75 mb-0">เข้าสู่ระบบสำหรับเจ้าของร้านค้า</p>
    </div>
    <div class="card-body p-4 p-md-5">
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger border-0 small mb-4">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="check_user_login.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">ชื่อผู้ใช้งาน (Username)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control border-start-0" placeholder="Username" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">รหัสผ่าน (Password)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="Password" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-login shadow-sm mb-3">เข้าสู่ระบบ</button>
            
            <div class="text-center">
                <a href="index.php" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> กลับหน้าหลัก</a>
                
            </div>
        </form>
    </div>
</div>

</body>
</html>