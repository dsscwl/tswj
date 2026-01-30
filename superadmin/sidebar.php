<?php
$admin = get_superadmin_info();
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="bi bi-shield-lock"></i> 超级管理</h3>
        <small class="text-white-50"><?php echo $admin['realname'] ?? '管理员'; ?></small>
    </div>
    <div class="sidebar-menu">
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="bi bi-speedometer2"></i> 仪表盘
            </a>
            <a class="nav-link <?php echo $current_page == 'brands.php' ? 'active' : ''; ?>" href="brands.php">
                <i class="bi bi-shop"></i> 品牌管理
            </a>
            <a class="nav-link <?php echo $current_page == 'brand_users.php' ? 'active' : ''; ?>" href="brand_users.php">
                <i class="bi bi-people"></i> 用户管理
            </a>
            <a class="nav-link <?php echo $current_page == 'complaints.php' ? 'active' : ''; ?>" href="complaints.php">
                <i class="bi bi-chat-left-text"></i> 投诉管理
            </a>
            <?php if ($admin['role'] == 'superadmin'): ?>
            <a class="nav-link <?php echo $current_page == 'superadmins.php' ? 'active' : ''; ?>" href="superadmins.php">
                <i class="bi bi-person-badge"></i> 管理员管理
            </a>
            <?php endif; ?>
            <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                <i class="bi bi-gear"></i> 系统设置
            </a>
            <a class="nav-link <?php echo $current_page == 'logs.php' ? 'active' : ''; ?>" href="logs.php">
                <i class="bi bi-clock-history"></i> 操作日志
            </a>
            <div class="mt-4">
                <a class="nav-link" href="<?php echo $site_url . $admin_path; ?>" target="_blank">
                    <i class="bi bi-arrow-right-square"></i> 前往管理后台
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> 退出登录
                </a>
            </div>
        </nav>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0;
    box-shadow: 3px 0 20px rgba(0,0,0,0.1);
    z-index: 1000;
}
.sidebar-header {
    padding: 25px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.sidebar-header h3 {
    margin: 0;
    font-size: 1.5rem;
}
.sidebar-menu {
    padding: 20px 0;
}
.sidebar-menu .nav-link {
    color: rgba(255,255,255,0.8);
    padding: 12px 25px;
    margin: 5px 0;
    border-radius: 0;
    transition: all 0.3s;
}
.sidebar-menu .nav-link:hover {
    color: white;
    background-color: rgba(255,255,255,0.1);
}
.sidebar-menu .nav-link.active {
    color: white;
    background-color: rgba(255,255,255,0.15);
    border-left: 4px solid white;
}
.sidebar-menu .nav-link i {
    width: 24px;
    text-align: center;
    margin-right: 10px;
}
</style>