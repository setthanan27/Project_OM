<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบด้วย Google</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
    <div class="container text-center">
        <div class="card shadow-sm p-5 mx-auto" style="max-width: 400px; border-radius: 20px;">
            <h4 class="fw-bold mb-4">เข้าสู่ระบบเพื่อจองกิจกรรม</h4>
            
            <div id="g_id_onload"
                 data-client_id="744547307609-9n8bsckm7f33vd0hbspnn6mfp1k9c614.apps.googleusercontent.com"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleCredentialResponse"
                 data-auto_prompt="false">
            </div>

            <div class="g_id_signin"
                 data-type="standard"
                 data-shape="pill"
                 data-theme="outline"
                 data-text="signin_with"
                 data-size="large"
                 data-logo_alignment="left">
            </div>

            <a href="javascript:history.back()" class="btn btn-link mt-3 text-muted">ย้อนกลับ</a>
        </div>
    </div>

    <script>
    // ฟังก์ชันรับข้อมูลหลังจากเลือกบัญชี Google แล้ว
    function handleCredentialResponse(response) {
    // ถอดรหัส Base64 ของ JWT Token เพื่อเอาข้อมูลโปรไฟล์ (ทำแบบง่ายเพื่อทดสอบ)
    const payload = JSON.parse(atob(response.credential.split('.')[1]));
    
    const body = { 
        id_token: response.credential,
        name: payload.name,
        picture: payload.picture,
        email: payload.email,
        sub: payload.sub
    };

    fetch('google_auth_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'activity_details.php?id=' + <?php echo $_GET['id'] ?? '0'; ?>;
        }
    });
}
    </script>
</body>
</html>