<?php
require_once '../config.php';
require_once 'get_config.php';  // 新增这行
check_admin_login();

// 获取菜单配置
$menu_configs = get_menu_configs();
$store_title = $menu_configs['store_title'] ?? '门店管理';
$category_title = $menu_configs['category_title'] ?? '分类管理';
$custom1_title = $menu_configs['custom1_title'] ?? '自定义管理1';
$custom2_title = $menu_configs['custom2_title'] ?? '自定义管理2';

// 网站标题
$site_title = get_config('site_title', '投诉建议管理系统');
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .filter-card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-resolved { background: #d4edda; color: #155724; }
        .stats-card { cursor: pointer; transition: transform 0.2s; }
        .stats-card:hover { transform: translateY(-2px); }
        .modal-lg { max-width: 900px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="#"><?php echo $site_title; ?></a>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarNav">
                            <ul class="navbar-nav">
                                <li class="nav-item">
                                    <a class="nav-link active" href="index.php">投诉建议</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="stores.php"><?php echo $store_title; ?></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="categories.php"><?php echo $category_title; ?></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="custom1.php"><?php echo $custom1_title; ?></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="custom2.php"><?php echo $custom2_title; ?></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="config.php">系统配置</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
        <!-- ... 其余代码不变 ... -->
        <?php
require_once '../config.php';
check_admin_login();

// 获取筛选参数
$type = isset($_GET['type']) ? safe_input($_GET['type']) : '';
$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$status = isset($_GET['status']) ? safe_input($_GET['status']) : '';
$start_date = isset($_GET['start_date']) ? safe_input($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? safe_input($_GET['end_date']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;

// 计算分页
$offset = ($page - 1) * $per_page;

// 构建查询条件
$conditions = [];
$params = [];

if (!empty($type)) {
    $conditions[] = "type = ?";
    $params[] = $type;
}

if ($store_id > 0) {
    $conditions[] = "store_id = ?";
    $params[] = $store_id;
}

if ($category_id > 0) {
    $conditions[] = "category_id = ?";
    $params[] = $category_id;
}

if (!empty($status)) {
    $conditions[] = "status = ?";
    $params[] = $status;
}

if (!empty($start_date)) {
    $conditions[] = "create_time >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $conditions[] = "create_time <= ?";
    $params[] = $end_date;
}

$where_sql = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// 获取统计数据（用于按钮显示）
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM complaints $where_sql";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// 获取门店列表
$stores = $pdo->query("SELECT id, name FROM stores ORDER BY id")->fetchAll();

// 获取分类列表
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY id")->fetchAll();

// 获取投诉建议列表
$sql = "SELECT c.*, s.name as store_name, cat.name as category_name 
        FROM complaints c 
        LEFT JOIN stores s ON c.store_id = s.id 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        $where_sql 
        ORDER BY c.id DESC 
        LIMIT $offset, $per_page";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// 获取总数用于分页
$count_sql = "SELECT COUNT(*) as total FROM complaints $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_count / $per_page);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投诉建议管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .filter-card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-resolved { background: #d4edda; color: #155724; }
        .stats-card { cursor: pointer; transition: transform 0.2s; }
        .stats-card:hover { transform: translateY(-2px); }
        .modal-lg { max-width: 900px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="#">投诉建议管理系统</a>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarNav">
                            <ul class="navbar-nav">
                                <li class="nav-item">
                                    <a class="nav-link active" href="index.php">投诉建议</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="stores.php">门店管理</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="categories.php">分类管理</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="custom1.php">自定义管理1</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="custom2.php">自定义管理2</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
            </div>
        </div>

        <!-- 统计卡片 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card" onclick="filterByStatus('')">
                    <div class="card-body text-center">
                        <h5 class="card-title">反馈总数</h5>
                        <h2 class="text-primary"><?php echo $stats['total']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card" onclick="filterByStatus('pending')">
                    <div class="card-body text-center">
                        <h5 class="card-title">待处理</h5>
                        <h2 class="text-warning"><?php echo $stats['pending']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card" onclick="filterByStatus('processing')">
                    <div class="card-body text-center">
                        <h5 class="card-title">处理中</h5>
                        <h2 class="text-info"><?php echo $stats['processing']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card" onclick="filterByStatus('resolved')">
                    <div class="card-body text-center">
                        <h5 class="card-title">已处理</h5>
                        <h2 class="text-success"><?php echo $stats['resolved']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- 筛选表单 -->
        <div class="card filter-card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">类型</label>
                        <select name="type" class="form-select">
                            <option value="">全部类型</option>
                            <option value="complaint" <?php echo $type == 'complaint' ? 'selected' : ''; ?>>投诉</option>
                            <option value="suggestion" <?php echo $type == 'suggestion' ? 'selected' : ''; ?>>建议</option>
                            <option value="praise" <?php echo $type == 'praise' ? 'selected' : ''; ?>>表扬</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">门店</label>
                        <select name="store_id" class="form-select">
                            <option value="0">全部门店</option>
                            <?php foreach ($stores as $store): ?>
                            <option value="<?php echo $store['id']; ?>" <?php echo $store_id == $store['id'] ? 'selected' : ''; ?>>
                                <?php echo $store['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">类别</label>
                        <select name="category_id" class="form-select">
                            <option value="0">全部类别</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo $cat['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">状态</label>
                        <select name="status" class="form-select">
                            <option value="">全部状态</option>
                            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>待处理</option>
                            <option value="processing" <?php echo $status == 'processing' ? 'selected' : ''; ?>>处理中</option>
                            <option value="resolved" <?php echo $status == 'resolved' ? 'selected' : ''; ?>>已处理</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">开始时间</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">结束时间</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">每页显示</label>
                        <select name="per_page" class="form-select">
                            <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10条</option>
                            <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20条</option>
                            <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50条</option>
                            <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100条</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">筛选</button>
                        <a href="index.php" class="btn btn-secondary">重置</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- 投诉建议列表 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">投诉建议列表</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>类型</th>
                                <th>门店</th>
                                <th>类别</th>
                                <th>标题</th>
                                <th>状态</th>
                                <th>提交时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td>
                                    <?php 
                                    $type_names = [
                                        'complaint' => '投诉',
                                        'suggestion' => '建议',
                                        'praise' => '表扬'
                                    ];
                                    echo $type_names[$item['type']] ?? $item['type'];
                                    ?>
                                </td>
                                <td><?php echo $item['store_name'] ?? '未指定'; ?></td>
                                <td><?php echo $item['category_name'] ?? '未分类'; ?></td>
                                <td><?php echo mb_substr($item['title'], 0, 20); ?>...</td>
                                <td>
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php 
                                        $status_names = [
                                            'pending' => '待处理',
                                            'processing' => '处理中',
                                            'resolved' => '已处理'
                                        ];
                                        echo $status_names[$item['status']] ?? $item['status'];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($item['create_time'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewDetail(<?php echo $item['id']; ?>)">
                                        <i class="bi bi-eye"></i> 查看
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 分页 -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">上一页</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">下一页</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 详情弹窗 -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">投诉建议详情</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- 内容通过AJAX加载 -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterByStatus(status) {
            const url = new URL(window.location.href);
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }
            window.location.href = url.toString();
        }

        function viewDetail(id) {
            fetch(`detail_ajax.php?id=${id}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detailContent').innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('detailContent').innerHTML = '<div class="alert alert-danger">加载失败</div>';
                    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                    modal.show();
                });
        }
    </script>
</body>
</html>