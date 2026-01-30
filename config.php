<?php
// 数据库配置 - 请根据宝塔面板的实际信息修改
$db_host = 'localhost';  // 宝塔中通常是 localhost 或 127.0.0.1
$db_name = 'tswj.dmykj.cn';  // 数据库名
$db_user = 'tswj.dmykj.cn';  // 数据库用户名
$db_pass = 'tZr3Z3ZKKRyRp38G';  // 数据库密码，如果不对请修改

// 如果上述密码不对，请使用宝塔面板中显示的实际密码：
// 可能的正确密码可能是：tZr3Z3ZKKRyRp38G 或其他
// 建议从宝塔面板复制粘贴，避免输入错误

// 连接数据库
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 网站配置
$site_url = 'http://tswj.dmykj.cn';
$admin_path = '/admin';
$superadmin_path = '/superadmin';
$per_page = 20;  // 默认每页显示条数

// 开始会话
session_start();

// 检查管理员登录
function check_admin_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit();
    }
}

// 获取安全的输入
function safe_input($data) {
    if (is_array($data)) {
        return array_map('safe_input', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// 跳转函数
function redirect($url) {
    header("Location: $url");
    exit();
}

// 输出消息
function show_message($type, $message) {
    $_SESSION["{$type}_message"] = $message;
}
?>