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

// ============ 获取动态配置 ============
$page_config_sql = "SELECT config_value FROM page_configs WHERE config_key = ?";
$page_stmt = $conn->prepare($page_config_sql);

$page_key = 'page_title_tags';
$page_stmt->bind_param("s", $page_key);
$page_stmt->execute();
$page_result = $page_stmt->get_result();
$page_config = $page_result->fetch_assoc();
$page_title = $page_config['config_value'] ?? '问题标签管理';

$header_key = 'table_header_tag_name';
$page_stmt->bind_param("s", $header_key);
$page_stmt->execute();
$header_result = $page_stmt->get_result();
$header_config = $header_result->fetch_assoc();
$table_header = $header_config['config_value'] ?? '标签名称';
// ============ 动态配置结束 ============

// 处理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_tag') {
        $tag_name = trim($_POST['tag_name']);
        $description = trim($_POST['description'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        if (!empty($tag_name)) {
            // 创建problem_tags表（如果不存在）
            $check_table = "SHOW TABLES LIKE 'problem_tags'";
            $table_exists = $conn->query($check_table)->num_rows > 0;
            
            if (!$table_exists) {
                $create_table = "CREATE TABLE problem_tags (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tag_name VARCHAR(100) NOT NULL,
                    description VARCHAR(255),
                    sort_order INT DEFAULT 0,
                    status TINYINT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $conn->query($create_table);
            }
            
            $sql = "INSERT INTO problem_tags (tag_name, description, sort_order) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $tag_name, $description, $sort_order);
            $stmt->execute();
            $message = "标签添加成功！";
        }
    } elseif ($_POST['action'] == 'delete_tag') {
        $id = intval($_POST['tag_id']);
        
        $sql = "DELETE FROM problem_tags WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = "标签删除成功！";
        
    } elseif ($_POST['action'] == 'update_tag') {
        $id = intval($_POST['tag_id']);
        $tag_name = trim($_POST['tag_name']);
        $description = trim($_POST['description'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $status = intval($_POST['status']);
        
        $sql = "UPDATE problem_tags SET tag_name = ?, description = ?, sort_order = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiii", $tag_name, $description, $sort_order, $status, $id);
        $stmt->execute();
        $message = "标签更新成功！";
    }
}

// 获取标签列表（检查表是否存在）
$check_table = "SHOW TABLES LIKE 'problem_tags'";
$table_exists = $conn->query($check_table)->num_rows > 0;

if ($table_exists) {
    $result = $conn->query("SELECT * FROM problem_tags ORDER BY sort_order, created_at DESC");
} else {
    $result = false;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - 投诉系统</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial; background: #f5f5f5; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 25px; border-radius: 15px; margin-bottom: 20px; }
        .card { background: white; padding: 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        .btn { padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .form-control { padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 100%; font-size: 14px; }
        .message { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .success-message { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-row .form-group { flex: 1; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-active { background: #e6f7ed; color: #27ae60; }
        .status-inactive { background: #ffeaea; color: #e74c3c; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tags"></i> <?php echo htmlspecialchars($page_title); ?></h1>
            <p>管理问题标签，用于更细致的分类</p>
            <a href="index.php" style="color: #3498db; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> 返回首页
            </a>
        </div>
        
        <?php if (isset($message)): ?>
        <div class="message success-message">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!$table_exists): ?>
        <div class="message success-message">
            <i class="fas fa-info-circle"></i> 标签表不存在，系统将自动创建。请继续添加标签。
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2><i class="fas fa-plus-circle"></i> 添加新标签</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_tag">
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo htmlspecialchars($table_header); ?> *</label>
                        <input type="text" name="tag_name" class="form-control" placeholder="例如：产品质量问题" required>
                    </div>
                    <div class="form-group">
                        <label>排序序号</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>标签描述</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="标签描述..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 添加标签
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-list"></i> 标签列表</h2>
            <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo htmlspecialchars($table_header); ?></th>
                        <th>描述</th>
                        <th>排序</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                <input type="hidden" name="action" value="update_tag">
                                <input type="hidden" name="tag_id" value="<?php echo $row['id']; ?>">
                                <input type="text" name="tag_name" value="<?php echo htmlspecialchars($row['tag_name']); ?>" 
                                       class="form-control" style="width: 200px;" required>
                        </td>
                        <td>
                            <input type="text" name="description" value="<?php echo htmlspecialchars($row['description'] ?? ''); ?>" 
                                   class="form-control" style="width: 250px;">
                        </td>
                        <td>
                            <input type="number" name="sort_order" value="<?php echo $row['sort_order']; ?>" 
                                   class="form-control" style="width: 80px;" min="0">
                        </td>
                        <td>
                            <select name="status" class="form-control" style="width: 90px;">
                                <option value="1" <?php echo $row['status'] == 1 ? 'selected' : ''; ?>>启用</option>
                                <option value="0" <?php echo $row['status'] == 0 ? 'selected' : ''; ?>>禁用</option>
                            </select>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                        <td style="display: flex; gap: 5px;">
                            <button type="submit" class="btn btn-primary" title="更新">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('确定要删除这个标签吗？')">
                                <input type="hidden" name="action" value="delete_tag">
                                <input type="hidden" name="tag_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger" title="删除">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #999; padding: 30px;">暂无标签数据，请添加标签</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>