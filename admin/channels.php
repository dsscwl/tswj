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

// 处理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_channel') {
        $channel_name = trim($_POST['channel_name']);
        $channel_type = $_POST['channel_type'] ?? 'online';
        $description = trim($_POST['description'] ?? '');
        
        if (!empty($channel_name)) {
            $sql = "INSERT INTO feedback_channels (channel_name, channel_type, description) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $channel_name, $channel_type, $description);
            $stmt->execute();
            $message = "渠道添加成功！";
        }
    } elseif ($action == 'delete_channel') {
        $id = intval($_POST['channel_id']);
        
        $sql = "DELETE FROM feedback_channels WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = "渠道删除成功！";
        
    } elseif ($action == 'update_channel') {
        $id = intval($_POST['channel_id']);
        $channel_name = trim($_POST['channel_name']);
        $channel_type = $_POST['channel_type'] ?? 'online';
        $description = trim($_POST['description'] ?? '');
        $status = intval($_POST['status']);
        
        $sql = "UPDATE feedback_channels SET channel_name = ?, channel_type = ?, description = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $channel_name, $channel_type, $description, $status, $id);
        $stmt->execute();
        $message = "渠道更新成功！";
    }
}

// 获取渠道列表
$result = $conn->query("SELECT * FROM feedback_channels ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>反馈渠道管理 - 投诉系统</title>
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
        .form-control { padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 100%; font-size: 14px; }
        .message { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .success-message { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .form-group { margin-bottom: 15px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-row .form-group { flex: 1; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-active { background: #e6f7ed; color: #27ae60; }
        .status-inactive { background: #ffeaea; color: #e74c3c; }
        .channel-icon { margin-right: 8px; color: #3498db; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-broadcast-tower"></i> 反馈渠道管理</h1>
            <p>管理客户反馈的各种渠道</p>
            <a href="index.php" style="color: #3498db; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> 返回首页
            </a>
        </div>
        
        <?php if (isset($message)): ?>
        <div class="message success-message">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2><i class="fas fa-plus-circle"></i> 添加新渠道</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_channel">
                <div class="form-row">
                    <div class="form-group">
                        <label>渠道名称 *</label>
                        <input type="text" name="channel_name" class="form-control" placeholder="例如：微信小程序" required>
                    </div>
                    <div class="form-group">
                        <label>渠道类型</label>
                        <select name="channel_type" class="form-control">
                            <option value="online">线上渠道</option>
                            <option value="offline">线下渠道</option>
                            <option value="phone">电话渠道</option>
                            <option value="wechat">微信渠道</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>渠道描述</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="渠道描述..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 添加渠道
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-list"></i> 渠道列表</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>渠道名称</th>
                        <th>渠道类型</th>
                        <th>描述</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): 
                        $type_icons = [
                            'online' => '<i class="fas fa-globe channel-icon"></i>',
                            'offline' => '<i class="fas fa-store channel-icon"></i>',
                            'phone' => '<i class="fas fa-phone channel-icon"></i>',
                            'wechat' => '<i class="fab fa-weixin channel-icon"></i>'
                        ];
                        $type_names = [
                            'online' => '线上渠道',
                            'offline' => '线下渠道',
                            'phone' => '电话渠道',
                            'wechat' => '微信渠道'
                        ];
                    ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                <input type="hidden" name="action" value="update_channel">
                                <input type="hidden" name="channel_id" value="<?php echo $row['id']; ?>">
                                <?php echo $type_icons[$row['channel_type']] ?? ''; ?>
                                <input type="text" name="channel_name" value="<?php echo htmlspecialchars($row['channel_name']); ?>" 
                                       class="form-control" style="width: 150px;" required>
                        </td>
                        <td>
                            <select name="channel_type" class="form-control" style="width: 120px;">
                                <option value="online" <?php echo $row['channel_type'] == 'online' ? 'selected' : ''; ?>>线上渠道</option>
                                <option value="offline" <?php echo $row['channel_type'] == 'offline' ? 'selected' : ''; ?>>线下渠道</option>
                                <option value="phone" <?php echo $row['channel_type'] == 'phone' ? 'selected' : ''; ?>>电话渠道</option>
                                <option value="wechat" <?php echo $row['channel_type'] == 'wechat' ? 'selected' : ''; ?>>微信渠道</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="description" value="<?php echo htmlspecialchars($row['description'] ?? ''); ?>" 
                                   class="form-control" style="width: 200px;">
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
                            <form method="POST" style="display: inline;" onsubmit="return confirm('确定要删除这个渠道吗？')">
                                <input type="hidden" name="action" value="delete_channel">
                                <input type="hidden" name="channel_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger" title="删除">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>