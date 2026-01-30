<?php
// 数据库配置（使用相同的数据库）
$db_host = 'localhost';
$db_name = 'tswj.dmykj.cn';
$db_user = 'tswj.dmykj.cn';
$db_pass = 'tZr3Z3ZKKRyRp38G';

// 连接数据库
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 网站配置
$site_url = 'http://tswj.dmykj.cn';
$superadmin_path = '/superadmin';
$admin_path = '/admin';

// 开始会话
session_start();

// 检查超级管理员登录
function check_superadmin_login() {
    if (!isset($_SESSION['superadmin_logged_in']) || $_SESSION['superadmin_logged_in'] !== true) {
        header('Location: login.php');
        exit();
    }
}

// 获取超级管理员信息
function get_superadmin_info() {
    if (isset($_SESSION['superadmin_id'])) {
        global $pdo;
        $sql = "SELECT * FROM super_admins WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['superadmin_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

// 获取安全的输入
function safe_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// 记录操作日志
function log_action($action, $details = '') {
    global $pdo;
    $admin_id = $_SESSION['superadmin_id'] ?? 0;
    $admin_name = $_SESSION['superadmin_username'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $sql = "INSERT INTO superadmin_logs (admin_id, admin_name, action, details, ip, create_time) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admin_id, $admin_name, $action, $details, $ip]);
}

// 生成密码哈希
function generate_password_hash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// 验证密码
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// 生成品牌代码
function generate_brand_code($name) {
    // 移除特殊字符，只保留字母数字，转换为小写
    $code = preg_replace('/[^a-zA-Z0-9]/', '', $name);
    $code = strtolower($code);
    
    // 如果代码为空，使用随机字符串
    if (empty($code)) {
        $code = 'brand' . date('Ymd') . rand(100, 999);
    }
    
    // 检查是否已存在
    global $pdo;
    $check_sql = "SELECT COUNT(*) FROM brands WHERE brand_code = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$code]);
    $count = $check_stmt->fetchColumn();
    
    // 如果已存在，添加后缀
    if ($count > 0) {
        $code .= rand(100, 999);
    }
    
    return $code;
}
?>