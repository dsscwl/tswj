<?php
require_once 'config.php';
check_superadmin_login();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$brand_id = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
$is_edit = $id > 0;

// 获取用户信息（编辑时）
if ($is_edit) {
    $sql = "SELECT bu.*, b.brand_name 
            FROM brand_users bu 
            LEFT JOIN brands b ON bu.brand_id = b.id 
            WHERE bu.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error_message'] = '用户不存在';
        header('Location: brand_users.php');
        exit();
    }
    
    $brand_id = $user['brand_id'];
}

// 获取品牌列表
$brands = $pdo->query("SELECT id, brand_name FROM brands ORDER BY brand_name")->fetchAll();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = safe_input($_POST['username']);
    $realname = safe_input($_POST['realname']);
    $brand_id = intval($_POST['brand_id']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // 验证用户名唯一性
    if ($is_edit) {
        $check_sql = "SELECT COUNT(*) FROM brand_users WHERE username = ? AND id != ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$username, $id]);
    } else {
        $check_sql = "SELECT COUNT(*) FROM brand_users WHERE username = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$username]);
    }
    
    if ($check_stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = '用户名已存在';
        header('Location: ' . ($is_edit ? "brand_user_edit.php?id=$id" : 'brand_user_edit.php'));
        exit();
    }
    
    // 如果是添加或修改密码，验证密码
    if (!$is_edit || !empty($password)) {
        if (empty($password)) {
            $_SESSION['error_message'] = '请输入密码';
            header('Location: brand_user_edit.php' . ($is_edit ? "?id=$id" : ''));
            exit();
        }
        
        if ($password !== $password_confirm) {
            $_SESSION['error_message'] = '两次输入的密码不一致';
            header('Location: brand_user_edit.php' . ($is_edit ? "?id=$id" : ''));
            exit();
        }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
    }
    
    if ($is_edit) {
        // 更新用户
        if (!empty($password)) {
            $sql = "UPDATE brand_users SET 
                    username = ?, realname = ?, brand_id = ?, role = ?, status = ?, 
                    password = ?, update_time = NOW() 
                    WHERE id = ?";
            $params = [$username, $realname, $brand_id, $role, $status, $password_hash, $id];
        } else {
            $sql = "UPDATE brand_users SET 
                    username = ?, realname = ?, brand_id = ?, role = ?, status = ?, 
                    update_time = NOW() 
                    WHERE id = ?";
            $params = [$username, $realname, $brand_id, $role, $status, $id];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        log_action('更新品牌用户', "更新用户: {$username} (ID: {$id})");
        $_SESSION['success_message'] = '用户更新成功';
    } else {
        // 添加用户
        $sql = "INSERT INTO brand_users (username, password, realname, brand_id, role, status, create_time) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $password_hash, $realname, $brand_id, $role, $status]);
        
        $new_id = $pdo->lastInsertId();
        log_action('添加品牌用户', "添加用户: {$username} (ID: {$new_id})");
        $_SESSION['success_message'] = '用户添加成功';
    }
    
    header('Location: brand_users.php' . ($brand_id > 0 ? "?brand_id=$brand_id" : ''));
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? '编辑用户' : '添加用户'; ?> - 超级管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <nav class="navbar navbar-light navbar-custom">
            <div class="container-fluid">
                <span class="navbar-brand"><?php echo $is_edit ? '编辑用户' : '添加用户'; ?></span>
                <a href="brand_users.php<?php echo $brand_id > 0 ? "?brand_id=$brand_id" : ''; ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> 返回用户列表
                </a>
            </div>
        </nav>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><?php echo $is_edit ? '编辑用户信息' : '创建新用户'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">用户名 *</label>
                                <input type="text" name="username" class="form-control" required
                                       value="<?php echo $user['username'] ?? ''; ?>">
                                <small class="text-muted">用于登录的用户名</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">真实姓名</label>
                                <input type="text" name="realname" class="form-control"
                                       value="<?php echo $user['realname'] ?? ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">所属品牌 *</label>
                                <select name="brand_id" class="form-select" required <?php echo $is_edit ? 'disabled' : ''; ?>>
                                    <option value="">请选择品牌</option>
                                    <?php foreach ($brands as $b): ?>
                                    <option value="<?php echo $b['id']; ?>"
                                        <?php echo ($user['brand_id'] ?? $brand_id) == $b['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['brand_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($is_edit): ?>
                                <input type="hidden" name="brand_id" value="<?php echo $user['brand_id']; ?>">
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">角色 *</label>
                                <select name="role" class="form-select" required>
                                    <option value="admin" <?php echo ($user['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>管理员</option>
                                    <option value="manager" <?php echo ($user['role'] ?? '') == 'manager' ? 'selected' : ''; ?>>经理</option>
                                    <option value="staff" <?php echo ($user['role'] ?? 'staff') == 'staff' ? 'selected' : ''; ?>>员工</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">状态 *</label>
                                <select name="status" class="form-select" required>
                                    <option value="active" <?php echo ($user['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>启用</option>
                                    <option value="inactive" <?php echo ($user['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>停用</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <?php echo $is_edit ? '重置密码（留空不修改）' : '密码 *'; ?>
                                </label>
                                <input type="password" name="password" class="form-control" <?php echo $is_edit ? '' : 'required'; ?>>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">确认密码</label>
                                <input type="password" name="password_confirm" class="form-control" <?php echo $is_edit ? '' : 'required'; ?>>
                            </div>
                            
                            <?php if ($is_edit): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>注意：</strong>如果不需要修改密码，请将密码字段留空。
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save"></i> <?php echo $is_edit ? '保存更改' : '创建用户'; ?>
                                </button>
                                <a href="brand_users.php<?php echo $brand_id > 0 ? "?brand_id=$brand_id" : ''; ?>" class="btn btn-secondary btn-lg">取消</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>