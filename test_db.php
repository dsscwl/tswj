<?php
// 数据库连接测试
echo "<h2>数据库连接测试</h2>";

// 可能的数据库配置
$configs = [
    [
        'host' => 'localhost',
        'dbname' => 'tswj.dmykj.cn',
        'user' => 'tswj.dmykj.cn',
        'pass' => 'tZr3Z3ZKKRyRp38G'
    ],
    [
        'host' => '127.0.0.1',
        'dbname' => 'tswj.dmykj.cn',
        'user' => 'tswj.dmykj.cn',
        'pass' => 'tZr3Z3ZKKRyRp38G'
    ],
    // 可能密码不同
    [
        'host' => 'localhost',
        'dbname' => 'tswj.dmykj.cn',
        'user' => 'tswj.dmykj.cn',
        'pass' => ''  // 空密码试试
    ]
];

foreach ($configs as $config) {
    echo "<h3>测试配置：</h3>";
    echo "主机：{$config['host']}<br>";
    echo "数据库：{$config['dbname']}<br>";
    echo "用户：{$config['user']}<br>";
    
    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
            $config['user'],
            $config['pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<span style='color: green;'>✓ 连接成功！</span><br>";
        
        // 显示数据库信息
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "MySQL版本：{$version['version']}<br>";
        
        // 显示所有表
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "表数量：" . count($tables) . "<br>";
        echo "表列表：" . implode(', ', $tables) . "<br>";
        
        break; // 成功就停止
    } catch(PDOException $e) {
        echo "<span style='color: red;'>✗ 连接失败：{$e->getMessage()}</span><br>";
    }
    echo "<hr>";
}

// 测试环境信息
echo "<h3>服务器环境信息：</h3>";
echo "PHP版本：" . PHP_VERSION . "<br>";
echo "PDO是否启用：" . (extension_loaded('pdo') ? '是' : '否') . "<br>";
echo "PDO MySQL是否启用：" . (extension_loaded('pdo_mysql') ? '是' : '否') . "<br>";
echo "服务器软件：" . ($_SERVER['SERVER_SOFTWARE'] ?? '未知') . "<br>";
?>