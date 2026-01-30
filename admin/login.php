<?php
// admin/login.php - 修复后的登录页面

// 开启所有错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// 开始会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 如果已登录，跳转到后台
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// 引入配置文件
$config_file = dirname(__DIR__) . '/config.php';
if (!file_exists($config_file)) {
    die('配置文件 config.php 不存在，请检查！');
}

require_once $config_file;

// 处理登录
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // 简单验证
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } else {
        try {
            // 查询用户
            $sql = "SELECT * FROM admin_users WHERE username = ? AND status = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                // 验证密码
                if (password_verify($password, $user['password'])) {
                    // 登录成功
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['store_id'] = $user['store_id'];
                    
                    // 跳转到后台
                    header('Location: index.php');
                    exit;
                } else {
                    $error = '密码错误';
                }
            } else {
                $error = '用户名不存在或已被禁用';
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $error = '登录出错: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 - 投诉管理系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Microsoft YaHei', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        
        .login-box {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }
        
        .demo-credentials h4 {
            margin-bottom: 10px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <h1>🔐 投诉管理系统</h1>
                <p>管理员登录</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="username" class="form-control" 
                           placeholder="用户名" required>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" class="form-control" 
                           placeholder="密码" required>
                </div>
                
                <button type="submit" class="btn-login">登录后台</button>
            </form>
            
            <div class="footer">
                <p>© 2024 超市投诉管理系统</p>
            </div>
        </div>
    </div>
</body>
</html>