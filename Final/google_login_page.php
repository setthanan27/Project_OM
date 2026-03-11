<?php
session_start();
include 'config.php';

// ดึง ID บูธกิจกรรมเพื่อส่งต่อไปยังหน้า Callback
$booking_id = $_GET['id'] ?? null;
if (!$booking_id) {
    die("ไม่พบรหัสกิจกรรมที่ต้องการเข้าถึง");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบเพื่อจองกิจกรรม | EventQ+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --su-green: #3a8173; }
        body { background-color: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Sarabun', sans-serif; }
        .login-card { background: white; padding: 40px; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 90%; }
        .icon-box { background: #e8f5e9; width: 80px; height: 80px; line-height: 80px; border-radius: 50%; margin: 0 auto 20px; color: var(--su-green); }
    </style>
</head>
<body>

<div class="login-card">
    <div class="icon-box">
        <i class="fas fa-calendar-check fa-2x"></i>
    </div>
    <h4 class="fw-bold mb-2">EventQ+ Login</h4>
    <p class="text-muted small mb-4">เข้าสู่ระบบด้วย Google เพื่อจองคิวกิจกรรม<br>ที่บูธนี้โดยเฉพาะ</p>

    <div id="g_id_onload"
         data-client_id="744547307609-9n8bsckm7f33vd0hbspnn6mfp1k9c614.apps.googleusercontent.com"
         data-context="signin"
         data-ux_mode="popup"
         data-callback="handleCredentialResponse"
         data-auto_prompt="false">
    </div>

    <div class="g_id_signin d-flex justify-content-center"
         data-type="standard"
         data-shape="pill"
         data-theme="outline"
         data-text="signin_with"
         data-size="large"
         data-logo_alignment="left">
    </div>

    <div class="mt-4 border-top pt-3">
        <a href="activity_details.php?id=<?php echo $booking_id; ?>" class="text-decoration-none text-muted small">
            <i class="fas fa-arrow-left me-1"></i> กลับไปยังหน้าบูธ
        </a>
    </div>
</div>

<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
    function handleCredentialResponse(response) {
        // ส่ง ID Token และ Booking ID ไปที่หน้า Backend
        const id_token = response.credential;
        window.location.href = "google_callback.php?id_token=" + id_token + "&id=<?php echo $booking_id; ?>";
    }
</script>

</body>
</html>