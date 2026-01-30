<?php
require_once 'config.php';

// 检查是否已安装
$check_sql = "SHOW TABLES LIKE 'super_admins'";
$check = $pdo->query($check_sql)->fetchColumn();

if ($check) {
    die('超级管理员系统已经安装！');
}

// 创建超级管理员表
$sqls = [
    // 超级管理员表
    "CREATE TABLE super_admins (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        realname VARCHAR(50),
        email VARCHAR(100),
        role ENUM('superadmin', 'admin') DEFAULT 'admin',
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login DATETIME,
        login_ip VARCHAR(50),
        permissions JSON,
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // 操作日志表
    "CREATE TABLE superadmin_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT NOT NULL,
        admin_name VARCHAR(50) NOT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        ip VARCHAR(50),
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_id (admin_id),
        INDEX idx_create_time (create_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // 品牌表（如果不存在）
    "CREATE TABLE IF NOT EXISTS brands (
        id INT PRIMARY KEY AUTO_INCREMENT,
        brand_name VARCHAR(100) NOT NULL COMMENT '品牌名称',
        brand_code VARCHAR(50) UNIQUE NOT NULL COMMENT '品牌代码',
        contact_person VARCHAR(50) COMMENT '联系人',
        contact_phone VARCHAR(20) COMMENT '联系电话',
        contact_email VARCHAR(100) COMMENT '联系邮箱',
        status ENUM('active', 'inactive') DEFAULT 'active' COMMENT '状态',
        max_stores INT DEFAULT 10 COMMENT '最大门店数',
        max_users INT DEFAULT 5 COMMENT '最大用户数',
        expire_date DATE COMMENT '过期时间',
        settings JSON COMMENT '品牌设置',
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_brand_code (brand_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // 品牌用户表（如果不存在）
    "CREATE TABLE IF NOT EXISTS brand_users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        brand_id INT NOT NULL COMMENT '品牌ID',
        username VARCHAR(50) NOT NULL COMMENT '用户名',
        password VARCHAR(255) NOT NULL COMMENT '密码',
        realname VARCHAR(50) COMMENT '真实姓名',
        role ENUM('admin', 'manager', 'staff') DEFAULT 'staff' COMMENT '角色',
        permissions JSON COMMENT '权限设置',
        status ENUM('active', 'inactive') DEFAULT 'active' COMMENT '状态',
        last_login DATETIME COMMENT '最后登录时间',
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_brand_id (brand_id),
        INDEX idx_username (username),
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // 插入默认超级管理员（密码：admin123）
    "INSERT INTO super_admins (username, password, realname, email, role, status) VALUES 
    ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', 'admin@example.com', 'superadmin', 'active');"
];

echo "<h2>超级管理员系统安装</h2>";
echo "<pre>";

foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ 执行成功\n";
    } catch (Exception $e) {
        echo "✗ 执行失败: " . $e->getMessage() . "\n";
    }
}

echo "</pre>";
echo "<h3>安装完成！</h3>";
echo "<p>默认登录账号：admin / admin123</p>";
echo '<a href="login.php" class="btn btn-primary">前往登录</a>';
?>