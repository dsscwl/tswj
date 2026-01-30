<?php
require_once '../config.php';
require_once 'get_config.php';
check_admin_login();

// 获取门店管理标题
$page_title = get_config('store_title', '门店管理');

// 处理添加门店
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = safe_input($_POST['name']);
        $address = safe_input($_POST['address']);
        $phone = safe_input($_POST['phone']);
        $manager = safe_input($_POST['manager']);
        $status = $_POST['status'];
        
        $sql = "INSERT INTO stores (name, address, phone, manager, status, create_time) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $address, $phone, $manager, $status]);
        
        $_SESSION['success_message'] = '门店添加成功';
        header('Location: stores.php');
        exit;
    } elseif ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $name = safe_input($_POST['name']);
        $address = safe_input($_POST['address']);
        $phone = safe_input($_POST['phone']);
        $manager = safe_input($_POST['manager']);
        $status = $_POST['status'];
        
        $sql = "UPDATE stores SET name = ?, address = ?, phone = ?, manager = ?, status = ?, update_time = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $address, $phone, $manager, $status, $id]);
        
        $_SESSION['success_message'] = '门店更新成功';
        header('Location: stores.php');
        exit;
    } elseif ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        
        // 检查是否有投诉关联
        $check_sql = "SELECT COUNT(*) FROM complaints WHERE store_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$id]);
        $count = $check_stmt->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error_message'] = '该门店下有投诉记录，无法删除';
        } else {
            $sql = "DELETE FROM stores WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $_SESSION['success_message'] = '门店删除成功';
        }
        header('Location: stores.php');
        exit;
    }
}

// 获取所有门店
$sql = "SELECT * FROM stores ORDER BY id DESC";
$stores = $pdo->query($sql)->fetchAll();
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
                            <a class="nav-link active" href="stores.php"><?php echo $page_title; ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php"><?php echo get_config('category_title', '分类管理'); ?></a>
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
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStoreModal">
                            <i class="bi bi-plus-lg"></i> 添加门店
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
                                        <th>门店名称</th>
                                        <th>地址</th>
                                        <th>电话</th>
                                        <th>负责人</th>
                                        <th>状态</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stores as $store): ?>
                                    <tr>
                                        <td><?php echo $store['id']; ?></td>
                                        <td class="editable" onclick="editCell(this, 'name', <?php echo $store['id']; ?>)">
                                            <?php echo htmlspecialchars($store['name']); ?>
                                        </td>
                                        <td class="editable" onclick="editCell(this, 'address', <?php echo $store['id']; ?>)">
                                            <?php echo htmlspecialchars($store['address']); ?>
                                        </td>
                                        <td class="editable" onclick="editCell(this, 'phone', <?php echo $store['id']; ?>)">
                                            <?php echo htmlspecialchars($store['phone']); ?>
                                        </td>
                                        <td class="editable" onclick="editCell(this, 'manager', <?php echo $store['id']; ?>)">
                                            <?php echo htmlspecialchars($store['manager']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $store['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo $store['status'] == 'active' ? '启用' : '停用'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($store['create_time'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editStoreModal" 
                                                    onclick="loadStoreData(<?php echo $store['id']; ?>)">
                                                编辑
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteStore(<?php echo $store['id']; ?>)">
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

    <!-- 添加门店模态框 -->
    <div class="modal fade" id="addStoreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">添加门店</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">门店名称 *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">地址</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">电话</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">负责人</label>
                            <input type="text" name="manager" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">状态</label>
                            <select name="status" class="form-select">
                                <option value="active">启用</option>
                                <option value="inactive">停用</option>
                            </select>
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

    <!-- 编辑门店模态框 -->
    <div class="modal fade" id="editStoreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editStoreForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editStoreId">
                    <div class="modal-header">
                        <h5 class="modal-title">编辑门店</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">门店名称 *</label>
                            <input type="text" name="name" id="editStoreName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">地址</label>
                            <input type="text" name="address" id="editStoreAddress" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">电话</label>
                            <input type="text" name="phone" id="editStorePhone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">负责人</label>
                            <input type="text" name="manager" id="editStoreManager" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">状态</label>
                            <select name="status" id="editStoreStatus" class="form-select">
                                <option value="active">启用</option>
                                <option value="inactive">停用</option>
                            </select>
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
        // 加载门店数据到编辑模态框
        function loadStoreData(id) {
            fetch(`ajax/get_store.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editStoreId').value = data.id;
                    document.getElementById('editStoreName').value = data.name;
                    document.getElementById('editStoreAddress').value = data.address;
                    document.getElementById('editStorePhone').value = data.phone;
                    document.getElementById('editStoreManager').value = data.manager;
                    document.getElementById('editStoreStatus').value = data.status;
                });
        }
        
        // 删除门店
        function deleteStore(id) {
            if (confirm('确定要删除这个门店吗？')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // 表格单元格编辑
        function editCell(cell, field, id) {
            const originalValue = cell.innerText;
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.value = originalValue;
            
            cell.innerHTML = '';
            cell.appendChild(input);
            input.focus();
            
            function saveEdit() {
                const newValue = input.value.trim();
                if (newValue !== originalValue) {
                    // 发送AJAX请求保存
                    fetch('ajax/update_store.php', {
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