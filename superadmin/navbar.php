<nav class="navbar navbar-light navbar-custom">
    <div class="container-fluid">
        <span class="navbar-brand"><?php echo $page_title ?? '超级管理后台'; ?></span>
        <div class="d-flex align-items-center">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" 
                   data-bs-toggle="dropdown">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 40px; height: 40px;">
                        <i class="bi bi-person"></i>
                    </div>
                    <div class="ms-2">
                        <div class="fw-bold"><?php echo $admin['realname'] ?? '管理员'; ?></div>
                        <div class="small text-muted"><?php echo $admin['role'] == 'superadmin' ? '超级管理员' : '管理员'; ?></div>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>个人资料</a></li>
                    <li><a class="dropdown-item" href="password.php"><i class="bi bi-key me-2"></i>修改密码</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>退出登录</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar-custom {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    padding: 15px 0;
}
</style>