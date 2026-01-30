<?php
require_once 'config.php';

// 记录退出日志
if (isset($_SESSION['superadmin_username'])) {
    log_action('退出系统', "用户 {$_SESSION['superadmin_username']} 退出登录");
}

// 清除所有会话变量
$_SESSION = array();

// 销毁会话
session_destroy();

// 跳转到登录页面
header('Location: login.php');
exit();
?>