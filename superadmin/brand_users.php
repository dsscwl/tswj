<?php
require_once 'config.php';
check_superadmin_login();

$brand_id = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 获取品牌信息（如果指定了品牌ID）
if ($brand_id > 0) {
    $brand_sql = "SELECT * FROM brands WHERE id = ?";
    $brand_stmt = $pdo->prepare($brand_sql);
    $brand_stmt->execute([$brand_id]);
    $brand = $brand_stmt->fetch(PDO::FETCH_ASSOC);
}

// 构建查询条件
$conditions = [];
$params = [];

if ($brand_id > 0) {
    $conditions[] = "bu.brand_id = ?";
    $params[] = $brand_id;
}

$where_sql = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// 获取用户总数
$count_sql = "SELECT COUNT(*) FROM brand_users bu $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetchColumn();
$total_pages = ceil($total_count / $per_page);

// 获取用户列表
$sql = "SELECT bu.*, b.brand_name 
        FROM brand_users bu 
        LEFT JOIN brands b ON bu.brand_id = b.id 
        $where_sql 
        ORDER BY bu.id DESC 
        LIMIT $offset, $per_page";
        
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// 处理删除
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    
    $delete_sql = "DELETE FROM brand_users WHERE id = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([$user_id]);
    
    log_action('删除品牌用户', "删除用户ID: {$user_id}");
    $_SESSION['success_message'] = '用户删除成功';
    
    header('Location: brand_users.php' . ($brand_id > 0 ? "?brand_id=$brand_id" : ''));
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>品牌用户管理 - 超级管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <nav class="navbar navbar-light navbar-custom">
            <div class="container-fluid">
                <span class="navbar-brand">
                    <?php echo $brand_id > 0 ? "{$brand['brand_name']} - 用户管理" : '品牌用户管理'; ?>
                </span>
                <div>
                    <?php if ($brand_id > 0): ?>
                    <a href="brand_users.php?action=add&brand_id=<?php echo $brand_id; ?>" class="btn btn-primary me-2">
                        <i class="bi bi-plus-lg"></i> 添加用户
                    </a>
                    <a href="brands.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> 返回品牌列表
                    </a>
                    <?php else: ?>
                    <a href="brand_users.php?action=add" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> 添加用户
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>

        <!-- 品牌筛选 -->
        <?php if ($brand_id == 0): ?>
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <select name="brand_id" class="form-select" onchange="this.form.submit()">
                            <option value="">选择品牌查看用户</option>
                            <?php 
                            $all_brands = $pdo->query("SELECT id, brand_name FROM brands ORDER BY brand_name")->fetchAll();
                            foreach ($all_brands as $b): 
                            ?>
                            <option value="<?php echo $b['id']; ?>" <?php echo $brand_id == $b['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($b['brand_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- 用户列表 -->
        <div class="card">
            <div class="card-header card-header-custom">
                <h5 class="mb-0">用户列表 (共 <?php echo $total_count; ?> 个用户)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>真实姓名</th>
                                <?php if ($brand_id == 0): ?>
                                <th>所属品牌</th>
                                <?php endif; ?>
                                <th>角色</th>
                                <th>状态</th>
                                <th>最后登录</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="<?php echo $brand_id == 0 ? 9 : 8; ?>" class="text-center py-4">
                                    <i class="bi bi-people display-4 text-muted"></i>
                                    <p class="mt-3 text-muted">暂无用户数据</p>
                                    <?php if ($brand_id > 0): ?>
                                    <a href="brand_users.php?action=add&brand_id=<?php echo $brand_id; ?>" class="btn btn-primary">
                                        <i class="bi bi-plus-lg"></i> 添加第一个用户
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <?php if ($user['role'] == 'admin'): ?>
                                    <span class="badge bg-danger ms-2">管理员</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['realname']); ?></td>
                                <?php if ($brand_id == 0): ?>
                                <td><?php echo $user['brand_name']; ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php 
                                    $role_names = [
                                        'admin' => '管理员',
                                        'manager' => '经理',
                                        'staff' => '员工'
                                    ];
                                    echo $role_names[$user['role']] ?? $user['role'];
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['status'] == 'active' ? '启用' : '停用'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : '从未登录'; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['create_time'])); ?></td>
                                <td>
                                    <a href="brand_user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="brand_users.php?delete=<?php echo $user['id']; ?><?php echo $brand_id > 0 ? "&brand_id=$brand_id" : ''; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('确定要删除用户【<?php echo addslashes($user['username']); ?>】吗？')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <button class="btn btn-sm btn-info" onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>')">
                                        <i class="bi bi-key"></i>
                                    </button>
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
        function resetPassword(userId, username) {
            if (!confirm('确定要重置用户【' + username + '】的密码吗？\n新密码将设置为：123456')) {
                return;
            }
            
            fetch('ajax/reset_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=' + userId + '&password=123456&type=brand'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('密码重置成功！新密码：123456');
                } else {
                    alert('重置失败：' + data.message);
                }
            })
            .catch(error => {
                alert('请求失败');
            });
        }
    </script>
</body>
</html>