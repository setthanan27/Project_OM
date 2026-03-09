<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="fas fa-layer-group me-2"></i>SU Web Portal
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="userNavbar">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && !isset($_GET['filter']) ? 'active' : ''; ?>" href="index.php">หน้าหลัก</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'confirmed') ? 'active' : ''; ?>" href="index.php?filter=confirmed">ยืนยันแล้ว</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_bookings.php' ? 'active' : ''; ?>" href="my_bookings.php">ประวัติการจอง</a>
                </li>
                <li class="nav-item ms-lg-4 border-start ps-lg-4 text-white">
                    <div class="d-flex align-items-center">
                        <div class="text-end me-3">
                            <small class="d-block opacity-75">ร้านค้า</small>
                            <span class="fw-bold"><?php echo $_SESSION['shop_name'] ?? 'Guest'; ?></span>
                        </div>
                        <a href="logout_user.php" class="btn btn-sm btn-outline-light rounded-pill" onclick="return confirm('ยืนยันการออกจากระบบ?')">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>