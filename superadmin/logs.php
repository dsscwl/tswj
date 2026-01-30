<?php
require_once 'config.php';
check_superadmin_login();

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// 搜索条件
$admin_name = isset($_GET['admin_name']) ? safe_input($_GET['admin_name']) : '';
$action = isset($_GET['action']) ? safe_input($_GET['action']) : '';
$start_date = isset($_GET['start_date']) ? safe_input($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? safe_input($_GET['end_date']) : '';

// 构建查询
$conditions = [];
$params = [];

if (!empty($admin_name)) {
    $conditions[] = "admin_name LIKE ?";
    $params[] = "%{$admin_name}%";
}

if (!empty($action)) {
    $conditions[] = "action LIKE ?";
    $params[] = "%{$action}%";
}

if (!empty($start_date)) {
    $conditions[] = "DATE(create_time) >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $conditions[] = "DATE(create_time) <= ?";
    $params[] = $end_date;
}

$where_sql = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// 获取日志总数
$count_sql = "SELECT COUNT(*) FROM superadmin_logs $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetchColumn();
$total_pages = ceil($total_count / $per_page);

// 获取日志列表
$sql = "SELECT * FROM superadmin_logs $where_sql ORDER BY id DESC LIMIT $offset, $per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// 清理旧日志（保留30天）
$clean_date = date('Y-m-d', strtotime('-30 days'));
$clean_sql = "DELETE FROM superadmin_logs WHERE create_time < ?";
$clean_stmt = $pdo->prepare($clean_sql);
$clean_stmt->execute([$clean_date]);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>操作日志 - 超级管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <nav class="navbar navbar-light navbar-custom">
            <div class="container-fluid">
                <span class="navbar-brand">操作日志</span>
                <button class="btn btn-danger" onclick="clearLogs()">
                    <i class="bi bi-trash"></i> 清理日志
                </button>
            </div>
        </nav>

        <!-- 搜索和筛选 -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">操作员</label>
                        <input type="text" name="admin_name" class="form-control" placeholder="操作员名称"
                               value="<?php echo htmlspecialchars($admin_name); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">操作类型</label>
                        <input type="text" name="action" class="form-control" placeholder="操作类型"
                               value="<?php echo htmlspecialchars($action); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">开始日期</label>
                        <input type="date" name="start_date" class="form-control"
                               value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">结束日期</label>
                        <input type="date" name="end_date" class="form-control"
                               value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> 搜索
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 日志列表 -->
        <div class="card">
            <div class="card-header card-header-custom">
                <h5 class="mb-0">操作日志 (共 <?php echo $total_count; ?> 条记录)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>操作员</th>
                                <th>操作类型</th>
                                <th>操作详情</th>
                                <th>IP地址</th>
                                <th>操作时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="bi bi-clock-history display-4 text-muted"></i>
                                    <p class="mt-3 text-muted">暂无日志数据</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['admin_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">ID: <?php echo $log['admin_id']; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        switch(strtolower($log['action'])) {
                                            case '登录系统': echo 'success'; break;
                                            case '登录失败': echo 'danger'; break;
                                            case '退出系统': echo 'secondary'; break;
                                            case '添加': echo 'info'; break;
                                            case '更新': echo 'warning'; break;
                                            case '删除': echo 'danger'; break;
                                            default: echo 'primary';
                                        }
                                    ?>">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo nl2br(htmlspecialchars($log['details'])); ?>
                                </td>
                                <td>
                                    <code><?php echo $log['ip']; ?></code>
                                </td>
                                <td>
                                    <?php echo date('Y-m-d H:i:s', strtotime($log['create_time'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 分页 -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php 
                        $query_params = $_GET;
                        unset($query_params['page']);
                        $base_url = '?' . http_build_query($query_params);
                        
                        if ($page > 1): 
                        ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $base_url . '&page=' . ($page - 1); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo $base_url . '&page=' . $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $base_url . '&page=' . ($page + 1); ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearLogs() {
            if (!confirm('确定要清理所有日志吗？\n此操作将删除所有日志记录，无法恢复！')) {
                return;
            }
            
            fetch('ajax/clear_logs.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('日志清理成功！');
                    window.location.reload();
                } else {
                    alert('清理失败：' + data.message);
                }
            })
            .catch(error => {
                alert('请求失败');
            });
        }
    </script>
</body>
</html>