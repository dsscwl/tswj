<?php
require_once 'config.php';
check_superadmin_login();

// 只有超级管理员可以访问
$admin_info = get_superadmin_info();
if ($admin_info['role'] !== 'superadmin') {
    header('Location: index.php');
    exit();
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 获取管理员总数
$count_sql = "SELECT COUNT(*) FROM super_admins";
$total_count = $pdo->query($count_sql)->fetchColumn();
$total_pages = ceil($total_count / $per_page);

// 获取管理员列表
$sql = "SELECT * FROM super_admins ORDER BY role DESC, id DESC LIMIT $offset, $per_page";
$admins = $pdo->query($sql)->fetchAll();

// 处理删除
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // 不能删除自己
    if ($id == $_SESSION['superadmin_id']) {
        $_SESSION['error_message'] = '不能删除自己的账号';
    } else {
        $delete_sql = "DELETE FROM super_admins WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$id]);
        
        log_action('删除管理员', "删除管理员ID: {$id}");
        $_SESSION['success_message'] = '管理员删除成功';
    }
    
    header('Location: superadmins.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员管理 - 超级管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <nav class="navbar navbar-light navbar-custom">
            <div class="container-fluid">
                <span class="navbar-brand">管理员管理</span>
                <a href="superadmin_edit.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> 添加管理员
                </a>
            </div>
        </nav>

        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>

        <div class="card">
            <div class="card-header card-header-custom">
                <h5 class="mb-0">管理员列表 (共 <?php echo $total_count; ?> 个管理员)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>真实姓名</th>
                                <th>角色</th>
                                <th>邮箱</th>
                                <th>状态</th>
                                <th>最后登录</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                    <?php if ($admin['role'] == 'superadmin'): ?>
                                    <span class="badge bg-danger ms-2">超级管理员</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($admin['realname']); ?></td>
                                <td>
                                    <?php echo $admin['role'] == 'superadmin' ? '超级管理员' : '管理员'; ?>
                                </td>
                                <td><?php echo $admin['email']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $admin['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $admin['status'] == 'active' ? '启用' : '停用'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : '从未登录'; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($admin['create_time'])); ?></td>
                                <td>
                                    <a href="superadmin_edit.php?id=<?php echo $admin['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($admin['id'] != $_SESSION['superadmin_id']): ?>
                                    <a href="superadmins.php?delete=<?php echo $admin['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('确定要删除管理员【<?php echo addslashes($admin['username']); ?>】吗？')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <button class="btn btn-sm btn-info" onclick="resetPassword(<?php echo $admin['id']; ?>, '<?php echo addslashes($admin['username']); ?>')">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <?php endif; ?>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">
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
            if (!confirm('确定要重置管理员【' + username + '】的密码吗？\n新密码将设置为：123456')) {
                return;
            }
            
            fetch('ajax/reset_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=' + userId + '&password=123456&type=superadmin'
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