<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT); // เข้ารหัสผ่าน
    $shop = $_POST['shop_name'];
    $owner = $_POST['owner_name'];
    $phone = $_POST['phone'];

    try {
        $stmt = $conn->prepare("INSERT INTO booth_owners (username, password, shop_name, owner_name, phone, status) 
                                VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$user, $pass, $shop, $owner, $phone]);

        echo "<script>
                alert('ลงทะเบียนสำเร็จ! กรุณารอผู้ดูแลระบบอนุมัติการใช้งาน');
                window.location.href = 'user_login.php';
              </script>";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // กรณีชื่อผู้ใช้ซ้ำ
            echo "<script>alert('Username นี้ถูกใช้ไปแล้ว'); history.back();</script>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>