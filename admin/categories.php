<?php
require_once '../config.php';
require_once 'get_config.php';
check_admin_login();

// 获取分类管理标题
$page_title = get_config('category_title', '分类管理');

// 处理添加分类
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = safe_input($_POST['name']);
        $description = safe_input($_POST['description']);
        $status = $_POST['status'];
        $sort_order = intval($_POST['sort_order']);
        
        $sql = "INSERT INTO categories (name, description, status, sort_order, create_time) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $description, $status, $sort_order]);
        
        $_SESSION['success_message'] = '分类添加成功';
        header('Location: categories.php');
        exit;
    } elseif ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $name = safe_input($_POST['name']);
        $description = safe_input($_POST['description']);
        $status = $_POST['status'];
        $sort_order = intval($_POST['sort_order']);
        
        $sql = "UPDATE categories SET name = ?, description = ?, status = ?, sort_order = ?, update_time = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $description, $status, $sort_order, $id]);
        
        $_SESSION['success_message'] = '分类更新成功';
        header('Location: categories.php');
        exit;
    } elseif ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        
        // 检查是否有投诉关联
        $check_sql = "SELECT COUNT(*) FROM complaints WHERE category_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$id]);
        $count = $check_stmt->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error_message'] = '该分类下有投诉记录，无法删除';
        } else {
            $sql = "DELETE FROM categories WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $_SESSION['success_message'] = '分类删除成功';
        }
        header('Location: categories.php');
        exit;
    }
}

// 获取所有分类
$sql = "SELECT * FROM categories ORDER BY sort_order ASC, id DESC";
$categories = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo get_config('site_title', '投诉建议管理系统'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .editable { cursor: pointer; }
        .editable:hover { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php"><?php echo get_config('site_title', '投诉建议管理系统'); ?></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">投诉建议</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="stores.php"><?php echo get_config('store_title', '门店管理'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="categories.php"><?php echo $page_title; ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="custom1.php"><?php echo get_config('custom1_title', '自定义管理1'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="custom2.php"><?php echo get_config('custom2_title', '自定义管理2'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="config.php">系统配置</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo $page_title; ?></h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus-lg"></i> 添加分类
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>分类名称</th>
                                        <th>描述</th>
                                        <th>排序</th>
                                        <th>状态</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td class="editable" onclick="editCell(this, 'name', <?php echo $category['id']; ?>)">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </td>
                                        <td class="editable" onclick="editCell(this, 'description', <?php echo $category['id']; ?>)">
                                            <?php echo htmlspecialchars($category['description']); ?>
                                        </td>
                                        <td class="editable" onclick="editCell(this, 'sort_order', <?php echo $category['id']; ?>)">
                                            <?php echo $category['sort_order']; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $category['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo $category['status'] == 'active' ? '启用' : '停用'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($category['create_time'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCategoryModal" 
                                                    onclick="loadCategoryData(<?php echo $category['id']; ?>)">
                                                编辑
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                                删除
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 添加分类模态框 -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">添加分类</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">分类名称 *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">描述</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">排序</label>
                                <input type="number" name="sort_order" class="form-control" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">状态</label>
                                <select name="status" class="form-select">
                                    <option value="active">启用</option>
                                    <option value="inactive">停用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">添加</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 编辑分类模态框 -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editCategoryForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editCategoryId">
                    <div class="modal-header">
                        <h5 class="modal-title">编辑分类</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">分类名称 *</label>
                            <input type="text" name="name" id="editCategoryName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">描述</label>
                            <textarea name="description" id="editCategoryDescription" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">排序</label>
                                <input type="number" name="sort_order" id="editCategorySort" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">状态</label>
                                <select name="status" id="editCategoryStatus" class="form-select">
                                    <option value="active">启用</option>
                                    <option value="inactive">停用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 删除确认表单 -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 加载分类数据到编辑模态框
        function loadCategoryData(id) {
            fetch(`ajax/get_category.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editCategoryId').value = data.id;
                    document.getElementById('editCategoryName').value = data.name;
                    document.getElementById('editCategoryDescription').value = data.description;
                    document.getElementById('editCategorySort').value = data.sort_order;
                    document.getElementById('editCategoryStatus').value = data.status;
                });
        }
        
        // 删除分类
        function deleteCategory(id) {
            if (confirm('确定要删除这个分类吗？')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // 表格单元格编辑
        function editCell(cell, field, id) {
            const originalValue = cell.innerText;
            let input;
            
            if (field === 'sort_order') {
                input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm';
                input.value = originalValue;
            } else {
                input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = originalValue;
            }
            
            cell.innerHTML = '';
            cell.appendChild(input);
            input.focus();
            
            function saveEdit() {
                const newValue = input.value.trim();
                if (newValue !== originalValue) {
                    // 发送AJAX请求保存
                    fetch('ajax/update_category.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}&field=${field}&value=${encodeURIComponent(newValue)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            cell.innerText = newValue;
                        } else {
                            alert('更新失败：' + data.message);
                            cell.innerText = originalValue;
                        }
                    })
                    .catch(error => {
                        alert('请求失败');
                        cell.innerText = originalValue;
                    });
                } else {
                    cell.innerText = originalValue;
                }
            }
            
            input.addEventListener('blur', saveEdit);
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    saveEdit();
                }
            });
        }
    </script>
</body>
</html>