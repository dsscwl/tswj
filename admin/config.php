<?php
require_once '../config.php';
require_once 'get_config.php';  // 新增这行
check_admin_login();

// 获取所有配置
$sql = "SELECT * FROM system_config ORDER BY config_group, id";
$configs = $pdo->query($sql)->fetchAll();

// 更新配置
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    foreach ($_POST['config'] as $key => $value) {
        $value = safe_input($value);
        $sql = "UPDATE system_config SET config_value = ? WHERE config_key = ? AND is_editable = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$value, $key]);
    }
    $_SESSION['success_message'] = '配置已保存';
    header('Location: config.php');
    exit;
}

// 添加新配置
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $key = safe_input($_POST['new_key']);
    $value = safe_input($_POST['new_value']);
    $name = safe_input($_POST['new_name']);
    $group = safe_input($_POST['new_group']);
    
    $sql = "INSERT INTO system_config (config_key, config_value, config_name, config_group) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$key, $value, $name, $group]);
    
    $_SESSION['success_message'] = '配置已添加';
    header('Location: config.php');
    exit;
}

// 删除配置
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM system_config WHERE id = ? AND is_editable = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    $_SESSION['success_message'] = '配置已删除';
    header('Location: config.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统配置 - <?php echo get_config('site_title', '投诉建议管理系统'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php"><?php echo get_config('site_title', '投诉建议管理系统'); ?></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">投诉建议</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="stores.php"><?php echo get_config('store_title', '门店管理'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php"><?php echo get_config('category_title', '分类管理'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="custom1.php"><?php echo get_config('custom1_title', '自定义管理1'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="custom2.php"><?php echo get_config('custom2_title', '自定义管理2'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="config.php">系统配置</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">系统配置管理</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="save">
                            
                            <?php 
                            // 按分组显示配置
                            $groups = [];
                            foreach ($configs as $config) {
                                $groups[$config['config_group']][] = $config;
                            }
                            
                            foreach ($groups as $group_name => $group_configs): 
                            ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <?php 
                                        $group_titles = [
                                            'site' => '网站配置',
                                            'menu' => '菜单标题配置',
                                            'system' => '系统配置',
                                            'general' => '通用配置'
                                        ];
                                        echo $group_titles[$group_name] ?? ucfirst($group_name);
                                        ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($group_configs as $config): ?>
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label">
                                                <?php echo $config['config_name']; ?>
                                                <?php if (!$config['is_editable']): ?>
                                                <span class="badge bg-secondary ms-2">只读</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" 
                                                   name="config[<?php echo $config['config_key']; ?>]" 
                                                   value="<?php echo htmlspecialchars($config['config_value']); ?>"
                                                   <?php echo $config['is_editable'] ? '' : 'readonly'; ?>>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">键：<?php echo $config['config_key']; ?></small>
                                            <?php if ($config['is_editable'] && $group_name != 'menu'): ?>
                                            <a href="?delete=<?php echo $config['id']; ?>" class="btn btn-sm btn-danger ms-2" 
                                               onclick="return confirm('确定要删除这个配置吗？')">
                                                删除
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">保存所有配置</button>
                                <a href="index.php" class="btn btn-secondary">返回</a>
                            </div>
                        </form>
                        
                        <!-- 添加新配置 -->
                        <hr>
                        <h5>添加新配置</h5>
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="add">
                            <div class="col-md-3">
                                <label class="form-label">配置键 *</label>
                                <input type="text" name="new_key" class="form-control" required>
                                <small class="text-muted">英文小写，如：custom3_title</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">配置名称 *</label>
                                <input type="text" name="new_name" class="form-control" required>
                                <small class="text-muted">中文名称，如：自定义管理3</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">配置值 *</label>
                                <input type="text" name="new_value" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">分组</label>
                                <select name="new_group" class="form-select">
                                    <option value="site">网站配置</option>
                                    <option value="menu">菜单配置</option>
                                    <option value="system">系统配置</option>
                                    <option value="general">通用配置</option>
                                    <option value="custom">自定义配置</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">添加配置</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>