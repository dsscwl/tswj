<?php
require_once 'config.php';

// 如果已登录，跳转到首页
if (isset($_SESSION['superadmin_logged_in']) && $_SESSION['superadmin_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

// 处理登录
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = safe_input($_POST['username']);
    $password = $_POST['password'];
    
    // 查询用户
    $sql = "SELECT * FROM super_admins WHERE username = ? AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password'])) {
        // 登录成功
        $_SESSION['superadmin_logged_in'] = true;
        $_SESSION['superadmin_id'] = $admin['id'];
        $_SESSION['superadmin_username'] = $admin['username'];
        $_SESSION['superadmin_realname'] = $admin['realname'];
        $_SESSION['superadmin_role'] = $admin['role'];
        
        // 更新最后登录信息
        $update_sql = "UPDATE super_admins SET last_login = NOW(), login_ip = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$_SERVER['REMOTE_ADDR'], $admin['id']]);
        
        // 记录登录日志
        log_action('登录系统', "用户 {$username} 登录成功");
        
        header('Location: index.php');
        exit();
    } else {
        $error = '用户名或密码错误';
        log_action('登录失败', "尝试登录的用户名: {$username}");
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>超级管理员登录</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            color: white;
        }
        .login-footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <h2><i class="bi bi-shield-lock"></i> 超级管理后台</h2>
                        <p class="mb-0">品牌投诉管理系统</p>
                    </div>
                    
                    <div class="login-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label">用户名</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="username" class="form-control" placeholder="请输入用户名" required autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">密码</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="请输入密码" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-login w-100">
                                    <i class="bi bi-box-arrow-in-right"></i> 登录
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <small class="text-muted">默认账号：admin / admin123</small>
                            </div>
                        </form>
                    </div>
                    
                    <div class="login-footer">
                        <small>© 2024 投诉建议管理系统 - 超级管理后台</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>