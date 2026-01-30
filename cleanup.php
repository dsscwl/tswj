<?php
// cleanup.php - 安全清理工具（使用后删除此文件）
echo "<h2>安全清理工具</h2>";

$dangerous_files = [
    'direct_login.php',
    'emergency_login.php', 
    'force_login.php',
    'reset_password.php',
    'change_password.php',
    'fix_admin.php',
    'debug_password.php',
    'test_login.php',
    'check_admin.php',
    'generator.php',
    'complete_admin.php',
    'setup.sh',
    'deploy.sh',
    'fix_sidebar.php',
    'emergency_login.php',
    'cleanup.php'  // 这个文件本身也要删除
];

echo "<h3>将删除以下文件：</h3>";
echo "<ul>";

foreach ($dangerous_files as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<li style='color:green;'>✅ 已删除：{$file}</li>";
        } else {
            echo "<li style='color:red;'>❌ 删除失败：{$file}</li>";
        }
    } else {
        echo "<li>📭 不存在：{$file}</li>";
    }
}

echo "</ul>";

// 检查剩余文件
echo "<h3>建议保留的文件：</h3>";
$important_files = [
    'index.php' => '消费者提交页面',
    'submit.php' => '数据提交处理',
    'config.php' => '配置文件',
    'admin/login.php' => '后台登录',
    'admin/index.php' => '后台主页',
    'admin/detail.php' => '投诉详情',
    'admin/stores.php' => '门店管理',
    'admin/categories.php' => '分类管理',
    'admin/admins.php' => '管理员管理',
];

foreach ($important_files as $file => $desc) {
    if (file_exists($file)) {
        echo "<p>✅ {$file} - {$desc}</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ 缺失：{$file} - {$desc}</p>";
    }
}

echo "<hr>";
echo "<h3>安全建议：</h3>";
echo "<ol>";
echo "<li>修改config.php中的数据库密码</li>";
echo "<li>修改admin/login.php中的登录逻辑</li>";
echo "<li>修改admin_users表中的默认密码</li>";
echo "<li>设置宝塔面板防火墙</li>";
echo "<li>定期备份数据库</li>";
echo "</ol>";

echo "<p><strong>⚠️ 重要：清理完成后请立即删除此文件！</strong></p>";
?>