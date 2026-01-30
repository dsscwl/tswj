<?php
require_once '../config.php';
check_admin_login();

// 获取配置的标题
$sql = "SELECT config_value FROM system_config WHERE config_key = 'custom1_title'";
$title = $pdo->query($sql)->fetchColumn() ?: '自定义管理1';

// 这里你可以根据需要创建其他管理功能
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2><?php echo $title; ?></h2>
        <p>这是一个自定义管理模块，你可以根据需要进行扩展。</p>
        <!-- 在这里添加你的自定义功能 -->
    </div>
</body>
</html>