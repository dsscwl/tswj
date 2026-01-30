<?php
session_start();
require_once '../config.php';

// 检查登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 只允许超级管理员管理管理员
if ($_SESSION['role'] != 'super_admin') {
    header('Location: index.php');
    exit;
}

// 处理管理员操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_admin') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $role = $_POST['role'];
        $store_id = !empty($_POST['store_id']) ? intval($_POST['store_id']) : NULL;
        
        if (!empty($username) && !empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO admin_users (username, password, role, store_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $username, $hashed_password, $role, $store_id);
            
            if ($stmt->execute()) {
                $message = "管理员添加成功！";
            } else {
                $error = "添加失败：用户名可能已存在";
            }
        }
    } elseif ($_POST['action'] == 'delete_admin') {
        $id = intval($_POST['admin_id']);
        
        // 不能删除自己
        if ($id == $_SESSION['admin_id']) {
            $error = "不能删除自己！";
        } else {
            $sql = "DELETE FROM admin_users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "管理员删除成功！";
        }
    } elseif ($_POST['action'] == 'update_admin') {
        $id = intval($_POST['admin_id']);
        $role = $_POST['role'];
        $store_id = !empty($_POST['store_id']) ? intval($_POST['store_id']) : NULL;
        $status = intval($_POST['status']);
        
        $sql = "UPDATE admin_users SET role = ?, store_id = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siii", $role, $store_id, $status, $id);
        $stmt->execute();
        $message = "管理员更新成功！";
    } elseif ($_POST['action'] == 'reset_password') {
        $id = intval($_POST['admin_id']);
        $new_password = trim($_POST['new_password']);
        
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $sql = "UPDATE admin_users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $id);
            $stmt->execute();
            $message = "密码重置成功！";
        }
    }
}

// 获取管理员列表
$result = $conn->query("
    SELECT a.*, s.store_name 
    FROM admin_users a 
    LEFT JOIN stores s ON a.store_id = s.id 
    ORDER BY a.created_at DESC
");

// 获取门店列表（用于分配门店管理员）
$stores_result = $conn->query("SELECT id, store_name FROM stores WHERE status = 1 ORDER BY store_name");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员管理 - 投诉系统</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial; background: #f5f5f5; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        .btn { padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .form-control { padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 100%; font-size: 14px; }
        .message { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .success-message { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-row .form-group { flex: 1; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-active { background: #e6f7ed; color: #27ae60; }
        .status-inactive { background: #ffeaea; color: #e74c3c; }
        .role-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .role-super { background: #3498db; color: white; }
        .role-store { background: #9b59b6; color: white; }
        .action-buttons { display: flex; gap: 5px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 20px; border-radius: 10px; width: 500px; max-width: 90%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users-cog"></i> 管理员管理</h1>
            <p>管理系统管理员和门店管理员账号</p>
            <a href="index.php" style="color: #3498db; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> 返回首页
            </a>
        </div>
        
        <?php if (isset($message)): ?>
        <div class="message success-message">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="message error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2><i class="fas fa-user-plus"></i> 添加新管理员</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_admin">
                <div class="form-row">
                    <div class="form-group">
                        <label>用户名 *</label>
                        <input type="text" name="username" class="form-control" placeholder="输入登录用户名" required>
                    </div>
                    <div class="form-group">
                        <label>密码 *</label>
                        <input type="password" name="password" class="form-control" placeholder="输入登录密码" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>角色类型 *</label>
                        <select name="role" class="form-control" required onchange="toggleStoreSelect(this.value)">
                            <option value="super_admin">总管理员（可管理所有内容）</option>
                            <option value="store_admin">门店管理员（只能管理指定门店）</option>
                        </select>
                    </div>
                    <div class="form-group" id="storeSelectGroup" style="display: none;">
                        <label>分配门店</label>
                        <select name="store_id" class="form-control">
                            <option value="">请选择门店</option>
                            <?php while ($store = $stores_result->fetch_assoc()): ?>
                            <option value="<?php echo $store['id']; ?>"><?php echo htmlspecialchars($store['store_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 添加管理员
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-user-friends"></i> 管理员列表</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>角色</th>
                        <th>所属门店</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                <input type="hidden" name="action" value="update_admin">
                                <input type="hidden" name="admin_id" value="<?php echo $row['id']; ?>">
                                <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                        </td>
                        <td>
                            <select name="role" class="form-control" style="width: 150px;">
                                <option value="super_admin" <?php echo $row['role'] == 'super_admin' ? 'selected' : ''; ?>>总管理员</option>
                                <option value="store_admin" <?php echo $row['role'] == 'store_admin' ? 'selected' : ''; ?>>门店管理员</option>
                            </select>
                        </td>
                        <td>
                            <select name="store_id" class="form-control" style="width: 150px;">
                                <option value="">无（总管理员）</option>
                                <?php 
                                $stores_result2 = $conn->query("SELECT id, store_name FROM stores WHERE status = 1 ORDER BY store_name");
                                while ($store = $stores_result2->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $store['id']; ?>" 
                                    <?php echo $row['store_id'] == $store['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($store['store_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                        <td>
                            <select name="status" class="form-control" style="width: 90px;">
                                <option value="1" <?php echo $row['status'] == 1 ? 'selected' : ''; ?>>启用</option>
                                <option value="0" <?php echo $row['status'] == 0 ? 'selected' : ''; ?>>禁用</option>
                            </select>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                        <td class="action-buttons">
                            <button type="submit" class="btn btn-primary" title="更新信息">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            </form>
                            <button onclick="showResetPasswordModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username']); ?>')" 
                                    class="btn btn-warning" title="重置密码">
                                <i class="fas fa-key"></i>
                            </button>
                            <?php if ($row['id'] != $_SESSION['admin_id']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('确定要删除这个管理员吗？')">
                                <input type="hidden" name="action" value="delete_admin">
                                <input type="hidden" name="admin_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger" title="删除管理员">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h3><i class="fas fa-info-circle"></i> 角色说明</h3>
            <ul style="color: #666; line-height: 1.6;">
                <li><strong>总管理员</strong>：可以管理所有门店、分类、管理员和投诉数据</li>
                <li><strong>门店管理员</strong>：只能查看和管理指定门店的投诉数据</li>
                <li><strong>禁用账号</strong>：被禁用的管理员将无法登录系统</li>
                <li><strong>重置密码</strong>：可以为管理员设置新密码</li>
            </ul>
        </div>
    </div>
    
    <!-- 重置密码模态框 -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <h2><i class="fas fa-key"></i> 重置密码</h2>
            <form method="POST" id="resetPasswordForm">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="admin_id" id="resetAdminId">
                
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" id="resetUsername" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>新密码 *</label>
                    <input type="password" name="new_password" class="form-control" placeholder="输入新密码" required>
                </div>
                
                <div class="form-group">
                    <label>确认新密码 *</label>
                    <input type="password" class="form-control" placeholder="再次输入新密码" required
                           oninput="checkPasswordMatch(this)">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">确认重置</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">取消</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // 显示/隐藏门店选择框
        function toggleStoreSelect(role) {
            const storeSelectGroup = document.getElementById('storeSelectGroup');
            storeSelectGroup.style.display = role === 'store_admin' ? 'block' : 'none';
        }
        
        // 显示重置密码模态框
        function showResetPasswordModal(adminId, username) {
            document.getElementById('resetAdminId').value = adminId;
            document.getElementById('resetUsername').value = username;
            document.getElementById('resetPasswordModal').style.display = 'block';
        }
        
        // 关闭模态框
        function closeModal() {
            document.getElementById('resetPasswordModal').style.display = 'none';
        }
        
        // 检查密码是否匹配
        function checkPasswordMatch(confirmInput) {
            const passwordInput = document.querySelector('input[name="new_password"]');
            if (confirmInput.value !== passwordInput.value) {
                confirmInput.setCustomValidity('两次输入的密码不一致');
            } else {
                confirmInput.setCustomValidity('');
            }
        }
        
        // 点击模态框外部关闭
        window.onclick = function(event) {
            const modal = document.getElementById('resetPasswordModal');
            if (event.target == modal) {
                closeModal();
            }
        };
        
        // 表单提交确认
        document.querySelectorAll('form').forEach(form => {
            if (form.querySelector('input[name="action"][value="delete_admin"]')) {
                form.onsubmit = function() {
                    return confirm('确定要删除这个管理员吗？此操作不可撤销！');
                };
            }
        });
    </script>
</body>
</html>