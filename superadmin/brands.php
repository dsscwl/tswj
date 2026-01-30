<?php
require_once 'config.php';
check_superadmin_login();

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 搜索条件
$search = isset($_GET['search']) ? safe_input($_GET['search']) : '';
$status = isset($_GET['status']) ? safe_input($_GET['status']) : '';

// 构建查询
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(brand_name LIKE ? OR brand_code LIKE ? OR contact_person LIKE ? OR contact_phone LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($status)) {
    $conditions[] = "status = ?";
    $params[] = $status;
}

$where_sql = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// 获取品牌总数
$count_sql = "SELECT COUNT(*) FROM brands $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetchColumn();
$total_pages = ceil($total_count / $per_page);

// 获取品牌列表
$sql = "SELECT * FROM brands $where_sql ORDER BY id DESC LIMIT $offset, $per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$brands = $stmt->fetchAll();

// 处理删除
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // 检查是否有投诉关联
    $check_sql = "SELECT COUNT(*) FROM complaints WHERE brand_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$id]);
    $complaint_count = $check_stmt->fetchColumn();
    
    if ($complaint_count > 0) {
        $_SESSION['error_message'] = '该品牌下有投诉记录，无法删除';
    } else {
        // 删除品牌用户
        $delete_users_sql = "DELETE FROM brand_users WHERE brand_id = ?";
        $delete_users_stmt = $pdo->prepare($delete_users_sql);
        $delete_users_stmt->execute([$id]);
        
        // 删除品牌
        $delete_sql = "DELETE FROM brands WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$id]);
        
        log_action('删除品牌', "删除品牌ID: {$id}");
        $_SESSION['success_message'] = '品牌删除成功';
    }
    
    header('Location: brands.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>品牌管理 - 超级管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .table-actions .btn {
            margin-right: 5px;
        }
        .search-box {
            max-width: 300px;
        }
        .brand-code {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <nav class="navbar navbar-light navbar-custom">
            <div class="container-fluid">
                <span class="navbar-brand">品牌管理</span>
                <a href="brands.php?action=add" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> 添加品牌
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

        <!-- 搜索和筛选 -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="搜索品牌名称、代码、联系人..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">所有状态</option>
                            <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>启用</option>
                            <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>停用</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> 筛选
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 品牌列表 -->
        <div class="card">
            <div class="card-header card-header-custom">
                <h5 class="mb-0">品牌列表 (共 <?php echo $total_count; ?> 个品牌)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>品牌名称</th>
                                <th>品牌代码</th>
                                <th>联系人</th>
                                <th>联系电话</th>
                                <th>状态</th>
                                <th>最大门店</th>
                                <th>最大用户</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($brands)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <p class="mt-3 text-muted">暂无品牌数据</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($brands as $brand): ?>
                            <tr>
                                <td><?php echo $brand['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($brand['brand_name']); ?></strong>
                                    <?php if (!empty($brand['contact_email'])): ?>
                                    <br>
                                    <small class="text-muted"><?php echo $brand['contact_email']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="brand-code"><?php echo $brand['brand_code']; ?></span>
                                    <br>
                                    <small class="text-muted">
                                        访问链接: <code><?php echo $site_url; ?>/?brand=<?php echo $brand['brand_code']; ?></code>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($brand['contact_person']); ?></td>
                                <td><?php echo htmlspecialchars($brand['contact_phone']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $brand['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $brand['status'] == 'active' ? '启用' : '停用'; ?>
                                    </span>
                                </td>
                                <td><?php echo $brand['max_stores']; ?></td>
                                <td><?php echo $brand['max_users']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($brand['create_time'])); ?></td>
                                <td class="table-actions">
                                    <a href="brand_edit.php?id=<?php echo $brand['id']; ?>" class="btn btn-sm btn-warning" 
                                       data-bs-toggle="tooltip" title="编辑">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="brand_users.php?brand_id=<?php echo $brand['id']; ?>" class="btn btn-sm btn-info"
                                       data-bs-toggle="tooltip" title="用户管理">
                                        <i class="bi bi-people"></i>
                                    </a>
                                    <a href="brands.php?delete=<?php echo $brand['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('确定要删除品牌【<?php echo addslashes($brand['brand_name']); ?>】吗？\n\n警告：删除后该品牌的所有数据将无法恢复！')"
                                       data-bs-toggle="tooltip" title="删除">
                                        <i class="bi bi-trash"></i>
                                    </a>
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
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                            if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
                        } ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
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
        // 初始化工具提示
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // 复制品牌链接
        function copyBrandLink(code) {
            var link = '<?php echo $site_url; ?>/?brand=' + code;
            navigator.clipboard.writeText(link).then(function() {
                alert('链接已复制到剪贴板：' + link);
            });
        }
    </script>
</body>
</html>