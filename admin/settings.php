<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] != 'super_admin') {
    header('Location: index.php');
    exit;
}

// 处理保存配置
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configs = $_POST['configs'] ?? [];
    
    foreach ($configs as $key => $value) {
        $value = trim($value);
        
        // 检查配置是否存在
        $check_sql = "SELECT id FROM page_configs WHERE config_key = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $key);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // 更新配置
            $update_sql = "UPDATE page_configs SET config_value = ? WHERE config_key = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $value, $key);
            $update_stmt->execute();
        } else {
            // 插入新配置
            $insert_sql = "INSERT INTO page_configs (config_key, config_value) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ss", $key, $value);
            $insert_stmt->execute();
        }
    }
    
    $message = "配置保存成功！";
}

// 获取所有配置
$configs_result = $conn->query("SELECT config_key, config_value, description FROM page_configs ORDER BY config_key");
$configs = [];
while ($row = $configs_result->fetch_assoc()) {
    $configs[$row['config_key']] = $row;
}

// 预定义可配置的页面和表头
$page_configurations = [
    '门店管理' => [
        'page_title_stores' => '页面标题',
        'table_header_store_name' => '门店名称表头',
        'table_header_store_address' => '门店地址表头',
        'table_header_store_phone' => '联系电话表头'
    ],
    '分类管理' => [
        'page_title_categories' => '页面标题',
        'table_header_category_name' => '分类名称表头'
    ],
    '问题标签' => [
        'page_title_tags' => '页面标题',
        'table_header_tag_name' => '标签名称表头'
    ],
    '反馈渠道' => [
        'page_title_channels' => '页面标题',
        'table_header_channel_name' => '渠道名称表头'
    ],
    '系统设置' => [
        'system_name' => '系统名称',
        'company_name' => '公司名称',
        'customer_service_phone' => '客服电话'
    ]
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - 投诉系统</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial; background: #f5f5f5; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 25px; border-radius: 15px; margin-bottom: 20px; }
        .card { background: white; padding: 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .message { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .success-message { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .config-table { width: 100%; border-collapse: collapse; }
        .config-table th, .config-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .config-table th { background: #f8f9fa; font-weight: 600; }
        .config-key { color: #666; font-size: 13px; }
        .config-input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .section-title { color: #3498db; padding-bottom: 10px; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-save { background: #2ecc71; color: white; margin-top: 20px; }
        .description { color: #999; font-size: 12px; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cog"></i> 系统设置</h1>
            <p>自定义系统页面标题和表头显示</p>
            <a href="index.php" style="color: #3498db; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> 返回首页
            </a>
        </div>
        
        <?php if (isset($message)): ?>
        <div class="message success-message">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php foreach ($page_configurations as $section => $fields): ?>
            <div class="card">
                <h2 class="section-title"><?php echo $section; ?>配置</h2>
                <table class="config-table">
                    <thead>
                        <tr>
                            <th width="30%">配置项</th>
                            <th width="40%">当前值</th>
                            <th width="30%">说明</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields as $key => $label): 
                            $config = $configs[$key] ?? [];
                            $value = $config['config_value'] ?? '';
                            $description = $config['description'] ?? '';
                        ?>
                        <tr>
                            <td>
                                <div class="config-key"><?php echo $label; ?></div>
                                <div style="font-size: 11px; color: #999;"><?php echo $key; ?></div>
                            </td>
                            <td>
                                <input type="text" name="configs[<?php echo $key; ?>]" 
                                       value="<?php echo htmlspecialchars($value); ?>" 
                                       class="config-input" placeholder="请输入<?php echo $label; ?>">
                            </td>
                            <td>
                                <div class="description"><?php echo $description; ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn btn-save">
                    <i class="fas fa-save"></i> 保存所有配置
                </button>
            </div>
        </form>
        
        <div class="card">
            <h2><i class="fas fa-info-circle"></i> 使用说明</h2>
            <ul style="color: #666; line-height: 1.6;">
                <li>修改配置后，需要刷新对应页面才能生效</li>
                <li>系统名称和公司名称会显示在页面底部和登录页面</li>
                <li>表头配置会更新对应管理页面的表格列标题</li>
                <li>所有配置支持中英文和特殊字符</li>
            </ul>
        </div>
    </div>
</body>
</html>