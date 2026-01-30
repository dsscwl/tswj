<?php
require_once 'config.php';
check_superadmin_login();

// 获取统计数据
$stats_sql = "SELECT 
    COUNT(*) as total_brands,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_brands,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_brands,
    (SELECT COUNT(*) FROM complaints) as total_complaints,
    (SELECT COUNT(*) FROM brand_users WHERE status = 'active') as active_users
    FROM brands";

$stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);

// 获取最近品牌
$recent_brands = $pdo->query("SELECT * FROM brands ORDER BY id DESC LIMIT 5")->fetchAll();

// 获取最近投诉
$recent_complaints = $pdo->query("
    SELECT c.*, b.brand_name 
    FROM complaints c 
    LEFT JOIN brands b ON c.brand_id = b.id 
    ORDER BY c.id DESC LIMIT 5
")->fetchAll();

// 获取管理员信息
$admin = get_superadmin_info();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>超级管理后台 - 首页</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --sidebar-width: 250px;
        }
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
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
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }
        .stats-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .recent-item {
            border-left: 4px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .dropdown-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
    </style>
</head>
<body>
    <!-- 侧边栏 -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="bi bi-shield-lock"></i> 超级管理</h3>
        </div>
        <div class="sidebar-menu">
            <nav class="nav flex-column">
                <a class="nav-link active" href="index.php">
                    <i class="bi bi-speedometer2"></i> 仪表盘
                </a>
                <a class="nav-link" href="brands.php">
                    <i class="bi bi-shop"></i> 品牌管理
                </a>
                <a class="nav-link" href="brand_users.php">
                    <i class="bi bi-people"></i> 用户管理
                </a>
                <a class="nav-link" href="complaints.php">
                    <i class="bi bi-chat-left-text"></i> 投诉管理
                </a>
                <a class="nav-link" href="superadmins.php">
                    <i class="bi bi-person-badge"></i> 管理员管理
                </a>
                <a class="nav-link" href="settings.php">
                    <i class="bi bi-gear"></i> 系统设置
                </a>
                <a class="nav-link" href="logs.php">
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

    <!-- 主内容区 -->
    <div class="main-content">
        <!-- 顶部导航 -->
        <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
            <div class="container-fluid">
                <span class="navbar-brand">仪表盘</span>
                <div class="d-flex align-items-center">
                    <div class="dropdown me-3">
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
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>个人资料</a></li>
                            <li><a class="dropdown-item" href="password.php"><i class="bi bi-key me-2"></i>修改密码</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>退出登录</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- 统计数据 -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">品牌总数</h6>
                                <h3 class="mb-0"><?php echo $stats['total_brands']; ?></h3>
                            </div>
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-shop"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">启用品牌</h6>
                                <h3 class="mb-0"><?php echo $stats['active_brands']; ?></h3>
                            </div>
                            <div class="stats-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">投诉总数</h6>
                                <h3 class="mb-0"><?php echo $stats['total_complaints']; ?></h3>
                            </div>
                            <div class="stats-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-chat-left-text"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">活跃用户</h6>
                                <h3 class="mb-0"><?php echo $stats['active_users']; ?></h3>
                            </div>
                            <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 最近品牌 -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-shop me-2"></i>最近添加的品牌</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_brands)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <p class="mt-3 text-muted">暂无品牌数据</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recent_brands as $brand): ?>
                        <div class="recent-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($brand['brand_name']); ?></h6>
                                    <small class="text-muted">
                                        <i class="bi bi-code me-1"></i>代码: <?php echo $brand['brand_code']; ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-person me-1"></i><?php echo $brand['contact_person']; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php echo $brand['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $brand['status'] == 'active' ? '启用' : '停用'; ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?php echo date('m-d', strtotime($brand['create_time'])); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="brands.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-right"></i> 查看所有品牌
                        </a>
                    </div>
                </div>
            </div>

            <!-- 最近投诉 -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>最近投诉建议</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_complaints)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-chat-square-text display-4 text-muted"></i>
                            <p class="mt-3 text-muted">暂无投诉数据</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recent_complaints as $complaint): ?>
                        <div class="recent-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($complaint['title']); ?></h6>
                                    <small class="text-muted">
                                        <i class="bi bi-building me-1"></i>
                                        <?php echo $complaint['brand_name'] ?? '未知品牌'; ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-person me-1"></i><?php echo $complaint['name']; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <?php
                                    $status_badge = [
                                        'pending' => ['label' => '待处理', 'class' => 'warning'],
                                        'processing' => ['label' => '处理中', 'class' => 'info'],
                                        'resolved' => ['label' => '已处理', 'class' => 'success']
                                    ];
                                    $status = $complaint['status'] ?? 'pending';
                                    ?>
                                    <span class="badge bg-<?php echo $status_badge[$status]['class'] ?? 'secondary'; ?>">
                                        <?php echo $status_badge[$status]['label'] ?? $status; ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($complaint['create_time'])); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="complaints.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-right"></i> 查看所有投诉
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 快速操作 -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>快速操作</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <a href="brands.php?action=add" class="btn btn-primary btn-lg w-100 py-3">
                                    <i class="bi bi-plus-circle display-6 mb-2"></i>
                                    <br>
                                    <span>添加品牌</span>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="brand_users.php?action=add" class="btn btn-success btn-lg w-100 py-3">
                                    <i class="bi bi-person-plus display-6 mb-2"></i>
                                    <br>
                                    <span>添加用户</span>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="complaints.php" class="btn btn-info btn-lg w-100 py-3">
                                    <i class="bi bi-eye display-6 mb-2"></i>
                                    <br>
                                    <span>查看投诉</span>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="settings.php" class="btn btn-warning btn-lg w-100 py-3">
                                    <i class="bi bi-gear display-6 mb-2"></i>
                                    <br>
                                    <span>系统设置</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>