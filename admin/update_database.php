<?php
require_once '../config.php';

// 执行数据库更新
$updates = [
    // 确保 system_config 表存在
    "CREATE TABLE IF NOT EXISTS system_config (
        id INT PRIMARY KEY AUTO_INCREMENT,
        config_key VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
        config_value TEXT COMMENT '配置值',
        config_name VARCHAR(100) COMMENT '配置名称',
        config_group VARCHAR(50) DEFAULT 'general' COMMENT '配置分组',
        is_editable TINYINT DEFAULT 1 COMMENT '是否可编辑',
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // 插入默认配置
    "INSERT IGNORE INTO system_config (config_key, config_value, config_name, config_group) VALUES
    ('site_title', '投诉建议管理系统', '网站标题', 'site'),
    ('store_title', '门店管理', '门店管理标题', 'menu'),
    ('category_title', '分类管理', '分类管理标题', 'menu'),
    ('custom1_title', '自定义管理1', '自定义管理1标题', 'menu'),
    ('custom2_title', '自定义管理2', '自定义管理2标题', 'menu');",
    
    // 创建投诉处理记录表
    "CREATE TABLE IF NOT EXISTS complaint_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        complaint_id INT NOT NULL,
        operator VARCHAR(100) NOT NULL COMMENT '处理人',
        content TEXT NOT NULL COMMENT '处理内容',
        attachment VARCHAR(255) COMMENT '附件',
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_complaint_id (complaint_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // 添加排序字段到 categories 表
    "ALTER TABLE categories ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0 COMMENT '排序';",
    
    // 添加更新时间字段
    "ALTER TABLE stores ADD COLUMN IF NOT EXISTS update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;",
    "ALTER TABLE categories ADD COLUMN IF NOT EXISTS update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;",
];

echo "<h2>数据库更新</h2>";
echo "<pre>";

foreach ($updates as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ 执行成功: " . substr($sql, 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "✗ 执行失败: " . $e->getMessage() . "\n";
        echo "SQL: " . $sql . "\n";
    }
}

echo "</pre>";
echo "<h3>更新完成！</h3>";
echo '<a href="index.php">返回首页</a>';
?>