<?php
require_once '../config.php';
check_admin_login();

$sql = "SELECT config_value FROM system_config WHERE config_key = 'custom2_title'";
$title = $pdo->query($sql)->fetchColumn() ?: '自定义管理2';
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
        <p>这是第二个自定义管理模块。</p>
    </div>
</body>
</html>